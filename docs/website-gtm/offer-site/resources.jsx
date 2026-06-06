/* global React, ReactDOM, SiteNav, SiteFooter, SiteTweaks, useSiteTheme */

const RESOURCES = [
  { ic: '{ }', title: 'Documentation', desc: 'Install, connect, build. The full quickstart plus reference for every tool category.', link: 'Read the docs →', href: 'Docs.html' },
  { ic: '⬚', title: 'Blueprint library', desc: 'Reusable section patterns you can build in one call — hero, pricing, FAQ, team and more.', link: 'Browse blueprints →', href: 'MCPWP Offer Site.html#blueprints' },
  { ic: '✦', title: 'AI client setup', desc: 'Connect Claude Code, Claude Desktop, Cursor or another MCP-capable client to your WordPress site.', link: 'Read setup →', href: 'Docs.html#connect' },
  { ic: '↯', title: 'Use cases', desc: 'Real workflows for agencies, builders, store owners and developers.', link: 'See use cases →', href: 'Use Cases.html' },
  { ic: '✎', title: 'Blog', desc: 'Tutorials, deep dives and product news from the team behind MCPWP.', link: 'Read the blog →', href: 'Blog.html' },
  { ic: '◷', title: 'Changelog', desc: 'Every release, what changed, and where the roadmap is heading.', link: 'View changelog →', href: 'Changelog.html' },
  { ic: '⌥', title: 'GitHub', desc: 'Source, issues, discussions and releases. Open and GPL-licensed.', link: 'Open repository →', href: 'https://github.com/Mumega-com/mcpwp' },
  { ic: '⛨', title: 'Security', desc: 'Our vulnerability disclosure policy and how we keep AI operations safe.', link: 'Read the policy →', href: 'https://github.com/Mumega-com/mcpwp/blob/main/SECURITY.md' },
  { ic: '◎', title: 'MCP operations', desc: 'Patterns for routing safe AI-assisted workflows across WordPress sites.', link: 'View workflows →', href: 'Use Cases.html' },
];

function Resources() {
  const [t, set] = useSiteTheme();
  return (
    <>
      <div className="bg-grid" aria-hidden />
      <SiteNav active="Resources" />

      <section className="page-hero">
        <div className="hero-glow" aria-hidden />
        <div className="wrap">
          <div className="crumbs"><a href="MCPWP Offer Site.html">Home</a> <span>/</span> <span>Resources</span></div>
          <span className="eyebrow">Resources</span>
          <h1>Everything you need to ship with&nbsp;MCPWP.</h1>
          <p>Docs, blueprints, the Claude Code plugin, source code and field-tested workflows — all in one place.</p>
        </div>
      </section>

      <div className="wrap" style={{ paddingBottom: '40px' }}>
        <div className="res-grid">
          {RESOURCES.map((r) => {
            const ext = r.href.startsWith('http');
            return (
              <a className="res-card" key={r.title} href={r.href} {...(ext ? { target: '_blank', rel: 'noreferrer' } : {})}>
                <span className="res-ic mono">{r.ic}</span>
                <h3>{r.title}</h3>
                <p>{r.desc}</p>
                <span className="res-link mono">{r.link}</span>
              </a>
            );
          })}
        </div>

        <div className="mini-cta">
          <h2>Ready to put it to work?</h2>
          <p>Download MCPWP, create a scoped key, and prove the first MCP connection on your site.</p>
          <div className="hero-actions">
            <a className="btn btn-primary btn-lg" href="MCPWP Offer Site.html#pricing">See current plans</a>
            <a className="btn btn-ghost btn-lg" href="Docs.html">Read the quickstart →</a>
          </div>
        </div>
      </div>

      <SiteFooter />
      <SiteTweaks t={t} set={set} />
    </>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<Resources />);
