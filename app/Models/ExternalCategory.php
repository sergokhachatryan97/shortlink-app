<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalCategory extends Model
{
    protected $table = 'external_categories';

    protected $fillable = [
        'name',
        'slug',
        'link_driver',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(ExternalService::class, 'category_id');
    }
}
