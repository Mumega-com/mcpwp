import fs from "node:fs";

const ROOT = "/Users/hadi/dev/mumega/mumcp";
const MCP_CONFIG = `${ROOT}/mcp-for-wp/.mcp.json`;
const PAGE_ID = 33;
const CSS_MARKER = "MCPWP download capture CSS - 2026-06-05";
const RELEASE_JSON = "https://mumega.com/mcp-updates/version.json";
const config = JSON.parse(fs.readFileSync(MCP_CONFIG, "utf8")).mcpServers.mcpwp;
let id = 1;

const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const rand = () => Math.random().toString(36).slice(2, 10);

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
  for (let attempt = 0; attempt < 10; attempt++) {
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

async function releaseInfo() {
  const response = await fetch(RELEASE_JSON, { cache: "no-store" });
  if (!response.ok) throw new Error(`Release JSON failed with HTTP ${response.status}`);
  const release = await response.json();
  const version = String(release.version || "").trim();
  if (!/^\d+\.\d+\.\d+/.test(version)) throw new Error(`Invalid release version: ${version}`);
  const rawUrl = String(release.download_url || "https://mumega.com/spai-updates/mumega-site-pilot-ai-latest.zip").trim();
  const url = new URL(rawUrl);
  if (/latest/i.test(url.pathname)) url.searchParams.set("v", version);
  return { version, downloadUrl: url.toString(), rawUrl, release };
}

function esc(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;");
}

function widget(html) {
  return { id: rand(), elType: "widget", widgetType: "html", settings: { html }, elements: [] };
}

function section(name, html) {
  return {
    id: rand(),
    elType: "section",
    settings: { _css_classes: `mcpwp-capture-block mcpwp-capture-${name}` },
    elements: [
      {
        id: rand(),
        elType: "column",
        settings: { _column_size: 100 },
        elements: [widget(html)],
      },
    ],
  };
}

function formSection(version, downloadUrl, adminEmail) {
  return {
    id: rand(),
    elType: "section",
    settings: { _css_classes: "mcpwp-capture-block mcpwp-capture-form-section" },
    elements: [
      {
        id: rand(),
        elType: "column",
        settings: { _column_size: 100 },
        elements: [
          widget(`<div class="mcpwp-capture"><section class="mcpwp-capture-section mcpwp-capture-form-intro"><div class="mcpwp-capture-wrap"><span class="mcpwp-capture-kicker">Conversion step</span><h2>Email yourself the current package.</h2><p>We send the versioned ZIP link plus the first-session setup path. The direct link remains visible below for transparency, but the recommended path captures the lead and moves them into setup.</p></div></section></div>`),
          {
            id: rand(),
            elType: "widget",
            widgetType: "form",
            settings: {
              form_name: `MCPWP Download v${version}`,
              form_fields: [
                {
                  field_type: "email",
                  field_label: "Work email",
                  placeholder: "you@company.com",
                  required: "true",
                  width: "50",
                  custom_id: "email",
                },
                {
                  field_type: "text",
                  field_label: "Use case",
                  placeholder: "Agency, builder, plugin developer...",
                  required: "",
                  width: "50",
                  custom_id: "use_case",
                },
                {
                  field_type: "url",
                  field_label: "WordPress site URL",
                  placeholder: "https://example.com",
                  required: "",
                  width: "100",
                  custom_id: "site_url",
                },
                {
                  field_type: "hidden",
                  field_label: "Download link",
                  field_value: downloadUrl,
                  value: downloadUrl,
                  required: "",
                  width: "100",
                  custom_id: "download_link",
                },
                {
                  field_type: "hidden",
                  field_label: "Release version",
                  field_value: version,
                  value: version,
                  required: "",
                  width: "100",
                  custom_id: "release_version",
                },
                {
                  field_type: "hidden",
                  field_label: "Lead source",
                  field_value: "mcpwp.net/download",
                  value: "mcpwp.net/download",
                  required: "",
                  width: "100",
                  custom_id: "lead_source",
                },
              ],
              button_text: `Email me MCPWP v${version}`,
              button_size: "lg",
              button_align: "stretch",
              email_to: `${adminEmail}, [field id="email"]`,
              email_subject: `MCPWP v${version} download link`,
              success_message: `Success. Check your inbox for MCPWP v${version}. If email is delayed, use the direct versioned link below and continue to setup.`,
              error_message: "The email did not send. Use the direct versioned link below and continue to setup.",
              required_message: "Please enter your email so we can send the package link.",
            },
            elements: [],
          },
        ],
      },
    ],
  };
}

function pageData({ version, downloadUrl, rawUrl, adminEmail }) {
  return [
    section("hero", `<div class="mcpwp-capture"><section class="mcpwp-capture-hero"><div class="mcpwp-capture-wrap"><span class="mcpwp-capture-kicker">Download</span><h1>Get MCPWP <span>v${esc(version)}</span> and complete your first connection.</h1><p class="mcpwp-capture-lead">The conversion goal is not just a ZIP file. It is a successful first MCP response from the user&apos;s own WordPress site.</p><div class="mcpwp-capture-actions"><a class="mcpwp-capture-btn mcpwp-capture-btn-primary" href="#download-form">Send me the link</a><a class="mcpwp-capture-btn" href="/docs/getting-started/">Read setup guide</a><a class="mcpwp-capture-btn" href="/pricing/">Compare plans</a></div></div></section></div>`),
    formSection(version, downloadUrl, adminEmail),
    section("fallback", `<div class="mcpwp-capture"><section class="mcpwp-capture-section"><div class="mcpwp-capture-wrap"><span class="mcpwp-capture-kicker">Fallback</span><h2>Need the file immediately?</h2><div class="mcpwp-capture-panel"><div><strong>Direct versioned ZIP</strong><p>This fallback is intentionally visible. The URL includes <code>?v=${esc(version)}</code> so browsers and proxies do not serve an older <code>latest.zip</code>.</p><code>${esc(downloadUrl)}</code></div><a class="mcpwp-capture-btn mcpwp-capture-btn-primary" href="${esc(downloadUrl)}" download>Download MCPWP v${esc(version)}</a></div></div></section></div>`),
    section("install", `<div class="mcpwp-capture"><section class="mcpwp-capture-section"><div class="mcpwp-capture-wrap"><span class="mcpwp-capture-kicker">Install</span><h2>Continue from download into setup.</h2><div class="mcpwp-capture-grid"><div class="mcpwp-capture-card"><strong>WordPress admin upload</strong><p>Upload the ZIP inside WordPress and activate it.</p><pre>Plugins -> Add New -> Upload Plugin -> Activate</pre></div><div class="mcpwp-capture-card"><strong>WP-CLI install</strong><p>Use the same resolved URL in managed environments.</p><pre>wp plugin install '${esc(downloadUrl)}' --activate</pre></div><div class="mcpwp-capture-card"><strong>Agency rollout</strong><p>Standardize scopes and first-run checks before enabling writes.</p><pre>Read-only first -> inspect tools -> enable writes</pre></div></div></div></section></div>`),
    section("first-session", `<div class="mcpwp-capture"><section class="mcpwp-capture-section"><div class="mcpwp-capture-wrap"><span class="mcpwp-capture-kicker">First session</span><h2>Make the first MCP response the actual conversion event.</h2><div class="mcpwp-capture-steps"><div><b>01</b><strong>Create a scoped key</strong><p>Start read-only or with tightly scoped categories.</p></div><div><b>02</b><strong>Connect your client</strong><p>Add the WordPress MCP endpoint to Codex, Claude Code, Cursor, or another MCP client.</p></div><div><b>03</b><strong>Inspect before writes</strong><p>Ask the client to list site info and available tools before any update.</p></div></div><pre class="mcpwp-capture-code">"mcpwp": {
  "url": "https://your-site.com/wp-json/site-pilot-ai/v1/mcp",
  "headers": { "X-API-Key": "spai_your_scoped_key" }
}</pre><div class="mcpwp-capture-actions"><a class="mcpwp-capture-btn mcpwp-capture-btn-primary" href="/docs/getting-started/">Continue to setup</a><a class="mcpwp-capture-btn" href="/docs/mcp-tools/">Review tool discovery</a></div><p class="mcpwp-capture-meta">Release source: ${esc(RELEASE_JSON)}<br>Raw package URL: ${esc(rawUrl)}</p></div></section></div>`),
  ];
}

function css() {
  return `
/* ${CSS_MARKER} */
.mcpwp-capture{--bg:#050812;--panel:#101827;--line:#263550;--text:#f7f9ff;--muted:#9aa8bd;--faint:#6f7b8d;--blue:#4d86ff;--cyan:#4be3c2;--warn:#ffcf66;--max:1120px;background:radial-gradient(circle at 20% 0%,rgba(77,134,255,.24),transparent 32%),linear-gradient(180deg,#050812,#070b14);color:var(--text);font-family:"IBM Plex Sans","Space Grotesk",system-ui,sans-serif;overflow:hidden}.mcpwp-capture *{box-sizing:border-box;min-width:0}.mcpwp-capture-wrap{max-width:var(--max);margin:0 auto;padding:0 28px}.mcpwp-capture-hero{padding:112px 0 72px}.mcpwp-capture-section{padding:82px 0;border-top:1px solid rgba(255,255,255,.08)}.mcpwp-capture-form-intro{padding-bottom:24px}.mcpwp-capture-kicker{display:inline-flex;gap:9px;align-items:center;color:var(--cyan);font:800 12px "JetBrains Mono",monospace;letter-spacing:.16em;text-transform:uppercase}.mcpwp-capture-kicker:before{content:"";width:24px;height:1px;background:var(--cyan)}.mcpwp-capture h1,.mcpwp-capture h2,.mcpwp-capture h3{font-family:"Space Grotesk",system-ui,sans-serif;letter-spacing:-.045em;line-height:1.04}.mcpwp-capture h1{max-width:980px;font-size:clamp(46px,7vw,82px);margin:22px 0}.mcpwp-capture h1 span{background:linear-gradient(100deg,#fff 25%,#78a5ff 64%,#4be3c2);-webkit-background-clip:text;background-clip:text;color:transparent}.mcpwp-capture h2{font-size:clamp(32px,4.6vw,54px);margin:18px 0 14px}.mcpwp-capture p{color:var(--muted);font-size:18px;line-height:1.65}.mcpwp-capture-lead{max-width:800px;font-size:clamp(20px,2.2vw,26px)!important}.mcpwp-capture-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}.mcpwp-capture-btn{display:inline-flex;align-items:center;justify-content:center;padding:15px 22px;border-radius:14px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.07);color:#fff!important;text-decoration:none!important;font-weight:800}.mcpwp-capture-btn-primary{background:linear-gradient(135deg,var(--blue),#2f6ef7);border-color:#78a5ff}.mcpwp-capture-panel{display:flex;align-items:center;justify-content:space-between;gap:28px;padding:26px;border:1px solid rgba(75,227,194,.2);border-radius:22px;background:linear-gradient(135deg,rgba(77,134,255,.14),rgba(75,227,194,.07))}.mcpwp-capture-panel strong{display:block;color:#fff;font-size:22px;margin-bottom:6px}.mcpwp-capture-panel code,.mcpwp-capture-code,.mcpwp-capture-card pre{display:block;max-width:100%;white-space:pre-wrap;overflow-wrap:anywhere;color:#d8e8ff;background:rgba(0,0,0,.25);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:14px 16px;font:700 13px/1.6 "JetBrains Mono",monospace}.mcpwp-capture-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.mcpwp-capture-card{padding:24px;border:1px solid rgba(255,255,255,.1);border-radius:20px;background:linear-gradient(180deg,rgba(255,255,255,.055),rgba(255,255,255,.025));overflow:hidden}.mcpwp-capture-card strong{display:block;color:#fff;font-size:18px;margin-bottom:8px}.mcpwp-capture-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin:26px 0}.mcpwp-capture-steps div{padding:22px;border-radius:18px;background:rgba(77,134,255,.06);border:1px solid rgba(77,134,255,.15)}.mcpwp-capture-steps b{display:inline-flex;margin-bottom:18px;width:44px;height:44px;align-items:center;justify-content:center;border-radius:14px;background:rgba(75,227,194,.12);color:var(--cyan);font:900 13px "JetBrains Mono",monospace}.mcpwp-capture-steps strong{display:block;color:#fff;font-size:19px}.mcpwp-capture-meta{margin-top:18px;color:var(--faint)!important;font:600 12px/1.6 "JetBrains Mono",monospace!important;overflow-wrap:anywhere}.mcpwp-capture-form-section{background:#050812!important;padding-bottom:80px}.mcpwp-capture-form-section>.elementor-container{max-width:1120px!important;margin:0 auto!important;padding:0 28px!important}.mcpwp-capture-form-section .elementor-widget-form{max-width:900px;margin:0 auto;padding:28px;border-radius:24px;border:1px solid rgba(75,227,194,.18);background:linear-gradient(180deg,rgba(255,255,255,.07),rgba(255,255,255,.03));box-shadow:0 30px 80px -50px rgba(77,134,255,.85)}.mcpwp-capture-form-section .elementor-field-group{padding:0 8px 16px!important}.mcpwp-capture-form-section label{color:#f7f9ff!important;font-weight:800!important;margin-bottom:8px}.mcpwp-capture-form-section input,.mcpwp-capture-form-section textarea,.mcpwp-capture-form-section select{min-height:50px!important;border-radius:14px!important;border:1px solid rgba(255,255,255,.14)!important;background:#080d16!important;color:#f7f9ff!important;padding:13px 15px!important}.mcpwp-capture-form-section input::placeholder{color:#6f7b8d!important}.mcpwp-capture-form-section .elementor-button{width:100%;min-height:54px;border-radius:15px!important;background:linear-gradient(135deg,var(--blue),#2f6ef7)!important;color:#fff!important;font-weight:900!important;box-shadow:0 22px 50px -28px rgba(77,134,255,.9)}.mcpwp-capture-form-section .elementor-message{color:#d9fff6!important;font-weight:800!important;margin:14px 8px 0!important}@media(max-width:860px){.mcpwp-capture-grid,.mcpwp-capture-steps{grid-template-columns:1fr}.mcpwp-capture-panel{align-items:stretch;flex-direction:column}.mcpwp-capture-wrap{padding:0 20px}.mcpwp-capture-form-section>.elementor-container{padding:0 20px!important}}@media(max-width:560px){.mcpwp-capture-btn{width:100%}.mcpwp-capture-hero{padding-top:76px}.mcpwp-capture-form-section .elementor-widget-form{padding:20px}.mcpwp-capture-code,.mcpwp-capture-card pre{font-size:12px}}
`;
}

async function setElementorPage(data) {
  const elementor_data_base64 = Buffer.from(JSON.stringify(data), "utf8").toString("base64");
  const dryRun = await callTool("wp_set_elementor", { id: PAGE_ID, elementor_data_base64, dry_run: true });
  const saved = await callTool("wp_set_elementor", { id: PAGE_ID, elementor_data_base64, dry_run: false });
  await callTool("wp_update_page_template", { id: PAGE_ID, template: "elementor_header_footer" });
  return { dryRun, saved };
}

async function ensureCss() {
  const customCss = await callTool("wp_get_custom_css", {});
  const current = typeof customCss === "string" ? customCss : customCss.css ?? customCss.custom_css ?? "";
  if (current.includes(CSS_MARKER)) return { skipped: true };
  return callTool("wp_set_custom_css", { css: css(), mode: "append" });
}

async function setMeta(version) {
  return Promise.all([
    callTool("wp_set_post_meta", { id: PAGE_ID, key: "_yoast_wpseo_title", value: "Download MCPWP | Email the Current WordPress MCP Package" }),
    callTool("wp_set_post_meta", { id: PAGE_ID, key: "_yoast_wpseo_metadesc", value: `Email yourself the current MCPWP v${version} package link, install the WordPress MCP plugin, and continue into first-connection setup.` }),
    callTool("wp_set_post_meta", { id: PAGE_ID, key: "_yoast_wpseo_focuskw", value: "download MCPWP" }),
  ]);
}

async function main() {
  await rpc("initialize", {
    protocolVersion: "2025-03-26",
    capabilities: {},
    clientInfo: { name: "mcpwp-download-capture-flow", version: "0.1" },
  });

  const release = await releaseInfo();
  const adminEmail = (await callTool("wp_get_option", { key: "admin_email" }))?.value || "admin@digid.ca";
  const cssResult = await ensureCss();
  const elementor = await setElementorPage(pageData({ ...release, adminEmail }));
  const meta = await setMeta(release.version);
  const cssRegen = await callTool("wp_regenerate_elementor_css", {});
  const summary = await callTool("wp_get_elementor_summary", { id: PAGE_ID });

  console.log(JSON.stringify({
    release,
    adminEmail,
    cssResult,
    elementor,
    meta: meta.map((item) => item?.success ?? item?.updated ?? item),
    cssRegen: { success: cssRegen.success, failed_count: cssRegen.failed_count, regenerated_count: cssRegen.regenerated_count },
    summary,
  }, null, 2));
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
