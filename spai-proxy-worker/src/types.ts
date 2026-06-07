export interface Env {
  AGENCY_KV: KVNamespace;
  ENCRYPTION_KEY: string; // 64-char hex = 32-byte AES-GCM key
  ADMIN_SECRET: string;
}

export interface SiteEntry {
  site_id: string;       // slug, e.g. "client-a"
  url: string;           // e.g. "https://client-a.com"
  api_key_enc: string;   // AES-GCM encrypted, base64
  label: string;         // human-readable name
  added_at: string;      // ISO timestamp
}

export interface AgencyAccount {
  id: string;
  name: string;
  created_at: string;
}
