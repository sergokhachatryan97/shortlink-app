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

        // Group requested withdrawals by partner for "Mark paid" / "Reject" actions
        $requestedWithdrawals = PartnerCommissionPayout::where('status', PartnerCommissionPayout::STATUS_REQUESTED)
            ->with('partnerUser')
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('partner_user_id')
            ->map(fn ($rows) => [
                'partner' => $rows->first()->partnerUser,
                'total' => $rows->sum(fn ($r) => (float) $r->commission_amount),
                'count' => $rows->count(),
                'wallet' => $rows->first()->wallet_address,
                'requested_at' => $rows->first()->updated_at,
                'ids' => $rows->pluck('id')->all(),
            ])
            ->values();

        return view('admin.dashboard', [
            'transactions' => $transactions,
            'totalPaid' => $totalPaid,
            'pricePerLink' => ShortlinkSetting::get('price_per_link', '0.01'),
            'partnerDefaultPayoutProvider' => ShortlinkSetting::get('partner_default_payout_provider') ?? config('partner.default_payout_provider', 'heleket'),
            'partnerDefaultCommissionPercent' => ShortlinkSetting::get('partner_default_commission_percent') ?? '10',
            'partnerMinPayoutAmount' => ShortlinkSetting::get('partner_min_payout_amount') ?? config('partner.default_min_payout_amount', 100),
            'plans' => $plans,
            'users' => $users,
            'partnerPayoutSettings' => $partnerPayoutSettings,
            'partnerPayouts' => $partnerPayouts,
            'requestedWithdrawals' => $requestedWithdrawals,
        ]);
    }

    /**
     * Mark a partner's requested withdrawal as paid (manager completed the payout manually).
     */
    public function markPartnerWithdrawalPaid(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'partner_user_id' => ['required', 'integer', 'exists:users,id'],
            'provider_transaction_id' => ['nullable', 'string', 'max:255'],
        ]);

        $updated = PartnerCommissionPayout::where('partner_user_id', $validated['partner_user_id'])
            ->where('status', PartnerCommissionPayout::STATUS_REQUESTED)
            ->update([
                'status' => PartnerCommissionPayout::STATUS_PAID,
                'provider_transaction_id' => $validated['provider_transaction_id'] ?? null,
                'error_message' => null,
            ]);

        if ($updated === 0) {
            return redirect()->route('admin.dashboard', ['tab' => 'partner-payouts'])->with('error', 'No requested withdrawal found for this partner.');
        }

        return redirect()->route('admin.dashboard', ['tab' => 'partner-payouts'])->with('success', "Marked {$updated} record(s) as paid.");
    }

    /**
     * Reject a partner's withdrawal request.
     */
    public function rejectPartnerWithdrawal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'partner_user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = PartnerCommissionPayout::where('partner_user_id', $validated['partner_user_id'])
            ->where('status', PartnerCommissionPayout::STATUS_REQUESTED)
            ->update([
                'status' => PartnerCommissionPayout::STATUS_PENDING,
                'error_message' => null,
            ]);

        if ($updated === 0) {
            return redirect()->route('admin.dashboard', ['tab' => 'partner-payouts'])->with('error', 'No requested withdrawal found for this partner.');
        }

        return redirect()->route('admin.dashboard', ['tab' => 'partner-payouts'])->with('success', "Rejected. {$updated} record(s) reverted to pending so the partner can submit a new request.");
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'price_per_link' => ['required', 'numeric', 'min:0.001', 'max:10'],
            'partner_default_payout_provider' => ['nullable', 'string', 'in:heleket'],
            'partner_default_commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'partner_min_payout_amount' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        ShortlinkSetting::set('price_per_link', $validated['price_per_link']);
        ShortlinkSetting::set('partner_default_payout_provider', $validated['partner_default_payout_provider'] ?? config('partner.default_payout_provider', 'heleket'));
        ShortlinkSetting::set('partner_default_commission_percent', (string) ($validated['partner_default_commission_percent'] ?? 10));
        ShortlinkSetting::set('partner_min_payout_amount', (string) ($validated['partner_min_payout_amount'] ?? config('partner.default_min_payout_amount', 100)));

        return redirect()->route('admin.dashboard', ['tab' => 'settings'])->with('success', 'Settings updated.');
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $locales = SubscriptionPlan::translationLocales();
        $rules = [
            'links_limit' => ['required', 'integer', 'min:0'],
            'price_usd' => ['required', 'numeric', 'min:0', 'max:9999.99'],
        ];
        foreach ($locales as $locale) {
            $rules["name_{$locale}"] = ['nullable', 'string', 'max:255'];
            $rules["description_{$locale}"] = ['nullable', 'string', 'max:1000'];
        }
        $validated = $request->validate($rules);

        $nameTranslations = [];
        $descriptionTranslations = [];
        foreach ($locales as $locale) {
            $nameTranslations[$locale] = trim($validated["name_{$locale}"] ?? '');
            $descriptionTranslations[$locale] = trim($validated["description_{$locale}"] ?? '');
        }
        $plan->update([
            'name_translations' => $nameTranslations,
            'description_translations' => $descriptionTranslations,
            'name' => $nameTranslations['en'] ?: $plan->name,
            'description' => $descriptionTranslations['en'] ?: $plan->description,
            'links_limit' => $validated['links_limit'],
            'price_usd' => $validated['price_usd'],
        ]);

        return redirect()->route('admin.dashboard', ['tab' => 'settings'])->with('success', 'Plan updated.');
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
            'payout_provider' => ['nullable', 'string', 'in:heleket'],
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
