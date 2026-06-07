import { describe, it, expect } from 'vitest';
import { generateToken, hashToken, validateToken } from '../src/auth';
import type { Env } from '../src/types';

function mockKV(data: Record<string, string> = {}): KVNamespace {
  const store = { ...data };
  return {
    get: async (key: string) => store[key] ?? null,
    put: async (key: string, value: string) => { store[key] = value; },
    delete: async (key: string) => { delete store[key]; },
    list: async () => ({ keys: [], list_complete: true, cursor: '' }),
    getWithMetadata: async (key: string) => ({ value: store[key] ?? null, metadata: null }),
  } as unknown as KVNamespace;
}

describe('auth', () => {
  it('generateToken starts with mcpwp_agency_', () => {
    const token = generateToken();
    expect(token.startsWith('mcpwp_agency_')).toBe(true);
    expect(token.length).toBeGreaterThan(20);
  });

  it('hashToken is deterministic', async () => {
    const h1 = await hashToken('abc');
    const h2 = await hashToken('abc');
    expect(h1).toBe(h2);
    expect(h1).toMatch(/^[0-9a-f]{64}$/);
  });

  it('validateToken returns agencyId for known token', async () => {
    const token = 'mcpwp_agency_testtoken';
    const hash = await hashToken(token);
    const kv = mockKV({ [`agency:token:${hash}`]: 'agency-123' });
    const env = { AGENCY_KV: kv } as unknown as Env;
    const result = await validateToken(token, env);
    expect(result).toBe('agency-123');
  });

  it('validateToken returns null for unknown token', async () => {
    const kv = mockKV({});
    const env = { AGENCY_KV: kv } as unknown as Env;
    const result = await validateToken('mcpwp_agency_unknown', env);
    expect(result).toBeNull();
  });
});
