import { getSites } from './registry';
import { decrypt } from './crypto';
import { fetchToolsList, forwardToolCall, siteUrl } from './proxy';
import type { Env } from './types';

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

export async function handleToolsList(id: unknown, agencyId: string, env: Env): Promise<unknown> {
  const sites = await getSites(agencyId, env);

  if (sites.length === 0) {
    return { jsonrpc: '2.0', id, result: { tools: PROXY_TOOLS } };
  }

  let upstreamTools: unknown[] = [];
  let fetchError: unknown;
  for (const site of sites) {
    try {
      upstreamTools = await fetchToolsList(site, env.ENCRYPTION_KEY);
      fetchError = undefined;
      break;
    } catch (err) {
      fetchError = err;
      console.warn(`[mcpwp-proxy] tools/list fetch failed for ${site.site_id}, trying next:`, err);
    }
  }
  if (fetchError !== undefined) {
    console.warn('[mcpwp-proxy] tools/list failed for all sites, returning proxy-only tools');
    return { jsonrpc: '2.0', id, result: { tools: PROXY_TOOLS } };
  }

  const siteIds = sites.map((s) => s.site_id);
  const forwardedTools = (upstreamTools as Array<{
    name: string;
    description: string;
    inputSchema: { type: string; properties: Record<string, unknown>; required: string[] };
  }>).map((tool) => ({
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

  return { jsonrpc: '2.0', id, result: { tools: [...PROXY_TOOLS, ...forwardedTools] } };
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
        const apiKey = await decrypt(s.api_key_enc, env.ENCRYPTION_KEY);
        const resp = await fetch(siteUrl(s.url), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-API-Key': apiKey },
          body: JSON.stringify({ jsonrpc: '2.0', id: 1, method: 'initialize', params: { protocolVersion: '2024-11-05', capabilities: {}, clientInfo: { name: 'proxy-health', version: '1' } } }),
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

  return forwardToolCall(site, name, cleanArgs, env.ENCRYPTION_KEY, id);
}
