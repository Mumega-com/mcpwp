import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const deploy = process.argv.includes("--deploy");

const posts = [
  {
    id: 458,
    title: "MCPWP: Controlled AI Site Operations Through MCP",
    excerpt: "MCPWP connects AI agents to WordPress through scoped API keys, Elementor-aware workflows, dynamic tools, and controlled site operations.",
  },
  {
    id: 416,
    excerpt: "How store owners can use MCPWP to manage products, orders, customers, and revenue workflows through controlled AI tools.",
  },
  {
    id: 413,
    excerpt: "How course creators can use MCPWP to manage LearnPress courses, lessons, quizzes, and curriculum workflows through AI.",
  },
  {
    id: 410,
    excerpt: "A practical workflow for using MCPWP to plan, draft, optimize, and publish WordPress content faster with AI agents.",
  },
  {
    id: 401,
    excerpt: "How WordPress agencies use MCPWP to manage client sites from a single AI workflow with content, SEO, and Elementor operations.",
  },
  {
    id: 398,
    excerpt: "Step-by-step guide to connecting WordPress to Claude Desktop using MCPWP and MCP for posts, pages, Elementor, WooCommerce, and SEO workflows.",
  },
  {
    id: 356,
    title: "MCPWP vs WP Engine AI vs AI Power: WordPress AI Plugin Comparison",
    excerpt: "Comparing MCPWP with WP Engine AI, AI Power, and other WordPress AI plugins across MCP workflows, Elementor, WooCommerce, SEO, and LMS use cases.",
  },
  {
    id: 355,
    excerpt: "Generate images, create alt text, coordinate text-to-speech, and search stock photos through WordPress MCP workflows with MCPWP.",
  },
  {
    id: 354,
    excerpt: "Connect WordPress to Google Gemini using MCPWP and MCP for content, Elementor, WooCommerce, and SEO workflows.",
  },
  {
    id: 353,
    excerpt: "Step-by-step tutorial: build a WooCommerce landing page with Elementor using AI and MCPWP. Create pages, design layouts, and coordinate products through conversation.",
  },
  {
    id: 352,
    excerpt: "Read and coordinate WordPress form workflows through AI. MCPWP can support Contact Form 7, WPForms, Gravity Forms, and Ninja Forms through MCP where available.",
  },
  {
    id: 351,
    excerpt: "Manage LearnPress courses, lessons, quizzes, and students through AI-assisted WordPress MCP workflows with MCPWP.",
  },
  {
    id: 350,
    excerpt: "Compare WordPress MCP plugins by workflow coverage, Elementor support, WooCommerce support, SEO operations, forms, LMS, and safety controls.",
  },
  {
    id: 349,
    excerpt: "Step-by-step guide to connecting WordPress to ChatGPT using MCPWP and MCP for posts, pages, Elementor, and WooCommerce workflows.",
  },
  {
    id: 348,
    excerpt: "Manage WordPress SEO through AI with MCPWP workflows for Yoast, Rank Math, and AIOSEO metadata, audits, bulk updates, and optimization.",
  },
  {
    id: 347,
    excerpt: "Manage WooCommerce products, orders, customers, and revenue workflows through AI-assisted WordPress MCP operations with MCPWP.",
  },
  {
    id: 346,
    title: "WordPress MCP Tools: Dynamic AI Commands for Site Operations",
    excerpt: "A practical reference for MCPWP tool categories and dynamic WordPress MCP capabilities for Claude, ChatGPT, Gemini, and other AI clients.",
  },
  {
    id: 132,
    excerpt: "The MCPWP website is being built and maintained through its own WordPress MCP workflows, including Elementor pages, content operations, and live verification.",
  },
  {
    id: 131,
    title: "What's New in MCPWP",
    excerpt: "MCPWP continues to evolve from a basic WordPress REST wrapper into a broader AI operations layer for real WordPress sites.",
  },
  {
    id: 45,
    title: "MCPWP vs Manual WordPress: A Time Comparison",
    excerpt: "We compare common WordPress tasks manually and through MCPWP with Claude to understand where AI-assisted operations save time.",
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
  await delay(2500);
  return parseToolResult(await rpc("tools/call", { name, arguments: args }));
}

async function main() {
  await rpc("initialize", {
    protocolVersion: "2025-03-26",
    capabilities: {},
    clientInfo: { name: "mcpwp-blog-branding-fixer", version: "0.1" },
  });

  if (!deploy) {
    console.log(JSON.stringify({ deploy: false, postCount: posts.length, posts, next: `node ${process.argv[1]} --deploy` }, null, 2));
    return;
  }

  const result = await callTool("wp_bulk_update_posts", { posts });
  console.log(JSON.stringify({ deploy: true, result }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
