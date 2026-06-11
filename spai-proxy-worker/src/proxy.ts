// #545: SSRF guard on site.url
// Rejects: IP-literal hosts, RFC1918 (10/8, 172.16/12, 192.168/16),
//           link-local (169.254/16, fe80::), loopback (127/8, ::1, localhost),
//           .internal/.local suffixes. Enforced at add-site time and re-checked
//           on every forward (DNS rebinding defense).

import { decryptForAgency } from './crypto';
import type { SiteEntry } from './types';

export function siteUrl(baseUrl: string): string {
  return baseUrl.replace(/\/$/, '') + '/wp-json/mcpwp/v1/mcp';
}

/**
 * SSRF guard: validates that a URL is safe to forward to.
 * Returns null on success, or an error string describing the rejection.
 * Checked at registration and on each forward request.
 */
export function checkSsrfUrl(url: string): string | null {
  let parsed: URL;
  try {
    parsed = new URL(url);
  } catch {
    return 'url is not a valid URL';
  }

  if (parsed.protocol !== 'https:') {
    return 'url must use HTTPS';
  }

  const host = parsed.hostname.toLowerCase();

  // Block IP-literal IPv6 (e.g. [::1], [fe80::1], [10.0.0.1])
  if (host.startsWith('[')) {
    const addr = host.slice(1, host.endsWith(']') ? host.length - 1 : host.length);
    if (isBlockedIpv6(addr)) {
      return `IP address '${host}' is not allowed (private/loopback/link-local)`;
    }
    return null; // public IPv6 literal — allowed
  }

  // Block IPv4 literals
  if (/^\d{1,3}(\.\d{1,3}){3}$/.test(host)) {
    if (isBlockedIpv4(host)) {
      return `IP address '${host}' is not allowed (private/loopback/link-local)`;
    }
    return null; // public IPv4 literal — allowed
  }

  // Block loopback hostname
  if (host === 'localhost') {
    return "hostname 'localhost' is not allowed";
  }

  // Block .internal and .local suffixes (RFC 6762 / private DNS)
  if (host.endsWith('.internal') || host.endsWith('.local')) {
    return `hostname '${host}' uses a blocked suffix (.internal/.local)`;
  }

  return null; // passes
}

function isBlockedIpv4(ip: string): boolean {
  const parts = ip.split('.').map(Number);
  if (parts.length !== 4 || parts.some((p) => isNaN(p) || p < 0 || p > 255)) return false;
  const [a, b] = parts;
  // 127.0.0.0/8 — loopback
  if (a === 127) return true;
  // 10.0.0.0/8 — RFC1918
  if (a === 10) return true;
  // 172.16.0.0/12 — RFC1918 (172.16 – 172.31)
  if (a === 172 && b >= 16 && b <= 31) return true;
  // 192.168.0.0/16 — RFC1918
  if (a === 192 && b === 168) return true;
  // 169.254.0.0/16 — link-local
  if (a === 169 && b === 254) return true;
  return false;
}

function isBlockedIpv6(addr: string): boolean {
  const lower = addr.toLowerCase();
  // ::1 loopback
  if (lower === '::1') return true;
  // fe80::/10 link-local
  if (lower.startsWith('fe8') || lower.startsWith('fe9') ||
      lower.startsWith('fea') || lower.startsWith('feb')) return true;
  // fc00::/7 unique local (fd00::/8, fc00::/8)
  if (lower.startsWith('fc') || lower.startsWith('fd')) return true;
  // ::ffff:0:0/96 IPv4-mapped — block if mapped address is private
  if (lower.startsWith('::ffff:')) {
    const mapped = lower.slice(7); // e.g. "10.0.0.1" or "0a00:0001"
    // dotted-decimal form
    if (/^\d{1,3}(\.\d{1,3}){3}$/.test(mapped)) {
      return isBlockedIpv4(mapped);
    }
  }
  return false;
}

// ─── Fetch helpers that use decryptForAgency (#546) ─────────────────────────

export async function fetchToolsList(
  site: SiteEntry,
  encKey: string,
  agencyId: string
): Promise<unknown[]> {
  // #545: re-validate URL on every forward (DNS rebinding defense)
  const ssrfErr = checkSsrfUrl(site.url);
  if (ssrfErr) throw new Error(`SSRF guard: ${ssrfErr}`);

  const { plaintext: apiKey } = await decryptForAgency(site.api_key_enc, encKey, agencyId);
  const resp = await fetch(siteUrl(site.url), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-API-Key': apiKey },
    body: JSON.stringify({ jsonrpc: '2.0', id: 1, method: 'tools/list', params: {} }),
    signal: AbortSignal.timeout(30000),
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
  reqId: unknown,
  agencyId: string
): Promise<unknown> {
  // #545: re-validate URL on every forward
  const ssrfErr = checkSsrfUrl(site.url);
  if (ssrfErr) {
    return {
      jsonrpc: '2.0',
      id: reqId,
      error: { code: -32000, message: `SSRF guard: ${ssrfErr}` },
    };
  }

  const { plaintext: apiKey } = await decryptForAgency(site.api_key_enc, encKey, agencyId);
  const resp = await fetch(siteUrl(site.url), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-API-Key': apiKey },
    body: JSON.stringify({
      jsonrpc: '2.0',
      id: reqId,
      method: 'tools/call',
      params: { name: toolName, arguments: args },
    }),
    signal: AbortSignal.timeout(30000),
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
