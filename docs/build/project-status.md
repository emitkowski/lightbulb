# AI Idea Engine — Project Status
*Codename: Lightbulb*
*June 2026 — Pre-Build Summary*

---

## What's Done

### Scoring Criteria (LOCKED)
`idea-scoring-criteria.md` — Version 2.0
- 6 scoring dimensions with weights
- Calibrated across 13 scoring rounds, 58 ideas, 12 review passes
- Section 6b: Product Definition Specificity Gate (mandatory pre-scoring)
- Kill conditions, soft kills, cap rules all defined — no stacking penalties
- Next review: September 2026

### Signal Sources (COMPLETE)
`signal-sources.md` — 20 layers across all meaningful public data sources
- Layers 1–6b: Demand signals (Reddit, G2, reviews, freelance postings)
- Layer 7: Success pattern corpus with full schema + extraction prompt
- Layers 8–13: GitHub issues, AppSumo, roadmaps, Stripe, Gumroad, HN
- Layers 14–20: Chrome/VS Code reviews, Stack Overflow, PH comments, dev Twitter, Capterra, Slack/Discord
- Signal quality hierarchy: 25 sources ranked by quality

### Ideas to Watch (18 IDEAS)
`ideas-to-watch.md`

**Top tier (60+, no fatal flaws):**

| Idea | Score | Next Step |
|---|---|---|
| ⭐ 37 — Laravel Agent Observability | 74 | Post in r/laravel — validate demand |
| 11 — Client Reporting for Freelancers | 68 | Search r/freelance for "status report" pain |
| 39 — Developer Retainer Management | 65 | Search r/freelance for "retainer" discussions |
| 58 — Proposal-to-Project Automation | 65 | Validate which integrations needed for v1 |
| 41 — Churn Reactivation Tool | 64 | Check if founders have usage tracking set up |
| 13 — Pause Flow Builder | 64 | Validate Churnkey pricing gap |
| 32+34 — SaaS Metrics Intelligence | 64 | Validate multi-processor is real pain |
| 8 Pivot — Living Documentation | 63 | Search "auto generate documentation from code" |
| 49 — SaaS Health Score | 63 | Consider merging with 32+34 |
| 43 — Pricing Intelligence | 63 | Consider as feature of Idea 24 |
| 24 — Competitor Digest | 62 | Check Competitors App G2 reviews |
| 56 — AI Release Notes Writer | 62 | Check Beamer G2 — is writing the value? |
| 45 — Developer Client Onboarding | 62 | Validate dev-specific vs. Dubsado |
| 50 — Laravel Webhook Delivery | 63 | Post in Laravel Discord — Svix vs. build |
| 27 — Indie SaaS Affiliate Manager | 61 | Check Rewardful G2 complaints |
| 17 — Contract + Proposal Generator | 60 | Check Bonsai G2 for retainer complaints |

**Borderline (55–59, needs validation):**

| Idea | Score | Issue |
|---|---|---|
| 26 — Freelancer Cold Email | 59 | Validate positioning vs. Lemlist |
| 22 — Pricing Page A/B | 57 | Stripe platform absorption risk |
| 29 — Job Board Builder | 54 | Needs differentiation from Job Boardly |

---

## What's Next — Build Plan

### Phase 0 (This week — before Costa Rica)
Run manual validation on the top 3 ideas on the watch list. Each one is a 30-minute task:
1. Post in r/laravel and Laravel Discord about Idea 37
2. Search r/freelance for client reporting and retainer pain
3. Check Churnkey pricing page and G2 reviews for Idea 13

### Phase 1 — Ingestion Layer (see docs/PHASE1.md)
Full spec in PHASE1.md. Covers:
- Database schema: `raw_signals`, `ingestion_runs` tables
- Reddit API ingestion job + scheduler
- HN Algolia ingestion job + scheduler
- Filament dashboard for browsing raw signals
- Artisan commands for manual runs

### Phase 2 — Scoring Agent (see docs/PHASE2.md stub)
Convert `idea-scoring-criteria.md` into structured agent system prompt. Key steps:
1. Pre-scoring: specificity gate (Section 6b)
2. Live search for Competition Gap (Serper.dev)
3. Six-dimension scoring
4. Layer 7 pattern confidence check
5. Structured JSON output
- Additional tables: `ideas`, `success_patterns`, `idea_signals`

### Phase 3 — Filament Dashboard
Simple list view: ideas sorted by score, filters, detail view, manual override. Build last.

### Phase 4 — Seed Success Pattern Corpus
One-time bulk extraction from IH and r/SaaS milestone posts before weekly crawl takes over.

---

## Key Decisions Still Open

1. **What to actually build first** — the system will surface ideas but the watch list already has candidates. Idea 37 (Laravel Agent Observability) is already partially built. That may be the right first product to focus on rather than waiting for the pipeline.

2. **Pricing on Idea 37** — $49/mo may be underpriced. Comparable tools charge $100/mo+. Worth testing $79/mo from launch.

3. **Cluster convergence** — Ideas 32+34, 49, 43, and 24 all want to be one SaaS founder intelligence platform. If that cluster scores consistently, it may be worth building as a single product rather than pursuing individual ideas.

4. **The meta-question** — this system is itself an experiment in automated idea-to-revenue. Idea 37 is already built. The fastest path to $1K MRR might be to launch Idea 37 now while building the pipeline in parallel, rather than waiting for the pipeline to surface something better.

---

*Documents: idea-scoring-criteria.md (locked), signal-sources.md, ideas-to-watch.md*
*Stack: Laravel 13 / Vue / PostgreSQL / pgvector / Filament 3*
*Target: Demoable pipeline by September 2026*
