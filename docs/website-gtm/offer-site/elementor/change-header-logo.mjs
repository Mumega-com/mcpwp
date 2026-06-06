import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const HEADER_ID = 315;
const config = JSON.parse(fs.readFileSync(MCP_CONFIG, "utf8")).mcpServers.mcpwp;
let id = 1;

const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

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
  for (let attempt = 0; attempt < 8; attempt++) {
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
    return json.result;
  }
  throw new Error("Rate limit did not clear after retries.");
}

async function callTool(name, args = {}) {
  await delay(1500);
  return parseToolResult(await rpc("tools/call", { name, arguments: args }));
}

function headerLogoHtml() {
  return `
<a class="mcpwp-brand-logo" href="https://mcpwp.net/" aria-label="MCPWP home">
  <span class="mcpwp-brand-mark" aria-hidden="true">
    <svg viewBox="0 0 64 64" role="img" focusable="false">
      <defs>
        <linearGradient id="mcpwpHeaderBg" x1="9" y1="8" x2="55" y2="56" gradientUnits="userSpaceOnUse">
          <stop stop-color="#77A2FF"/>
          <stop offset="0.48" stop-color="#2F6EF7"/>
          <stop offset="1" stop-color="#34E6B2"/>
        </linearGradient>
        <linearGradient id="mcpwpHeaderStroke" x1="18" y1="16" x2="48" y2="49" gradientUnits="userSpaceOnUse">
          <stop stop-color="#FFFFFF"/>
          <stop offset="0.58" stop-color="#DCE6FF"/>
          <stop offset="1" stop-color="#B5FFF0"/>
        </linearGradient>
      </defs>
      <rect width="64" height="64" rx="16" fill="#080D14"/>
      <rect x="8" y="8" width="48" height="48" rx="13" fill="url(#mcpwpHeaderBg)"/>
      <rect x="10" y="10" width="44" height="44" rx="11" stroke="white" stroke-opacity=".22" stroke-width="1.25"/>
      <path d="M18 22 26 32 18 42" stroke="#07101C" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M18 22 26 32 18 42" stroke="url(#mcpwpHeaderStroke)" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M30 42 34 20 39 42 44 20 49 42" stroke="#07101C" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M30 42 34 20 39 42 44 20 49 42" stroke="url(#mcpwpHeaderStroke)" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M26 32h4" stroke="#B5FFF0" stroke-width="2.4" stroke-linecap="round"/>
      <circle cx="14.5" cy="22" r="3.4" fill="#07101C"/>
      <circle cx="14.5" cy="22" r="1.7" fill="#34E6B2"/>
      <circle cx="51.5" cy="42" r="3.4" fill="#07101C"/>
      <circle cx="51.5" cy="42" r="1.7" fill="#fff"/>
    </svg>
  </span>
  <span class="mcpwp-brand-word">MCPWP</span>
</a>
<style>
.mcpwp-brand-logo{display:inline-flex;align-items:center;gap:10px;color:#fff!important;text-decoration:none!important;line-height:1}
.mcpwp-brand-mark{display:inline-flex;width:34px;height:34px;filter:drop-shadow(0 10px 22px rgba(77,134,255,.24))}
.mcpwp-brand-mark svg{display:block;width:100%;height:100%}
.mcpwp-brand-word{font-family:"Space Grotesk","IBM Plex Sans",system-ui,sans-serif;font-size:22px;font-weight:800;letter-spacing:-.04em;color:#f8fbff}
@media(max-width:767px){.mcpwp-brand-mark{width:30px;height:30px}.mcpwp-brand-word{font-size:20px}}
</style>`;
}

function replaceLogoWidget(nodes) {
  for (const node of nodes) {
    if (node.id === "bwg77xi" && node.elType === "widget") {
      node.widgetType = "html";
      node.settings = { html: headerLogoHtml() };
      return true;
    }
    if (Array.isArray(node.elements) && replaceLogoWidget(node.elements)) return true;
  }
  return false;
}

async function main() {
  await rpc("initialize", {
    protocolVersion: "2025-03-26",
    capabilities: {},
    clientInfo: { name: "mcpwp-change-header-logo", version: "0.1" },
  });

  const header = await callTool("wp_get_elementor", { id: HEADER_ID });
  const data = header.elementor_data ?? JSON.parse(header.elementor_json);
  if (!replaceLogoWidget(data)) throw new Error("Header logo widget bwg77xi was not found.");

  const elementor_data_base64 = Buffer.from(JSON.stringify(data), "utf8").toString("base64");
  const dryRun = await callTool("wp_set_elementor", { id: HEADER_ID, elementor_data_base64, dry_run: true });
  const saved = await callTool("wp_set_elementor", { id: HEADER_ID, elementor_data_base64, dry_run: false });
  const css = await callTool("wp_regenerate_elementor_css", {});
  const summary = await callTool("wp_get_elementor_summary", { id: HEADER_ID });

  console.log(JSON.stringify({
    dryRun,
    saved,
    css: { success: css.success, failed_count: css.failed_count, regenerated_count: css.regenerated_count },
    summary,
  }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
