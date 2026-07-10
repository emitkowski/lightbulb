# CODE_PATTERNS.md
_How this project specifically solves recurring problems_
_Claude-maintained — check for existing pattern before appending_
_Project-specific only — not general conventions_

## Structural patterns

### Ingestion jobs
**Context:** All signal ingestion jobs. **Pattern:** Each job takes its source-specific key(s) as constructor args, calls `IngestionService::startRun()` first, then `insertSignal()` per item, then `finishRun()` with stats. Both the no-API-key check and the exception catch call `finishRun` with `status='failed'`. Jobs inject `IngestionService` (and optionally `ApifyService`) via `handle()` params.
**Example:** `IngestDevToSignalsJob`, `IngestG2ReviewsJob`

### Apify actor calls
**Context:** Any job that scrapes via Apify. **Pattern:** Inject `ApifyService` via `handle()`; call `$apifyService->hasToken()` first and log a failed run if false; then call `$apifyService->runSync($actorId, $input)` inside the main `try` block — it throws `RuntimeException` on HTTP failure, returns `[]` on empty success. Actor IDs come from `config('ingestion.apify.{source}.actor_id')`.
**Example:** `IngestG2ReviewsJob`, `IngestFreelancePlatformsJob`

### Serper.dev search in ingestion jobs
**Context:** Jobs that search Google via Serper. **Pattern:** Check `config('scoring.serper.api_key')` first (reuses the same config key as the scoring pipeline). POST to `config('scoring.serper.base_url') . '/search'` with `X-API-KEY` header. Non-200 responses → `continue` to next query, log warning; overall run finishes as 'success' with whatever was found.
**Example:** `IngestAlternativesSearchJob`, `IngestProductRoadmapsJob`, `IngestIndieHackersSignalsJob` (used when a source has no public API/RSS and isn't scrapable directly, e.g. a client-rendered SPA — site-restrict the Serper query instead of building a dedicated scraper)

### source_id for Serper signals
**Context:** Deduplicating Serper search results. **Pattern:** `{tool_or_category_key}:{substr(md5($url), 0, 12)}` where the key is `strtolower($this->tool)`. Same URL found for two different tools → two different source_ids → two signals stored (both are valid gap indicators for their respective tools).

### Unsafe array key access in tests
**Context:** When actor returns items with optional fields. **Pattern:** Always use `$item['key'] ?? null` (or `$item['key'] ?? $item['altKey'] ?? default`). Never access `$item['key']` bare inside a nested expression — PHPUnit converts E_WARNING (undefined array key) to an exception, triggering the job's catch block and logging a spurious 'failed' run.

### Direct RSS/XML feed ingestion (no API, no Apify)
**Context:** A source exposes a plain public RSS/XML feed with no auth required (e.g. LaraJobs). **Pattern:** `Http::get($feedUrl)`, then `@simplexml_load_string($response->body())` — never pass the 4th/5th namespace-filter args to `simplexml_load_string`, they filter the ROOT element and silently return `false` if the root isn't in that namespace (see `docs/memory/laravel.md`, 2026-07-01). Use `$item->children('prefix', true)` per-item to read namespaced fields (e.g. `job:location`, `dc:creator`). No constructor args needed on the job since there's one feed, not a query loop — the command dispatches it once.
**Example:** `IngestLaraJobsSignalsJob`

### Apify actor metadata reads (unbilled) vs. runs (billed)
**Context:** Checking an actor's health/stats rather than scraping data through it. **Pattern:** `ApifyService::getActorInfo($actorId)` calls `GET /v2/acts/{id}` — a metadata read that is NOT billed against Apify usage credits, unlike `runSync()`'s `POST .../run-sync-get-dataset-items`. Same URL-safe `/`→`~` actor ID conversion as `runSync()`, same no-token/HTTP-failure handling shape. Use this when a job needs to know *about* an actor (stats, reviews, categories) without running it.
**Example:** `IngestApifyActorGapsJob` — checks `stats.publicActorRunStats30Days` and `stats.actorReviewCount`/`actorReviewRating` on the actor IDs this app already depends on, to detect a struggling supplier as a market-gap signal. Note: `publicActorRunStats30Days.SUCCEEDED` only means the run didn't crash, not that it did real work (confirmed live 2026-07-08/09 against actors already known to be broken) — treat failure-rate as a weak supplementary signal, not proof on its own.

### Reusing an existing generic job for a new layer instead of writing a new one
**Context:** A new ingestion layer's source is mechanically identical to an already-built one (same API, different query/category/repo). **Pattern:** If the existing job's constructor already takes the varying part as an argument (e.g. `IngestRedditSignalsJob($subreddit, $query)`, `IngestGitHubIssuesJob($repo)`), dispatch it directly from the new layer's command method with new config values — don't write a new job class just to get a different `source` value in `raw_signals`. The new layer's signals land under the existing source value, distinguished by `category`.
**Example:** Layer 21 dispatches `IngestGitHubIssuesJob` against `apify/*` repos and `IngestRedditSignalsJob` against `r/webscraping` unmodified — see `runApifyGaps()` in `IngestionRunCommand`.

### Direct HTML scrape of a server-rendered page (no API, no Apify, no RSS)
**Context:** A source has no API/RSS but its listing page IS server-rendered HTML (unlike a client-rendered SPA — check with a plain `curl`/`Http::get` first and look for real content in the raw response, not just a 200 status). **Pattern:** `Http::get($url)`, then parse with PHP's built-in `DOMDocument`/`DOMXPath` (no new Composer dependency needed — `ext-dom` ships with PHP). Always call `libxml_use_internal_errors(true)` and pass `LIBXML_NOERROR | LIBXML_NOWARNING` to `loadHTML()` (plus `@` on the call) — malformed HTML5 tags otherwise emit E_WARNING, which PHPUnit converts to an exception (same class of gotcha as the RSS pattern below, see `docs/memory/testing.md`). No constructor args needed when there's one page to crawl, not a query loop.
**Example:** `IngestPaddleCustomersJob` — deduplicates each case study by `href` and keeps the longest matching text when the same link appears twice on the page (a compact nav link and a fuller card), rather than relying on CSS class names, which are build-hashed and likely to change.

### Verify a target site is actually scrapable before building against it
**Context:** Before writing a scraper (direct HTTP, Serper, or Apify) against a new site, especially one described in an old spec doc. **Pattern:** `curl` the real URL first and check for actual server-rendered content (e.g. `grep` for expected listing links), not just a 200 status — heavy JS marketing sites (Next.js, etc.) return 200 with an empty shell. If the page is client-rendered, fall back to the Serper site-search pattern instead of direct scraping (or Apify if a suitable actor exists). If the URL 404s or the feature is gone entirely, don't build it — document it as ruled out.
**Example:** Layer 11 — `stripe.com/customers` returns 0 real listing links via plain GET (client-rendered) while `paddle.com/customers` returns 50+ (server-rendered), so Stripe uses Serper site-search and Paddle uses direct HTML scraping. Lemon Squeezy's `/discover` directory 404s entirely (discontinued post-Stripe-acquisition) and isn't built at all — confirmed live before writing any code, not assumed from the original spec.

## Naming conventions
[Project-specific naming beyond language/framework standards.]

## Cross-cutting concerns
[Auth, logging, error handling — how this project does it specifically.]

## Anti-patterns — do not replicate
[Things in the codebase that should not be copied. Name the specific file/class.]

---

<!-- Entry format:
### [Pattern name]
**Context:** [When this applies] | **Pattern:** [What to do] | **Anti-pattern:** [What NOT to do]
**Example:** [Brief code reference]
-->

<!-- Review and remove obvious patterns when file exceeds 150 lines -->
