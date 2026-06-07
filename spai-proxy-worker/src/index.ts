import { Hono } from 'hono';
import type { Env } from './types';

const app = new Hono<{ Bindings: Env }>();

app.get('/', (c) => c.json({ service: 'mcpwp-agency-proxy', version: '1.0.0' }));

export default { fetch: app.fetch };
