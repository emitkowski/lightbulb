# ARCHITECTURE_HISTORY.md
_Append-only — never edit or delete. Claude writes immediately on decision._
_Current state: docs/ARCHITECTURE.md | Summarise superseded entries when approaching 200 lines_
_Reversals: add new entry "YYYY-MM-DD — Reversal of [original title]"_

## Decision format
### YYYY-MM-DD — [Decision title]
**Decision:** [What was decided, specific and unambiguous]
**Alternatives considered:** [What else was evaluated]
**Reasoning:** [Why this option was chosen]
**Consequences:** [What this constrains going forward]

---

### 2026-07-09 — Upgraded Filament v3 → v5 (via v4), skipping v4 as a resting point
**Decision:** Upgraded `filament/filament` from v3.3.54 straight through to v5.6.8 in one session, running both official codemods in sequence (`filament/upgrade:^4.0` → `vendor/bin/filament-v4` → composer, then `^5.0` → `vendor/bin/filament-v5` → composer). This also pulled `livewire/livewire` to v4.3.3 (v5's only hard new requirement). Manual fixes after the v4 codemod: converted 4 `BadgeColumn` usages to `TextColumn::badge()->color(fn ...)` across the 3 Resources, and fixed a hand-built `tableFilters[...]` URL query string to `filters[...]` in `IngestionRunResource`'s cross-resource link. The v4 codemod handled Form→Schema signatures, `->actions()`/`->bulkActions()` → `->recordActions()`/`->toolbarActions()`, icon type hints, and header-action `->form()`→`->schema()` automatically. The v4→v5 hop needed zero code changes (v5 is purely a Livewire-v4-support release).
**Alternatives considered:** Deferring the whole upgrade (declined — user explicitly chose to do it now); stopping at v4 as a stable resting point (unnecessary — v4→v5 has no functional breaking changes, so landing on v5 costs nothing extra and gets Livewire v4).
**Reasoning:** All real migration risk is concentrated in the v3→v4 hop; v5 just adds Livewire v4 support. Doing both hops together, with a full-suite checkpoint between them, keeps a v4-caused failure distinguishable from a v5-caused one while avoiding a half-finished upgrade sitting on v4.
**Consequences:** The app is now on Livewire v4 — any future custom Livewire/`@script` code must follow v4 conventions. Filament table filters now default to deferred (Apply-button) behavior, a deliberate accepted UX change (no `deferFilters(false)` shims added). Filament widgets now lazy-load by default, so `BroadcastPingWidget`'s body (including its Echo `@script`) renders via a follow-up Livewire request, not in the initial page HTML. Tailwind must stay on v4 (already was). Added `BroadcastPingWidgetTest` (previously untested); the client-side WebSocket round-trip remains only manually verifiable.

### 2026-06-26 — ApifyService throws RuntimeException on HTTP failure
**Decision:** `ApifyService::runSync()` throws `RuntimeException` on non-2xx responses rather than logging + returning `[]`. Empty successful runs return `[]`.
**Alternatives considered:** Return `null` on failure (requires null checks in each job); silent `[]` return (can't distinguish failure from empty dataset).
**Reasoning:** Throwing lets each job's existing `catch (\Throwable $e)` block log `status='failed'` without extra logic. Distinguishing failure from empty matters for run stats.
**Consequences:** All `runSync` callers must be inside try/catch (all current jobs are). Always check `hasToken()` before calling to avoid spurious throws.

### 2026-06-26 — Ingestion Serper jobs share config with scoring pipeline
**Decision:** Ingestion jobs that search via Serper.dev reuse `config('scoring.serper.api_key')` and `config('scoring.serper.base_url')`.
**Alternatives considered:** Separate `ingestion.serper.api_key` config key; dedicated SerperService class.
**Reasoning:** Same API key for both pipelines. Duplicating would require two env vars for the same key. Existing config structure already has the right values.
**Consequences:** Changing `scoring.serper.*` affects ingestion too. If independent keys are needed later, extract to `config/serper.php`.

### 2026-07-01 — Twitter/X ingestion intentionally deviates from Layer 18 spec
**Decision:** `IngestTwitterSignalsJob` uses general keyword search (same shape as every other query-based ingestion job) instead of the spec's account-specific monitoring of ~13 named developer influencers.
**Alternatives considered:** Building the spec as written — `from:username` queries against the named account list (Taylor Otwell, DHH, Pieter Levels, etc.).
**Reasoning:** Developer chose to keep the simpler, already-built general-search implementation over rewriting to match spec, trading signal quality for operational simplicity and broader category coverage.
**Consequences:** This source will produce noisier signal than the spec intended — `docs/build/signal-sources.md` explicitly calls general Twitter search "too reactive, too surface-level, too many dunks and not enough pain." Revisit if scored ideas sourced from `twitter` consistently score low or get discarded.

### 2026-07-07 — Single login page: removed Filament's built-in `->login()`
**Decision:** `AdminPanelProvider` no longer calls `->login()`. The Filament admin panel has no login page or route of its own; unauthenticated visits to `/admin` (or any panel route) fall through to the app's single Jetstream/Fortify `/login`.
**Alternatives considered:** Keep both — Filament's own `/admin/login` for panel access, Jetstream's `/login` for everything else.
**Reasoning:** This is a solo-developer tool with one user role; two separate login forms was pure redundancy from adding Filament with its defaults, not a deliberate two-tier auth design. One canonical login page is simpler to maintain and reason about.
**Consequences:** Filament's `Authenticate` middleware now relies on Laravel's default `redirectGuestsTo(route('login'))` (registered in `bootstrap/app.php`'s `withMiddleware`) rather than its own `getLoginUrl()`. If the panel ever needs a separate guard/auth flow from the main app, `->login()` will need to come back with an explicit distinct login route.

### 2026-07-01 — Codeable and Contra excluded from Layer 6b build
**Decision:** Neither Codeable nor Contra were built as ingestion sources, despite being named in the Layer 6b spec.
**Alternatives considered:** Building generic Apify actors against their public pages.
**Reasoning:** Codeable gates all job listings behind an approved-developer application (~4-week vetting, no public listing page or API). Contra's public surface is freelancer profiles/portfolios, not client project postings — no source exists for the buyer-side "I need X built" signal this pipeline needs.
**Consequences:** Layer 6b coverage is Upwork + PeoplePerHour + Guru + LaraJobs + r/forhire (via existing Reddit job), not the full 9-platform list in the spec. Revisit Codeable if a developer account is ever obtained; revisit Contra if it exposes a public project board.
