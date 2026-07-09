# STATUS.md
_Last updated: 2026-07-09 by Claude_

## Current phase
**Phase 4 — Live-verified, with corrections** (19 of 22 built sources confirmed working with real data; 1 Apify actor still unresolved — see below; only Reddit and Twitter still need keys)

## Project health
| Indicator | Status | Detail |
|---|---|---|
| Build | ✓ passing | Sail + Vite functional (Filament v5 assets rebuilt) |
| Tests | ✓ passing | 311/311 pass, 0 failures, 7 skipped, 0 notices |
| Coverage | ✓ 86.68% | Above 80% threshold |
| Open bugs | ⚠ 1 (medium) | BUG-10 (Upwork) — 3 actors tried, all fail differently; blocked on exhausted Apify credit until 2026-08-01, see docs/BUGS.md |
| Blocker | none | — |

## Last meaningful progress
- **Upgraded Filament v3.3.54 → v5.6.8 (skipping v4 as a resting point) and updated all other packages.** All routine composer/npm patch-minor updates applied first (Laravel 13.19, Inertia, Ziggy, etc.), then the two-hop Filament upgrade via its official codemods (`filament-v4`, `filament-v5`), which also pulled Livewire to v4.3.3. The v4 codemod handled most breaking changes automatically (Form→Schema signatures, action-method renames, icon type hints, header-action `->form()`→`->schema()`); manual fixes were limited to 4 `BadgeColumn`→`TextColumn::badge()` conversions and one `tableFilters[...]`→`filters[...]` URL query string. v4→v5 needed zero code changes. Verified end-to-end: 311/311 tests pass, `npm run build` succeeds, all 6 admin pages load error-free under an authenticated session, and the `BroadcastPingWidget` renders + dispatches correctly under Livewire v4 (added `BroadcastPingWidgetTest`, previously untested, now 100%). One caveat: the widget's client-side WebSocket round-trip (Echo→`$wire.onPing` over Reverb) is only manually verifiable — flagged for a browser click next time the panel is open. Full detail in `docs/ARCHITECTURE_HISTORY.md` (2026-07-09) and `docs/memory/laravel.md`.
- **Built Layer 5 (Indie Hackers) and live-verified it.** Indie Hackers has no public API/RSS and is a client-rendered SPA (confirmed via a direct fetch returning no server-rendered HTML) — a headless browser or Apify actor would be needed for direct scraping. Instead, reused the already-established Serper.dev site-search pattern (`site:indiehackers.com`, same as Layers 3/10/19), needing no new credentials or Apify budget. Added `IngestIndieHackersSignalsJob`, wired into `ingestion:run --source=indiehackers`, and live-verified: found 2 real IH posts on first run, including one containing the exact phrase "does anyone know a tool that does X" — the target signal type. Also bumped the pipeline source count from 21 to 22 layers built.
- **Tried 2 more Upwork actors for BUG-10, both failed differently, exhausting the remaining Apify credit.** `energizing_technology/cheapest-upwork-jobs-scraper` looked promising (100% success stats, flat per-run pricing) but its run log revealed it does zero real scraping — just echoes input and exits. `curious_coder/upwork-jobs-scraper` (same trusted author as the working `linkedin` source) genuinely attempted browser-based scraping but failed to establish a session after 4 retries, erroring with "Could not connect to Upwork right now" — suggesting Upwork may currently resist automated access broadly, not just from one bad actor. Apify's $5/month free-tier cap is now fully exhausted (~$0.08 left); no further live testing possible until it resets 2026-08-01.
- **Fixed 2 of the 3 broken Apify sources (BUG-7 AppSumo, BUG-11 Guru) by finding and live-verifying replacement actors**, rather than accepting them as permanently broken. Both original actors' replacements (`shahidirfan/appsumo-scraper`, `shahidirfan/guru-com-scraper`) use a different, simpler input schema (`keyword`/`results_wanted`) than the old actors — updated both jobs' input construction and added field-name fallbacks for each actor's actual output shape. Live-verified both with real inserted signals (AppSumo: "BreezeDoc", 162 reviews, 3.72★; Guru: 3 real Guru.com job postings). **BUG-10 (Upwork) remains unresolved** — the original actor turned out not to be deterministically broken as previously diagnosed (returned real data on retest), but the job's field mapping is stale (checks camelCase field names against an actor that returns snake_case) and no budget field was found at all. A blind test-call using the old job's param names cost $4.81 in Apify credit (the actor likely ran uncapped because the param name wasn't recognized) — logged as a hard lesson in `docs/memory/gotchas.md`: always fetch an actor's real input schema before a live test, never reuse another actor's param names.
- **Unified auth on a single login page and fixed an Inertia "modal" bug that hit it from two directions.** The app had accumulated two separate login pages (Jetstream's `/login` and a redundant Filament-scaffolded `/admin/login`, from calling `->login()` on the panel) — removed the Filament one so all unauthenticated access funnels to `/login`. While fixing this, found and fixed a real UX bug: any plain `redirect()` from an Inertia-driven page to a non-Inertia destination (Filament's `/admin`) caused Inertia's client to render the raw HTML inside a fallback modal `<dialog>` instead of navigating. Root-caused two separate triggers of this — (1) the app's own `/dashboard` route, and (2) Fortify's default `LoginResponse`/`TwoFactorLoginResponse`, which redirect to whatever URL is in the `url.intended` session key (set automatically whenever a guest is bounced from any auth-guarded route, including `/admin` — bypassing `/dashboard` entirely). Fixed both by using `inertia()->location()` instead of a raw `redirect()`, added `app/Http/Responses/LoginResponse.php` + `TwoFactorLoginResponse.php` bound in `FortifyServiceProvider`. Also made `/` redirect guests to `/login` instead of showing the Welcome page. All new behavior covered by tests (11 new tests, 100% coverage on the 2 new response classes); full details in `docs/memory/laravel.md`.
- **Root-caused all 3 broken Apify actors via their own run logs** (`GET /v2/actor-runs/{id}/log`), rather than leaving them as generic "times out" guesses:
  - **BUG-7 (AppSumo):** `easyapi/appsumo-product-scraper`'s headless browser crashes (`BrowserPool: Page crashed!`) on every single navigation to an AppSumo page — 3/3 retries crashed, 0 succeeded. Deterministic, not transient; consistent with AppSumo killing automated browser sessions on detection.
  - **BUG-10 (Upwork):** `getdataforme/upwork-actor`'s container runs out of disk space downloading its CAPTCHA-solver dependency on every attempt (`[Errno 28] No space left on device`), so it silently gives up on Upwork's CAPTCHA and returns empty.
  - **BUG-11 (Guru), corrected:** originally reported as "worked live then trial expired" — that was wrong. The 100-found/87-inserted figure actually belongs to Gumroad's test, mixed up when writing the bug report. `ingestion_runs` has exactly one row ever for `guru`, and it's a failed 403 (`actor-is-not-rented`). There's no evidence this actor ever worked — the trial may have already been expired on the very first attempt.
  - None of these 3 are fixable from our side — they're bugs/limits in the third-party actors' own environments, not our code or config. Added `docs/memory/gotchas.md` guidance: when an Apify actor returns 2xx with an empty dataset, pull its run log before assuming it's a query/filter problem — and double-check which source a result number actually came from before writing it into a bug report.
- **Added `ingestion:run --limit=N` and `--free-only` flags** to smoke-test the pipeline cheaply. Ran both live: `--free-only --limit=2` (6 zero-key sources, clean), and `--limit=1` across all 20 sources for real — this is what surfaced BUG-10 and BUG-11 in the first place.
- Coverage cleanup: added a test for `IngestRedditSignalsJob::getAccessToken()`'s exception path, and a full `ListIngestionRunsPageTest` (Filament page action) — that page went from ~54% to 100%. 86.05% → 86.59%, 275 → 284 tests.
- **`PRODUCTHUNT_API_KEY` configured and `producthunt` live-verified** — found 2 real bugs, both invisible until a real key existed: **BUG-8** (`order: BEST` isn't a valid GraphQL enum; fixed) and **BUG-9** (default post/comment counts exceeded Product Hunt's query-complexity cap; reduced defaults, verified live).
- **`SERPER_API_KEY` configured and all 3 Serper-based ingestion jobs live-verified with real data.** Caught a real key mix-up first (developer's initial key was SerpApi.com, not Serper.dev) before wasting a verification cycle.
- **Live-verified all 6 zero-key ingestion sources for the first time.**
- Fixed 2 broken Apify actors: **Chrome** and **Gumroad**, both live-verified.
- Real `APIFY_TOKEN` configured this session, which surfaced BUG-6 (fixed: `ApifyService` built malformed URLs — affected all 9 Apify sources).
- Built Layer 6 (job boards), corrected the ingestion-layer map, expanded Layer 6b, closed the coverage gap 72.14% → 86.59% across sessions.

## What's next
1. **Run the full pipeline for real** — 19/22 sources confirmed reliably working; only `freelance`/Upwork (BUG-10) remains broken. Add the 2 remaining keys (`REDDIT_CLIENT_ID/SECRET`, `TWITTER_BEARER_TOKEN`), seed success_patterns, run `ingestion:run` across all sources + `scoring:run`, review ideas in Filament
2. **Resolve BUG-10 (Upwork)** — blocked until Apify credit resets 2026-08-01 (or the developer adds billing). 3 of ~159 candidate actors tried and ruled out so far (see docs/BUGS.md); next attempt should check run logs for real scraping activity (not just success stats) before spending on a full test, and confirm a usable budget field exists before wiring into the job
3. **Add remaining ingestion layers** — see table below; Layer 11 (Stripe/Paddle/Lemon Squeezy) is next since it's also free and no-auth, and enriches the Layer 7 success-pattern corpus (currently only 30 hand-seeded entries)

## Remaining ingestion layers (not yet built)
_Corrected 2026-07-01 against the real `docs/build/signal-sources.md` layer numbers — the previous version of this table used wrong layer numbers. Layer 5 (Indie Hackers) built 2026-07-09._

| Layer | Source | Notes |
|---|---|---|
| 4 | Google Trends (Pytrends) | No Laravel package; would need a Python subprocess or unofficial HTTP wrapper. Lowest signal-quality rank in the spec (2 stars) |
| 11 | Stripe/Paddle/Lemon Squeezy directories | Used more for Layer 7 corpus enrichment than raw signal |
| 20 | Public Slack/Discord archives | Laravel/Vue/Filament Discords; requires a Discord bot token |

**Partial/ruled-out within built layers:**
- Layer 2 (G2/Capterra/Trustpilot) — only G2 built, Trustpilot not attempted
- Layer 6b (freelance postings) — PeoplePerHour, LaraJobs, and Guru (BUG-11 fixed 2026-07-08) reliably working; Upwork (BUG-10) still broken; r/forhire built (needs Reddit key); Codeable ruled out (gated, no public listings); Contra ruled out (no project-posting data, only freelancer profiles)
- Layer 18 (dev influencer Twitter) — built as general keyword search, deviates from spec's account-specific monitoring (documented, developer's call)

## Metrics snapshot
| Metric | Value | Trend |
|---|---|---|
| Test count | 307 passing / 7 skipped | +23 this session |
| Coverage | 86.97% lines | up from 86.59% |
| Open bugs | 1 (medium, non-blocking) | -2 this session (BUG-7 AppSumo, BUG-11 Guru fixed) |
| Blocking bugs | 0 | — |
| Ingestion layers | 22 built, **19/22 confirmed reliably working live** | up from 21 built/16 working after fixing BUG-7, BUG-11, and building Layer 5 (Indie Hackers) |
| Milestones complete | 3/N | Phase 1 + Phase 2 + Phase 3 ingestion |
