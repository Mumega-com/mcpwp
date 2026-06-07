import type { Env } from './types';

export function generateToken(): string {
  const bytes = crypto.getRandomValues(new Uint8Array(32));
  const b64 = btoa(String.fromCharCode(...bytes))
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=/g, '');
  return `mcpwp_agency_${b64}`;
}

export async function hashToken(token: string): Promise<string> {
  const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(token));
  return Array.from(new Uint8Array(buf))
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');
}

export async function validateToken(token: string, env: Env): Promise<string | null> {
  const hash = await hashToken(token);
  return env.AGENCY_KV.get(`agency:token:${hash}`);
}
