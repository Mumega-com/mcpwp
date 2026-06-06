import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const CSS_MARKER = "MCPWP related page Elementor CSS - 2026-06-05";
const deploy = process.argv.includes("--deploy");

const css = `
/* ${CSS_MARKER} */
.mcpwp-related{--bg:#050812;--panel:#101827;--line:#25334c;--text:#f7f9ff;--muted:#a8b4c7;--faint:#728096;--blue:#4d86ff;--cyan:#4be3c2;background:radial-gradient(circle at 16% 0%,rgba(77,134,255,.2),transparent 32%),linear-gradient(180deg,#050812,#070b14);color:var(--text);font-family:"IBM Plex Sans","Space Grotesk",system-ui,sans-serif;overflow:hidden}.mcpwp-related *{box-sizing:border-box}.mcpwp-related-wrap{max-width:1120px;margin:0 auto;padding:0 28px}.mcpwp-related-hero{padding:108px 0 74px}.mcpwp-related-kicker{display:inline-flex;gap:10px;align-items:center;color:var(--cyan);font:800 12px/1 "JetBrains Mono",monospace;letter-spacing:.16em;text-transform:uppercase}.mcpwp-related-kicker:before{content:"";width:24px;height:1px;background:var(--cyan)}.mcpwp-related h1,.mcpwp-related h2,.mcpwp-related h3{font-family:"Space Grotesk",system-ui,sans-serif;letter-spacing:-.045em;line-height:1.04}.mcpwp-related h1{max-width:900px;font-size:clamp(44px,7vw,78px);margin:22px 0}.mcpwp-related h2{font-size:clamp(30px,4.4vw,50px);margin:0 0 16px}.mcpwp-related h3{font-size:24px;margin:0 0 10px}.mcpwp-related p{color:var(--muted);font-size:18px;line-height:1.65}.mcpwp-related-lead{max-width:780px;font-size:clamp(20px,2.2vw,25px)!important}.mcpwp-related-grad{background:linear-gradient(100deg,#fff 22%,#78a5ff 62%,#4be3c2);-webkit-background-clip:text;background-clip:text;color:transparent}.mcpwp-related-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:30px}.mcpwp-related-btn{display:inline-flex;align-items:center;justify-content:center;padding:15px 22px;border-radius:14px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.07);color:#fff!important;text-decoration:none!important;font-weight:800}.mcpwp-related-btn-primary{background:linear-gradient(135deg,var(--blue),#2f6ef7);border-color:#78a5ff}.mcpwp-related-section{padding:78px 0;border-top:1px solid rgba(255,255,255,.08)}.mcpwp-related-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.mcpwp-related-card{padding:24px;border:1px solid rgba(255,255,255,.1);border-radius:20px;background:linear-gradient(180deg,rgba(255,255,255,.055),rgba(255,255,255,.025))}.mcpwp-related-card strong{display:block;color:#fff;font-size:18px;margin-bottom:8px}.mcpwp-related-card span{display:block;color:var(--muted);line-height:1.55}.mcpwp-related-note{padding:24px;border-radius:20px;background:rgba(75,227,194,.07);border:1px solid rgba(75,227,194,.16);color:#d9fff6;font-weight:800}.mcpwp-related-note span{display:block;color:var(--muted);font-weight:500;margin-top:8px}.mcpwp-related-code{display:block;max-width:100%;margin-top:18px;padding:18px 20px;border-radius:16px;background:#070b12;border:1px solid rgba(255,255,255,.12);color:#d5e4ff;font:600 13px/1.7 "JetBrains Mono",monospace;overflow-x:auto;white-space:pre-wrap;word-break:break-word}@media(max-width:860px){.mcpwp-related-grid{grid-template-columns:1fr}.mcpwp-related-wrap{padding:0 20px}.mcpwp-related-hero{padding:78px 0 56px}}@media(max-width:560px){.mcpwp-related-btn{width:100%}.mcpwp-related h1{font-size:42px}}
`;

const pages = [
  {
    id: 259,
    title: "MCPWP Tools Reference",
    slug: "tools",
    kicker: "Tools",
    h1: "Dynamic WordPress MCP tools for real site operations.",
    lead: "MCPWP exposes tools based on the live WordPress site: active plugins, enabled categories, license or build state, and the scopes attached to the API key.",
    cards: [
      ["Dynamic discovery", "Do not depend on a fixed public count. Ask the connected site for its current tools/list result."],
      ["Scoped access", "Different keys can expose different workflows for content, design, SEO, media, commerce, or admin work."],
      ["Operational breadth", "Use tools for pages, posts, Elementor, media, menus, metadata, settings, and integrations when available."],
    ],
    note: "Use this page as the durable tools reference. Counts change; capability categories and safe discovery are the product promise.",
    code: 'First command: "Inspect this WordPress site and list the MCPWP tools available to my current key."',
  },
  {
    id: 263,
    title: "Elementor MCP",
    slug: "elementor-mcp",
    kicker: "Elementor",
    h1: "Control Elementor pages through MCP.",
    lead: "MCPWP lets AI clients inspect, build, patch, and validate Elementor layouts without treating WordPress as a static mockup.",
    cards: [
      ["Read real layouts", "Inspect Elementor page data, widgets, sections, and reusable structures before changing anything."],
      ["Patch safely", "Edit sections or widgets with validation, cache clearing, and CSS regeneration."],
      ["Build from patterns", "Use reusable page and section patterns for hero, pricing, FAQ, CTA, service, and product pages."],
    ],
    note: "Elementor workflows should start with inspection, then a proposed diff, then save/regenerate only after the desired change is clear.",
  },
  {
    id: 261,
    title: "WordPress Content Management via MCP",
    slug: "content-management",
    kicker: "Content",
    h1: "Manage WordPress content from an AI client.",
    lead: "Create, update, organize, and review WordPress pages and posts through a scoped MCP connection.",
    cards: [
      ["Pages and posts", "Draft, update, publish, or revise content while preserving WordPress-native data."],
      ["Taxonomy workflows", "Manage categories, tags, and content organization from the same conversation."],
      ["Review before write", "Start read-only, inspect content, then apply controlled changes with the right key scope."],
    ],
    note: "For launch work, content operations should feed conversion pages, setup docs, and SEO landing pages.",
  },
  {
    id: 265,
    title: "Elementor Theme Builder via MCP",
    slug: "theme-builder",
    kicker: "Theme builder",
    h1: "Operate Elementor theme workflows with AI assistance.",
    lead: "Use MCPWP to inspect and coordinate Elementor theme templates, reusable parts, and site design structures.",
    cards: [
      ["Template awareness", "Understand what templates and reusable parts exist before creating new ones."],
      ["Safer changes", "Keep AI changes scoped and reviewable instead of editing theme behavior blindly."],
      ["Design consistency", "Reuse established visual patterns across landing pages, docs, and product pages."],
    ],
    note: "Theme-builder workflows are high impact. Treat them as approval-first operations.",
  },
  {
    id: 267,
    title: "WordPress Webhooks and Automation via MCP",
    slug: "webhooks-automation",
    kicker: "Automation",
    h1: "Connect WordPress operations to automation workflows.",
    lead: "MCPWP exposes operational context and events so AI-assisted changes can fit into real agency and site workflows.",
    cards: [
      ["Event awareness", "Understand site events and workflow triggers before automating around them."],
      ["Webhook operations", "Create, test, and inspect webhook flows when the feature is available."],
      ["Human boundaries", "Keep destructive or sensitive automations behind review, scopes, and logs."],
    ],
    note: "Automation is valuable only when it is observable. MCPWP should keep the audit trail close to every workflow.",
  },
  {
    id: 269,
    title: "WordPress API Key Management and Security",
    slug: "api-security",
    kicker: "Security",
    h1: "Scoped keys for safer WordPress AI operations.",
    lead: "MCPWP uses API keys and workflow scopes so each AI client gets only the capabilities it needs.",
    cards: [
      ["Least privilege", "Start read-only or category-limited, then expand only when the workflow requires write access."],
      ["Role-aware access", "Separate editor, designer, admin, and custom operational permissions."],
      ["Revocation path", "Rotate or revoke keys when a client, project, or contractor no longer needs access."],
    ],
    note: "Security is a conversion feature. Buyers need to see that AI access is controlled, auditable, and reversible.",
  },
  {
    id: 271,
    title: "Gutenberg Block Management via MCP",
    slug: "gutenberg-blocks",
    kicker: "Gutenberg",
    h1: "Use Gutenberg where it fits: content and blog workflows.",
    lead: "MCPWP can work with WordPress blocks for posts, docs, and structured content while keeping landing pages in Elementor.",
    cards: [
      ["Blog-native", "Use Gutenberg for articles and editorial content where the block editor is the right tool."],
      ["Validation", "Parse, validate, and serialize blocks before saving changes."],
      ["Hybrid site", "Keep marketing pages Elementor-first and blog/docs content block-friendly."],
    ],
    note: "This page supports the site rule: Elementor for product/funnel pages, Gutenberg for blog/editorial content.",
  },
  {
    id: 273,
    title: "WordPress Menu Management via MCP",
    slug: "navigation-menus",
    kicker: "Navigation",
    h1: "Manage WordPress menus from an MCP client.",
    lead: "Inspect, add, update, reorder, and assign navigation items through MCPWP without losing track of the site structure.",
    cards: [
      ["List menus", "See menus, locations, and current items before making changes."],
      ["Update safely", "Rename links, change URLs, or reorder items with explicit IDs."],
      ["Launch hygiene", "Use menu tools to remove stale domains and align the funnel."],
    ],
    note: "Navigation changes are conversion-critical. Verify rendered links after every menu update.",
  },
  {
    id: 275,
    title: "WordPress Widgets and Sidebars via MCP",
    slug: "widgets-sidebars",
    kicker: "Widgets",
    h1: "Coordinate widgets and sidebars through controlled AI workflows.",
    lead: "MCPWP can expose widget/sidebar operations where the active WordPress setup supports them.",
    cards: [
      ["Inventory", "Inspect widget areas before changing site chrome or utility sections."],
      ["Scoped edits", "Keep sidebar changes separate from content and builder changes."],
      ["Theme-aware", "Treat widget workflows as dependent on the active theme and plugin stack."],
    ],
    note: "Widget support should be presented as site-aware, not universal across every theme.",
  },
  {
    id: 277,
    title: "WordPress Multilingual Content via MCP",
    slug: "multilingual",
    kicker: "Multilingual",
    h1: "Plan multilingual WordPress workflows around the active stack.",
    lead: "MCPWP can help AI clients inspect language setup and coordinate translated content when multilingual plugins are present.",
    cards: [
      ["Plugin-aware", "Capabilities depend on WPML, Polylang, TranslatePress, or the multilingual system installed."],
      ["Content mapping", "Keep source and translated pages linked conceptually before writing."],
      ["SEO care", "Review slugs, canonicals, and localized metadata as part of the workflow."],
    ],
    note: "Multilingual AI workflows need explicit review; do not blindly auto-publish translations.",
  },
  {
    id: 239,
    title: "Codex and WordPress MCP",
    slug: "wordpress-codex",
    kicker: "Codex",
    h1: "Connect Codex to WordPress through MCPWP.",
    lead: "Use Codex for code and WordPress operations in the same workflow: inspect the site, update content, and validate changes through MCP.",
    cards: [
      ["Code plus content", "Let Codex work on repo changes and update matching WordPress pages or docs."],
      ["Live verification", "Inspect rendered pages after changes instead of assuming the site matches the code."],
      ["Scoped site access", "Use a key that matches the task: read-only, content, design, or admin."],
    ],
    note: "Codex should start by listing available MCPWP tools and site context before making writes.",
  },
  {
    id: 237,
    title: "Claude Code and WordPress MCP",
    slug: "wordpress-claude-code",
    kicker: "Claude Code",
    h1: "Use Claude Code with WordPress through MCPWP.",
    lead: "Connect Claude Code to a live WordPress site so development, content, and page operations happen in one controlled workflow.",
    cards: [
      ["Developer workflow", "Coordinate code changes with WordPress content and Elementor page updates."],
      ["Site inspection", "Read site state, active plugins, pages, menus, and available tools."],
      ["Safer publishing", "Use scoped keys and review changes before pushing them live."],
    ],
    note: "The strongest Claude Code story is not chat; it is repo-aware development plus site-aware operations.",
  },
  {
    id: 240,
    title: "OpenClaw and WordPress MCP",
    slug: "wordpress-openclaw",
    kicker: "OpenClaw",
    h1: "Give agent workflows a controlled WordPress surface.",
    lead: "MCPWP provides a WordPress MCP endpoint that agent frameworks can use to inspect and operate site workflows.",
    cards: [
      ["Structured tools", "Expose named WordPress operations instead of asking agents to improvise raw HTTP calls."],
      ["Operational limits", "Use key scopes and categories to constrain what an agent can do."],
      ["Traceable changes", "Keep site operations observable for review and rollback planning."],
    ],
    note: "Agent access should be boring and controlled. That is the selling point.",
  },
  {
    id: 238,
    title: "Team WordPress AI Workflows via MCP",
    slug: "wordpress-cowork",
    kicker: "Teams",
    h1: "Let teams coordinate WordPress work with AI clients.",
    lead: "MCPWP gives teams a shared operational layer for content, design, SEO, and site maintenance workflows.",
    cards: [
      ["Shared context", "AI clients can inspect the same live WordPress state instead of relying on screenshots or stale docs."],
      ["Role separation", "Give editors, designers, and admins different key scopes."],
      ["Repeatable playbooks", "Turn common launch and maintenance tasks into repeatable workflows."],
    ],
    note: "For teams, MCPWP should reduce handoff friction without removing human approval.",
  },
  {
    id: 234,
    title: "Brand Consistency for WordPress AI Workflows",
    slug: "brand-canon",
    kicker: "Brand",
    h1: "Keep AI-generated WordPress pages on brand.",
    lead: "MCPWP workflows can use design references, reusable patterns, and site context so generated pages feel like the same product.",
    cards: [
      ["Design references", "Store and reuse visual direction instead of starting every prompt from zero."],
      ["Reusable sections", "Build from proven Elementor patterns for hero, pricing, FAQ, and CTA blocks."],
      ["Review loops", "Verify rendered pages visually and textually before publishing."],
    ],
    note: "Brand consistency is an operational workflow, not a prompt trick.",
  },
  {
    id: 235,
    title: "Multi-Site WordPress Management with MCPWP",
    slug: "multi-site-management",
    kicker: "Multi-site",
    h1: "Manage multiple WordPress sites with repeatable AI workflows.",
    lead: "Use MCPWP as the site-level endpoint, then standardize scopes, setup, and checks across client or portfolio sites.",
    cards: [
      ["Site-by-site keys", "Each WordPress site should have its own endpoint and scoped API keys."],
      ["Standard checks", "Inspect plugins, pages, menus, SEO, and media before making changes."],
      ["Agency rollout", "Use playbooks for consistent setup and review across accounts."],
    ],
    note: "The conversion target for agencies is confidence: repeatability, safety, and proof on the first site.",
  },
  {
    id: 236,
    title: "Bulk WordPress Publishing with AI",
    slug: "bulk-wordpress-publishing",
    kicker: "Publishing",
    h1: "Scale WordPress publishing without losing control.",
    lead: "MCPWP can support bulk content workflows while keeping review, metadata, and site structure visible.",
    cards: [
      ["Draft first", "Generate or update drafts before publishing production content."],
      ["Metadata included", "Treat title, excerpt, SEO fields, taxonomy, and internal links as part of the job."],
      ["Batch review", "Summarize changes and validate pages before publishing in volume."],
    ],
    note: "Bulk publishing should optimize throughput, not bypass editorial judgment.",
  },
  {
    id: 456,
    title: "Claude Code Plugin for MCPWP",
    slug: "claude-plugin",
    kicker: "Claude Code",
    h1: "Claude Code workflows for MCPWP.",
    lead: "Use Claude Code alongside MCPWP to coordinate repository work, WordPress site operations, and launch verification.",
    cards: [
      ["Setup guidance", "Keep MCP server configuration and site endpoint details close to the project."],
      ["Builder workflows", "Use structured prompts and tools for Elementor, docs, and content tasks."],
      ["Verification", "Pair code changes with browser and WordPress MCP checks."],
    ],
    note: "Keep install commands current in docs; do not hard-code stale plugin names on the marketing site.",
  },
  {
    id: 506,
    title: "MCPWP Demo",
    slug: "demo",
    kicker: "Demo",
    h1: "See MCPWP operate a real WordPress workflow.",
    lead: "The demo should show the loop that matters: inspect the site, propose the change, apply through MCPWP, then verify the rendered WordPress result.",
    cards: [
      ["Inspect", "The AI reads site context, active plugins, and available tools."],
      ["Operate", "MCPWP applies a scoped page, content, SEO, media, or menu change."],
      ["Verify", "The browser and MCP tools confirm the result on the live site."],
    ],
    note: "A credible demo is not a mockup. It is a visible before/after on WordPress.",
  },
];

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;");
}

function pageHtml(page) {
  const cards = page.cards
    .map(([title, body]) => `<div class="mcpwp-related-card"><strong>${escapeHtml(title)}</strong><span>${escapeHtml(body)}</span></div>`)
    .join("");
  const code = page.code ? `<pre class="mcpwp-related-code">${escapeHtml(page.code)}</pre>` : "";
  return `<div class="mcpwp-related" data-related-page="${escapeHtml(page.slug)}">
  <section class="mcpwp-related-hero"><div class="mcpwp-related-wrap">
    <span class="mcpwp-related-kicker">${escapeHtml(page.kicker)}</span>
    <h1>${escapeHtml(page.h1).replace(/MCPWP|WordPress|Elementor|Codex|Claude Code/g, '<span class="mcpwp-related-grad">$&</span>')}</h1>
    <p class="mcpwp-related-lead">${escapeHtml(page.lead)}</p>
    <div class="mcpwp-related-actions"><a class="mcpwp-related-btn mcpwp-related-btn-primary" href="/get-started/">Connect your first site</a><a class="mcpwp-related-btn" href="/pricing/">See pricing</a></div>
  </div></section>
  <section class="mcpwp-related-section"><div class="mcpwp-related-wrap"><div class="mcpwp-related-grid">${cards}</div></div></section>
  <section class="mcpwp-related-section"><div class="mcpwp-related-wrap"><div class="mcpwp-related-note">${escapeHtml(page.note)}<span>Use MCPWP as a controlled WordPress operating layer: inspect first, scope access, then apply and verify.</span></div>${code}</div></section>
</div>`;
}

function elementorData(page) {
  const rand = () => Math.random().toString(36).slice(2, 10);
  return [
    {
      id: rand(),
      elType: "section",
      settings: { _css_classes: `mcpwp-related-block mcpwp-related-block-${page.slug}` },
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
              settings: { html: pageHtml(page) },
              elements: [],
            },
          ],
        },
      ],
    },
  ];
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
  for (let attempt = 0; attempt < 8; attempt++) {
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

async function main() {
  await rpc("initialize", {
    protocolVersion: "2025-03-26",
    capabilities: {},
    clientInfo: { name: "mcpwp-related-page-converter", version: "0.1" },
  });

  if (!deploy) {
    console.log(JSON.stringify({ deploy: false, pageCount: pages.length, pages: pages.map(({ id, title, slug }) => ({ id, title, slug })), next: `node ${process.argv[1]} --deploy` }, null, 2));
    return;
  }

  const currentCss = await callTool("wp_get_custom_css", {});
  const cssText = typeof currentCss === "string" ? currentCss : currentCss.css ?? currentCss.custom_css ?? "";
  if (!cssText.includes(CSS_MARKER)) {
    await callTool("wp_set_custom_css", { css, mode: "append" });
  }

  const results = [];
  for (const page of pages) {
    const elementorPayload = JSON.stringify(elementorData(page));
    const elementor_data_base64 = Buffer.from(elementorPayload, "utf8").toString("base64");
    const dryRun = await callTool("wp_set_elementor", {
      id: page.id,
      elementor_data_base64,
      dry_run: true,
    });
    const saved = await callTool("wp_set_elementor", {
      id: page.id,
      elementor_data_base64,
      dry_run: false,
    });
    const template = await callTool("wp_update_page_template", {
      id: page.id,
      template: "elementor_header_footer",
    });
    const updated = await callTool("wp_update_page", {
      id: page.id,
      title: page.title,
      slug: page.slug,
      status: "publish",
    });
    results.push({
      id: page.id,
      slug: page.slug,
      title: page.title,
      dryRun: Boolean(dryRun?.success),
      savedSections: saved?.sections_saved ?? null,
      template: template?.template ?? template,
      updatedTitle: updated?.title ?? null,
    });
  }

  console.log(JSON.stringify({ deploy: true, results }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
