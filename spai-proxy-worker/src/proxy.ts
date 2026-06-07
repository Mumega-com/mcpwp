import { decrypt } from './crypto';
import type { SiteEntry } from './types';

export function siteUrl(baseUrl: string): string {
  return baseUrl.replace(/\/$/, '') + '/wp-json/site-pilot-ai/v1/mcp';
}

export async function fetchToolsList(site: SiteEntry, encKey: string): Promise<unknown[]> {
  const apiKey = await decrypt(site.api_key_enc, encKey);
  const resp = await fetch(siteUrl(site.url), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-API-Key': apiKey },
    body: JSON.stringify({ jsonrpc: '2.0', id: 1, method: 'tools/list', params: {} }),
  });
  if (!resp.ok) {
    throw new Error(`Upstream ${site.url} returned ${resp.status}`);
  }
  const data = (await resp.json()) as { result?: { tools?: unknown[] } };
  return data.result?.tools ?? [];
}

export async function forwardToolCall(
  site: SiteEntry,
  toolName: string,
  args: Record<string, unknown>,
  encKey: string,
  reqId: unknown
): Promise<unknown> {
  const apiKey = await decrypt(site.api_key_enc, encKey);
  const resp = await fetch(siteUrl(site.url), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-API-Key': apiKey },
    body: JSON.stringify({
      jsonrpc: '2.0',
      id: reqId,
      method: 'tools/call',
      params: { name: toolName, arguments: args },
    }),
  });
  if (!resp.ok) {
    return {
      jsonrpc: '2.0',
      id: reqId,
      error: { code: -32000, message: `Upstream error: ${resp.status} from ${site.url}` },
    };
  }
  return resp.json();
}
