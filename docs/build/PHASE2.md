# Phase 2 — Scoring Agent
*Project: Lightbulb*

> **Status: NOT STARTED**
> Do not build anything in this file until Phase 1 is complete and confirmed working.
> This file exists so Claude Code understands the full system context while building Phase 1.

---

## Goal
An AI agent that reads unprocessed signals from `raw_signals`, clusters related signals into candidate ideas, scores each idea against the rubric in `idea-scoring-criteria.md`, and writes structured output to an `ideas` table.

---

## What Phase 1 Must Deliver First
- [ ] `raw_signals` table populated with real data from Reddit and HN
- [ ] `ingestion_runs` table logging crawl history
- [ ] Filament dashboard browseable
- [ ] Scheduler configured

**Do not proceed to Phase 2 until all Phase 1 definition-of-done items are checked.**

---

## High-Level Architecture (for context only)

```
raw_signals (unprocessed)
    ↓
Clustering step — group related signals by topic/keyword
    ↓
Pre-scoring step — Specificity Gate (Section 6b of scoring criteria)
    ↓
Live search — Serper.dev for Competition Gap dimension
    ↓
Scoring agent — Claude API with rubric as system prompt
    ↓
Pattern confidence check — query success_patterns table
    ↓
ideas table — structured scored output
    ↓
Filament dashboard — ideas list sorted by score
```

---

## Tables Phase 2 Will Add
- `ideas` — scored ideas with all dimensions and overall score
- `success_patterns` — Layer 7 corpus (proven products that hit $1K MRR)
- `idea_signals` — pivot table linking ideas to their source signals

---

## Key Files Phase 2 Will Need
- `idea-scoring-criteria.md` — converts to agent system prompt
- `signal-sources.md` — reference for understanding signal context
- Serper.dev API key in `.env` for live competition search

---

## Phase 2 will be specced in detail once Phase 1 is running.
