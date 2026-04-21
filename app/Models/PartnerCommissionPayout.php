<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerCommissionPayout extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REJECTED = 'rejected';

    /** Commission settled by crediting the partner's in-app USD balance. */
    public const STATUS_CREDITED_BALANCE = 'credited_balance';

    protected $fillable = [
        'source_user_id',
        'partner_user_id',
        'provider',
        'source_provider',
        'source_amount',
        'commission_percent',
        'commission_amount',
        'currency',
        'network',
        'wallet_address',
        'provider_transaction_id',
        'status',
        'error_message',
        'meta',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'source_amount' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    public function partnerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRequested(): bool
    {
        return $this->status === self::STATUS_REQUESTED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isSettled(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_FAILED, self::STATUS_REJECTED, self::STATUS_CREDITED_BALANCE], true);
    }

    public function isCreditedToBalance(): bool
    {
        return $this->status === self::STATUS_CREDITED_BALANCE;
    }

    /** Whether this record counts toward available withdrawal (pending only). */
    public function isAvailableForWithdrawal(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
