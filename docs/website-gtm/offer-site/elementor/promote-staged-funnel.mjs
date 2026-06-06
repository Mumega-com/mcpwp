import fs from "node:fs";
import path from "node:path";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const BACKUP_DIR = `${ROOT}/Offer website for https_github.comMumega-commcpwp/elementor/backups`;

const deploy = process.argv.includes("--deploy");
const confirmed = process.argv.includes("--confirm-promote");

const routes = [
  {
    name: "homepage",
    sourceId: 541,
    targetId: 95,
    targetTemplate: "elementor_header_footer",
    targetUrl: "https://mcpwp.net/",
  },
  {
    name: "pricing",
    sourceId: 556,
    targetId: 502,
    targetTemplate: "elementor_header_footer",
    targetUrl: "https://mcpwp.net/pricing/",
  },
  {
    name: "download",
    sourceId: 560,
    targetId: 33,
    targetTemplate: "elementor_header_footer",
    targetUrl: "https://mcpwp.net/download/",
  },
  {
    name: "first-connection",
    sourceId: 552,
    targetId: 34,
    targetTemplate: "elementor_header_footer",
    targetUrl: "https://mcpwp.net/get-started/",
  },
];

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

function normalizeElementorPayload(elementorResult) {
  const candidates = [
    elementorResult?.elementor_data,
    elementorResult?.data,
    elementorResult?.raw_data,
    elementorResult?.elements,
  ];
  const value = candidates.find((candidate) => candidate !== undefined);
  if (value === undefined) throw new Error(`Could not find Elementor data in result keys: ${Object.keys(elementorResult ?? {}).join(", ")}`);
  return typeof value === "string" ? value : JSON.stringify(value);
}

function parseElementorPayloadForBackup(payload) {
  try {
    return JSON.parse(payload);
  } catch {
    return payload;
  }
}

function staleClaimReport(text) {
  return {
    hasTbd: /\bTBD\b/i.test(text),
    hasFixedCounts: /239|24 blueprints|up to 239/i.test(text),
    hasLegacyNaming: /mumcp|mumega-mcp/i.test(text),
    hasOldUpdateUrl: /mumega-mcp-latest|mcp-updates/i.test(text),
    hasHardPrice: /\$\d/.test(text),
  };
}

async function getRouteReport(route) {
  const sourceSummary = await callTool("wp_get_elementor_summary", { id: route.sourceId });
  const targetSummary = await callTool("wp_get_elementor_summary", { id: route.targetId });
  const sourcePreview = await callTool("wp_preview_elementor", { id: route.sourceId, format: "text" });
  const sourceElementor = await callTool("wp_get_elementor", { id: route.sourceId, strip_defaults: false });
  const targetElementor = await callTool("wp_get_elementor", { id: route.targetId, strip_defaults: false });
  const sourceText = typeof sourcePreview === "string" ? sourcePreview : sourcePreview.text ?? JSON.stringify(sourcePreview);
  return {
    route,
    sourceSummary,
    targetSummary,
    sourceText,
    sourcePayload: normalizeElementorPayload(sourceElementor),
    targetPayload: normalizeElementorPayload(targetElementor),
    staleClaims: staleClaimReport(sourceText),
  };
}

async function main() {
  await rpc("initialize", {
    protocolVersion: "2025-03-26",
    capabilities: {},
    clientInfo: { name: "mcpwp-funnel-promoter", version: "0.1" },
  });

  if (deploy && !confirmed) {
    throw new Error("Promotion requires both --deploy and --confirm-promote. Run without flags for a dry-run audit.");
  }

  const reports = [];
  for (const route of routes) {
    const report = await getRouteReport(route);
    const staleValues = Object.entries(report.staleClaims).filter(([, value]) => value);
    const sourceOk = Boolean(report.sourceSummary?.has_elementor && report.sourceSummary?.section_count > 0);
    reports.push({
      route: report.route.name,
      sourceId: report.route.sourceId,
      targetId: report.route.targetId,
      targetUrl: report.route.targetUrl,
      sourceSections: report.sourceSummary?.section_count ?? null,
      targetSectionsBefore: report.targetSummary?.section_count ?? null,
      targetHadElementor: Boolean(report.targetSummary?.has_elementor),
      sourceOk,
      staleClaims: report.staleClaims,
      ready: sourceOk && staleValues.length === 0,
    });
  }

  const notReady = reports.filter((report) => !report.ready);
  if (!deploy || notReady.length) {
    console.log(JSON.stringify({
      deploy: false,
      readyToPromote: notReady.length === 0,
      notReady,
      reports,
      next: notReady.length === 0
        ? `Run: node ${process.argv[1]} --deploy --confirm-promote`
        : "Fix notReady routes before promotion.",
    }, null, 2));
    return;
  }

  fs.mkdirSync(BACKUP_DIR, { recursive: true });
  const timestamp = new Date().toISOString().replace(/[:.]/g, "-");
  const results = [];

  for (const route of routes) {
    const report = await getRouteReport(route);
    const backupPath = path.join(BACKUP_DIR, `${timestamp}-${route.name}-target-${route.targetId}.json`);
    fs.writeFileSync(backupPath, JSON.stringify({
      route,
      targetSummary: report.targetSummary,
      targetPayload: parseElementorPayloadForBackup(report.targetPayload),
    }, null, 2));

    const elementor_data_base64 = Buffer.from(report.sourcePayload, "utf8").toString("base64");
    const dryRun = await callTool("wp_set_elementor", {
      id: route.targetId,
      elementor_data_base64,
      dry_run: true,
    });
    const saved = await callTool("wp_set_elementor", {
      id: route.targetId,
      elementor_data_base64,
      dry_run: false,
    });
    const template = await callTool("wp_update_page_template", {
      id: route.targetId,
      template: route.targetTemplate,
    });
    results.push({ route: route.name, targetId: route.targetId, backupPath, dryRun, saved, template });
  }

  const css = await callTool("wp_regenerate_elementor_css", {});
  const summaries = [];
  for (const route of routes) {
    summaries.push(await callTool("wp_get_elementor_summary", { id: route.targetId }));
  }
  console.log(JSON.stringify({ deploy: true, results, css, summaries }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
