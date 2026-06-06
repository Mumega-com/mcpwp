import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const PAGE_TITLE = "MCPWP Download Draft";
const PAGE_SLUG = "mcpwp-download-draft";
const CSS_MARKER = "MCPWP download draft CSS - 2026-06-04";
const deploy = process.argv.includes("--deploy");

const css = `
/* ${CSS_MARKER} */
.mcpwp-download{--bg:#050812;--panel:#101827;--line:#263550;--text:#f7f9ff;--muted:#9aa8bd;--faint:#6f7b8d;--blue:#4d86ff;--cyan:#4be3c2;--max:1120px;background:radial-gradient(circle at 20% 0%,rgba(77,134,255,.22),transparent 30%),linear-gradient(180deg,#050812,#070b14);color:var(--text);font-family:"IBM Plex Sans","Space Grotesk",system-ui,sans-serif;max-width:100%;overflow:hidden}.mcpwp-download *{box-sizing:border-box;min-width:0}.mcpwp-download-wrap{max-width:var(--max);margin:0 auto;padding:0 28px}.mcpwp-download-section{padding:86px 0;border-top:1px solid rgba(255,255,255,.07)}.mcpwp-download-hero{padding:112px 0 78px}.mcpwp-download-kicker{display:inline-flex;gap:9px;align-items:center;color:var(--cyan);font:800 12px "JetBrains Mono",monospace;letter-spacing:.16em;text-transform:uppercase}.mcpwp-download-kicker:before{content:"";width:24px;height:1px;background:var(--cyan)}.mcpwp-download h1,.mcpwp-download h2,.mcpwp-download h3{font-family:"Space Grotesk",system-ui,sans-serif;letter-spacing:-.04em;line-height:1.04}.mcpwp-download h1{max-width:900px;font-size:clamp(46px,7vw,82px);margin:22px 0}.mcpwp-download h2{font-size:clamp(32px,4.6vw,54px);margin:18px 0 14px}.mcpwp-download h3{font-size:24px;margin:0 0 10px}.mcpwp-download p{color:var(--muted);font-size:18px;line-height:1.65}.mcpwp-download-lead{max-width:760px;font-size:clamp(20px,2.2vw,26px)!important}.mcpwp-download-grad{background:linear-gradient(100deg,#fff 25%,#78a5ff 64%,#4be3c2);-webkit-background-clip:text;background-clip:text;color:transparent}.mcpwp-download-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}.mcpwp-download-btn{display:inline-flex;align-items:center;justify-content:center;padding:15px 22px;border-radius:14px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.07);color:#fff!important;text-decoration:none!important;font-weight:800}.mcpwp-download-btn-primary{background:linear-gradient(135deg,var(--blue),#2f6ef7);border-color:#78a5ff}.mcpwp-download-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.mcpwp-download-card{padding:24px;border:1px solid rgba(255,255,255,.1);border-radius:20px;background:linear-gradient(180deg,rgba(255,255,255,.055),rgba(255,255,255,.025));overflow:hidden}.mcpwp-download-card strong{display:block;color:#fff;font-size:18px;margin-bottom:8px}.mcpwp-download-code{display:block;max-width:100%;margin:16px 0 0;padding:18px 20px;border-radius:16px;background:#070b12;border:1px solid rgba(255,255,255,.12);color:#d5e4ff;font:600 13px/1.7 "JetBrains Mono",monospace;overflow-x:auto;white-space:pre-wrap;word-break:break-word}.mcpwp-download-steps{display:grid;gap:14px}.mcpwp-download-step{display:grid;grid-template-columns:62px minmax(0,1fr);gap:18px;padding:22px;border-radius:18px;background:rgba(77,134,255,.06);border:1px solid rgba(77,134,255,.15);overflow:hidden}.mcpwp-download-num{width:48px;height:48px;border-radius:14px;background:rgba(75,227,194,.12);color:var(--cyan);display:flex;align-items:center;justify-content:center;font:900 15px "JetBrains Mono",monospace}.mcpwp-download-note{padding:22px 24px;border-radius:18px;background:rgba(75,227,194,.07);border:1px solid rgba(75,227,194,.16);color:#d9fff6;font-weight:800}.mcpwp-download-note span{display:block;color:var(--muted);font-weight:500;margin-top:8px}@media(max-width:860px){.mcpwp-download-grid{grid-template-columns:1fr}.mcpwp-download-step{grid-template-columns:1fr}.mcpwp-download-wrap{padding:0 20px}}@media(max-width:560px){.mcpwp-download-btn{width:100%}.mcpwp-download-hero{padding-top:76px}.mcpwp-download-code{font-size:12px}}
`;

const blocks = [
  {
    name: "hero",
    html: `<div class="mcpwp-download" data-download-section="hero"><section class="mcpwp-download-hero"><div class="mcpwp-download-wrap"><span class="mcpwp-download-kicker">Download</span><h1>Install MCPWP, then connect WordPress to <span class="mcpwp-download-grad">your AI client.</span></h1><p class="mcpwp-download-lead">The download step should get users to a working plugin install, a scoped API key, and a clear next action: inspect the site through MCP before making changes.</p><div class="mcpwp-download-actions"><a class="mcpwp-download-btn mcpwp-download-btn-primary" href="#install">Install options</a><a class="mcpwp-download-btn" href="/docs/">Read docs</a></div></div></section></div>`,
  },
  {
    name: "options",
    html: `<div class="mcpwp-download" data-download-section="options"><section class="mcpwp-download-section" id="install"><div class="mcpwp-download-wrap"><span class="mcpwp-download-kicker">Install options</span><h2>Choose the install path that matches your site.</h2><div class="mcpwp-download-grid"><div class="mcpwp-download-card"><strong>WordPress admin upload</strong><p>Use the plugin ZIP from the current product download flow, then activate it in WP Admin.</p><pre class="mcpwp-download-code">Plugins → Add New → Upload Plugin → Activate</pre></div><div class="mcpwp-download-card"><strong>WP-CLI install</strong><p>For managed environments, install the ZIP with WP-CLI using your approved package source.</p><pre class="mcpwp-download-code">wp plugin install /path/to/mcpwp.zip --activate</pre></div><div class="mcpwp-download-card"><strong>Agency rollout</strong><p>For multiple sites, standardize key scopes, naming, and first-run checks before enabling write operations.</p><pre class="mcpwp-download-code">Read-only first → inspect tools → enable writes</pre></div></div></div></section></div>`,
  },
  {
    name: "after-install",
    html: `<div class="mcpwp-download" data-download-section="after-install"><section class="mcpwp-download-section"><div class="mcpwp-download-wrap"><span class="mcpwp-download-kicker">After install</span><h2>Do these four things before the first AI action.</h2><div class="mcpwp-download-steps"><div class="mcpwp-download-step"><div class="mcpwp-download-num">01</div><div><h3>Create a key</h3><p>Create a scoped API key in MCPWP settings. Start read-only unless the workflow needs mutation access.</p></div></div><div class="mcpwp-download-step"><div class="mcpwp-download-num">02</div><div><h3>Copy the endpoint</h3><p>Use your site URL with the MCPWP endpoint path.</p><pre class="mcpwp-download-code">https://your-site.com/wp-json/site-pilot-ai/v1/mcp</pre></div></div><div class="mcpwp-download-step"><div class="mcpwp-download-num">03</div><div><h3>Configure the client</h3><p>Name the MCP server clearly and keep the key in the client config or secret store.</p><pre class="mcpwp-download-code">"mcpwp": {
  "url": "https://your-site.com/wp-json/site-pilot-ai/v1/mcp",
  "headers": { "X-API-Key": "spai_your_scoped_key" }
}</pre></div></div><div class="mcpwp-download-step"><div class="mcpwp-download-num">04</div><div><h3>Inspect before writing</h3><p>Ask the client to inspect the site and list available tools before requesting page, SEO, media or menu changes.</p></div></div></div></div></section></div>`,
  },
  {
    name: "handoff",
    html: `<div class="mcpwp-download" data-download-section="handoff"><section class="mcpwp-download-section"><div class="mcpwp-download-wrap"><div class="mcpwp-download-note">A successful download is not the finish line.<span>The conversion completes when the user sees the first MCP response from their own WordPress site.</span></div><div class="mcpwp-download-actions"><a class="mcpwp-download-btn mcpwp-download-btn-primary" href="/docs/getting-started/">Continue to setup</a><a class="mcpwp-download-btn" href="/pricing/">Back to plans</a></div></div></section></div>`,
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
    settings: { _css_classes: `mcpwp-download-block mcpwp-download-block-${block.name}` },
    elements: [{
      id: rand(),
      elType: "column",
      settings: { _column_size: 100 },
      elements: [{ id: rand(), elType: "widget", widgetType: "html", settings: { html: block.html }, elements: [] }],
    }],
  }));
}

async function main() {
  await rpc("initialize", { protocolVersion: "2025-03-26", capabilities: {}, clientInfo: { name: "mcpwp-download-deployer", version: "0.1" } });
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
