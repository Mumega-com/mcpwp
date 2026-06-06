import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const CSS_MARKER = "MCPWP site integrity CSS - 2026-06-05";
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
  for (let attempt = 0; attempt < 10; attempt++) {
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
  return String(value).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;");
}

function widget(html) {
  return { id: rand(), elType: "widget", widgetType: "html", settings: { html }, elements: [] };
}

function section(name, html) {
  return {
    id: rand(),
    elType: "section",
    settings: { _css_classes: `mcpwp-flow-block mcpwp-flow-block-${name}` },
    elements: [{ id: rand(), elType: "column", settings: { _column_size: 100 }, elements: [widget(html)] }],
  };
}

function cards(items) {
  return items.map((item) => `<a class="mcpwp-flow-card" href="${esc(item.href || "#")}"><span>${esc(item.kicker)}</span><strong>${esc(item.title)}</strong><p>${esc(item.text)}</p></a>`).join("");
}

function page({ kicker, h1, lead, primaryHref = "/download/", primary = "Download MCPWP", secondaryHref = "/docs/", secondary = "Read docs", blocks = [], code = "" }) {
  const sections = [
    section("hero", `<div class="mcpwp-flow"><section class="mcpwp-flow-hero"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">${esc(kicker)}</span><h1>${esc(h1)}</h1><p class="mcpwp-flow-lead">${esc(lead)}</p><div class="mcpwp-flow-actions"><a class="mcpwp-flow-btn mcpwp-flow-btn-primary" href="${esc(primaryHref)}">${esc(primary)}</a><a class="mcpwp-flow-btn" href="${esc(secondaryHref)}">${esc(secondary)}</a><a class="mcpwp-flow-btn" href="/pricing/">Compare plans</a></div></div></section></div>`),
  ];
  if (blocks.length) {
    sections.push(section("cards", `<div class="mcpwp-flow"><section class="mcpwp-flow-section"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">Flow</span><h2>What this page is for</h2><div class="mcpwp-flow-grid">${cards(blocks)}</div></div></section></div>`));
  }
  if (code) {
    sections.push(section("code", `<div class="mcpwp-flow"><section class="mcpwp-flow-section"><div class="mcpwp-flow-wrap"><span class="mcpwp-flow-kicker">Reference</span><h2>Use this in the first session</h2><pre class="mcpwp-flow-code">${esc(code)}</pre></div></section></div>`));
  }
  sections.push(section("handoff", `<div class="mcpwp-flow"><section class="mcpwp-flow-section"><div class="mcpwp-flow-wrap"><div class="mcpwp-flow-note">Every page should move the visitor forward.<span>For MCPWP, that means pricing clarity, a live package download, setup, and a first read-only MCP response.</span></div><div class="mcpwp-flow-actions"><a class="mcpwp-flow-btn mcpwp-flow-btn-primary" href="/download/">Get the current ZIP</a><a class="mcpwp-flow-btn" href="/docs/getting-started/">Continue setup</a></div></div></section></div>`));
  return sections;
}

async function setElementor(id, data) {
  const elementor_data_base64 = Buffer.from(JSON.stringify(data), "utf8").toString("base64");
  const dryRun = await callTool("wp_set_elementor", { id, elementor_data_base64, dry_run: true });
  const saved = await callTool("wp_set_elementor", { id, elementor_data_base64, dry_run: false });
  await callTool("wp_update_page_template", { id, template: "elementor_header_footer" });
  return { dryRun, saved };
}

async function setMeta(id, title, description, focus) {
  return Promise.all([
    callTool("wp_set_post_meta", { id, key: "_yoast_wpseo_title", value: title }),
    callTool("wp_set_post_meta", { id, key: "_yoast_wpseo_metadesc", value: description }),
    callTool("wp_set_post_meta", { id, key: "_yoast_wpseo_focuskw", value: focus }),
  ]);
}

async function main() {
  await rpc("initialize", { protocolVersion: "2025-03-26", capabilities: {}, clientInfo: { name: "mcpwp-integrity-pass2", version: "0.1" } });

  const pages = [
    {
      id: 114,
      meta: ["MCPWP Getting Started | WordPress MCP Setup", "Install MCPWP, create a scoped API key, connect an MCP client, and run the first read-only WordPress inspection.", "MCPWP setup"],
      data: page({
        kicker: "Getting started",
        h1: "Set up MCPWP in one read-first session.",
        lead: "Install the current package, create a scoped API key in WordPress Admin, connect your MCP client, and inspect the site before any write operation.",
        primaryHref: "/download/",
        secondaryHref: "/docs/mcp-tools/",
        secondary: "Review tools",
        blocks: [
          { kicker: "01", title: "Download", text: "Use the live versioned package link so the ZIP is current.", href: "/download/" },
          { kicker: "02", title: "Create a key", text: "Start with a read-only or tightly scoped key in MCPWP settings.", href: "/api-security/" },
          { kicker: "03", title: "Connect client", text: "Add the MCP endpoint to Codex, Claude Code, Claude Desktop, Cursor, or Windsurf.", href: "/wordpress-codex/" },
          { kicker: "04", title: "Inspect first", text: "Ask the AI client to list site state and available MCPWP tools before writes.", href: "/docs/mcp-tools/" },
        ],
        code: `"mcpwp": {
  "url": "https://your-site.com/wp-json/site-pilot-ai/v1/mcp",
  "headers": { "X-API-Key": "spai_your_scoped_key" }
}`,
      }),
    },
    {
      id: 115,
      meta: ["MCPWP Tools Reference | Dynamic WordPress MCP Tools", "Understand MCPWP dynamic tool discovery, capability categories, scopes, and safe read-first workflows for WordPress AI operations.", "MCPWP tools"],
      data: page({
        kicker: "Tools",
        h1: "MCPWP tools are discovered from the connected site.",
        lead: "Do not depend on a fixed public tool count. Available tools vary by plugin version, active plugins, enabled categories, license state, and API key scope.",
        primaryHref: "/docs/getting-started/",
        primary: "Start setup",
        secondaryHref: "/tools/",
        secondary: "Capability page",
        blocks: [
          { kicker: "Content", title: "Pages and posts", text: "Inspect, draft, update, publish, and organize WordPress content.", href: "/content-management/" },
          { kicker: "Builder", title: "Elementor", text: "Read, patch, validate, and regenerate Elementor pages.", href: "/elementor-mcp/" },
          { kicker: "Security", title: "Scopes and logs", text: "Use category controls, role-aware access, and activity logs.", href: "/api-security/" },
          { kicker: "Operations", title: "Menus, media, SEO", text: "Coordinate site operations when the connected site exposes them.", href: "/features/" },
        ],
        code: `First prompt:
"Inspect this WordPress site through MCPWP. List site info, enabled tool categories, and available tools for my current key. Do not write anything yet."`,
      }),
    },
    {
      id: 116,
      meta: ["MCPWP API Reference | WordPress MCP REST Endpoint", "Reference MCPWP endpoint compatibility, API key authentication, MCP routing, and REST patterns for connected WordPress AI clients.", "MCPWP API"],
      data: page({
        kicker: "API",
        h1: "MCPWP API reference for connected agents.",
        lead: "The public product is MCPWP. The REST namespace remains site-pilot-ai for compatibility with existing installs, configs, and package history.",
        primaryHref: "/docs/getting-started/",
        primary: "Setup guide",
        secondaryHref: "/docs/mcp-tools/",
        secondary: "Tool reference",
        blocks: [
          { kicker: "Endpoint", title: "MCP route", text: "Connect clients to /wp-json/site-pilot-ai/v1/mcp.", href: "/docs/getting-started/" },
          { kicker: "Auth", title: "API key header", text: "Use X-API-Key with a scoped spai_ key generated in MCPWP.", href: "/api-security/" },
          { kicker: "Clients", title: "MCP clients", text: "Use the same endpoint pattern for Codex, Claude, Cursor, and Windsurf.", href: "/wordpress-codex/" },
        ],
        code: `Endpoint:
https://your-site.com/wp-json/site-pilot-ai/v1/mcp

Header:
X-API-Key: spai_your_scoped_key`,
      }),
    },
    {
      id: 32,
      meta: ["MCPWP Features | WordPress MCP Workflows", "Explore MCPWP features for dynamic WordPress MCP tools, Elementor workflows, content, SEO, media, menus, scoped keys, and activity logs.", "MCPWP features"],
      data: page({
        kicker: "Features",
        h1: "WordPress MCP workflows with scoped AI control.",
        lead: "MCPWP turns WordPress into an MCP server so AI clients can inspect and operate real site workflows without hard-coding a static tool count.",
        secondaryHref: "/demo/",
        secondary: "View demo",
        blocks: [
          { kicker: "Discovery", title: "Dynamic tools", text: "Expose tools based on the live site, active plugins, and key scopes.", href: "/docs/mcp-tools/" },
          { kicker: "Builder", title: "Elementor operations", text: "Inspect, build, patch, and validate Elementor pages safely.", href: "/elementor-mcp/" },
          { kicker: "Content", title: "WordPress content", text: "Manage pages, posts, metadata, media, menus, and SEO workflows.", href: "/content-management/" },
          { kicker: "Safety", title: "Scoped access", text: "Start read-only, use role/category controls, and review logs.", href: "/api-security/" },
        ],
      }),
    },
    {
      id: 461,
      meta: ["MCPWP for Agencies | WordPress MCP Operations", "Use MCPWP to give agencies scoped, auditable WordPress MCP workflows for Elementor, content, SEO, client sites, and AI operations.", "WordPress MCP agency"],
      data: page({
        kicker: "Agencies",
        h1: "AI operations for WordPress agencies.",
        lead: "MCPWP gives agencies a controlled way to let AI clients inspect, update, and verify client WordPress sites through scoped MCP access.",
        primaryHref: "/pricing/",
        primary: "Compare agency plans",
        secondaryHref: "/download/",
        secondary: "Download MCPWP",
        blocks: [
          { kicker: "Client ops", title: "Launch and maintenance", text: "Refresh pages, menus, content, SEO, and media without losing auditability.", href: "/features/" },
          { kicker: "Builder", title: "Elementor workflows", text: "Use AI clients to inspect, patch, and validate Elementor pages.", href: "/elementor-mcp/" },
          { kicker: "Safety", title: "Separate scopes", text: "Give editors, designers, and admins different keys by job.", href: "/api-security/" },
          { kicker: "Rollout", title: "Repeatable setup", text: "Standardize first inspections and agency playbooks across sites.", href: "/multi-site-management/" },
        ],
      }),
    },
  ];

  const results = [];
  for (const pageDef of pages) {
    const elementor = await setElementor(pageDef.id, pageDef.data);
    const meta = await setMeta(pageDef.id, ...pageDef.meta);
    results.push({ id: pageDef.id, elementor, meta: meta.map((item) => item?.success ?? item?.updated ?? item) });
  }

  await callTool("wp_bulk_find_replace", { id: 338, search: "mumcp", replace: "MCPWP" });
  await callTool("wp_bulk_find_replace", { id: 338, search: "Mumega MCP", replace: "MCPWP" });
  await setMeta(338, "MCPWP Screenshot Worker Setup", "Configure screenshot worker support for MCPWP visual verification and site review workflows.", "MCPWP screenshot worker");

  const css = await callTool("wp_regenerate_elementor_css", {});
  console.log(JSON.stringify({ results, css }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
