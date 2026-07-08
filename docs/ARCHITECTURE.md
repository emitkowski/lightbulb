# ARCHITECTURE.md
_Claude-maintained, human reviews for accuracy_
_Decision history: docs/ARCHITECTURE_HISTORY.md | Last updated: 2026-06-26_

## System overview
Lightbulb is a solo-developer idea evaluation engine. It ingests signals (forum posts, marketplace reviews, job listings, search results) from up to 20 sources, clusters them into candidate product ideas, and scores each idea against a rubric using Claude. Scored ideas surface in a Filament admin panel where the developer reviews them. The frontend (Inertia + Vue) is minimal — auth, welcome page, and the Filament dashboard.

## Component inventory
| Component | Responsibility | Owns | Does not own |
|---|---|---|---|
| Ingestion pipeline | Fetch raw signals from external sources and write to `raw_signals` | `raw_signals`, `ingestion_runs` | Scoring, clustering |
| Clustering service | Group semantically related signals into candidate ideas | `ideas`, `idea_signals` | Fetching signals, scoring |
| Scoring pipeline | Run specificity gate, competition search, and Claude scoring on pending ideas | `ideas` score/status fields | Signal ingestion |
| `ClaudeCliRunner` | Subprocess wrapper for the Claude Code CLI | Temp files, temp HOME dirs | Prompt construction, JSON parsing |
| `ScoringAgentService` | Build prompts, call Claude (CLI or API), parse JSON responses | Prompt logic, response parsing | Database writes |
| `CompetitionSearchService` | Search Serper.dev for competitor landscape | Competition fields on `ideas` | Scoring |
| Filament admin | Browse and review scored ideas | Read-only UI over all tables | Business logic |
| `App\Http\Responses\LoginResponse` / `TwoFactorLoginResponse` | Override Fortify's post-login redirect to be Inertia-aware | Wrapping `redirect()->intended(...)` in `inertia()->location(...)` | Credential validation, 2FA logic (still Fortify's) |

## Data flow
**Ingestion:** `ingestion:run --source=X` dispatches one job per query/category. Each job calls the external API, filters items by threshold, and calls `IngestionService::insertSignal()` per item (unique constraint on `source + source_id` deduplicates). Every job logs one `ingestion_runs` record regardless of outcome.

**Scoring:** `scoring:run --cluster` calls `ClusteringService` which groups unprocessed signals by keyword overlap into `ideas` (status=`pending`) and links them via `idea_signals`. Then `scoring:run --score` dispatches `ScoreIdeaJob` per pending idea: runs the specificity gate, competition search, and Claude scoring in sequence. The idea lands in status `scored`, `gate_failed`, or `discarded`.

## Integration points
| Service | Purpose | Direction |
|---|---|---|
| Reddit API | Subreddit posts as pain signals | outbound |
| Hacker News API | Ask HN / Show HN posts | outbound |
| GitHub API | Issue threads in target repos | outbound |
| VS Code Marketplace API | Extension ratings/reviews | outbound |
| Stack Exchange API | Questions tagged with target tech | outbound |
| Product Hunt API | Upvoted product posts | outbound |
| Dev.to API | Posts mentioning building/gaps | outbound |
| Twitter/X API v2 | Recent-search tweets as pain signals (App-only bearer token) | outbound |
| LaraJobs RSS feed | Laravel-ecosystem job postings (no auth) | outbound |
| Serper.dev | Google search for competitors, alternatives, roadmaps, Capterra | outbound |
| Apify | G2, AppSumo, Chrome Web Store, Gumroad, Freelance/PeoplePerHour/Guru, Indeed, LinkedIn Jobs platform scraping | outbound |
| Claude CLI / Anthropic API | Specificity gate + idea scoring | outbound |

## Architectural boundaries
- Ingestion jobs write only to `raw_signals` and `ingestion_runs`. They never touch `ideas` or scoring state.
- `ScoringAgentService` returns plain arrays — it never writes to the database. `ScoreIdeaJob` owns all database writes for the scoring pipeline.
- `ClaudeCliRunner` is scoring-only. Ingestion jobs do not use it.
- The Filament admin panel is read-only over all tables. No business logic lives in Filament resources.
- Serper.dev config (`config('scoring.serper.*')`) is shared between the scoring pipeline and Serper-based ingestion jobs — changing it affects both.

## What is intentionally excluded
- No multi-user support — this is a solo developer tool. Auth scaffolding (Jetstream) is present but teams are non-functional beyond the default setup.
- No real-time pipeline — scoring is triggered manually via Artisan commands, not on ingestion.
- No frontend for ideas — all idea review happens in the Filament admin panel.
- Google Trends (Layer 4), Indie Hackers (Layer 5), Stripe/Paddle/Lemon Squeezy directories (Layer 11), public Slack/Discord archives (Layer 20) are not yet built. Codeable and Contra (Layer 6b) were evaluated and ruled out — see `docs/build/signal-sources.md`.

## Component docs
_No subsystem docs created yet — add per-component files to docs/architecture/ as the system grows_
| Component | File |
|---|---|
