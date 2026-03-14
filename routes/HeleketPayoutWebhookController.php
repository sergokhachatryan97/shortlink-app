<?php

namespace App\Http\Controllers;

use App\Models\PartnerCommissionPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HeleketPayoutWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->all();

        Log::info('Heleket payout webhook', $data);

        $uuid = $data['uuid'] ?? null;
        $status = $data['status'] ?? null;

        if (!$uuid) {
            return response()->json(['ok' => false]);
        }

        $payouts = PartnerCommissionPayout::where('provider_transaction_id', $uuid)->get();

        foreach ($payouts as $payout) {

            if ($status === 'paid') {
                $payout->status = 'paid';
            }

            if ($status === 'failed') {
                $payout->status = 'failed';
                $payout->error_message = $data['message'] ?? 'Heleket payout failed';
            }

            $payout->save();
        }

        return response()->json(['ok' => true]);
    }
}
