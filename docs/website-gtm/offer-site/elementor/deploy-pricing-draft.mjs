import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const PAGE_TITLE = "MCPWP Pricing Draft";
const PAGE_SLUG = "mcpwp-pricing-draft";
const CSS_MARKER = "MCPWP pricing draft CSS - 2026-06-04";
const deploy = process.argv.includes("--deploy");

const css = `
/* ${CSS_MARKER} */
.mcpwp-price-page{--bg:#050812;--panel:#101827;--line:#263550;--text:#f7f9ff;--muted:#9aa8bd;--faint:#6f7b8d;--blue:#4d86ff;--cyan:#4be3c2;--max:1140px;background:radial-gradient(circle at 20% 0%,rgba(77,134,255,.22),transparent 30%),radial-gradient(circle at 80% 20%,rgba(75,227,194,.1),transparent 30%),linear-gradient(180deg,#050812,#070b14);color:var(--text);font-family:"IBM Plex Sans","Space Grotesk",system-ui,sans-serif}.mcpwp-price-page *{box-sizing:border-box}.mcpwp-price-wrap{max-width:var(--max);margin:0 auto;padding:0 28px}.mcpwp-price-section{padding:88px 0;border-top:1px solid rgba(255,255,255,.07)}.mcpwp-price-hero{padding:112px 0 74px}.mcpwp-price-kicker{display:inline-flex;gap:9px;align-items:center;color:var(--cyan);font:800 12px "JetBrains Mono",monospace;letter-spacing:.16em;text-transform:uppercase}.mcpwp-price-kicker:before{content:"";width:24px;height:1px;background:var(--cyan)}.mcpwp-price-page h1,.mcpwp-price-page h2,.mcpwp-price-page h3{font-family:"Space Grotesk",system-ui,sans-serif;letter-spacing:-.04em;line-height:1.04}.mcpwp-price-page h1{max-width:900px;font-size:clamp(46px,7vw,82px);margin:22px 0}.mcpwp-price-page h2{font-size:clamp(32px,4.6vw,54px);margin:18px 0 14px}.mcpwp-price-page h3{font-size:25px;margin:0 0 10px}.mcpwp-price-page p{color:var(--muted);font-size:18px;line-height:1.65}.mcpwp-price-lead{max-width:760px;font-size:clamp(20px,2.2vw,26px)!important}.mcpwp-price-grad{background:linear-gradient(100deg,#fff 25%,#78a5ff 64%,#4be3c2);-webkit-background-clip:text;background-clip:text;color:transparent}.mcpwp-price-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.mcpwp-plan-card{padding:30px;border:1px solid rgba(255,255,255,.1);border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));position:relative;overflow:hidden}.mcpwp-plan-card.featured{border-color:rgba(77,134,255,.55);box-shadow:0 0 0 1px rgba(77,134,255,.16),0 30px 80px -42px rgba(77,134,255,.9)}.mcpwp-plan-label{display:inline-flex;padding:6px 10px;border-radius:999px;background:rgba(75,227,194,.12);color:var(--cyan);font:800 11px "JetBrains Mono",monospace;text-transform:uppercase;letter-spacing:.12em;margin-bottom:18px}.mcpwp-plan-value{font-family:"Space Grotesk",system-ui,sans-serif;font-size:42px;font-weight:800;letter-spacing:-.05em;margin:14px 0 8px}.mcpwp-plan-card ul{list-style:none;padding:0;margin:22px 0 0}.mcpwp-plan-card li{margin:11px 0;color:var(--muted);line-height:1.5}.mcpwp-plan-card li:before{content:"✓";color:var(--cyan);font-weight:900;margin-right:10px}.mcpwp-price-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:26px}.mcpwp-price-btn{display:inline-flex;align-items:center;justify-content:center;padding:14px 20px;border-radius:14px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.07);color:#fff!important;text-decoration:none!important;font-weight:800}.mcpwp-price-btn-primary{background:linear-gradient(135deg,var(--blue),#2f6ef7);border-color:#78a5ff}.mcpwp-proof-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.mcpwp-proof{padding:18px;border:1px solid rgba(255,255,255,.1);border-radius:16px;background:rgba(255,255,255,.035);color:#dbe6f7;font-weight:800}.mcpwp-proof span{display:block;color:var(--muted);font-weight:500;margin-top:8px}.mcpwp-path{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.mcpwp-path-step{padding:20px;border-radius:18px;background:rgba(77,134,255,.07);border:1px solid rgba(77,134,255,.15)}.mcpwp-path-step strong{display:block;color:#fff;margin-bottom:8px}.mcpwp-note{padding:22px 24px;border-radius:18px;background:rgba(255,207,102,.08);border:1px solid rgba(255,207,102,.18);color:#ffe8a8;font-weight:700}.mcpwp-note span{display:block;color:var(--muted);font-weight:500;margin-top:8px}@media(max-width:900px){.mcpwp-price-grid,.mcpwp-proof-grid,.mcpwp-path{grid-template-columns:1fr}.mcpwp-price-wrap{padding:0 20px}}@media(max-width:560px){.mcpwp-price-btn{width:100%}.mcpwp-price-hero{padding-top:76px}}
`;

const blocks = [
  {
    name: "hero",
    html: `<div class="mcpwp-price-page" data-price-section="hero"><section class="mcpwp-price-hero"><div class="mcpwp-price-wrap"><span class="mcpwp-price-kicker">Plans</span><h1>Choose how much AI control your <span class="mcpwp-price-grad">WordPress site needs.</span></h1><p class="mcpwp-price-lead">Start with a scoped MCP connection, then expand into builder, SEO, commerce and multi-site workflows when your operation needs more depth.</p><div class="mcpwp-price-actions"><a class="mcpwp-price-btn mcpwp-price-btn-primary" href="/download/">Download MCPWP</a><a class="mcpwp-price-btn" href="/docs/">Read setup docs</a></div></div></section></div>`,
  },
  {
    name: "plans",
    html: `<div class="mcpwp-price-page" data-price-section="plans"><section class="mcpwp-price-section"><div class="mcpwp-price-wrap"><span class="mcpwp-price-kicker">Plan fit</span><h2>Package by workflow, not by a fake fixed tool count.</h2><p>Each site discovers its own tools based on active plugins, enabled capabilities and API key scopes.</p><div class="mcpwp-price-grid"><div class="mcpwp-plan-card"><span class="mcpwp-plan-label">Core</span><h3>Connect WordPress to MCP</h3><div class="mcpwp-plan-value">Inspect</div><p>Best for first installs, content workflows and safe read-first discovery.</p><ul><li>MCP endpoint and scoped keys</li><li>Posts, pages, media and menus</li><li>Basic Elementor operations when installed</li><li>Activity logs and tool controls</li></ul><div class="mcpwp-price-actions"><a class="mcpwp-price-btn" href="/download/">Download</a></div></div><div class="mcpwp-plan-card featured"><span class="mcpwp-plan-label">Builder</span><h3>Operate site workflows with AI</h3><div class="mcpwp-plan-value">Build</div><p>Best for agencies, builders and operators using Elementor, SEO and page patterns.</p><ul><li>Elementor layout workflows</li><li>Blueprint-driven page sections</li><li>SEO and content operations</li><li>Media, menus and site context</li></ul><div class="mcpwp-price-actions"><a class="mcpwp-price-btn mcpwp-price-btn-primary" href="/download/">Start setup</a></div></div><div class="mcpwp-plan-card"><span class="mcpwp-plan-label">Agency</span><h3>Scale repeatable client operations</h3><div class="mcpwp-plan-value">Manage</div><p>Best for teams managing multiple WordPress sites with consistent playbooks.</p><ul><li>Multi-site operating model</li><li>Reusable workflow patterns</li><li>Client-safe scopes and approvals</li><li>Priority rollout support</li></ul><div class="mcpwp-price-actions"><a class="mcpwp-price-btn" href="/contact/">Talk to us</a></div></div></div></div></section></div>`,
  },
  {
    name: "proof",
    html: `<div class="mcpwp-price-page" data-price-section="proof"><section class="mcpwp-price-section"><div class="mcpwp-price-wrap"><span class="mcpwp-price-kicker">What you are buying</span><h2>A controlled operating layer for WordPress.</h2><div class="mcpwp-proof-grid"><div class="mcpwp-proof">MCP server<span>Your site exposes authenticated tools to AI clients.</span></div><div class="mcpwp-proof">Live discovery<span>Capabilities change by plugins, scopes and setup.</span></div><div class="mcpwp-proof">Elementor workflows<span>Draft, edit and validate real page layouts.</span></div><div class="mcpwp-proof">Scoped safety<span>Keys, roles and logs keep access intentional.</span></div></div></div></section></div>`,
  },
  {
    name: "path",
    html: `<div class="mcpwp-price-page" data-price-section="path"><section class="mcpwp-price-section"><div class="mcpwp-price-wrap"><span class="mcpwp-price-kicker">Conversion path</span><h2>From pricing decision to first successful MCP call.</h2><div class="mcpwp-path"><div class="mcpwp-path-step"><strong>01 Download</strong><p>Install MCPWP on the WordPress site you want to operate.</p></div><div class="mcpwp-path-step"><strong>02 Scope</strong><p>Create a read-first API key and enable only needed categories.</p></div><div class="mcpwp-path-step"><strong>03 Connect</strong><p>Paste endpoint and key into an MCP-capable client.</p></div><div class="mcpwp-path-step"><strong>04 Prove</strong><p>Ask the client to inspect the site and list available tools.</p></div></div><div class="mcpwp-price-actions"><a class="mcpwp-price-btn mcpwp-price-btn-primary" href="/download/">Continue to download</a><a class="mcpwp-price-btn" href="/docs/">Read the setup guide</a></div></div></section></div>`,
  },
  {
    name: "note",
    html: `<div class="mcpwp-price-page" data-price-section="note"><section class="mcpwp-price-section"><div class="mcpwp-price-wrap"><div class="mcpwp-note">Current product terms live on the checkout/download flow.<span>This draft intentionally avoids hard-coded prices, trial claims or fixed feature counts until packaging is reconciled across Freemius, docs and plugin UI.</span></div></div></section></div>`,
  },
];

const config = JSON.parse(fs.readFileSync(MCP_CONFIG, "utf8")).mcpServers.mcpwp;
let id = 1;
const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const rand = () => Math.random().toString(36).slice(2, 10);

function parseToolResult(result) {
  const text = result?.content?.find?.((item) => item.type === "text")?.text;
  if (!text) return result;
  try { return JSON.parse(text); } catch { return text; }
}

async function rpc(method, params = {}) {
  for (let attempt = 0; attempt < 6; attempt++) {
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
  await delay(2500);
  return parseToolResult(await rpc("tools/call", { name, arguments: args }));
}

function elementorData() {
  return blocks.map((block) => ({
    id: rand(),
    elType: "section",
    settings: { _css_classes: `mcpwp-price-block mcpwp-price-block-${block.name}` },
    elements: [{
      id: rand(),
      elType: "column",
      settings: { _column_size: 100 },
      elements: [{ id: rand(), elType: "widget", widgetType: "html", settings: { html: block.html }, elements: [] }],
    }],
  }));
}

async function main() {
  await rpc("initialize", { protocolVersion: "2025-03-26", capabilities: {}, clientInfo: { name: "mcpwp-pricing-deployer", version: "0.1" } });
  const pages = await callTool("wp_list_pages", { status: "draft", search: PAGE_TITLE, per_page: 20, fields: "id,title,slug,status,url,has_elementor,modified,template" });
  let page = pages.pages?.find((candidate) => candidate.title === PAGE_TITLE);
  if (!deploy) {
    console.log(JSON.stringify({ deploy: false, pageFound: Boolean(page), page, next: `Run: node ${process.argv[1]} --deploy` }, null, 2));
    return;
  }
  if (!page) {
    const created = await callTool("wp_create_page", { title: PAGE_TITLE, slug: PAGE_SLUG, status: "draft", content: "" });
    page = created.page ?? created;
  }
  const pageId = Number(page.id ?? page.ID);
  const elementor_data_base64 = Buffer.from(JSON.stringify(elementorData()), "utf8").toString("base64");
  const dryRun = await callTool("wp_set_elementor", { id: pageId, elementor_data_base64, dry_run: true });
  const customCss = await callTool("wp_get_custom_css", {});
  const currentCss = typeof customCss === "string" ? customCss : customCss.css ?? customCss.custom_css ?? "";
  if (!currentCss.includes(CSS_MARKER)) await callTool("wp_set_custom_css", { css, mode: "append" });
  const saved = await callTool("wp_set_elementor", { id: pageId, elementor_data_base64, dry_run: false });
  await callTool("wp_update_page_template", { id: pageId, template: "elementor_header_footer" });
  await callTool("wp_regenerate_elementor_css", {});
  const check = await callTool("wp_list_pages", { ids: String(pageId), status: "draft", fields: "id,title,slug,status,url,has_elementor,modified,template" });
  console.log(JSON.stringify({ pageId, dryRun, saved, check }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
