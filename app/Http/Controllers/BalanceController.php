<?php

namespace App\Http\Controllers;

use App\Models\ShortlinkTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BalanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view('balance.index', [
            'balance' => $user->balance,
            'transactions' => ShortlinkTransaction::where('identifier', 'user:' . $user->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function tronTopupSuccess(Request $request): RedirectResponse
    {
        $orderId = $request->query('order_id');
        $user = Auth::user();
        if (!$user || !$orderId) {
            return redirect()->route('balance.index')->with('error', 'Invalid request');
        }

        $tx = ShortlinkTransaction::where('order_id', $orderId)
            ->where('identifier', 'user:' . $user->id)
            ->first();

        if (!$tx) {
            return redirect()->route('balance.index')->with('error', 'Transaction not found');
        }

        if (!str_contains($tx->provider_ref ?? '', 'credited')) {
            $tx->update(['status' => 'paid', 'provider_ref' => ($tx->provider_ref ?? '') . ':credited']);
            $user->increment('balance', $tx->amount);
        }

        return redirect()->route('balance.index')->with('success', 'Balance topped up: $' . number_format($tx->amount, 2));
    }

    public function prepareTopup(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate(['amount' => 'required|numeric|min:0.1|max:10000']);
        $amount = (float) $data['amount'];
        if ($amount < 0.10) {
            return response()->json(['error' => 'Minimum top-up is $0.10'], 400);
        }
        if ($amount > 10000) {
            return response()->json(['error' => 'Maximum top-up is $10,000'], 400);
        }

        $orderId = 'bal-' . uniqid();
        $user = Auth::user();

        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'pending',
            'identifier' => 'user:' . $user->id,
            'count' => 0,
            'url' => null,
            'provider_ref' => 'tron_topup',
        ]);

        $request->session()->put('balance_topup_order', $orderId);

        return response()->json(['order_id' => $orderId, 'amount' => $amount]);
    }
}
