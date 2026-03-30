<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalClient extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'api_key_hash',
        'api_key_encrypted',
        'is_active',
        'balance',
        'currency',
        'callback_url',
        'webhook_secret',
        'allowed_ips',
        'rate_limit_per_minute',
    ];

    protected $hidden = [
        'api_key_hash',
        'api_key_encrypted',
        'webhook_secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'balance' => 'decimal:6',
        'allowed_ips' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'external_client_id');
    }

    /**
     * Set linked external_clients.balance to the user's current wallet (Panel API uses both).
     * Call after crediting the user so top-ups are visible on /api/v2 balance.
     */
    public static function syncBalanceFromUserWallet(int $userId): void
    {
        $balance = User::query()->whereKey($userId)->value('balance');
        if ($balance === null) {
            return;
        }

        self::query()->where('user_id', $userId)->update(['balance' => $balance]);
    }
}
