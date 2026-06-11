// #543: token revocation + rotation
// Token KV layout:
//   agency:token:<hmacHex>  → agencyId          (forward index: token → agency)
//   agency:account:<id>     → AgencyAccount JSON (account stores current token_hash for revocation)
//
// Legacy tokens (pre-HKDF) stored under plain SHA-256 hash are still looked up
// as a fallback so existing sessions survive the upgrade.

import type { Env, AgencyAccount } from './types';
import { hmacToken } from './crypto';

export function generateToken(): string {
  const bytes = crypto.getRandomValues(new Uint8Array(32));
  const b64 = btoa(String.fromCharCode(...bytes))
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=/g, '');
  return `mcpwp_agency_${b64}`;
}

/**
 * Plain SHA-256 hash of a token (legacy / backward-compat lookup only).
 * New tokens are stored under the HMAC key; this exists for legacy fallback.
 */
export async function hashToken(token: string): Promise<string> {
  const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(token));
  return Array.from(new Uint8Array(buf))
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');
}

/**
 * Validate a token.
 * Checks the HMAC-keyed KV entry first (new tokens post-#546), then falls back
 * to the plain SHA-256 entry (legacy tokens).
 * Returns the agencyId string on success, null on failure.
 */
export async function validateToken(token: string, env: Env): Promise<string | null> {
  // Primary lookup: HMAC-keyed entry (tokens issued after #546/#543)
  const hmac = await hmacToken(token, env.ENCRYPTION_KEY);
  const fromHmac = await env.AGENCY_KV.get(`agency:token:${hmac}`);
  if (fromHmac) return fromHmac;

  // Legacy fallback: plain SHA-256 (tokens created before this upgrade)
  const sha256 = await hashToken(token);
  return env.AGENCY_KV.get(`agency:token:${sha256}`);
}

/**
 * Store a new token → agencyId mapping using the HMAC key.
 * Also records the current token_hash on the account record so revocation
 * can find and delete the KV entry without knowing the raw token.
 */
export async function storeToken(
  token: string,
  agencyId: string,
  accountName: string,
  env: Env
): Promise<void> {
  const hmac = await hmacToken(token, env.ENCRYPTION_KEY);
  await env.AGENCY_KV.put(`agency:token:${hmac}`, agencyId);

  const account: AgencyAccount = {
    id: agencyId,
    name: accountName,
    created_at: new Date().toISOString(),
    token_hash: hmac,
  };
  await env.AGENCY_KV.put(`agency:account:${agencyId}`, JSON.stringify(account));
}

/**
 * Revoke the current token for an agency.
 * Deletes the token→agency KV entry. Subsequent validateToken calls return null.
 * Returns true if a token was found and deleted, false if none was set.
 */
export async function revokeToken(agencyId: string, env: Env): Promise<boolean> {
  const accountRaw = await env.AGENCY_KV.get(`agency:account:${agencyId}`);
  if (!accountRaw) return false;
  const account = JSON.parse(accountRaw) as AgencyAccount;
  if (!account.token_hash) return false;

  await env.AGENCY_KV.delete(`agency:token:${account.token_hash}`);
  const updated: AgencyAccount = { ...account, token_hash: undefined };
  await env.AGENCY_KV.put(`agency:account:${agencyId}`, JSON.stringify(updated));
  return true;
}

/**
 * Rotate: issue a new token, invalidate the old one atomically (KV is eventually
 * consistent; old token may work for a brief window — acceptable trade-off without DO).
 * Returns the new token string (never stored in plaintext).
 */
export async function rotateToken(agencyId: string, env: Env): Promise<string | null> {
  const accountRaw = await env.AGENCY_KV.get(`agency:account:${agencyId}`);
  if (!accountRaw) return null;
  const account = JSON.parse(accountRaw) as AgencyAccount;

  // Delete old token entry if present
  if (account.token_hash) {
    await env.AGENCY_KV.delete(`agency:token:${account.token_hash}`);
  }

  // Issue new token
  const newToken = generateToken();
  const hmac = await hmacToken(newToken, env.ENCRYPTION_KEY);
  await env.AGENCY_KV.put(`agency:token:${hmac}`, agencyId);

  const updated: AgencyAccount = { ...account, token_hash: hmac };
  await env.AGENCY_KV.put(`agency:account:${agencyId}`, JSON.stringify(updated));

  return newToken;
}
