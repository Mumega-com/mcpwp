/* global React, ReactDOM, HeroDemo, Clients, HowItWorks, Tools, Blueprints, Compare, Pricing, FAQ, FinalCTA, SiteNav, SiteFooter, SiteTweaks, useSiteTheme */

function Hero() {
  return (
    <header className="hero" id="top">
      <div className="hero-glow" aria-hidden />
      <div className="wrap hero-top">
        <span className="hero-badge">
          <span className="tag-new">New</span>
          <span><b>Dynamic</b> MCP tools · <b>Reusable</b> blueprints · WordPress + Elementor</span>
        </span>
        <h1>Run WordPress<br/>by <span className="grad-text">talking to your AI.</span></h1>
        <p className="hero-sub">MCPWP turns your site into a Model Context Protocol server — so Claude, Cursor and Windsurf can build pages, edit Elementor, manage WooCommerce and handle SEO in plain English.</p>
        <div className="hero-actions">
          <a className="btn btn-primary btn-lg" href="MCPWP Offer Site.html#pricing">See current plans</a>
          <a className="btn btn-ghost btn-lg" href="#how">See how it works →</a>
        </div>
        <p className="hero-note">Scoped keys · Live tool discovery · First connection workflow</p>
      </div>
      <div className="wrap hero-demo-wrap">
        <HeroDemo />
      </div>
    </header>
  );
}

function App() {
  const [t, set] = useSiteTheme();
  return (
    <>
      <div className="bg-grid" aria-hidden />
      <SiteNav />
      <Hero />
      <Clients />
      <HowItWorks />
      <Tools />
      <Blueprints />
      <Compare />
      <Pricing />
      <FAQ />
      <FinalCTA />
      <SiteFooter />
      <SiteTweaks t={t} set={set} />
    </>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App />);
