<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class PartnerActivationService
{
    public function __construct(
        protected ReferralCodeGenerator $referralCodeGenerator
    ) {}

    public function activate(User $user): bool
    {
        if ($user->is_partner) {
            return true;
        }

        return DB::transaction(function () use ($user) {
            $user->update([
                'is_partner' => true,
                'referral_code' => $user->referral_code ?? $this->referralCodeGenerator->generate(),
            ]);

            return true;
        });
    }
}
