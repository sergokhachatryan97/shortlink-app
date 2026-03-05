<?php

namespace App\Http\Controllers;

use App\Models\ShortlinkSetting;
use App\Models\ShortlinkTransaction;
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

        return view('admin.dashboard', [
            'transactions' => $transactions,
            'totalPaid' => $totalPaid,
            'pricePerLink' => ShortlinkSetting::get('price_per_link', '0.01'),
            'minAmount' => ShortlinkSetting::get('min_amount', '0.10'),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'price_per_link' => ['required', 'numeric', 'min:0.001', 'max:10'],
            'min_amount' => ['required', 'numeric', 'min:0.01', 'max:1000'],
        ]);

        ShortlinkSetting::set('price_per_link', $validated['price_per_link']);
        ShortlinkSetting::set('min_amount', $validated['min_amount']);

        return back()->with('success', 'Settings updated.');
    }
}
