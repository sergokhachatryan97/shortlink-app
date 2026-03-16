<?php

namespace App\Http\Controllers;

use App\Mail\PartnerWithdrawalRequestMail;
use App\Models\PartnerCommissionPayout;
use App\Models\PartnerPayoutSetting;
use App\Services\PartnerActivationService;
use App\Services\PartnerCommissionService;
use App\Services\PayoutRouteResolver;
use App\Services\WalletValidationService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PartnerController extends Controller
{
    public function activate(Request $request, PartnerActivationService $activationService): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('auth.login')->with('error', 'Please sign in to become a partner.');
        }

        $activationService->activate($user);

        $redirect = $request->input('redirect') ?? $request->query('redirect') ?? route('partner.dashboard');

        return redirect($redirect)->with('success', 'You are now a partner! Share your referral link to earn commissions.');
    }

    public function dashboard(Request $request): \Illuminate\View\View|RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('auth.login');
        }

        $referralLink = $user->is_partner && $user->referral_code
            ? config('app.url') . '/r/' . $user->referral_code
            : null;

        $payoutSettings = $user->partnerPayoutSettings;
        $hasActivePayout = $payoutSettings->where('is_active', true)->whereNotNull('wallet_address')->where('wallet_address', '!=', '')->isNotEmpty();
        $commissionService = app(PartnerCommissionService::class);
        $commissionPercent = $user->is_partner ? $commissionService->getEffectiveCommissionPercent($user) : null;

        $availableWithdrawAmount = 0.0;
        $minWithdrawAmount = 100.0;
        $canRequestWithdrawal = false;
        $requestedWithdrawal = null;
        $lastPaidWithdrawal = null;
        $lastRejectedWithdrawal = null;
        if ($user->is_partner) {
            $availableWithdrawAmount = $commissionService->getAvailableWithdrawAmount($user);
            $minWithdrawAmount = $commissionService->getMinWithdrawAmount();
            $canRequestWithdrawal = $availableWithdrawAmount >= $minWithdrawAmount;
            $requested = PartnerCommissionPayout::where('partner_user_id', $user->id)
                ->where('status', PartnerCommissionPayout::STATUS_REQUESTED)
                ->orderByDesc('updated_at')
                ->get();
            if ($requested->isNotEmpty()) {
                $requestedWithdrawal = [
                    'amount' => $requested->sum(fn ($r) => (float) $r->commission_amount),
                    'requested_at' => $requested->first()->updated_at,
                ];
            }
            $lastPaid = PartnerCommissionPayout::where('partner_user_id', $user->id)
                ->where('status', PartnerCommissionPayout::STATUS_PAID)
                ->orderByDesc('updated_at')
                ->first();
            $lastPaidWithdrawal = $lastPaid ? [
                'amount' => (float) PartnerCommissionPayout::where('partner_user_id', $user->id)
                    ->where('status', PartnerCommissionPayout::STATUS_PAID)
                    ->where('updated_at', $lastPaid->updated_at->format('Y-m-d H:i:s'))
                    ->sum('commission_amount'),
                'completed_at' => $lastPaid->updated_at,
                'provider_transaction_id' => $lastPaid->provider_transaction_id,
            ] : null;
            $lastRejectedWithdrawal = PartnerCommissionPayout::where('partner_user_id', $user->id)
                ->where('status', PartnerCommissionPayout::STATUS_REJECTED)
                ->orderByDesc('updated_at')
                ->first();
        }

        return view('partner.dashboard', [
            'user' => $user,
            'referralLink' => $referralLink,
            'referralCode' => $user->referral_code,
            'payoutSettings' => $payoutSettings,
            'hasActivePayout' => $hasActivePayout,
            'referredCount' => $user->referredUsers()->count(),
            'commissionPercent' => $commissionPercent,
            'availableWithdrawAmount' => $availableWithdrawAmount,
            'minWithdrawAmount' => $minWithdrawAmount,
            'canRequestWithdrawal' => $canRequestWithdrawal,
            'requestedWithdrawal' => $requestedWithdrawal,
            'lastPaidWithdrawal' => $lastPaidWithdrawal,
            'lastRejectedWithdrawal' => $lastRejectedWithdrawal,
        ]);
    }

    public function submitWithdrawalRequest(
        Request $request,
        WalletValidationService $walletValidator,
        PartnerCommissionService $commissionService
    ): RedirectResponse {

        $user = Auth::user();
        if (!$user || !$user->is_partner) {
            return redirect()->route('partner.dashboard')->with('error', 'Partner mode required.');
        }

        $validated = $request->validate([
            'wallet_address' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $wallet = trim($validated['wallet_address']);
        $comment = trim($validated['message'] ?? '');

        try {
            $walletValidator->validateOrFail($wallet, 'USDT', 'TRC20');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('partner.dashboard')
                ->withErrors($e->errors())
                ->withInput()
                ->with('openWithdrawalModal', true);
        }

        $available = $commissionService->getAvailableWithdrawAmount($user);
        $min = $commissionService->getMinWithdrawAmount();

        if ($available < $min) {
            return redirect()->route('partner.dashboard')->with('error', 'Available commission is below the minimum withdrawal amount.');
        }

        $pendingIds = PartnerCommissionPayout::where('partner_user_id', $user->id)
            ->whereIn('status', [PartnerCommissionPayout::STATUS_PENDING, PartnerCommissionPayout::STATUS_REJECTED])
            ->pluck('id')
            ->all();

        if (empty($pendingIds)) {
            return redirect()->route('partner.dashboard')->with('error', 'No pending commission available for withdrawal.');
        }

        PartnerCommissionPayout::whereIn('id', $pendingIds)->update([
            'status' => PartnerCommissionPayout::STATUS_REQUESTED,
        ]);

        $managerEmail = config('app.support_email');
        if ($managerEmail) {
            Mail::to($managerEmail)->send(new PartnerWithdrawalRequestMail(
                $user,
                number_format($available, 2),
                $wallet,
                $comment
            ));
        }

        return redirect()->route('partner.dashboard')->with('success', 'Your withdrawal request has been sent to the manager.');
    }

    public function referralRedirect(string $code): RedirectResponse
    {
        $partner = User::where('referral_code', strtoupper($code))
            ->where('is_partner', true)
            ->first();

        if (!$partner) {
            return redirect()->route('shortlink.index')->with('info', 'Invalid referral link.');
        }

        session()->put('referral_code', $partner->referral_code);
        session()->put('referral_code_at', now()->timestamp);

        return redirect()
            ->route('auth.register')
            ->with('info', 'You were referred by a partner. Sign up to get started!')
            ->cookie('referral_code', $partner->referral_code, 60 * 24 * 30); // 30 days
    }

    public function updatePayoutSettings(Request $request, WalletValidationService $walletValidator, PayoutRouteResolver $routeResolver): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->is_partner) {
            return redirect()->route('partner.dashboard')->with('error', 'Partner mode required.');
        }

        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:heleket'],
            'currency' => ['required', 'string', 'max:20'],
            'network' => ['required', 'string', 'max:50'],
            'wallet_address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $provider = strtolower($validated['provider']);
        $currency = trim($validated['currency']);
        $network = trim($validated['network']);
        $wallet = trim($validated['wallet_address'] ?? '');

        if (empty($wallet)) {
            PartnerPayoutSetting::where('user_id', $user->id)
                ->where('provider', $provider)
                ->where('currency', $currency)
                ->where('network', $network)
                ->delete();
            return redirect()->route('partner.dashboard')->with('success', 'USDT wallet removed.');
        }

        if (!$routeResolver->isRouteAllowed($provider, $currency, $network)) {
            return redirect()->route('partner.dashboard')->with('error', 'Invalid payout route for this provider.');
        }

        $walletValidator->validateOrFail($wallet, $currency, $network);

        PartnerPayoutSetting::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
                'currency' => $currency,
                'network' => $network,
            ],
            [
                'wallet_address' => $wallet,
                'is_active' => $request->boolean('is_active', false),
            ]
        );

        return redirect()->route('partner.dashboard')->with('success', 'USDT wallet saved.');
    }
}
