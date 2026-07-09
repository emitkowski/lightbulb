# BUGS.md
_Known bugs — updated by Claude on discovery or after test failures_
_Claude writes immediately on discovery — do not wait for session end_
_Fixed and verified bugs move to docs/BUGS_ARCHIVE.md_

<!-- Severity: blocking=no further work | high=no workaround | medium=workaround exists | low=minor -->

## Open bugs

### BUG-10 — Upwork: no working Apify actor found yet; 3 tried, all fail or misbehave differently
- **Discovered:** 2026-07-01 (original "broken CAPTCHA solver" diagnosis). **Revised 2026-07-08:** tried 3 actors live, each fails a different way.
- **Affects:** `IngestFreelancePlatformsJob`, `config/ingestion.php` (`apify.freelance.actor_id`)
- **Severity:** medium
- **Description:** Three actors tested live on 2026-07-08:
  1. `getdataforme/upwork-actor` (original) — actually returned real data this time, contradicting the original "deterministic CAPTCHA crash" diagnosis (may have been transient). But its real response fields (`job_title`, `job_url`, `job_description`, snake_case) don't match the job's field mapping (`title`/`jobTitle`, `url`/`jobUrl`, camelCase) at all, and no budget/price field was present in the response — every item would be silently skipped as written. The test call also used the wrong input param names (`queries`/`item_limit`, from the old shape) which the actor didn't recognize, so it ran uncapped and cost $4.81 (PAID_ACTORS_PER_EVENT, $0.05/start + $0.035/result) — see `docs/memory/gotchas.md` 2026-07-08.
  2. `energizing_technology/cheapest-upwork-jobs-scraper` — despite "100% success" stats, its run log shows it does zero real scraping: forwards the input payload and exits in under a second every time. A non-functional stub, not usable regardless of price.
  3. `curious_coder/upwork-jobs-scraper` — genuinely attempts real browser-based scraping (same trusted author as the working `linkedin` source), but failed to establish a search session after 4 retries and gave up with `"Could not connect to Upwork right now"`. Suggests Upwork may currently be broadly resistant to automated access, not just hostile to one specific actor.
- **Blocking:** Apify's $5/month free-tier cap is now exhausted (~$0.08 remaining) for the rest of the cycle (resets 2026-08-01) — no further live actor testing possible until then. Only 3 of ~159 Upwork-related actors in the Apify store have been tried; there may still be a working one, but each further attempt costs real (if now depleted) budget.
- **Status:** open

<!-- BUG-N: scan this file for the highest existing number and increment by 1 -->
<!-- Format:
### BUG-[N] — [Short title]
- **Discovered:** YYYY-MM-DD via [test failure / code review / runtime]
- **Affects:** [file or module]
- **Severity:** [blocking / high / medium / low]
- **Description:** [What is wrong]
- **Blocking:** [What this prevents, or NONE]
- **Status:** open / investigating
-->

## Fixed bugs

### BUG-7 — AppSumo has no working Apify actor ✓
- **Fixed:** 2026-07-08
- **Fix:** Found and live-verified a replacement actor, `shahidirfan/appsumo-scraper` (free pricing, 100% success rate over 112 runs in the prior 30 days). Its input schema uses `keyword`/`results_wanted` rather than the old `startUrls`/`maxItems`/`includeReviews` shape, so `IngestAppSumoSignalsJob` now sends `keyword => str_replace('-', ' ', $category)`. Its output is deal/product-level only (no per-review text), so only the existing `processProduct()` path applies — added `review_count`/`description_text` as additional field-name fallbacks alongside the existing camelCase ones. Live-verified: `productivity-automation` returned 79 found / 2 inserted with real AppSumo deals (e.g. "BreezeDoc", 162 reviews, 3.72★).
- **Covered by:** `tests/Feature/IngestAppSumoSignalsJobTest.php` (`test_inserts_products_using_the_actors_actual_snake_case_field_names`, `test_sends_a_keyword_search_request_to_the_actor`)

### BUG-11 — Guru's Apify actor requires a paid rental ✓
- **Fixed:** 2026-07-08
- **Fix:** Found and live-verified a replacement actor, `shahidirfan/guru-com-scraper` (cheap pay-per-event: $0.0005/start + $0.00149/result; 118/118 succeeded in the prior 30 days). Its input schema uses `keyword`/`results_wanted` rather than the old `queries`/`item_limit` shape — updated `IngestGuruSignalsJob` accordingly. Its `price` field is free text (e.g. `"Fixed Price | Under $250"`), which the job's existing budget-parsing regex already handles correctly (strips non-digits, takes the value before any `-`). Live-verified: `build a custom dashboard` returned 3 found / 3 inserted with real Guru.com job postings. Minor known gap: this actor has no `id`/`postedAt` fields, so signals fall back to `md5(url)` for dedup and `now()` for `published_at` (the `max_age_days` cutoff is effectively inert, same class of gap as BUG-7's category-matching) — not blocking, revisit if precise posting dates matter later.
- **Covered by:** `tests/Feature/IngestGuruSignalsJobTest.php` (`test_inserts_postings_using_the_actors_actual_field_shape`, `test_sends_a_keyword_search_request_to_the_actor`)

<!-- Move here when resolved. Include fix summary and covering test. -->
<!-- When this section exceeds 20 entries, archive oldest to docs/BUGS_ARCHIVE.md -->
<!-- Format:
### BUG-[N] — [Short title] ✓
- **Fixed:** YYYY-MM-DD
- **Fix:** [What was done]
- **Covered by:** [test name or file]
-->
