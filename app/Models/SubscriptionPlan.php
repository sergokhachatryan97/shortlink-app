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
        'price_usd',
        'links_limit',
        'duration_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_usd' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'subscription_plan_id');
    }

    public function isUnlimited(): bool
    {
        return $this->links_limit === 0;
    }
}
