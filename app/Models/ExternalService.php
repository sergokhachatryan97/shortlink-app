<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalService extends Model
{
    protected $table = 'external_services';

    protected $fillable = [
        'category_id',
        'name',
        'is_active',
        'is_external_available',
        'min_quantity',
        'max_quantity',
        'rate',
        'unit',
        'requires_link',
        'requires_quantity',
        'requires_custom_comments',
        'rules',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_external_available' => 'boolean',
        'requires_link' => 'boolean',
        'requires_quantity' => 'boolean',
        'requires_custom_comments' => 'boolean',
        'rules' => 'array',
        'rate' => 'decimal:4',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExternalCategory::class, 'category_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'service_id');
    }
}
