import { Hono } from 'hono';
import type { MiddlewareHandler } from 'hono';
import type { Env } from './types';
import { handleInitialize, handleToolsList, handleToolsCall } from './mcp';
import { validateToken, generateToken, hashToken } from './auth';
import { getSites, addSite, removeSite } from './registry';
import { encrypt } from './crypto';

type Variables = { agencyId: string };
type AppMiddleware = MiddlewareHandler<{ Bindings: Env; Variables: Variables }>;
const app = new Hono<{ Bindings: Env; Variables: Variables }>();

// Health check
app.get('/', (c) => c.json({ service: 'mcpwp-agency-proxy', version: '1.0.0' }));

// Auth middleware factory
const requireAgencyToken: AppMiddleware = async (c, next) => {
  const auth = c.req.header('Authorization') ?? '';
  const token = auth.startsWith('Bearer ') ? auth.slice(7) : '';
  if (!token) {
    return c.json(
      { jsonrpc: '2.0', id: null, error: { code: -32000, message: 'Missing Authorization: Bearer <agency_token>' } },
      401
    );
  }
  const agencyId = await validateToken(token, c.env);
  if (!agencyId) {
    return c.json(
      { jsonrpc: '2.0', id: null, error: { code: -32000, message: 'Invalid agency token' } },
      401
    );
  }
  c.set('agencyId', agencyId);
  await next();
}

const requireApiToken: AppMiddleware = async (c, next) => {
  const auth = c.req.header('Authorization') ?? '';
  const token = auth.startsWith('Bearer ') ? auth.slice(7) : '';
  if (!token) return c.json({ error: 'Missing Authorization: Bearer <agency_token>' }, 401);
  const agencyId = await validateToken(token, c.env);
  if (!agencyId) return c.json({ error: 'Invalid token' }, 401);
  c.set('agencyId', agencyId);
  await next();
}

// MCP endpoint
app.post('/mcp', requireAgencyToken, async (c) => {
  let body: { method?: string; id?: unknown; params?: unknown };
  try {
    body = await c.req.json();
  } catch {
    return c.json({ jsonrpc: '2.0', id: null, error: { code: -32700, message: 'Parse error: invalid JSON' } }, 400);
  }

  const { method, id, params } = body;
  const agencyId = c.get('agencyId');

  if (method === 'initialize') return c.json(handleInitialize(id));
  if (method === 'notifications/initialized') return c.json({ jsonrpc: '2.0', id, result: {} });
  if (method === 'ping') return c.json({ jsonrpc: '2.0', id, result: {} });
  if (method === 'tools/list') return c.json(await handleToolsList(id, agencyId, c.env));
  if (method === 'tools/call') {
    if (!params || typeof params !== 'object') {
      return c.json({ jsonrpc: '2.0', id, error: { code: -32602, message: 'params required for tools/call' } }, 400);
    }
    return c.json(await handleToolsCall(id, params as { name: string; arguments: Record<string, unknown> }, agencyId, c.env));
  }

  return c.json({ jsonrpc: '2.0', id, error: { code: -32601, message: `Unknown method: ${method}` } });
});

// Account creation — returns one-time agency token
app.post('/api/accounts', async (c) => {
  const adminSecret = c.req.header('X-Admin-Secret');
  if (!adminSecret || adminSecret !== c.env.ADMIN_SECRET) {
    return c.json({ error: 'Forbidden: X-Admin-Secret required' }, 403);
  }

  let name = 'My Agency';
  try {
    const body = await c.req.json();
    if (typeof body.name === 'string') name = body.name;
  } catch { /* name stays default */ }

  const token = generateToken();
  const hash = await hashToken(token);
  const agencyId = crypto.randomUUID();

  await c.env.AGENCY_KV.put(`agency:token:${hash}`, agencyId);
  await c.env.AGENCY_KV.put(
    `agency:account:${agencyId}`,
    JSON.stringify({ id: agencyId, name, created_at: new Date().toISOString() })
  );

  return c.json(
    { agency_id: agencyId, token, warning: 'Store this token securely — it cannot be retrieved again.' },
    201
  );
});

// List sites
app.get('/api/sites', requireApiToken, async (c) => {
  const sites = await getSites(c.get('agencyId'), c.env);
  return c.json(sites.map((s) => ({ site_id: s.site_id, url: s.url, label: s.label, added_at: s.added_at })));
});

// Add site
app.post('/api/sites', requireApiToken, async (c) => {
  let body: { url?: string; api_key?: string; label?: string; site_id?: string };
  try {
    body = await c.req.json();
  } catch {
    return c.json({ error: 'Invalid JSON body' }, 400);
  }

  const { url, api_key, label, site_id: providedId } = body;
  if (!url || !api_key) return c.json({ error: 'url and api_key are required' }, 400);

  let parsedUrl: URL;
  try {
    parsedUrl = new URL(url);
  } catch {
    return c.json({ error: 'url is not a valid URL' }, 400);
  }

  if (parsedUrl.protocol !== 'https:') {
    return c.json({ error: 'url must use HTTPS' }, 400);
  }

  const hostname = parsedUrl.hostname.replace(/\./g, '-');
  const site_id = (providedId ?? (label ? label.toLowerCase().replace(/[^a-z0-9-]/g, '-') : hostname)).slice(0, 64);
  const api_key_enc = await encrypt(api_key, c.env.ENCRYPTION_KEY);

  await addSite(
    c.get('agencyId'),
    { site_id, url, api_key_enc, label: label ?? hostname, added_at: new Date().toISOString() },
    c.env
  );

  return c.json({ site_id, status: 'registered' }, 201);
});

// Remove site
app.delete('/api/sites/:siteId', requireApiToken, async (c) => {
  const removed = await removeSite(c.get('agencyId'), c.req.param('siteId'), c.env);
  return removed ? c.json({ status: 'removed' }) : c.json({ error: 'Site not found' }, 404);
});

export default { fetch: app.fetch };
