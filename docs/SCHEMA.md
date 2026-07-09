# SCHEMA.md
_Claude-maintained — update immediately when schema changes_
_Keep concise — summaries only, not a migration dump. Human reviews for accuracy._

## Tables

### raw_signals
Stores raw ingested content from all signal sources before scoring or processing.

| Column | Type | Notes |
|---|---|---|
| id | uuid | PK (`HasUuids`) |
| source | varchar(50) | `reddit`, `hackernews`, `github`, `vscode`, `stackoverflow`, `producthunt`, `devto`, `twitter`, `serper_alternatives`, `serper_roadmaps`, `serper_capterra`, `indiehackers`, `g2`, `appsumo`, `chrome_webstore`, `gumroad`, `freelance`, `peopleperhour`, `guru`, `larajobs`, `indeed`, `linkedin` |
| source_id | varchar | Platform-specific post/item ID |
| source_url | varchar | Original permalink |
| title | varchar | Post/story headline |
| content | text | Body text |
| author | varchar | Username |
| score | int | Upvotes/points (default 0) |
| comment_count | int | Reply count (default 0) |
| category | varchar | Subreddit name or `ask_hn` / `show_hn` |
| metadata | json | Source-specific extras (flair, url, is_self, etc.) |
| processed | boolean | Scored/analysed (default false) |
| flagged | boolean | Manually flagged for review (default false) |
| published_at | timestamp | Original post date |
| created_at / updated_at | timestamps | |

Indexes: unique `(source, source_id)` — null `source_id` bypasses dedup; `(source, processed)`; `published_at`; `score`.

### ingestion_runs
Audit log for every ingestion job execution.

| Column | Type | Notes |
|---|---|---|
| id | uuid | PK |
| source | varchar(50) | `reddit` or `hackernews` |
| query | varchar | Search term that was queried |
| signals_found | int | Total API results |
| signals_inserted | int | New signals stored |
| signals_skipped | int | Duplicates or below-threshold |
| status | varchar | `success`, `failed`, or `partial` |
| error_message | text | Populated on failure or partial |
| duration_ms | int | Job wall-clock time |
| created_at / updated_at | timestamps | |

Indexes: `(source, created_at)`.

### ideas
Scored ideas synthesised from clustered signals. One idea = one candidate product concept.

| Column | Type | Notes |
|---|---|---|
| id | uuid | PK |
| title | varchar | Synthesised from cluster anchor signal |
| description | text | Optional human-readable expansion |
| signals_summary | text | Bullet list of contributing signals |
| source_signals_count | int | How many signals formed this cluster |
| specificity_gate_status | varchar | null / `passed` / `failed` |
| specificity_gate_answers | json | 4-question gate answers |
| specificity_gate_reasoning | text | Gate decision explanation |
| competition_query | varchar | What was searched on Serper.dev |
| competition_results | json | Raw Serper organic results |
| competition_summary | text | Claude's competition analysis |
| score_problem_strength | smallint | 0–100 (weight 20%) |
| score_distribution_path | smallint | 0–100 (weight 20%) |
| score_competition_gap | smallint | 0–100 (weight 20%) |
| score_build_feasibility | smallint | 0–100 (weight 20%) |
| score_automability | smallint | 0–100 (weight 10%) |
| score_revenue_plausibility | smallint | 0–100 (weight 10%) |
| score_overall | smallint | 0–100 weighted average with cap rules applied |
| score_reasoning | json | Per-dimension reasoning text |
| kill_condition | varchar | Which hard kill fired, or null |
| kill_reasoning | text | Kill explanation |
| success_pattern_confidence | smallint | 0–100 pattern match score |
| success_pattern_notes | text | Pattern matching explanation |
| status | varchar | `pending` / `scoring` / `scored` / `gate_failed` / `discarded` |
| processed_at | timestamp | When scoring completed |
| created_at / updated_at | timestamps | |

Indexes: `status`, `score_overall`, `created_at`.

### success_patterns
Layer 7 corpus — proven products that reached $1K MRR. Used by scoring agent for pattern confidence check.

| Column | Type | Notes |
|---|---|---|
| id | uuid | PK |
| product_name | varchar | |
| revenue_milestone | varchar | e.g. `$1K MRR` |
| mrr_amount | int | Dollars |
| category | varchar | e.g. `developer-tools`, `freelancer` |
| description | text | What the product does |
| pain_solved | text | The pain it addresses |
| target_customer | varchar | Who pays for it |
| pricing_model | varchar | `subscription` / `one-time` / `usage` |
| key_insight | text | Why it worked |
| source_url / source | varchar | Where this data came from |
| metadata | json | Extra fields |
| created_at / updated_at | timestamps | |

Indexes: `category`, `mrr_amount`.

### idea_signals
Pivot table linking ideas to the raw signals that formed them.

| Column | Type | Notes |
|---|---|---|
| id | uuid | PK |
| idea_id | uuid FK | → ideas |
| raw_signal_id | uuid FK | → raw_signals |
| weight | decimal(3,2) | 0.00–1.00: how central this signal is |
| created_at / updated_at | timestamps | |

Unique constraint: `(idea_id, raw_signal_id)`.

## Relationships
`raw_signals` and `ingestion_runs` are independent — no FK between them. Both are append-only write targets for ingestion jobs.

## Key business rules
- `raw_signals`: unique constraint on `(source, source_id)` enforces deduplication at the DB level; null `source_id` is exempt.
- Neither table uses soft deletes.
- `processed` and `flagged` on `raw_signals` should only be set by the scoring pipeline or admin actions, not by ingestion jobs.

## Internal contracts
`IngestionService::insertSignal(array $data)` accepts keys matching `RawSignal::$fillable`. Callers must provide `source`; all other fields are optional.
`IngestionService::logRun(string $source, string $query, array $stats)` expects stats keys: `found`, `inserted`, `skipped`, `status`, and optionally `error` and `duration_ms`.

---
