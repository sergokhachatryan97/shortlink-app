<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    public const DISCOUNT_FIXED = 'fixed';

    public const DISCOUNT_PERCENT = 'percent';

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'expires_at',
        'max_uses',
        'once_per_user',
        'first_purchase_only',
        'applies_to_plan_ids',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'max_uses' => 'integer',
            'once_per_user' => 'boolean',
            'first_purchase_only' => 'boolean',
            'applies_to_plan_ids' => 'array',
            'is_active' => 'boolean',
            'discount_value' => 'decimal:2',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function normalizeCode(?string $code): string
    {
        return strtoupper(trim((string) $code));
    }

    public function appliesToPlanId(int $planId): bool
    {
        $ids = $this->applies_to_plan_ids;
        if ($ids === null || $ids === []) {
            return true;
        }

        return in_array($planId, array_map('intval', $ids), true);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
