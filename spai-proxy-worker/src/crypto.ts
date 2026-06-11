// #546: per-agency HKDF-derived keys + token HMAC (defense-in-depth)
// Key hierarchy:
//   ENCRYPTION_KEY (master, 32 bytes hex)
//     └─ HKDF(salt=agencyId, info="mcpwp-agency-site-key") → per-agency AES-GCM key
// Legacy fallback: if decrypt under derived key fails, retry with global key and re-encrypt.

const HKDF_INFO_SITE_KEY = new TextEncoder().encode('mcpwp-agency-site-key-v1');
const HKDF_INFO_TOKEN_HMAC = new TextEncoder().encode('mcpwp-token-hmac-v1');

function hexToBytes(hex: string): Uint8Array {
  const bytes = new Uint8Array(hex.length / 2);
  for (let i = 0; i < bytes.length; i++) {
    bytes[i] = parseInt(hex.slice(i * 2, i * 2 + 2), 16);
  }
  return bytes;
}

async function importHkdfKey(masterHex: string): Promise<CryptoKey> {
  return crypto.subtle.importKey(
    'raw',
    hexToBytes(masterHex),
    { name: 'HKDF' },
    false,
    ['deriveKey', 'deriveBits']
  );
}

/**
 * Derive a per-agency AES-GCM-256 key from the master ENCRYPTION_KEY via HKDF.
 * Salt = UTF-8 bytes of agencyId; info = fixed label.
 */
export async function deriveAgencyKey(masterHex: string, agencyId: string): Promise<CryptoKey> {
  const hkdfKey = await importHkdfKey(masterHex);
  return crypto.subtle.deriveKey(
    {
      name: 'HKDF',
      hash: 'SHA-256',
      salt: new TextEncoder().encode(agencyId),
      info: HKDF_INFO_SITE_KEY,
    },
    hkdfKey,
    { name: 'AES-GCM', length: 256 },
    false,
    ['encrypt', 'decrypt']
  );
}

/**
 * Derive an HMAC-SHA-256 key for token hashing from the master ENCRYPTION_KEY via HKDF.
 * Keyed HMAC so the KV key is not guessable by external parties.
 */
export async function deriveTokenHmacKey(masterHex: string): Promise<CryptoKey> {
  const hkdfKey = await importHkdfKey(masterHex);
  return crypto.subtle.deriveKey(
    {
      name: 'HKDF',
      hash: 'SHA-256',
      salt: new TextEncoder().encode('mcpwp-token-hmac-salt-v1'),
      info: HKDF_INFO_TOKEN_HMAC,
    },
    hkdfKey,
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign', 'verify']
  );
}

// ─── AES-GCM helpers ───────────────────────────────────────────────────────

async function aesGcmEncrypt(plaintext: string, key: CryptoKey): Promise<string> {
  const iv = crypto.getRandomValues(new Uint8Array(12));
  const encoded = new TextEncoder().encode(plaintext);
  const enc = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, key, encoded);
  const combined = new Uint8Array(iv.byteLength + enc.byteLength);
  combined.set(iv, 0);
  combined.set(new Uint8Array(enc), 12);
  return btoa(Array.from(combined, (b) => String.fromCharCode(b)).join(''));
}

async function aesGcmDecrypt(ciphertext: string, key: CryptoKey): Promise<string> {
  const combined = Uint8Array.from(atob(ciphertext), (c) => c.charCodeAt(0));
  const iv = combined.slice(0, 12);
  const data = combined.slice(12);
  const dec = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, key, data);
  return new TextDecoder().decode(dec);
}

// ─── Legacy global-key encrypt/decrypt (kept for migration fallback) ────────

async function importLegacyKey(keyHex: string, doEncrypt: boolean): Promise<CryptoKey> {
  const usages: string[] = doEncrypt ? ['encrypt'] : ['decrypt'];
  return crypto.subtle.importKey('raw', hexToBytes(keyHex), { name: 'AES-GCM' }, false, usages);
}

/**
 * Encrypt plaintext under the global key (legacy path, no agency context).
 * Used only by the old single-agency flow and tests that predated HKDF.
 */
export async function encrypt(plaintext: string, keyHex: string): Promise<string> {
  const key = await importLegacyKey(keyHex, true);
  return aesGcmEncrypt(plaintext, key);
}

/**
 * Decrypt ciphertext encrypted under the global key (legacy path).
 */
export async function decrypt(ciphertext: string, keyHex: string): Promise<string> {
  const key = await importLegacyKey(keyHex, false);
  return aesGcmDecrypt(ciphertext, key);
}

// ─── Per-agency encrypt/decrypt with legacy fallback (#546) ─────────────────

/**
 * Encrypt a site API key under the per-agency HKDF-derived key.
 */
export async function encryptForAgency(
  plaintext: string,
  masterHex: string,
  agencyId: string
): Promise<string> {
  const key = await deriveAgencyKey(masterHex, agencyId);
  return aesGcmEncrypt(plaintext, key);
}

/**
 * Decrypt a site API key.
 * Tries per-agency HKDF-derived key first.
 * Falls back to the legacy global key if that fails (migration-safe).
 * Returns the plaintext AND a flag indicating whether re-encryption is needed.
 */
export async function decryptForAgency(
  ciphertext: string,
  masterHex: string,
  agencyId: string
): Promise<{ plaintext: string; needsReEncrypt: boolean }> {
  try {
    const key = await deriveAgencyKey(masterHex, agencyId);
    const plaintext = await aesGcmDecrypt(ciphertext, key);
    return { plaintext, needsReEncrypt: false };
  } catch {
    // Legacy fallback — entry was encrypted with the global key
    const plaintext = await decrypt(ciphertext, masterHex);
    return { plaintext, needsReEncrypt: true };
  }
}

// ─── Token HMAC (#546 / #543) ────────────────────────────────────────────────

/**
 * Compute HMAC-SHA-256 of the token using the HKDF-derived token key.
 * Returns a hex string used as the KV lookup key.
 * This replaces the plain SHA-256 hash to prevent preimage lookup by KV readers.
 *
 * Backward-compat note: existing entries are stored under plain SHA-256 hash
 * (`agency:token:<sha256>`). After rotation (#543) issues new tokens,
 * those are stored under the HMAC key. Both lookup paths are tried in validateToken.
 */
export async function hmacToken(token: string, masterHex: string): Promise<string> {
  const key = await deriveTokenHmacKey(masterHex);
  const sig = await crypto.subtle.sign('HMAC', key, new TextEncoder().encode(token));
  return Array.from(new Uint8Array(sig))
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');
}
