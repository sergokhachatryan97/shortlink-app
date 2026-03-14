<?php

namespace App\Http\Controllers;

use App\Models\PartnerPayoutSetting;
use App\Services\PartnerActivationService;
use App\Services\PayoutRouteResolver;
use App\Services\WalletValidationService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $commissionPercent = $user->is_partner ? app(\App\Services\PartnerCommissionService::class)->getEffectiveCommissionPercent($user) : null;

        return view('partner.dashboard', [
            'user' => $user,
            'referralLink' => $referralLink,
            'referralCode' => $user->referral_code,
            'payoutSettings' => $payoutSettings,
            'hasActivePayout' => $hasActivePayout,
            'referredCount' => $user->referredUsers()->count(),
            'commissionPercent' => $commissionPercent,
        ]);
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
