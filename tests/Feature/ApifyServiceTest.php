<?php

namespace Tests\Feature;

use App\Services\Ingestion\ApifyService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApifyServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ingestion.apify.token', 'fake-apify-token');
        Config::set('ingestion.apify.timeout_secs', 120);
        Config::set('ingestion.apify.memory_mbytes', 512);
    }

    public function test_run_sync_converts_slash_actor_id_to_tilde_in_url(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        (new ApifyService())->runSync('username/actor-name', ['foo' => 'bar']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/acts/username~actor-name/run-sync-get-dataset-items')
                && ! str_contains($request->url(), 'username/actor-name');
        });
    }

    public function test_run_sync_returns_dataset_items_on_success(): void
    {
        Http::fake(['*' => Http::response([['title' => 'Item 1']], 200)]);

        $items = (new ApifyService())->runSync('someone/actor', []);

        $this->assertSame([['title' => 'Item 1']], $items);
    }

    public function test_run_sync_returns_empty_array_when_no_token(): void
    {
        Config::set('ingestion.apify.token', null);

        $items = (new ApifyService())->runSync('someone/actor', []);

        $this->assertSame([], $items);
        Http::assertNothingSent();
    }

    public function test_run_sync_throws_on_http_failure(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->expectException(\RuntimeException::class);

        (new ApifyService())->runSync('someone/actor', []);
    }

    public function test_has_token_returns_true_when_configured(): void
    {
        $this->assertTrue((new ApifyService())->hasToken());
    }

    public function test_has_token_returns_false_when_not_configured(): void
    {
        Config::set('ingestion.apify.token', null);

        $this->assertFalse((new ApifyService())->hasToken());
    }
}
