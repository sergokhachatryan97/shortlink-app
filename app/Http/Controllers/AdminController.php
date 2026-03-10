<?php

namespace App\Http\Controllers;

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

        return view('admin.dashboard', [
            'transactions' => $transactions,
            'totalPaid' => $totalPaid,
            'pricePerLink' => ShortlinkSetting::get('price_per_link', '0.01'),
            'plans' => $plans,
            'users' => $users,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'price_per_link' => ['required', 'numeric', 'min:0.001', 'max:10'],
        ]);

        ShortlinkSetting::set('price_per_link', $validated['price_per_link']);

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
