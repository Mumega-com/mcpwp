/* global React, ReactDOM, SiteNav, SiteFooter, SiteTweaks, useSiteTheme */

const { useState, useEffect } = React;

const SIDEBAR = [
  { group: 'Getting started', items: [
    { id: 'overview', label: 'Overview', on: true },
    { id: 'install', label: 'Installation' },
    { id: 'keys', label: 'API keys' },
    { id: 'connect', label: 'Connect a client' },
  ]},
  { group: 'Building', items: [
    { id: 'first-build', label: 'Your first build' },
    { id: 'blueprints', label: 'Blueprints' },
    { id: 'editing', label: 'Editing widgets' },
  ]},
  { group: 'Operations', items: [
    { id: 'woocommerce', label: 'WooCommerce' },
    { id: 'media', label: 'Media & SEO' },
    { id: 'roles', label: 'Roles & limits' },
  ]},
];

const TOC = [
  { id: 'overview', label: 'Overview' },
  { id: 'install', label: 'Installation' },
  { id: 'keys', label: 'Create an API key' },
  { id: 'connect', label: 'Connect a client' },
  { id: 'first-build', label: 'Your first build' },
];

function Docs() {
  const [t, set] = useSiteTheme();
  const [active, setActive] = useState('overview');

  useEffect(() => {
    const ids = TOC.map((s) => s.id);
    const io = new IntersectionObserver((entries) => {
      entries.forEach((e) => { if (e.isIntersecting) setActive(e.target.id); });
    }, { rootMargin: '-30% 0px -60% 0px' });
    ids.forEach((id) => { const el = document.getElementById(id); if (el) io.observe(el); });
    return () => io.disconnect();
  }, []);

  return (
    <>
      <div className="bg-grid" aria-hidden />
      <SiteNav active="Docs" />

      <div className="docs-layout">
        {/* sidebar */}
        <aside className="docs-side">
          {SIDEBAR.map((g) => (
            <div className="docs-side-group" key={g.group}>
              <h4>{g.group}</h4>
              {g.items.map((it) => (
                <a key={it.id} href={'#' + it.id} className={active === it.id ? 'on' : ''}>{it.label}</a>
              ))}
            </div>
          ))}
        </aside>

        {/* main */}
        <main className="docs-main">
          <div className="crumbs"><a href="MCPWP Offer Site.html">Home</a> <span>/</span> <span>Docs</span> <span>/</span> <span>Quickstart</span></div>
          <h1 id="overview" className="docs-section">Quickstart</h1>
          <p className="lead">Get from install to your first AI-built page in about five minutes. No code required.</p>
          <div className="callout"><span className="callout-ic">i</span><p>You'll need a WordPress 5.0+ site and an MCP-capable client like Claude, Cursor or Windsurf. Elementor is optional but unlocks the full builder toolset.</p></div>

          <section className="docs-section" id="install" style={{ marginTop: '40px' }}>
            <h2 style={{ fontSize: '28px', marginBottom: '12px' }}>Installation</h2>
            <p style={{ color: 'var(--text-muted)' }}>Install MCPWP from the current package source, or upload the plugin zip from your dashboard.</p>
            <div className="code">
              <div className="code-top"><span className="tl tl-r"></span><span className="tl tl-y"></span><span className="tl tl-g"></span><span className="code-lang">bash</span></div>
              <pre><code><span className="tok-fn">wp</span> plugin install /path/to/current-mcpwp.zip --activate</code></pre>
            </div>
            <p style={{ color: 'var(--text-muted)' }}>Or in WP Admin: <strong style={{color:'var(--text)'}}>Plugins → Add New → Upload Plugin</strong>.</p>
          </section>

          <section className="docs-section" id="keys" style={{ marginTop: '40px' }}>
            <h2 style={{ fontSize: '28px', marginBottom: '12px' }}>Create an API key</h2>
            <p style={{ color: 'var(--text-muted)' }}>Keys are role-scoped — pick exactly how much access an assistant gets.</p>
            <div className="step"><span className="step-n">1</span><div className="step-body"><h3>Open MCPWP → Setup</h3><p>In WP Admin, head to the MCPWP menu and choose the Setup tab.</p></div></div>
            <div className="step"><span className="step-n">2</span><div className="step-body"><h3>Create a key</h3><p>Give it a label and a role: <code className="mono" style={{color:'var(--accent-bright)'}}>admin</code>, <code className="mono" style={{color:'var(--accent-bright)'}}>designer</code>, <code className="mono" style={{color:'var(--accent-bright)'}}>editor</code>, <code className="mono" style={{color:'var(--accent-bright)'}}>author</code>, or <code className="mono" style={{color:'var(--accent-bright)'}}>custom</code>.</p></div></div>
            <div className="step"><span className="step-n">3</span><div className="step-body"><h3>Copy the key</h3><p>It looks like <code className="mono" style={{color:'var(--accent-bright)'}}>spai_•••••</code>. Store it safely — you'll paste it into your client next.</p></div></div>
          </section>

          <section className="docs-section" id="connect" style={{ marginTop: '40px' }}>
            <h2 style={{ fontSize: '28px', marginBottom: '12px' }}>Connect a client</h2>
            <p style={{ color: 'var(--text-muted)' }}>Add MCPWP as an MCP server. Same URL and key work for Claude, Cursor and Windsurf.</p>
            <div className="code">
              <div className="code-top"><span className="tl tl-r"></span><span className="tl tl-y"></span><span className="tl tl-g"></span><span className="code-lang">claude_desktop_config.json</span></div>
              <pre><code>{'{'}
  <span className="tok-key">"mcpServers"</span>: {'{'}
    <span className="tok-key">"mcpwp"</span>: {'{'}
      <span className="tok-key">"url"</span>: <span className="tok-str">"https://your-site.com/wp-json/site-pilot-ai/v1/mcp"</span>,
      <span className="tok-key">"headers"</span>: {'{'} <span className="tok-key">"X-API-Key"</span>: <span className="tok-str">"spai_your_key_here"</span> {'}'}
    {'}'}
  {'}'}
{'}'}</code></pre>
            </div>
          </section>

          <section className="docs-section" id="first-build" style={{ marginTop: '40px' }}>
            <h2 style={{ fontSize: '28px', marginBottom: '12px' }}>Your first build</h2>
            <p style={{ color: 'var(--text-muted)' }}>Restart your client and just ask. No tool names, no IDs.</p>
            <blockquote className="prose" style={{ margin: '20px 0' }}>"Build a services page with a hero, three feature cards and a contact form."</blockquote>
            <p style={{ color: 'var(--text-muted)' }}>MCPWP resolves that into <code className="mono" style={{color:'var(--accent-bright)'}}>wp_build_page</code>, validates the structure, saves it, regenerates CSS and returns a live URL. Open it — your page is published.</p>
          </section>

          <div className="doc-next">
            <a href="MCPWP Offer Site.html#blueprints"><span className="dir">← Explore</span><span className="nxt-title">Reusable blueprints</span></a>
            <a href="Use Cases.html" className="nxt-r"><span className="dir">Next →</span><span className="nxt-title">Use cases & workflows</span></a>
          </div>
        </main>

        {/* on this page */}
        <aside className="docs-toc">
          <h4>On this page</h4>
          {TOC.map((s) => (
            <a key={s.id} href={'#' + s.id} className={active === s.id ? 'on' : ''}>{s.label}</a>
          ))}
        </aside>
      </div>

      <SiteFooter />
      <SiteTweaks t={t} set={set} />
    </>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<Docs />);
