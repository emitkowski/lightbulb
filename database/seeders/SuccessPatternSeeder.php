<?php

namespace Database\Seeders;

use App\Models\SuccessPattern;
use Illuminate\Database\Seeder;

class SuccessPatternSeeder extends Seeder
{
    public function run(): void
    {
        $patterns = [

            // ── Developer Tools ──────────────────────────────────────────────────

            [
                'product_name' => 'Screenshotone',
                'revenue_milestone' => '$10K MRR',
                'mrr_amount' => 10000,
                'category' => 'developer-tools',
                'description' => 'API for taking website screenshots on demand. Renders any URL as a PNG, JPEG, or PDF with options for viewport, delay, and scroll.',
                'pain_solved' => 'Generating website screenshots programmatically is complex — browser automation (Puppeteer/Chrome) is flaky, expensive to run, and a maintenance burden. Teams were maintaining custom screenshot servers.',
                'target_customer' => 'SaaS apps, web apps, and developers who need to embed link previews or generate screenshots at scale',
                'pricing_model' => 'usage',
                'key_insight' => 'Developers hate running headless Chrome in production. A cheap, reliable API with a generous free tier converts them quickly.',
                'source_url' => 'https://screenshotone.com',
                'source' => 'maker_interview',
            ],

            [
                'product_name' => 'PDFShift',
                'revenue_milestone' => '€3K MRR',
                'mrr_amount' => 3200,
                'category' => 'developer-tools',
                'description' => 'HTML-to-PDF conversion API. Converts any URL or raw HTML to a pixel-perfect PDF with headers, footers, and custom CSS.',
                'pain_solved' => 'wkhtmltopdf and headless Chrome are unreliable in production, hard to deploy in containers, and break on complex CSS. No reliable managed alternative existed.',
                'target_customer' => 'SaaS apps generating invoices, reports, or documents for end-users',
                'pricing_model' => 'usage',
                'key_insight' => 'Invoice and report generation is a universal SaaS need. A reliable "throw a URL at it, get a PDF back" API has obvious ROI.',
                'source_url' => 'https://pdfshift.io',
                'source' => 'indie_hackers',
            ],

            [
                'product_name' => 'Bannerbear',
                'revenue_milestone' => '$10K MRR',
                'mrr_amount' => 10000,
                'category' => 'developer-tools',
                'description' => 'API and no-code tool to auto-generate social media images, open graph images, and videos from templates. Used for dynamic thumbnails, certificates, and marketing assets.',
                'pain_solved' => 'Creating individual social images or certificates at scale requires a designer or complex Puppeteer scripts. Marketing teams and content pipelines needed a programmatic way to fill image templates.',
                'target_customer' => 'Marketing teams, content publishers, SaaS apps generating personalised certificates or OG images',
                'pricing_model' => 'subscription',
                'key_insight' => 'Non-developers can design templates; developers call the API. Bridging design and code unlocked a market that neither Figma nor ImageMagick served well.',
                'source_url' => 'https://www.bannerbear.com',
                'source' => 'indie_hackers',
            ],

            [
                'product_name' => 'Oh Dear',
                'revenue_milestone' => '$10K MRR',
                'mrr_amount' => 10000,
                'category' => 'developer-tools',
                'description' => 'All-in-one website monitoring: uptime, SSL certificates, broken links, scheduled tasks, DNS, and performance. Built by two well-known Laravel community members.',
                'pain_solved' => 'Uptime monitoring tools (Pingdom, UptimeRobot) only check if a site is up. SSL expiry, broken links, and cron job failures caused silent production incidents.',
                'target_customer' => 'Laravel developers and agencies managing multiple client sites',
                'pricing_model' => 'subscription',
                'key_insight' => 'Community distribution in the Laravel ecosystem meant organic growth. Solving monitoring holistically (not just uptime) justified higher pricing than free alternatives.',
                'source_url' => 'https://ohdear.app',
                'source' => 'maker_interview',
            ],

            [
                'product_name' => 'Flare',
                'revenue_milestone' => '$5K MRR',
                'mrr_amount' => 5000,
                'category' => 'developer-tools',
                'description' => 'Error tracking designed specifically for Laravel. Integrates natively with Laravel\'s exception handling, shows full context (request, session, user, queries), and has clean Blade-aware stack traces.',
                'pain_solved' => 'Sentry and Bugsnag have too much overhead for Laravel devs. Their stack traces lack PHP/Laravel context (e.g. which query caused the error). Setting them up for Laravel took 30+ minutes.',
                'target_customer' => 'Laravel developers and agencies who want error tracking without the enterprise complexity of Sentry',
                'pricing_model' => 'subscription',
                'key_insight' => 'Deep framework-specific integrations beat generic tools for niche developer communities. Building for one ecosystem (Laravel) rather than all languages made the product dramatically better for that audience.',
                'source_url' => 'https://flareapp.io',
                'source' => 'maker_interview',
            ],

            [
                'product_name' => 'Microlink',
                'revenue_milestone' => '$3K MRR',
                'mrr_amount' => 3000,
                'category' => 'developer-tools',
                'description' => 'API for extracting structured data (title, description, image, author) from any URL, generating link previews, taking screenshots, and converting pages to PDFs. Free tier is generous.',
                'pain_solved' => 'Generating link previews (like Slack or Twitter card unfurls) requires parsing Open Graph tags, fetching images, and handling edge cases. Developers were re-implementing this for every project.',
                'target_customer' => 'Developers and SaaS apps that display user-submitted links with rich previews',
                'pricing_model' => 'usage',
                'key_insight' => 'A generous free tier drove word-of-mouth among developers. Unfurl/preview generation is needed by nearly every community or social product.',
                'source_url' => 'https://microlink.io',
                'source' => 'maker_interview',
            ],

            // ── Freelancer / Agency ──────────────────────────────────────────────

            [
                'product_name' => 'SolidGigs',
                'revenue_milestone' => '$3K MRR',
                'mrr_amount' => 3000,
                'category' => 'freelancer',
                'description' => 'Curated freelance job board. A team manually filters hundreds of job postings daily and delivers only the top 1–2% to subscribers via email and a simple dashboard.',
                'pain_solved' => 'Freelancers waste hours sifting through Upwork and job boards to find legitimate, well-paying gigs. The signal-to-noise ratio on generic job boards is terrible.',
                'target_customer' => 'Established freelance developers, designers, and writers who bill $75+/hr and need a steady pipeline of quality leads',
                'pricing_model' => 'subscription',
                'key_insight' => 'Curation is a product. Charging for someone else\'s time (filtering) is a clear ROI for busy professionals. The "I only need one good client a month to pay this off" framing drove conversions.',
                'source_url' => 'https://solidgigs.com',
                'source' => 'indie_hackers',
            ],

            [
                'product_name' => 'Invoice Ninja',
                'revenue_milestone' => '$5K MRR',
                'mrr_amount' => 5000,
                'category' => 'freelancer',
                'description' => 'Open source invoicing and billing platform with a hosted cloud version. Supports recurring invoices, client portals, time tracking, and multiple payment gateways.',
                'pain_solved' => 'FreshBooks and QuickBooks are expensive and bloated for solo freelancers who just need to send invoices and get paid. No good self-hosted option existed.',
                'target_customer' => 'Freelancers and small agencies who want professional invoicing without paying $50+/mo for accounting software',
                'pricing_model' => 'subscription',
                'key_insight' => 'Open source drove adoption (free self-hosted). Cloud version converted self-hosters who didn\'t want to maintain a server. The network effect of "my accountant already has access" created sticky accounts.',
                'source_url' => 'https://invoiceninja.com',
                'source' => 'indie_hackers',
            ],

            [
                'product_name' => 'ManyRequests',
                'revenue_milestone' => '$10K MRR',
                'mrr_amount' => 10000,
                'category' => 'freelancer',
                'description' => 'White-label client portal for agencies and productised service businesses. Clients submit requests, agencies manage the queue, deliver files, and communicate — all in one branded portal.',
                'pain_solved' => 'Agencies juggle client communication across email, Slack, Trello, Google Drive, and Loom. Clients complain about losing track of requests. There was no single tool built for the agency ↔ client workflow.',
                'target_customer' => 'Productised service agencies (design, dev, content) with 5–50 active client accounts',
                'pricing_model' => 'subscription',
                'key_insight' => '"Productised services" is a growing category. Founders who sell subscription-based creative work (unlimited design, dev retainers) needed a portal purpose-built for that model, not a generic PM tool.',
                'source_url' => 'https://manyrequests.com',
                'source' => 'indie_hackers',
            ],

            [
                'product_name' => 'Copilot',
                'revenue_milestone' => '$1M ARR',
                'mrr_amount' => 83333,
                'category' => 'freelancer',
                'description' => 'Modern client portal for freelancers and boutique agencies. Clients get a single branded hub for contracts, invoices, messages, files, and intake forms.',
                'pain_solved' => 'Client communication is scattered across email, DocuSign, PayPal, Dropbox, and Slack. Every new client onboarding is a manual process involving 5+ tools.',
                'target_customer' => 'Solo consultants and small agencies (1–10 people) who want to look professional and reduce admin overhead',
                'pricing_model' => 'subscription',
                'key_insight' => 'A polished, branded experience makes freelancers look more professional. Clients who use the portal pay faster and have fewer questions — the ROI for the freelancer is clear.',
                'source_url' => 'https://copilot.app',
                'source' => 'twitter',
            ],

            [
                'product_name' => 'Wethos',
                'revenue_milestone' => '$2K MRR',
                'mrr_amount' => 2000,
                'category' => 'freelancer',
                'description' => 'Scope and rate library for creative freelancers and agencies. Helps set rates, generate scopes of work from templates, and send proposals with benchmarked pricing.',
                'pain_solved' => 'Freelancers chronically underprice because they don\'t know market rates and spend hours writing custom scopes from scratch. Proposals are guesswork.',
                'target_customer' => 'Freelance designers, copywriters, and strategists who need to write proposals but lack confidence in their pricing',
                'pricing_model' => 'subscription',
                'key_insight' => 'Data-driven confidence around pricing is valuable enough to pay for. The "community benchmark" angle (what are others charging?) provided the "aha moment" that drove signups.',
                'source_url' => 'https://wethos.co',
                'source' => 'maker_interview',
            ],

            // ── Analytics / Monitoring ───────────────────────────────────────────

            [
                'product_name' => 'Plausible Analytics',
                'revenue_milestone' => '$10K MRR',
                'mrr_amount' => 10000,
                'category' => 'analytics',
                'description' => 'Lightweight, privacy-first web analytics that doesn\'t use cookies and is GDPR compliant by default. Script is under 1KB. Dashboard shows the most useful stats without the noise.',
                'pain_solved' => 'Google Analytics 4 is complex, cookie-dependent, and a GDPR liability. Privacy-conscious site owners wanted a simple analytics tool that didn\'t require a cookie banner.',
                'target_customer' => 'Privacy-conscious developers, indie makers, and site owners in GDPR jurisdictions',
                'pricing_model' => 'subscription',
                'key_insight' => 'GDPR anxiety created a real market for cookie-less alternatives. A clean, simple dashboard that showed what users actually care about (traffic sources, top pages, geography) vs GA\'s 200 metrics was a strong differentiator.',
                'source_url' => 'https://plausible.io',
                'source' => 'indie_hackers',
            ],

            [
                'product_name' => 'Fathom Analytics',
                'revenue_milestone' => '$7K MRR',
                'mrr_amount' => 7000,
                'category' => 'analytics',
                'description' => 'Simple, privacy-first website analytics with no tracking of personal data. Single-page dashboard, fast load times, and EU-based data infrastructure.',
                'pain_solved' => 'GA4\'s complexity and privacy implications drove site owners to look for alternatives. Existing alternatives were either ugly, slow, or still used cookies.',
                'target_customer' => 'Content creators, bloggers, small business owners, and developers who want analytics without the privacy overhead',
                'pricing_model' => 'subscription',
                'key_insight' => 'Paul Jarvis and Jack Ellis built in public and had existing audiences. Simplicity was the differentiator: "see your traffic in 5 seconds, not 50 tabs."',
                'source_url' => 'https://usefathom.com',
                'source' => 'indie_hackers',
            ],

            [
                'product_name' => 'Better Uptime',
                'revenue_milestone' => '$10K MRR',
                'mrr_amount' => 10000,
                'category' => 'developer-tools',
                'description' => 'Uptime monitoring combined with on-call scheduling and incident management. Monitors HTTP, keyword, ping, TCP. Alerts via phone call, SMS, Slack, and email.',
                'pain_solved' => 'Uptime monitoring (PagerDuty, OpsGenie) is priced for enterprises. Small teams needed phone call alerts and on-call rotation without paying $500/mo.',
                'target_customer' => 'Small engineering teams, agencies, and SaaS founders who need reliable on-call alerting but can\'t justify enterprise monitoring pricing',
                'pricing_model' => 'subscription',
                'key_insight' => 'Phone call alerts are the killer feature — developers sleep through Slack notifications. Including on-call schedules in the base plan (vs as an add-on) undercut enterprise tools dramatically.',
                'source_url' => 'https://betteruptime.com',
                'source' => 'indie_hackers',
            ],

            // ── SaaS Metrics / Revenue ───────────────────────────────────────────

            [
                'product_name' => 'Baremetrics',
                'revenue_milestone' => '$1.5M ARR',
                'mrr_amount' => 125000,
                'category' => 'saas-metrics',
                'description' => 'MRR analytics dashboard for Stripe. Shows MRR, ARR, LTV, churn, ARPU, and cohort analysis — all pulled automatically from Stripe data.',
                'pain_solved' => 'Stripe\'s native reporting is limited. Founders were building their own MRR dashboards in spreadsheets or paying for enterprise analytics tools to see basic SaaS metrics.',
                'target_customer' => 'SaaS founders and subscription businesses on Stripe who want real-time revenue metrics without building them',
                'pricing_model' => 'subscription',
                'key_insight' => 'Josh Pigford built in public from day one, sharing monthly revenue transparently. This created trust and social proof simultaneously. The "I want that dashboard" reaction to his public metrics page drove virality.',
                'source_url' => 'https://baremetrics.com',
                'source' => 'indie_hackers',
            ],

            [
                'product_name' => 'ChurnBuster',
                'revenue_milestone' => '$1K MRR',
                'mrr_amount' => 1000,
                'category' => 'saas-metrics',
                'description' => 'Dunning management for Stripe subscriptions. Automatically retries failed payments, sends smart recovery emails, and pauses vs cancels subscriptions to recover churned revenue.',
                'pain_solved' => 'Failed payments (passive churn) can account for 20–40% of total churn. Stripe\'s built-in smart retries are basic. SaaS founders were losing thousands per month to fixable payment failures.',
                'target_customer' => 'B2C and SMB SaaS companies on Stripe with 500+ subscribers',
                'pricing_model' => 'subscription',
                'key_insight' => 'Recovery revenue is easy to quantify — customers could see exactly how much ChurnBuster had recovered, making the ROI calculation trivial. "We recovered $8K last month" is a compelling sales pitch.',
                'source_url' => 'https://churnbuster.io',
                'source' => 'indie_hackers',
            ],

            // ── Email / Newsletter ────────────────────────────────────────────────

            [
                'product_name' => 'Buttondown',
                'revenue_milestone' => '$10K MRR',
                'mrr_amount' => 10000,
                'category' => 'email-marketing',
                'description' => 'Simple newsletter tool for writers and developers. Markdown-first, no visual bloat. Includes subscriber management, automations, paid subscriptions, and a clean API.',
                'pain_solved' => 'Mailchimp and ConvertKit are bloated for writers who just want to send a well-formatted email to a list. Substack takes a revenue cut. Nothing felt built for developers who wanted control.',
                'target_customer' => 'Independent writers, developers, and technical bloggers who care about ownership and don\'t need a visual email builder',
                'pricing_model' => 'subscription',
                'key_insight' => 'Markdown-first resonated with the developer audience who hate drag-and-drop editors. Charging per subscriber (not per email) removed anxiety about sending. Substack\'s cut created refugees.',
                'source_url' => 'https://buttondown.email',
                'source' => 'indie_hackers',
            ],

            [
                'product_name' => 'EmailOctopus',
                'revenue_milestone' => '$100K MRR',
                'mrr_amount' => 100000,
                'category' => 'email-marketing',
                'description' => 'Email marketing platform focused on affordability. Uses Amazon SES under the hood for sending, dramatically reducing per-email costs vs Mailchimp.',
                'pain_solved' => 'Mailchimp is expensive at scale. A list of 50K subscribers costs $300+/mo. For bootstrapped companies and charities with large lists, this is prohibitive.',
                'target_customer' => 'Bootstrapped SaaS companies, creators, and non-profits with large email lists who are paying too much for Mailchimp',
                'pricing_model' => 'subscription',
                'key_insight' => 'SES pricing arbitrage: charge $20/mo for what Mailchimp charges $250/mo. The cost savings sell themselves. Growing from the bottom of the market is a defensible wedge.',
                'source_url' => 'https://emailoctopus.com',
                'source' => 'indie_hackers',
            ],

            // ── Scheduling / Productivity ────────────────────────────────────────

            [
                'product_name' => 'SavvyCal',
                'revenue_milestone' => '$10K MRR',
                'mrr_amount' => 10000,
                'category' => 'productivity',
                'description' => 'Calendar scheduling app with a key difference: recipients can overlay their own calendar before picking a time slot, reducing back-and-forth and double-booking.',
                'pain_solved' => 'Calendly shows available slots but doesn\'t help the recipient avoid conflicts — they still have to switch to their own calendar to cross-reference. Meetings still get double-booked.',
                'target_customer' => 'Consultants, sales reps, and knowledge workers who schedule 5+ external meetings per week',
                'pricing_model' => 'subscription',
                'key_insight' => 'Derrick Reimer (ex-Basecamp, co-creator of Drip) built in public with a strong existing audience. The "overlay your calendar" feature was a genuinely better UX that Calendly hadn\'t matched.',
                'source_url' => 'https://savvycal.com',
                'source' => 'indie_hackers',
            ],

            [
                'product_name' => 'Sunsama',
                'revenue_milestone' => '$1M ARR',
                'mrr_amount' => 83333,
                'category' => 'productivity',
                'description' => 'Daily planner for knowledge workers. Pulls tasks from Asana, Linear, GitHub, Todoist, and Gmail into one focused daily plan. Enforces time-boxing and a shutdown ritual.',
                'pain_solved' => 'Knowledge workers have tasks spread across 5+ tools and no single view of what to work on today. They overcommit daily because they don\'t time-box. End-of-day feels unfinished.',
                'target_customer' => 'Managers, founders, and individual contributors who juggle work across multiple tools and struggle to focus',
                'pricing_model' => 'subscription',
                'key_insight' => '"Intentional work" resonated strongly with burnout-aware professionals. $20/mo felt cheap vs losing hours of productivity to task-switching. The opinionated daily workflow (morning plan, shutdown ritual) created habits, which created retention.',
                'source_url' => 'https://sunsama.com',
                'source' => 'twitter',
            ],

            // ── Forms / Surveys ──────────────────────────────────────────────────

            [
                'product_name' => 'Tally',
                'revenue_milestone' => '$3K MRR',
                'mrr_amount' => 3000,
                'category' => 'productivity',
                'description' => 'Free Typeform alternative with a clean block-based editor. No response limits on the free plan. Paid plan adds logic, custom domains, and team features.',
                'pain_solved' => 'Typeform limits free responses to 10/month, forcing upgrade. Most form tools charge per response or have restrictive free plans. Small teams needed unlimited forms without paying $50/mo.',
                'target_customer' => 'Startups, students, non-profits, and indie makers who need forms but can\'t justify Typeform pricing for low-volume use',
                'pricing_model' => 'subscription',
                'key_insight' => 'No-response-limit free tier went viral on Indie Hackers and Twitter ("Typeform but free"). High adoption created a large free funnel of users who convert when they hit team or logic features.',
                'source_url' => 'https://tally.so',
                'source' => 'indie_hackers',
            ],

            // ── Website Building ─────────────────────────────────────────────────

            [
                'product_name' => 'Carrd',
                'revenue_milestone' => '$1M ARR',
                'mrr_amount' => 83333,
                'category' => 'website-builder',
                'description' => 'Simple one-page website builder. Designed for landing pages, portfolios, and simple sites. Free tier is genuinely useful. Paid plans unlock custom domains and forms.',
                'pain_solved' => 'Squarespace and Wix are overkill and expensive for a simple one-page site. GitHub Pages requires coding. Nothing existed that was both free-to-try and genuinely simple.',
                'target_customer' => 'Creatives, freelancers, and indie makers who need a fast, clean one-page site with no coding required',
                'pricing_model' => 'subscription',
                'key_insight' => 'AJ built it as a side project, kept it lean ($19/yr), and grew entirely by word-of-mouth. The "one-page only" constraint was a feature — it forced simplicity and made the product fast to build a site with.',
                'source_url' => 'https://carrd.co',
                'source' => 'indie_hackers',
            ],

            [
                'product_name' => 'Unicorn Platform',
                'revenue_milestone' => '$3K MRR',
                'mrr_amount' => 3000,
                'category' => 'website-builder',
                'description' => 'No-code landing page builder specifically designed for SaaS startups. Includes SaaS-specific blocks (pricing tables, feature sections, testimonials, FAQs) optimised for conversions.',
                'pain_solved' => 'Generic website builders (Wix, Squarespace) require heavy customisation to look like a professional SaaS landing page. Developers spend days building the same sections from scratch for each product.',
                'target_customer' => 'Indie hackers and early-stage SaaS founders who need a professional marketing site in hours, not weeks',
                'pricing_model' => 'subscription',
                'key_insight' => 'The SaaS-specific vertical focus meant blocks looked immediately right for the audience. Targeted distribution on Indie Hackers and Product Hunt drove the initial user base.',
                'source_url' => 'https://unicornplatform.com',
                'source' => 'indie_hackers',
            ],

            // ── Content / SEO ────────────────────────────────────────────────────

            [
                'product_name' => 'Beehiiv',
                'revenue_milestone' => '$1M ARR',
                'mrr_amount' => 83333,
                'category' => 'email-marketing',
                'description' => 'Newsletter platform with built-in monetisation (paid subscriptions, ad network), SEO-optimised web hosting, referral programmes, and advanced analytics.',
                'pain_solved' => 'Substack takes a 10% revenue cut. ConvertKit lacks a native ad network and referral system. Newsletter operators needed a platform built for media business models, not just email.',
                'target_customer' => 'Newsletter operators and media entrepreneurs who want to monetise their audience without a platform taking a percentage cut',
                'pricing_model' => 'subscription',
                'key_insight' => 'Founding team came from Morning Brew and understood the newsletter business from both sides. The referral programme became a viral growth mechanism for their users (and indirectly for Beehiiv).',
                'source_url' => 'https://beehiiv.com',
                'source' => 'twitter',
            ],

            // ── No-code / Automation ─────────────────────────────────────────────

            [
                'product_name' => 'n8n',
                'revenue_milestone' => '$1M ARR',
                'mrr_amount' => 83333,
                'category' => 'automation',
                'description' => 'Self-hostable workflow automation platform (Zapier alternative). Open source with a cloud version. 400+ integrations, custom code nodes, and a visual workflow editor.',
                'pain_solved' => 'Zapier is expensive at scale and doesn\'t allow custom code or self-hosting. Tech teams building internal automation needed flexibility and data privacy guarantees.',
                'target_customer' => 'Technical teams and developers who need flexible automation with custom logic, self-hosting options, or privacy requirements',
                'pricing_model' => 'subscription',
                'key_insight' => 'Open source drove adoption — 50K GitHub stars meant free distribution. Self-hosting is the differentiator vs Zapier. Cloud version captures teams who try self-hosted and want managed.',
                'source_url' => 'https://n8n.io',
                'source' => 'twitter',
            ],

            [
                'product_name' => 'Pipedream',
                'revenue_milestone' => '$1M ARR',
                'mrr_amount' => 83333,
                'category' => 'automation',
                'description' => 'Event-driven integration platform. Developers write JavaScript/Python in serverless workflows triggered by webhooks, APIs, or schedules. 700+ pre-built integrations.',
                'pain_solved' => 'Zapier forces non-code workflows that hit walls fast. Lambda/Cloud Functions have cold start problems and require deployment pipelines. Developers needed code-first automation with managed infrastructure.',
                'target_customer' => 'Developers and technical founders who need flexible, code-based automation without managing infrastructure',
                'pricing_model' => 'usage',
                'key_insight' => 'Code-first beats no-code for technical users. Free tier with generous credits (millions of compute credits) let developers try and build complex automations before paying.',
                'source_url' => 'https://pipedream.com',
                'source' => 'twitter',
            ],

            // ── Documentation ────────────────────────────────────────────────────

            [
                'product_name' => 'Mintlify',
                'revenue_milestone' => '$1M ARR',
                'mrr_amount' => 83333,
                'category' => 'developer-tools',
                'description' => 'Documentation platform purpose-built for developer docs. Beautiful out-of-the-box, built-in search, API reference generation from OpenAPI specs, and React component support.',
                'pain_solved' => 'ReadTheDocs is ugly. GitBook is a generic wiki. Docusaurus requires frontend knowledge to customise. Devtool companies were spending weeks making docs look professional.',
                'target_customer' => 'Developer tool companies, API providers, and startups that ship SDKs and need great-looking technical documentation quickly',
                'pricing_model' => 'subscription',
                'key_insight' => 'Beautiful, zero-config docs were a status symbol for developer-facing companies — it signalled professionalism. Y Combinator companies were a concentrated early adopter base that created powerful word-of-mouth.',
                'source_url' => 'https://mintlify.com',
                'source' => 'twitter',
            ],

            // ── Server Management ────────────────────────────────────────────────

            [
                'product_name' => 'Ploi',
                'revenue_milestone' => '$20K MRR',
                'mrr_amount' => 20000,
                'category' => 'developer-tools',
                'description' => 'Server management panel for provisioning and deploying PHP/Laravel applications on DigitalOcean, Hetzner, and other cloud providers. Handles Nginx, SSL, databases, and deployments.',
                'pain_solved' => 'Laravel Forge was the only option but at $19/mo felt overpriced for small teams. Ploi launched at $8/mo. Forge also didn\'t support Hetzner (significantly cheaper VPS).',
                'target_customer' => 'Laravel/PHP developers who self-host applications on cloud VPS providers and want to avoid manual server configuration',
                'pricing_model' => 'subscription',
                'key_insight' => 'Launching cheaper than the market leader on a platform (Hetzner) the leader didn\'t support created a clear wedge. Laravel community distribution through social and forums accelerated adoption.',
                'source_url' => 'https://ploi.io',
                'source' => 'indie_hackers',
            ],

            // ── Client Reporting ─────────────────────────────────────────────────

            [
                'product_name' => 'AgencyAnalytics',
                'revenue_milestone' => '$5M ARR',
                'mrr_amount' => 416667,
                'category' => 'freelancer',
                'description' => 'Client reporting platform for digital marketing agencies. Pulls data from SEO tools, Google Ads, Facebook Ads, and social platforms into white-label client dashboards.',
                'pain_solved' => 'Agencies manually compiled client reports in spreadsheets or PowerPoint. Pulling data from 10+ platforms and formatting it took 4–8 hours per client per month.',
                'target_customer' => 'Digital marketing agencies managing 5–100 client accounts who need to automate monthly reporting',
                'pricing_model' => 'subscription',
                'key_insight' => 'The "8 hours to 30 minutes" time savings justification made pricing easy. White-labelling added agency branding, which made them look more professional and increased retention (switching costs).',
                'source_url' => 'https://agencyanalytics.com',
                'source' => 'maker_interview',
            ],

            // ── HR / Payroll ─────────────────────────────────────────────────────

            [
                'product_name' => 'Gusto',
                'revenue_milestone' => '$1M ARR',
                'mrr_amount' => 83333,
                'category' => 'business-ops',
                'description' => 'Payroll, benefits, and HR platform for small businesses. Automates payroll tax filings, direct deposits, and employee onboarding. More polished UX than ADP/Paychex.',
                'pain_solved' => 'Small business payroll is unnecessarily complex. ADP and Paychex have clunky interfaces, poor support, and pricing built for enterprise. Payroll mistakes have legal consequences.',
                'target_customer' => 'Small businesses (2–50 employees) who want reliable, automated payroll without the complexity of legacy providers',
                'pricing_model' => 'subscription',
                'key_insight' => 'Making payroll a delightful experience (vs anxious one) was the insight. Simplicity and a clean UX in a market with terrible software created a strong "finally!" moment for small business owners.',
                'source_url' => 'https://gusto.com',
                'source' => 'maker_interview',
            ],

        ];

        foreach ($patterns as $pattern) {
            SuccessPattern::create($pattern);
        }

        $this->command->info('Seeded ' . count($patterns) . ' success patterns.');
    }
}
