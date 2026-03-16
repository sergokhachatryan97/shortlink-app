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
        'duration_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_usd' => 'decimal:2',
        'is_active' => 'boolean',
        'name_translations' => 'array',
        'description_translations' => 'array',
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
}
