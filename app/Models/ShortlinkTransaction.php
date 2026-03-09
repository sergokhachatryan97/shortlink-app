<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortlinkTransaction extends Model
{
    protected $table = 'shortlink_transactions';

    protected $fillable = [
        'order_id',
        'amount',
        'currency',
        'status',
        'identifier',
        'count',
        'url',
        'provider_ref',
        'result_links',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'result_links' => 'array',
    ];

    public function isShortlinkPayment(): bool
    {
        return $this->count > 0 && $this->url;
    }

    public function isBalanceTopup(): bool
    {
        return str_starts_with($this->identifier ?? '', 'user:')
            && str_contains($this->provider_ref ?? '', '_topup');
    }
}
