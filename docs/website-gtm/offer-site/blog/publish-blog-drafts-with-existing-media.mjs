import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const deploy = process.argv.includes("--deploy");

const posts = [
  { id: 626, mediaId: 298, seoTitle: "How We Turned a WordPress Plugin Into a Real AI Operator | MCPWP", seoDesc: "A practical breakdown of how MCPWP became a real WordPress AI operator through scoped tools, safe writes, live verification, and clear workflow categories.", focus: "WordPress AI operator" },
  { id: 627, mediaId: 166, seoTitle: "Why Gutenberg Stays on the Blog and Elementor Owns the Funnel | MCPWP", seoDesc: "See why MCPWP uses Gutenberg for editorial posts and Elementor for product, pricing, docs, and conversion pages in the live site architecture.", focus: "Gutenberg blog Elementor" },
  { id: 628, mediaId: 368, seoTitle: "Dynamic WordPress Tools Beat Fixed Counts for Commercial Trust | MCPWP", seoDesc: "MCPWP uses live tool discovery and workflow categories because static tool counts age badly and reduce product credibility over time.", focus: "dynamic WordPress tools" },
  { id: 629, mediaId: 396, seoTitle: "Cleaning Old Brand Copy Out of a Live Launch Site | MCPWP", seoDesc: "A practical launch lesson from MCPWP: stale names, counts, URLs, and metadata hurt trust and must be cleaned before growth work.", focus: "brand cleanup SEO" },
  { id: 630, mediaId: 403, seoTitle: "How a Safe WordPress AI Product Earns Agency Trust | MCPWP", seoDesc: "Why agencies trust MCPWP when it uses scoped keys, visible operations, read-only inspection, and controlled write paths.", focus: "agency trust WordPress AI" },
  { id: 631, mediaId: 400, seoTitle: "What a Good MCP Demo Has to Prove Before Anyone Buys | MCPWP", seoDesc: "A useful MCP demo proves inspection, action, and verification on a real WordPress site. That is what converts curiosity into trust.", focus: "MCP demo" },
  { id: 632, mediaId: 370, seoTitle: "How We Turned SEO Cleanup Into a Product Feature | MCPWP", seoDesc: "MCPWP treats SEO cleanup as part of product readiness: titles, descriptions, social metadata, and snippet consistency all matter.", focus: "SEO cleanup" },
  { id: 633, mediaId: 390, seoTitle: "The Product Story Behind a WordPress MCP Launcher Page | MCPWP", seoDesc: "A launcher page for MCPWP has to explain the product problem, the workflow path, and the first successful site operation.", focus: "launcher page" },
  { id: 634, mediaId: 394, seoTitle: "Why the Blog Exists Even When the Product Is the Real Offer | MCPWP", seoDesc: "MCPWP uses the blog to explain workflows, answer objections, and earn search visibility around real WordPress MCP use cases.", focus: "product blog strategy" },
  { id: 635, mediaId: 372, seoTitle: "Ten Signals That a WordPress AI Plugin Is Ready for Market | MCPWP", seoDesc: "A market-ready WordPress AI plugin needs clear messaging, visible workflows, safe access, real setup paths, and proof that it solves a buyer job.", focus: "market ready plugin" },
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
  await delay(2200);
  return parseToolResult(await rpc("tools/call", { name, arguments: args }));
}

async function main() {
  await rpc("initialize", {
    protocolVersion: "2025-03-26",
    capabilities: {},
    clientInfo: { name: "mcpwp-blog-media-publisher", version: "0.1" },
  });

  if (!deploy) {
    console.log(JSON.stringify({ deploy: false, posts }, null, 2));
    return;
  }

  const results = [];
  for (const post of posts) {
    await callTool("wp_set_post_meta", { id: post.id, key: "_yoast_wpseo_title", value: post.seoTitle });
    await callTool("wp_set_post_meta", { id: post.id, key: "_yoast_wpseo_metadesc", value: post.seoDesc });
    await callTool("wp_set_post_meta", { id: post.id, key: "_yoast_wpseo_focuskw", value: post.focus });
    await callTool("wp_set_featured_image", { id: post.id, media_id: post.mediaId });
    results.push(await callTool("wp_update_post", { id: post.id, status: "publish" }));
  }

  console.log(JSON.stringify({ deploy: true, published: results.length }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
