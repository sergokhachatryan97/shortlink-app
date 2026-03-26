<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATING = 'validating';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'external_client_id',
        'service_id',
        'category_id',
        'link',
        'quantity',
        'charge',
        'currency',
        'status',
        'provider_order_id',
        'external_reference',
        'idempotency_key',
        'start_count',
        'remains',
        'error_code',
        'error_message',
        'custom_comments',
        'metadata',
    ];

    protected $casts = [
        'charge' => 'decimal:4',
        'custom_comments' => 'array',
        'metadata' => 'array',
    ];

    public function externalClient(): BelongsTo
    {
        return $this->belongsTo(ExternalClient::class, 'external_client_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ExternalService::class, 'service_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExternalCategory::class, 'category_id');
    }

    public function canBeCanceled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_VALIDATING,
            self::STATUS_ACCEPTED,
        ], true);
    }
}
