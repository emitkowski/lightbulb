# memory/shared.md
_Shared team learnings — committed to the repo, available to all developers_
_Human and Claude co-maintained — add entries that should be shared across the whole team_

2026-06-26 — Serper.dev ingestion jobs use `config('scoring.serper.api_key')` and `config('scoring.serper.base_url')` — the same config keys as the scoring pipeline. There is no separate `INGESTION_SERPER_API_KEY`. One env var covers both pipelines. — Do not add a duplicate Serper key for ingestion; changing `scoring.serper.*` affects both.

2026-06-26 — `source_id` for Serper signals follows the pattern `{strtolower($tool)}:{substr(md5($url), 0, 12)}`. The same URL for two different tools produces two different `source_id` values — both are stored as distinct signals (each is a valid gap indicator for its respective tool). This is intentional. — Do not "fix" duplicate URLs across tools; the dedup is per-tool by design.
