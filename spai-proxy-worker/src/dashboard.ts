export const DASHBOARD_HTML = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>MCPWP Agency Dashboard</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; color: #222; }
  header { background: #1e1e2e; color: #fff; padding: 1rem 1.5rem; display: flex; align-items: center; gap: 1rem; }
  header h1 { font-size: 1.1rem; font-weight: 600; }
  header .badge { background: #7c3aed; color: #fff; font-size: 0.7rem; padding: 2px 8px; border-radius: 99px; font-weight: 600; }
  .container { max-width: 960px; margin: 0 auto; padding: 1.5rem; }
  .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 1.5rem; overflow: hidden; }
  .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; }
  .card-header h2 { font-size: 0.95rem; font-weight: 600; color: #374151; }
  .card-body { padding: 1.25rem; }
  .row { display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap; }
  label { display: block; font-size: 0.8rem; font-weight: 500; color: #6b7280; margin-bottom: 4px; }
  input[type=text], input[type=password], input[type=url] { border: 1px solid #d1d5db; border-radius: 6px; padding: 6px 10px; font-size: 0.875rem; min-width: 200px; width: 100%; }
  input:focus { outline: 2px solid #7c3aed; border-color: #7c3aed; }
  .field { flex: 1; min-width: 160px; }
  button { border: none; border-radius: 6px; padding: 7px 14px; font-size: 0.875rem; font-weight: 500; cursor: pointer; }
  .btn-primary { background: #7c3aed; color: #fff; }
  .btn-primary:hover { background: #6d28d9; }
  .btn-danger { background: #dc2626; color: #fff; font-size: 0.75rem; padding: 4px 10px; }
  .btn-danger:hover { background: #b91c1c; }
  .btn-sm { padding: 4px 10px; font-size: 0.75rem; }
  table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
  th { text-align: left; font-weight: 500; color: #6b7280; font-size: 0.75rem; padding: 8px 10px; border-bottom: 1px solid #e5e7eb; background: #f9fafb; }
  td { padding: 10px; border-bottom: 1px solid #f3f4f6; }
  tr:last-child td { border-bottom: none; }
  .badge-green { background: #d1fae5; color: #065f46; border-radius: 99px; padding: 2px 8px; font-size: 0.72rem; font-weight: 600; }
  .badge-red { background: #fee2e2; color: #991b1b; border-radius: 99px; padding: 2px 8px; font-size: 0.72rem; font-weight: 600; }
  .badge-gray { background: #f3f4f6; color: #6b7280; border-radius: 99px; padding: 2px 8px; font-size: 0.72rem; font-weight: 600; }
  .text-muted { color: #9ca3af; font-size: 0.8rem; }
  .msg { padding: 10px 14px; border-radius: 6px; font-size: 0.875rem; margin-top: 0.75rem; }
  .msg-ok { background: #d1fae5; color: #065f46; }
  .msg-err { background: #fee2e2; color: #991b1b; }
  code { font-family: monospace; font-size: 0.85em; background: #f3f4f6; padding: 2px 5px; border-radius: 4px; word-break: break-all; }
  #connect-section { padding: 1.25rem; }
  #agency-info { font-size: 0.8rem; color: #6b7280; }
  .collapsible summary { cursor: pointer; font-size: 0.875rem; font-weight: 500; color: #6b7280; list-style: none; padding: 0.75rem 1.25rem; border-top: 1px solid #e5e7eb; }
  .collapsible summary::before { content: '▸ '; }
  details.collapsible[open] summary::before { content: '▾ '; }
  .collapsible-body { padding: 1.25rem; padding-top: 0.75rem; }
  #sites-table-wrap { overflow-x: auto; }
  #empty-sites { padding: 2rem; text-align: center; color: #9ca3af; font-size: 0.875rem; }
</style>
</head>
<body>
<header>
  <h1>MCPWP</h1>
  <span class="badge">Agency Dashboard</span>
  <span id="agency-info" style="margin-left:auto"></span>
</header>
<div class="container">

  <!-- Connect panel -->
  <div class="card">
    <div class="card-header"><h2>Agency Token</h2></div>
    <div id="connect-section">
      <div class="row">
        <div class="field">
          <label for="token-input">Agency Token</label>
          <input type="password" id="token-input" placeholder="mcpwp_agency_..." autocomplete="off" />
        </div>
        <button class="btn-primary" onclick="connect()">Connect</button>
        <button class="btn-sm" style="background:#f3f4f6;color:#374151" onclick="disconnect()">Clear</button>
      </div>
      <div id="connect-msg"></div>
      <p class="text-muted" style="margin-top:0.5rem">Token is stored in localStorage for this browser session.</p>
    </div>
  </div>

  <!-- Sites panel (hidden until connected) -->
  <div class="card" id="sites-card" style="display:none">
    <div class="card-header">
      <h2>Client Sites <span id="site-count" class="text-muted"></span></h2>
      <button class="btn-primary btn-sm" onclick="refreshSites()">↺ Refresh</button>
    </div>
    <div id="sites-table-wrap">
      <div id="empty-sites">No sites registered yet. Add one below.</div>
    </div>

    <!-- Add site form -->
    <details class="collapsible">
      <summary>Add Client Site</summary>
      <div class="collapsible-body">
        <div class="row">
          <div class="field">
            <label>Site URL (https://)</label>
            <input type="url" id="add-url" placeholder="https://client.com" />
          </div>
          <div class="field">
            <label>MCPWP API Key</label>
            <input type="password" id="add-key" placeholder="spai_..." />
          </div>
          <div class="field" style="max-width:180px">
            <label>Label (optional)</label>
            <input type="text" id="add-label" placeholder="Client A" />
          </div>
          <button class="btn-primary" onclick="addSite()">Add Site</button>
        </div>
        <div id="add-msg"></div>
      </div>
    </details>
  </div>

  <!-- Admin panel — create agency -->
  <details class="collapsible" style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:1.5rem">
    <summary>Admin: Create Agency Account</summary>
    <div class="collapsible-body">
      <p class="text-muted" style="margin-bottom:0.75rem">Requires the ADMIN_SECRET configured in the worker. The returned token cannot be retrieved again — save it securely.</p>
      <div class="row">
        <div class="field">
          <label>Admin Secret</label>
          <input type="password" id="admin-secret" placeholder="••••••••" autocomplete="off" />
        </div>
        <div class="field" style="max-width:240px">
          <label>Agency Name</label>
          <input type="text" id="agency-name" placeholder="Acme Agency" />
        </div>
        <button class="btn-primary" onclick="createAgency()">Create Agency</button>
      </div>
      <div id="create-msg"></div>
    </div>
  </details>

</div>

<script>
const BASE = '';  // same origin

function token() { return localStorage.getItem('mcpwp_agency_token') ?? ''; }
function setToken(t) { localStorage.setItem('mcpwp_agency_token', t); }
function clearToken() { localStorage.removeItem('mcpwp_agency_token'); }

function showMsg(id, text, ok) {
  const el = document.getElementById(id);
  el.className = 'msg ' + (ok ? 'msg-ok' : 'msg-err');
  el.textContent = text;
  el.style.display = 'block';
  if (ok) setTimeout(() => { el.style.display = 'none'; }, 4000);
}

async function connect() {
  const t = document.getElementById('token-input').value.trim();
  if (!t) return showMsg('connect-msg', 'Enter your agency token.', false);
  setToken(t);
  await loadSites();
}

function disconnect() {
  clearToken();
  document.getElementById('token-input').value = '';
  document.getElementById('sites-card').style.display = 'none';
  document.getElementById('agency-info').textContent = '';
  document.getElementById('connect-msg').style.display = 'none';
}

async function loadSites() {
  const t = token();
  if (!t) return;

  let sites;
  try {
    const r = await fetch(BASE + '/api/sites', { headers: { Authorization: 'Bearer ' + t } });
    if (!r.ok) {
      showMsg('connect-msg', 'Invalid token or server error: ' + r.status, false);
      clearToken();
      return;
    }
    sites = await r.json();
  } catch (e) {
    showMsg('connect-msg', 'Network error: ' + e.message, false);
    return;
  }

  document.getElementById('connect-msg').style.display = 'none';
  document.getElementById('sites-card').style.display = '';
  document.getElementById('site-count').textContent = '(' + sites.length + ')';
  document.getElementById('agency-info').textContent = 'Connected · ' + sites.length + ' sites';

  // Render table
  const wrap = document.getElementById('sites-table-wrap');
  if (!sites.length) {
    wrap.innerHTML = '<div id="empty-sites">No sites registered yet. Add one below.</div>';
    return;
  }
  // Build table using DOM methods — no innerHTML with user data.
  const table = document.createElement('table');
  const thead = table.createTHead();
  const hrow = thead.insertRow();
  ['Label', 'URL', 'Health', 'Added', ''].forEach(h => {
    const th = document.createElement('th');
    th.textContent = h;
    hrow.appendChild(th);
  });
  const tbody = table.createTBody();
  sites.forEach(s => {
    const tr = tbody.insertRow();

    const tdLabel = tr.insertCell();
    const strong = document.createElement('strong');
    strong.textContent = s.label ?? '';
    tdLabel.appendChild(strong);

    const tdUrl = tr.insertCell();
    // Only render as link if URL is safely https
    if (typeof s.url === 'string' && s.url.startsWith('https://')) {
      const a = document.createElement('a');
      a.href = s.url;
      a.target = '_blank';
      a.rel = 'noopener noreferrer';
      a.style.color = '#7c3aed';
      a.textContent = s.url;
      tdUrl.appendChild(a);
    } else {
      tdUrl.textContent = s.url ?? '';
    }

    const tdHealth = tr.insertCell();
    const badge = document.createElement('span');
    badge.className = 'badge-gray';
    badge.id = 'health-' + (s.site_id ?? '');
    badge.textContent = 'Checking…';
    tdHealth.appendChild(badge);

    const tdDate = tr.insertCell();
    tdDate.className = 'text-muted';
    tdDate.textContent = (s.added_at ?? '').slice(0, 10);

    const tdAction = tr.insertCell();
    const btn = document.createElement('button');
    btn.className = 'btn-danger';
    btn.textContent = 'Remove';
    const siteId = s.site_id;
    btn.addEventListener('click', () => removeSite(siteId));
    tdAction.appendChild(btn);
  });
  wrap.innerHTML = '';
  wrap.appendChild(table);

  // Async health checks
  pollHealth(sites);
}

async function pollHealth(sites) {
  const t = token();
  if (!t || !sites.length) return;
  try {
    const r = await fetch(BASE + '/api/sites/health', { headers: { Authorization: 'Bearer ' + t } });
    if (!r.ok) return;
    const data = await r.json();
    for (const { site_id, online } of data) {
      const el = document.getElementById('health-' + site_id);
      if (!el) continue;
      el.className = online ? 'badge-green' : 'badge-red';
      el.textContent = online ? '● Online' : '✕ Offline';
    }
  } catch { /* ignore */ }
}

async function refreshSites() { await loadSites(); }

async function addSite() {
  const t = token();
  if (!t) return showMsg('add-msg', 'Not connected.', false);
  const url = document.getElementById('add-url').value.trim();
  const api_key = document.getElementById('add-key').value.trim();
  const label = document.getElementById('add-label').value.trim() || undefined;
  if (!url || !api_key) return showMsg('add-msg', 'URL and API key are required.', false);

  try {
    const r = await fetch(BASE + '/api/sites', {
      method: 'POST',
      headers: { Authorization: 'Bearer ' + t, 'Content-Type': 'application/json' },
      body: JSON.stringify({ url, api_key, label }),
    });
    const data = await r.json();
    if (!r.ok) return showMsg('add-msg', data.error ?? 'Failed: ' + r.status, false);
    showMsg('add-msg', 'Site added: ' + data.site_id, true);
    document.getElementById('add-url').value = '';
    document.getElementById('add-key').value = '';
    document.getElementById('add-label').value = '';
    await loadSites();
  } catch (e) {
    showMsg('add-msg', 'Network error: ' + e.message, false);
  }
}

async function removeSite(siteId) {
  if (!confirm('Remove site "' + siteId + '"? This cannot be undone.')) return;
  const t = token();
  try {
    const r = await fetch(BASE + '/api/sites/' + encodeURIComponent(siteId), {
      method: 'DELETE',
      headers: { Authorization: 'Bearer ' + t },
    });
    if (!r.ok) { const d = await r.json(); return alert('Error: ' + (d.error ?? r.status)); }
    await loadSites();
  } catch (e) { alert('Network error: ' + e.message); }
}

async function createAgency() {
  const secret = document.getElementById('admin-secret').value.trim();
  const name = document.getElementById('agency-name').value.trim() || 'My Agency';
  if (!secret) return showMsg('create-msg', 'Admin secret is required.', false);

  try {
    const r = await fetch(BASE + '/api/accounts', {
      method: 'POST',
      headers: { 'X-Admin-Secret': secret, 'Content-Type': 'application/json' },
      body: JSON.stringify({ name }),
    });
    const data = await r.json();
    if (!r.ok) return showMsg('create-msg', data.error ?? 'Failed: ' + r.status, false);
    const el = document.getElementById('create-msg');
    el.className = 'msg msg-ok';
    el.replaceChildren(); // clear
    const intro = document.createElement('p');
    intro.textContent = 'Agency created! Save this token — it cannot be retrieved again:';
    const tokenCode = document.createElement('code');
    tokenCode.style.cssText = 'display:block;margin-top:8px;word-break:break-all';
    tokenCode.textContent = data.token ?? '';
    const idLine = document.createElement('p');
    idLine.style.marginTop = '6px';
    idLine.textContent = 'Agency ID: ';
    const idCode = document.createElement('code');
    idCode.textContent = data.agency_id ?? '';
    idLine.appendChild(idCode);
    el.appendChild(intro);
    el.appendChild(tokenCode);
    el.appendChild(idLine);
    el.style.display = 'block';
    // Pre-fill the token field
    document.getElementById('token-input').value = data.token;
  } catch (e) {
    showMsg('create-msg', 'Network error: ' + e.message, false);
  }
}

// Auto-connect if token already in storage
window.addEventListener('DOMContentLoaded', () => {
  const t = token();
  if (t) {
    document.getElementById('token-input').value = t;
    loadSites();
  }
});
</script>
</body>
</html>`;
