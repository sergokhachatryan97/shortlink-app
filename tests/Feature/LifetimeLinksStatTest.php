<?php

namespace Tests\Feature;

use App\Models\ShortlinkLink;
use App\Models\SiteStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LifetimeLinksStatTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_shortlink_link_increments_site_stat(): void
    {
        $user = User::factory()->create();
        $before = SiteStat::lifetimeLinksGenerated();

        ShortlinkLink::create([
            'user_id' => $user->id,
            'user_subscription_id' => null,
            'original_url' => 'https://example.com/a',
            'short_url' => 'https://trastly.org/x1',
            'batch_index' => 1,
            'batch_id' => 'batch-test',
            'expires_at' => now()->addDay(),
        ]);

        $this->assertSame($before + 1, SiteStat::lifetimeLinksGenerated());
    }

    public function test_homepage_shows_lifetime_stat(): void
    {
        Cache::forget(SiteStat::LIFETIME_LINKS_CACHE_KEY);
        $user = User::factory()->create();
        ShortlinkLink::create([
            'user_id' => $user->id,
            'user_subscription_id' => null,
            'original_url' => 'https://example.com/b',
            'short_url' => 'https://trastly.org/x2',
            'batch_index' => 1,
            'batch_id' => 'batch-test2',
            'expires_at' => null,
        ]);

        $response = $this->get(route('shortlink.index'));
        $response->assertOk();
        $response->assertSee('Trastly links generated', false);
    }

    public function test_guest_free_trial_generate_increments_site_stat_without_shortlink_rows(): void
    {
        Http::fake([
            'share.loldev.lol/*' => Http::response(['https://short/a', 'https://short/b'], 200),
        ]);

        Cache::forget(SiteStat::LIFETIME_LINKS_CACHE_KEY);
        $before = SiteStat::lifetimeLinksGenerated();

        $response = $this->postJson(route('shortlink.generate'), [
            'url' => 'https://example.com/path',
            'count' => 2,
            'fingerprint' => 'fp-lifetime-'.uniqid('', true),
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('lifetime_links_generated', $before + 2);
        $this->assertSame($before + 2, SiteStat::lifetimeLinksGenerated());
        $this->assertDatabaseCount('shortlink_links', 0);
    }

    public function test_trial_quota_persisted_shortlink_does_not_double_count_lifetime(): void
    {
        Cache::forget(SiteStat::LIFETIME_LINKS_CACHE_KEY);
        $user = User::factory()->create();

        DB::table('shortlink_free_trial_uses')->insert([
            'identifier' => 'user:'.$user->id,
            'ip_address' => '127.0.0.1',
            'links_count' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $before = SiteStat::lifetimeLinksGenerated();
        $this->assertSame(1, $before);

        ShortlinkLink::create([
            'user_id' => $user->id,
            'user_subscription_id' => null,
            'original_url' => 'https://example.com/trial',
            'short_url' => 'https://trastly.org/trial-1',
            'batch_index' => 1,
            'batch_id' => 'batch-trial',
            'expires_at' => now()->addDays(30),
            'from_free_trial_quota' => true,
        ]);

        $this->assertSame($before, SiteStat::lifetimeLinksGenerated());
    }
}
