# BUGS.md
_Known bugs — updated by Claude on discovery or after test failures_
_Claude writes immediately on discovery — do not wait for session end_
_Fixed and verified bugs move to docs/BUGS_ARCHIVE.md_

<!-- Severity: blocking=no further work | high=no workaround | medium=workaround exists | low=minor -->

## Open bugs

### BUG-7 — AppSumo has no working Apify actor
- **Discovered:** 2026-07-01 via live testing with a real Apify token; root cause confirmed via `GET /v2/actor-runs/{id}/log` on retry
- **Affects:** `IngestAppSumoSignalsJob`, `config/ingestion.php` (`apify.appsumo.actor_id`)
- **Severity:** medium — not fixable on our side; this is AppSumo's anti-bot defense working as intended against the actor
- **Description:** The original `epctex/appsumo-scraper` actor doesn't exist (fabricated in an earlier session). Tried 2 real replacements live: `scraper-mind/appsumo-scraper` requires a paid rental subscription (`actor-is-not-rented`, not fixable with API credits alone). `easyapi/appsumo-product-scraper` accepts the right input shape and sometimes returns `status='success'` at the Apify level, but its headless Chrome browser crashes (`BrowserPool: Page crashed!`) every single time it navigates to an AppSumo page — confirmed via the run log: 3/3 retries crashed, 0 succeeded, 0 items scraped. This is consistent with AppSumo detecting and killing automated browser sessions.
- **Blocking:** `appsumo` will never return real data from `easyapi/appsumo-product-scraper` — the crash is deterministic, not transient. Only fixable by renting `scraper-mind/appsumo-scraper` or finding an actor with stronger anti-detection (stealth browser fingerprinting, residential proxies specifically tuned for AppSumo).
- **Status:** open

### BUG-10 — Upwork's Apify actor (getdataforme/upwork-actor) has a broken CAPTCHA solver
- **Discovered:** 2026-07-01, root cause confirmed via `GET /v2/actor-runs/{id}/log` after retrying the actor at the developer's request
- **Affects:** `IngestFreelancePlatformsJob`, `config/ingestion.php` (`apify.freelance.actor_id`)
- **Severity:** medium — not fixable on our side; this is a bug inside the third-party actor's own container
- **Description:** Upwork serves a CAPTCHA on every search request. The actor tries to solve it with a browser tool called Camoufox (~713MB download), but its own container runs out of disk space mid-download every time (`Error installing Camoufox: [Errno 28] No space left on device`), so it gives up on the CAPTCHA and returns 0 results — or, in earlier attempts, retried enough times to hit our own request timeout instead. Confirmed via the actor's raw run log, not guessed. All 6 real runs against this actor today returned `SUCCEEDED` at the Apify level but 0 jobs scraped.
- **Blocking:** `freelance` (Upwork) will never return real data from this actor until its author fixes the container's disk allocation (outside our control) or a different Upwork actor is found.
- **Status:** open

### BUG-11 — Guru's Apify actor requires a paid rental (correction: never actually confirmed working)
- **Discovered:** 2026-07-01. **Correction (same day):** this bug was originally written as "worked live, 100 found/87 inserted, then the free trial expired mid-session" — that was a documentation mistake. The 100-found/87-inserted result belongs to the **Gumroad** live test (`IngestGumroadSignalsJob`), not Guru. Checked `ingestion_runs` directly: there is exactly one row ever recorded for `source='guru'`, and it's a failed 403. No evidence exists that this actor ever successfully returned data — its input schema was verified via documentation (WebFetch) before being wired into the job, but the job's first real execution was the `--limit=1` full-pipeline run, which failed immediately with `actor-is-not-rented`. Retried again independently — same 403, no run log exists because Apify rejects the request before execution starts.
- **Affects:** `IngestGuruSignalsJob`, `config/ingestion.php` (`apify.guru.actor_id`)
- **Severity:** medium
- **Description:** `getdataforme/guru-jobs-scraper` requires a paid rental subscription to run at all (`actor-is-not-rented`, 403) — this may have been true from the very first attempt, not a trial that "expired mid-session" as originally reported.
- **Blocking:** `guru` will log `status='failed'` every time until the developer rents the actor (`console.apify.com/actors/AlaMViRHYLFctBOpO`) or a different actor is found and verified with an actual live call before being wired in.
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
<!-- Move here when resolved. Include fix summary and covering test. -->
<!-- When this section exceeds 20 entries, archive oldest to docs/BUGS_ARCHIVE.md -->
<!-- Format:
### BUG-[N] — [Short title] ✓
- **Fixed:** YYYY-MM-DD
- **Fix:** [What was done]
- **Covered by:** [test name or file]
-->
