# Agency Gateway — one AI connection for your whole WordPress fleet

The agency gateway lets an operator connect **one** MCP client (Claude, Cursor, etc.) and manage **all** their MCPWP-running WordPress sites through it — instead of adding a separate connector per site. One token, one connection, a `_site` selector to switch. Tool count stays flat (~252) no matter how many sites you add.

- **Live endpoint:** `https://proxy.mcpwp.net/mcp`
- **Source:** `spai-proxy-worker/` (Cloudflare Worker, Hono, TypeScript) — deployed as `mcpwp-agency-proxy`.
- **Status:** hardened + adversarial-reviewed for the operator's own sites. Onboarding *third-party* customers is gated on the OAuth-custody upgrade (issue #549) so the gateway holds revocable tokens, not master keys.

## Why

Adding 3 sites as 3 separate MCP connectors loads ~750 tools into the client (250 × 3) — context bloat and tool confusion. The gateway collapses that: one connector, ~252 tools, and you pick the target site per call.

```
Claude ──one agency token──► proxy.mcpwp.net ──each site's own key──► site-a.com
                              (the Worker)     ──────────────────────► site-b.com
                                               ──────────────────────► site-c.com
```

## How it works

1. Your MCP client sends `POST /mcp` with `Authorization: Bearer <agency token>`.
2. The Worker validates the token (KV lookup), then checks the requested `_site` is one of **your** registered sites (per-agency isolation — your token can never reach another agency's site).
3. It pulls that site's API key from KV, **decrypts** it (AES-GCM, per-agency HKDF-derived key), and forwards the request to `<site>/wp-json/mcpwp/v1/mcp` with `X-API-Key`.
4. WordPress responds; the Worker returns it verbatim.

`proxy_list_sites` (a gateway-native tool) lists your registered sites; every other tool takes a `_site` argument.

## Setup

### 1. Each site
Run the latest MCPWP (new `mcpwp/v1` slug). Generate a **least-privilege** API key per site (WP Admin → **MCPWP → Setup → Generate API Key**, role `editor`/`designer` — **not admin**). The gateway stores it encrypted; a scoped key that leaks is revocable on that site and exposes nothing else.

### 2. Create your agency account (admin)
```bash
curl -X POST https://proxy.mcpwp.net/api/accounts \
  -H "X-Admin-Secret: <ADMIN_SECRET>" \
  -d '{"name":"Acme Agency"}'
# → { "agency_id": "...", "token": "mcpwp_agency_..." }   ← store the token
```

### 3. Register each site (agency token)
```bash
curl -X POST https://proxy.mcpwp.net/api/sites \
  -H "Authorization: Bearer <agency token>" \
  -d '{"url":"https://site-a.com","api_key":"mcpwp_...","label":"Site A"}'
# → { "site_id": "<uuid>" }
```

### 4. Connect your AI client (one connector)
```json
{ "mcpServers": { "mcpwp-agency": {
  "url": "https://proxy.mcpwp.net/mcp",
  "headers": { "Authorization": "Bearer <agency token>" } } } }
```
Then: *"list my sites"* → `proxy_list_sites`; *"use Site A, list pages"* → routed via `_site`.

## API reference

**Admin (`X-Admin-Secret` header):**
| Method | Path | Purpose |
|--------|------|---------|
| POST | `/api/accounts` | create agency account → returns token |
| DELETE | `/api/accounts/:id/token` | revoke an agency token |
| POST | `/api/accounts/:id/token/rotate` | rotate (issue new, kill old) |

**Agency (`Authorization: Bearer <agency token>`):**
| Method | Path | Purpose |
|--------|------|---------|
| POST | `/mcp` | the MCP endpoint (JSON-RPC; `_site` selects the target) |
| GET | `/api/sites` | list your registered sites |
| POST | `/api/sites` | register a site `{url, api_key, label}` |
| DELETE | `/api/sites/:siteId` | remove a site |
| GET | `/api/sites/health` | health-check all your sites |

`/dashboard` — admin-secret-gated web UI.

## Security model

- **Per-agency isolation** — every site lookup is scoped to the authenticated agency; there is no global site lookup. A token reaches only its own sites.
- **Encrypted keys at rest** — AES-GCM with a random IV, under a per-agency key derived via HKDF from the worker's `ENCRYPTION_KEY`. Decrypted keys are never logged, returned, or shown in the dashboard.
- **Token revocation / rotation** — leaked agency tokens can be killed or rotated; legacy tokens migrate to the revocable form on first use.
- **SSRF guard** — registered site URLs are validated (no RFC1918/loopback/link-local/IP-literal/numeric-form hosts), re-checked on every forward, and upstream redirects are refused.
- **Least privilege** — store scoped (non-admin) site keys. The gateway never needs admin on your sites.
- **Optional, never a single point of failure** — direct-to-site (key in the client config) always works; the gateway is a convenience layer.

For onboarding **other people's** sites, the roadmap (#549) replaces stored master keys with short-lived, revocable OAuth tokens the customer can revoke from their own WP admin. Until then, the gateway is intended for an operator's **own** fleet.

## Custom domain

The worker serves on its `*.workers.dev` URL and on a Cloudflare **Custom Domain** (`proxy.mcpwp.net`) which auto-provisions DNS + TLS. Prefer a Custom Domain over a Route + manual DNS record.
