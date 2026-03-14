<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class ReferralCodeGenerator
{
    private const LENGTH = 8;

    private const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function generate(): string
    {
        do {
            $code = $this->randomCode();
        } while ($this->exists($code));

        return $code;
    }

    private function randomCode(): string
    {
        $chars = self::CHARS;
        $code = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= $chars[random_int(0, $max)];
        }
        return $code;
    }

    private function exists(string $code): bool
    {
        return User::where('referral_code', $code)->exists();
    }
}
