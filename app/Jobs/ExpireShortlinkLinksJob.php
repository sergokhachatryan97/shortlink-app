<?php

namespace App\Jobs;

use App\Models\ShortlinkLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireShortlinkLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $deleted = ShortlinkLink::whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();

        if ($deleted > 0) {
            Log::info('Expired shortlink links removed', ['count' => $deleted]);
        }
    }
}
