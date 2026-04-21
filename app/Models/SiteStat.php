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
        'free_trial_links_generated',
    ];

    protected $casts = [
        'lifetime_links_generated' => 'integer',
        'free_trial_links_generated' => 'integer',
    ];

    /**
     * Cached read: persisted non-trial links (site_stats) plus all free-trial quota generations.
     */
    public static function lifetimeLinksGenerated(): int
    {
        return (int) Cache::remember(self::LIFETIME_LINKS_CACHE_KEY, 86400, function () {
            $row = self::query()
                ->where('id', self::ROW_ID)
                ->first(['lifetime_links_generated', 'free_trial_links_generated']);
            if (! $row) {
                return 0;
            }

            return (int) $row->lifetime_links_generated + (int) ($row->free_trial_links_generated ?? 0);
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

    public static function incrementFreeTrialLinksGenerated(int $by = 1): void
    {
        if ($by < 1) {
            return;
        }

        DB::table('site_stats')
            ->where('id', self::ROW_ID)
            ->increment('free_trial_links_generated', $by);

        self::forgetLifetimeLinksCache();
    }
}
