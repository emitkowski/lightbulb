# TESTING_COVERAGE.md
_Updated by Claude after running the coverage command and reviewing results_
_Do not update without running the coverage command first — never estimate from memory_

Legend: `[covered]` = dedicated test file, `[partial]` = some paths tested, `[none]` = no test.

---

## Coverage snapshot

| Suite | Tool | Overall | Threshold | Status | Last run |
|---|---|---|---|---|---|
| Backend | PHPUnit | Lines: 86.59% | 80% | ✓ above threshold | 2026-07-07 |

---

# Backend (PHPUnit)

**Suite:** 295 passed, 0 failing, 7 skipped, 0 notices.
**Overall coverage:** Classes 36.49% (27/74) · Methods 53.78% (121/225) · Lines 86.62% (2544/2937)

> Re-run `vendor/bin/sail php vendor/bin/phpunit --coverage-text --colors=never` whenever a tracked file's coverage moves materially.

## How to run

```bash
vendor/bin/sail artisan test --compact                                        # run the suite
vendor/bin/sail php vendor/bin/phpunit --coverage-text --colors=never        # per-file coverage report
```

---

## Area 1 — Core business logic (ingestion + scoring)

| File | Lines% | Status | Notes |
|---|---|---|---|
| `app/Services/Ingestion/IngestionService.php` | 100% | `[covered]` | All paths exercised by ingestion job tests |
| `app/Services/Scoring/ClusteringService.php` | 100% | `[covered]` | Full coverage via ClusterSignalsJobTest |
| `app/Services/Scoring/ScoringAgentService.php` | 98% | `[covered]` | CLI + API driver paths, stub fallback, code-fence JSON, kill conditions all tested |
| `app/Services/Scoring/ClaudeCliRunner.php` | 96% | `[covered]` | `run()`, `buildEnv()`, `createTempHome()`, `removeTempHome()` tested via fake shell script; `removeDirectory()` covered indirectly |
| `app/Services/Scoring/CompetitionSearchService.php` | 100% | `[covered]` | All paths: stub, success, API error, exception, empty results |
| `app/Services/Ingestion/ApifyService.php` | 100% | `[covered]` | Dedicated `ApifyServiceTest` — asserts on actual request URL (`~` conversion), success, HTTP failure, no-token, `hasToken()` both branches |

## Area 2 — Ingestion jobs

| File | Lines% | Status | Notes |
|---|---|---|---|
| `app/Jobs/Ingestion/IngestHackerNewsSignalsJob.php` | 98% | `[covered]` | |
| `app/Jobs/Ingestion/IngestRedditSignalsJob.php` | 84% | `[covered]` | Rate-limit (429), date-cutoff, API error, and `getAccessToken` exception path all tested |
| `app/Jobs/Ingestion/IngestGitHubIssuesJob.php` | 97% | `[covered]` | |
| `app/Jobs/Ingestion/IngestVSCodeMarketplaceSignalsJob.php` | 97% | `[covered]` | |
| `app/Jobs/Ingestion/IngestStackOverflowSignalsJob.php` | 97% | `[covered]` | |
| `app/Jobs/Ingestion/IngestProductHuntSignalsJob.php` | 99% | `[covered]` | |
| `app/Jobs/Ingestion/IngestDevToSignalsJob.php` | 99% | `[covered]` | |
| `app/Jobs/Ingestion/IngestAlternativesSearchJob.php` | 99% | `[covered]` | |
| `app/Jobs/Ingestion/IngestProductRoadmapsJob.php` | 96% | `[covered]` | |
| `app/Jobs/Ingestion/IngestCapterraBuyerGuidesJob.php` | 96% | `[covered]` | |
| `app/Jobs/Ingestion/IngestG2ReviewsJob.php` | 99% | `[covered]` | |
| `app/Jobs/Ingestion/IngestAppSumoSignalsJob.php` | 87% | `[covered]` | Product-level path slightly lower than review path |
| `app/Jobs/Ingestion/IngestChromeExtensionSignalsJob.php` | 96% | `[covered]` | |
| `app/Jobs/Ingestion/IngestGumroadSignalsJob.php` | 99% | `[covered]` | |
| `app/Jobs/Ingestion/IngestFreelancePlatformsJob.php` | 98% | `[covered]` | |
| `app/Jobs/Ingestion/IngestTwitterSignalsJob.php` | 87% | `[covered]` | No-token, 429 rate-limit, API error, dedup, username resolution all tested |
| `app/Jobs/Ingestion/IngestPeoplePerHourSignalsJob.php` | 96% | `[covered]` | No-token, min-budget, max-age, dedup, run-stats all tested |
| `app/Jobs/Ingestion/IngestGuruSignalsJob.php` | 97% | `[covered]` | Same coverage shape as PeoplePerHour plus string-range budget parsing |
| `app/Jobs/Ingestion/IngestLaraJobsSignalsJob.php` | 87% | `[covered]` | RSS parse, max-age filter, dedup by guid, feed-error and malformed-XML failure paths all tested |
| `app/Jobs/Ingestion/IngestIndeedSignalsJob.php` | 97% | `[covered]` | No-token, missing-url/title, max-age, dedup, run-stats all tested |
| `app/Jobs/Ingestion/IngestLinkedInJobsSignalsJob.php` | 97% | `[covered]` | Same coverage shape as Indeed |

## Area 3 — Scoring jobs

| File | Lines% | Status | Notes |
|---|---|---|---|
| `app/Jobs/Scoring/ClusterSignalsJob.php` | 100% | `[covered]` | |
| `app/Jobs/Scoring/ScoreIdeaJob.php` | 93% | `[covered]` | Kill condition and gate-failed paths tested; queue middleware path not tested |

## Area 4 — Models

| File | Lines% | Status | Notes |
|---|---|---|---|
| `app/Models/RawSignal.php` | 89% | `[partial]` | Scopes not explicitly tested |
| `app/Models/Idea.php` | 86% | `[partial]` | Factory states tested; computed score accessor not tested |
| `app/Models/IngestionRun.php` | 86% | `[partial]` | |
| `app/Models/SuccessPattern.php` | 100% | `[covered]` | |
| `app/Models/IdeaSignal.php` | 33% | `[partial]` | Pivot model — FK relations only; no dedicated tests |
| `app/Models/TeamInvitation.php` | 90% | `[covered]` | `generate()` and `isPending()` both tested |
| `app/Models/User.php` | 50% | `[partial]` | Auth paths covered; `teams()` relation not tested |

## Area 5 — HTTP / Filament / Providers

| File | Lines% | Status | Notes |
|---|---|---|---|
| `app/Filament/Resources/IdeaResource.php` | 96% | `[covered]` | Smoke test covers `table()`, `form()`, `canCreate()`, `getPages()` |
| `app/Filament/Resources/IngestionRunResource.php` | 97% | `[covered]` | Smoke test covers index page; `ListIngestionRuns` page class 100% via `ListIngestionRunsPageTest` |
| `app/Filament/Resources/RawSignalResource.php` | 100% | `[covered]` | Index + create pages both tested |
| `app/Http/Middleware/HandleInertiaRequests.php` | 100% | `[covered]` | |
| `app/Providers/*` | 100% | `[covered]` | All providers boot paths exercised by suite |
| `app/Notifications/TeamInvitationNotification.php` | 100% | `[covered]` | |
| `app/Http/Responses/LoginResponse.php` | 100% | `[covered]` | JSON, Inertia-location, and plain-redirect branches all tested |
| `app/Http/Responses/TwoFactorLoginResponse.php` | 100% | `[covered]` | Same coverage shape as `LoginResponse` |

---

## What's left to tackle

1. **`User::teams()` / `currentTeam()` relations** — not directly tested; exercised at the DB level but not via test assertions. Low priority.

---

## Run history

| Date | Suite | Lines% | Tests | Duration |
|---|---|---|---|---|
| 2026-07-07 | PHPUnit | 86.62% | 295 passed / 7 skipped / 0 notices | — |
| 2026-07-01 | PHPUnit | 86.59% | 279 passed / 7 skipped / 0 notices | — |
| 2026-07-01 | PHPUnit | 86.05% | 275 passed / 7 skipped / 0 notices | — |
| 2026-07-01 | PHPUnit | 85.97% | 269 passed / 7 skipped / 0 notices | — |
| 2026-07-01 | PHPUnit | 85.34% | 257 passed / 7 skipped / 0 notices | — |
| 2026-06-30 | PHPUnit | 84.55% | 234 passed / 7 skipped / 0 notices | — |
| 2026-06-28 | PHPUnit | 84.47% | 226 passed / 7 skipped / 0 notices | ~73s |
| 2026-06-28 | PHPUnit | 72.14% | 217 passed / 7 skipped / 0 notices | 71.12s |
| 2026-06-26 | PHPUnit | 72.14% | 210 passed / 7 skipped | — |
| 2026-06-26 | PHPUnit | 68.73% | 192 passed / 7 skipped | 12.29s |
