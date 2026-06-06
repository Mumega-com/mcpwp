/* global React, ReactDOM, SiteNav, SiteFooter, SiteTweaks, useSiteTheme */

const PERSONAS = [
  {
    ic: '◇', name: 'Agencies', tag: 'many sites, one workflow',
    desc: 'Spin up client sites, replicate proven layouts and manage everything from a centralized dashboard with role-scoped keys.',
    prompt: 'Clone our standard 5-page service site onto the new client install and swap in their brand colors.',
    tools: ['wp_build_page', 'elementor-templates', 'site', 'admin'],
  },
  {
    ic: '◆', name: 'Site builders', tag: 'ship pages faster',
    desc: 'Go from brief to published page without leaving the chat. Blueprints handle structure; you refine the details.',
    prompt: 'Build a landing page with a hero, three feature cards, testimonials and a pricing table.',
    tools: ['elementor-build', 'blueprints', 'media', 'seo'],
  },
  {
    ic: '▣', name: 'Store owners', tag: 'WooCommerce on autopilot',
    desc: 'Create products, fulfill orders and pull analytics in plain English — no spreadsheet gymnastics.',
    prompt: 'Add 12 products from this list, set categories, and show me last month\u2019s top sellers.',
    tools: ['wc_create_product', 'woocommerce', 'taxonomy', 'media'],
  },
  {
    ic: '⌗', name: 'Developers', tag: 'AI-native WordPress',
    desc: 'Wire your assistant to a real site with role-scoped keys, validation and a full activity log behind every call.',
    prompt: 'Edit widget abc123 on page 42 to update its title and regenerate the page CSS.',
    tools: ['wp_edit_widget', 'elementor', 'webhooks', 'gutenberg'],
  },
];

function UseCases() {
  const [t, set] = useSiteTheme();
  return (
    <>
      <div className="bg-grid" aria-hidden />
      <SiteNav active="Use cases" />

      <section className="page-hero">
        <div className="hero-glow" aria-hidden />
        <div className="wrap">
          <div className="crumbs"><a href="MCPWP Offer Site.html">Home</a> <span>/</span> <span>Use cases</span></div>
          <span className="eyebrow">Use cases</span>
          <h1>One plugin. <span className="grad-text">Four ways to work faster.</span></h1>
          <p>However you run WordPress, MCPWP turns repetitive operations into a sentence. Here's what that looks like in practice.</p>
        </div>
      </section>

      <div className="wrap" style={{ paddingBottom: '40px' }}>
        <div className="uc-grid">
          {PERSONAS.map((p) => (
            <div className="uc-card" key={p.name}>
              <div className="uc-persona">
                <span className="res-ic mono">{p.ic}</span>
                <div><h3>{p.name}</h3><span className="mono">{p.tag}</span></div>
              </div>
              <p>{p.desc}</p>
              <div className="uc-prompt">
                <span className="uc-q mono">You ask</span>
                <p>"{p.prompt}"</p>
              </div>
              <div className="uc-tools">
                {p.tools.map((tl) => <span className="uc-tool" key={tl}>{tl}</span>)}
              </div>
            </div>
          ))}
        </div>

        <div className="mini-cta">
          <h2>Find your workflow yet?</h2>
          <p>Connect your assistant with a scoped key, then operate your site in plain English.</p>
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

ReactDOM.createRoot(document.getElementById('root')).render(<UseCases />);
