import { describe, it, expect } from 'vitest';
import { getSites, addSite, removeSite } from '../src/registry';
import type { Env, SiteEntry } from '../src/types';

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

function makeEnv(kv: KVNamespace): Env {
  return { AGENCY_KV: kv, ENCRYPTION_KEY: 'a'.repeat(64) } as unknown as Env;
}

const siteA: SiteEntry = {
  site_id: 'client-a',
  url: 'https://client-a.com',
  api_key_enc: 'encrypted_key_a',
  label: 'Client A',
  added_at: '2026-06-07T00:00:00Z',
};

describe('registry', () => {
  it('getSites returns empty array when no sites registered', async () => {
    const env = makeEnv(mockKV());
    expect(await getSites('agency-1', env)).toEqual([]);
  });

  it('addSite stores the site and getSites returns it', async () => {
    const env = makeEnv(mockKV());
    await addSite('agency-1', siteA, env);
    const sites = await getSites('agency-1', env);
    expect(sites).toHaveLength(1);
    expect(sites[0].site_id).toBe('client-a');
  });

  it('addSite with same site_id updates in-place', async () => {
    const env = makeEnv(mockKV());
    await addSite('agency-1', siteA, env);
    await addSite('agency-1', { ...siteA, label: 'Updated A' }, env);
    const sites = await getSites('agency-1', env);
    expect(sites).toHaveLength(1);
    expect(sites[0].label).toBe('Updated A');
  });

  it('removeSite removes the site and returns true', async () => {
    const env = makeEnv(mockKV());
    await addSite('agency-1', siteA, env);
    const removed = await removeSite('agency-1', 'client-a', env);
    expect(removed).toBe(true);
    expect(await getSites('agency-1', env)).toHaveLength(0);
  });

  it('removeSite returns false when site not found', async () => {
    const env = makeEnv(mockKV());
    const removed = await removeSite('agency-1', 'nonexistent', env);
    expect(removed).toBe(false);
  });
});
