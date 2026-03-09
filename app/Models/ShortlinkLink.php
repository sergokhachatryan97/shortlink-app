<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShortlinkLink extends Model
{
    protected $table = 'shortlink_links';

    protected $fillable = [
        'user_id',
        'user_subscription_id',
        'original_url',
        'short_url',
        'batch_index',
        'batch_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }
}
