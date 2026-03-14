<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerPayoutSetting extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'wallet_address',
        'currency',
        'network',
        'percent',
        'min_payout_amount',
        'is_active',
    ];

    protected $casts = [
        'percent' => 'decimal:2',
        'min_payout_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isHeleket(): bool
    {
        return strtolower($this->provider ?? '') === 'heleket';
    }

    public function isCoinrush(): bool
    {
        return strtolower($this->provider ?? '') === 'coinrush';
    }
}
