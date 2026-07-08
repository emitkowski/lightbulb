<?php

namespace Tests\Feature;

use App\Services\Scoring\CompetitionSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompetitionSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private CompetitionSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CompetitionSearchService();
    }

    public function test_returns_stub_when_no_api_key(): void
    {
        Config::set('scoring.serper.api_key', null);

        $result = $this->service->search('invoice automation tool');

        $this->assertTrue($result['stubbed']);
        $this->assertEmpty($result['results']);
        $this->assertStringContainsString('invoice automation tool', $result['summary']);
    }

    public function test_searches_serper_and_returns_results(): void
    {
        Config::set('scoring.serper.api_key', 'test-key');

        Http::fake([
            '*' => Http::response([
                'organic' => [
                    ['title' => 'FreshBooks', 'snippet' => 'Online invoicing software', 'link' => 'https://freshbooks.com'],
                    ['title' => 'Wave', 'snippet' => 'Free invoicing for small business', 'link' => 'https://wave.com'],
                ],
            ], 200),
        ]);

        $result = $this->service->search('invoice automation');

        $this->assertFalse($result['stubbed']);
        $this->assertCount(2, $result['results']);
        $this->assertStringContainsString('FreshBooks', $result['summary']);
        $this->assertStringContainsString('Wave', $result['summary']);
    }

    public function test_falls_back_to_stub_on_api_error(): void
    {
        Config::set('scoring.serper.api_key', 'test-key');

        Http::fake(['*' => Http::response([], 500)]);

        $result = $this->service->search('invoice automation');

        $this->assertTrue($result['stubbed']);
    }

    public function test_falls_back_to_stub_on_connection_exception(): void
    {
        Config::set('scoring.serper.api_key', 'test-key');

        Http::fake(['*' => function () {
            throw new \RuntimeException('Connection refused');
        }]);

        $result = $this->service->search('invoice automation');

        $this->assertTrue($result['stubbed']);
    }

    public function test_summary_lists_up_to_five_results(): void
    {
        Config::set('scoring.serper.api_key', 'test-key');

        $organic = array_map(
            fn ($i) => ['title' => "Tool {$i}", 'snippet' => "Snippet {$i}"],
            range(1, 8)
        );

        Http::fake(['*' => Http::response(['organic' => $organic], 200)]);

        $result = $this->service->search('some query');

        $this->assertStringContainsString('Tool 1', $result['summary']);
        $this->assertStringContainsString('Tool 5', $result['summary']);
        $this->assertStringNotContainsString('Tool 6', $result['summary']);
    }

    public function test_empty_organic_results_returns_no_competitors_message(): void
    {
        Config::set('scoring.serper.api_key', 'test-key');

        Http::fake(['*' => Http::response(['organic' => []], 200)]);

        $result = $this->service->search('very niche idea');

        $this->assertFalse($result['stubbed']);
        $this->assertEmpty($result['results']);
        $this->assertStringContainsString('No significant competitors', $result['summary']);
    }
}
