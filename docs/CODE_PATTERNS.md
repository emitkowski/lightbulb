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
**Example:** `IngestAlternativesSearchJob`, `IngestProductRoadmapsJob`

### source_id for Serper signals
**Context:** Deduplicating Serper search results. **Pattern:** `{tool_or_category_key}:{substr(md5($url), 0, 12)}` where the key is `strtolower($this->tool)`. Same URL found for two different tools → two different source_ids → two signals stored (both are valid gap indicators for their respective tools).

### Unsafe array key access in tests
**Context:** When actor returns items with optional fields. **Pattern:** Always use `$item['key'] ?? null` (or `$item['key'] ?? $item['altKey'] ?? default`). Never access `$item['key']` bare inside a nested expression — PHPUnit converts E_WARNING (undefined array key) to an exception, triggering the job's catch block and logging a spurious 'failed' run.

### Direct RSS/XML feed ingestion (no API, no Apify)
**Context:** A source exposes a plain public RSS/XML feed with no auth required (e.g. LaraJobs). **Pattern:** `Http::get($feedUrl)`, then `@simplexml_load_string($response->body())` — never pass the 4th/5th namespace-filter args to `simplexml_load_string`, they filter the ROOT element and silently return `false` if the root isn't in that namespace (see `docs/memory/laravel.md`, 2026-07-01). Use `$item->children('prefix', true)` per-item to read namespaced fields (e.g. `job:location`, `dc:creator`). No constructor args needed on the job since there's one feed, not a query loop — the command dispatches it once.
**Example:** `IngestLaraJobsSignalsJob`

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
