<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestStackOverflowSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestStackOverflowSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    private function runJob(string $tag): void
    {
        (new IngestStackOverflowSignalsJob($tag))->handle(new IngestionService());
    }

    private function makeQuestion(array $overrides = []): array
    {
        return array_merge([
            'question_id' => 99001,
            'title' => 'How to automate client reporting without doing it manually?',
            'body' => '<p>I have been doing this manually for years. Is there a tool?</p>',
            'link' => 'https://stackoverflow.com/questions/99001',
            'score' => 25,
            'view_count' => 3000,
            'answer_count' => 1,
            'is_answered' => false,
            'tags' => ['laravel', 'saas'],
            'creation_date' => now()->subDays(500)->timestamp,
        ], $overrides);
    }

    public function test_inserts_qualifying_questions_as_signals(): void
    {
        Http::fakeSequence()->push(['items' => [$this->makeQuestion()]], 200);

        $this->runJob('laravel');

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'stackoverflow',
            'source_id' => '99001',
            'category' => 'laravel',
        ]);
    }

    public function test_skips_questions_below_min_view_count(): void
    {
        Http::fakeSequence()->push(['items' => [$this->makeQuestion(['view_count' => 100])]], 200);

        $this->runJob('laravel');

        $this->assertDatabaseMissing('raw_signals', ['source' => 'stackoverflow', 'source_id' => '99001']);
    }

    public function test_deduplicates_questions(): void
    {
        Http::fakeSequence()->push(['items' => [$this->makeQuestion()]], 200);
        $this->runJob('laravel');

        Http::fakeSequence()->push(['items' => [$this->makeQuestion()]], 200);
        $this->runJob('php');

        $this->assertSame(1, RawSignal::where('source_id', '99001')->count());
    }

    public function test_falls_back_to_title_when_body_absent(): void
    {
        $question = $this->makeQuestion();
        unset($question['body']);

        Http::fakeSequence()->push(['items' => [$question]], 200);
        $this->runJob('laravel');

        $this->assertDatabaseHas('raw_signals', [
            'source_id' => '99001',
            'content' => $question['title'],
        ]);
    }

    public function test_strips_html_tags_from_body(): void
    {
        Http::fakeSequence()->push(['items' => [$this->makeQuestion()]], 200);

        $this->runJob('laravel');

        $signal = RawSignal::where('source_id', '99001')->first();
        $this->assertStringNotContainsString('<p>', $signal->content);
    }

    public function test_logs_failed_run_on_api_error(): void
    {
        Http::fakeSequence()->push([], 500);

        $this->runJob('laravel');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'stackoverflow',
            'status' => 'failed',
        ]);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fakeSequence()->push(['items' => [$this->makeQuestion()]], 200);

        $this->runJob('laravel');

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'stackoverflow',
            'query' => 'laravel',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
