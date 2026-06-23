# Phase 1 — Signal Ingestion Layer
*Project: Lightbulb*

## Goal
Build the data ingestion foundation. Raw signals from external sources land in the database, normalized and ready for the scoring agent in Phase 2. No scoring happens in this phase — ingestion only.

## Success Criteria
- [ ] Database schema migrated and seeded
- [ ] Reddit API ingestion running on scheduler
- [ ] Hacker News Algolia ingestion running on scheduler
- [ ] Raw signals deduplicated on insert
- [ ] Basic Filament resource to browse raw signals

---

## Stack
- Laravel 13 / PHP 8.4
- PostgreSQL 16 with pgvector
- Filament 3 for dashboard
- Laravel Scheduler for crawl jobs
- Laravel Queues (database driver to start, Redis later)

---

## Database Schema

### Table: `raw_signals`
Primary table. Every ingested signal lands here regardless of source.

```php
Schema::create('raw_signals', function (Blueprint $table) {
    $table->id();
    $table->string('source', 50); // 'reddit', 'hackernews', 'github', 'stackoverflow'
    $table->string('source_id')->nullable(); // external ID for deduplication
    $table->string('source_url')->nullable();
    $table->string('title')->nullable();
    $table->text('content'); // raw text content
    $table->string('author')->nullable();
    $table->integer('score')->default(0); // upvotes, points, reactions
    $table->integer('comment_count')->default(0);
    $table->string('category')->nullable(); // subreddit, HN type, etc.
    $table->json('metadata')->nullable(); // source-specific extra fields
    $table->boolean('processed')->default(false); // picked up by scoring agent
    $table->boolean('flagged')->default(false); // manually flagged for review
    $table->timestamp('published_at')->nullable(); // when original was posted
    $table->timestamps();

    $table->unique(['source', 'source_id']); // deduplication
    $table->index(['source', 'processed']);
    $table->index('published_at');
    $table->index('score');
});
```

### Table: `ingestion_runs`
Tracks each scheduler run for debugging and monitoring.

```php
Schema::create('ingestion_runs', function (Blueprint $table) {
    $table->id();
    $table->string('source', 50);
    $table->string('query')->nullable(); // what was searched
    $table->integer('signals_found')->default(0);
    $table->integer('signals_inserted')->default(0);
    $table->integer('signals_skipped')->default(0); // duplicates
    $table->string('status'); // 'success', 'failed', 'partial'
    $table->text('error_message')->nullable();
    $table->integer('duration_ms')->nullable();
    $table->timestamps();

    $table->index(['source', 'created_at']);
});
```

---

## Source 1 — Reddit API

### Authentication
Reddit API uses OAuth2 client credentials. Register an app at reddit.com/prefs/apps (script type).

Store in `.env`:
```
REDDIT_CLIENT_ID=
REDDIT_CLIENT_SECRET=
REDDIT_USER_AGENT="Lightbulb/1.0 by YourUsername"
```

### Job: `IngestRedditSignalsJob`
Location: `app/Jobs/Ingestion/IngestRedditSignalsJob.php`

**Behavior:**
- Accepts a subreddit name and search query as constructor params
- Searches the subreddit for the query using Reddit search API
- Filters posts: score >= 10, posted within last 7 days
- Inserts new signals, skips duplicates (unique source + source_id)
- Logs results to `ingestion_runs`

**Reddit Search API endpoint:**
```
GET https://oauth.reddit.com/r/{subreddit}/search
  ?q={query}
  &sort=relevance
  &t=week
  &limit=25
  &type=link
```

**Field mapping to `raw_signals`:**
```
source        → 'reddit'
source_id     → post.id
source_url    → "https://reddit.com" + post.permalink
title         → post.title
content       → post.selftext (body) or post.title if no body
author        → post.author
score         → post.score
comment_count → post.num_comments
category      → subreddit name
published_at  → Carbon::createFromTimestamp(post.created_utc)
metadata      → { subreddit, url, is_self, link_flair_text }
```

### Subreddits and queries to run
Defined as a config array. Start with high-signal subreddits only:

```php
// config/ingestion.php
'reddit' => [
    'subreddits' => [
        'SaaS', 'indiehackers', 'microsaas', 'startups',
        'freelance', 'webdev', 'laravel', 'Entrepreneur',
        'EntrepreneurRideAlong', 'smallbusiness',
    ],
    'queries' => [
        'does anyone know a tool that',
        'is there a way to automate',
        'looking for something that',
        'I wish there was',
        'anyone else frustrated with',
        'alternatives to',
        'sick of paying for',
        'I built this because',
        'just hit $1K MRR',
        'reached $1K MRR',
        'I\'ve been doing this manually',
    ],
    'min_score' => 10,
    'max_age_days' => 7,
],
```

Run each query against each subreddit — that's ~110 API calls per run. Batch with rate limiting (1 req/sec to stay within Reddit API limits).

---

## Source 2 — Hacker News (Algolia API)

### Authentication
No auth required. Free public API.

```
Base URL: https://hn.algolia.com/api/v1
```

### Job: `IngestHackerNewsSignalsJob`
Location: `app/Jobs/Ingestion/IngestHackerNewsSignalsJob.php`

**Behavior:**
- Searches HN for Ask HN and Show HN posts matching query patterns
- Filters: points >= 50 for Ask HN, points >= 100 for Show HN
- Published within last 30 days (HN signal has longer shelf life than Reddit)
- Inserts new signals, skips duplicates

**Algolia Search endpoint:**
```
GET https://hn.algolia.com/api/v1/search
  ?query={query}
  &tags=ask_hn,show_hn  (comma = OR)
  &numericFilters=points>=50,created_at_i>{timestamp_30_days_ago}
  &hitsPerPage=25
```

**Field mapping to `raw_signals`:**
```
source        → 'hackernews'
source_id     → hit.objectID
source_url    → "https://news.ycombinator.com/item?id=" + hit.objectID
title         → hit.title
content       → hit.story_text or hit.title if no body
author        → hit.author
score         → hit.points
comment_count → hit.num_comments
category      → hit._tags[0] (ask_hn or show_hn)
published_at  → Carbon::createFromTimestamp(hit.created_at_i)
metadata      → { url, _tags }
```

### HN query patterns:
```php
'hackernews' => [
    'queries' => [
        'Ask HN: Is there a tool',
        'Ask HN: What do you use for',
        'Ask HN: Does anyone know',
        'Show HN: I built this because',
        'we couldn\'t find a tool that',
        'I\'ve been doing this manually',
        '$1K MRR',
        'launched because nothing existed',
    ],
    'min_points_ask' => 50,
    'min_points_show' => 100,
    'max_age_days' => 30,
],
```

---

## Service Layer

### `IngestionService`
Location: `app/Services/Ingestion/IngestionService.php`

Shared logic used by all ingestion jobs:

```php
class IngestionService
{
    // Insert signal, skip if duplicate (source + source_id)
    public function insertSignal(array $data): bool

    // Log an ingestion run result
    public function logRun(string $source, string $query, array $stats): void

    // Check if signal already exists
    public function exists(string $source, string $sourceId): bool
}
```

---

## Scheduler

In `routes/console.php` or `app/Console/Kernel.php`:

```php
// Reddit — run weekly, stagger to avoid rate limits
Schedule::job(new IngestRedditSignalsJob())->weekly()->withoutOverlapping();

// HN — run weekly
Schedule::job(new IngestHackerNewsSignalsJob())->weekly()->withoutOverlapping();

// Both should log start/end to ingestion_runs
```

For local development, run manually:
```bash
php artisan app:ingest-reddit
php artisan app:ingest-hackernews
```

Create corresponding Artisan commands that dispatch the jobs synchronously for local testing.

---

## Filament Resource

### `RawSignalResource`
Location: `app/Filament/Resources/RawSignalResource.php`

**List view columns:**
- Source (badge — reddit/hackernews)
- Title (truncated to 80 chars)
- Score
- Comment count
- Category (subreddit or HN type)
- Processed (boolean badge)
- Published at
- Created at

**Filters:**
- Source
- Processed (yes/no)
- Flagged (yes/no)
- Score range
- Date range

**Actions:**
- View full content (modal)
- Flag for review
- Mark as processed (manual override)

**Stats widgets on dashboard:**
- Total signals ingested
- Signals by source (bar chart)
- Unprocessed signals count
- Last ingestion run per source

### `IngestionRunResource`
Simple read-only table showing ingestion run history:
- Source, query, signals found/inserted/skipped, status, duration, timestamp

---

## Artisan Commands

```bash
# Run all ingestion sources
php artisan ingestion:run

# Run specific source
php artisan ingestion:run --source=reddit
php artisan ingestion:run --source=hackernews

# Show ingestion stats
php artisan ingestion:stats
```

---

## Error Handling

- Wrap each API call in try/catch — log failure to `ingestion_runs`, continue to next query
- Rate limit errors (429): retry after delay, log warning
- Empty responses: log as 0 signals found, not an error
- Network timeouts: fail gracefully, mark run as 'partial'
- Never let one failed query kill the entire ingestion run

---

## Environment Variables Needed

```env
# Reddit
REDDIT_CLIENT_ID=
REDDIT_CLIENT_SECRET=
REDDIT_USER_AGENT="Lightbulb/1.0"

# Queue
QUEUE_CONNECTION=database

# DB (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=lightbulb
DB_USERNAME=
DB_PASSWORD=
```

---

## Definition of Done

Phase 1 is complete when:
1. Both migrations run clean on fresh database
2. `php artisan ingestion:run` pulls real signals from Reddit and HN
3. Duplicate signals are skipped without error
4. Ingestion runs are logged to `ingestion_runs`
5. Filament dashboard shows raw signals browseable with filters
6. Scheduler is configured (even if not running in prod yet)

Phase 2 (scoring agent) starts after this is confirmed working.

---

## What Phase 2 Will Need From This

The scoring agent in Phase 2 will:
- Query `raw_signals` where `processed = false`
- Group related signals into candidate ideas
- Score each against the rubric in `idea-scoring-criteria.md`
- Write results to an `ideas` table (Phase 2 schema)
- Mark signals as `processed = true`

Build Phase 1 without coupling to Phase 2. Keep `raw_signals` generic.
