import { describe, it, expect } from 'vitest';
import { encrypt, decrypt } from '../src/crypto';

const TEST_KEY = 'a'.repeat(64); // 32 bytes as hex

describe('crypto', () => {
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
