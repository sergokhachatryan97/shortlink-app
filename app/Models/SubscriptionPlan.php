<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'name_translations',
        'description_translations',
        'price_usd',
        'links_limit',
        'daily_links_limit',
        'duration_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_usd' => 'decimal:2',
        'is_active' => 'boolean',
        'name_translations' => 'array',
        'description_translations' => 'array',
        'daily_links_limit' => 'integer',
    ];

    /** Supported locales for plan name/description. */
    public static function translationLocales(): array
    {
        return ['en', 'zh', 'ru'];
    }

    /** Get plan name for the current (or given) locale. */
    public function getTranslatedName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $translations = $this->name_translations ?? [];
        if (isset($translations[$locale]) && trim((string) $translations[$locale]) !== '') {
            return trim((string) $translations[$locale]);
        }
        if (isset($translations['en']) && trim((string) $translations['en']) !== '') {
            return trim((string) $translations['en']);
        }

        return (string) ($this->getOriginal('name') ?? '');
    }

    /** Get plan description for the current (or given) locale. */
    public function getTranslatedDescription(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $translations = $this->description_translations ?? [];
        if (isset($translations[$locale]) && trim((string) $translations[$locale]) !== '') {
            return trim((string) $translations[$locale]);
        }
        if (isset($translations['en']) && trim((string) $translations['en']) !== '') {
            return trim((string) $translations['en']);
        }
        $raw = $this->getOriginal('description');

        return $raw !== null ? (string) $raw : null;
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'subscription_plan_id');
    }

    public function isUnlimited(): bool
    {
        return $this->links_limit === 0;
    }

    public function dailyPriceUsd(): float
    {
        $days = max(1, (int) ($this->duration_days ?? 0));
        $price = (float) ($this->price_usd ?? 0);

        return $price / $days;
    }

    /** Max subscription-attributed links per calendar day (app timezone). Null = no daily cap. */
    public function hasDailyLinksLimit(): bool
    {
        return $this->daily_links_limit !== null && (int) $this->daily_links_limit > 0;
    }

    public static function countSubscriptionLinksToday(int $userSubscriptionId): int
    {
        if ($userSubscriptionId <= 0) {
            return 0;
        }

        return (int) ShortlinkLink::query()
            ->where('user_subscription_id', $userSubscriptionId)
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }
}
