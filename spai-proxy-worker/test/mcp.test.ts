import { describe, it, expect, vi, afterEach } from 'vitest';
import { handleInitialize, handleToolsList, handleToolsCall } from '../src/mcp';
import type { Env, SiteEntry } from '../src/types';

afterEach(() => vi.restoreAllMocks());

const TEST_KEY = 'a'.repeat(64);

function mockKV(data: Record<string, string> = {}): KVNamespace {
  const store: Record<string, string> = { ...data };
  return {
    get: async (key: string) => store[key] ?? null,
    put: async (key: string, value: string) => { store[key] = value; },
    delete: async (key: string) => { delete store[key]; },
    list: async () => ({ keys: [], list_complete: true, cursor: '' }),
    getWithMetadata: async (key: string) => ({ value: store[key] ?? null, metadata: null }),
  } as unknown as KVNamespace;
}

async function makeEnvWithSite(): Promise<{ env: Env; site: SiteEntry }> {
  const { encrypt } = await import('../src/crypto');
  const api_key_enc = await encrypt('spai_real_key', TEST_KEY);
  const site: SiteEntry = {
    site_id: 'client-a',
    url: 'https://client-a.com',
    api_key_enc,
    label: 'Client A',
    added_at: '2026-06-07T00:00:00Z',
  };
  const kv = mockKV({
    'agency:sites:agency-1': JSON.stringify([site]),
  });
  const env = { AGENCY_KV: kv, ENCRYPTION_KEY: TEST_KEY } as unknown as Env;
  return { env, site };
}

describe('handleInitialize', () => {
  it('returns MCP capabilities', () => {
    const result = handleInitialize(1) as any;
    expect(result.result.protocolVersion).toBe('2024-11-05');
    expect(result.result.serverInfo.name).toBe('mcpwp-agency-proxy');
  });
});

describe('handleToolsList', () => {
  it('returns proxy-native tools when no sites registered', async () => {
    const kv = mockKV({});
    const env = { AGENCY_KV: kv, ENCRYPTION_KEY: TEST_KEY } as unknown as Env;
    const result = await handleToolsList(1, 'agency-1', env) as any;
    const toolNames = result.result.tools.map((t: any) => t.name);
    expect(toolNames).toContain('proxy_list_sites');
    expect(toolNames).toContain('proxy_site_health');
  });

  it('injects _site param with site enum into forwarded tools', async () => {
    const { env } = await makeEnvWithSite();
    const mockTools = [{ name: 'wp_list_pages', description: 'List pages', inputSchema: { type: 'object', properties: {}, required: [] } }];
    vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(
      new Response(JSON.stringify({ jsonrpc: '2.0', id: 1, result: { tools: mockTools } }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      })
    );
    const result = await handleToolsList(1, 'agency-1', env) as any;
    const wpTool = result.result.tools.find((t: any) => t.name === 'wp_list_pages');
    expect(wpTool).toBeDefined();
    expect(wpTool.inputSchema.properties._site).toBeDefined();
    expect(wpTool.inputSchema.required).toContain('_site');
    expect(wpTool.inputSchema.properties._site.enum).toEqual(['client-a']);
  });
});

describe('handleToolsCall — proxy-native', () => {
  it('proxy_list_sites returns registered sites', async () => {
    const { env } = await makeEnvWithSite();
    const result = await handleToolsCall(1, { name: 'proxy_list_sites', arguments: {} }, 'agency-1', env) as any;
    const text = result.result.content[0].text;
    const sites = JSON.parse(text);
    expect(sites[0].site_id).toBe('client-a');
  });
});

describe('handleToolsCall — forwarding', () => {
  it('missing _site returns JSON-RPC error', async () => {
    const { env } = await makeEnvWithSite();
    const result = await handleToolsCall(1, { name: 'wp_list_pages', arguments: {} }, 'agency-1', env) as any;
    expect(result.error.code).toBe(-32602);
    expect(result.error.message).toMatch(/_site/);
  });

  it('unknown _site returns JSON-RPC error', async () => {
    const { env } = await makeEnvWithSite();
    const result = await handleToolsCall(1, { name: 'wp_list_pages', arguments: { _site: 'unknown' } }, 'agency-1', env) as any;
    expect(result.error.code).toBe(-32602);
    expect(result.error.message).toMatch(/unknown/i);
  });

  it('forwards call to correct upstream site', async () => {
    const { env } = await makeEnvWithSite();
    const upstream = { jsonrpc: '2.0', id: 1, result: { content: [{ type: 'text', text: '[]' }] } };
    vi.spyOn(globalThis, 'fetch').mockResolvedValueOnce(
      new Response(JSON.stringify(upstream), { status: 200, headers: { 'Content-Type': 'application/json' } })
    );
    const result = await handleToolsCall(1, { name: 'wp_list_pages', arguments: { _site: 'client-a', status: 'draft' } }, 'agency-1', env);
    expect(result).toEqual(upstream);
  });
});
