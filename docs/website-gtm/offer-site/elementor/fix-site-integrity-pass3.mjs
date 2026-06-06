import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
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
  for (let attempt = 0; attempt < 12; attempt++) {
    const response = await fetch(config.url, {
      method: "POST",
      headers: { "Content-Type": "application/json", ...config.headers },
      body: JSON.stringify({ jsonrpc: "2.0", id: id++, method, params }),
    });
    const json = JSON.parse(await response.text());
    const rateLimit = json.code === "rate_limit_exceeded"
      || json.error?.code === "rate_limit_exceeded"
      || json.error?.data?.code === "rate_limit_exceeded";
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

function esc(value) {
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

function cards(items) {
  return items.map((item) => `
    <a class="mcpwp-flow-card" href="${esc(item.href)}">
      <span>${esc(item.kicker)}</span>
      <strong>${esc(item.title)}</strong>
      <p>${esc(item.text)}</p>
    </a>
  `).join("");
}

function docsData() {
  const customerPath = [
    { kicker: "Start", title: "Getting started", text: "Install the plugin, create a scoped key, and connect your first MCP client.", href: "/docs/getting-started/" },
    { kicker: "Download", title: "Current package", text: "Get the live versioned ZIP and continue into setup.", href: "/download/" },
    { kicker: "Pricing", title: "Plan fit", text: "Choose the access level that matches your WordPress workflow.", href: "/pricing/" },
  ];

  const coreReference = [
    { kicker: "Reference", title: "MCP tools", text: "Understand dynamic tool discovery, scopes, and capability categories.", href: "/docs/mcp-tools/" },
    { kicker: "API", title: "REST API", text: "Use the endpoint, key header, and compatibility namespace correctly.", href: "/docs/api-reference/" },
    { kicker: "Visual QA", title: "Screenshot Worker", text: "Use screenshots for visual verification and page review workflows.", href: "/docs/screenshot-worker/" },
    { kicker: "Builder", title: "Elementor MCP", text: "Inspect, patch, validate, and regenerate Elementor pages through MCP.", href: "/elementor-mcp/" },
    { kicker: "Security", title: "API key scopes", text: "Operate with read-first access, role controls, and logs.", href: "/api-security/" },
    { kicker: "Clients", title: "Codex and Claude Code", text: "Connect development agents to real WordPress operations.", href: "/wordpress-codex/" },
  ];

  const workflows = [
    { kicker: "Content", title: "Content management", text: "Draft, update, publish, and review WordPress content.", href: "/content-management/" },
    { kicker: "Publishing", title: "Bulk publishing", text: "Scale WordPress publishing without giving up review control.", href: "/bulk-wordpress-publishing/" },
    { kicker: "Brand", title: "Brand consistency", text: "Keep AI-generated WordPress pages aligned with a brand canon.", href: "/brand-canon/" },
    { kicker: "Menus", title: "Navigation menus", text: "Manage WordPress menus from an MCP client.", href: "/navigation-menus/" },
    { kicker: "Gutenberg", title: "Blocks and blog", text: "Use Gutenberg where it fits: editorial content and blog workflows.", href: "/gutenberg-blocks/" },
    { kicker: "Theme", title: "Theme Builder", text: "Coordinate Elementor theme workflows with AI assistance.", href: "/theme-builder/" },
    { kicker: "Widgets", title: "Widgets and sidebars", text: "Coordinate widgets and sidebars through controlled AI workflows.", href: "/widgets-sidebars/" },
    { kicker: "Automation", title: "Webhooks", text: "Connect WordPress operations to automation workflows.", href: "/webhooks-automation/" },
    { kicker: "Languages", title: "Multilingual", text: "Plan multilingual WordPress workflows around the active stack.", href: "/multilingual/" },
    { kicker: "Team", title: "Team workflows", text: "Let teams coordinate WordPress work with AI clients.", href: "/wordpress-cowork/" },
    { kicker: "Claude", title: "Claude Code", text: "Use Claude Code with WordPress through MCPWP.", href: "/wordpress-claude-code/" },
    { kicker: "Plugin", title: "Claude plugin", text: "Use Claude Code workflows for MCPWP.", href: "/claude-plugin/" },
    { kicker: "Agents", title: "OpenClaw", text: "Give agent workflows a controlled WordPress surface.", href: "/wordpress-openclaw/" },
    { kicker: "Agencies", title: "Agency operations", text: "Standardize safe MCP workflows across client sites.", href: "/agencies/" },
  ];

  const company = [
    { kicker: "Company", title: "About MCPWP", text: "Why the product exists and how it is built.", href: "/about/" },
    { kicker: "Release", title: "Changelog", text: "Track product changes and release notes.", href: "/changelog/" },
    { kicker: "Legal", title: "Privacy and terms", text: "Data handling, license, and usage terms.", href: "/privacy-policy/" },
    { kicker: "Contact", title: "Contact", text: "Ask setup, support, agency, or commercial questions.", href: "/contact/" },
    { kicker: "Blog", title: "Field notes", text: "Read launch, SEO, setup, and operational notes.", href: "/blog/" },
  ];

  return [
    section("docs-hero", `<div class="mcpwp-flow"><section class="mcpwp-flow-hero"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">Documentation</span><h1>MCPWP docs for the first successful connection.</h1><p class="mcpwp-flow-lead">Start with the live package, configure a scoped API key, inspect the site, then expand into Elementor, SEO, content, media, menus, and operational workflows.</p><div class="mcpwp-flow-actions"><a class="mcpwp-flow-btn mcpwp-flow-btn-primary" href="/download/">Download MCPWP</a><a class="mcpwp-flow-btn" href="/docs/getting-started/">Start setup</a><a class="mcpwp-flow-btn" href="/pricing/">Compare plans</a></div></div></section></div>`),
    section("docs-primary", `<div class="mcpwp-flow"><section class="mcpwp-flow-section"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">Path</span><h2>The customer path</h2><div class="mcpwp-flow-grid mcpwp-flow-grid-3">${cards(customerPath)}</div></div></section></div>`),
    section("docs-reference", `<div class="mcpwp-flow"><section class="mcpwp-flow-section"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">Reference</span><h2>Core setup and references</h2><div class="mcpwp-flow-grid">${cards(coreReference)}</div></div></section></div>`),
    section("docs-workflows", `<div class="mcpwp-flow"><section class="mcpwp-flow-section"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">Workflows</span><h2>Everything should be reachable from one map.</h2><div class="mcpwp-flow-grid">${cards(workflows)}</div></div></section></div>`),
    section("docs-company", `<div class="mcpwp-flow"><section class="mcpwp-flow-section"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">Company</span><h2>Trust, releases, and contact paths</h2><div class="mcpwp-flow-grid">${cards(company)}</div></div></section></div>`),
    section("docs-config", `<div class="mcpwp-flow"><section class="mcpwp-flow-section"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">Client config</span><h2>Start every setup with read-only inspection.</h2><pre class="mcpwp-flow-code">"mcpwp": {
  "url": "https://your-site.com/wp-json/site-pilot-ai/v1/mcp",
  "headers": { "X-API-Key": "spai_your_scoped_key" }
}</pre><p class="mcpwp-flow-muted">The REST namespace remains <code>site-pilot-ai</code> for compatibility. Public product copy should use MCPWP.</p><div class="mcpwp-flow-actions"><a class="mcpwp-flow-btn mcpwp-flow-btn-primary" href="/docs/getting-started/">Read setup guide</a><a class="mcpwp-flow-btn" href="https://github.com/Mumega-com/mcpwp/issues" target="_blank" rel="noopener">GitHub issues</a></div></div></section></div>`),
  ];
}

async function setElementorPage(pageId, data) {
  const elementor_data_base64 = Buffer.from(JSON.stringify(data), "utf8").toString("base64");
  const dryRun = await callTool("wp_set_elementor", { id: pageId, elementor_data_base64, dry_run: true });
  const saved = await callTool("wp_set_elementor", { id: pageId, elementor_data_base64, dry_run: false });
  await callTool("wp_update_page_template", { id: pageId, template: "elementor_header_footer" });
  return { dryRun, saved };
}

async function setMeta(pageId, title, description, focus) {
  return Promise.all([
    callTool("wp_set_post_meta", { id: pageId, key: "_yoast_wpseo_title", value: title }),
    callTool("wp_set_post_meta", { id: pageId, key: "_yoast_wpseo_metadesc", value: description }),
    callTool("wp_set_post_meta", { id: pageId, key: "_yoast_wpseo_focuskw", value: focus }),
  ]);
}

async function replaceAll(replacements) {
  const results = [];
  for (const replacement of replacements) {
    results.push(await callTool("wp_bulk_find_replace", replacement));
  }
  return results;
}

async function main() {
  await rpc("initialize", {
    protocolVersion: "2025-03-26",
    capabilities: {},
    clientInfo: { name: "mcpwp-site-integrity-pass3", version: "0.1" },
  });

  const frontPage = await callTool("wp_get_option", { key: "page_on_front" });
  const duplicateHome = frontPage?.value === "541"
    ? await callTool("wp_update_page", { id: 95, status: "draft" })
    : { skipped: true, reason: "front page option did not match expected page 541" };

  const replacements = await replaceAll([
    { id: 8, search: "mumcp", replace: "MCPWP" },
    { id: 8, search: "https://github.com/Mumega-com/mcp-for-wpwp-ai-operator", replace: "https://github.com/Mumega-com/mcpwp" },
    { id: 117, search: "mumcp", replace: "MCPWP" },
    { id: 117, search: "https://github.com/Mumega-com/mcp-for-wpwp-ai-operator", replace: "https://github.com/Mumega-com/mcpwp" },
    { id: 118, search: "mumcp", replace: "MCPWP" },
    { id: 95, search: "mumcp", replace: "MCPWP" },
    { id: 95, search: "239 MCP Tools", replace: "WordPress MCP" },
    { id: 95, search: "239 MCP tools", replace: "dynamic MCP tools" },
  ]);

  const docs = await setElementorPage(113, docsData());

  const meta = await Promise.all([
    setMeta(95, "MCPWP | WordPress MCP for AI Site Operations", "MCPWP connects AI clients to WordPress through scoped MCP access for content, Elementor, SEO, media, menus, and safe site operations.", "WordPress MCP"),
    setMeta(8, "About MCPWP | WordPress MCP Operations", "MCPWP is built for operators who want AI clients to inspect and operate WordPress through scoped MCP access.", "about MCPWP"),
    setMeta(117, "MCPWP Changelog | WordPress MCP Releases", "Track MCPWP release notes, product changes, workflow improvements, and WordPress MCP updates.", "MCPWP changelog"),
    setMeta(118, "MCPWP Privacy Policy and Terms", "Read MCPWP privacy, data handling, GPL license, and usage terms for the WordPress MCP plugin.", "MCPWP privacy policy"),
    setMeta(113, "MCPWP Documentation | WordPress MCP Setup and Workflow Map", "Use MCPWP docs to download the current package, connect an MCP client, and explore WordPress MCP workflows for Elementor, content, SEO, and agencies.", "MCPWP documentation"),
  ]);

  const css = await callTool("wp_regenerate_elementor_css", {});

  console.log(JSON.stringify({
    frontPage,
    duplicateHome,
    replacements,
    docs,
    meta: meta.flat().map((item) => item?.success ?? item?.updated ?? item),
    css,
  }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
