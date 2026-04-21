<?php

namespace App\Observers;

use App\Models\ShortlinkLink;
use App\Models\SiteStat;

class ShortlinkLinkObserver
{
    public function created(ShortlinkLink $shortlinkLink): void
    {
        if ($shortlinkLink->from_free_trial_quota) {
            return;
        }

        SiteStat::incrementLifetimeLinksGenerated(1);
    }
}
