<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'telegram_id',
        'avatar',
        'balance',
        'partner_id',
        'is_partner',
        'referral_code',
        'payout_provider',
        'commission_percent',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
            'is_partner' => 'boolean',
            'commission_percent' => 'decimal:2',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription(): ?UserSubscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->orderByDesc('ends_at')
            ->first();
    }

    public function lastExpiredSubscription(): ?UserSubscription
    {
        return $this->subscriptions()
            ->where(function ($q) {
                $q->where('status', '!=', 'active')
                    ->orWhere('ends_at', '<=', now());
            })
            ->orderByDesc('ends_at')
            ->first();
    }

    public function generatedLinks(): HasMany
    {
        return $this->hasMany(ShortlinkLink::class, 'user_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'partner_id');
    }

    public function partnerPayoutSettings(): HasMany
    {
        return $this->hasMany(PartnerPayoutSetting::class);
    }

    public function partnerCommissionsAsReceiver(): HasMany
    {
        return $this->hasMany(PartnerCommissionPayout::class, 'partner_user_id');
    }

    public function partnerCommissionsAsSource(): HasMany
    {
        return $this->hasMany(PartnerCommissionPayout::class, 'source_user_id');
    }

    public function activePartnerPayoutSetting(string $provider): ?PartnerPayoutSetting
    {
        return $this->partnerPayoutSettings()
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();
    }
}
