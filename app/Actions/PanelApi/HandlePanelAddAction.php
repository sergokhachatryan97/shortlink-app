<?php

namespace App\Actions\PanelApi;

use App\Jobs\ProcessExternalOrderJob;
use App\Models\ExternalClient;
use App\Models\ExternalService;
use App\Models\Order;
use App\Models\ShortlinkSetting;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\PanelApi\PanelStatusMapper;
use App\Services\ShortenService;
use App\Services\ShortlinkEntitlementService;
use App\Support\MoneyDisplay;
use Illuminate\Support\Facades\DB;

class HandlePanelAddAction
{
    public function __construct(
        protected ShortenService $shortenService,
        protected PanelStatusMapper $statusMapper,
        protected ShortlinkEntitlementService $entitlement,
        protected BalanceService $balanceService
    ) {}

    public function execute(ExternalClient $client, array $payload): array
    {
        $serviceId = (int) ($payload['service'] ?? 0);
        $quantity = (int) ($payload['quantity'] ?? 0);
        $link = trim((string) ($payload['link'] ?? ''));

        if ($serviceId <= 0) {
            return ['error' => 'Service parameter is required'];
        }
        if ($quantity <= 0) {
            return ['error' => 'Quantity parameter is required'];
        }

        $service = ExternalService::query()
            ->where('id', $serviceId)
            ->where('is_active', true)
            ->where('is_external_available', true)
            ->first();

        if (! $service) {
            return ['error' => 'Service not found'];
        }

        if ($quantity < (int) $service->min_quantity || $quantity > (int) $service->max_quantity) {
            return ['error' => "Quantity must be between {$service->min_quantity} and {$service->max_quantity}"];
        }

        if ($service->requires_link && $link === '') {
            return ['error' => 'Link parameter is required'];
        }

        if ($link !== '' && filter_var($link, FILTER_VALIDATE_URL) === false) {
            return ['error' => 'Invalid link'];
        }

        $requestedQuantity = $quantity;
        $panelQuota = [
            'effective_quantity' => $requestedQuantity,
            'free_quantity' => 0,
            'paid_quantity' => $requestedQuantity,
            'user_subscription_id' => null,
        ];

        $clientIp = (string) (request()->ip() ?? '0.0.0.0');

        /** @var User|null $linkedUser */
        $linkedUser = null;
        if ($client->user_id) {
            $linkedUser = User::query()->find($client->user_id);
            if ($linkedUser) {
                $panelQuota = $this->entitlement->computePanelOrderQuota(
                    $linkedUser,
                    $requestedQuantity,
                    $clientIp
                );
                $quantity = $panelQuota['effective_quantity'];
            }
        }

        $freeTrialRemainingBeforeOrder = null;
        if ($linkedUser && $panelQuota['user_subscription_id'] === null) {
            $freeTrialRemainingBeforeOrder = $this->entitlement->getRemainingFreeTrial(
                $this->entitlement->identifierForUser($linkedUser),
                $clientIp
            );
        }

        if ($quantity < (int) $service->min_quantity) {
            $err = [
                'error' => sprintf(
                    'Your subscription or free allowance only allows %d more link(s) for this order; this service requires at least %d.',
                    $quantity,
                    $service->min_quantity
                ),
            ];
            $snap = $this->freeTrialSnapshot($panelQuota, $freeTrialRemainingBeforeOrder, null, $requestedQuantity);
            if ($snap !== null) {
                $err['free_trial'] = $snap;
            }
            if ($linkedUser) {
                $subSnap = $this->entitlement->subscriptionOrderSnapshot($linkedUser, $panelQuota, $requestedQuantity);
                if ($subSnap !== null) {
                    $err['subscription'] = $subSnap;
                }
            }

            return $this->withSubscriptionExpiredNotice($linkedUser, $err);
        }

        $paidQty = (int) $panelQuota['paid_quantity'];
        if ($linkedUser) {
            $charge = $this->entitlement->computeLinkedUserPaidCharge($paidQty);
        } else {
            $charge = $paidQty * (float) $service->rate;
        }

        $freeTrialForIncomplete = $this->freeTrialSnapshot($panelQuota, $freeTrialRemainingBeforeOrder, null, $requestedQuantity);

        $insufficientPayload = null;

        $order = DB::transaction(function () use ($client, $service, $quantity, $link, $charge, $panelQuota, &$insufficientPayload, $freeTrialForIncomplete, $clientIp) {
            $lockedClient = ExternalClient::query()->lockForUpdate()->find($client->id);
            if (! $lockedClient) {
                return null;
            }

            /** @var User|null $lockedUser */
            $lockedUser = null;
            if ($lockedClient->user_id) {
                $lockedUser = User::query()->lockForUpdate()->find($lockedClient->user_id);
            }

            $chargeNorm = $charge > 0
                ? $this->balanceService->normalizeAmount($charge)
                : $this->balanceService->normalizeAmount(0);

            $clientBalanceStr = $this->balanceService->normalizeAmount(
                $lockedClient->getRawOriginal('balance') ?? $lockedClient->balance
            );
            $effectiveBalanceStr = $clientBalanceStr;
            if ($lockedUser) {
                $userBalanceStr = $this->balanceService->normalizeAmount(
                    $lockedUser->getRawOriginal('balance') ?? $lockedUser->balance
                );
                $effectiveBalanceStr = bccomp($clientBalanceStr, $userBalanceStr, BalanceService::SCALE) <= 0
                    ? $clientBalanceStr
                    : $userBalanceStr;
            }

            if ($charge > 0 && bccomp($effectiveBalanceStr, $chargeNorm, BalanceService::SCALE) < 0) {
                $current = $effectiveBalanceStr;
                $missing = bcsub($chargeNorm, $current, BalanceService::SCALE);
                $insufficientPayload = [
                    'error' => 'Not enough balance. Please add balance.',
                    'required' => MoneyDisplay::plainDecimal($chargeNorm),
                    'current_balance' => MoneyDisplay::plainDecimal($current),
                    'missing' => MoneyDisplay::plainDecimal($missing),
                    'currency' => $lockedClient->currency,
                    'action' => 'add_balance',
                ];
                if ($freeTrialForIncomplete !== null) {
                    $insufficientPayload['free_trial'] = $freeTrialForIncomplete;
                }

                return false;
            }

            if ($charge > 0) {
                $this->balanceService->decrementBalance(ExternalClient::class, (int) $lockedClient->id, $chargeNorm);
                if ($lockedUser) {
                    $this->balanceService->decrementBalance(User::class, (int) $lockedUser->id, $chargeNorm);
                }
                $lockedClient->refresh();
                $lockedUser?->refresh();
            }

            return Order::query()->create([
                'external_client_id' => $lockedClient->id,
                'service_id' => $service->id,
                'category_id' => $service->category_id,
                'link' => $link !== '' ? $link : null,
                'quantity' => $quantity,
                'charge' => $charge,
                'currency' => $lockedClient->currency,
                'status' => Order::STATUS_VALIDATING,
                'metadata' => [
                    'panel_free_quantity' => (int) $panelQuota['free_quantity'],
                    'panel_paid_quantity' => (int) $panelQuota['paid_quantity'],
                    'panel_user_subscription_id' => $panelQuota['user_subscription_id'],
                    'panel_trial_quantity_this_order' => (int) ($panelQuota['trial_quantity_this_order'] ?? 0),
                    'panel_plan_quantity_this_order' => (int) ($panelQuota['plan_quantity_this_order'] ?? 0),
                    'panel_client_ip' => $clientIp,
                ],
            ]);
        });

        if ($order === false) {
            $payload = $insufficientPayload ?? ['error' => 'Not enough balance. Please add balance.'];
            if ($linkedUser) {
                $subSnap = $this->entitlement->subscriptionOrderSnapshot($linkedUser, $panelQuota, $requestedQuantity);
                if ($subSnap !== null) {
                    $payload['subscription'] = $subSnap;
                }
            }

            return $payload;
        }
        if (! $order) {
            return ['error' => 'Unable to create order'];
        }

        // Prefer synchronous generation for this project so API can return links immediately.
        if ($order->link) {
            try {
                $generatedLinks = $this->shortenService->shorten($order->link, $order->quantity);

                $order->update([
                    'status' => Order::STATUS_COMPLETED,
                    'start_count' => 0,
                    'remains' => 0,
                    'metadata' => array_merge($order->metadata ?? [], [
                        'generated_links' => $generatedLinks,
                        'original_link' => $order->link,
                    ]),
                ]);

                $order->refresh();
                $panelUser = null;
                $panelUserId = $order->externalClient?->user_id;
                if ($panelUserId) {
                    $panelUser = User::query()->find($panelUserId);
                    if ($panelUser) {
                        $this->entitlement->persistPanelOrderSideEffects(
                            $order,
                            $panelUser,
                            (string) request()->ip()
                        );
                    }
                }

                $success = [
                    'order' => $order->id,
                    'status' => $this->statusMapper->map($order->status),
                    'original_link' => $order->link,
                    'quantity' => (int) $order->quantity,
                    'charge' => MoneyDisplay::plainDecimal((float) $order->charge),
                    'currency' => $order->currency,
                    'generated_links' => $generatedLinks,
                ];
                if ($freeTrialRemainingBeforeOrder !== null && $panelUser) {
                    $remainingAfter = $this->entitlement->getRemainingFreeTrial(
                        $this->entitlement->identifierForUser($panelUser),
                        $clientIp
                    );
                    $snapshot = $this->freeTrialSnapshot($panelQuota, $freeTrialRemainingBeforeOrder, $remainingAfter, $requestedQuantity);
                    if ($snapshot !== null) {
                        $success['free_trial'] = $snapshot;
                    }
                }
                if ($panelUser) {
                    $subSnap = $this->entitlement->subscriptionOrderSnapshot($panelUser, $panelQuota, $requestedQuantity, true);
                    if ($subSnap !== null) {
                        $success['subscription'] = $subSnap;
                    }
                }

                return $this->withSubscriptionExpiredNotice($linkedUser, $success);
            } catch (\Throwable) {
                // Fallback to async flow when immediate generation is unavailable.
                ProcessExternalOrderJob::dispatch($order->id);
            }
        } else {
            ProcessExternalOrderJob::dispatch($order->id);
        }

        $pending = [
            'order' => $order->id,
            'status' => $this->statusMapper->map($order->status),
            'original_link' => $order->link,
            'quantity' => (int) $order->quantity,
            'charge' => MoneyDisplay::plainDecimal((float) $order->charge),
            'currency' => $order->currency,
        ];
        if ($freeTrialForIncomplete !== null) {
            $pending['free_trial'] = $freeTrialForIncomplete;
        }
        if ($linkedUser) {
            $subSnap = $this->entitlement->subscriptionOrderSnapshot($linkedUser, $panelQuota, $requestedQuantity);
            if ($subSnap !== null) {
                $pending['subscription'] = $subSnap;
            }
        }

        return $this->withSubscriptionExpiredNotice($linkedUser, $pending);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function withSubscriptionExpiredNotice(?User $linkedUser, array $payload): array
    {
        if (! $linkedUser) {
            return $payload;
        }

        $notice = $this->entitlement->expiredSubscriptionNotice($linkedUser);
        if ($notice !== null) {
            $payload['subscription_expired'] = $notice;
        }

        return $payload;
    }

    /**
     * Free-trial breakdown for linked accounts without an active subscription.
     *
     * @return array<string, mixed>|null
     */
    private function freeTrialSnapshot(array $panelQuota, ?int $remainingBefore, ?int $remainingAfter, int $requestedQuantity): ?array
    {
        if ($panelQuota['user_subscription_id'] !== null || $remainingBefore === null) {
            return null;
        }

        $effective = (int) ($panelQuota['effective_quantity'] ?? $requestedQuantity);

        $freePart = (int) $panelQuota['free_quantity'];
        $paidPart = (int) $panelQuota['paid_quantity'];
        $pricePerLink = (float) ShortlinkSetting::get('price_per_link', '0.01');

        $out = [
            'limit' => ShortlinkEntitlementService::FREE_TRIAL_LIMIT,
            'requested_quantity' => $requestedQuantity,
            'effective_quantity' => $effective,
            'remaining_before_order' => $remainingBefore,
            'quantity_beyond_free_trial_remaining' => max(0, $requestedQuantity - $remainingBefore),
            'requested_minus_effective' => max(0, $requestedQuantity - $effective),
            'applied_this_order' => $freePart,
            'paid_quantity_this_order' => $paidPart,
            'price_per_link_usd' => number_format($pricePerLink, 3, '.', ''),
            'overage_billed_from_balance' => $paidPart > 0,
        ];

        if ($paidPart > 0) {
            $out['notice'] = sprintf(
                '%d link(s) covered by free trial; %d link(s) charged from account balance at $%s per link (minimum charge rules match the website).',
                $freePart,
                $paidPart,
                number_format($pricePerLink, 3, '.', '')
            );
        } else {
            $out['notice'] = 'All links in this order are covered by your free trial; no balance charge.';
        }

        if ($remainingAfter !== null) {
            $out['remaining_after_order'] = $remainingAfter;
        }

        return $out;
    }
}
