import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const deploy = process.argv.includes("--deploy");

function parseToolResult(result) {
  const text = result?.content?.find?.((item) => item.type === "text")?.text;
  if (!text) return result;
  try {
    return JSON.parse(text);
  } catch {
    return text;
  }
}

function rewriteText(value) {
  if (!value) return value;
  return value
    .replace(/Mumega MCP/gi, "MCPWP")
    .replace(/\bmumcp\b/gi, "MCPWP")
    .replace(/sitepilotai\.mumega\.com/gi, "mcpwp.net")
    .replace(/site-pilot-ai/gi, "MCPWP")
    .replace(/\b207 MCP tools\b/gi, "MCPWP tools")
    .replace(/\b207 AI commands\b/gi, "MCPWP tools")
    .replace(/\ball 207 tools\b/gi, "MCPWP tools")
    .replace(/\b207 tools\b/gi, "MCPWP tools")
    .replace(/\b121 MCP\b/gi, "MCPWP")
    .replace(/\b139\+\b/gi, "MCPWP");
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
    clientInfo: { name: "mcpwp-blog-brand-rewriter", version: "0.1" },
  });

  const list = parseToolResult(await callTool("wp_list_posts", {
    status: "publish",
    per_page: 100,
    fields: "id,title,excerpt,content,slug",
  }));
  const posts = list.posts ?? list;
  const updates = [];
  for (const post of posts) {
    const title = rewriteText(post.title);
    const excerpt = rewriteText(post.excerpt);
    const content = rewriteText(post.content);
    if (title !== post.title || excerpt !== post.excerpt || content !== post.content) {
      updates.push({
        id: post.id,
        title,
        excerpt,
        content,
        status: "publish",
        slug: post.slug,
      });
    }
  }

  if (!deploy) {
    console.log(JSON.stringify({ deploy: false, updateCount: updates.length, updates: updates.map(({ id, title }) => ({ id, title })) }, null, 2));
    return;
  }

  const batches = [];
  for (let i = 0; i < updates.length; i += 10) batches.push(updates.slice(i, i + 10));
  const results = [];
  for (const batch of batches) {
    results.push(await callTool("wp_bulk_update_posts", { posts: batch }));
  }

  console.log(JSON.stringify({ deploy: true, updateCount: updates.length, batchCount: batches.length }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
