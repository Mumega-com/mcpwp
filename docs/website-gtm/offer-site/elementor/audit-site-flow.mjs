import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const SITE = "https://mcpwp.net";
const OUTPUT = `${ROOT}/Offer website for https_github.comMumega-commcpwp/elementor/site-flow-audit.json`;

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
  await delay(1200);
  return parseToolResult(await rpc("tools/call", { name, arguments: args }));
}

function normalizeUrl(url) {
  try {
    const parsed = new URL(url, SITE);
    parsed.hash = "";
    return parsed.toString();
  } catch {
    return "";
  }
}

function strip(value) {
  return String(value)
    .replace(/<script[\s\S]*?<\/script>/gi, " ")
    .replace(/<style[\s\S]*?<\/style>/gi, " ")
    .replace(/<[^>]+>/g, " ")
    .replace(/&nbsp;/g, " ")
    .replace(/&amp;/g, "&")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">")
    .replace(/&#8211;/g, "-")
    .replace(/&#8212;/g, "-")
    .replace(/&#038;/g, "&")
    .replace(/\s+/g, " ")
    .trim();
}

function isPublicPage(page) {
  const title = (page.title || "").toLowerCase();
  const slug = (page.slug || "").toLowerCase();
  if (slug.includes("draft") || title.includes("draft")) return false;
  if (["header", "footer", "single-post-template", "blog-editorial-default", "landing-showcase-default", "service-conversion-default"].includes(slug)) return false;
  if (title.startsWith("spai:")) return false;
  if (title.includes("smoke")) return false;
  return true;
}

async function fetchPage(url) {
  const response = await fetch(url, { redirect: "follow" });
  const html = await response.text();
  return { status: response.status, finalUrl: response.url, html };
}

function extract(html, pageUrl) {
  const title = (html.match(/<title[^>]*>(.*?)<\/title>/is)?.[1] || "").replace(/\s+/g, " ").trim();
  const h1s = [...html.matchAll(/<h1[^>]*>(.*?)<\/h1>/gis)].map((m) => strip(m[1])).filter(Boolean).slice(0, 4);
  const links = [...html.matchAll(/<a\s+[^>]*href=["']([^"']+)["'][^>]*>(.*?)<\/a>/gis)]
    .map((m) => ({ href: normalizeUrl(m[1]), text: strip(m[2]) }))
    .filter((link) => link.href && link.text)
    .slice(0, 250);
  const pageText = strip(html).replace(/\s+/g, " ");
  const ctas = links.filter((link) => /download|pricing|plan|setup|start|demo|get started|docs|contact|github|install|continue/i.test(link.text)).slice(0, 20);
  const internalLinks = [...new Set(links.filter((link) => link.href.startsWith(SITE)).map((link) => link.href))].slice(0, 80);
  const stale = {
    legacyNaming: /\bmumcp\b|Mumega MCP|Site Pilot AI/i.test(pageText),
    fixedCounts: /239 tools|207 tools|24 blueprints|50\+ MCP tools/i.test(pageText),
    archiveHeading: h1s.some((h1) => /^Archives$/i.test(h1)),
    brokenRepoSlug: /Mumega-com\/mcp-for-wpwp-ai-operator/i.test(pageText),
    deadLoopDownload: pageUrl.endsWith("/download/") && !/https:\/\/mumega\.com\/.+latest\.zip\?v=\d+\.\d+\.\d+/.test(html),
  };
  return { title, h1s, links, internalLinks, ctas, stale };
}

async function checkUrl(url) {
  try {
    const response = await fetch(url, { method: "HEAD", redirect: "follow" });
    return { url, status: response.status, finalUrl: response.url };
  } catch (error) {
    return { url, status: 0, error: error.message };
  }
}

async function main() {
  await rpc("initialize", {
    protocolVersion: "2025-03-26",
    capabilities: {},
    clientInfo: { name: "mcpwp-site-flow-auditor", version: "0.1" },
  });

  const pageResult = await callTool("wp_list_pages", {
    status: "publish",
    per_page: 100,
    fields: "id,title,slug,status,url,has_elementor,template,modified",
  });
  const pages = (pageResult.pages || []).filter(isPublicPage);
  const pageReports = [];

  for (const page of pages) {
    const url = normalizeUrl(page.url || `${SITE}/${page.slug}/`);
    const rendered = await fetchPage(url);
    const observed = extract(rendered.html, url);
    pageReports.push({ page, url, status: rendered.status, finalUrl: rendered.finalUrl, ...observed });
  }

  const allInternal = [...new Set(pageReports.flatMap((report) => report.internalLinks))];
  const checked = [];
  for (const url of allInternal) {
    checked.push(await checkUrl(url));
  }

  const knownUrls = new Set(pageReports.map((report) => normalizeUrl(report.url)));
  const recommendations = pageReports.map((report) => {
    const staleKeys = Object.entries(report.stale).filter(([, value]) => value).map(([key]) => key);
    const hasDownload = report.ctas.some((cta) => /download/i.test(cta.text));
    const hasSetup = report.ctas.some((cta) => /setup|start|get started|continue/i.test(cta.text));
    const hasPricing = report.ctas.some((cta) => /pricing|plan/i.test(cta.text));
    const needsFlowCta = !hasDownload && !hasSetup && !hasPricing && !/privacy|terms/i.test(report.page.title || "");
    return {
      id: report.page.id,
      title: report.page.title,
      url: report.url,
      hasElementor: report.page.has_elementor,
      h1s: report.h1s,
      stale: staleKeys,
      ctaCount: report.ctas.length,
      needsFlowCta,
      orphanFromMainSet: ![...knownUrls].some((candidate) => candidate !== report.url && pageReports.find((r) => r.url === candidate)?.internalLinks.includes(report.url)),
    };
  });

  const brokenLinks = checked.filter((item) => item.status >= 400 || item.status === 0);
  const result = { generatedAt: new Date().toISOString(), pageCount: pages.length, pages: pageReports, brokenLinks, recommendations };
  fs.writeFileSync(OUTPUT, JSON.stringify(result, null, 2));
  console.log(JSON.stringify({
    output: OUTPUT,
    pageCount: pages.length,
    brokenLinks: brokenLinks.slice(0, 20),
    stalePages: recommendations.filter((item) => item.stale.length),
    needsFlowCta: recommendations.filter((item) => item.needsFlowCta),
    orphanFromMainSet: recommendations.filter((item) => item.orphanFromMainSet).slice(0, 30),
  }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
