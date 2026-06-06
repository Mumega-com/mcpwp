import fs from "node:fs";
import path from "node:path";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const BACKUP_DIR = `${ROOT}/Offer website for https_github.comMumega-commcpwp/elementor/backups`;

const backupPathArg = process.argv.find((arg) => arg.startsWith("--backup="));
const deploy = process.argv.includes("--deploy");
const confirmed = process.argv.includes("--confirm-restore");

if (!backupPathArg) {
  console.error("Usage: node restore-funnel-backup.mjs --backup=/absolute/path/to/backup.json [--deploy --confirm-restore]");
  process.exit(1);
}

const backupPath = path.resolve(backupPathArg.replace("--backup=", ""));
if (!backupPath.startsWith(BACKUP_DIR)) {
  console.error(`Backup must be inside ${BACKUP_DIR}`);
  process.exit(1);
}

const config = JSON.parse(fs.readFileSync(MCP_CONFIG, "utf8")).mcpServers.mcpwp;
const backup = JSON.parse(fs.readFileSync(backupPath, "utf8"));
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

function payloadToString(payload) {
  return typeof payload === "string" ? payload : JSON.stringify(payload);
}

async function main() {
  if (deploy && !confirmed) {
    throw new Error("Restore requires both --deploy and --confirm-restore. Run without flags for a dry-run audit.");
  }

  await rpc("initialize", {
    protocolVersion: "2025-03-26",
    capabilities: {},
    clientInfo: { name: "mcpwp-funnel-restore", version: "0.1" },
  });

  const targetId = backup?.route?.targetId;
  const targetTemplate = backup?.route?.targetTemplate;
  if (!targetId || !backup.targetPayload) {
    throw new Error("Backup is missing route.targetId or targetPayload.");
  }

  const elementor_data_base64 = Buffer.from(payloadToString(backup.targetPayload), "utf8").toString("base64");
  const dryRun = await callTool("wp_set_elementor", {
    id: targetId,
    elementor_data_base64,
    dry_run: true,
  });

  if (!deploy) {
    console.log(JSON.stringify({
      deploy: false,
      backupPath,
      targetId,
      route: backup.route?.name,
      targetTemplate,
      dryRun,
      next: `Run: node ${process.argv[1]} --backup=${backupPath} --deploy --confirm-restore`,
    }, null, 2));
    return;
  }

  const saved = await callTool("wp_set_elementor", {
    id: targetId,
    elementor_data_base64,
    dry_run: false,
  });
  const template = targetTemplate
    ? await callTool("wp_update_page_template", { id: targetId, template: targetTemplate })
    : null;
  const css = await callTool("wp_regenerate_elementor_css", {});
  const summary = await callTool("wp_get_elementor_summary", { id: targetId });

  console.log(JSON.stringify({
    deploy: true,
    backupPath,
    targetId,
    route: backup.route?.name,
    saved,
    template,
    css,
    summary,
  }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
