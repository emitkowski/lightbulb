<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestStripeCustomersSearchJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestStripeCustomersSearchJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('scoring.serper.api_key', 'fake-serper-key');
        Config::set('scoring.serper.base_url', 'https://google.serper.dev');
        Config::set('ingestion.serper.stripe_customers.results_per_query', 5);
    }

    private function runJob(string $category): void
    {
        (new IngestStripeCustomersSearchJob($category))->handle(new IngestionService());
    }

    private function makeSerperResponse(array $organic = []): array
    {
        return ['organic' => $organic];
    }

    private function makeResult(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Brightwheel Grows from Startup to Vertical SaaS Category Leader',
            'link' => 'https://stripe.com/customers/brightwheel',
            'snippet' => 'Brightwheel is a leading provider of childcare management software.',
        ], $overrides);
    }

    public function test_inserts_organic_results_as_signals(): void
    {
        Http::fakeSequence()->push($this->makeSerperResponse([$this->makeResult()]), 200);

        $this->runJob('SaaS');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'stripe_customers',
            'category' => 'SaaS',
        ]);
    }

    public function test_restricts_search_to_stripe_customers(): void
    {
        Http::fakeSequence()->push($this->makeSerperResponse([$this->makeResult()]), 200);

        $this->runJob('SaaS');

        Http::assertSent(function ($request) {
            return ($request['q'] ?? null) === 'site:stripe.com/customers SaaS';
        });
    }

    public function test_skips_the_customers_index_page(): void
    {
        Http::fakeSequence()->push($this->makeSerperResponse([
            $this->makeResult(['link' => 'https://stripe.com/customers', 'title' => 'Our customers | Stripe']),
        ]), 200);

        $this->runJob('marketplace platform');

        $this->assertSame(0, RawSignal::where('source', 'stripe_customers')->count());
    }

    public function test_deduplicates_the_same_url_across_different_categories(): void
    {
        Http::fakeSequence()->push($this->makeSerperResponse([$this->makeResult()]), 200);
        $this->runJob('SaaS');

        Http::fakeSequence()->push($this->makeSerperResponse([$this->makeResult()]), 200);
        $this->runJob('vertical software');

        $this->assertSame(1, RawSignal::where('source', 'stripe_customers')->count());
    }

    public function test_skips_results_without_a_url(): void
    {
        $result = $this->makeResult(['link' => null]);
        unset($result['link']);
        Http::fakeSequence()->push($this->makeSerperResponse([$result]), 200);

        $this->runJob('SaaS');

        $this->assertSame(0, RawSignal::where('source', 'stripe_customers')->count());
    }

    public function test_logs_failed_run_when_serper_key_not_configured(): void
    {
        Config::set('scoring.serper.api_key', null);

        $this->runJob('SaaS');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'stripe_customers',
            'status' => 'failed',
        ]);
    }

    public function test_inserts_zero_signals_on_api_error(): void
    {
        Http::fakeSequence()->push([], 500);

        $this->runJob('SaaS');

        $this->assertSame(0, RawSignal::where('source', 'stripe_customers')->count());
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push($this->makeSerperResponse([$this->makeResult()]), 200);

        $this->runJob('SaaS');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'stripe_customers',
            'query' => 'SaaS',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
