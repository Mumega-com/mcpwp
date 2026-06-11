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

// #544: site_id is now a UUID
const siteA: SiteEntry = {
  site_id: '00000000-0000-0000-0000-000000000001',
  url: 'https://client-a.com',
  api_key_enc: 'encrypted_key_a',
  label: 'Client A',
  added_at: '2026-06-07T00:00:00Z',
};

const siteB: SiteEntry = {
  site_id: '00000000-0000-0000-0000-000000000002',
  url: 'https://client-b.com',
  api_key_enc: 'encrypted_key_b',
  label: 'Client B',
  added_at: '2026-06-07T00:00:00Z',
};

describe('registry', () => {
  it('getSites returns empty array when no sites registered', async () => {
    const env = makeEnv(mockKV());
    expect(await getSites('agency-1', env)).toEqual([]);
  });

  it('addSite stores the site and getSites returns it', async () => {
    const env = makeEnv(mockKV());
    const result = await addSite('agency-1', siteA, env);
    expect(result.ok).toBe(true);
    const sites = await getSites('agency-1', env);
    expect(sites).toHaveLength(1);
    expect(sites[0].site_id).toBe(siteA.site_id);
  });

  // #544: addSite with same site_id must NOT silently overwrite without update:true
  it('addSite with same site_id returns conflict without update:true (#544)', async () => {
    const env = makeEnv(mockKV());
    await addSite('agency-1', siteA, env);
    const result = await addSite('agency-1', { ...siteA, label: 'Conflict A' }, env);
    expect(result.ok).toBe(false);
    if (!result.ok) {
      expect(result.conflict).toBe(true);
    }
    // Label must not have changed — no silent overwrite
    const sites = await getSites('agency-1', env);
    expect(sites[0].label).toBe('Client A');
  });

  it('addSite with same site_id and update:true updates in-place', async () => {
    const env = makeEnv(mockKV());
    await addSite('agency-1', siteA, env);
    const result = await addSite('agency-1', { ...siteA, label: 'Updated A' }, env, { update: true });
    expect(result.ok).toBe(true);
    const sites = await getSites('agency-1', env);
    expect(sites).toHaveLength(1);
    expect(sites[0].label).toBe('Updated A');
  });

  it('removeSite removes the site and returns true', async () => {
    const env = makeEnv(mockKV());
    await addSite('agency-1', siteA, env);
    const removed = await removeSite('agency-1', siteA.site_id, env);
    expect(removed).toBe(true);
    expect(await getSites('agency-1', env)).toHaveLength(0);
  });

  it('removeSite returns false when site not found', async () => {
    const env = makeEnv(mockKV());
    const removed = await removeSite('agency-1', 'nonexistent-uuid', env);
    expect(removed).toBe(false);
  });

  // #544: two distinct sites never collide
  it('two distinct UUID site_ids coexist without collision (#544)', async () => {
    const env = makeEnv(mockKV());
    await addSite('agency-1', siteA, env);
    const result = await addSite('agency-1', siteB, env);
    expect(result.ok).toBe(true);
    const sites = await getSites('agency-1', env);
    expect(sites).toHaveLength(2);
    const ids = sites.map((s) => s.site_id);
    expect(new Set(ids).size).toBe(2);
  });

  // #544: two clients with the same label/URL but different UUIDs do not overwrite each other
  it('same-domain sites with different UUIDs never clobber (#544)', async () => {
    const env = makeEnv(mockKV());
    const uuid1 = crypto.randomUUID();
    const uuid2 = crypto.randomUUID();
    expect(uuid1).not.toBe(uuid2);

    const entry1: SiteEntry = {
      site_id: uuid1,
      url: 'https://same-domain.com',
      api_key_enc: 'enc_1',
      label: 'same-domain',
      added_at: new Date().toISOString(),
    };
    const entry2: SiteEntry = {
      site_id: uuid2,
      url: 'https://same-domain.com',
      api_key_enc: 'enc_2',
      label: 'same-domain',
      added_at: new Date().toISOString(),
    };

    await addSite('agency-1', entry1, env);
    const result = await addSite('agency-1', entry2, env);
    // No collision — different UUIDs
    expect(result.ok).toBe(true);
    const sites = await getSites('agency-1', env);
    expect(sites).toHaveLength(2);
  });
});
