import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const deploy = process.argv.includes("--deploy");

const pages = [
  {
    id: 259,
    title: "MCPWP Tools Reference | WordPress MCP Capabilities",
    description: "Explore MCPWP tool categories for WordPress content, Elementor, SEO, media, menus, admin, and integrations. Tools are discovered dynamically from each site.",
    focus: "WordPress MCP tools",
  },
  {
    id: 263,
    title: "Elementor MCP | AI Page Builder Workflows for WordPress",
    description: "Use MCPWP to inspect, build, patch, and validate Elementor pages through Claude, Cursor, Codex, and other MCP clients.",
    focus: "Elementor MCP",
  },
  {
    id: 261,
    title: "WordPress Content Management via MCP | MCPWP",
    description: "Create, update, organize, and review WordPress pages and posts through MCPWP with scoped AI access and live site context.",
    focus: "WordPress content MCP",
  },
  {
    id: 265,
    title: "Elementor Theme Builder via MCP | MCPWP",
    description: "Coordinate Elementor templates, reusable parts, and design structures with MCPWP while keeping high-impact theme workflows scoped and reviewable.",
    focus: "Elementor theme builder MCP",
  },
  {
    id: 267,
    title: "WordPress Webhooks and Automation via MCP | MCPWP",
    description: "Use MCPWP to connect WordPress site operations with event-aware automation, webhook workflows, scoped access, and observable changes.",
    focus: "WordPress automation MCP",
  },
  {
    id: 269,
    title: "WordPress MCP API Key Security | MCPWP",
    description: "Learn how MCPWP uses scoped API keys, role-aware access, revocation, and audit-friendly workflows for safer WordPress AI operations.",
    focus: "WordPress MCP security",
  },
  {
    id: 271,
    title: "Gutenberg Blocks via MCP | MCPWP",
    description: "Use MCPWP with Gutenberg for blog and editorial workflows while keeping product and funnel pages Elementor-first.",
    focus: "Gutenberg MCP",
  },
  {
    id: 273,
    title: "WordPress Menu Management via MCP | MCPWP",
    description: "Inspect, add, update, reorder, and verify WordPress navigation menus through MCPWP without losing track of site structure.",
    focus: "WordPress menu MCP",
  },
  {
    id: 275,
    title: "WordPress Widgets and Sidebars via MCP | MCPWP",
    description: "Coordinate widget and sidebar workflows through MCPWP where the active WordPress theme and plugin stack supports them.",
    focus: "WordPress widgets MCP",
  },
  {
    id: 277,
    title: "WordPress Multilingual Content via MCP | MCPWP",
    description: "Plan multilingual WordPress workflows with MCPWP around the active language plugin, translated content, metadata, and review process.",
    focus: "multilingual WordPress MCP",
  },
  {
    id: 239,
    title: "Codex and WordPress MCP | MCPWP",
    description: "Connect Codex to WordPress through MCPWP so code, content, page updates, and live verification can happen in one controlled workflow.",
    focus: "Codex WordPress MCP",
  },
  {
    id: 237,
    title: "Claude Code and WordPress MCP | MCPWP",
    description: "Use Claude Code with MCPWP to coordinate repository work, WordPress content, Elementor updates, and site verification.",
    focus: "Claude Code WordPress MCP",
  },
  {
    id: 240,
    title: "OpenClaw and WordPress MCP | MCPWP",
    description: "Give agent workflows a controlled WordPress MCP endpoint with structured tools, scoped access, and traceable site changes.",
    focus: "WordPress MCP agent",
  },
  {
    id: 238,
    title: "Team WordPress AI Workflows via MCP | MCPWP",
    description: "Use MCPWP to help teams coordinate WordPress content, design, SEO, and maintenance workflows through scoped AI clients.",
    focus: "WordPress AI workflow",
  },
  {
    id: 234,
    title: "Brand Consistency for WordPress AI Workflows | MCPWP",
    description: "Keep AI-generated WordPress pages on brand with MCPWP design references, reusable Elementor patterns, and visual verification.",
    focus: "WordPress AI brand consistency",
  },
  {
    id: 235,
    title: "Multi-Site WordPress Management with MCPWP",
    description: "Standardize MCPWP setup, scoped keys, site inspections, and repeatable AI workflows across multiple WordPress sites.",
    focus: "multi-site WordPress MCP",
  },
  {
    id: 236,
    title: "Bulk WordPress Publishing with AI | MCPWP",
    description: "Scale WordPress publishing with MCPWP while keeping drafts, metadata, taxonomy, internal links, and review workflows visible.",
    focus: "bulk WordPress publishing AI",
  },
  {
    id: 456,
    title: "Claude Code Plugin for MCPWP",
    description: "Use Claude Code alongside MCPWP to coordinate repository work, WordPress site operations, Elementor workflows, and launch verification.",
    focus: "Claude Code plugin MCPWP",
  },
  {
    id: 506,
    title: "MCPWP Demo | WordPress MCP Workflow",
    description: "See MCPWP inspect a WordPress site, apply a scoped page or content operation, and verify the rendered result through MCP.",
    focus: "WordPress MCP demo",
  },
  {
    id: 10,
    pageTitle: "MCPWP Blog",
    title: "MCPWP Blog | WordPress MCP and AI Site Operations",
    description: "Articles, guides, and launch notes for MCPWP, WordPress MCP workflows, Elementor operations, AI SEO, and safe site automation.",
    focus: "WordPress MCP blog",
  },
];

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
  await delay(2200);
  return parseToolResult(await rpc("tools/call", { name, arguments: args }));
}

async function setMeta(page, key, value) {
  return callTool("wp_set_post_meta", { id: page.id, key, value });
}

async function main() {
  await rpc("initialize", {
    protocolVersion: "2025-03-26",
    capabilities: {},
    clientInfo: { name: "mcpwp-related-seo-fixer", version: "0.1" },
  });

  if (!deploy) {
    console.log(JSON.stringify({ deploy: false, pageCount: pages.length, pages, next: `node ${process.argv[1]} --deploy` }, null, 2));
    return;
  }

  const results = [];
  for (const page of pages) {
    const metas = [];
    metas.push(await setMeta(page, "_yoast_wpseo_title", page.title));
    metas.push(await setMeta(page, "_yoast_wpseo_metadesc", page.description));
    metas.push(await setMeta(page, "_yoast_wpseo_focuskw", page.focus));
    const updated = page.pageTitle
      ? await callTool("wp_update_page", { id: page.id, title: page.pageTitle, status: "publish" })
      : null;
    results.push({
      id: page.id,
      title: page.title,
      focus: page.focus,
      metaUpdates: metas.map((item) => item?.success ?? item?.updated ?? item),
      pageTitle: updated?.title ?? null,
    });
  }

  console.log(JSON.stringify({ deploy: true, results }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
