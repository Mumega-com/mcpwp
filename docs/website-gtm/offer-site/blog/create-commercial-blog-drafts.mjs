import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const deploy = process.argv.includes("--deploy");

const posts = [
  {
    title: "How We Turned a WordPress Plugin Into a Real AI Operator",
    slug: "wordpress-plugin-real-ai-operator",
    excerpt: "The difference between a chatbot wrapper and a commercially viable WordPress MCP product is whether it can actually operate the site safely.",
    tags: ["MCPWP", "WordPress MCP", "AI Operations", "Product Strategy"],
    intro: "The first thing I learned while shaping MCPWP is that buyers do not pay for a demo that talks well. They pay for a system that can read the site, understand context, make changes safely, and verify the result. That means the product has to behave like an operator, not a chat interface.",
    sections: [
      {
        heading: "What made it real",
        body: [
          "The site had to expose named workflows instead of generic prompts. Content edits, Elementor changes, SEO updates, media work, and menu edits all had to be visible as separate capabilities.",
          "That separation matters commercially because it lets a buyer understand what they are actually getting and what they are not."
        ]
      },
      {
        heading: "Why it matters for sales",
        body: [
          "A commercial WordPress AI product must reduce uncertainty. The product page has to show the live site, the available tools, the setup path, and the first successful workflow.",
          "If the product only sounds clever, the buyer assumes it is fragile. If it can inspect, act, and verify, the buyer sees a system."
        ]
      },
      {
        heading: "The practical takeaway",
        body: [
          "A WordPress MCP product should be sold as controlled site operations, not as a general-purpose assistant.",
          "That framing is clearer, safer, and easier to defend in a sales conversation."
        ]
      }
    ]
  },
  {
    title: "Why We Kept Gutenberg for the Blog and Elementor for Everything Else",
    slug: "gutenberg-blog-elementor-product-pages",
    excerpt: "The blog should behave like an editorial system. The product and funnel pages should behave like a controlled conversion system.",
    tags: ["Elementor", "Gutenberg", "WordPress Design", "Conversion"],
    intro: "One of the cleanest product decisions we made was to stop trying to make every page behave the same way. The blog stays Gutenberg. The product pages, pricing, download, docs, and launch pages are Elementor-based. That split is not cosmetic; it is operational.",
    sections: [
      {
        heading: "Why the split works",
        body: [
          "Gutenberg is good for structured editorial content. It is fast, simple, and well suited to articles that need clean text flow.",
          "Elementor is better for landing pages, conversion sections, pricing tables, feature grids, and call-to-action heavy layouts."
        ]
      },
      {
        heading: "Why it matters commercially",
        body: [
          "Buyers do not want to hear that the product uses the same builder for every use case. They want to hear that the site is designed for the task.",
          "That makes the product feel intentional instead of improvised."
        ]
      },
      {
        heading: "The implementation lesson",
        body: [
          "A launch-ready WordPress site should use the editor that fits the content model instead of forcing one system everywhere.",
          "The commercial story improves when the user can see that the product understands the difference."
        ]
      }
    ]
  },
  {
    title: "The Commercial Difference Between Fixed Counts and Dynamic Tools",
    slug: "fixed-counts-vs-dynamic-tools",
    excerpt: "Static claims like '207 tools' age badly. A commercially serious plugin should explain categories, scopes, and live discovery instead.",
    tags: ["MCPWP", "Product Messaging", "SEO", "WordPress Automation"],
    intro: "Static tool counts are tempting marketing copy, but they become a liability as soon as the product changes. We moved the positioning toward dynamic discovery because the value is not the number. The value is that the site exposes the right workflows for the active configuration and the current key.",
    sections: [
      {
        heading: "Why the count breaks down",
        body: [
          "If features are added, removed, gated, or reorganized, a fixed number becomes stale immediately.",
          "That makes the brand look careless even when the underlying product is getting better."
        ]
      },
      {
        heading: "What buyers really need",
        body: [
          "They need to know whether the plugin can handle content, Elementor, SEO, media, menus, forms, or multi-site work.",
          "That is a workflow question, not a numeric question."
        ]
      },
      {
        heading: "How to frame it",
        body: [
          "The right commercial claim is that MCPWP discovers capabilities from the live site and the current API key.",
          "That is more credible than chasing a vanity number."
        ]
      }
    ]
  },
  {
    title: "What We Learned Cleaning Old Brand Copy Out of a Live Launch Site",
    slug: "cleaning-old-brand-copy-live-launch-site",
    excerpt: "Old plugin names, stale counts, and legacy URLs do not just look messy. They damage trust, SEO coherence, and product credibility.",
    tags: ["SEO", "Brand Cleanup", "Launch Readiness", "WordPress Content"],
    intro: "A launch site can look polished and still fail commercially if the copy is inconsistent. During the MCPWP cleanup, the biggest credibility issue was not layout. It was stale language: old product names, old tool counts, and old URLs still visible in public content and metadata.",
    sections: [
      {
        heading: "What hurt credibility",
        body: [
          "When the product says MCPWP on the homepage but the blog and metadata still say mumcp, the brand feels unfinished.",
          "When tool counts appear in multiple places and do not match the current state, buyers notice."
        ]
      },
      {
        heading: "What we fixed",
        body: [
          "We cleaned the public landing pages first, then the metadata, then the blog index copy.",
          "That order matters because users see the frontend first, but search engines carry the metadata forward."
        ]
      },
      {
        heading: "What to do next",
        body: [
          "Any commercial WordPress product should include a content debt audit before launch.",
          "If the product name changes, the cleanup needs to reach the pages, excerpts, titles, alt text, and internal links."
        ]
      }
    ]
  },
  {
    title: "How a Safe WordPress AI Product Earns Agency Trust",
    slug: "safe-wordpress-ai-product-agency-trust",
    excerpt: "Agencies buy control. The product earns trust when it separates read-only inspection, scoped writes, and risky admin actions.",
    tags: ["Agencies", "Security", "Scoped Access", "MCPWP"],
    intro: "The agency question is never 'can it do something interesting?' The question is whether it can do the work without creating hidden risk. That is why scopes, keys, and visible workflows matter so much in MCPWP.",
    sections: [
      {
        heading: "What agencies need",
        body: [
          "They need repeatable workflows, predictable access, and a clear boundary between inspection and write operations.",
          "They also need to know who can revoke access and how to review what changed."
        ]
      },
      {
        heading: "Why the security story sells",
        body: [
          "Security is not only a compliance issue. It is a trust and adoption issue.",
          "If an agency can explain the access model to a client in one minute, the product gets easier to approve."
        ]
      },
      {
        heading: "The product framing",
        body: [
          "The best framing is least privilege by default, broader access only when needed, and visible operations for every write.",
          "That is a better commercial story than 'full access to everything.'"
        ]
      }
    ]
  },
  {
    title: "What a Good MCP Demo Has to Prove Before Anyone Buys",
    slug: "good-mcp-demo-before-buying",
    excerpt: "A real demo should show the page state, the action, and the verification step. If it skips any of those, it is not convincing.",
    tags: ["Demo", "Conversion", "WordPress MCP", "Product Strategy"],
    intro: "A demo is not a slideshow. For a WordPress MCP product, the demo must prove that the system can inspect the site, perform the operation, and verify the result. If any one of those steps is missing, the user is left guessing.",
    sections: [
      {
        heading: "What the demo should show",
        body: [
          "The live page structure before the change.",
          "The tool or workflow being used.",
          "The rendered result after the change."
        ]
      },
      {
        heading: "What not to show",
        body: [
          "Do not show only prompt text and call it a demo.",
          "Do not hide the actual WordPress response if the product claims real site control."
        ]
      },
      {
        heading: "The commercial lesson",
        body: [
          "A buyer is not impressed by words alone.",
          "A buyer is impressed when the product turns a real site into a controlled, visible workflow."
        ]
      }
    ]
  },
  {
    title: "How We Turned SEO Cleanup Into a Product Feature",
    slug: "seo-cleanup-product-feature",
    excerpt: "If SEO metadata is stale, the site feels unfinished. We treated cleanup as part of the product experience, not a back-office chore.",
    tags: ["SEO", "Metadata", "Launch Quality", "WordPress"],
    intro: "At launch time, SEO work is usually treated like housekeeping. In practice, it is part of the product. If the title tags, descriptions, and social metadata are inconsistent, the site feels less trustworthy and less commercially ready.",
    sections: [
      {
        heading: "Why metadata matters",
        body: [
          "Search snippets are one of the first brand impressions a prospect gets.",
          "If the snippet still says the old product name, the entire launch feels behind."
        ]
      },
      {
        heading: "What we changed",
        body: [
          "We updated the visible landing pages and then aligned the metadata on the related pages.",
          "That included title tags, descriptions, and page-level focus keywords."
        ]
      },
      {
        heading: "Why this is commercial work",
        body: [
          "SEO cleanup is not just traffic work. It is trust work.",
          "When the metadata matches the product, the site is easier to recommend and easier to index."
        ]
      }
    ]
  },
  {
    title: "The Product Story Behind a WordPress MCP Launcher Page",
    slug: "product-story-wordpress-mcp-launcher-page",
    excerpt: "A launcher page must explain the problem, the workflow, and the first successful outcome. Anything else is just decoration.",
    tags: ["Landing Page", "GTM", "MCPWP", "Elementor"],
    intro: "The launcher page is where the product story either becomes clear or falls apart. For MCPWP, the page has to explain that WordPress can act like an MCP server and that AI clients can then operate real workflows safely.",
    sections: [
      {
        heading: "The structure that works",
        body: [
          "Problem statement.",
          "The workflow categories the plugin supports.",
          "The first setup path.",
          "The proof that the workflow really runs."
        ]
      },
      {
        heading: "Why layout alone is not enough",
        body: [
          "A nice page without a clear flow will not convert.",
          "A clear flow without proof will not convert either."
        ]
      },
      {
        heading: "The commercial version",
        body: [
          "A good launch page tells the visitor what the product is, who it is for, and how to begin.",
          "That is what turns a tool into a product."
        ]
      }
    ]
  },
  {
    title: "Why the Blog Exists Even When the Product Is the Real Offer",
    slug: "why-the-blog-exists-for-a-product-offer",
    excerpt: "The blog is not a side quest. It is where the product explains itself, earns search visibility, and proves operational maturity.",
    tags: ["Content Strategy", "SEO", "GTM", "WordPress"],
    intro: "A lot of plugin sites treat the blog as filler. That does not work for a product like MCPWP. The blog is part of the commercial stack because it explains the workflows, answers objections, and creates search coverage around the exact problems the product solves.",
    sections: [
      {
        heading: "Why it matters",
        body: [
          "A buyer often arrives through a problem statement, not the homepage.",
          "The blog is where those problem statements can be addressed in practical language."
        ]
      },
      {
        heading: "What the content should do",
        body: [
          "It should document real workflows, not generic AI hype.",
          "It should help the reader understand how the product behaves on a live WordPress site."
        ]
      },
      {
        heading: "The commercial outcome",
        body: [
          "If the blog is useful, the product feels credible.",
          "If the blog is merely promotional, the whole brand feels weaker."
        ]
      }
    ]
  },
  {
    title: "Ten Signals That a WordPress AI Plugin Is Ready for Market",
    slug: "ten-signals-wordpress-ai-plugin-ready-for-market",
    excerpt: "A market-ready plugin is not just functional. It has clear messaging, visible workflows, safe access, and proof that it matches the buyer’s job.",
    tags: ["Commercial Viability", "Product Readiness", "WordPress AI", "Launch"],
    intro: "Commercial readiness is a checklist, not a vibe. If the plugin pages cannot answer the basic questions about what it does, who it is for, how it works, and how it stays safe, then it is not ready to sell.",
    sections: [
      {
        heading: "The signals I look for",
        body: [
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
        ]
      },
      {
        heading: "What it means in practice",
        body: [
          "The product should help the buyer imagine the first success quickly.",
          "If the buyer can see the workflow, the risk drops and the decision gets easier."
        ]
      },
      {
        heading: "Why this matters for MCPWP",
        body: [
          "MCPWP is strongest when it is framed as controlled WordPress site operations for AI clients.",
          "That is the commercial category I would keep pushing."
        ]
      }
    ]
  }
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

function slugify(text) {
  return text
    .toLowerCase()
    .replace(/&amp;/g, "and")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

function buildContent(post) {
  const parts = [];
  parts.push(`<p>${post.intro}</p>`);
  for (const section of post.sections) {
    parts.push(`<h2>${section.heading}</h2>`);
    for (const paragraph of section.body) {
      parts.push(`<p>${paragraph}</p>`);
    }
  }
  return parts.join("\n\n");
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
    clientInfo: { name: "mcpwp-blog-drafts", version: "0.1" },
  });

  if (!deploy) {
    console.log(JSON.stringify({ deploy: false, postCount: posts.length, posts: posts.map((post) => ({ title: post.title, slug: post.slug })) }, null, 2));
    return;
  }

  const payload = posts.map((post) => ({
    title: post.title,
    content: buildContent(post),
    status: "draft",
    categories: [1],
    tags: post.tags,
    excerpt: post.excerpt,
    slug: post.slug || slugify(post.title),
    post_type: "post",
  }));

  const result = await callTool("wp_bulk_create_posts", { posts: payload });
  console.log(JSON.stringify({ deploy: true, result }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
