import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const deploy = process.argv.includes("--deploy");

const posts = [
  {
    id: 626,
    title: "How We Turned a WordPress Plugin Into a Real AI Operator",
    slug: "wordpress-plugin-real-ai-operator",
    excerpt: "The difference between a chatbot wrapper and a commercially viable WordPress MCP product is whether it can actually operate the site safely.",
    focus: "WordPress AI operator",
    seoTitle: "How We Turned a WordPress Plugin Into a Real AI Operator | MCPWP",
    seoDesc: "A practical breakdown of how MCPWP became a real WordPress AI operator through scoped tools, safe writes, live verification, and clear workflow categories.",
    imagePrompt: "Editorial dark hero image for a WordPress AI operator product post, abstract dashboard, Elementor panels, terminal, blue and cyan lighting, premium SaaS blog illustration, high contrast, modern, not cartoonish",
    intro: "The line between a demo and a product is whether the system can do real work safely. MCPWP only became commercially credible when it stopped behaving like a prompt wrapper and started behaving like a controlled operator for WordPress.",
    sections: [
      ["What changed", [
        "The product stopped advertising itself as a vague AI helper and started exposing named operations: content, Elementor, SEO, media, menus, and site administration.",
        "That shift matters because the buyer can now see exactly what the system is designed to do."
      ]],
      ["Why operators win", [
        "Operators reduce uncertainty. They read the site, make a scoped change, and verify the result before the user has to trust it.",
        "That is a stronger commercial story than a generic chat interface that promises to help but cannot prove it."
      ]],
      ["What the site has to show", [
        "A launch-ready page has to show the live product shape, the workflows, the setup path, and the output.",
        "If the site does not show those things clearly, the product feels experimental instead of sellable."
      ]],
      ["Practical takeaway", [
        "Sell WordPress AI as controlled operations, not as an abstract assistant.",
        "That framing is clearer, safer, and easier to defend in a sales conversation."
      ]],
    ],
  },
  {
    id: 627,
    title: "Why We Kept Gutenberg for the Blog and Elementor for Everything Else",
    slug: "gutenberg-blog-elementor-product-pages",
    excerpt: "The blog should behave like an editorial system. The product and funnel pages should behave like a controlled conversion system.",
    focus: "Gutenberg blog Elementor",
    seoTitle: "Why Gutenberg Stays on the Blog and Elementor Owns the Funnel | MCPWP",
    seoDesc: "See why MCPWP uses Gutenberg for editorial posts and Elementor for product, pricing, docs, and conversion pages in the live site architecture.",
    imagePrompt: "Premium editorial blog image about WordPress content architecture, split composition showing Gutenberg editor on one side and Elementor page builder on the other, dark background, blue neon accents, modern SaaS illustration",
    intro: "We stopped trying to force every page into the same editing model. The blog belongs to Gutenberg because it is editorial. The product and funnel pages belong to Elementor because they are conversion surfaces.",
    sections: [
      ["Why the split works", [
        "Gutenberg is efficient for structured writing, articles, and documentation-style content.",
        "Elementor is stronger for landing pages, feature grids, pricing blocks, hero sections, and CTA-heavy layouts."
      ]],
      ["Why it matters commercially", [
        "Buyers can tell when a site was built with the right tool for the job.",
        "A mixed content model signals that the product understands how WordPress actually gets used."
      ]],
      ["What the split protects", [
        "The blog remains easier to maintain, while the marketing pages stay design-controlled and conversion-oriented.",
        "That separation keeps product pages from becoming editorial clutter and keeps the blog from becoming over-designed."
      ]],
      ["Implementation lesson", [
        "Pick the editor that matches the content model instead of forcing one system everywhere.",
        "That decision improves both usability and commercial clarity."
      ]],
    ],
  },
  {
    id: 628,
    title: "The Commercial Difference Between Fixed Counts and Dynamic Tools",
    slug: "fixed-counts-vs-dynamic-tools",
    excerpt: "Static claims like '207 tools' age badly. A commercially serious plugin should explain categories, scopes, and live discovery instead.",
    focus: "dynamic WordPress tools",
    seoTitle: "Dynamic WordPress Tools Beat Fixed Counts for Commercial Trust | MCPWP",
    seoDesc: "MCPWP uses live tool discovery and workflow categories because static tool counts age badly and reduce product credibility over time.",
    imagePrompt: "Dark SaaS editorial illustration of dynamic tool discovery, floating tool cards, live API dashboard, WordPress MCP interface, blue cyan glow, premium product blog art",
    intro: "Static tool counts are useful until the product changes. After that, they become debt. The stronger position is not 'we have X tools' but 'the site exposes the right workflows for the current configuration and key.'",
    sections: [
      ["Why counts fail", [
        "Counts age the moment a feature is added, removed, or reclassified.",
        "That creates a mismatch between marketing and reality, and buyers notice."
      ]],
      ["What buyers actually want", [
        "They want to know if the product can handle content, Elementor, SEO, media, menus, forms, and multi-site operations.",
        "That is a workflow question, not a vanity metric."
      ]],
      ["How MCPWP frames it", [
        "Capabilities are discovered from the live site and the scoped key, which means the product can present what is actually available.",
        "That creates a much more credible commercial message."
      ]],
      ["Takeaway", [
        "Use dynamic capability language, not fixed counts, when the product is evolving.",
        "The story stays honest and the sales message stays durable."
      ]],
    ],
  },
  {
    id: 629,
    title: "What We Learned Cleaning Old Brand Copy Out of a Live Launch Site",
    slug: "cleaning-old-brand-copy-live-launch-site",
    excerpt: "Old plugin names, stale counts, and legacy URLs do not just look messy. They damage trust, SEO coherence, and product credibility.",
    focus: "brand cleanup SEO",
    seoTitle: "Cleaning Old Brand Copy Out of a Live Launch Site | MCPWP",
    seoDesc: "A practical launch lesson from MCPWP: stale names, counts, URLs, and metadata hurt trust and must be cleaned before growth work.",
    imagePrompt: "Professional editorial illustration of a launch site cleanup audit, old brand labels being replaced with new brand labels, dashboard and SEO metadata, dark modern SaaS style, blue and cyan accents",
    intro: "A site can be visually polished and still feel unfinished if the naming is inconsistent. In MCPWP, the biggest credibility gap was not the layout. It was the old brand language still surfacing in pages, metadata, and blog snippets.",
    sections: [
      ["What hurt credibility", [
        "If the homepage says MCPWP but the blog snippets still say the old brand, the product feels half-migrated.",
        "If the tool counts or URLs disagree with the current site state, trust drops fast."
      ]],
      ["What we fixed first", [
        "We cleaned the visible landing pages, then aligned metadata, then removed the most obvious legacy copy from the blog index.",
        "That order matters because front-end trust comes before search trust."
      ]],
      ["What this means for launches", [
        "Brand cleanup is not a cosmetic task; it is a commercial one.",
        "If a product name changes, every visible surface has to change with it."
      ]],
      ["Practical takeaway", [
        "Do a content debt audit before launch.",
        "Titles, excerpts, metadata, internal links, and image alt text should all match the new positioning."
      ]],
    ],
  },
  {
    id: 630,
    title: "How a Safe WordPress AI Product Earns Agency Trust",
    slug: "safe-wordpress-ai-product-agency-trust",
    excerpt: "Agencies buy control. The product earns trust when it separates read-only inspection, scoped writes, and risky admin actions.",
    focus: "agency trust WordPress AI",
    seoTitle: "How a Safe WordPress AI Product Earns Agency Trust | MCPWP",
    seoDesc: "Why agencies trust MCPWP when it uses scoped keys, visible operations, read-only inspection, and controlled write paths.",
    imagePrompt: "Dark premium agency trust illustration for a WordPress AI product, shield icon, scoped keys, workflow cards, live site inspection, modern SaaS editorial art, blue cyan glow",
    intro: "Agencies do not buy novelty. They buy control. If a WordPress AI product cannot clearly separate inspection from write access, agencies will hesitate because the risk lands on them, not the vendor.",
    sections: [
      ["What agencies need", [
        "Repeatable workflows, predictable access, and a clear boundary between read-only and write-capable actions.",
        "They also need visible logs and a clear revocation path."
      ]],
      ["Why security sells", [
        "Security is not only a technical feature. It is a trust primitive.",
        "If the access model can be explained in one minute, approvals become easier."
      ]],
      ["How MCPWP should be framed", [
        "Least privilege by default, broader access only when needed, and visible operations for every write.",
        "That is easier to sell than unrestricted access to everything."
      ]],
      ["Takeaway", [
        "Trust comes from predictable scope, not from promises.",
        "The product story should emphasize control first and speed second."
      ]],
    ],
  },
  {
    id: 631,
    title: "What a Good MCP Demo Has to Prove Before Anyone Buys",
    slug: "good-mcp-demo-before-buying",
    excerpt: "A real demo should show the page state, the action, and the verification step. If it skips any of those, it is not convincing.",
    focus: "MCP demo",
    seoTitle: "What a Good MCP Demo Has to Prove Before Anyone Buys | MCPWP",
    seoDesc: "A useful MCP demo proves inspection, action, and verification on a real WordPress site. That is what converts curiosity into trust.",
    imagePrompt: "High-end product demo illustration showing before-action-after on a WordPress MCP dashboard, split screen with inspection, change, and verification, dark modern editorial style, blue cyan glow",
    intro: "A convincing demo is not a slide deck. For a WordPress MCP product, the demo has to prove the full loop: inspect the site, perform the action, and verify the result. If any of those steps are missing, the user is forced to guess.",
    sections: [
      ["What to show", [
        "The current page or site state before the change.",
        "The actual tool or workflow being used.",
        "The rendered result after the write completes."
      ]],
      ["What not to show", [
        "Do not show only prompt text and call it proof.",
        "Do not hide the WordPress response if the product claims real operational control."
      ]],
      ["Why this matters", [
        "The buyer needs to see a closed loop, not a promise.",
        "That loop is what turns curiosity into confidence."
      ]],
      ["Takeaway", [
        "A good demo proves the product can operate a real site safely.",
        "That is more persuasive than any amount of abstract product language."
      ]],
    ],
  },
  {
    id: 632,
    title: "How We Turned SEO Cleanup Into a Product Feature",
    slug: "seo-cleanup-product-feature",
    excerpt: "If SEO metadata is stale, the site feels unfinished. We treated cleanup as part of the product experience, not a back-office chore.",
    focus: "SEO cleanup",
    seoTitle: "How We Turned SEO Cleanup Into a Product Feature | MCPWP",
    seoDesc: "MCPWP treats SEO cleanup as part of product readiness: titles, descriptions, social metadata, and snippet consistency all matter.",
    imagePrompt: "Modern SEO product editorial illustration, metadata cards, search snippet preview, WordPress dashboard, dark theme, blue cyan glow, premium SaaS blog art",
    intro: "SEO work is usually treated like housekeeping. On a launch site, that is a mistake. The search snippet is part of the product experience, so stale metadata makes the whole site feel unfinished.",
    sections: [
      ["Why metadata matters", [
        "Search titles and descriptions are often the first brand impression before a visit happens.",
        "If the snippet still says the old product name, the launch looks behind."
      ]],
      ["What we changed", [
        "We aligned the visible page copy and the SEO metadata on the related pages.",
        "That included titles, descriptions, and focus keywords."
      ]],
      ["Why it is commercial work", [
        "SEO cleanup is trust work, not just traffic work.",
        "A coherent snippet makes the site easier to recommend and easier to index."
      ]],
      ["Takeaway", [
        "Treat metadata as part of product polish.",
        "If the brand is changing, the metadata has to change with it."
      ]],
    ],
  },
  {
    id: 633,
    title: "The Product Story Behind a WordPress MCP Launcher Page",
    slug: "product-story-wordpress-mcp-launcher-page",
    excerpt: "A launcher page must explain the problem, the workflow, and the first successful outcome. Anything else is just decoration.",
    focus: "launcher page",
    seoTitle: "The Product Story Behind a WordPress MCP Launcher Page | MCPWP",
    seoDesc: "A launcher page for MCPWP has to explain the product problem, the workflow path, and the first successful site operation.",
    imagePrompt: "Launch page hero illustration for a WordPress MCP product, conversion flow, hero section, pricing cards, docs and demo path, premium dark marketing art with blue cyan accents",
    intro: "The launcher page is where the product story either becomes clear or falls apart. For MCPWP, the page has to explain that WordPress can act like an MCP server and that AI clients can operate real workflows safely.",
    sections: [
      ["The structure that works", [
        "Problem statement.",
        "Workflow categories.",
        "Setup path.",
        "Proof that the workflow really runs."
      ]],
      ["Why layout alone is not enough", [
        "A nice page without a clear flow will not convert.",
        "A clear flow without proof will not convert either."
      ]],
      ["What the visitor needs to learn", [
        "What the product is.",
        "Who it is for.",
        "How to begin.",
        "What the first successful outcome looks like."
      ]],
      ["Takeaway", [
        "A good launch page turns a tool into a product.",
        "That requires both visual design and a clear operating story."
      ]],
    ],
  },
  {
    id: 634,
    title: "Why the Blog Exists Even When the Product Is the Real Offer",
    slug: "why-the-blog-exists-for-a-product-offer",
    excerpt: "The blog is not a side quest. It is where the product explains itself, earns search visibility, and proves operational maturity.",
    focus: "product blog strategy",
    seoTitle: "Why the Blog Exists Even When the Product Is the Real Offer | MCPWP",
    seoDesc: "MCPWP uses the blog to explain workflows, answer objections, and earn search visibility around real WordPress MCP use cases.",
    imagePrompt: "Editorial blog strategy illustration for a SaaS product, content calendar, search results, WordPress articles, dark premium theme, blue cyan accents, modern marketing art",
    intro: "A lot of plugin sites treat the blog as filler. That does not work for a product like MCPWP. The blog is part of the commercial stack because it explains the workflows, answers objections, and creates search coverage around the exact problems the product solves.",
    sections: [
      ["Why it matters", [
        "Many buyers arrive through a problem statement rather than the homepage.",
        "The blog is where those problem statements can be addressed in practical language."
      ]],
      ["What the content should do", [
        "It should document real workflows, not generic AI hype.",
        "It should help readers understand how the product behaves on a live WordPress site."
      ]],
      ["Why this helps SEO", [
        "Helpful posts can rank for workflow and problem queries that the homepage cannot cover well.",
        "That makes the site more discoverable without making the main product pages bloated."
      ]],
      ["Takeaway", [
        "A useful blog makes the product feel credible.",
        "A promotional blog makes the brand feel weaker."
      ]],
    ],
  },
  {
    id: 635,
    title: "Ten Signals That a WordPress AI Plugin Is Ready for Market",
    slug: "ten-signals-wordpress-ai-plugin-ready-for-market",
    excerpt: "A market-ready plugin is not just functional. It has clear messaging, visible workflows, safe access, and proof that it matches the buyer’s job.",
    focus: "market ready plugin",
    seoTitle: "Ten Signals That a WordPress AI Plugin Is Ready for Market | MCPWP",
    seoDesc: "A market-ready WordPress AI plugin needs clear messaging, visible workflows, safe access, real setup paths, and proof that it solves a buyer job.",
    imagePrompt: "Checklist-style premium SaaS editorial illustration for a market-ready WordPress AI plugin, ten signal cards, dark background, blue cyan glow, modern and polished",
    intro: "Commercial readiness is a checklist, not a vibe. If the plugin pages cannot answer the basic questions about what it does, who it is for, how it works, and how it stays safe, then it is not ready to sell.",
    sections: [
      ["The signals I look for", [
        "Clear product positioning.",
        "Visible workflow categories.",
        "A safe access model.",
        "A real setup path.",
        "A demo that proves the output.",
        "SEO pages that match the brand.",
        "Blog content that explains the use cases.",
        "A pricing page that answers budget questions.",
        "A docs path that reduces support load.",
        "A site that looks coherent in search and on social."
      ]],
      ["What it means in practice", [
        "The product should help the buyer imagine the first success quickly.",
        "If the buyer can see the workflow, the risk drops and the decision gets easier."
      ]],
      ["Why this matters for MCPWP", [
        "MCPWP is strongest when it is framed as controlled WordPress site operations for AI clients.",
        "That is the commercial category worth pushing."
      ]],
      ["Takeaway", [
        "Readiness is visible when the site, the product, and the message all line up.",
        "That is the threshold a real market-ready plugin has to hit."
      ]],
    ],
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

function buildContent(post) {
  const chunks = [`<p>${post.intro}</p>`];
  for (const [heading, paragraphs] of post.sections) {
    chunks.push(`<h2>${heading}</h2>`);
    for (const paragraph of paragraphs) {
      chunks.push(`<p>${paragraph}</p>`);
    }
  }
  return chunks.join("\n\n");
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
    return json.result;
  }
  throw new Error("Rate limit did not clear after retries.");
}

async function callTool(name, args = {}) {
  await delay(2200);
  return parseToolResult(await rpc("tools/call", { name, arguments: args }));
}

async function main() {
  await rpc("initialize", {
    protocolVersion: "2025-03-26",
    capabilities: {},
    clientInfo: { name: "mcpwp-blog-finisher", version: "0.1" },
  });

  if (!deploy) {
    console.log(JSON.stringify({ deploy: false, postCount: posts.length, posts: posts.map((post) => ({ id: post.id, title: post.title })) }, null, 2));
    return;
  }

  const updatePayload = posts.map((post) => ({
    id: post.id,
    title: post.title,
    content: buildContent(post),
    excerpt: post.excerpt,
    status: "draft",
    slug: post.slug,
  }));
  const updated = parseToolResult(await callTool("wp_bulk_update_posts", { posts: updatePayload }));

  const metaResults = [];
  const imageResults = [];
  for (const post of posts) {
    metaResults.push(await callTool("wp_set_post_meta", { id: post.id, key: "_yoast_wpseo_title", value: post.seoTitle }));
    metaResults.push(await callTool("wp_set_post_meta", { id: post.id, key: "_yoast_wpseo_metadesc", value: post.seoDesc }));
    metaResults.push(await callTool("wp_set_post_meta", { id: post.id, key: "_yoast_wpseo_focuskw", value: post.focus }));
    imageResults.push(await callTool("wp_generate_featured_image", {
      post_id: post.id,
      prompt: post.imagePrompt,
      provider: "openai",
      size: "1792x1024",
      style: "vivid",
    }));
  }

  const published = [];
  for (const post of posts) {
    published.push(await callTool("wp_update_post", { id: post.id, status: "publish" }));
  }

  console.log(JSON.stringify({
    deploy: true,
    updatedCount: updated?.updated?.length ?? null,
    metaCount: metaResults.length,
    imageCount: imageResults.length,
    publishedCount: published.length,
  }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
