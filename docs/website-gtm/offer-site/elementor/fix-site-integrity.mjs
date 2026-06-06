import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const CSS_MARKER = "MCPWP site integrity CSS - 2026-06-05";
const SITE = "https://mcpwp.net";

const config = JSON.parse(fs.readFileSync(MCP_CONFIG, "utf8")).mcpServers.mcpwp;
let id = 1;
const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const rand = () => Math.random().toString(36).slice(2, 10);

function parseToolResult(result) {
  const text = result?.content?.find?.((item) => item.type === "text")?.text;
  if (!text) return result;
  try {
    return JSON.parse(text);
  } catch {
    return text;
  }
}

async function rpc(method, params = {}) {
  for (let attempt = 0; attempt < 10; attempt++) {
    const response = await fetch(config.url, {
      method: "POST",
      headers: { "Content-Type": "application/json", ...config.headers },
      body: JSON.stringify({ jsonrpc: "2.0", id: id++, method, params }),
    });
    const json = JSON.parse(await response.text());
    const rateLimit = json.code === "rate_limit_exceeded" || json.error?.code === "rate_limit_exceeded";
    if (rateLimit) {
      const retryAfter = json.data?.retry_after ?? json.error?.data?.retry_after ?? 5;
      await delay((retryAfter + 2) * 1000);
      continue;
    }
    if (json.error) throw new Error(JSON.stringify(json.error));
    if (!json.result && json.code) throw new Error(JSON.stringify(json));
    return json.result;
  }
  throw new Error("Rate limit did not clear after retries.");
}

async function callTool(name, args = {}) {
  await delay(1700);
  return parseToolResult(await rpc("tools/call", { name, arguments: args }));
}

async function setMeta(id, title, description, focus) {
  return Promise.all([
    callTool("wp_set_post_meta", { id, key: "_yoast_wpseo_title", value: title }),
    callTool("wp_set_post_meta", { id, key: "_yoast_wpseo_metadesc", value: description }),
    callTool("wp_set_post_meta", { id, key: "_yoast_wpseo_focuskw", value: focus }),
  ]);
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;");
}

function widget(html) {
  return { id: rand(), elType: "widget", widgetType: "html", settings: { html }, elements: [] };
}

function section(name, html) {
  return {
    id: rand(),
    elType: "section",
    settings: { _css_classes: `mcpwp-flow-block mcpwp-flow-block-${name}` },
    elements: [
      {
        id: rand(),
        elType: "column",
        settings: { _column_size: 100 },
        elements: [widget(html)],
      },
    ],
  };
}

function cardGrid(items) {
  return items.map((item) => `
    <a class="mcpwp-flow-card" href="${escapeHtml(item.href)}">
      <span>${escapeHtml(item.kicker)}</span>
      <strong>${escapeHtml(item.title)}</strong>
      <p>${escapeHtml(item.text)}</p>
    </a>
  `).join("");
}

function docsData() {
  const primary = [
    { kicker: "Start", title: "Getting started", text: "Install the plugin, create a scoped key, and connect your first client.", href: "/docs/getting-started/" },
    { kicker: "Download", title: "Current package", text: "Get the live versioned ZIP and continue into setup.", href: "/download/" },
    { kicker: "Pricing", title: "Plan fit", text: "Choose the access level that matches the WordPress workflow.", href: "/pricing/" },
  ];
  const references = [
    { kicker: "Reference", title: "MCP tools", text: "Understand dynamic tool discovery and capability categories.", href: "/docs/mcp-tools/" },
    { kicker: "API", title: "REST API", text: "Read endpoint, authentication, and response format notes.", href: "/docs/api-reference/" },
    { kicker: "Workflow", title: "Elementor MCP", text: "Inspect, patch, and validate Elementor pages through MCP.", href: "/elementor-mcp/" },
    { kicker: "Security", title: "API key scopes", text: "Operate with read-first access, role controls, and logs.", href: "/api-security/" },
    { kicker: "Content", title: "Content operations", text: "Draft, update, publish, and review WordPress content.", href: "/content-management/" },
    { kicker: "Clients", title: "Codex and Claude Code", text: "Connect development agents to real WordPress operations.", href: "/wordpress-codex/" },
  ];
  return [
    section("docs-hero", `<div class="mcpwp-flow"><section class="mcpwp-flow-hero"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">Documentation</span><h1>MCPWP docs for the first successful connection.</h1><p class="mcpwp-flow-lead">Start with the live package, configure a scoped API key, inspect the site, then expand into Elementor, SEO, content, media, menus, and operational workflows.</p><div class="mcpwp-flow-actions"><a class="mcpwp-flow-btn mcpwp-flow-btn-primary" href="/download/">Download MCPWP</a><a class="mcpwp-flow-btn" href="/docs/getting-started/">Start setup</a><a class="mcpwp-flow-btn" href="/pricing/">Compare plans</a></div></div></section></div>`),
    section("docs-primary", `<div class="mcpwp-flow"><section class="mcpwp-flow-section"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">Path</span><h2>The customer path</h2><div class="mcpwp-flow-grid mcpwp-flow-grid-3">${cardGrid(primary)}</div></div></section></div>`),
    section("docs-reference", `<div class="mcpwp-flow"><section class="mcpwp-flow-section"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">Reference</span><h2>Use the docs as a map, not a pile of pages.</h2><div class="mcpwp-flow-grid">${cardGrid(references)}</div></div></section></div>`),
    section("docs-config", `<div class="mcpwp-flow"><section class="mcpwp-flow-section"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">Client config</span><h2>Start every setup with read-only inspection.</h2><pre class="mcpwp-flow-code">"mcpwp": {
  "url": "https://your-site.com/wp-json/site-pilot-ai/v1/mcp",
  "headers": { "X-API-Key": "spai_your_scoped_key" }
}</pre><p class="mcpwp-flow-muted">The REST namespace remains <code>site-pilot-ai</code> for compatibility. Public product copy should use MCPWP.</p><div class="mcpwp-flow-actions"><a class="mcpwp-flow-btn mcpwp-flow-btn-primary" href="/docs/getting-started/">Read setup guide</a><a class="mcpwp-flow-btn" href="https://github.com/Mumega-com/mcpwp/issues" target="_blank" rel="noopener">GitHub issues</a></div></div></section></div>`),
  ];
}

function blogData(posts) {
  const cards = posts.map((post) => ({
    kicker: "Article",
    title: post.title,
    text: (post.excerpt || "Read the latest MCPWP field notes on WordPress MCP operations.").replace(/\s+/g, " ").slice(0, 155),
    href: post.url,
  }));
  return [
    section("blog-hero", `<div class="mcpwp-flow"><section class="mcpwp-flow-hero"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">Blog</span><h1>MCPWP field notes for WordPress AI operations.</h1><p class="mcpwp-flow-lead">Commercial, technical, and operational notes from building a WordPress MCP product: setup, safety, Elementor, SEO, launch flow, and agency adoption.</p><div class="mcpwp-flow-actions"><a class="mcpwp-flow-btn mcpwp-flow-btn-primary" href="/download/">Download MCPWP</a><a class="mcpwp-flow-btn" href="/docs/">Read docs</a><a class="mcpwp-flow-btn" href="/pricing/">Compare plans</a></div></div></section></div>`),
    section("blog-posts", `<div class="mcpwp-flow"><section class="mcpwp-flow-section"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">Latest</span><h2>Articles that support the product path.</h2><div class="mcpwp-flow-grid">${cardGrid(cards)}</div></div></section></div>`),
    section("blog-path", `<div class="mcpwp-flow"><section class="mcpwp-flow-section"><div class="mcpwp-flow-wrap"><div class="mcpwp-flow-note">The blog is for search and proof. The funnel still ends at download, setup, and first MCP connection.<span>Keep posts editorial in Gutenberg, but keep the blog landing page commercially designed.</span></div></div></section></div>`),
  ];
}

function css() {
  return `
/* ${CSS_MARKER} */
.mcpwp-flow{--bg:#050812;--panel:#101827;--line:#263550;--text:#f7f9ff;--muted:#9aa8bd;--faint:#6f7b8d;--blue:#4d86ff;--cyan:#4be3c2;--max:1120px;background:radial-gradient(circle at 18% 0%,rgba(77,134,255,.22),transparent 30%),linear-gradient(180deg,#050812,#070b14);color:var(--text);font-family:"IBM Plex Sans","Space Grotesk",system-ui,sans-serif;overflow:hidden}.mcpwp-flow *{box-sizing:border-box;min-width:0}.mcpwp-flow-wrap{max-width:var(--max);margin:0 auto;padding:0 28px}.mcpwp-flow-hero{padding:110px 0 74px}.mcpwp-flow-section{padding:82px 0;border-top:1px solid rgba(255,255,255,.08)}.mcpwp-flow-kicker{display:inline-flex;gap:9px;align-items:center;color:var(--cyan);font:800 12px "JetBrains Mono",monospace;letter-spacing:.16em;text-transform:uppercase}.mcpwp-flow-kicker:before{content:"";width:24px;height:1px;background:var(--cyan)}.mcpwp-flow h1,.mcpwp-flow h2,.mcpwp-flow h3{font-family:"Space Grotesk",system-ui,sans-serif;letter-spacing:-.04em;line-height:1.04}.mcpwp-flow h1{max-width:930px;font-size:clamp(44px,7vw,80px);margin:22px 0}.mcpwp-flow h2{font-size:clamp(32px,4.5vw,54px);margin:18px 0 24px}.mcpwp-flow p{color:var(--muted);font-size:18px;line-height:1.65}.mcpwp-flow-lead{max-width:800px;font-size:clamp(20px,2.2vw,25px)!important}.mcpwp-flow-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}.mcpwp-flow-btn{display:inline-flex;align-items:center;justify-content:center;padding:15px 22px;border-radius:14px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.07);color:#fff!important;text-decoration:none!important;font-weight:800}.mcpwp-flow-btn-primary{background:linear-gradient(135deg,var(--blue),#2f6ef7);border-color:#78a5ff}.mcpwp-flow-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.mcpwp-flow-grid-3{grid-template-columns:repeat(3,1fr)}.mcpwp-flow-card{display:block;padding:24px;border:1px solid rgba(255,255,255,.1);border-radius:20px;background:linear-gradient(180deg,rgba(255,255,255,.055),rgba(255,255,255,.025));color:#fff!important;text-decoration:none!important}.mcpwp-flow-card span{display:block;margin-bottom:12px;color:var(--cyan);font:800 11px "JetBrains Mono",monospace;letter-spacing:.14em;text-transform:uppercase}.mcpwp-flow-card strong{display:block;color:#fff;font-size:22px;line-height:1.15;margin-bottom:10px}.mcpwp-flow-card p{font-size:16px;margin:0}.mcpwp-flow-code{display:block;max-width:100%;margin:20px 0 0;padding:20px;border-radius:16px;background:#070b12;border:1px solid rgba(255,255,255,.12);color:#d5e4ff;font:600 14px/1.7 "JetBrains Mono",monospace;overflow-x:auto;white-space:pre-wrap;word-break:break-word}.mcpwp-flow-muted{max-width:760px}.mcpwp-flow-note{padding:24px;border-radius:20px;background:rgba(75,227,194,.07);border:1px solid rgba(75,227,194,.16);color:#d9fff6;font-weight:800}.mcpwp-flow-note span{display:block;color:var(--muted);font-weight:500;margin-top:8px}@media(max-width:900px){.mcpwp-flow-grid,.mcpwp-flow-grid-3{grid-template-columns:1fr}.mcpwp-flow-wrap{padding:0 20px}.mcpwp-flow-hero{padding:78px 0 56px}}@media(max-width:560px){.mcpwp-flow-btn{width:100%}.mcpwp-flow h1{font-size:40px}}
`;
}

async function setElementorPage(pageId, data) {
  const elementor_data_base64 = Buffer.from(JSON.stringify(data), "utf8").toString("base64");
  const dryRun = await callTool("wp_set_elementor", { id: pageId, elementor_data_base64, dry_run: true });
  const saved = await callTool("wp_set_elementor", { id: pageId, elementor_data_base64, dry_run: false });
  await callTool("wp_update_page_template", { id: pageId, template: "elementor_header_footer" });
  return { dryRun, saved };
}

async function replaceAll(replacements) {
  const results = [];
  for (const item of replacements) {
    results.push(await callTool("wp_bulk_find_replace", item));
  }
  return results;
}

async function main() {
  await rpc("initialize", {
    protocolVersion: "2025-03-26",
    capabilities: {},
    clientInfo: { name: "mcpwp-site-integrity-fixer", version: "0.1" },
  });

  const posts = await callTool("wp_list_posts", {
    status: "publish",
    per_page: 12,
    fields: "id,title,slug,url,excerpt,date,featured_media",
  });
  const latestPosts = posts.posts || posts.items || [];

  const customCss = await callTool("wp_get_custom_css", {});
  const currentCss = typeof customCss === "string" ? customCss : customCss.css ?? customCss.custom_css ?? "";
  if (!currentCss.includes(CSS_MARKER)) {
    await callTool("wp_set_custom_css", { css: css(), mode: "append" });
  }

  const docs = await setElementorPage(113, docsData());
  const blogOptionBefore = await callTool("wp_get_option", { key: "page_for_posts" });
  const blogOption = await callTool("wp_update_option", { key: "page_for_posts", value: "0" });
  const blog = await setElementorPage(10, blogData(latestPosts));

  const replacements = await replaceAll([
    { id: 8, search: "/documentation/", replace: "/docs/" },
    { id: 9, search: "/documentation/", replace: "/docs/" },
    { id: 116, search: "/documentation/mcp-tools-reference/", replace: "/docs/mcp-tools/" },
    { id: 116, search: "/documentation/getting-started-guide/", replace: "/docs/getting-started/" },
    { id: 32, search: "50+ <span style=\"background:linear-gradient(90deg,#0B1220 0%,#1B4DFF 45%,#00B7A8 95%);color:transparent\">AI-Powered</span> WordPress Tools", replace: "Dynamic <span style=\"background:linear-gradient(90deg,#0B1220 0%,#1B4DFF 45%,#00B7A8 95%);color:transparent\">AI-Powered</span> WordPress Tools" },
    { id: 32, search: "Generate a key in mumcp settings", replace: "Generate a scoped key in MCPWP settings" },
    { id: 32, search: "One-click install from WordPress.org", replace: "Download the current versioned ZIP" },
    { id: 32, search: "Get Started Free", replace: "Download MCPWP" },
    { id: 461, search: "239 AI tools for your Elementor agency.", replace: "AI operations for your Elementor agency." },
    { id: 461, search: "v2.7.1 · WordPress.org Approved · 239 Free Tools", replace: "v2.8.36 · Scoped MCP access · Dynamic tools" },
    { id: 461, search: "everything AI can touch, mumcp exposes as a tool.", replace: "everything a scoped AI client can touch, MCPWP exposes as a tool." },
    { id: 461, search: "239 MCP tools", replace: "Dynamic MCP tools" },
  ]);

  await Promise.all([
    setMeta(113, "MCPWP Documentation | WordPress MCP Setup and Tools", "Read MCPWP docs for installation, scoped API keys, MCP clients, dynamic WordPress tools, Elementor workflows, and first-connection setup.", "MCPWP documentation"),
    setMeta(10, "MCPWP Blog | WordPress MCP and AI Site Operations", "Read MCPWP articles about WordPress MCP workflows, Elementor operations, AI SEO, safe automation, agency rollout, and product launch lessons.", "WordPress MCP blog"),
    setMeta(32, "MCPWP Features | WordPress MCP Tools and Workflows", "Explore MCPWP features for dynamic WordPress MCP tools, Elementor page workflows, content, SEO, media, menus, scoped keys, and activity logs.", "MCPWP features"),
    setMeta(461, "MCPWP for Agencies | WordPress MCP Operations", "Use MCPWP to give agencies a scoped, auditable WordPress MCP workflow for Elementor, content, SEO, client sites, and safe AI operations.", "WordPress MCP agency"),
    setMeta(8, "About MCPWP | WordPress MCP Operations", "MCPWP is built for operators who want AI clients to inspect and operate WordPress through scoped MCP access.", "about MCPWP"),
    setMeta(9, "Contact MCPWP | WordPress MCP Support", "Contact MCPWP for WordPress MCP setup, agency workflows, support, and commercial questions.", "MCPWP contact"),
    setMeta(116, "MCPWP API Reference | WordPress MCP REST Endpoints", "Reference MCPWP authentication, REST endpoints, MCP routing, and WordPress operation patterns for connected AI clients.", "MCPWP API"),
  ]);

  const cssResult = await callTool("wp_regenerate_elementor_css", {});
  const summaries = await Promise.all([
    callTool("wp_get_elementor_summary", { id: 113 }),
    callTool("wp_get_elementor_summary", { id: 10 }),
    callTool("wp_get_elementor_summary", { id: 32 }),
    callTool("wp_get_elementor_summary", { id: 461 }),
  ]);

  console.log(JSON.stringify({ docs, blogOptionBefore, blogOption, blog, replacements, cssResult, summaries }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
