<?php

namespace Tests\Feature;

use App\Jobs\Ingestion\IngestLaraJobsSignalsJob;
use App\Models\RawSignal;
use App\Services\Ingestion\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestLaraJobsSignalsJobTest extends TestCase
{
    use RefreshDatabase;

    private function runJob(): void
    {
        (new IngestLaraJobsSignalsJob())->handle(new IngestionService());
    }

    private function feedXml(string $pubDate, array $overrides = []): string
    {
        $item = array_merge([
            'title' => 'Senior full-stack',
            'link' => 'https://larajobs.com/job/3898',
            'guid' => 'https://larajobs.com/job/3898',
            'creator' => 'Woven Advice',
            'location' => 'Remote / UK',
            'job_type' => 'FULL_TIME',
            'tags' => 'AWS,Laravel,MySQL,PHP,VueJS',
        ], $overrides);

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:job="https://larajobs.com/job" version="2.0">
        <channel>
        <title>LaraJobs</title>
        <item>
        <title>{$item['title']}</title>
        <link>{$item['link']}</link>
        <guid>{$item['guid']}</guid>
        <pubDate>{$pubDate}</pubDate>
        <dc:creator><![CDATA[{$item['creator']}]]></dc:creator>
        <job:location><![CDATA[{$item['location']}]]></job:location>
        <job:job_type><![CDATA[{$item['job_type']}]]></job:job_type>
        <job:tags><![CDATA[{$item['tags']}]]></job:tags>
        </item>
        </channel>
        </rss>
        XML;
    }

    public function test_inserts_qualifying_job_as_signal(): void
    {
        Http::fake(['*' => Http::response($this->feedXml(now()->toRfc2822String()), 200)]);

        $this->runJob();

        $this->assertDatabaseHas('raw_signals', [
            'source' => 'larajobs',
            'title' => 'Senior full-stack',
            'author' => 'Woven Advice',
        ]);
    }

    public function test_skips_jobs_older_than_max_age(): void
    {
        Http::fake(['*' => Http::response($this->feedXml(now()->subDays(30)->toRfc2822String()), 200)]);

        $this->runJob();

        $this->assertDatabaseMissing('raw_signals', ['source' => 'larajobs']);
    }

    public function test_deduplicates_by_guid(): void
    {
        Http::fake(['*' => Http::response($this->feedXml(now()->toRfc2822String()), 200)]);
        $this->runJob();
        $this->runJob();

        $this->assertSame(1, RawSignal::where('source', 'larajobs')->count());
    }

    public function test_logs_failed_run_on_feed_error(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->runJob();

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'larajobs',
            'status' => 'failed',
        ]);
    }

    public function test_logs_failed_run_on_malformed_xml(): void
    {
        Http::fake(['*' => Http::response('<rss><channel><item><title>Unclosed', 200)]);

        $this->runJob();

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'larajobs',
            'status' => 'failed',
        ]);
    }

    public function test_stores_tags_and_location_in_metadata(): void
    {
        Http::fake(['*' => Http::response($this->feedXml(now()->toRfc2822String()), 200)]);

        $this->runJob();

        $signal = RawSignal::where('source', 'larajobs')->first();
        $this->assertSame(['AWS', 'Laravel', 'MySQL', 'PHP', 'VueJS'], $signal->metadata['tags']);
        $this->assertSame('Remote / UK', $signal->metadata['location']);
    }

    public function test_logs_run_stats_after_successful_ingestion(): void
    {
        Http::fake(['*' => Http::response($this->feedXml(now()->toRfc2822String()), 200)]);

        $this->runJob();

        $this->assertDatabaseHas('ingestion_runs', [
            'source' => 'larajobs',
            'signals_found' => 1,
            'signals_inserted' => 1,
            'status' => 'success',
        ]);
    }
}
