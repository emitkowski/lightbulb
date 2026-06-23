# STATUS.md
_Last updated: 2026-06-22 by Claude_

## Current phase
**Phase 2 — Scoring Pipeline** (in progress)

## Project health
| Indicator | Status | Detail |
|---|---|---|
| Build | ✓ passing | Sail + Vite functional |
| Tests | ✓ passing | 36/36 Phase 1+2 tests pass (8 pre-existing Jetstream failures unrelated — see BUG-1) |
| Coverage | ○ not measured | Run `vendor/bin/sail artisan test --coverage` |
| Open bugs | ⚠ 1 low | BUG-1: pre-existing Jetstream test failures, non-blocking |
| Blocker | none | — |

## Last meaningful progress
- Phase 2 core pipeline complete (stub mode — ready for API keys)
- `ideas`, `success_patterns`, `idea_signals` tables migrated (UUID PKs)
- `ClusteringService` — keyword-based clustering (no API needed)
- `CompetitionSearchService` — stubbed; wires to Serper.dev when `SERPER_API_KEY` is set
- `ScoringAgentService` — stubbed; wires to Claude API when `ANTHROPIC_API_KEY` is set; uses `docs/build/idea-scoring-criteria.md` as system prompt
- `ClusterSignalsJob` + `ScoreIdeaJob` — full pipeline with gate/kill/score/pattern steps
- `scoring:run` and `scoring:stats` Artisan commands
- `IdeaResource` Filament dashboard sorted by score with analysis modal
- Scheduler: `scoring:run` runs weekly after ingestion
- 18 new PHPUnit tests (6 clustering, 8 scoring job, 4 command)

## What's next
1. Add `ANTHROPIC_API_KEY` + `SERPER_API_KEY` to `.env` to activate real scoring
2. Seed `success_patterns` table with Layer 7 corpus (Phase 4)
3. Run `scoring:run` against real ingested signals
4. Review ideas in Filament dashboard

## Metrics snapshot
| Metric | Value | Trend |
|---|---|---|
| Test count | 36 (Phase 1+2) | +18 this session |
| Coverage | not measured | — |
| Open bugs | 1 (low, non-blocking) | +1 pre-existing |
| Blocking bugs | 0 | — |
| Milestones complete | 2/N | Phase 1 + Phase 2 pipeline done |
