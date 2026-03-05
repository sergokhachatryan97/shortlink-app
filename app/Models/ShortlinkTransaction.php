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
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
