import { describe, it, expect, vi, afterEach } from 'vitest';
import { fetchToolsList, forwardToolCall, siteUrl, checkSsrfUrl, fetchNoRedirect } from '../src/proxy';
import type { SiteEntry } from '../src/types';

const TEST_KEY = 'a'.repeat(64);
const AGENCY_ID = 'agency-test';

const site: SiteEntry = {
  site_id: '00000000-0000-0000-0000-000000000001',
  url: 'https://test-site.com',
  api_key_enc: '', // set per-test via encryptForAgency
  label: 'Test',
  added_at: '2026-06-07T00:00:00Z',
};

afterEach(() => vi.restoreAllMocks());

describe('siteUrl', () => {
  it('appends WP REST MCP path', () => {
    expect(siteUrl('https://example.com')).toBe('https://example.com/wp-json/mcpwp/v1/mcp');
    expect(siteUrl('https://example.com/')).toBe('https://example.com/wp-json/mcpwp/v1/mcp');
  });
});

describe('#545 checkSsrfUrl', () => {
  it('allows public HTTPS URLs', () => {
    expect(checkSsrfUrl('https://example.com')).toBeNull();
    expect(checkSsrfUrl('https://my-client.wordpress.com')).toBeNull();
    expect(checkSsrfUrl('https://1.1.1.1')).toBeNull(); // public IP allowed
  });

  it('rejects http:// URLs', () => {
    expect(checkSsrfUrl('http://example.com')).toMatch(/HTTPS/i);
  });

  it('rejects loopback 127.x.x.x (#545)', () => {
    expect(checkSsrfUrl('https://127.0.0.1')).toMatch(/not allowed/i);
    expect(checkSsrfUrl('https://127.1.2.3')).toMatch(/not allowed/i);
  });

  it('rejects localhost (#545)', () => {
    expect(checkSsrfUrl('https://localhost')).toMatch(/not allowed/i);
  });

  it('rejects RFC1918 10.0.0.0/8 (#545)', () => {
    expect(checkSsrfUrl('https://10.0.0.1')).toMatch(/not allowed/i);
    expect(checkSsrfUrl('https://10.255.255.255')).toMatch(/not allowed/i);
  });

  it('rejects RFC1918 172.16.0.0/12 (#545)', () => {
    expect(checkSsrfUrl('https://172.16.0.1')).toMatch(/not allowed/i);
    expect(checkSsrfUrl('https://172.31.255.255')).toMatch(/not allowed/i);
    // Outside /12 — must be allowed
    expect(checkSsrfUrl('https://172.15.0.1')).toBeNull();
    expect(checkSsrfUrl('https://172.32.0.1')).toBeNull();
  });

  it('rejects RFC1918 192.168.0.0/16 (#545)', () => {
    expect(checkSsrfUrl('https://192.168.1.1')).toMatch(/not allowed/i);
  });

  it('rejects link-local 169.254.x.x (metadata endpoint) (#545)', () => {
    expect(checkSsrfUrl('https://169.254.169.254')).toMatch(/not allowed/i);
  });

  it('rejects IPv6 loopback ::1 (#545)', () => {
    expect(checkSsrfUrl('https://[::1]')).toMatch(/not allowed/i);
  });

  it('rejects IPv6 link-local fe80:: (#545)', () => {
    expect(checkSsrfUrl('https://[fe80::1]')).toMatch(/not allowed/i);
  });

  it('rejects .internal suffix (#545)', () => {
    expect(checkSsrfUrl('https://metadata.internal')).toMatch(/blocked suffix/i);
    expect(checkSsrfUrl('https://db.prod.internal')).toMatch(/blocked suffix/i);
  });

  it('rejects .local suffix (#545)', () => {
    expect(checkSsrfUrl('https://myservice.local')).toMatch(/blocked suffix/i);
  });

  it('rejects invalid URL', () => {
    expect(checkSsrfUrl('not-a-url')).toMatch(/valid URL/i);
  });
});

describe('fetchToolsList', () => {
  it('returns tools array from upstream', async () => {
    const { encryptForAgency } = await import('../src/crypto');
    const encKey = await encryptForAgency('spai_test_api_key', TEST_KEY, AGENCY_ID);
    const testSite = { ...site, api_key_enc: encKey };

    const mockTools = [{ name: 'wp_list_pages', description: 'List pages', inputSchema: { type: 'object', properties: {}, required: [] } }];
    vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(
      new Response(JSON.stringify({ jsonrpc: '2.0', id: 1, result: { tools: mockTools } }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      })
    );

    const tools = await fetchToolsList(testSite, TEST_KEY, AGENCY_ID);
    expect(tools).toHaveLength(1);
    expect((tools[0] as { name: string }).name).toBe('wp_list_pages');
  });

  it('throws on upstream non-200', async () => {
    const { encryptForAgency } = await import('../src/crypto');
    const encKey = await encryptForAgency('spai_key', TEST_KEY, AGENCY_ID);
    const testSite = { ...site, api_key_enc: encKey };
    vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(new Response('', { status: 500 }));
    await expect(fetchToolsList(testSite, TEST_KEY, AGENCY_ID)).rejects.toThrow('Upstream');
  });

  it('throws SSRF guard error for private IP URL without fetching (#545)', async () => {
    const testSite = { ...site, url: 'https://192.168.1.1' };
    const fetchSpy = vi.spyOn(globalThis, 'fetch');
    await expect(fetchToolsList(testSite, TEST_KEY, AGENCY_ID)).rejects.toThrow(/SSRF/i);
    expect(fetchSpy).not.toHaveBeenCalled();
  });
});

describe('forwardToolCall', () => {
  it('returns upstream result verbatim', async () => {
    const { encryptForAgency } = await import('../src/crypto');
    const encKey = await encryptForAgency('spai_key', TEST_KEY, AGENCY_ID);
    const testSite = { ...site, api_key_enc: encKey };
    const upstreamResult = { jsonrpc: '2.0', id: 42, result: { content: [{ type: 'text', text: '[]' }] } };
    vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(
      new Response(JSON.stringify(upstreamResult), { status: 200, headers: { 'Content-Type': 'application/json' } })
    );

    const result = await forwardToolCall(testSite, 'wp_list_pages', { status: 'draft' }, TEST_KEY, 42, AGENCY_ID);
    expect(result).toEqual(upstreamResult);
  });

  it('returns JSON-RPC error on upstream non-200', async () => {
    const { encryptForAgency } = await import('../src/crypto');
    const encKey = await encryptForAgency('spai_key', TEST_KEY, AGENCY_ID);
    const testSite = { ...site, api_key_enc: encKey };
    vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(new Response('', { status: 503 }));

    const result = await forwardToolCall(testSite, 'wp_list_pages', {}, TEST_KEY, 1, AGENCY_ID) as { error: { code: number } };
    expect(result.error).toBeDefined();
    expect(result.error.code).toBe(-32000);
  });

  it('returns JSON-RPC SSRF error for private IP without fetching (#545)', async () => {
    const testSite = { ...site, url: 'https://169.254.169.254' };
    const fetchSpy = vi.spyOn(globalThis, 'fetch');
    const result = await forwardToolCall(testSite, 'wp_list_pages', {}, TEST_KEY, 1, AGENCY_ID) as { error: { code: number; message: string } };
    expect(result.error).toBeDefined();
    expect(result.error.message).toMatch(/SSRF/i);
    expect(fetchSpy).not.toHaveBeenCalled();
  });
});

// ─── Warden SSRF host-parsing matrix ────────────────────────────────────────

describe('#545 checkSsrfUrl — Warden host-parsing matrix', () => {
  // IPv6 literal: IPv4-mapped link-local
  it('blocks [::ffff:169.254.169.254] (IPv4-mapped link-local)', () => {
    expect(checkSsrfUrl('https://[::ffff:169.254.169.254]')).toMatch(/not allowed/i);
  });

  // IPv6 literal: IPv4-mapped loopback in hex-hextet form (was bypass before fix)
  it('blocks [::ffff:7f00:1] (IPv4-mapped loopback hex-hextet)', () => {
    expect(checkSsrfUrl('https://[::ffff:7f00:1]')).toMatch(/not allowed/i);
  });

  // Decimal integer encoding of 127.0.0.1.
  // The Web URL parser normalises 2130706433 → 127.0.0.1 before our guard sees
  // the hostname, so isBlockedIpv4 catches it as a loopback address.
  it('blocks 2130706433 (decimal 127.0.0.1 — parsed by URL to dotted-quad)', () => {
    expect(checkSsrfUrl('https://2130706433')).not.toBeNull();
  });

  // Hex integer encoding of 127.0.0.1 — same parser normalisation.
  it('blocks 0x7f000001 (hex 127.0.0.1 — parsed by URL to dotted-quad)', () => {
    expect(checkSsrfUrl('https://0x7f000001')).not.toBeNull();
  });

  // Short-form IPv4 127.1 — the Web URL parser normalises this to 127.0.0.1.
  it('blocks 127.1 (non-canonical IPv4 — parsed by URL to 127.0.0.1)', () => {
    expect(checkSsrfUrl('https://127.1')).not.toBeNull();
  });

  // 0.0.0.0 — unspecified, routes to loopback on many stacks
  it('blocks 0.0.0.0', () => {
    expect(checkSsrfUrl('https://0.0.0.0')).toMatch(/not allowed/i);
  });

  // Trailing-dot localhost resolves to loopback
  it('blocks localhost. (trailing-dot stripped → localhost)', () => {
    expect(checkSsrfUrl('https://localhost.')).toMatch(/not allowed/i);
  });

  // :: unspecified IPv6
  it('blocks [::] (IPv6 unspecified)', () => {
    expect(checkSsrfUrl('https://[::]')).toMatch(/not allowed/i);
  });

  // Sanity: a normal public URL must still be allowed
  it('allows https://example.com (public URL)', () => {
    expect(checkSsrfUrl('https://example.com')).toBeNull();
  });
});

// ─── Blocker A: redirect-refusal tests ───────────────────────────────────────

describe('Blocker A — fetchNoRedirect refuses 3xx', () => {
  afterEach(() => vi.restoreAllMocks());

  it('passes through 200 responses unchanged', async () => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(
      new Response('{"ok":true}', { status: 200 })
    );
    const resp = await fetchNoRedirect('https://example.com', { method: 'GET' });
    expect(resp.status).toBe(200);
  });

  it('throws on 301 redirect without making a second fetch', async () => {
    const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(
      new Response(null, { status: 301, headers: { Location: 'https://169.254.169.254/latest/meta-data/' } })
    );
    await expect(fetchNoRedirect('https://example.com', { method: 'GET' })).rejects.toThrow(/redirect 301/i);
    // Only called once — never fetched the Location
    expect(fetchSpy).toHaveBeenCalledTimes(1);
  });

  it('throws on 302 redirect without making a second fetch', async () => {
    const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(
      new Response(null, { status: 302, headers: { Location: 'http://192.168.1.1/' } })
    );
    await expect(fetchNoRedirect('https://example.com', { method: 'POST' })).rejects.toThrow(/redirect 302/i);
    expect(fetchSpy).toHaveBeenCalledTimes(1);
  });
});

describe('Blocker A — fetchToolsList refuses upstream 302', () => {
  afterEach(() => vi.restoreAllMocks());

  it('throws (not follows) when upstream returns 302 to internal address', async () => {
    const { encryptForAgency } = await import('../src/crypto');
    const encKey = await encryptForAgency('spai_test_api_key', TEST_KEY, AGENCY_ID);
    const testSite = { ...site, api_key_enc: encKey };

    const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(
      new Response(null, {
        status: 302,
        headers: { Location: 'https://169.254.169.254/latest/meta-data/' },
      })
    );

    await expect(fetchToolsList(testSite, TEST_KEY, AGENCY_ID)).rejects.toThrow(/redirect/i);
    // Only one fetch call — never fetched the Location URL
    expect(fetchSpy).toHaveBeenCalledTimes(1);
  });
});

describe('Blocker A — forwardToolCall refuses upstream 302', () => {
  afterEach(() => vi.restoreAllMocks());

  it('returns JSON-RPC error (not follows) when upstream returns 302', async () => {
    const { encryptForAgency } = await import('../src/crypto');
    const encKey = await encryptForAgency('spai_key', TEST_KEY, AGENCY_ID);
    const testSite = { ...site, api_key_enc: encKey };

    const fetchSpy = vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(
      new Response(null, {
        status: 302,
        headers: { Location: 'https://169.254.169.254/latest/meta-data/' },
      })
    );

    const result = await forwardToolCall(testSite, 'wp_list_pages', {}, TEST_KEY, 99, AGENCY_ID) as {
      error: { code: number; message: string };
    };
    expect(result.error).toBeDefined();
    expect(result.error.code).toBe(-32000);
    expect(result.error.message).toMatch(/redirect/i);
    // Only one fetch call — never fetched the Location URL
    expect(fetchSpy).toHaveBeenCalledTimes(1);
  });
});
