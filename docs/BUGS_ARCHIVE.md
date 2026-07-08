# BUGS_ARCHIVE.md
_Fixed bugs — moved here from BUGS.md once verified resolved_
_Append-only — never edit or delete entries_
_Claude-maintained — move from BUGS.md immediately after fix is verified_

## Format
### YYYY-MM-DD — [Bug title]
**Was:** [What the bug was and where it occurred]
**Fix:** [What was done to resolve it]
**Commit/PR:** [Reference if known]

---

### 2026-06-26 — BUG-1: Pre-existing Jetstream test failures (8 tests)
**Was:** Four test groups failed after app switched to UUID PKs and simplified Jetstream teams: (1) `team_members.id` NOT NULL violated on SQLite pivot `attach()` — migration had a UUID PK column but BelongsToMany pivot insert never populates it; (2) `TeamInvitation::generate()` had `int $invitedBy` but User IDs are now UUID strings; (3) `ExampleTest` expected redirect from `/` but route returns 200 (Inertia welcome page); (4) `PasswordConfirmationTest` called `UserFactory::withPersonalTeam()` which was removed; (5) `TeamInvitationNotification::toMail()` called `route('team.invitation')` which didn't exist.
**Fix:** Removed `uuid('id')->primary()` from `team_members` migration — replaced with composite `primary(['team_id', 'user_id'])`; changed `generate()` type hint from `int` to `string`; fixed ExampleTest assertion to `assertOk()`; removed `->withPersonalTeam()` from PasswordConfirmationTest; added `team.invitation` GET/POST routes to `routes/web.php`.
**Covered by:** Full suite — 185 passing, 0 failing.

### 2026-06-26 — BUG-2: DashboardTest expects 200 but route returns 302
**Was:** `tests/Feature/DashboardTest::test_authenticated_user_can_access_dashboard` asserted `assertOk()` (200) but `/dashboard` intentionally redirects authenticated users to `/admin` (Filament panel).
**Fix:** Changed assertion to `assertRedirect('/admin')`.
**Covered by:** `tests/Feature/DashboardTest`.

### 2026-06-28 — BUG-3: User model missing FilamentUser interface
**Was:** `User` did not implement `Filament\Models\Contracts\FilamentUser`, so Filament v3 returned 403 for every authenticated user, including in tests via `actingAs()`. Discovered while writing admin panel smoke tests.
**Fix:** Added `implements FilamentUser` and `canAccessPanel(Panel $panel): bool` returning `true` (solo-developer tool, no per-user restriction needed).
**Covered by:** `tests/Feature/FilamentAdminSmokeTest`.

### 2026-06-28 — BUG-4: ClaudeCliRunner used invalid Process named parameter
**Was:** `ClaudeCliRunner::run()` called `Process::fromShellCommandline(..., working_directory: base_path(), ...)` but Symfony's method signature names that parameter `$cwd`, not `$working_directory`. Every real CLI scoring call would throw `Error: Unknown named parameter`. Discovered while writing subprocess-level tests with a fake `claude` shell script.
**Fix:** Changed the named parameter to `cwd: base_path()`.
**Covered by:** `tests/Unit/Services/Scoring/ClaudeCliRunnerRunTest`.

### 2026-06-30 — BUG-5: IngestionStatsCommand only showed reddit/hackernews
**Was:** `ingestion:stats` hardcoded `collect(['reddit', 'hackernews'])` for its signal-count table, so none of the 13 sources added in Phase 3 (or Twitter, added this session) ever appeared in the stats output.
**Fix:** Replaced the hardcoded list with `RawSignal::distinct()->orderBy('source')->pluck('source')`, so the table always reflects whatever sources actually have data.
**Covered by:** `tests/Feature/IngestionStatsCommandTest`.

### 2026-07-01 — BUG-6: ApifyService built malformed URLs for every actor call
**Was:** `ApifyService::runSync()` interpolated the actor ID directly into the URL path as `/acts/{$actorId}/run-sync-get-dataset-items`. Every actor ID in this project is stored in human-readable `username/actor-name` form (matching how Apify displays it on apify.com and in its own docs) — but Apify's API requires that literal `/` to be either URL-encoded or replaced with `~` in the path, since a raw `/` is parsed as additional path segments. This meant every single Apify-based ingestion job (9 sources: g2, appsumo, chrome, gumroad, freelance/upwork, peopleperhour, guru, indeed, linkedin) would 404 on any real run, regardless of whether the actor itself existed. Discovered only once a real `APIFY_TOKEN` was configured and a live test was run — no test in the suite had ever asserted on the actual request URL, only on the faked response, so `Http::fakeSequence()` masked the bug in every test.
**Fix:** `ApifyService::runSync()` now does `str_replace('/', '~', $actorId)` before building the URL.
**Covered by:** `tests/Feature/ApifyServiceTest::test_run_sync_converts_slash_actor_id_to_tilde_in_url` — asserts on the actual request URL via `Http::assertSent()`, closing the exact gap that let this ship unnoticed.

### 2026-07-01 — 5 fabricated Apify actor IDs from earlier sessions replaced with real ones
**Was:** `epctex/g2-scraper`, `epctex/appsumo-scraper`, `epctex/chrome-web-store-scraper`, `epctex/gumroad-scraper`, `epctex/upwork-scraper` were never real Apify actors — confirmed via `GET /v2/acts/{id}` returning `record-not-found` for all 5. Likely invented as plausible-looking placeholders in an earlier session and never verified against the live Apify API (no token was configured until this session).
**Fix:** Found and verified real replacements for G2 (`memo23/g2-scraper`, no code change needed), Upwork (`getdataforme/upwork-actor`, required switching `IngestFreelancePlatformsJob` from `startUrls`-based input to `queries`-based input), Chrome (`vujeen/chrome-web-store-scraper`, switched `IngestChromeExtensionSignalsJob` from category-URL `startUrls` to keyword `searchQueries` input), and Gumroad (`muhammetakkurtt/gumroad-scraper`, switched `IngestGumroadSignalsJob` to `searchQueries` input and remapped fields — this actor exposes `ratings.count` rather than a lifetime sales count, so the job's filter was renamed from `min_sales_count` to `min_rating_count` to match reality). All 4 verified live end-to-end (real HTTP calls, real data returned, `status='success'` in `ingestion_runs`). AppSumo has no working free replacement — tracked as BUG-7.
**Covered by:** `tests/Feature/IngestFreelancePlatformsJobTest`, `tests/Feature/IngestChromeExtensionSignalsJobTest`, `tests/Feature/IngestGumroadSignalsJobTest` (all updated to match real actor output shapes), live verification against `api.apify.com` and via `dispatchSync()` in tinker (see BUG-6 fix, same session).

### 2026-07-01 — BUG-8: IngestProductHuntSignalsJob used an invalid GraphQL enum value
**Was:** The job's GraphQL query ordered comments with `order: BEST`, but Product Hunt's `CommentsOrder` enum only accepts `NEWEST` or `VOTES_COUNT` — every real call failed with `GraphQL error: Argument 'order' on Field 'comments' has an invalid value (BEST)`. Never caught before because no `PRODUCTHUNT_API_KEY` had been configured until this session, and the mocked tests fake the whole HTTP response rather than validating the query against Product Hunt's real schema.
**Fix:** Changed `order: BEST` to `order: VOTES_COUNT` (confirmed via GraphQL introspection: `query { __type(name: "CommentsOrder") { enumValues { name } } }`).
**Covered by:** `tests/Feature/IngestProductHuntSignalsJobTest` (unaffected, still passing), live verification via `dispatchSync()`.

### 2026-07-01 — BUG-9: IngestProductHuntSignalsJob's default config exceeded Product Hunt's GraphQL complexity limit
**Was:** Default config (`max_posts_per_topic=20`, `max_comments_per_post=30`) produced a query with complexity 963,202 — Product Hunt's API caps query complexity at 500,000, so every real call failed with `GraphQL error: Query has complexity of 963202, which exceeds max complexity of 500000`. Same root cause as BUG-8: never tested against the real API until a real key was configured.
**Fix:** Reduced defaults to `max_posts_per_topic=10`, `max_comments_per_post=15` (150 total comment nodes vs. 600) — verified live, well under the complexity budget.
**Covered by:** Live verification via `dispatchSync()` — 150 found, 4 inserted, `status='success'`.
