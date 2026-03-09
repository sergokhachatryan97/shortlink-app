<?php

namespace Tests\Feature;

use App\Models\ShortlinkSetting;
use App\Models\ShortlinkTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ShortlinkSetting::firstOrCreate(['key' => 'price_per_link'], ['value' => '0.01']);
        ShortlinkSetting::firstOrCreate(['key' => 'min_amount'], ['value' => '0.10']);
    }

    private function heleketSignedPayload(array $data): array
    {
        $key = config('services.heleket.payment_key', 'test_secret_key');
        $sign = md5(base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE)) . $key);
        $data['sign'] = $sign;
        return $data;
    }

    public function test_prepare_topup_returns_order_id_and_amount(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('balance.topup.prepare'), [
            'amount' => 10,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['order_id', 'amount']);
        $response->assertJson(['amount' => 10]);

        $this->assertDatabaseHas('shortlink_transactions', [
            'amount' => 10,
            'identifier' => 'user:' . $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_prepare_topup_rejects_invalid_amount(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('balance.topup.prepare'), [
            'amount' => 0.05,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['amount']);
    }

    public function test_tron_topup_success_shows_success_when_webhook_already_credited(): void
    {
        $user = User::factory()->create(['balance' => 25]);

        $orderId = 'bal-test-' . uniqid();
        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => 25,
            'currency' => 'USD',
            'status' => 'paid',
            'identifier' => 'user:' . $user->id,
            'count' => 0,
            'url' => null,
            'provider_ref' => 'tron:xyz',
        ]);

        $response = $this->actingAs($user)->get(route('balance.tron.success', ['order_id' => $orderId]));

        $response->assertRedirect(route('balance.index'));
        $response->assertSessionHas('success', 'Balance topped up: $25.00');
    }

    public function test_tron_topup_success_shows_pending_when_webhook_not_arrived(): void
    {
        $user = User::factory()->create(['balance' => 0]);

        $orderId = 'bal-pending-' . uniqid();
        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => 25,
            'currency' => 'USD',
            'status' => 'pending',
            'identifier' => 'user:' . $user->id,
            'count' => 0,
            'url' => null,
            'provider_ref' => 'tron_topup',
        ]);

        $response = $this->actingAs($user)->get(route('balance.tron.success', ['order_id' => $orderId]));

        $response->assertRedirect(route('balance.index'));
        $response->assertSessionHas('info');
    }

    public function test_tron_topup_success_rejects_invalid_order(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('balance.tron.success', ['order_id' => 'invalid-order']));

        $response->assertRedirect(route('balance.index'));
        $response->assertSessionHas('error', 'Transaction not found.');
    }

    public function test_payment_tron_success_shows_links_when_webhook_already_generated(): void
    {
        $orderId = 'sl-test-' . uniqid();
        $links = ['https://short.link/1', 'https://short.link/2'];

        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => 0.50,
            'currency' => 'USD',
            'status' => 'paid',
            'identifier' => 'fp:test',
            'count' => 50,
            'url' => 'https://example.com',
            'provider_ref' => 'tron:abc',
            'result_links' => $links,
        ]);

        $response = $this->get(route('shortlink.payment-tron-success', ['order_id' => $orderId]));

        $response->assertRedirect(route('shortlink.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('download_ready', true);
        $response->assertSessionHas('payment_provider', 'tron');
        $response->assertSessionHas('shortlink_result', $links);
    }

    public function test_payment_tron_success_shows_pending_when_webhook_not_arrived(): void
    {
        $orderId = 'sl-pending-' . uniqid();
        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => 0.50,
            'currency' => 'USD',
            'status' => 'pending',
            'identifier' => 'fp:test',
            'count' => 50,
            'url' => 'https://example.com',
            'provider_ref' => 'tron',
        ]);

        $response = $this->get(route('shortlink.payment-tron-success', ['order_id' => $orderId]));

        $response->assertViewIs('shortlink.payment-pending');
    }

    public function test_payment_status_returns_paid_with_links(): void
    {
        $orderId = 'sl-status-' . uniqid();
        $links = ['https://a.com', 'https://b.com'];
        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => 0.50,
            'currency' => 'USD',
            'status' => 'paid',
            'identifier' => 'fp:test',
            'count' => 2,
            'url' => 'https://example.com',
            'provider_ref' => 'tron',
            'result_links' => $links,
        ]);

        $response = $this->getJson(route('shortlink.payment-status', ['order_id' => $orderId]));

        $response->assertOk();
        $response->assertJson(['status' => 'paid', 'links' => $links]);
    }

    public function test_coinrush_webhook_marks_transaction_paid(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        $orderId = 'bal-webhook-' . uniqid();
        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => 5,
            'currency' => 'USD',
            'status' => 'pending',
            'identifier' => 'user:' . $user->id,
            'count' => 0,
            'url' => null,
            'provider_ref' => 'tron_topup',
        ]);

        $response = $this->postJson('/api/webhooks/payments/coinrush', [
            'transaction_id' => $orderId,
            'status' => 'completed',
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $tx = ShortlinkTransaction::where('order_id', $orderId)->first();
        $this->assertEquals('paid', $tx->status);
        $user->refresh();
        $this->assertEquals(5, $user->balance);
    }

    public function test_heleket_webhook_marks_transaction_paid_with_valid_signature(): void
    {
        config(['services.heleket.payment_key' => 'test_secret_key']);

        $orderId = 'sl-webhook-' . uniqid();
        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => 1,
            'currency' => 'USD',
            'status' => 'pending',
            'identifier' => 'fp:test',
            'count' => 0,
            'url' => null,
            'provider_ref' => 'heleket',
        ]);

        $payload = $this->heleketSignedPayload([
            'order_id' => $orderId,
            'status' => 'paid',
            'uuid' => 'test-uuid-123',
        ]);

        $response = $this->postJson('/api/webhooks/payments/heleket', $payload);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $tx = ShortlinkTransaction::where('order_id', $orderId)->first();
        $this->assertEquals('paid', $tx->status);
    }

    public function test_heleket_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/api/webhooks/payments/heleket', [
            'order_id' => 'any',
            'status' => 'paid',
            'sign' => 'invalid_signature',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_heleket_topup_success_shows_success_when_webhook_credited(): void
    {
        $user = User::factory()->create(['balance' => 15]);

        $orderId = 'bal-heleket-' . uniqid();
        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => 15,
            'currency' => 'USD',
            'status' => 'paid',
            'identifier' => 'user:' . $user->id,
            'count' => 0,
            'url' => null,
            'provider_ref' => 'heleket-uuid',
        ]);

        $response = $this->actingAs($user)->get(route('balance.heleket.success', ['order_id' => $orderId]));

        $response->assertRedirect(route('balance.index'));
        $response->assertSessionHas('success', 'Balance topped up: $15.00');
    }

    public function test_coinrush_webhook_credits_balance_for_tron_topup(): void
    {
        $user = User::factory()->create(['balance' => 0]);

        $orderId = 'bal-webhook-' . uniqid();
        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => 12.50,
            'currency' => 'USD',
            'status' => 'pending',
            'identifier' => 'user:' . $user->id,
            'count' => 0,
            'url' => null,
            'provider_ref' => 'tron_topup',
        ]);

        $response = $this->postJson('/api/webhooks/payments/coinrush', [
            'transaction_id' => $orderId,
            'status' => 'completed',
        ]);

        $response->assertOk();
        $user->refresh();
        $this->assertEquals(12.50, $user->balance);

        $tx = ShortlinkTransaction::where('order_id', $orderId)->first();
        $this->assertEquals('paid', $tx->status);
    }

    public function test_heleket_webhook_credits_balance_for_heleket_topup(): void
    {
        config(['services.heleket.payment_key' => 'test_secret_key']);

        $user = User::factory()->create(['balance' => 0]);

        $orderId = 'bal-heleket-webhook-' . uniqid();
        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => 8,
            'currency' => 'USD',
            'status' => 'pending',
            'identifier' => 'user:' . $user->id,
            'count' => 0,
            'url' => null,
            'provider_ref' => 'heleket_topup',
        ]);

        $payload = $this->heleketSignedPayload([
            'order_id' => $orderId,
            'status' => 'paid',
            'uuid' => 'uuid-456',
        ]);

        $response = $this->postJson('/api/webhooks/payments/heleket', $payload);

        $response->assertOk();
        $user->refresh();
        $this->assertEquals(8, $user->balance);

        $tx = ShortlinkTransaction::where('order_id', $orderId)->first();
        $this->assertEquals('paid', $tx->status);
    }

    public function test_coinrush_webhook_does_not_double_credit(): void
    {
        $user = User::factory()->create(['balance' => 0]);

        $orderId = 'bal-idempotent-' . uniqid();
        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => 10,
            'currency' => 'USD',
            'status' => 'paid',
            'identifier' => 'user:' . $user->id,
            'count' => 0,
            'url' => null,
            'provider_ref' => 'tron:abc',
        ]);
        $user->increment('balance', 10);

        $response = $this->postJson('/api/webhooks/payments/coinrush', [
            'transaction_id' => $orderId,
            'status' => 'completed',
        ]);

        $response->assertOk();
        $user->refresh();
        $this->assertEquals(10, $user->balance);
    }

    public function test_heleket_webhook_generates_links_for_shortlink_payment(): void
    {
        Http::fake(['*shorten*' => Http::response(['https://s2.co/1', 'https://s2.co/2'], 200)]);
        config(['services.heleket.payment_key' => 'test_secret_key']);

        $orderId = 'sl-heleket-' . uniqid();
        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => 0.20,
            'currency' => 'USD',
            'status' => 'pending',
            'identifier' => 'fp:x',
            'count' => 2,
            'url' => 'https://example.com',
            'provider_ref' => 'heleket',
        ]);

        $payload = $this->heleketSignedPayload([
            'order_id' => $orderId,
            'status' => 'paid',
            'uuid' => 'uuid-heleket',
        ]);

        $response = $this->postJson('/api/webhooks/payments/heleket', $payload);

        $response->assertOk();

        $tx = ShortlinkTransaction::where('order_id', $orderId)->first();
        $this->assertEquals('paid', $tx->status);
        $this->assertNotNull($tx->result_links);
        $this->assertCount(2, $tx->result_links);
    }

    public function test_coinrush_webhook_generates_links_for_shortlink_payment(): void
    {
        Http::fake(['*shorten*' => Http::response(['https://s1.co/1', 'https://s1.co/2'], 200)]);

        $orderId = 'sl-webhook-' . uniqid();
        ShortlinkTransaction::create([
            'order_id' => $orderId,
            'amount' => 0.20,
            'currency' => 'USD',
            'status' => 'pending',
            'identifier' => 'fp:x',
            'count' => 2,
            'url' => 'https://example.com',
            'provider_ref' => 'tron',
        ]);

        $response = $this->postJson('/api/webhooks/payments/coinrush', [
            'transaction_id' => $orderId,
            'status' => 'completed',
        ]);

        $response->assertOk();

        $tx = ShortlinkTransaction::where('order_id', $orderId)->first();
        $this->assertEquals('paid', $tx->status);
        $this->assertNotNull($tx->result_links);
        $this->assertCount(2, $tx->result_links);
    }

    public function test_test_add_balance_route_adds_balance(): void
    {
        $user = User::factory()->create(['balance' => 0]);

        $response = $this->actingAs($user)->get(route('balance.test-add', ['amount' => 25]));

        $response->assertRedirect(route('balance.index'));
        $response->assertSessionHas('success', 'Balance added (test): $25.00');

        $user->refresh();
        $this->assertEquals(25, $user->balance);
    }

    public function test_test_add_balance_uses_default_amount(): void
    {
        $user = User::factory()->create(['balance' => 0]);

        $response = $this->actingAs($user)->get(route('balance.test-add'));

        $response->assertRedirect(route('balance.index'));
        $user->refresh();
        $this->assertEquals(10, $user->balance);
    }
}
