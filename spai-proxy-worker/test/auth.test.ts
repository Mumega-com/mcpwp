import { describe, it, expect } from 'vitest';
import {
  generateToken,
  hashToken,
  validateToken,
  storeToken,
  revokeToken,
  rotateToken,
} from '../src/auth';
import { hmacToken } from '../src/crypto';
import type { Env } from '../src/types';

const TEST_KEY = 'a'.repeat(64);

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

function makeEnv(kv: KVNamespace): Env {
  return { AGENCY_KV: kv, ENCRYPTION_KEY: TEST_KEY } as unknown as Env;
}

describe('auth — basic', () => {
  it('generateToken starts with mcpwp_agency_', () => {
    const token = generateToken();
    expect(token.startsWith('mcpwp_agency_')).toBe(true);
    expect(token.length).toBeGreaterThan(20);
  });

  it('hashToken (SHA-256) is deterministic', async () => {
    const h1 = await hashToken('abc');
    const h2 = await hashToken('abc');
    expect(h1).toBe(h2);
    expect(h1).toMatch(/^[0-9a-f]{64}$/);
  });

  // Legacy SHA-256 KV lookup still works (backward-compat path)
  it('validateToken returns agencyId for legacy SHA-256 stored token', async () => {
    const token = 'mcpwp_agency_testtoken';
    const hash = await hashToken(token);
    const kv = mockKV({ [`agency:token:${hash}`]: 'agency-123' });
    const env = makeEnv(kv);
    const result = await validateToken(token, env);
    expect(result).toBe('agency-123');
  });

  it('validateToken returns null for unknown token', async () => {
    const env = makeEnv(mockKV());
    const result = await validateToken('mcpwp_agency_unknown', env);
    expect(result).toBeNull();
  });
});

describe('auth — #546 HMAC token lookup', () => {
  it('validateToken finds token stored under HMAC key', async () => {
    const token = generateToken();
    const hmac = await hmacToken(token, TEST_KEY);
    const kv = mockKV({ [`agency:token:${hmac}`]: 'agency-hmac' });
    const env = makeEnv(kv);
    const result = await validateToken(token, env);
    expect(result).toBe('agency-hmac');
  });

  it('HMAC key is different from plain SHA-256 key (#546)', async () => {
    const token = generateToken();
    const hmac = await hmacToken(token, TEST_KEY);
    const sha256 = await hashToken(token);
    expect(hmac).not.toBe(sha256);
  });
});

describe('auth — #543 storeToken', () => {
  it('storeToken writes HMAC KV entry and records token_hash on account', async () => {
    const kv = mockKV();
    const env = makeEnv(kv);
    const token = generateToken();
    await storeToken(token, 'agency-abc', 'Test Agency', env);

    // Can validate the token
    const validated = await validateToken(token, env);
    expect(validated).toBe('agency-abc');

    // Account record has token_hash
    const accountRaw = await kv.get('agency:account:agency-abc');
    expect(accountRaw).not.toBeNull();
    const account = JSON.parse(accountRaw!);
    expect(account.token_hash).toBeDefined();
    expect(account.token_hash).toMatch(/^[0-9a-f]{64}$/);
    expect(account.name).toBe('Test Agency');
  });
});

describe('auth — #543 revokeToken', () => {
  it('revoked token fails validateToken', async () => {
    const kv = mockKV();
    const env = makeEnv(kv);
    const token = generateToken();
    await storeToken(token, 'agency-revoke', 'Revoke Agency', env);

    // Confirm token valid before revocation
    expect(await validateToken(token, env)).toBe('agency-revoke');

    const revoked = await revokeToken('agency-revoke', env);
    expect(revoked).toBe(true);

    // Token must now fail
    expect(await validateToken(token, env)).toBeNull();
  });

  it('revokeToken returns false when agency has no active token', async () => {
    const kv = mockKV({
      'agency:account:agency-no-token': JSON.stringify({
        id: 'agency-no-token',
        name: 'No Token',
        created_at: new Date().toISOString(),
        // token_hash absent intentionally
      }),
    });
    const env = makeEnv(kv);
    const result = await revokeToken('agency-no-token', env);
    expect(result).toBe(false);
  });

  it('revokeToken returns false for unknown agency', async () => {
    const env = makeEnv(mockKV());
    expect(await revokeToken('nonexistent-agency', env)).toBe(false);
  });
});

describe('auth — #543 rotateToken', () => {
  it('old token is invalid after rotation, new token is valid', async () => {
    const kv = mockKV();
    const env = makeEnv(kv);
    const oldToken = generateToken();
    await storeToken(oldToken, 'agency-rotate', 'Rotate Agency', env);

    const newToken = await rotateToken('agency-rotate', env);
    expect(newToken).not.toBeNull();
    expect(newToken).not.toBe(oldToken);

    // Old token must fail
    expect(await validateToken(oldToken, env)).toBeNull();

    // New token must succeed
    expect(await validateToken(newToken!, env)).toBe('agency-rotate');
  });

  it('rotateToken returns null for unknown agency', async () => {
    const env = makeEnv(mockKV());
    const result = await rotateToken('nonexistent', env);
    expect(result).toBeNull();
  });
});
