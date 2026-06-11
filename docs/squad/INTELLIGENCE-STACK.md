# Intelligence Stack — signal gathering + AI-SEO/GEO (compiled 2026-06-11)

> Two-agent deep research (Apify/signals/analytics; AI-SEO/GEO). Figures latest disclosed,
> mostly 2025–2026. Full source URLs in agent reports; key sources inline.

## A. Signal-gathering stack (agent-operated market intelligence)

**Picks (SMB fleet floor ≈ $234/mo; growth ≈ $470/mo):**

| Layer | Pick | Cost | MCP-native? |
|---|---|---|---|
| SERP/discovery | Serper | $40/mo, 500k queries | LangChain tool / MCP marketplaces |
| Deep extraction | **Apify Starter** | $29/mo | **YES — `mcp.apify.com`, 38k actors as dynamic MCP tools**, Streamable HTTP (SSE dropped Apr 2026) |
| JS crawl | Firecrawl Hobby | $16/mo | YES — official MCP server |
| Social listening | Mention Pro (API tier) OR Apify social actors | $149/mo or PAYG $0.40–1.70/1k | partial |
| Analytics/obs | PostHog free | $0 (1M events + 100k LLM-obs events) | already in MCPWP |

Key actor prices (Apify): Google Maps+reviews $4/1k, Instagram $1.50/1k, TikTok $1.70/1k, X $0.40/1k, Meta Ad Library $0.0015/ad.
**Apify agentic payments** (x402/USDC, Skyfire) → pairs with mupot `cap_micro_usd`: capped autonomous signal-buying.
Search-API alternatives if needed: Exa (MCP-native, semantic, $7/1k), Tavily (agent-optimized), Brave ($5/mo flat).

**GHL has an official MCP server** (~21 tools: contacts, pipeline, conversations, payments). Gap: no reporting API — agents reconstruct analytics from raw pulls (pipeline → LLM synth → push). Funnel arm is already agent-native.

**The signal loop** (proven market pattern): trigger → scrape → synthesize → act. Vendors doing it: Autobound (700+ signal types), Kadoa ("generate scraper once, run cheap, self-heal"). Our version = gated + WP-native. Fits dermalounge competitor-watch, viamar freight-lead signals.

**Legal rails (design into every signal tool):**
- DMCA §1201 circumvention = the 2026 battlefront (Reddit v Perplexity pending). Never defeat Turnstile/CAPTCHA; human-rate pace (1 req/5–10s on protected sites); managed residential proxies, never our own/cloud IPs.
- GDPR/CCPA: aggregate only, no scraped PII into CRMs; business profiles/reviews low-risk, private individuals high-risk.
- Prefer official APIs (Google Business Profile, Meta Graph) where they exist = zero ban risk.

## B. AI-SEO / GEO — the territory thesis, with evidence

**State of AI search (Jun 2026):**
- AI Overviews on **48% of Google queries** (up from 34.5% Dec 2025). Zero-click rose 54%→72%. AIO cuts outbound clicks −38% on triggered queries.
- **Being cited in AIO = +120% clicks per impression vs not cited** (Seer, 5.47M queries). Citation is the new rank-1.
- AI referral traffic still small (ChatGPT ~0.20% of all referrals) but +85% since Jan 2026. Big-4 (ChatGPT/Claude/Gemini/Perplexity) = 99% of AI referrals. Only 11% domain overlap between ChatGPT and Perplexity citations — platform-specific game.

**What measurably wins citations (ranked by evidence):**
| Signal | Evidence | Number |
|---|---|---|
| Earned media / digital PR | HIGH | 84% of all AI citations (Muck Rack 25M links); 3rd-party distribution = 239–325% lift |
| Content freshness | HIGH | <30-day content cited 3.2×; 50% of cited content <13 weeks old |
| Front-loaded answers + tables + bullets | HIGH | 44% of citations from first 30% of article; comparison tables 47% higher; bullets 2–3× paragraphs |
| llms.txt | MED | Anthropic+Perplexity confirmed; modest uplift; 10% adoption = early window |
| Schema (FAQ/Speakable/Article/HowTo) | LOW-MED | 71% of ChatGPT-cited pages have it; correlation not causation |
| Reddit/Quora niche presence | MED (volatile) | 4× citation rate for big-footprint domains; platform-swingy |
| Niche coherence | HIGH (Google survival) | Feb-2026 Discover penalizes off-niche; specialists +5–35% |

**Google policy line 2026 (validates STRATEGY's coherence thesis):**
- Scaled-content-abuse: "many pages generated to manipulate rankings without helping users." March-2026 core update → sites with hundreds of ungoverned AI pages saw **50–80% traffic drops in 14 days**.
- Firefly enforcement (`QualityCopiaFireflySiteSignal`): tracks new-pages-per-30-days spike × quality ratio × NavBoost clicks → **site-wide** demotion. This is exactly the slop our coherence gate burns. Coherence = literal Google safe-harbor.
- Feb-2026 Discover: rewards niche expertise, topic clusters, author bylines, **1200px+ images (+45% CTR)**, headline-content alignment. (Our featured-image pass + niche lane = on-policy.)

**Crawler economics:** Training = 80% of AI bot traffic. ClaudeBot crawl:referral ≈ 24,000:1, GPTBot 1,276:1, PerplexityBot 111:1 (best). Cloudflare pay-per-crawl ($0.01–0.05/req, HTTP 402 for non-payers) in private beta, ~1M domains. Publisher leverage forming.

**WP AI-SEO competitive landscape:**
- Rank Math: llms.txt + AI-traffic tracker ("still maturing"). Yoast: llms.txt "extremely simplified". AIOSEO: third-party llms.txt only.
- **RankReady (POSIMYTH, free)** is the threat to watch: full llms.txt, markdown endpoints, AI crawler logs (31 bots), citation leaderboard, AI referral analytics, per-post readiness score (22 signals), auto schema, AI summaries, **WebMCP server**. Free; LLM costs direct. Merges with Yoast/RankMath.

**Gaps NO WP plugin fills (our AI-SEO product wedge):**
1. **Competitive AI share-of-voice** — % of AI answers in your category mentioning you vs competitors, per platform. Standalone SaaS (Profound $499, Otterly $29–489, Peec €89–499) exist; none embedded in WP.
2. **Citation-decay alerts + freshness re-optimization queue** — correlate page age with citation probability, surface prioritized re-opt list. Highest-evidence signal, zero plugins operationalize it.
3. **Earned-media citation pipeline** — track which 3rd-party domains cite you in AI answers + competitor gap. 84% of citations are earned; nobody maps off-site footprint to AI appearances.

GEO tooling raised $300M+ summer-2025→spring-2026 (Profound $155M/$1B val, Peec $21M Series A). Category is hot and SaaS-only — WP-embedded + agent-operated is open.

## C. What this changes for MCPWP

1. **GEO is a product line, not a feature** — the three gaps above = a differentiated AI-SEO suite no WP plugin or standalone SaaS occupies (WP-native + agent-operated + gated). Highest-evidence first: freshness re-optimization queue (we already have `wp_get_signals` surfacing stale content — extend it with citation-decay scoring).
2. **The coherence thesis is now externally proven** — Firefly + Discover = Google paying for exactly what our gate enforces. "Coherence is Google's safe harbor" is a sourced claim, not a slogan. Marketing line.
3. **Signal stack = the dermalounge/viamar loop input** — competitor + review + ad-library monitoring via Apify MCP, gated, capped. Build as agent-callable tools behind approval.
4. **RankReady is the benchmark to beat** — free, WebMCP, comprehensive on-page GEO. We win on the off-page/competitive/agent-operated axis they don't touch, + the operator surface (Control Room).
5. **Earned-media reality reframes content strategy** — our 52 owned posts get 8% of the citation a third-party placement gets. The Prefrontal Club community + digital-PR motion matters more than volume on-site. Distribution > publishing.
