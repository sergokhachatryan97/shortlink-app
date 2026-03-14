<?php

namespace App\Http\Controllers;

use App\Models\PartnerCommissionPayout;
use App\Models\PartnerPayoutSetting;
use App\Models\ShortlinkSetting;
use App\Models\ShortlinkTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function loginForm()
    {
        if (session('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $password = config('services.admin.password', env('ADMIN_PASSWORD'));
        if (!$password) {
            return back()->with('error', 'Admin password not configured. Set ADMIN_PASSWORD in .env');
        }

        if ($request->password !== $password) {
            return back()->with('error', 'Invalid password');
        }

        session(['admin_logged_in' => true]);
        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_logged_in');
        return redirect()->route('admin.login');
    }

    public function dashboard()
    {
        $transactions = ShortlinkTransaction::orderByDesc('created_at')->paginate(20);
        $totalPaid = ShortlinkTransaction::where('status', 'paid')->sum('amount');
        $plans = SubscriptionPlan::orderBy('sort_order')->get();
        $users = User::orderByDesc('created_at')->paginate(15, ['*'], 'users_page');
        $partnerPayoutSettings = PartnerPayoutSetting::with('user')->get()->groupBy('user_id');
        $partnerPayouts = PartnerCommissionPayout::with(['sourceUser', 'partnerUser'])
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'partner_payouts_page');

        return view('admin.dashboard', [
            'transactions' => $transactions,
            'totalPaid' => $totalPaid,
            'pricePerLink' => ShortlinkSetting::get('price_per_link', '0.01'),
            'partnerDefaultPayoutProvider' => ShortlinkSetting::get('partner_default_payout_provider') ?? config('partner.default_payout_provider', 'heleket'),
            'partnerDefaultCommissionPercent' => ShortlinkSetting::get('partner_default_commission_percent') ?? '10',
            'plans' => $plans,
            'users' => $users,
            'partnerPayoutSettings' => $partnerPayoutSettings,
            'partnerPayouts' => $partnerPayouts,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'price_per_link' => ['required', 'numeric', 'min:0.001', 'max:10'],
            'partner_default_payout_provider' => ['nullable', 'string', 'in:heleket,coinrush'],
            'partner_default_commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        ShortlinkSetting::set('price_per_link', $validated['price_per_link']);
        ShortlinkSetting::set('partner_default_payout_provider', $validated['partner_default_payout_provider'] ?? config('partner.default_payout_provider', 'heleket'));
        ShortlinkSetting::set('partner_default_commission_percent', (string) ($validated['partner_default_commission_percent'] ?? 10));

        return redirect()->route('admin.dashboard', ['tab' => 'settings'])->with('success', 'Settings updated.');
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:500'],
            'links_limit' => ['required', 'integer', 'min:0'],
            'price_usd' => ['required', 'numeric', 'min:0', 'max:9999.99'],
        ]);

        $plan->update([
            'description' => $validated['description'] ?? '',
            'links_limit' => $validated['links_limit'],
            'price_usd' => $validated['price_usd'],
        ]);

        return redirect()->route('admin.dashboard', ['tab' => 'settings'])->with('success', 'Plan "' . $plan->name . '" updated.');
    }

    public function setUserPartner(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'partner_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $partnerId = !empty($validated['partner_id']) && (int) $validated['partner_id'] > 0
            ? (int) $validated['partner_id']
            : null;

        if ($partnerId && !User::where('id', $partnerId)->exists()) {
            return redirect()->route('admin.dashboard', ['tab' => 'users'])->with('error', 'Partner user not found.');
        }

        if ($partnerId && (int) $partnerId === (int) $user->id) {
            return redirect()->route('admin.dashboard', ['tab' => 'users'])->with('error', 'User cannot be their own partner.');
        }

        $user->update(['partner_id' => $partnerId]);

        return redirect()->route('admin.dashboard', ['tab' => 'users'])->with('success', 'Partner updated.');
    }

    public function setUserPayoutProvider(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'payout_provider' => ['nullable', 'string', 'in:heleket,coinrush'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $provider = !empty($validated['payout_provider']) ? $validated['payout_provider'] : null;

        $user->update(['payout_provider' => $provider]);

        $msg = $provider ? "Payout provider set to {$provider}." : 'Payout provider cleared (will use global default).';
        return redirect()->route('admin.dashboard', ['tab' => 'users'])->with('success', $msg);
    }

    public function setUserCommissionPercent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $percent = isset($validated['commission_percent']) && $validated['commission_percent'] !== ''
            ? (float) $validated['commission_percent']
            : null;

        $user->update(['commission_percent' => $percent]);

        $msg = $percent !== null ? "Commission set to {$percent}%." : 'Commission cleared (will use global default).';
        return redirect()->route('admin.dashboard', ['tab' => 'users'])->with('success', $msg);
    }

    public function savePartnerPayoutSetting(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'provider' => ['required', 'string', 'in:heleket,coinrush'],
            'wallet_address' => ['required', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:20'],
            'network' => ['nullable', 'string', 'max:50'],
            'percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        PartnerPayoutSetting::updateOrCreate(
            [
                'user_id' => $validated['user_id'],
                'provider' => $validated['provider'],
            ],
            [
                'wallet_address' => $validated['wallet_address'],
                'currency' => $validated['currency'] ?? 'USDT',
                'network' => $validated['network'] ?? null,
                'percent' => $validated['percent'] ?? 10,
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        return redirect()->route('admin.dashboard', ['tab' => 'users'])->with('success', 'Payout setting saved.');
    }

    public function addUserBalance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000'],
        ]);

        $user = User::where('email', $validated['user'])
            ->orWhere('id', (int) $validated['user'])
            ->first();

        if (!$user) {
            return back()->with('error', 'User not found.');
        }

        $amount = (float) $validated['amount'];
        $user->increment('balance', $amount);

        $tab = $request->input('tab', 'users');
        return redirect()->route('admin.dashboard', ['tab' => $tab])->with('success', 'Added $' . number_format($amount, 2) . ' to ' . ($user->email ?? 'user#' . $user->id) . '. New balance: $' . number_format($user->fresh()->balance, 2));
    }
}
