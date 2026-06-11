import { describe, it, expect } from 'vitest';
import {
  encrypt,
  decrypt,
  encryptForAgency,
  decryptForAgency,
  hmacToken,
  deriveAgencyKey,
} from '../src/crypto';

const TEST_KEY = 'a'.repeat(64); // 32 bytes as hex

describe('crypto — legacy global encrypt/decrypt', () => {
  it('encrypt then decrypt returns original', async () => {
    const original = 'spai_abc123secretkey';
    const ciphertext = await encrypt(original, TEST_KEY);
    expect(ciphertext).not.toBe(original);
    const decrypted = await decrypt(ciphertext, TEST_KEY);
    expect(decrypted).toBe(original);
  });

  it('each encrypt call produces different ciphertext (random IV)', async () => {
    const c1 = await encrypt('hello', TEST_KEY);
    const c2 = await encrypt('hello', TEST_KEY);
    expect(c1).not.toBe(c2);
  });

  it('decrypt with wrong key throws', async () => {
    const c = await encrypt('hello', TEST_KEY);
    const wrongKey = 'b'.repeat(64);
    await expect(decrypt(c, wrongKey)).rejects.toThrow();
  });
});

describe('crypto — #546 per-agency HKDF', () => {
  it('per-agency keys differ for different agency IDs (opaque CryptoKey — verified via cross-decrypt)', async () => {
    // Encrypt under agency-1's key, fail to decrypt under agency-2's key
    const ct = await encryptForAgency('secret', TEST_KEY, 'agency-1');
    await expect(decryptForAgency(ct, TEST_KEY, 'agency-2')).rejects.toThrow();
    // Confirm derived keys exist (not null/undefined)
    const key1 = await deriveAgencyKey(TEST_KEY, 'agency-1');
    const key2 = await deriveAgencyKey(TEST_KEY, 'agency-2');
    expect(key1).toBeDefined();
    expect(key2).toBeDefined();
  });

  it('encryptForAgency + decryptForAgency round-trips correctly', async () => {
    const plaintext = 'spai_real_api_key_xyz';
    const ct = await encryptForAgency(plaintext, TEST_KEY, 'agency-1');
    expect(ct).not.toBe(plaintext);
    const { plaintext: decrypted, needsReEncrypt } = await decryptForAgency(ct, TEST_KEY, 'agency-1');
    expect(decrypted).toBe(plaintext);
    expect(needsReEncrypt).toBe(false);
  });

  it('decryptForAgency falls back to legacy global key and sets needsReEncrypt (#546)', async () => {
    // Simulate a legacy entry encrypted with the global key (pre-HKDF)
    const legacyPlaintext = 'spai_legacy_key';
    const legacyCiphertext = await encrypt(legacyPlaintext, TEST_KEY);

    const { plaintext, needsReEncrypt } = await decryptForAgency(
      legacyCiphertext,
      TEST_KEY,
      'agency-999'
    );
    expect(plaintext).toBe(legacyPlaintext);
    expect(needsReEncrypt).toBe(true); // caller must re-encrypt under derived key
  });

  it('cross-agency decrypt fails — tenant isolation (#546)', async () => {
    const ct = await encryptForAgency('tenant-secret', TEST_KEY, 'agency-A');
    // agency-B must not decrypt agency-A's ciphertext
    // Both derived-key AND legacy-key attempts should fail
    await expect(decryptForAgency(ct, TEST_KEY, 'agency-B')).rejects.toThrow();
  });

  it('random IV — same plaintext produces different ciphertexts (#546)', async () => {
    const ct1 = await encryptForAgency('hello', TEST_KEY, 'agency-1');
    const ct2 = await encryptForAgency('hello', TEST_KEY, 'agency-1');
    expect(ct1).not.toBe(ct2);
  });
});

describe('crypto — #546 hmacToken', () => {
  it('hmacToken is deterministic', async () => {
    const h1 = await hmacToken('some-token', TEST_KEY);
    const h2 = await hmacToken('some-token', TEST_KEY);
    expect(h1).toBe(h2);
  });

  it('hmacToken produces 64-char hex string (HMAC-SHA256)', async () => {
    const h = await hmacToken('test', TEST_KEY);
    expect(h).toMatch(/^[0-9a-f]{64}$/);
  });

  it('hmacToken differs for different tokens', async () => {
    const h1 = await hmacToken('token-a', TEST_KEY);
    const h2 = await hmacToken('token-b', TEST_KEY);
    expect(h1).not.toBe(h2);
  });

  it('hmacToken differs for different master keys', async () => {
    const h1 = await hmacToken('same-token', TEST_KEY);
    const h2 = await hmacToken('same-token', 'b'.repeat(64));
    expect(h1).not.toBe(h2);
  });
});
