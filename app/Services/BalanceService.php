<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\ExternalClient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Row-locked, transaction-safe balance changes for {@see User} and {@see ExternalClient}
 * using bcmath (no float arithmetic on monetary deltas).
 *
 * Example — debit a user wallet:
 *
 * ```php
 * $balanceService->decrementBalance(User::class, $userId, '0.005');
 * // or
 * $balanceService->decrementBalance(User::class, $userId, 0.005);
 * ```
 *
 * Example — credit an external client:
 *
 * ```php
 * $balanceService->incrementBalance(ExternalClient::class, $clientId, '10.25');
 * ```
 *
 * Nested {@see DB::transaction} calls participate in the same Laravel transaction when
 * already inside one (savepoints), so you can compose multiple balance moves atomically.
 */
final class BalanceService
{
    public const SCALE = 6;

    /** @var list<class-string<Model>> */
    private const ALLOWED = [
        User::class,
        ExternalClient::class,
    ];

    /**
     * @param  class-string<User|ExternalClient>  $modelClass
     */
    public function incrementBalance(string $modelClass, int $id, float|int|string $amount): User|ExternalClient
    {
        $this->assertAllowedModelClass($modelClass);
        $delta = $this->normalizePositiveAmount($amount);

        return DB::transaction(function () use ($modelClass, $id, $delta) {
            $model = $this->lockModelOrFail($modelClass, $id);
            $current = $this->rawBalanceString($model);
            $newBalance = bcadd($current, $delta, self::SCALE);
            $this->persistBalance($model, $newBalance);

            return $model->refresh();
        });
    }

    /**
     * @param  class-string<User|ExternalClient>  $modelClass
     */
    public function decrementBalance(string $modelClass, int $id, float|int|string $amount): User|ExternalClient
    {
        $this->assertAllowedModelClass($modelClass);
        $delta = $this->normalizePositiveAmount($amount);

        return DB::transaction(function () use ($modelClass, $id, $delta) {
            $model = $this->lockModelOrFail($modelClass, $id);
            $current = $this->rawBalanceString($model);

            if (bccomp($current, $delta, self::SCALE) < 0) {
                throw new InsufficientBalanceException(
                    $modelClass,
                    $id,
                    $current,
                    $delta,
                    sprintf(
                        'Insufficient balance: has %s, required %s.',
                        $current,
                        $delta
                    )
                );
            }

            $newBalance = bcsub($current, $delta, self::SCALE);

            $this->persistBalance($model, $newBalance);

            return $model->refresh();
        });
    }

    /**
     * Read balance as a normalized decimal string (scale {@see self::SCALE}), without locking.
     *
     * @param  class-string<User|ExternalClient>  $modelClass
     */
    public function getBalance(string $modelClass, int $id): string
    {
        $this->assertAllowedModelClass($modelClass);

        $raw = $modelClass::query()->whereKey($id)->value('balance');
        if ($raw === null) {
            throw (new ModelNotFoundException)->setModel($modelClass, [$id]);
        }

        return $this->normalizeAmount($raw);
    }

    /**
     * Normalize a monetary value to {@see self::SCALE} decimal places for comparisons / bcmath.
     */
    public function normalizeAmount(float|int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return bcadd('0', '0', self::SCALE);
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' || ! is_numeric($value)) {
                throw new \InvalidArgumentException('Amount must be numeric.');
            }

            return bcadd($value, '0', self::SCALE);
        }

        if (is_int($value)) {
            return bcadd((string) $value, '0', self::SCALE);
        }

        if (is_nan($value) || is_infinite($value)) {
            throw new \InvalidArgumentException('Amount must be a finite number.');
        }

        return bcadd(sprintf('%.10F', $value), '0', self::SCALE);
    }

    /**
     * Compare two normalized (or raw) decimal strings: returns same as bccomp at {@see self::SCALE}.
     */
    public function compareAmounts(float|int|string|null $a, float|int|string|null $b): int
    {
        return bccomp($this->normalizeAmount($a), $this->normalizeAmount($b), self::SCALE);
    }

    /**
     * @param  class-string  $modelClass
     */
    private function assertAllowedModelClass(string $modelClass): void
    {
        if (! in_array($modelClass, self::ALLOWED, true)) {
            throw new \InvalidArgumentException(
                'Balance operations are only supported for '.implode(' and ', self::ALLOWED).'.'
            );
        }
    }

    /**
     * @param  class-string<User|ExternalClient>  $modelClass
     */
    private function lockModelOrFail(string $modelClass, int $id): User|ExternalClient
    {
        /** @var User|ExternalClient|null $model */
        $model = $modelClass::query()->whereKey($id)->lockForUpdate()->first();
        if (! $model) {
            throw (new ModelNotFoundException)->setModel($modelClass, [$id]);
        }

        return $model;
    }

    private function rawBalanceString(Model $model): string
    {
        $raw = $model->getRawOriginal('balance');
        if ($raw === null) {
            return bcadd('0', '0', self::SCALE);
        }

        return $this->normalizeAmount($raw);
    }

    private function persistBalance(Model $model, string $newBalance): void
    {
        $model->forceFill(['balance' => $newBalance])->save();
    }

    private function normalizePositiveAmount(float|int|string $amount): string
    {
        $normalized = $this->normalizeAmount($amount);
        if (bccomp($normalized, '0', self::SCALE) <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        return $normalized;
    }
}
