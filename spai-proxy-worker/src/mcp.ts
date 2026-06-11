// #542: tools/list must not advertise one site's schema for all heterogeneous sites.
//
// Design:
//   - 0 sites → proxy-native tools only.
//   - 1 site  → proxy-native tools + that site's tools (enum scoped to that one site).
//   - N sites, _site hint provided (param or X-Mcpwp-Site header) → proxy-native + that
//              one site's tools with enum scoped to caller's sites.
//   - N sites, no hint → proxy-native tools only, with a guide description instructing
//              the client to call proxy_list_sites and then pass _site.
//
// Per-site concurrency is capped at 1 request (single-site fetch, no fan-out).
// 30s timeout (CF Worker limit). Response trimmed if tools list would exceed 900 KB
// to stay under the CF 1 MB response limit with headroom for JSON framing.

import { getSites } from './registry';
import { decryptForAgency } from './crypto';
import { fetchToolsList, forwardToolCall, siteUrl } from './proxy';
import type { Env, SiteEntry } from './types';

const PROXY_TOOLS = [
  {
    name: 'proxy_list_sites',
    description: 'List all WordPress sites registered to this agency account.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },
  {
    name: 'proxy_site_health',
    description: 'Check connectivity to all registered sites. Returns status for each.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },
];

// Instruction tool injected when multiple sites exist but no _site is selected.
// Guides the client to discover sites before requesting a real tool list.
const MULTI_SITE_GUIDE_TOOL = {
  name: 'proxy_select_site',
  description:
    'Multiple WordPress sites are registered. To see tools for a specific site, ' +
    'call proxy_list_sites first to get site IDs, then repeat tools/list with ' +
    'the X-Mcpwp-Site header or the _site param set to the desired site ID.',
  inputSchema: { type: 'object', properties: {}, required: [] },
};

// Max byte size of the tools JSON payload before trimming to stay under CF 1 MB limit.
const MAX_TOOLS_PAYLOAD_BYTES = 900_000;

type ToolDef = {
  name: string;
  description: string;
  inputSchema: { type: string; properties: Record<string, unknown>; required: string[] };
};

function injectSiteParam(tools: ToolDef[], siteIds: string[]): ToolDef[] {
  return tools.map((tool) => ({
    ...tool,
    inputSchema: {
      ...tool.inputSchema,
      properties: {
        _site: {
          type: 'string',
          enum: siteIds,
          description: `Target WordPress site. Registered: ${siteIds.join(', ')}`,
        },
        ...(tool.inputSchema?.properties ?? {}),
      },
      required: ['_site', ...(tool.inputSchema?.required ?? [])],
    },
  }));
}

function trimToLimit(tools: unknown[]): unknown[] {
  let cumulative = 0;
  const result: unknown[] = [];
  for (const tool of tools) {
    const bytes = JSON.stringify(tool).length;
    if (cumulative + bytes > MAX_TOOLS_PAYLOAD_BYTES) break;
    result.push(tool);
    cumulative += bytes;
  }
  return result;
}

export function handleInitialize(id: unknown): unknown {
  return {
    jsonrpc: '2.0',
    id,
    result: {
      protocolVersion: '2024-11-05',
      capabilities: { tools: {} },
      serverInfo: { name: 'mcpwp-agency-proxy', version: '1.0.0' },
    },
  };
}

/**
 * handleToolsList — #542 corrected multi-site behaviour.
 *
 * @param siteHint  Optional site_id from X-Mcpwp-Site header or params._site
 */
export async function handleToolsList(
  id: unknown,
  agencyId: string,
  env: Env,
  siteHint?: string
): Promise<unknown> {
  const sites = await getSites(agencyId, env);

  if (sites.length === 0) {
    return { jsonrpc: '2.0', id, result: { tools: PROXY_TOOLS } };
  }

  // Determine the single target site, if any.
  let targetSite: SiteEntry | undefined;

  if (sites.length === 1) {
    targetSite = sites[0];
  } else if (siteHint) {
    // Validate hint belongs to this agency (#542: scope enum to caller's sites only).
    targetSite = sites.find((s) => s.site_id === siteHint);
    // If hint is unknown, fall through to multi-site guide response.
  }

  if (!targetSite) {
    // Multiple sites, no valid selection → guide the client.
    return {
      jsonrpc: '2.0',
      id,
      result: { tools: [...PROXY_TOOLS, MULTI_SITE_GUIDE_TOOL] },
    };
  }

  // Fetch tools from the selected site only.
  let upstreamTools: unknown[];
  try {
    upstreamTools = await fetchToolsList(targetSite, env.ENCRYPTION_KEY, agencyId);
  } catch (err) {
    console.warn(`[mcpwp-proxy] tools/list fetch failed for ${targetSite.site_id}:`, err);
    return { jsonrpc: '2.0', id, result: { tools: PROXY_TOOLS } };
  }

  // Scope the _site enum to this agency's sites (not all sites globally).
  const siteIds = sites.map((s) => s.site_id);
  const forwardedTools = injectSiteParam(upstreamTools as ToolDef[], siteIds);
  const combined = [...PROXY_TOOLS, ...forwardedTools];
  const trimmed = trimToLimit(combined);

  return { jsonrpc: '2.0', id, result: { tools: trimmed } };
}

export async function handleToolsCall(
  id: unknown,
  params: { name: string; arguments: Record<string, unknown> },
  agencyId: string,
  env: Env
): Promise<unknown> {
  const { name, arguments: args = {} } = params;

  if (name === 'proxy_list_sites') {
    const sites = await getSites(agencyId, env);
    const list = sites.map((s) => ({ site_id: s.site_id, url: s.url, label: s.label }));
    return {
      jsonrpc: '2.0',
      id,
      result: { content: [{ type: 'text', text: JSON.stringify(list, null, 2) }] },
    };
  }

  if (name === 'proxy_site_health') {
    const sites = await getSites(agencyId, env);
    const checks = await Promise.allSettled(
      sites.map(async (s) => {
        const { plaintext: apiKey } = await decryptForAgency(
          s.api_key_enc,
          env.ENCRYPTION_KEY,
          agencyId
        );
        const resp = await fetch(siteUrl(s.url), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-API-Key': apiKey },
          body: JSON.stringify({
            jsonrpc: '2.0',
            id: 1,
            method: 'initialize',
            params: {
              protocolVersion: '2024-11-05',
              capabilities: {},
              clientInfo: { name: 'proxy-health', version: '1' },
            },
          }),
          signal: AbortSignal.timeout(5000),
        });
        return { site_id: s.site_id, status: resp.ok ? 'ok' : `error:${resp.status}` };
      })
    );
    const results = checks.map((r, i) =>
      r.status === 'fulfilled' ? r.value : { site_id: sites[i].site_id, status: 'unreachable' }
    );
    return {
      jsonrpc: '2.0',
      id,
      result: { content: [{ type: 'text', text: JSON.stringify(results, null, 2) }] },
    };
  }

  const { _site, ...cleanArgs } = args as { _site?: string } & Record<string, unknown>;

  if (!_site) {
    return {
      jsonrpc: '2.0',
      id,
      error: {
        code: -32602,
        message: 'Missing required param _site. Call proxy_list_sites to see registered sites.',
      },
    };
  }

  const sites = await getSites(agencyId, env);
  const site = sites.find((s) => s.site_id === _site);
  if (!site) {
    return {
      jsonrpc: '2.0',
      id,
      error: {
        code: -32602,
        message: `Unknown site '${_site}'. Call proxy_list_sites to see registered sites.`,
      },
    };
  }

  return forwardToolCall(site, name, cleanArgs, env.ENCRYPTION_KEY, id, agencyId);
}
