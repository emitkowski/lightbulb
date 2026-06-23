<?php

namespace Tests\Feature;

use App\Models\IngestionRun;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngestionServiceTest extends TestCase
{
    use RefreshDatabase;

    private IngestionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IngestionService();
    }

    public function test_insert_signal_creates_record_and_returns_true(): void
    {
        $inserted = $this->service->insertSignal([
            'source' => 'reddit',
            'source_id' => 'abc123',
            'title' => 'Test post',
            'content' => 'Test content',
        ]);

        $this->assertTrue($inserted);
        $this->assertDatabaseHas('raw_signals', ['source' => 'reddit', 'source_id' => 'abc123']);
    }

    public function test_insert_signal_skips_duplicate_and_returns_false(): void
    {
        RawSignal::factory()->create(['source' => 'reddit', 'source_id' => 'dup123']);

        $inserted = $this->service->insertSignal([
            'source' => 'reddit',
            'source_id' => 'dup123',
            'content' => 'Some content',
        ]);

        $this->assertFalse($inserted);
        $this->assertSame(1, RawSignal::where('source_id', 'dup123')->count());
    }

    public function test_insert_signal_allows_null_source_id_without_dedup(): void
    {
        $this->service->insertSignal(['source' => 'reddit', 'source_id' => null, 'content' => 'a']);
        $inserted = $this->service->insertSignal(['source' => 'reddit', 'source_id' => null, 'content' => 'b']);

        $this->assertTrue($inserted);
        $this->assertSame(2, RawSignal::count());
    }

    public function test_exists_returns_true_when_signal_present(): void
    {
        RawSignal::factory()->create(['source' => 'hackernews', 'source_id' => 'hn999']);

        $this->assertTrue($this->service->exists('hackernews', 'hn999'));
    }

    public function test_exists_returns_false_when_signal_absent(): void
    {
        $this->assertFalse($this->service->exists('hackernews', 'missing'));
    }

    public function test_exists_returns_false_for_null_source_id(): void
    {
        $this->assertFalse($this->service->exists('reddit', null));
    }

    public function test_start_and_finish_run_creates_ingestion_run_record(): void
    {
        $run = $this->service->startRun('reddit', 'test query');

        $this->assertInstanceOf(IngestionRun::class, $run);
        $this->assertSame('running', $run->status);

        $this->service->finishRun($run, [
            'found' => 10,
            'inserted' => 8,
            'skipped' => 2,
            'status' => 'success',
            'duration_ms' => 1500,
        ]);

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'reddit',
            'query' => 'test query',
            'signals_found' => 10,
            'signals_inserted' => 8,
            'signals_skipped' => 2,
            'status' => 'success',
        ]);
    }

    public function test_finish_run_records_error_message_on_failure(): void
    {
        $run = $this->service->startRun('hackernews', 'some query');
        $this->service->finishRun($run, [
            'found' => 0,
            'inserted' => 0,
            'skipped' => 0,
            'status' => 'failed',
            'error' => 'Connection timeout',
        ]);

        $run->refresh();
        $this->assertSame('failed', $run->status);
        $this->assertSame('Connection timeout', $run->error_message);
    }

    public function test_insert_signal_stores_ingestion_run_id(): void
    {
        $run = $this->service->startRun('hackernews', 'test query');
        $this->service->insertSignal([
            'source' => 'hackernews',
            'source_id' => 'hn123',
            'content' => 'Test content',
        ], $run->id);

        $this->assertDatabaseHas('raw_signals', [
            'source_id' => 'hn123',
            'ingestion_run_id' => $run->id,
        ]);
    }
}
