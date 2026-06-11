export interface Env {
  AGENCY_KV: KVNamespace;
  ENCRYPTION_KEY: string; // 64-char hex = 32-byte AES-GCM key
  ADMIN_SECRET: string;
  RATE_LIMITER_MCP: RateLimit;   // 120 req/min per IP on /mcp
  RATE_LIMITER_ADMIN: RateLimit; // 10 req/min per IP on /api/accounts
}

export interface SiteEntry {
  site_id: string;       // UUID v4, e.g. "550e8400-e29b-41d4-a716-446655440000" (#544)
  url: string;           // e.g. "https://client-a.com"
  api_key_enc: string;   // AES-GCM encrypted under per-agency HKDF-derived key, base64 (#546)
  label: string;         // human-readable name (slug/hostname — display field only, #544)
  added_at: string;      // ISO timestamp
}

export interface AgencyAccount {
  id: string;
  name: string;
  created_at: string;
  token_hash?: string;   // current active HMAC token hash — for revocation/rotation (#543)
}
