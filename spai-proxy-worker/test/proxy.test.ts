import { describe, it, expect, vi, afterEach } from 'vitest';
import { fetchToolsList, forwardToolCall, siteUrl } from '../src/proxy';
import type { SiteEntry } from '../src/types';

const TEST_KEY = 'a'.repeat(64);

const site: SiteEntry = {
  site_id: 'test-site',
  url: 'https://test-site.com',
  api_key_enc: '', // set per-test via encrypt
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

describe('fetchToolsList', () => {
  it('returns tools array from upstream', async () => {
    const { encrypt } = await import('../src/crypto');
    const encKey = await encrypt('spai_test_api_key', TEST_KEY);
    const testSite = { ...site, api_key_enc: encKey };

    const mockTools = [{ name: 'wp_list_pages', description: 'List pages', inputSchema: { type: 'object', properties: {}, required: [] } }];
    vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(
      new Response(JSON.stringify({ jsonrpc: '2.0', id: 1, result: { tools: mockTools } }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      })
    );

    const tools = await fetchToolsList(testSite, TEST_KEY);
    expect(tools).toHaveLength(1);
    expect((tools[0] as any).name).toBe('wp_list_pages');
  });

  it('throws on upstream non-200', async () => {
    const { encrypt } = await import('../src/crypto');
    const encKey = await encrypt('spai_key', TEST_KEY);
    const testSite = { ...site, api_key_enc: encKey };
    vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(new Response('', { status: 500 }));
    await expect(fetchToolsList(testSite, TEST_KEY)).rejects.toThrow('Upstream');
  });
});

describe('forwardToolCall', () => {
  it('returns upstream result verbatim', async () => {
    const { encrypt } = await import('../src/crypto');
    const encKey = await encrypt('spai_key', TEST_KEY);
    const testSite = { ...site, api_key_enc: encKey };
    const upstreamResult = { jsonrpc: '2.0', id: 42, result: { content: [{ type: 'text', text: '[]' }] } };
    vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(
      new Response(JSON.stringify(upstreamResult), { status: 200, headers: { 'Content-Type': 'application/json' } })
    );

    const result = await forwardToolCall(testSite, 'wp_list_pages', { status: 'draft' }, TEST_KEY, 42);
    expect(result).toEqual(upstreamResult);
  });

  it('returns JSON-RPC error on upstream non-200', async () => {
    const { encrypt } = await import('../src/crypto');
    const encKey = await encrypt('spai_key', TEST_KEY);
    const testSite = { ...site, api_key_enc: encKey };
    vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(new Response('', { status: 503 }));

    const result = await forwardToolCall(testSite, 'wp_list_pages', {}, TEST_KEY, 1) as any;
    expect(result.error).toBeDefined();
    expect(result.error.code).toBe(-32000);
  });
});
