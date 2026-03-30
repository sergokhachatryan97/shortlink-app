<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientBalanceException;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_decrement_supports_micro_amounts_without_drift(): void
    {
        $user = User::factory()->create(['balance' => '0.010000']);
        $svc = app(BalanceService::class);

        $svc->decrementBalance(User::class, (int) $user->id, '0.0025');
        $svc->decrementBalance(User::class, (int) $user->id, '0.0025');

        $user->refresh();
        $this->assertSame('0.005000', $user->balance);
    }

    public function test_decrement_throws_when_insufficient(): void
    {
        $user = User::factory()->create(['balance' => '0.001000']);
        $svc = app(BalanceService::class);

        $this->expectException(InsufficientBalanceException::class);
        $svc->decrementBalance(User::class, (int) $user->id, '0.005');
    }

    public function test_increment_is_atomic_under_lock(): void
    {
        $user = User::factory()->create(['balance' => '1.000000']);
        $svc = app(BalanceService::class);

        $svc->incrementBalance(User::class, (int) $user->id, '0.000001');

        $user->refresh();
        $this->assertSame('1.000001', $user->balance);
    }

    public function test_get_balance_normalizes_string(): void
    {
        $user = User::factory()->create(['balance' => '3.5']);
        $svc = app(BalanceService::class);

        $this->assertSame('3.500000', $svc->getBalance(User::class, (int) $user->id));
    }
}
