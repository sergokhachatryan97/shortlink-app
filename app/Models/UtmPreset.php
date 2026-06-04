<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtmPreset extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
