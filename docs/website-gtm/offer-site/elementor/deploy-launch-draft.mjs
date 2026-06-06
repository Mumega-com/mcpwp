import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const PAGE_TITLE = "MCPWP Launch Elementor Draft";
const PAGE_SLUG = "mcpwp-launch-elementor-draft";
const CSS_MARKER = "MCPWP launch draft from Claude offer design - 2026-06-04";
const CTA_FIX_MARKER = "MCPWP launch draft CTA contrast fix v2 - 2026-06-04";
const EXPANDED_BLOCKS_MARKER = "MCPWP launch expanded blocks CSS - 2026-06-04";

const deploy = process.argv.includes("--deploy");

const css = `
/* ${CSS_MARKER} */
.mcpwp-launch{--mcp-bg:#050812;--mcp-surface:#101827;--mcp-line:#243149;--mcp-text:#f7f9ff;--mcp-muted:#98a6bd;--mcp-faint:#667389;--mcp-blue:#4d86ff;--mcp-cyan:#4be3c2;--mcp-radius:18px;--mcp-radius-lg:28px;--mcp-max:1180px;background:radial-gradient(circle at 20% 0%,rgba(77,134,255,.23),transparent 30%),radial-gradient(circle at 80% 10%,rgba(75,227,194,.12),transparent 30%),linear-gradient(180deg,var(--mcp-bg),#070b14 62%,#050812);color:var(--mcp-text);font-family:"IBM Plex Sans","Space Grotesk",system-ui,sans-serif;overflow:hidden}.mcpwp-launch *{box-sizing:border-box}.mcpwp-wrap{max-width:var(--mcp-max);margin:0 auto;padding:0 28px;position:relative}.mcpwp-section{padding:92px 0;border-top:1px solid rgba(255,255,255,.06)}.mcpwp-eyebrow{display:inline-flex;gap:10px;align-items:center;color:var(--mcp-cyan);font:700 12px/1 "JetBrains Mono",monospace;letter-spacing:.16em;text-transform:uppercase}.mcpwp-eyebrow:before{content:"";width:24px;height:1px;background:var(--mcp-cyan);opacity:.65}.mcpwp-hero{padding:118px 0 78px}.mcpwp-badge{display:inline-flex;align-items:center;gap:10px;padding:8px 14px;border:1px solid rgba(77,134,255,.35);border-radius:999px;background:rgba(77,134,255,.1);color:#bdd2ff;font:600 13px/1.2 "JetBrains Mono",monospace}.mcpwp-badge b{color:#fff}.mcpwp-hero h1{max-width:900px;margin:28px 0 22px;font-family:"Space Grotesk",system-ui,sans-serif;font-size:clamp(48px,8vw,92px);line-height:.95;letter-spacing:-.055em}.mcpwp-grad{background:linear-gradient(100deg,#fff 22%,#6ea0ff 64%,#4be3c2);-webkit-background-clip:text;background-clip:text;color:transparent}.mcpwp-sub{max-width:810px;color:var(--mcp-muted);font-size:clamp(20px,2.3vw,27px);line-height:1.45}.mcpwp-actions{display:flex;gap:14px;flex-wrap:wrap;margin-top:34px}.mcpwp-btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:16px 24px;border-radius:14px;border:1px solid rgba(255,255,255,.14);font-weight:800;text-decoration:none;color:#fff;background:rgba(255,255,255,.06)}.mcpwp-btn-primary{background:linear-gradient(135deg,var(--mcp-blue),#2f6ef7);box-shadow:0 18px 52px -20px rgba(77,134,255,.8);border-color:#78a5ff}.mcpwp-note{margin-top:14px;color:var(--mcp-faint);font:600 13px/1.4 "JetBrains Mono",monospace}.mcpwp-demo{margin-top:56px;border:1px solid rgba(255,255,255,.12);border-radius:var(--mcp-radius-lg);background:linear-gradient(180deg,rgba(16,24,39,.92),rgba(7,11,20,.92));box-shadow:0 30px 90px -40px #000;overflow:hidden}.mcpwp-demo-top{height:46px;display:flex;align-items:center;gap:8px;padding:0 18px;border-bottom:1px solid rgba(255,255,255,.08);color:var(--mcp-faint);font:600 12px "JetBrains Mono",monospace}.mcpwp-dot{width:11px;height:11px;border-radius:50%;background:#ff5f56}.mcpwp-dot:nth-child(2){background:#ffbd2e}.mcpwp-dot:nth-child(3){background:#27c93f}.mcpwp-terminal{display:grid;grid-template-columns:1fr 1fr}.mcpwp-pane{padding:28px;border-right:1px solid rgba(255,255,255,.08)}.mcpwp-pane:last-child{border-right:0;background:rgba(77,134,255,.05)}.mcpwp-line{display:block;margin:0 0 12px;color:#c8d6ec;font:500 14px/1.65 "JetBrains Mono",monospace}.mcpwp-line strong{color:var(--mcp-cyan)}.mcpwp-head{max-width:720px;margin-bottom:44px}.mcpwp-head h2{margin:18px 0 14px;font-family:"Space Grotesk",system-ui,sans-serif;font-size:clamp(34px,5vw,56px);line-height:1;letter-spacing:-.04em}.mcpwp-head p{color:var(--mcp-muted);font-size:19px;line-height:1.6}.mcpwp-grid,.mcpwp-flow,.mcpwp-pricing{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.mcpwp-card{padding:24px;border:1px solid rgba(255,255,255,.1);border-radius:var(--mcp-radius);background:linear-gradient(180deg,rgba(255,255,255,.055),rgba(255,255,255,.025));box-shadow:inset 0 1px 0 rgba(255,255,255,.05)}.mcpwp-card h3{margin:0 0 10px;font-family:"Space Grotesk",system-ui,sans-serif;font-size:24px;letter-spacing:-.03em}.mcpwp-card p{margin:0;color:var(--mcp-muted);font-size:16px;line-height:1.55}.mcpwp-kicker{display:block;margin-bottom:18px;color:var(--mcp-blue);font:800 12px "JetBrains Mono",monospace;text-transform:uppercase;letter-spacing:.14em}.mcpwp-step-num{display:inline-flex;width:38px;height:38px;align-items:center;justify-content:center;border-radius:12px;background:rgba(75,227,194,.12);color:var(--mcp-cyan);font:800 13px "JetBrains Mono",monospace;margin-bottom:22px}.mcpwp-tools{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.mcpwp-chip{padding:16px;border:1px solid rgba(255,255,255,.1);border-radius:14px;background:rgba(255,255,255,.04);color:#d8e2f4;font:700 13px "JetBrains Mono",monospace}.mcpwp-chip span{display:block;color:var(--mcp-muted);font:500 12px/1.45 "IBM Plex Sans",system-ui,sans-serif;margin-top:7px}.mcpwp-plan{padding:30px}.mcpwp-plan-featured{border-color:rgba(77,134,255,.55);box-shadow:0 0 0 1px rgba(77,134,255,.15),0 28px 70px -38px rgba(77,134,255,.9)}.mcpwp-price{font-family:"Space Grotesk",system-ui,sans-serif;font-size:42px;letter-spacing:-.04em;margin:16px 0 4px}.mcpwp-plan ul{padding:0;margin:22px 0 0;list-style:none}.mcpwp-plan li{margin:12px 0;color:var(--mcp-muted)}.mcpwp-plan li:before{content:"✓";color:var(--mcp-cyan);font-weight:900;margin-right:10px}.mcpwp-final{text-align:center;padding-bottom:120px}.mcpwp-final .mcpwp-head{margin-left:auto;margin-right:auto}.mcpwp-code{margin:28px auto 0;max-width:760px;text-align:left;padding:18px 20px;border-radius:16px;background:#070b12;border:1px solid rgba(255,255,255,.12);color:#d5e4ff;font:600 13px/1.6 "JetBrains Mono",monospace;overflow:auto}@media(max-width:900px){.mcpwp-terminal,.mcpwp-grid,.mcpwp-flow,.mcpwp-pricing{grid-template-columns:1fr}.mcpwp-tools{grid-template-columns:repeat(2,1fr)}.mcpwp-pane{border-right:0;border-bottom:1px solid rgba(255,255,255,.08)}}@media(max-width:560px){.mcpwp-wrap{padding:0 18px}.mcpwp-hero{padding-top:78px}.mcpwp-tools{grid-template-columns:1fr}.mcpwp-btn{width:100%}}
.mcpwp-launch a.mcpwp-btn,.mcpwp-launch a.mcpwp-btn:visited{color:#fff!important;text-decoration:none!important}.mcpwp-launch a.mcpwp-btn-primary,.mcpwp-launch a.mcpwp-btn-primary:visited{background:linear-gradient(135deg,var(--mcp-blue),#2f6ef7)!important;color:#fff!important;border-color:#78a5ff!important}.mcpwp-launch a.mcpwp-btn:not(.mcpwp-btn-primary){background:rgba(255,255,255,.07)!important;color:#f7f9ff!important}
`;

const ctaFixCss = `
/* ${CTA_FIX_MARKER} */
.mcpwp-launch a.mcpwp-btn,.mcpwp-launch a.mcpwp-btn:visited{color:#fff!important;text-decoration:none!important}.mcpwp-launch a.mcpwp-btn-primary,.mcpwp-launch a.mcpwp-btn-primary:visited{background:linear-gradient(135deg,var(--mcp-blue),#2f6ef7)!important;color:#fff!important;border-color:#78a5ff!important}.mcpwp-launch a.mcpwp-btn:not(.mcpwp-btn-primary){background:rgba(255,255,255,.07)!important;color:#f7f9ff!important}
`;

const expandedBlocksCss = `
/* ${EXPANDED_BLOCKS_MARKER} */
.mcpwp-clients{padding:34px 0 58px}.mcpwp-clients-label{color:var(--mcp-faint);font:700 12px "JetBrains Mono",monospace;text-transform:uppercase;letter-spacing:.14em;margin:0 0 14px}.mcpwp-clients-row{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}.mcpwp-client{display:flex;align-items:center;gap:10px;padding:13px 14px;border:1px solid rgba(255,255,255,.08);border-radius:14px;background:rgba(255,255,255,.035);color:#d8e2f4;font-weight:700}.mcpwp-client-glyph{display:inline-flex;width:30px;height:30px;align-items:center;justify-content:center;border-radius:10px;background:rgba(77,134,255,.13);color:#8fb2ff;font:800 11px "JetBrains Mono",monospace}.mcpwp-blueprints{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}.mcpwp-bp{padding:14px 12px;border:1px solid rgba(255,255,255,.1);border-radius:14px;background:rgba(255,255,255,.035);color:#dbe6f7;font:800 12px "JetBrains Mono",monospace;text-transform:lowercase}.mcpwp-bp small{display:block;color:var(--mcp-faint);font-size:10px;margin-bottom:6px}.mcpwp-compare{width:100%;border-collapse:collapse;overflow:hidden;border-radius:18px;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1)}.mcpwp-compare th,.mcpwp-compare td{padding:17px 18px;border-bottom:1px solid rgba(255,255,255,.08);text-align:left;color:#cbd7ea}.mcpwp-compare th{color:#fff;background:rgba(255,255,255,.045);font:800 13px "JetBrains Mono",monospace}.mcpwp-compare tr:last-child td{border-bottom:0}.mcpwp-compare .mcpwp-us{color:#fff;background:rgba(77,134,255,.08)}.mcpwp-check{color:var(--mcp-cyan);font-weight:900;margin-right:8px}.mcpwp-faq{display:grid;grid-template-columns:.8fr 1.2fr;gap:28px}.mcpwp-faq-list{display:grid;gap:12px}.mcpwp-faq-item{padding:20px 22px;border:1px solid rgba(255,255,255,.1);border-radius:16px;background:rgba(255,255,255,.035)}.mcpwp-faq-item h3{margin:0 0 8px;color:#fff;font-family:"Space Grotesk",system-ui,sans-serif;font-size:21px}.mcpwp-faq-item p{margin:0;color:var(--mcp-muted);line-height:1.6}.mcpwp-footer{padding:44px 0;border-top:1px solid rgba(255,255,255,.08);color:var(--mcp-faint)}.mcpwp-footer-inner{display:flex;align-items:flex-start;justify-content:space-between;gap:24px}.mcpwp-footer-brand{font-family:"Space Grotesk",system-ui,sans-serif;color:#fff;font-size:24px;font-weight:800}.mcpwp-footer-links{display:flex;flex-wrap:wrap;gap:14px}.mcpwp-footer-links a{color:#cbd7ea;text-decoration:none;font-weight:700}@media(max-width:900px){.mcpwp-clients-row{grid-template-columns:repeat(2,1fr)}.mcpwp-blueprints{grid-template-columns:repeat(2,1fr)}.mcpwp-faq{grid-template-columns:1fr}.mcpwp-compare{display:block;overflow-x:auto}.mcpwp-footer-inner{display:block}.mcpwp-footer-links{margin-top:18px}}@media(max-width:560px){.mcpwp-clients-row,.mcpwp-blueprints{grid-template-columns:1fr}}
`;

const sectionBlocks = [
  {
    name: "hero",
    html: `
<div class="mcpwp-launch" data-launch-section="hero">
  <section class="mcpwp-hero"><div class="mcpwp-wrap">
    <span class="mcpwp-badge"><b>Live MCP tools</b> for WordPress, Elementor, WooCommerce, SEO, media and admin workflows</span>
    <h1>Run WordPress by <span class="mcpwp-grad">talking to your AI.</span></h1>
    <p class="mcpwp-sub">MCPWP turns your WordPress site into a Model Context Protocol server, so Claude, Cursor, Codex and other AI clients can safely operate real site workflows in plain English.</p>
    <div class="mcpwp-actions"><a class="mcpwp-btn mcpwp-btn-primary" href="/pricing/">See pricing</a><a class="mcpwp-btn" href="/docs/">Connect your first site</a></div>
    <p class="mcpwp-note">Dynamic tool discovery. Role-scoped keys. Human-safe WordPress operations.</p>
    <div class="mcpwp-demo"><div class="mcpwp-demo-top"><i class="mcpwp-dot"></i><i class="mcpwp-dot"></i><i class="mcpwp-dot"></i><span>mcpwp.net · MCP session</span></div><div class="mcpwp-terminal"><div class="mcpwp-pane"><code class="mcpwp-line"><strong>You:</strong> Build a pricing page from our offer design.</code><code class="mcpwp-line"><strong>AI:</strong> I found Elementor, checked reusable parts, and will draft the page safely.</code><code class="mcpwp-line"><strong>Tool:</strong> wp_set_elementor → validated → saved</code></div><div class="mcpwp-pane"><code class="mcpwp-line"><strong>Result:</strong> A real WordPress page, not a mockup.</code><code class="mcpwp-line">Edit sections, menus, SEO, media and WooCommerce with scoped access.</code></div></div></div>
  </div></section>
</div>`,
  },
  {
    name: "clients",
    html: `
<div class="mcpwp-launch" data-launch-section="clients">
  <section class="mcpwp-clients"><div class="mcpwp-wrap"><p class="mcpwp-clients-label">Works with MCP-capable clients and developer workflows</p><div class="mcpwp-clients-row"><div class="mcpwp-client"><span class="mcpwp-client-glyph">CC</span>Claude Code</div><div class="mcpwp-client"><span class="mcpwp-client-glyph">CD</span>Claude Desktop</div><div class="mcpwp-client"><span class="mcpwp-client-glyph">CX</span>Cursor</div><div class="mcpwp-client"><span class="mcpwp-client-glyph">WX</span>Windsurf</div><div class="mcpwp-client"><span class="mcpwp-client-glyph">OA</span>OpenAI tools</div><div class="mcpwp-client"><span class="mcpwp-client-glyph">AI</span>Custom agents</div></div></div></section>
</div>`,
  },
  {
    name: "how-it-works",
    html: `
<div class="mcpwp-launch" data-launch-section="how-it-works">
  <section class="mcpwp-section"><div class="mcpwp-wrap"><div class="mcpwp-head"><span class="mcpwp-eyebrow">How it works</span><h2>Your site becomes a tool the AI can operate.</h2><p>The assistant speaks natural language. MCPWP translates requests into authenticated, validated WordPress operations with scoped access and an activity trail.</p></div><div class="mcpwp-flow"><div class="mcpwp-card"><span class="mcpwp-step-num">01</span><h3>Connect an AI client</h3><p>Use Claude, Cursor, Codex or any MCP-compatible client with your site endpoint and API key.</p></div><div class="mcpwp-card"><span class="mcpwp-step-num">02</span><h3>Discover live capabilities</h3><p>Tools adapt to your active plugins, site setup, role permissions and enabled integrations.</p></div><div class="mcpwp-card"><span class="mcpwp-step-num">03</span><h3>Operate WordPress safely</h3><p>Create pages, edit Elementor, manage SEO, media, menus and commerce workflows with validation.</p></div></div></div></section>
</div>`,
  },
  {
    name: "toolbox",
    html: `
<div class="mcpwp-launch" data-launch-section="toolbox">
  <section class="mcpwp-section"><div class="mcpwp-wrap"><div class="mcpwp-head"><span class="mcpwp-eyebrow">Toolbox</span><h2>Dynamic WordPress tools for serious site operations.</h2><p>Instead of promising a fixed count, MCPWP exposes the right tools for each site: what is installed, licensed, enabled and allowed by the key.</p></div><div class="mcpwp-tools"><div class="mcpwp-chip">Content<span>Pages, posts, drafts, bulk operations</span></div><div class="mcpwp-chip">Elementor<span>Read, write, patch and preview layouts</span></div><div class="mcpwp-chip">SEO<span>Metadata, audits, readiness checks</span></div><div class="mcpwp-chip">WooCommerce<span>Products, orders and shop operations</span></div><div class="mcpwp-chip">Media<span>Uploads, galleries and page assets</span></div><div class="mcpwp-chip">Menus<span>Navigation and site structure</span></div><div class="mcpwp-chip">Admin<span>Options, keys, health and context</span></div><div class="mcpwp-chip">Blueprints<span>Reusable page-building patterns</span></div></div></div></section>
</div>`,
  },
  {
    name: "launch-path",
    html: `
<div class="mcpwp-launch" data-launch-section="launch-path">
  <section class="mcpwp-section"><div class="mcpwp-wrap"><div class="mcpwp-head"><span class="mcpwp-eyebrow">Launch path</span><h2>Homepage to first successful MCP connection.</h2><p>Start with the outcome, then move from plan selection to setup, scoped API key creation, and a first working AI-to-WordPress action.</p></div><div class="mcpwp-grid"><div class="mcpwp-card"><span class="mcpwp-kicker">01 Understand</span><h3>WordPress as MCP server</h3><p>See how your existing site becomes a controllable, auditable tool for AI clients.</p></div><div class="mcpwp-card"><span class="mcpwp-kicker">02 Trust</span><h3>Scoped, validated access</h3><p>Choose which workflows an assistant can use before it touches real site content.</p></div><div class="mcpwp-card"><span class="mcpwp-kicker">03 Connect</span><h3>Prove it on your site</h3><p>Paste the endpoint and key into your MCP client, then inspect live tools before making changes.</p></div></div></div></section>
</div>`,
  },
  {
    name: "blueprints",
    html: `
<div class="mcpwp-launch" data-launch-section="blueprints">
  <section class="mcpwp-section"><div class="mcpwp-wrap"><div class="mcpwp-head"><span class="mcpwp-eyebrow">Blueprints</span><h2>Reusable page patterns without the blank canvas.</h2><p>MCPWP can assemble structured Elementor sections from intent: hero, pricing, FAQ, feature grids, CTAs, service pages and other repeatable site patterns.</p></div><div class="mcpwp-blueprints"><div class="mcpwp-bp"><small>01</small>hero</div><div class="mcpwp-bp"><small>02</small>features</div><div class="mcpwp-bp"><small>03</small>pricing</div><div class="mcpwp-bp"><small>04</small>faq</div><div class="mcpwp-bp"><small>05</small>cta</div><div class="mcpwp-bp"><small>06</small>services</div><div class="mcpwp-bp"><small>07</small>about</div><div class="mcpwp-bp"><small>08</small>process</div><div class="mcpwp-bp"><small>09</small>social proof</div><div class="mcpwp-bp"><small>10</small>products</div><div class="mcpwp-bp"><small>11</small>gallery</div><div class="mcpwp-bp"><small>12</small>contact</div></div></div></section>
</div>`,
  },
  {
    name: "compare",
    html: `
<div class="mcpwp-launch" data-launch-section="compare">
  <section class="mcpwp-section"><div class="mcpwp-wrap"><div class="mcpwp-head"><span class="mcpwp-eyebrow">Why MCPWP</span><h2>Built for real WordPress operations, not just content snippets.</h2><p>The differentiator is depth across the site stack: MCP discovery, Elementor, content, SEO, media, menus, commerce, permissions and repeatable workflows.</p></div><table class="mcpwp-compare"><thead><tr><th>Capability</th><th class="mcpwp-us">MCPWP</th><th>Basic adapter</th><th>Manual workflow</th></tr></thead><tbody><tr><td>Live tool discovery</td><td class="mcpwp-us"><span class="mcpwp-check">✓</span>Site-aware</td><td>Limited</td><td>No</td></tr><tr><td>Elementor workflows</td><td class="mcpwp-us"><span class="mcpwp-check">✓</span>Build and edit</td><td>Usually no</td><td>Manual</td></tr><tr><td>Scoped API keys</td><td class="mcpwp-us"><span class="mcpwp-check">✓</span>Role controlled</td><td>Varies</td><td>No</td></tr><tr><td>SEO/media/menus</td><td class="mcpwp-us"><span class="mcpwp-check">✓</span>Operational layer</td><td>Partial</td><td>Manual</td></tr><tr><td>Blueprint patterns</td><td class="mcpwp-us"><span class="mcpwp-check">✓</span>Reusable sections</td><td>No</td><td>No</td></tr></tbody></table></div></section>
</div>`,
  },
  {
    name: "pricing",
    html: `
<div class="mcpwp-launch" data-launch-section="pricing">
  <section class="mcpwp-section"><div class="mcpwp-wrap"><div class="mcpwp-head"><span class="mcpwp-eyebrow">Plans</span><h2>Pick the level of AI control your site needs.</h2><p>Start with a scoped MCP connection, then expand into advanced builder, commerce, SEO and multi-site workflows as your operation grows.</p></div><div class="mcpwp-pricing"><div class="mcpwp-card mcpwp-plan"><h3>Core</h3><div class="mcpwp-price">Connect</div><p>Get a WordPress site speaking MCP with scoped API keys and auditable access.</p><ul><li>MCP endpoint and key setup</li><li>Posts, pages, media and menus</li><li>Basic Elementor operations when available</li></ul></div><div class="mcpwp-card mcpwp-plan mcpwp-plan-featured"><h3>Builder</h3><div class="mcpwp-price">Operate</div><p>Use AI clients for richer site-building and optimization workflows.</p><ul><li>Elementor layout workflows</li><li>SEO and content operations</li><li>Blueprint-driven page creation</li></ul></div><div class="mcpwp-card mcpwp-plan"><h3>Agency</h3><div class="mcpwp-price">Scale</div><p>Coordinate repeatable AI-assisted workflows across client sites.</p><ul><li>Multi-site operating model</li><li>Reusable playbooks and patterns</li><li>Priority setup and rollout support</li></ul></div></div></div></section>
</div>`,
  },
  {
    name: "faq",
    html: `
<div class="mcpwp-launch" data-launch-section="faq">
  <section class="mcpwp-section"><div class="mcpwp-wrap mcpwp-faq"><div class="mcpwp-head"><span class="mcpwp-eyebrow">FAQ</span><h2>Questions before connecting an AI to WordPress.</h2><p>The short answer: MCPWP gives assistants a controlled interface. You decide the key, scope and workflows before any operation runs.</p></div><div class="mcpwp-faq-list"><div class="mcpwp-faq-item"><h3>What does MCPWP actually do?</h3><p>It exposes your WordPress site as a Model Context Protocol server so AI clients can discover and call site operations through a secure endpoint.</p></div><div class="mcpwp-faq-item"><h3>Is this safe for production sites?</h3><p>Use scoped API keys, roles, tool controls and activity logs. Start read-only, inspect tools, then enable only the workflows you need.</p></div><div class="mcpwp-faq-item"><h3>Does it only work with Elementor?</h3><p>No. Elementor is a major workflow, but MCPWP also covers core WordPress content, media, menus, settings and other integrations when available.</p></div><div class="mcpwp-faq-item"><h3>What is the first successful connection?</h3><p>Your MCP client calls the endpoint, authenticates with a scoped key, and asks MCPWP to inspect the site and list available tools.</p></div></div></div></section>
</div>`,
  },
  {
    name: "final-cta",
    html: `
<div class="mcpwp-launch" data-launch-section="final-cta">
  <section class="mcpwp-section mcpwp-final"><div class="mcpwp-wrap"><div class="mcpwp-head"><span class="mcpwp-eyebrow">Get started</span><h2>Tell your AI what to build. MCPWP does the WordPress part.</h2><p>Install the plugin, create a scoped API key, paste the endpoint into your MCP client, and run your first safe site operation.</p></div><div class="mcpwp-actions" style="justify-content:center"><a class="mcpwp-btn mcpwp-btn-primary" href="/pricing/">Start with pricing</a><a class="mcpwp-btn" href="/docs/">Read setup docs</a></div><pre class="mcpwp-code">Endpoint: https://your-site.com/wp-json/site-pilot-ai/v1/mcp
Header: X-API-Key: your scoped key
First command: "Inspect my WordPress site and list available MCP tools."</pre></div></section>
</div>`,
  },
  {
    name: "footer",
    html: `
<div class="mcpwp-launch" data-launch-section="footer">
  <footer class="mcpwp-footer"><div class="mcpwp-wrap mcpwp-footer-inner"><div><div class="mcpwp-footer-brand">MCPWP</div><p>WordPress as an MCP server for safe AI-assisted site operations.</p></div><nav class="mcpwp-footer-links"><a href="/docs/">Docs</a><a href="/pricing/">Pricing</a><a href="/download/">Download</a><a href="https://github.com/Mumega-com/mcpwp">GitHub</a></nav></div></footer>
</div>`,
  },
];

function elementorData() {
  const rand = () => Math.random().toString(36).slice(2, 10);
  return sectionBlocks.map((block) => ({
      id: rand(),
      elType: "section",
      settings: { _css_classes: `mcpwp-launch-block mcpwp-launch-block-${block.name}` },
      elements: [
        {
          id: rand(),
          elType: "column",
          settings: { _column_size: 100 },
          elements: [
            {
              id: rand(),
              elType: "widget",
              widgetType: "html",
              settings: { html: block.html },
              elements: [],
            },
          ],
        },
      ],
    }));
}

function parseToolResult(result) {
  const text = result?.content?.find?.((item) => item.type === "text")?.text;
  if (!text) return result;
  try {
    return JSON.parse(text);
  } catch {
    return text;
  }
}

const config = JSON.parse(fs.readFileSync(MCP_CONFIG, "utf8")).mcpServers.mcpwp;
let id = 1;
const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function rpc(method, params = {}) {
  for (let attempt = 0; attempt < 6; attempt++) {
    const response = await fetch(config.url, {
      method: "POST",
      headers: { "Content-Type": "application/json", ...config.headers },
      body: JSON.stringify({ jsonrpc: "2.0", id: id++, method, params }),
    });
    const text = await response.text();
    const json = JSON.parse(text);
    const rateLimit = json.code === "rate_limit_exceeded" || json.error?.code === "rate_limit_exceeded";
    if (rateLimit) {
      const retryAfter = json.data?.retry_after ?? json.error?.data?.retry_after ?? 5;
      await delay((retryAfter + 2) * 1000);
      continue;
    }
    if (json.error) throw new Error(JSON.stringify(json.error));
    if (!json.result && json.code) throw new Error(text);
    return json.result;
  }
  throw new Error("Rate limit did not clear after retries.");
}

async function callTool(name, args = {}) {
  await delay(2500);
  const result = await rpc("tools/call", { name, arguments: args });
  return parseToolResult(result);
}

async function main() {
  await rpc("initialize", {
    protocolVersion: "2025-03-26",
    capabilities: {},
    clientInfo: { name: "mcpwp-launch-draft-deployer", version: "0.1" },
  });

  const pages = await callTool("wp_list_pages", {
    status: "draft",
    search: PAGE_TITLE,
    per_page: 20,
    fields: "id,title,slug,status,url,has_elementor,modified,template",
  });
  let page = pages.pages?.find((candidate) => candidate.title === PAGE_TITLE);

  if (!deploy) {
    console.log(JSON.stringify({
      deploy: false,
      pageFound: Boolean(page),
      page,
      cssMarker: CSS_MARKER,
      next: `Run: node ${process.argv[1]} --deploy`,
    }, null, 2));
    return;
  }

  if (!page) {
    const created = await callTool("wp_create_page", {
      title: PAGE_TITLE,
      slug: PAGE_SLUG,
      status: "draft",
      content: "",
    });
    page = created.page ?? created;
  }

  const pageId = Number(page.id ?? page.ID);
  if (!pageId) throw new Error(`Could not determine page ID from: ${JSON.stringify(page)}`);

  const data = elementorData();
  const elementor_data_base64 = Buffer.from(JSON.stringify(data), "utf8").toString("base64");

  const dryRun = await callTool("wp_set_elementor", {
    id: pageId,
    elementor_data_base64,
    dry_run: true,
  });

  const customCss = await callTool("wp_get_custom_css", {});
  const currentCss = typeof customCss === "string" ? customCss : customCss.css ?? customCss.custom_css ?? "";
  if (!currentCss.includes(CSS_MARKER)) {
    await callTool("wp_set_custom_css", { css, mode: "append" });
  }
  if (!currentCss.includes(CTA_FIX_MARKER)) {
    await callTool("wp_set_custom_css", { css: ctaFixCss, mode: "append" });
  }
  if (!currentCss.includes(EXPANDED_BLOCKS_MARKER)) {
    await callTool("wp_set_custom_css", { css: expandedBlocksCss, mode: "append" });
  }

  const saved = await callTool("wp_set_elementor", {
    id: pageId,
    elementor_data_base64,
    dry_run: false,
  });
  await callTool("wp_update_page_template", { id: pageId, template: "elementor_header_footer" });
  await callTool("wp_regenerate_elementor_css", {});

  const check = await callTool("wp_list_pages", {
    ids: String(pageId),
    status: "draft",
    fields: "id,title,slug,status,url,has_elementor,modified,template",
  });
  console.log(JSON.stringify({ pageId, dryRun, saved, check }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
