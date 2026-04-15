<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortlinkTransaction extends Model
{
    public const KIND_BALANCE_TOPUP = 'balance_topup';

    public const KIND_SHORTLINK_PAYMENT = 'shortlink_payment';

    public const KIND_SUBSCRIPTION = 'subscription';

    public const KIND_SUBSCRIPTION_UPGRADE = 'subscription_upgrade';

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
        'payment_kind',
        'result_links',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'result_links' => 'array',
    ];

    /**
     * Shortlink / paid-link flow: positive count and target URL.
     * Uses explicit payment_kind when set; otherwise legacy fields + sl- order heuristic.
     */
    public function isShortlinkPayment(): bool
    {
        if ($this->payment_kind === self::KIND_SHORTLINK_PAYMENT) {
            return true;
        }

        if (filled($this->payment_kind) && $this->payment_kind !== self::KIND_SHORTLINK_PAYMENT) {
            return false;
        }

        if (($this->count ?? 0) > 0 && filled($this->url)) {
            return true;
        }

        return str_starts_with((string) ($this->order_id ?? ''), 'sl-')
            && ($this->count ?? 0) > 0
            && filled($this->url);
    }

    /**
     * Balance top-up via Heleket / Tron (CoinRush).
     * Uses explicit payment_kind when set; otherwise legacy provider_ref *_topup + bal- heuristic
     * so commission still classifies after provider_ref is overwritten by gateway UUID.
     */
    public function isBalanceTopup(): bool
    {
        if ($this->payment_kind === self::KIND_BALANCE_TOPUP) {
            return true;
        }

        if (filled($this->payment_kind) && $this->payment_kind !== self::KIND_BALANCE_TOPUP) {
            return false;
        }

        if (str_starts_with((string) ($this->identifier ?? ''), 'user:')
            && str_contains((string) ($this->provider_ref ?? ''), '_topup')) {
            return true;
        }

        return str_starts_with((string) ($this->order_id ?? ''), 'bal-')
            && str_starts_with((string) ($this->identifier ?? ''), 'user:');
    }
}
