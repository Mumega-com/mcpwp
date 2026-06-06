import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const PAGE_TITLE = "MCPWP First Connection Draft";
const PAGE_SLUG = "mcpwp-first-connection-draft";
const CSS_MARKER = "MCPWP first connection draft CSS - 2026-06-04";
const deploy = process.argv.includes("--deploy");

const css = `
/* ${CSS_MARKER} */
.mcpwp-setup{--bg:#050812;--panel:#101827;--panel2:#152033;--line:#263550;--text:#f7f9ff;--muted:#9aa8bd;--faint:#6f7b8d;--blue:#4d86ff;--cyan:#4be3c2;--warn:#ffcf66;--max:1120px;background:radial-gradient(circle at 15% 0%,rgba(77,134,255,.22),transparent 30%),linear-gradient(180deg,#050812,#070b14);color:var(--text);font-family:"IBM Plex Sans","Space Grotesk",system-ui,sans-serif}.mcpwp-setup *{box-sizing:border-box}.mcpwp-setup-wrap{max-width:var(--max);margin:0 auto;padding:0 28px}.mcpwp-setup-section{padding:86px 0;border-top:1px solid rgba(255,255,255,.07)}.mcpwp-setup-hero{padding:112px 0 80px}.mcpwp-setup-kicker{display:inline-flex;gap:9px;align-items:center;color:var(--cyan);font:800 12px "JetBrains Mono",monospace;letter-spacing:.16em;text-transform:uppercase}.mcpwp-setup-kicker:before{content:"";width:24px;height:1px;background:var(--cyan)}.mcpwp-setup h1,.mcpwp-setup h2,.mcpwp-setup h3{font-family:"Space Grotesk",system-ui,sans-serif;letter-spacing:-.04em;line-height:1.04}.mcpwp-setup h1{max-width:900px;font-size:clamp(46px,7vw,82px);margin:22px 0}.mcpwp-setup h2{font-size:clamp(32px,4.6vw,54px);margin:18px 0 14px}.mcpwp-setup h3{font-size:24px;margin:0 0 10px}.mcpwp-setup p{color:var(--muted);font-size:18px;line-height:1.65}.mcpwp-setup-lead{max-width:760px;font-size:clamp(20px,2.2vw,26px)!important}.mcpwp-setup-grad{background:linear-gradient(100deg,#fff 25%,#78a5ff 64%,#4be3c2);-webkit-background-clip:text;background-clip:text;color:transparent}.mcpwp-setup-actions{display:flex;gap:14px;flex-wrap:wrap;margin-top:30px}.mcpwp-setup-btn{display:inline-flex;align-items:center;justify-content:center;padding:15px 22px;border-radius:14px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.07);color:#fff!important;text-decoration:none!important;font-weight:800}.mcpwp-setup-btn-primary{background:linear-gradient(135deg,var(--blue),#2f6ef7);border-color:#78a5ff}.mcpwp-setup-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.mcpwp-setup-card{padding:24px;border:1px solid rgba(255,255,255,.1);border-radius:18px;background:linear-gradient(180deg,rgba(255,255,255,.055),rgba(255,255,255,.025))}.mcpwp-setup-step{display:grid;grid-template-columns:74px 1fr;gap:20px;align-items:start;padding:24px;border:1px solid rgba(255,255,255,.1);border-radius:20px;background:rgba(255,255,255,.035);margin-bottom:14px}.mcpwp-setup-num{width:54px;height:54px;border-radius:16px;background:rgba(75,227,194,.13);color:var(--cyan);display:flex;align-items:center;justify-content:center;font:900 16px "JetBrains Mono",monospace}.mcpwp-code{margin:16px 0 0;padding:18px 20px;border-radius:16px;background:#070b12;border:1px solid rgba(255,255,255,.12);color:#d5e4ff;font:600 13px/1.7 "JetBrains Mono",monospace;overflow:auto;white-space:pre}.mcpwp-setup-checks{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.mcpwp-setup-check{padding:16px 18px;border-radius:14px;background:rgba(75,227,194,.07);border:1px solid rgba(75,227,194,.16);color:#d9fff6;font-weight:800}.mcpwp-setup-check span{display:block;margin-top:5px;color:var(--muted);font-weight:500}.mcpwp-setup-final{text-align:center}.mcpwp-setup-final p{max-width:720px;margin-left:auto;margin-right:auto}@media(max-width:860px){.mcpwp-setup-grid,.mcpwp-setup-checks{grid-template-columns:1fr}.mcpwp-setup-step{grid-template-columns:1fr}.mcpwp-setup-wrap{padding:0 20px}}@media(max-width:560px){.mcpwp-setup-btn{width:100%}.mcpwp-setup-hero{padding-top:76px}.mcpwp-code{font-size:12px}}
`;

const blocks = [
  {
    name: "hero",
    html: `<div class="mcpwp-setup" data-setup-section="hero"><section class="mcpwp-setup-hero"><div class="mcpwp-setup-wrap"><span class="mcpwp-setup-kicker">First connection</span><h1>Connect your AI client to <span class="mcpwp-setup-grad">WordPress through MCPWP.</span></h1><p class="mcpwp-setup-lead">This is the shortest path from install to proof: create a scoped key, paste the endpoint into an MCP client, inspect live tools, then run one safe WordPress operation.</p><div class="mcpwp-setup-actions"><a class="mcpwp-setup-btn mcpwp-setup-btn-primary" href="#steps">Start setup</a><a class="mcpwp-setup-btn" href="/download/">Download MCPWP</a></div></div></section></div>`,
  },
  {
    name: "prerequisites",
    html: `<div class="mcpwp-setup" data-setup-section="prerequisites"><section class="mcpwp-setup-section"><div class="mcpwp-setup-wrap"><span class="mcpwp-setup-kicker">Before you start</span><h2>Three things you need.</h2><div class="mcpwp-setup-grid"><div class="mcpwp-setup-card"><h3>WordPress admin access</h3><p>You need permission to install MCPWP and create API keys in the WordPress dashboard.</p></div><div class="mcpwp-setup-card"><h3>An MCP-capable client</h3><p>Use Claude Code, Claude Desktop, Cursor, Windsurf, Codex tooling, or another client that can connect to remote MCP servers.</p></div><div class="mcpwp-setup-card"><h3>A scoped key</h3><p>Start with read access for inspection. Add write access only when you are ready to let the assistant make changes.</p></div></div></div></section></div>`,
  },
  {
    name: "steps",
    html: `<div class="mcpwp-setup" data-setup-section="steps"><section class="mcpwp-setup-section" id="steps"><div class="mcpwp-setup-wrap"><span class="mcpwp-setup-kicker">Setup path</span><h2>From plugin install to first MCP response.</h2><div class="mcpwp-setup-step"><div class="mcpwp-setup-num">01</div><div><h3>Install and activate MCPWP</h3><p>Install the plugin on the WordPress site you want your AI client to operate.</p><pre class="mcpwp-code">WP Admin → Plugins → Add New → Upload Plugin → Activate</pre></div></div><div class="mcpwp-setup-step"><div class="mcpwp-setup-num">02</div><div><h3>Create a scoped API key</h3><p>Create a key in MCPWP settings. Use read-only for discovery, then add write/admin scopes only for workflows that need them.</p><pre class="mcpwp-code">WP Admin → MCPWP → API Keys → New key</pre></div></div><div class="mcpwp-setup-step"><div class="mcpwp-setup-num">03</div><div><h3>Copy your MCP endpoint</h3><p>Your endpoint follows the same pattern on every site. The key decides what the client can do.</p><pre class="mcpwp-code">https://your-site.com/wp-json/site-pilot-ai/v1/mcp</pre></div></div><div class="mcpwp-setup-step"><div class="mcpwp-setup-num">04</div><div><h3>Ask the client to inspect the site</h3><p>The first successful connection should be read-only: confirm site info and available tools before making changes.</p><pre class="mcpwp-code">Inspect this WordPress site and list the MCPWP tools available to my key.</pre></div></div></div></section></div>`,
  },
  {
    name: "config",
    html: `<div class="mcpwp-setup" data-setup-section="config"><section class="mcpwp-setup-section"><div class="mcpwp-setup-wrap"><span class="mcpwp-setup-kicker">Client config</span><h2>Use the same endpoint and key in your MCP client.</h2><p>Keep the server name simple and product-aligned. Do not paste admin keys into clients that only need read access.</p><pre class="mcpwp-code">{
  "mcpServers": {
    "mcpwp": {
      "url": "https://your-site.com/wp-json/site-pilot-ai/v1/mcp",
      "headers": {
        "X-API-Key": "spai_your_scoped_key"
      }
    }
  }
}</pre></div></section></div>`,
  },
  {
    name: "safety",
    html: `<div class="mcpwp-setup" data-setup-section="safety"><section class="mcpwp-setup-section"><div class="mcpwp-setup-wrap"><span class="mcpwp-setup-kicker">Safety checklist</span><h2>Make the first run boring on purpose.</h2><div class="mcpwp-setup-checks"><div class="mcpwp-setup-check">Start read-only<span>Verify site info and tools before granting mutation scopes.</span></div><div class="mcpwp-setup-check">Name the key clearly<span>Use labels like Claude Code Read or Agency Builder Write.</span></div><div class="mcpwp-setup-check">Check tool categories<span>Disable categories the client does not need.</span></div><div class="mcpwp-setup-check">Review activity logs<span>Confirm every request is visible and attributable.</span></div></div></div></section></div>`,
  },
  {
    name: "final",
    html: `<div class="mcpwp-setup" data-setup-section="final"><section class="mcpwp-setup-section mcpwp-setup-final"><div class="mcpwp-setup-wrap"><span class="mcpwp-setup-kicker">Proof command</span><h2>Your first success is a site inspection.</h2><p>Once the client responds with your site name, WordPress version, active integrations, and available MCPWP tools, the conversion path has worked.</p><div class="mcpwp-setup-actions" style="justify-content:center"><a class="mcpwp-setup-btn mcpwp-setup-btn-primary" href="/download/">Download MCPWP</a><a class="mcpwp-setup-btn" href="/docs/">Read docs</a></div></div></section></div>`,
  },
];

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
    settings: { _css_classes: `mcpwp-setup-block mcpwp-setup-block-${block.name}` },
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

async function main() {
  await rpc("initialize", {
    protocolVersion: "2025-03-26",
    capabilities: {},
    clientInfo: { name: "mcpwp-first-connection-deployer", version: "0.1" },
  });

  const pages = await callTool("wp_list_pages", {
    status: "draft",
    search: PAGE_TITLE,
    per_page: 20,
    fields: "id,title,slug,status,url,has_elementor,modified,template",
  });
  let page = pages.pages?.find((candidate) => candidate.title === PAGE_TITLE);

  if (!deploy) {
    console.log(JSON.stringify({ deploy: false, pageFound: Boolean(page), page, next: `Run: node ${process.argv[1]} --deploy` }, null, 2));
    return;
  }

  if (!page) {
    page = await callTool("wp_create_page", { title: PAGE_TITLE, slug: PAGE_SLUG, status: "draft", content: "" });
    page = page.page ?? page;
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
