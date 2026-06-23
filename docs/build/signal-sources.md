# Signal Source List
## AI Idea Engine — Ingestion Layer
*Version 1.0 — Built June 2026*

*Note on voice: This document uses second-person ("your ecosystem," "your target customer") intentionally. It is written as context for the AI scoring agent, framing the builder's specific situation so the agent can apply signal quality judgments accurately. This is not an error — it is deliberate agent framing.*

---

## Philosophy

Cast a wide net. The scoring rubric filters — the ingestion layer should be greedy. Better to ingest 500 weak signals and score 490 of them out than to miss the one thread where 200 people complained about the same unsolved problem last month.

Every source is monitored on a **weekly cadence** unless noted otherwise. Raw signals land in `raw_signals` table with source, content, timestamp, and a `processed` flag.

---

## Layer 1 — Reddit

Reddit is the highest-signal source. Look specifically for:
- "Does X exist?" posts — explicit market demand signal
- "I hate that Y doesn't do Z" — pain + implicit competitor weakness
- "What do you use for X?" — category discovery + competitor research
- Tool comparison threads — competitive landscape in one place
- "I built X" posts with high engagement — validation that people care

### Subreddits to Monitor

**Freelancer & Agency**
- r/freelance
- r/freelanceWriters (content tool crossover)
- r/webdev (freelance angle)
- r/agency
- r/PPC (agency tooling pain)
- r/SEO (agency + content)

**Indie SaaS & Founders**
- r/SaaS
- r/indiehackers
- r/startups
- r/EntrepreneurRideAlong
- r/microsaas
- r/entrepreneur

**Developer Productivity**
- r/programming
- r/webdev
- r/laravel
- r/vuejs
- r/ExperiencedDevs
- r/devops
- r/softwareengineering

**Small Business Ops**
- r/smallbusiness
- r/Entrepreneur
- r/ecommerce
- r/bookkeeping
- r/humanresources

**AI & Automation**
- r/ChatGPT (pain points with AI tools)
- r/artificial
- r/MachineLearning (applied angle)
- r/n8n
- r/zapier
- r/nocode

**Content & Marketing**
- r/content_marketing
- r/digital_marketing
- r/emailmarketing
- r/socialmedia
- r/copywriting

### Reddit Query Patterns
Run these query patterns against each subreddit weekly via Reddit API:

```
"does anyone know a tool that"
"is there a way to automate"
"looking for something that"
"does X exist"
"what do you use for"
"I wish there was"
"anyone else frustrated with"
"alternatives to"
"sick of paying for"
"switched away from"
"built something for"
"launched X" (for competitor/validation research)
```

**Priority signal:** Posts from the last 7 days with 10+ upvotes and 5+ comments. High engagement on a pain post = active, validated frustration.

---

## Layer 2 — G2 / Capterra / Trustpilot (via Apify)

Review sites are underrated for idea generation. The signal isn't in positive reviews — it's in:
- 3-star reviews that say "great but missing X"
- 1-star reviews that say "switched because Y doesn't do Z"
- Review titles that start with "Good but..." or "Almost perfect except..."

These are feature gap signals disguised as complaints.

### G2 Categories to Monitor

**Freelancer & Agency**
- Proposal Software
- Contract Management
- Time Tracking
- Invoicing & Billing
- Agency Management Software
- Client Portal Software

**Indie SaaS & Founders**
- Customer Success Software
- Churn Management
- Product Analytics
- Feature Request Management
- Changelog Software
- SaaS Metrics & Reporting

**Developer Productivity**
- Application Performance Monitoring
- Error Tracking
- CI/CD Tools
- Code Review Tools
- API Documentation
- Developer Portals

**Small Business Ops**
- CRM Software (SMB category)
- Accounting Software
- HR Software (SMB)
- Scheduling Software
- Inventory Management

**AI & Automation**
- AI Writing Assistants
- Workflow Automation
- No-Code Development Platforms
- Chatbot Platforms
- AI Customer Service

**Content & Marketing**
- Email Marketing
- Social Media Management
- SEO Tools
- Content Management
- Landing Page Builders

### Apify Actor Configuration
- **Target:** 3-star and below reviews only — higher stars rarely contain actionable gap signals
- **Fields to capture:** Review title, review body, star rating, reviewer role, company size
- **Frequency:** Weekly crawl, deduplicated by review ID
- **Volume:** Top 20 most-reviewed tools per category, latest 50 reviews each

---

## Layer 3 — "Alternatives to X" Searches (via Serper.dev)

This is your competitive intelligence layer. When someone searches "alternatives to X" they've already decided X isn't good enough — that's a buying signal and a gap signal simultaneously.

### Query Templates
Run weekly against Serper.dev:

```
"alternatives to [tool]" 
"[tool] alternative"
"[tool] competitor"
"[tool] vs"
"cheaper alternative to [tool]"
"open source alternative to [tool]"
"[tool] too expensive"
```

### Seed Tool List
These are the tools whose "alternatives" searches are most likely to surface your next idea:

**Freelancer & Agency**
- Bonsai, AND.CO, HoneyBook, Dubsado, Proposify, PandaDoc, Harvest, Toggl, Clockify, Teamwork

**Indie SaaS & Founders**
- Canny, Productboard, Churnkey, ProfitWell, Baremetrics, Beamer, Headway, Loom, Intercom (SMB), Crisp

**Developer Tools**
- Sentry, Datadog, New Relic, Postman, Swagger, Confluence (dev docs), Notion (dev use), Linear, Jira (SMB), GitHub Actions

**Small Business**
- Calendly, Acuity, Typeform, Jotform, Mailchimp, ActiveCampaign, HubSpot (SMB), Freshdesk, Zendesk (SMB)

**AI & Automation**
- Zapier, Make, n8n, Bubble, Webflow, ChatGPT plugins, Jasper, Copy.ai

**Content & Marketing**
- Buffer, Hootsuite, Later, Semrush, Ahrefs, SurferSEO, ConvertKit, Beehiiv, Substack

---

## Layer 4 — Google Trends (via Pytrends)

Trends tells you not just that a problem exists but that interest is **growing**. A flat trend on a pain point is less interesting than a trend that's up 40% over 6 months.

### Query Clusters to Track Weekly

```python
# Automation & AI workflows
["ai automation tool", "workflow automation software", "no code automation"]

# Freelancer tooling
["freelance proposal software", "client reporting tool", "freelance contract generator"]

# Indie SaaS ops
["saas churn reduction", "feature request tool", "saas onboarding software"]

# Developer productivity
["api documentation tool", "error monitoring tool", "code review automation"]

# Content & marketing
["ai content tool", "social media scheduler", "email marketing automation"]
```

### Signal Threshold
Flag any query cluster showing **>20% growth** over the trailing 90 days. Flat or declining trends are noted but deprioritized at scoring time.

---

## Layer 5 — Indie Hackers & Niche Forums

Indie Hackers is particularly valuable because people share revenue numbers, which gives you direct WTP and market size validation.

### Sources
- **Indie Hackers** (indiehackers.com) — "Ask IH" posts, product milestones, interviews
  - Search weekly: "looking for", "does anyone use", "what tool do you use for", "I wish"
- **Hacker News** — see Layer 13 for comprehensive HN coverage. Do not duplicate ingestion here.
- **Product Hunt** — "Upcoming" section for competitive awareness, recent launches in monitored categories
- **Dev.to** — Articles titled "I built X because Y didn't exist" — explicit gap validation

---

## Layer 6 — Job Boards (Secondary Signal)

Job postings are a lagging but useful signal. If 50 companies are hiring "AI automation specialist" that's a market signal. If job descriptions consistently mention a pain point as a required skill ("must know how to manage client reporting manually because no tool does X"), that's a gap signal.

### Sources
- **Indeed RSS** — search by category keywords weekly
- **LinkedIn Jobs** — spot check monthly, not weekly (noisier signal)

### Query Clusters
```
"workflow automation"
"client reporting"
"saas operations"
"content operations"
"ai tools specialist"
"no-code automation"
```

**Signal to look for:** Job descriptions that describe manual processes where software should exist. "Responsible for manually compiling weekly client reports" = gap signal.

---

## Layer 6b — Freelance & Project Posting Sources

*Note: This layer is numbered 6b rather than 7 because it extends the job board signal in Layer 6 with a more comprehensive set of freelance platforms. Layer 7 is the Success Pattern Corpus — a distinct signal type. The 6b designation is intentional, not an error.*

This layer captures businesses actively paying for custom-built solutions — the clearest possible signal that no adequate SaaS exists. When multiple independent clients post the same type of project across these platforms, that's not a coincidence. It's a product gap with validated budget behind it.

Collectively these sources answer the question: *what are businesses paying developers to build manually right now?*

### Priority A — Highest Signal

**Upwork**
- Broadest volume, most categories covered
- Filter: software/automation projects, budget $500+, posted in last 7 days
- Query patterns:
```
"build a custom dashboard"
"automate my client reporting"
"build a tool that"
"create a script that automatically"
"need a system to track"
"build an internal tool"
"automate invoicing"
"custom client portal"
"recurring report automation"
"workflow automation tool"
"I've been doing this manually"
```

**Codeable (WordPress-specific)**
- Every posting here is someone who couldn't find a plugin that did what they needed
- Extremely high gap signal — buyers have already evaluated the WordPress plugin ecosystem and found it lacking
- Crawl weekly, all active project postings
- Signal to look for: projects described as "ongoing" or "recurring" — subscription potential

**Laravel.io Job Board + Larajobs.com**
- Direct signal in your ecosystem
- Recurring patterns here are uniquely actionable — you have the domain knowledge to evaluate them instantly
- Monitor weekly, flag any project type appearing 3+ times per month
- Signal to look for: "we need someone to build X every month" — explicit recurring workflow with no tool

**Indie Hackers "Looking to Hire"**
- Technically literate buyers who have already evaluated SaaS options and found them lacking
- When a founder posts here, they've already Googled, trialed tools, and given up — that's a strong gap signal
- Query: indiehackers.com/for-hire filtered to "looking to hire" posts
- Signal to look for: posts describing why existing tools didn't work — competitor weaknesses handed to you directly

**r/forhire (Reddit)**
- High volume, requires filtering
- Filter: software/automation projects only, budget mentioned and over $200, posted in last 7 days
- Query patterns same as Upwork above
- Signal advantage: buyers here are often more candid about why they can't find an off-the-shelf solution

### Priority B — High Signal

**PeoplePerHour**
- Mid-market, different client base from Upwork with UK/EU skew
- Useful for catching projects that don't appear on Upwork
- Crawl weekly with same query patterns as Upwork
- Filter: "project" type postings only, not hourly gigs

**Guru.com**
- Partial overlap with Upwork but distinct client base
- Mid-market businesses, often more established than Upwork clients
- Weekly crawl, same query patterns
- Lower priority than Upwork but worth including for volume

**Contra**
- Newer platform, skews toward modern stack work (Laravel/Vue adjacent)
- Client base tends to be startups and indie founders — your target customer
- Lower volume than Upwork but higher relevance per posting
- Crawl weekly

**r/webdev hiring flair**
- Same audience as your target customer posting their own needs
- Developers hiring other developers understand the technical landscape — when they can't find a tool, it genuinely doesn't exist
- Filter: posts with "hiring" or "for hire" flair, software/automation projects

---

### Cross-Platform Signal Amplification

The real value of monitoring multiple platforms simultaneously is **pattern detection across sources.** A single Upwork posting for "custom client reporting tool" is noise. The same project type appearing on Upwork, PeoplePerHour, r/forhire, and Larajobs in the same week is a validated gap signal.

The agent should flag any project type that appears across 3+ platforms in a 30-day window as a **high-confidence gap signal** regardless of individual posting volume.

---

### Extraction Schema for All Layer 6b Sources

```sql
CREATE TABLE freelance_signals (
    id SERIAL PRIMARY KEY,
    source VARCHAR(50),              -- 'upwork', 'codeable', 'larajobs', 'indiehackers',
                                     --  'reddit_forhire', 'peopleperhour', 'guru', 
                                     --  'contra', 'reddit_webdev'
    project_title VARCHAR(255),
    project_description TEXT,
    budget_min INTEGER,              -- USD
    budget_max INTEGER,              -- USD
    recurring BOOLEAN,               -- True if ongoing/monthly work
    manual_workflow_mentioned BOOLEAN, -- "I've been doing this manually"
    no_tool_mentioned BOOLEAN,       -- "couldn't find a tool that"
    category VARCHAR(100),           -- 'reporting', 'automation', 'client_portal', etc.
    tech_stack VARCHAR(100),         -- Laravel, Vue, WordPress, etc. if mentioned
    posted_at TIMESTAMP,
    extracted_at TIMESTAMP DEFAULT NOW(),
    url TEXT
);
```

**Cross-platform pattern detection query — run weekly:**
```sql
SELECT 
    category,
    COUNT(DISTINCT source) as platform_count,
    COUNT(*) as total_postings,
    AVG(budget_max) as avg_budget,
    SUM(CASE WHEN recurring THEN 1 ELSE 0 END) as recurring_count
FROM freelance_signals
WHERE posted_at > NOW() - INTERVAL '30 days'
GROUP BY category
HAVING COUNT(DISTINCT source) >= 3
ORDER BY platform_count DESC, total_postings DESC;
```

Any category returning from this query is a **priority gap signal** — flag for immediate scoring against the rubric.

---

## Layer 8 — GitHub Issues & Feature Requests

Public GitHub repositories for popular open source tools are full of feature requests that never get built — not because there's no demand, but because maintainers are volunteers with limited time. A feature request issue with 200+ thumbs-up that's been open for 2+ years is a validated paid product waiting to exist.

This layer is particularly high-value given your Laravel ecosystem advantage. You know which packages people actually use, which maintainers are overwhelmed, and which feature gaps are genuinely painful vs. nice-to-have.

### Target Repositories

**Laravel ecosystem**
- laravel/framework
- spatie/* (all Spatie packages)
- livewire/livewire
- filamentphp/filament
- laravel/horizon
- laravel/telescope
- barryvdh/laravel-debugbar

**General PHP/developer tooling**
- Top 50 PHP repositories by stars on GitHub
- Any repository with 1K+ stars in the monitored problem categories

**Adjacent ecosystems** (ideas that could be Laravel-served)
- Top Vue.js packages
- Popular headless CMS repositories
- Popular API tooling repositories

### Signal Filters
- Issues labeled "feature request" or "enhancement"
- 👍 reaction count > 20 — meaningful demand threshold
- Issue age > 6 months — maintainer has consciously not built it
- Issue age > 24 months with no PR merged — almost certainly won't be built natively

### Query via GitHub API
```json
{
  "query": "is:issue is:open label:\"feature request\" reactions:>20",
  "sort": "reactions",
  "order": "desc"
}
```

Run per target repository weekly. Store: repo name, issue title, issue body, reaction count, age in days, label, URL.

### Signal to Look For
- Feature requests that describe a workflow, not just a bug fix
- Requests where multiple commenters describe the same pain in different words — organic demand, not astroturfed
- Requests referencing "I'm currently doing this manually" or "I pay for X to do this"
- Maintainer comments saying "out of scope for this package" — explicitly punted, not forgotten

---

## Layer 9 — AppSumo Deals & Reviews

AppSumo is one of the most underused idea validation sources available. When a product sells 2,000+ lifetime deal codes on AppSumo, that's proof of demand at scale — 2,000 paying customers in days. Their reviews are written by small business owners and solopreneurs who are brutally honest about what's missing, what's broken, and what they wish existed instead.

Two distinct signals come from AppSumo:

**Signal A — What sells:** Categories and product types that consistently perform well on AppSumo reveal what problems small business operators are actively paying to solve right now.

**Signal B — Review gaps:** 3-star AppSumo reviews follow a predictable pattern: "Great tool for X but completely missing Y." Those Y gaps are product ideas.

### Sources
- **AppSumo active deals** — appsumo.com/products (crawl weekly)
- **AppSumo past deals** — appsumo.com/products?sort=most-reviews (historical, crawl once then weekly updates)
- **AppSumo Plus deals** — higher-priced tier signals higher WTP audience

### Signal Filters
**For demand validation:**
- Products with 500+ reviews — sufficient sample size
- Products with 4+ star average — product works, reviews are about features not bugs
- Sales badge "X,000+ sold" — direct volume signal

**For gap mining:**
- Reviews rated 3–4 stars specifically
- Review text containing: "wish it had", "missing", "doesn't do", "would be perfect if", "waiting for"
- Patterns across multiple reviews mentioning the same missing feature

### Extraction Target
Store per AppSumo product: product name, category, review count, average rating, sales volume tier, top mentioned missing features (extracted from 3-star reviews), pricing model, launch date.

### Categories to Monitor
- Productivity & automation tools
- Marketing & SEO tools
- Business & sales tools
- Developer tools (smaller but highly relevant)
- Design & creative tools (for content/marketing angle)

### Critical Note on AppSumo Audience
AppSumo buyers skew toward non-technical small business owners hunting for deals. This is useful for validating SMB demand but requires adjustment for developer-targeted products — developer tools rarely appear on AppSumo, so absence of a category doesn't mean no demand.

---

## Layer 10 — Public Product Roadmaps

When a SaaS product's public roadmap has the same feature sitting at "planned" for 12+ months, that's an explicit gap signal. The company knows customers want it, hasn't built it, and probably won't — either because it's architecturally hard, outside their strategic focus, or they're too resource-constrained. Any of those reasons creates an opportunity for a focused tool.

### Sources to Monitor

**Canny boards (public)**
Search: `site:canny.io [product name]` for any tool in your monitored categories. Many SaaS products use Canny for public feature voting.

**GitHub Projects used as public roadmaps**
Many open source and hybrid products maintain public GitHub project boards. Monitor the same repositories as Layer 8.

**Trello public boards**
Some products use public Trello boards for roadmaps. Less common now but still used.

**Product-specific roadmap pages**
Direct crawl of known roadmap URLs for tools in your monitored categories:
```
[product].com/roadmap
[product].com/changelog  
[product].com/feedback
feedback.[product].com
```

### Signal Filters
- Feature has been in "planned" or "in progress" status for 6+ months — deprioritized
- Feature has been in "planned" status for 12+ months — almost certainly won't ship
- Vote count > 100 on a stuck feature — validated demand, acknowledged gap
- Feature request comments show users describing workarounds — they want it badly enough to hack around it

### Signal to Look For
The most valuable pattern: a feature with 200+ votes sitting at "planned" for 18 months on a tool used by your target audience. That's your product. The incumbent validated the demand and handed you the spec.

### Crawl Strategy
- **Weekly:** Canny boards for top 20 tools per monitored category
- **Monthly:** GitHub project boards for top repositories
- **One-time seed:** Historical review of roadmaps for all tools in signal-sources Layer 3 seed list

---

## Layer 11 — Stripe & Payment Processor Ecosystems

Stripe, Paddle, and Lemon Squeezy all maintain public directories of products built on their platforms. These aren't just marketing pages — they're curated lists of working businesses with verified payment integration, which means verified revenue activity.

### Sources

**Stripe**
- stripe.com/partners/directory — certified partners and products
- stripe.com/customers — case studies with business model details
- Stripe's annual "Built with Stripe" reports — revenue tier data

**Paddle**
- paddle.com/customers — SaaS products using Paddle with business descriptions
- Particularly useful: Paddle skews toward indie SaaS and bootstrapped products more than Stripe

**Lemon Squeezy**
- lemonsqueezy.com/discover — public product directory
- High signal for indie SaaS specifically — almost entirely bootstrapped founders
- Reviews and ratings visible per product

### How to Use This Layer

**Primary use — Layer 7 corpus seeding:** Products listed in these directories are verified paying businesses. Cross-reference with Indie Hackers and Reddit MRR posts to enrich the success pattern corpus with products that haven't publicly announced their revenue but are clearly generating it.

**Secondary use — Gap analysis:** Browse categories in these directories the same way you'd browse AppSumo. What problem categories have 50+ products? That's a proven market. What problem categories have 2–3 products with poor reviews? That's an underserved market.

**Lemon Squeezy Discover specifically** is the highest-signal source here — products are shown with sales counts and review scores, making it a mini-AppSumo for indie SaaS. Crawl it monthly and extract: product name, category, sales count tier, review score, pricing model.

### Signal Filters
- Products with 100+ sales on Lemon Squeezy — proven demand
- Paddle customers in your target categories with 3-star reviews — gap signals
- Stripe case studies mentioning manual processes replaced — workflow automation signals

---

## Layer 7 — Success Pattern Corpus

This layer is fundamentally different from all others. Layers 1–6 surface *problems*. Layer 7 surfaces *proven solutions* — products that actually reached meaningful revenue — and extracts the patterns that made them work. Used not as a primary scoring source but as a **pattern confidence booster** applied after the rubric scores an idea 60+.

### The Core Question This Layer Answers

*Does this idea rhyme with 3+ products that reached $1K MRR in the last 18 months?*

If yes → confidence boost, not a score change.
If no similar product has ever worked → flag as unvalidated pattern, not a kill.
If similar products worked 3+ years ago but not recently → flag as potentially saturated or timing-sensitive.

### ⚠️ Survivorship Bias Warning

This layer only contains products that worked publicly. The thousands of identical-looking products that launched the same week and failed silently are invisible. Pattern-matching against winners tells you what successful ideas look like — not what separates them from failures that looked identical at launch. Use this layer to boost confidence on ideas that already score well, never to rescue ideas that score poorly.

---

### Data Sources

**Source 1 — Indie Hackers Milestone Posts**
- URL: indiehackers.com/milestones
- Signal: Founders voluntarily post revenue milestones with product descriptions, pricing, audience, and distribution notes
- Filter: "$1K MRR", "$5K MRR", "first paying customer" posts from last 18 months
- Richest single source — includes founder narrative about what worked

**Source 2 — r/SaaS and r/indiehackers Revenue Posts**
- Query patterns:
  ```
  "just hit $1K MRR"
  "reached $1K MRR"
  "first $1K month"
  "hit $5K MRR"
  "crossed $1K MRR"
  "from $0 to $1K"
  ```
- Filter: Posts with 10+ upvotes from last 18 months
- Raw but rich — founders share what the product does, how they got customers, and what surprised them

**Source 3 — Acquire.com / MicroAcquire Listings**
- Products listed for sale include verified MRR, product age, audience, and business model
- A product at $2K MRR listed after 12–18 months tells you exactly what worked and at what scale
- Caveat: founders list when they want to exit — may skew toward products that hit a ceiling, not just successes

**Source 4 — Product Hunt "Maker" Revenue Badges**
- Products that publicly shared revenue milestones on PH with launch context
- Useful for correlating launch tactic (PH launch) with revenue outcome
- Supplement only — PH biases toward consumer and dev tools

**Crawl frequency:** Weekly for Reddit/IH posts. Monthly for Acquire.com listings.

---

### Structured Extraction Schema

Every success signal gets parsed into a `success_patterns` table with these fields. The agent uses this schema to compare against candidate ideas at scoring time.

```sql
CREATE TABLE success_patterns (
    id SERIAL PRIMARY KEY,
    source VARCHAR(50),           -- 'indiehackers', 'reddit', 'acquire', 'producthunt'
    product_name VARCHAR(255),
    product_description TEXT,
    
    -- Classification
    product_type VARCHAR(50),     -- 'saas_dashboard', 'api_tool', 'ai_wrapper', 
                                  --  'marketplace', 'plugin', 'one_time_tool'
    audience VARCHAR(50),         -- 'solo_devs', 'small_teams', 'smb', 'agencies',
                                  --  'freelancers', 'founders', 'consumers'
    problem_category VARCHAR(100),-- 'reporting', 'monitoring', 'automation', 
                                  --  'client_management', 'analytics', etc.
    
    -- Business model
    pricing_model VARCHAR(50),    -- 'subscription', 'one_time', 'usage_based', 'freemium'
    price_point_monthly INTEGER,  -- Monthly equivalent in USD
    
    -- Traction
    mrr_reported INTEGER,         -- MRR at time of post
    time_to_first_mrr_days INTEGER, -- Days from launch to first paying customer
    time_to_1k_mrr_days INTEGER,  -- Days from launch to $1K MRR
    solo_built BOOLEAN,           -- True if solo founder
    
    -- Distribution
    primary_distribution VARCHAR(100), -- 'seo', 'reddit', 'product_hunt', 
                                       --  'community', 'paid_ads', 'word_of_mouth'
    secondary_distribution VARCHAR(100),
    
    -- Pattern flags
    replaced_manual_workflow BOOLEAN,  -- Did it automate something people did manually?
    enterprise_gap BOOLEAN,            -- Did it undercut an expensive enterprise tool?
    niche_audience BOOLEAN,            -- Was the audience highly specific?
    ai_powered BOOLEAN,                -- Core AI feature or just tooling?
    
    -- Meta
    post_url TEXT,
    published_at TIMESTAMP,
    extracted_at TIMESTAMP DEFAULT NOW()
);
```

---

### Pattern Extraction Prompt

When the agent processes a raw success signal, it runs this extraction prompt before storing:

```
You are extracting structured data from a founder's revenue milestone post.

Extract the following fields as JSON. If a field cannot be determined from the text, use null.

Fields to extract:
- product_name: string
- product_description: 1-2 sentence plain English description
- product_type: one of [saas_dashboard, api_tool, ai_wrapper, marketplace, plugin, one_time_tool, other]
- audience: one of [solo_devs, small_teams, smb, agencies, freelancers, founders, consumers, other]
- problem_category: short phrase describing the problem solved (e.g. "client reporting", "error monitoring")
- pricing_model: one of [subscription, one_time, usage_based, freemium]
- price_point_monthly: integer USD (monthly equivalent even if annual)
- mrr_reported: integer USD
- time_to_first_mrr_days: integer or null
- time_to_1k_mrr_days: integer or null
- solo_built: boolean
- primary_distribution: the main channel that drove customers
- replaced_manual_workflow: boolean — did this automate something people did manually?
- enterprise_gap: boolean — did this undercut an expensive enterprise tool at a lower price?
- niche_audience: boolean — was the audience highly specific rather than general?
- ai_powered: boolean — is AI a core feature of the product?

Return only valid JSON. No explanation.

Post content:
{raw_signal_content}
```

---

### How the Agent Uses This Layer

**Step 1 — Trigger condition:** Only runs when a candidate idea scores 55+ on the main rubric. Below 55 the rubric already has enough signal to act.

**Step 2 — Similarity query:** The agent queries `success_patterns` for products that share at least 3 of these attributes with the candidate idea:
- Same `product_type`
- Same or adjacent `audience`
- Same `problem_category` or close semantic match
- Same `pricing_model`
- `price_point_monthly` within ±$20

**Step 3 — Recency filter:** Only patterns from the last 18 months count as strong signal. Patterns from 18–36 months ago are noted but flagged as potentially stale. Patterns older than 36 months are excluded.

**Step 4 — Pattern confidence output:** Agent appends one of four verdicts to the idea score:

| Verdict | Condition | Effect |
|---|---|---|
| ✅ **Pattern confirmed** | 3+ similar products hit $1K MRR in last 18 months | +5 to overall score, note distribution channels that worked |
| ⚠️ **Pattern exists but aging** | Similar products worked 18–36 months ago, none recent | No score change, flag timing risk |
| ❓ **No pattern found** | No similar products in corpus | No score change, flag as unvalidated — not a kill |
| 🚫 **Pattern failed** | Similar products launched but stalled below $1K MRR | -10 to overall score, note what killed them |

**Step 5 — Distribution insight:** When pattern is confirmed, extract the `primary_distribution` from matching successes and include it in the idea output. "3 similar products hit $1K MRR — all via SEO + Product Hunt launch" is actionable. "3 similar products hit $1K MRR — all via cold outreach" is a signal to check against Section 4 of the scoring criteria before proceeding.

---

### What Good Pattern Data Looks Like

A high-quality extracted pattern looks like this:

```json
{
  "product_name": "StatusPal",
  "product_description": "Status page tool for SaaS products, replacing manual incident email updates",
  "product_type": "saas_dashboard",
  "audience": "founders",
  "problem_category": "incident communication",
  "pricing_model": "subscription",
  "price_point_monthly": 29,
  "mrr_reported": 3200,
  "time_to_1k_mrr_days": 87,
  "solo_built": true,
  "primary_distribution": "seo",
  "replaced_manual_workflow": true,
  "enterprise_gap": true,
  "niche_audience": false,
  "ai_powered": false
}
```

This tells the agent: *a solo founder replaced a manual workflow with a $29/mo subscription targeting founders, reached $1K MRR in 87 days via SEO, undercutting enterprise tools.* Any candidate idea that rhymes with this pattern gets a confidence boost.

---

### Corpus Size Target

The pattern confidence check is only meaningful with enough data. Targets:

| Milestone | Corpus Size | Confidence Level |
|---|---|---|
| Launch | 50+ patterns | Low — directional only |
| 30 days | 200+ patterns | Medium — reasonable signal |
| 90 days | 500+ patterns | High — statistically meaningful |

Seed the corpus before launch using historical Indie Hackers posts and r/SaaS milestone threads. Don't wait for the weekly crawl to build the initial dataset — do a one-time bulk extraction first.

---

### What to Ignore in This Layer

- **Products that raised funding** — not comparable to solo bootstrap
- **Consumer apps** — B2C success patterns don't transfer to B2B SaaS
- **Products with "viral" growth** — not replicable without existing audience
- **Products in regulated industries** — healthcare, finance, legal add compliance overhead that makes patterns non-transferable
- **Products older than 36 months** — market conditions change too fast

Not all sources are equal. When signals conflict or the agent needs to weight inputs:

## Signal Quality Hierarchy

| Rank | Source | Layer | Signal Quality |
|---|---|---|---|
| 1 | Reddit "does X exist" with 10+ upvotes | 1 | ⭐⭐⭐⭐⭐ |
| 2 | G2 3-star reviews mentioning missing feature | 2 | ⭐⭐⭐⭐⭐ |
| 3 | Chrome Web Store 3-star reviews — "would pay for X" | 14 | ⭐⭐⭐⭐⭐ |
| 4 | VS Code Marketplace — team/backend feature requests | 15 | ⭐⭐⭐⭐⭐ |
| 5 | GitHub issue 200+ thumbs-up, open 12+ months | 8 | ⭐⭐⭐⭐⭐ |
| 6 | AppSumo 3-star reviews mentioning missing feature | 9 | ⭐⭐⭐⭐⭐ |
| 7 | Public roadmap feature stuck "planned" 12+ months with 100+ votes | 10 | ⭐⭐⭐⭐ |
| 8 | Gumroad 500+ sales product solving workflow manually | 12 | ⭐⭐⭐⭐ |
| 9 | Stack Overflow unanswered — 500+ views, 10+ upvotes, 12+ months | 16 | ⭐⭐⭐⭐ |
| 10 | Product Hunt comment gaps on competitor launches | 17 | ⭐⭐⭐⭐ |
| 11 | Dev influencer Twitter complaint with 10+ "me too" replies | 18 | ⭐⭐⭐⭐ |
| 12 | Capterra buyer guide — requirement no tool satisfies | 19 | ⭐⭐⭐⭐ |
| 13 | Same project type across 3+ freelance platforms in 30 days | 6b | ⭐⭐⭐⭐ |
| 14 | Codeable / Laravel.io / Larajobs recurring project pattern | 6b | ⭐⭐⭐⭐ |
| 15 | Public Slack/Discord — 3+ reactions, 5+ replies | 20 | ⭐⭐⭐⭐ |
| 16 | "Alternatives to X" search volume growing | 3 | ⭐⭐⭐ |
| 17 | Layer 7 pattern confirmed (3+ similar products hit $1K MRR recently) | 7 | ⭐⭐⭐ |
| 18 | Upwork/PeoplePerHour/Guru single-platform project pattern | 6b | ⭐⭐⭐ |
| 19 | Indie Hackers "looking to hire" — SaaS ruled out explicitly | 5 | ⭐⭐⭐ |
| 20 | Hacker News "Ask HN" with 50+ points | 13 | ⭐⭐⭐ |
| 21 | Indie Hackers "Ask IH" with engagement | 5 | ⭐⭐⭐ |
| 22 | Lemon Squeezy / Paddle — category with few products + poor reviews | 11 | ⭐⭐⭐ |
| 23 | Google Trends growing cluster | 4 | ⭐⭐ |
| 24 | Indeed / LinkedIn job board manual process descriptions | 6 | ⭐⭐ |
| 25 | AI-generated idea (this system's own output) | — | ⭐ |

---

## Layer 12 — Gumroad & Digital Product Marketplaces

Almost nobody mines Gumroad for SaaS ideas. When a creator is selling a spreadsheet template, Notion template, or Airtable base that automates a specific workflow — and that product has hundreds of sales — it means: the problem is real, people will pay to solve it, and the best solution currently available is a manual workaround. That's a SaaS gap hiding as a digital product.

A $15 Gumroad spreadsheet with 800 sales is a $29/mo SaaS waiting to exist. The creator validated the market for you.

### Sources
- **Gumroad Discover** — gumroad.com/discover (browse by category)
- **Payhip** — payhip.com/explore (secondary, similar model)
- **Lemon Squeezy Discover** — already covered in Layer 11 but worth cross-referencing here

### Categories to Monitor on Gumroad
```
"client reporting template"
"freelance invoice tracker"
"SaaS metrics spreadsheet"
"project status dashboard"
"content calendar template"
"social media scheduler spreadsheet"
"email sequence tracker"
"SEO audit template"
"developer productivity tracker"
"agency client onboarding"
```

### Signal Filters
- Sales count visible on product page — look for 200+ sales minimum
- Products priced $10–49 — cheap enough to buy impulsively, expensive enough to signal real WTP
- Products with reviews mentioning "I wish this was automated" or "I had to modify it for my workflow"
- Products updated multiple times — creator is responding to customer feedback, market is active

### Signal to Extract
Store per product: product name, description, price, estimated sales count, category, whether it describes a manual workflow (boolean), any review signals about automation wishes.

### Key Insight
The Gumroad signal is uniquely valuable because it captures **non-technical buyers** solving problems manually. These are the same buyers who would pay for a SaaS if it existed. Unlike Reddit (developers) or IH (founders), Gumroad buyers are often small business owners and solopreneurs — your SMB target audience.

---

## Layer 13 — Hacker News

Hacker News is different from Reddit in one important way: the signal-to-noise ratio on technical and product questions is extremely high, and the audience — senior developers, founders, technical PMs — represents a highly opinionated, high-WTP group. When something gets 50+ points on HN, a large number of discerning people decided it was worth their attention.

Two distinct signal types come from HN:

**Signal A — "Ask HN" tool requests:** Direct demand for something that doesn't exist yet.

**Signal B — "Show HN" reception:** When a founder posts "Show HN: I built X because nothing existed" and gets 200+ points, that's the market validating both the problem and the solution simultaneously.

### Sources
- **Hacker News Algolia search API** — hn.algolia.com/api (free, no auth required)
- **HN front page and Ask HN section** — monitor weekly

### Query Patterns via Algolia API
```
"Ask HN: Is there a tool"
"Ask HN: What do you use for"
"Ask HN: Does anyone know"
"Show HN:" [filter by score > 100]
"I built this because nothing existed"
"we couldn't find a tool that"
"we were frustrated that no tool"
```

### Signal Filters
- **Ask HN posts:** Score > 50 points, posted in last 18 months, comments contain "I've been looking for this" or "I'd pay for this"
- **Show HN posts:** Score > 100 points — front page validation. Comments saying "how much does this cost" = WTP signal
- **Comment threads:** Individual comments with 20+ points describing a pain point are worth extracting even when the parent post isn't directly relevant

### What Makes HN Signal Valuable
HN commenters are unusually candid about whether they'd pay for something. "I'd pay for this tomorrow" and "we've been doing this manually for years" in the same comment thread is about as strong a validation signal as you'll find publicly. Equally useful: "we tried 5 tools and none of them did X" in a HN comment thread gives you competitor analysis and gap definition in one sentence.

### Crawl Frequency
- **Weekly:** Algolia API search for new Ask HN and Show HN posts matching query patterns
- **One-time seed:** Historical search of Ask HN posts from last 24 months — rich corpus of unmet needs

---

## Layer 14 — Chrome Web Store Reviews

Chrome extensions with large install bases and 3-star reviews are one of the most underused SaaS idea sources available. When an extension has 50K+ installs and consistent reviews saying "I wish this had a backend," "needs team features," or "would pay for a pro version that does X" — that's a validated market asking for a SaaS product that doesn't exist yet.

The signal is uniquely strong because: installs are public (volume confirmed), reviews are honest (Chrome users are blunt), and the gap is specific (they tell you exactly what's missing).

### Target Categories to Monitor
- Productivity and workflow extensions
- Developer tools extensions
- SEO and marketing extensions
- Client and project management extensions
- Time tracking and reporting extensions
- Email and communication extensions

### Signal Filters
- Extensions with 10,000+ installs — sufficient user base for meaningful reviews
- Average rating 3.0–3.9 stars — product works but has real gaps
- Review text containing: "wish it had", "missing", "would pay for", "needs a pro version", "backend", "team features", "export to", "integrate with"
- Reviews mentioning a specific workflow that the extension only partially solves

### Crawl Method
Apify has a Chrome Web Store scraper actor. Run weekly on top 50 extensions per target category. Extract: extension name, install count, rating, review text, review date.

### Key Signal Pattern
"Great free tool but I need it to sync across my team and store data — would happily pay $20/mo for that" = a SaaS product with a validated free acquisition channel built in. The extension becomes your top-of-funnel.

---

## Layer 15 — VS Code Marketplace Reviews

VS Code extensions follow the same pattern as Chrome extensions but with a developer-specific audience — your exact target customer. Extensions with 100K+ installs and feature request patterns in reviews represent validated developer pain with a built-in distribution channel.

Uniquely valuable: VS Code extension reviews skew more technical and specific than Chrome reviews. Developers describe exactly what workflow they're trying to accomplish and why the extension falls short. That specificity makes gap identification faster and more reliable.

### Target Categories
- Code productivity and snippet tools
- Git and version control helpers
- API testing and documentation tools
- Database management tools
- Deployment and DevOps helpers
- Laravel/PHP specific extensions
- AI coding assistant extensions (gaps in existing tools)

### Signal Filters
- Extensions with 50,000+ installs
- Reviews mentioning "SaaS version", "team features", "cloud sync", "persistent storage", "backend"
- Issues on the extension's GitHub repo with feature requests for server-side functionality
- Extensions that are free with reviews asking about paid tiers

### Crawl Method
VS Code Marketplace has a public API: `marketplace.visualstudio.com/_apis/public/gallery/extensionquery`
Run weekly. Extract: extension name, install count, rating, review text, linked GitHub repo for cross-referencing with Layer 8.

### Compound Signal
When a VS Code extension GitHub repo (Layer 8) has 100+ thumbs-up on a feature request AND the Marketplace reviews mention the same gap — that's a double-confirmed signal from the same audience.

---

## Layer 16 — Stack Overflow Unanswered Questions

Stack Overflow has a uniquely reliable gap signal: questions with high views and upvotes but no accepted answer after 12+ months. This means developers want to accomplish X, have searched extensively, asked publicly, and still found no clean solution. That's a product gap with a measurable demand size (view count = market size proxy).

Unlike Reddit where people discuss problems, Stack Overflow surfaces problems that people have actively tried to solve technically and failed. The specificity is unmatched — you know exactly what the developer was trying to build.

### Access Method
Stack Exchange API — free, no auth required for read operations:
```
https://api.stackexchange.com/2.3/questions?
  order=desc&
  sort=votes&
  tagged=laravel;php;vue.js&
  filter=!9_bDE(fI5&
  site=stackoverflow&
  min=10&           // minimum score
  fromdate=TIMESTAMP
```

### Signal Filters
- Score (upvotes) > 10 — meaningful demand
- View count > 500 — real traffic, not a one-off
- No accepted answer — gap confirmed
- Question age > 12 months — not just unanswered yet, genuinely unsolved
- Tags relevant to your monitored categories

### Tag Clusters to Monitor
```
Laravel, PHP, Vue.js, Filament, Livewire     # Your ecosystem
freelance, invoicing, client-management       # Your target customer
saas, stripe, subscription, billing           # Product category
automation, workflow, scheduling              # Problem type
api, webhook, integration                     # Technical gaps
```

### Signal to Extract
Store: question title, body summary, score, view count, answer count, accepted answer boolean, age in days, tags, URL. Flag any question with 0 accepted answers, score > 10, views > 1000, age > 365 days as high-priority gap signal.

### Why This Is Underused
Most idea researchers look at Reddit and IH. Nobody is systematically mining Stack Overflow for product gaps. A question with 5,000 views and no accepted answer is 5,000 developers who hit the same wall — and none of them found a product that solved it.

---

## Layer 17 — Product Hunt Comment Sections

Product Hunt launches are in the system already as competitive awareness signals. But the comment sections on those launches are an entirely separate and richer signal source. When a product launches on PH, buyers actively evaluating it post specific feature gaps, comparison questions, and "would you consider adding X" requests in real time.

These comments represent buyers at peak purchase intent — they're on the product page, interested enough to comment, and telling you exactly what would make them buy.

### Signal Types

**Gap comments on competitor launches:**
"Love this but I need it to also do X — any plans?" = feature gap + WTP signal

**Comparison questions:**
"How does this compare to Y on Z feature?" = competitive landscape + unmet need

**"Almost bought but" comments:**
"Was going to sign up but it doesn't integrate with X" = specific gap with lost sale attached

**Price sensitivity comments:**
"Would sign up at $X/mo but not $Y" = pricing calibration data

### Crawl Method
Product Hunt has a public GraphQL API. Query weekly for all launches in your monitored categories from the last 30 days. Extract all comments with 3+ upvotes.

```graphql
{
  posts(first: 20, topic: "developer-tools") {
    edges {
      node {
        name
        tagline
        commentsCount
        comments(first: 50, order: VOTES_COUNT) {
          edges {
            node {
              body
              votesCount
              createdAt
            }
          }
        }
      }
    }
  }
}
```

### Signal Filters
- Comments with 3+ upvotes — community validated the point
- Comments containing: "missing", "wish it had", "would buy if", "doesn't do", "waiting for", "almost signed up"
- Comments from users with existing PH history — not throwaway accounts

---

## Layer 18 — Developer Influencer Complaints (Targeted Twitter/X)

General Twitter/X is excluded from the system as too noisy. But targeted monitoring of a small list of influential developers is a different signal entirely. When Taylor Otwell complains about a missing tool, or Adam Wathan describes a workflow he's hacking together manually, or DHH expresses frustration with an existing solution — that's not a random tweet. It's a signal from someone whose opinion shapes what tens of thousands of developers think and buy.

These complaints are rare, which is exactly what makes them high signal. Influential developers don't complain publicly unless something is genuinely painful.

### Target Accounts — Laravel/PHP Ecosystem
- @taylorotwell (Taylor Otwell — Laravel creator)
- @adamwathan (Adam Wathan — Tailwind CSS)
- @calebporzio (Caleb Porzio — Livewire/Alpine)
- @jessarchercodes (Jess Archer — Filament core)
- @reinink (Jonathan Reinink — Inertia.js)
- @freekmurze (Freek Van der Herten — Spatie)
- @sebastiandedeyne (Sebastian De Deyne — Spatie)

### Target Accounts — Indie SaaS / Founder Ecosystem
- @DHH (DHH — Rails, Basecamp)
- @levelsio (Pieter Levels — prolific indie founder)
- @marc_louvion (Marc Lou — prolific micro-SaaS builder)
- @jdnoc (Jon Yongfook — Bannerbear)
- @csallen (Courtland Allen — Indie Hackers founder)
- @dvassallo (Daniel Vassallo — small bets)

### Signal Filters
- Tweets describing a manual workflow they're doing themselves
- Complaints about an existing tool's limitations
- "I wish someone would build X" or "does anyone know a tool that does X"
- Replies from followers saying "yes I have this problem too" with 10+ likes

### Crawl Method
Twitter/X API basic tier ($100/mo) or use Apify Twitter scraper for targeted account monitoring. Run weekly. Extract tweets and replies from target accounts only — not general search.

### Important Constraint
This layer is account-specific, not keyword-specific. The signal quality comes from who is saying it, not just what is being said. Do not expand this to general Twitter search — that's the noise this layer is specifically designed to avoid.

---

## Layer 19 — Capterra Buyer Guides & Category Pages

Capterra is different from G2 in one important way: its buyer guide pages describe what buyers are looking for across a category, not just what they think of specific products. The "what to look for in X software" sections on Capterra category pages are written from aggregated buyer research — they describe requirements that multiple buyers have expressed.

When a requirement appears prominently in a Capterra buyer guide AND no product in their comparison table fully satisfies it — that's a category-level gap signal, not just a product-level one.

### Signal Sources on Capterra
- **Category buyer guides** — capterra.com/[category]/buyers-guide
- **"Alternatives to X" comparison pages** — capterra.com/[tool]/alternatives
- **Category comparison tables** — feature checkboxes that show which tools lack which features
- **"Not sure about X? Consider these alternatives" sections** — explicit switching intent

### Signal Filters
- Buyer guide sections titled "key features to look for" — extract feature requirements
- Comparison tables where a column is mostly empty checkboxes — gap across multiple products
- "Limitations" sections in individual product reviews
- "Who it's best for / not best for" sections — audience segmentation gaps

### Crawl Method
Serper.dev site search + Apify for specific Capterra category pages. Run monthly — buyer guides don't change weekly.

```
site:capterra.com/[category]/buyers-guide
site:capterra.com inurl:alternatives
```

### What Makes This Unique
Capterra buyer guides aggregate buyer requirements from actual software purchasing processes. When they list "X is a key feature" — that's not opinion, it's synthesized from real buyer interviews and search behavior. It's market research handed to you for free.

---

## Layer 20 — Public Slack & Discord Community Archives

Many indie hacker, SaaS founder, and developer communities have public archives or public channels where members ask "does anyone know a tool that does X." This is identical in signal quality to Reddit but represents a different, often more committed audience — people who have self-selected into a community around a specific topic.

### High-Value Communities with Public Access

**Developer communities**
- Laravel Discord — discord.gg/laravel (public read access on many channels)
- Vue Land Discord — public channels
- Filament Discord — public channels
- Dev.to community Slack (partial public archive)

**Indie SaaS / Founder communities**
- Indie Hackers community (forum posts accessible without login)
- WIP.co public maker logs — makers sharing what they're building and what tools they need
- Makerpad community (some public content)
- Starter Story community posts

### Signal Query Patterns
```
"does anyone know a tool"
"looking for something that"
"is there a way to automate"
"what do you use for"
"I've been doing this manually"
"can't find anything that"
"built something for this"
"anyone else frustrated"
```

### Crawl Method
- Discord public channels: Discord API read access for public servers
- Slack public archives: direct web crawl where available
- WIP.co: public API available
- Indie Hackers forum: crawlable without auth

### Signal Filters
- Messages with 3+ emoji reactions — community validated the pain
- Thread with 5+ replies — engaged discussion, not a one-off question
- Messages from users with established community history — not spam accounts
- Posted in last 6 months — active pain, not historical

### Why This Differs from Reddit
Reddit is public by default and heavily indexed. Slack/Discord communities are semi-private and almost never systematically mined for idea signals. The people in these communities have higher signal-to-noise than general Reddit — they've opted into a specific community around a topic they care about deeply. A question in the Laravel Discord gets fewer responses than a Laravel subreddit post, but the responses are from more experienced practitioners with stronger opinions.

---

## What to Ignore

These sources sound useful but produce noise:

- **Twitter/X viral threads and general search** — too reactive, too surface-level, too many dunks and not enough pain. *Exception: targeted monitoring of specific developer influencer accounts is high signal — see Layer 18 for the approved account list and crawl method. General Twitter search is excluded; account-specific monitoring is included.*
- **LinkedIn posts** — performative, not candid
- **Press releases and tech news** — lags real market by 6–12 months, optimism bias
- **Quora** — outdated, low signal-to-noise
- **General "best tools for X" listicles** — SEO content, not genuine pain signal
- **AI-generated idea lists from other tools** — recursive noise

---

*This document should be reviewed quarterly. Add subreddits and tools as new categories emerge. Remove sources that consistently produce low-quality signals after 90 days of data.*
