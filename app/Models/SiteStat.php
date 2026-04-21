<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SiteStat extends Model
{
    public const ROW_ID = 1;

    public const LIFETIME_LINKS_CACHE_KEY = 'site_stats.lifetime_links_generated';

    protected $table = 'site_stats';

    public $timestamps = false;

    protected $fillable = [
        'lifetime_links_generated',
    ];

    protected $casts = [
        'lifetime_links_generated' => 'integer',
    ];

    /**
     * Sum of links issued under free-trial quota (guest session + persisted trial rows).
     */
    public static function totalFreeTrialLinksRecorded(): int
    {
        return (int) DB::table('shortlink_free_trial_uses')->sum('links_count');
    }

    /**
     * Cached read: persisted non-trial links (site_stats) plus all free-trial quota generations.
     */
    public static function lifetimeLinksGenerated(): int
    {
        return (int) Cache::remember(self::LIFETIME_LINKS_CACHE_KEY, 86400, function () {
            $site = (int) self::query()->where('id', self::ROW_ID)->value('lifetime_links_generated');

            return $site + self::totalFreeTrialLinksRecorded();
        });
    }

    public static function forgetLifetimeLinksCache(): void
    {
        Cache::forget(self::LIFETIME_LINKS_CACHE_KEY);
    }

    /**
     * Increment the all-time counter (survives row deletes). Safe to call after each persisted link.
     */
    public static function incrementLifetimeLinksGenerated(int $by = 1): void
    {
        if ($by < 1) {
            return;
        }

        DB::table('site_stats')
            ->where('id', self::ROW_ID)
            ->increment('lifetime_links_generated', $by);

        self::forgetLifetimeLinksCache();
    }
}
