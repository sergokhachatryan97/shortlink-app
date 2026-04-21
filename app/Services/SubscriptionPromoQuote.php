<?php

namespace App\Services;

use App\Models\PromoCode;

/**
 * Result of evaluating a promo code against a subscription price (preview or checkout).
 */
final class SubscriptionPromoQuote
{
    private function __construct(
        public readonly bool $valid,
        public readonly ?string $errorCode,
        public readonly ?PromoCode $promoCode,
        public readonly float $originalAmount,
        public readonly float $discountAmount,
        public readonly float $finalAmount,
    ) {}

    public static function noPromo(float $base): self
    {
        return new self(true, null, null, $base, 0.0, $base);
    }

    public static function failure(string $errorCode, float $base): self
    {
        return new self(false, $errorCode, null, $base, 0.0, $base);
    }

    public static function success(
        PromoCode $promo,
        float $original,
        float $discount,
        float $final,
    ): self {
        return new self(true, null, $promo, $original, $discount, $final);
    }

    public function toArray(): array
    {
        $discountType = $this->promoCode?->discount_type;
        $discountValue = $this->promoCode !== null ? (float) $this->promoCode->discount_value : null;

        return [
            'valid' => $this->valid,
            'error_code' => $this->errorCode,
            'original_amount' => round($this->originalAmount, 2),
            'discount_amount' => round($this->discountAmount, 2),
            'final_amount' => round($this->finalAmount, 2),
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'promo_code' => $this->promoCode?->code,
        ];
    }
}
