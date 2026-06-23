# Idea Scoring Criteria
## AI Idea Engine — Personal Constraint Document
*Version 2.0 — Locked June 2026*
*Calibrated across 13 scoring rounds, 58 ideas, 12 review passes*
*Next review: September 2026 or when constraints materially change*

---

## Purpose

This document is the system prompt source of truth for the AI scoring agent. Every idea the system surfaces gets evaluated against these constraints — not in the abstract, but against *this specific person's* situation. A 60% score here means something precise. Update this document when your situation changes; the agent is only as good as what's written here.

**Meta-goal:** This entire system is an experiment in automated idea-to-revenue pipeline. The question being tested is: *can a solo dev go from raw market signal to paying customers with the least possible manual intervention?*

---

## Section 1 — Success Definition

**Target outcome (18 months):** $1,000–$3,000 MRR as a side income while maintaining current employment.

This is not a moonshot filter. This is a *realism* filter. The hard floor is $400/mo — see Revenue Plausibility (Dimension 6) for ceiling scoring mechanics. "Bluesky $10M ARR" framing is irrelevant — ignore it.

---

## Section 2 — Hard Constraints

These are fixed. They do not bend for a good idea.

| Constraint | Value |
|---|---|
| Max upfront investment | $10,000 (tools, infra, ads — not time) |
| Max build time before revenue signal | 4 months (16 weeks) — ideas requiring longer are high variance; reflect in Build Feasibility score |
| Available hours per week | ~10 hours |
| Preferred stack | Laravel + Vue (primary); Python or other tools acceptable if clearly better fit |
| Team size | Solo — no co-founder, no employees |

**Upfront investment scoring effect:** If an idea credibly requires more than $10K upfront before revenue is possible — paid data licensing, significant infrastructure, required legal/compliance spend — reflect this in Build Feasibility score (scores lower in the 40–59 range). This is a controllable constraint — if the idea scores exceptionally well elsewhere, higher upfront investment is a tradeoff worth making. No hard cap.

---

## Section 3 — Automatic Kill Conditions

If any of the following **hard kills** are true, the idea scores 0 regardless of other signals. Do not score around them.

**Hard kills (score 0, discard immediately):**
1. **Requires sales calls or demos to close** — Self-serve only. No outbound sales or demo-gated conversion.
2. **Hardware or physical component** — No manufacturing, no supply chain, no shipping.
3. **Ad-supported content site as primary model** — Too slow to $400/mo floor, not a software product, incompatible with self-serve SaaS constraints.

**Soft kills (do not score 0 — apply the specified dimension effects and proceed with explicit justification):**
4. **Well-funded direct competitor ($1M+ ARR)** — Market reality, can't control. Pushes Competition Gap below 20, triggering the 35 overall cap. Apply the cap cascade in Dimension 3 directly.
5. **Network effect required at launch** — Market reality, can't control. Hard-caps Distribution Path at 35 — you can't bootstrap a network effect solo regardless of build quality. A score of 35 on Distribution Path sits at the top of the "no realistic path" range — meaning distribution is severely constrained but not technically impossible. The overall score will reflect this drag.
6. **Large content library required at launch** — Market reality if content must be manually created or user-generated. Hard-caps Revenue Plausibility at 35 — can't launch without it. If content is obtainable (scraped, licensed, public data), no penalty applies.
7. **Platform absorption risk** — Market reality, can't control. Hard-caps Revenue Plausibility at 45 — if the platform ships your feature, revenue disappears regardless of build quality. No cap on Build Feasibility — you can still build it, the risk is commercial not technical.
8. **Data business disguised as software** — Partially controllable (you can source data) but high effort and high risk. Scores Build Feasibility in the 40–59 range — reflects difficulty without capping. Also hard-caps Revenue Plausibility at 40 — without a viable data sourcing plan the product has no value. Requires explicit sourcing plan before proceeding.

---

## Section 4 — Distribution Reality Check

**Available distribution channels:**

| Channel | Realistic? | Notes |
|---|---|---|
| SEO | Yes | Can rank content, willing to wait 3–6 months for traction |
| Product Hunt launch | Yes | One-shot, not a long-term channel |
| Reddit / community seeding | Yes | Dev communities, indie hacker spaces |
| Paid ads | Conditional | Only if unit economics make ROI clear upfront |
| Cold outreach (automated) | Conditional | Only if fully automatable via email sequences, LinkedIn automation, or similar. Manual outreach at scale is excluded. |
| Twitter/X audience | No | No existing audience |
| Enterprise relationships | No | No rolodex |

**Scoring implication:** Any idea whose *only* viable distribution is manual cold outreach, enterprise relationships, or paid ads with unclear ROI should score poorly on distribution. Automated outreach (email sequences, drip campaigns, LinkedIn automation) is acceptable if the targeting is precise. Distribution must have at least one realistic low-touch or automated path to first 100 customers.

**Direct link to Dimension 2:** If an idea's only viable distribution channel is listed as "No" in the table above (Twitter/X audience, Enterprise relationships), score Distribution Path below 40. A single realistic channel from the "Yes" or "Conditional" rows is enough to score normally.

---

## Section 5 — Target Customer

**No hard category exclusions. The rubric dimensions handle suitability naturally.**

Willingness to pay and reachability are what matter — not category labels. B2C ideas face structural scoring challenges through Distribution Path (consumer acquisition requires virality or paid ads), Revenue Plausibility (low ARPU, high volume, high churn), and Problem Strength (free substitutes dominate consumer categories). A genuinely strong B2C idea that clears 60 on the rubric deserves to be on the watch list. A weak one won't clear regardless. Don't add bias — let the dimensions do their job.

**Preferred customer types (in order):**
1. Solo devs and indie hackers
2. Small dev teams (2–10 people)
3. Freelance developers and agencies
4. SMB founders and operators (technical buyer preferred)
5. Any buyer with clear WTP and a reachable distribution channel

**Avoid (buyer characteristics, not categories):**
- Buyers with no clear distribution channel to reach them
- Buyers who need hand-holding before they see value — activation energy kills conversion

---

## Section 6 — Acceptable Product Shapes

*Guidance only — the ✅ list does not add points to the score. Ideas that fit these archetypes are easier to build and distribute at solo scale, but fitting them doesn't change the score. The ❌ item is the only entry with a mechanical scoring effect.*

The idea must fit one of these product archetypes:

- ✅ SaaS dashboard with recurring subscription
- ✅ Pure API / developer tool
- ✅ AI wrapper with clear workflow value (not a thin wrapper — must save real time or remove real pain)
- ✅ One-time purchase tool or plugin
- ✅ Marketplace (two-sided) — only if cold start problem is solvable solo
- ✅ Mobile app — buildable but adds complexity; reflect in Build Feasibility score

**Not acceptable:**
- ❌ Hardware product — hard kill, see Section 3

---

## Section 6b — Product Definition Specificity Gate

**This gate runs before scoring. If the idea fails it, scoring does not proceed.**

Ideas that rely on vague framing score artificially high because each scoring dimension fills in the best-case interpretation. Specificity forces the product into the real world where tradeoffs appear.

Before scoring any idea, the agent must be able to answer all four questions below with one concrete sentence each. If any answer is vague, unclear, or relies on "it depends," stop and resolve it first.

**Question 1 — What does the user actually do on day one?**
Not what the product does. What does the *user* do. What screen do they see, what do they click, what do they configure. A vague answer ("they set up automations") means the product isn't defined yet.

**Question 2 — What does the product do that the user couldn't do in 10 minutes with a free tool?**
This is the free substitute and behavior change test combined. If the honest answer is "not much" or "they could use Zapier/ChatGPT/a spreadsheet," the score needs to reflect that before any dimension is evaluated.

**Question 3 — Who specifically is the first paying customer?**
Not "developers" or "SaaS founders." A real person with a real job title at a real company size doing a specific thing. "A solo Laravel developer who has built a SaaS product with 50+ customers and needs to offer webhooks to those customers" is specific. "Laravel developers" is not.

**Question 4 — What does the direct competitor do, and why would someone switch?**
Name the direct competitor. Describe what it does. State specifically why someone using that competitor would pay for this instead. "It's cheaper" is not enough — cheaper only works if the existing tool is actively resented for its price, which requires evidence.

**If all four are answered specifically and honestly, proceed to scoring.**
If any answer reveals a fatal flaw — free substitute is better, no real switching reason, customer is too vague to find — score it 0 and discard. Don't score around a flaw.

---

## Section 7 — Scoring Rubric

Each idea is scored 0–100. Score = weighted average across six dimensions.

### Dimension 1 — Problem Strength (20%)

*Is this a real, recurring pain with evidence people will pay to solve it — not just work around it?*

**Critical distinction:** Behavior evidence (people do X manually) is not the same as willingness-to-pay evidence (people pay money to avoid doing X). Behavior alone caps at 60. Scores above 60 require WTP signal.

| Score | Meaning |
|---|---|
| 80–100 | Paid tools exist with real reviews AND/OR people actively complain that free tools are insufficient AND/OR people are asking where to pay for a solution |
| 60–79 | Clear behavior signal (people doing this manually at scale) but WTP is inferred, not confirmed |
| 40–59 | Mild inconvenience; people tolerate it, work around it for free, or don't mention it unprompted |
| 0–39 | Manufactured problem, niche problem unique to the builder, or a free tool adequately solves it — WTP near zero |

**Behavior change tax:** If the product requires users to meaningfully change how they currently work before it delivers value — structured documentation they don't currently produce, moving communication to a new platform, logging things they currently don't log — apply a -15 penalty to this dimension. The pain is real but activation energy kills conversion. Fits-existing-workflow products beat requires-new-workflow products at the indie price point every time.

**Developer WTP resistance:** If the target customer is a developer AND the product solves something developers feel they could build themselves or find free on GitHub, apply a -15 penalty to this dimension. Developers are the hardest customers to charge for tooling they culturally believe should be free.

**Stacking rule:** Behavior change tax and Developer WTP resistance cannot both apply to the same idea. Apply whichever penalty is stronger — maximum -15 to this dimension from modifiers, never -30.

**Free substitute rule:** If a credible free tool (ChatGPT, open source, freemium dominant player) adequately solves the core problem, this dimension is hard-capped at 30 regardless of behavior signals. This cap supersedes any modifier penalties — don't apply behavior change tax or WTP resistance on top of a free substitute cap.

**Gate interaction:** If the idea already passed Question 2 of the Specificity Gate ("what does this do that a free tool couldn't do in 10 minutes?"), do not re-evaluate the free substitute question here unless new competitive evidence emerged specifically during scoring. Passing the gate is sufficient — don't double-penalize.

### Dimension 2 — Distribution Path (20%)

*Can the first 100 customers be reached without manual effort at scale?*

| Score | Meaning |
|---|---|
| 80–100 | Clear SEO angle OR automated outreach path OR strong community fit — no manual grind required |
| 60–79 | Requires some paid spend or community effort, but path is plausible and mostly low-touch |
| 40–59 | Distribution is unclear or requires significant manual effort |
| 0–39 | No realistic path to first 100 customers without manual outbound or enterprise sales |

### Dimension 3 — Competition Gap (20%)

*Is there a real gap, or is this walking into a solved problem?*

**Note on free substitutes:** Free substitute signal belongs in Problem Strength (where WTP is evaluated), not here. This dimension evaluates the competitive landscape of paid solutions only.

**Market saturation rule:** If 3+ direct paid competitors appear on the first Google search, this dimension scores below 25.

**Overall cap cascade — clean single rule, no stacking:**
- Competition Gap scores 25–39 → overall idea score hard-capped at 45
- Competition Gap scores 20–24 → overall idea score hard-capped at 40
- Competition Gap scores below 20 → overall idea score hard-capped at 35 (replaces lower caps, does not add to them)

**Enterprise gap pattern validation gate:** If the scoring rationale relies on "enterprise tool exists but no indie-priced alternative," this pattern requires explicit validation before the idea progresses — specifically, evidence that indie founders are actively complaining about the enterprise tool's price in forums, reviews, or communities. Assumed pricing gap is not enough.

| Score | Meaning |
|---|---|
| 80–100 | No dominant player; existing tools are old, clunky, or miss a clear segment |
| 60–79 | Competitors exist but have exploitable weaknesses (price, UX, focus, audience) |
| 40–59 | Crowded market; will need strong differentiation to carve out space |
| 25–39 | 3+ direct paid competitors OR niche-dominant player owns the space → overall cap 45 |
| 20–24 | Dominant player plus crowded market — meaningful gap is unlikely → overall cap 40 |
| 0–19 | Well-funded dominant player owns the space OR free tool adequately solves it → overall cap 35 |

### Dimension 4 — Build Feasibility (20%)

*Can a useful v1 ship in 4 months at 10 hrs/week? If not, how long realistically?*

**Philosophy:** Build Feasibility reflects execution risk — something you can control. Complexity, integration count, technical novelty, and upfront investment all affect this score honestly, but none of them produce hard caps or override strong market signal from other dimensions. A hard-to-build idea that scores 45 here but 80+ on Problem Strength, Competition Gap, and Revenue Plausibility is still a strong candidate — the weighted average handles the tradeoff. Score this dimension honestly and let the math do its job.

**What this dimension measures:**
- Estimated time to a working, sellable v1 at 10 hrs/week
- Stack familiarity — familiar tech ships faster and more reliably
- Integration count — each required third-party API at launch is a point of failure and timeline risk
- Technical novelty — known-solution problems vs. genuinely unsolved technical challenges
- Minimum viable scope — whether a narrow v1 exists or every feature is required before the product has value

**Integration count guidance (informational, reflected in time estimate and score):**
- 0–2 required integrations at launch: straightforward, score normally
- 3–4 required integrations: adds 2–4 weeks, score accordingly in the time estimate
- 5+ required integrations: adds significant risk and timeline, reflect in score — but a well-scoped v1 that defers non-essential integrations is still viable

**Complexity multipliers (each adds to time estimate and pulls score lower within its range):**
- Mobile app as primary interface: adds 4–8 weeks — native mobile requires a different build approach; reflect in time estimate and score lower within the appropriate range
- Realtime features (websockets, live updates): adds 2–3 weeks
- Multi-tenant architecture from day one: adds 2–4 weeks
- Payment marketplace with splits: adds 3–4 weeks

**Technical novelty guidance (informational, not a penalty cap):**
- Known-solution problem (use existing library, follow established pattern): score normally
- New-to-me but well-documented technology: add 2–3 weeks, score accordingly
- Genuinely novel technical problem with no clear solution path: reflect the uncertainty in a lower score — this is high variance, not impossible

**Minimum viable scope guidance:**
- Clear narrow v1 exists (ship one feature, validate, expand): score higher — lower risk of never shipping
- Every feature required before the product has value: score lower — scope creep risk is real, but don't automatically cap

| Score | Meaning |
|---|---|
| 80–100 | Core v1 is 4–8 weeks at 10 hrs/week; familiar stack; 0–2 integrations; clear narrow scope |
| 60–79 | 8–12 weeks; some new territory or 3–4 integrations; manageable with discipline |
| 40–59 | 12–16 weeks; significant new tech OR 5+ integrations OR no clear v1 subset — hard but not impossible |
| 0–39 | 16+ weeks or genuinely unsolved technical problem at the core — very high variance, proceed only if other dimensions are exceptional |

### Dimension 5 — Automability (10%)

*How much of the acquire → onboard → retain loop can run without human intervention?*

| Score | Meaning |
|---|---|
| 80–100 | Acquisition, onboarding, billing, and basic support are all automatable from day one |
| 60–79 | Most of the loop is automatable; some manual touch required early on |
| 40–59 | Significant manual effort required at launch (demos, custom onboarding, hand-holding) |
| 0–39 | Cannot reach first 10 paying customers without significant manual intervention at every stage |

### Dimension 6 — Revenue Plausibility (10%)

*Is $1K MRR achievable within 12 months of launch? What is the realistic ceiling?*

**Hard disqualification:** If the total addressable market credibly caps below $400/mo — meaning even 100% market penetration of a realistic solo-served audience wouldn't reach $400 MRR — score this dimension 0 and discard the idea. Not worth building at any quality level.

**MRR ceiling scoring:** The ceiling matters as much as the path. An idea with a $500 MRR ceiling is a dead end even if it reaches that quickly. There is no upper cap — the higher the realistic ceiling, the better.

**Order of operations for this dimension:**
1. Check hard disqualification (below $400/mo → score 0, stop)
2. Apply the score table below based on achievability and pricing model
3. Apply ceiling adjustment as a bonus or cap on top of the score table result
4. Cap final dimension score at 100

| Realistic MRR Ceiling | Revenue Plausibility Adjustment |
|---|---|
| Below $400/mo | Score 0 — hard disqualification, stop here |
| $400–$999/mo | Hard-cap this dimension at 35 regardless of score table result |
| $1K–$3K/mo | Score normally — this is the target range, no adjustment |
| $3K–$10K/mo | +10 bonus applied after score table — cap final score at 100 |
| $10K+/mo | +20 bonus applied after score table — cap final score at 100 |

**Feature not a product rule:** If the idea would fit naturally as a single feature in an existing tool category — describable as "[existing tool] but with X added" where X is one checkbox feature — hard-cap this dimension at 20. A standalone product needs to justify its own subscription. Features get shipped by incumbents next quarter.

**One-time pricing penalty:** A one-time purchase model requires constant new customer acquisition to sustain revenue. For a $1K MRR equivalent goal, a $29 one-time tool needs ~35 new paying customers every single month with zero churn protection. This is a treadmill, not a business. One-time pricing hard-caps this dimension at 40 unless there's a clear recurring upgrade path.

| Score | Meaning |
|---|---|
| 80–100 | Recurring subscription with clear path to $1K MRR at 20–50 customers; strong ARPU |
| 60–79 | Recurring model with reasonable assumptions needed about conversion or pricing tier |
| 40–59 | One-time purchase with a plausible recurring upsell, OR recurring but very low ARPU requiring high volume |
| 0–39 | One-time purchase with no recurring path, market too small, or pricing model fundamentally unclear |

*Note: Ceiling adjustments from the table above are applied after this score. The rows above reflect achievability and pricing model only — ceiling upside or downside is handled separately.*

---

## Section 8 — Score Interpretation

| Overall Score | Action |
|---|---|
| 75–100 | Strong signal. Do 30-minute manual validation immediately. |
| 60–74 | Worth investigating. Queue for weekly manual review. |
| 45–59 | Weak signal. If competitive search returns no direct paid competitors → investigate further. If search confirms 3+ direct competitors → discard. |
| 0–44 | Discard. Do not revisit unless fundamental market change. |

**Important:** A 60% score is not an endorsement. It means "worth a closer look," not "build this." Manual validation is required before any serious time investment.

---

## Section 9 — What a 60% Idea Looks Like vs. a 40% Idea

**60% example:** A Laravel-specific deployment checklist SaaS. Clear audience, SEO-friendly content angle, no dominant player, buildable in 6 weeks, $19/mo pricing makes sense. Weakness: realistic MRR ceiling is below $1K/mo — caps Revenue Plausibility at 35, which drags the overall score down despite strong other dimensions.

**40% example:** A general project management tool for freelancers. Real pain, but distribution is a nightmare (everyone is already using something), competition is brutal (Notion, Linear, Todoist all partially solve it), and differentiation is unclear without cold outreach to validate the angle.

**The difference:** The 60% idea has at least two strong dimensions and no glaring distribution problem. The 40% idea has one real pain signal but no clear path to reach the right customers without significant spend that may not be recoverable.

---

*This document should be reviewed and updated every 90 days, or whenever your constraints materially change (new job, new budget, new skills, new audience).*
