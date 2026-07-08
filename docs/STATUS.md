# STATUS.md
_Last updated: 2026-07-08 by Claude_

## Current phase
**Phase 4 — Live-verified, with corrections** (16 of 21 built sources confirmed working with real data; 3 Apify actors broken — see below; only Reddit and Twitter still need keys)

## Project health
| Indicator | Status | Detail |
|---|---|---|
| Build | ✓ passing | Sail + Vite functional |
| Tests | ✓ passing | 295/295 pass, 0 failures, 7 skipped, 0 notices |
| Coverage | ✓ 86.62% | Above 80% threshold |
| Open bugs | ⚠ 3 (medium) | BUG-7 (AppSumo), BUG-10 (Upwork), BUG-11 (Guru) — all Apify actor failures, see docs/BUGS.md |
| Blocker | none | — |

## Last meaningful progress
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
1. **Run the full pipeline for real** — 16/21 sources confirmed reliably working; `appsumo`, `freelance`/Upwork, and `guru` are all confirmed broken, not just flaky (see BUG-7, BUG-10, BUG-11). Add the 2 remaining keys (`REDDIT_CLIENT_ID/SECRET`, `TWITTER_BEARER_TOKEN`), seed success_patterns, run `ingestion:run` across all sources + `scoring:run`, review ideas in Filament
2. **Decide on the 3 broken Apify sources (BUG-7, BUG-10, BUG-11)** — rent the relevant actors (real cost) or accept appsumo/freelance/guru stay unreliable
3. **Add remaining ingestion layers** — see table below; Layer 5 (Indie Hackers) is highest priority since it also enriches the Layer 7 success-pattern corpus (currently only 30 hand-seeded entries)

## Remaining ingestion layers (not yet built)
_Corrected 2026-07-01 against the real `docs/build/signal-sources.md` layer numbers — the previous version of this table used wrong layer numbers._

| Layer | Source | Notes |
|---|---|---|
| 4 | Google Trends (Pytrends) | No Laravel package; would need a Python subprocess or unofficial HTTP wrapper. Lowest signal-quality rank in the spec (2 stars) |
| 5 | Indie Hackers & niche forums | Milestone posts + "Ask IH" threads, crawlable without auth. Also feeds Layer 7 corpus |
| 11 | Stripe/Paddle/Lemon Squeezy directories | Used more for Layer 7 corpus enrichment than raw signal |
| 20 | Public Slack/Discord archives | Laravel/Vue/Filament Discords; requires a Discord bot token |

**Partial/ruled-out within built layers:**
- Layer 2 (G2/Capterra/Trustpilot) — only G2 built, Trustpilot not attempted
- Layer 6b (freelance postings) — PeoplePerHour and LaraJobs reliably working; Upwork (BUG-10) and Guru (BUG-11) currently broken; r/forhire built (needs Reddit key); Codeable ruled out (gated, no public listings); Contra ruled out (no project-posting data, only freelancer profiles)
- Layer 18 (dev influencer Twitter) — built as general keyword search, deviates from spec's account-specific monitoring (documented, developer's call)

## Metrics snapshot
| Metric | Value | Trend |
|---|---|---|
| Test count | 284 passing / 7 skipped | +9 this session |
| Coverage | 86.59% lines | up from 86.05% |
| Open bugs | 3 (medium, non-blocking) | +2 this session (BUG-10 Upwork, BUG-11 Guru) |
| Blocking bugs | 0 | — |
| Ingestion layers | 21 built, **16/21 confirmed reliably working live** | corrected down from 18 after full-pipeline dry run exposed 2 flaky/broken sources |
| Milestones complete | 3/N | Phase 1 + Phase 2 + Phase 3 ingestion |
