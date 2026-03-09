<?php

namespace App\Jobs;

use App\Models\ShortlinkLink;
use App\Models\UserSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expired = UserSubscription::where('status', 'active')
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($expired as $sub) {
            $sub->update(['status' => 'expired']);
            $count = ShortlinkLink::where('user_subscription_id', $sub->id)->count();
            ShortlinkLink::where('user_subscription_id', $sub->id)->delete();
            Log::info('Subscription expired: links removed', [
                'user_id' => $sub->user_id,
                'subscription_id' => $sub->id,
                'links_deleted' => $count,
            ]);
            // TODO: Notify user to download CSV before expiry (e.g. email or in-app)
        }
    }
}
