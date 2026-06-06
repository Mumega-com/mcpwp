/* global React, ReactDOM, SiteNav, SiteFooter, SiteTweaks, useSiteTheme */

const RELEASES = [
  { ver: 'v2.8.31', date: 'May 29, 2026', tag: 'latest', title: 'Stability & validation polish', changes: [
    ['imp', 'Faster Elementor CSS regeneration with smarter cache purging across SiteGround, WP Rocket and LiteSpeed.'],
    ['fix', 'Resolved a rare nesting error when building deeply-grouped container layouts.'],
    ['imp', 'Improved fuzzy matching for mistyped widget types.'],
  ]},
  { ver: 'v2.8.0', date: 'May 12, 2026', tag: 'minor', title: 'Dynamic tool discovery', changes: [
    ['new', 'Expanded the toolbox with dynamic discovery across core WordPress and active plugin workflows.'],
    ['new', 'Added webhooks category — create, test and monitor deliveries.'],
    ['imp', 'Dynamic tools/list now adapts to active plugins, license plan and role-scoped keys.'],
  ]},
  { ver: 'v2.6.0', date: 'Apr 20, 2026', tag: 'minor', title: 'Claude Code plugin & agent skills', changes: [
    ['new', 'Released the Claude Code plugin with 6 skills and a wp-builder agent.'],
    ['new', 'Added setup, tools, Elementor and design commands for MCPWP workflows.'],
  ]},
  { ver: 'v2.4.0', date: 'Mar 28, 2026', tag: 'minor', title: 'Role-scoped API keys', changes: [
    ['new', 'Introduced 5 key roles: admin, designer, editor, author and custom.'],
    ['imp', 'Per-category access control for custom keys.'],
    ['fix', 'Hardened rate limiting and activity logging.'],
  ]},
  { ver: 'v2.2.0', date: 'Mar 6, 2026', tag: 'minor', title: 'Reusable page blueprints', changes: [
    ['new', 'Added reusable blueprint section types — hero, features, pricing, faq, team, portfolio and more.'],
    ['new', 'Build entire pages from a single wp_build_page call.'],
    ['imp', 'Validation auto-fixes missing IDs, wrong keys and nesting errors.'],
  ]},
  { ver: 'v2.0.0', date: 'Feb 10, 2026', tag: 'minor', title: 'WooCommerce & LearnPress', changes: [
    ['new', 'Added 21 WooCommerce tools for products, orders, categories and analytics.'],
    ['new', 'Added 18 LearnPress tools for courses, lessons, quizzes and curriculum.'],
    ['new', 'Admin UI: Setup, Library, Tools and Settings.'],
  ]},
];

function Changelog() {
  const [t, set] = useSiteTheme();
  return (
    <>
      <div className="bg-grid" aria-hidden />
      <SiteNav active="Changelog" />

      <section className="page-hero">
        <div className="hero-glow" aria-hidden />
        <div className="wrap">
          <div className="crumbs"><a href="MCPWP Offer Site.html">Home</a> <span>/</span> <span>Changelog</span></div>
          <span className="eyebrow">Changelog</span>
          <h1>Every release, in one place.</h1>
          <p>What's new, improved and fixed across MCPWP. Follow along on <a href="https://github.com/Mumega-com/mcpwp/releases" target="_blank" rel="noreferrer" style={{color:'var(--accent-bright)'}}>GitHub Releases</a>.</p>
        </div>
      </section>

      <div className="wrap" style={{ paddingBottom: '40px' }}>
        <div className="cl-wrap">
          {RELEASES.map((r) => (
            <div className="cl-entry" key={r.ver}>
              <div className="cl-meta">
                <div className="cl-ver">{r.ver}</div>
                <div className="cl-date">{r.date}</div>
              </div>
              <div className="cl-body">
                <span className={"cl-tag " + r.tag}>{r.tag === 'latest' ? 'Latest' : 'Release'}</span>
                <h3>{r.title}</h3>
                <ul className="cl-list">
                  {r.changes.map((c, i) => (
                    <li key={i}><span className={"chg " + c[0]}>{c[0]}</span><span>{c[1]}</span></li>
                  ))}
                </ul>
              </div>
            </div>
          ))}
        </div>

        <div className="mini-cta">
          <h2>Want to shape what ships next?</h2>
          <p>Open issues, request features and follow the roadmap on GitHub.</p>
          <div className="hero-actions">
            <a className="btn btn-primary btn-lg" href="https://github.com/Mumega-com/mcpwp/issues" target="_blank" rel="noreferrer">Open an issue</a>
            <a className="btn btn-ghost btn-lg" href="https://github.com/Mumega-com/mcpwp" target="_blank" rel="noreferrer">View on GitHub →</a>
          </div>
        </div>
      </div>

      <SiteFooter />
      <SiteTweaks t={t} set={set} />
    </>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<Changelog />);
