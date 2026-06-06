/* global React */
// pricing.jsx — pricing, FAQ, final CTA. Exports to window.

const { useState } = React;

const PLANS = [
  {
    name: 'Core', price: 'Connect', cadence: 'single site start', sites: '1 site',
    blurb: 'Get a WordPress site speaking MCP with scoped access.',
    cta: 'Download MCPWP', ghost: true,
    feats: ['MCP server endpoint', 'Posts, pages & media', 'Menus & options', 'Basic Elementor workflows', 'Scoped API keys', 'Activity log'],
  },
  {
    name: 'Builder', price: 'Operate', cadence: 'site-building workflows', sites: '1 site',
    blurb: 'Everything an operator or builder needs.',
    cta: 'See current plan', highlight: true,
    feats: ['Everything in Core', 'SEO + WooCommerce workflows', 'Elementor Pro + theme builder support', 'Design references & archetypes', 'Agent workflows & AI tools', 'Setup guidance'],
  },
  {
    name: 'Agency', price: 'Scale', cadence: 'multi-site operations', sites: 'Multiple sites',
    blurb: 'Run every client site from one place.',
    cta: 'Talk to us', ghost: true,
    feats: ['Everything in Builder', 'Multi-site operating model', 'Agency workflows', 'Centralized key management', 'Priority setup support'],
  },
];

const FAQS = [
  { q: 'What exactly does MCPWP do?', a: 'It turns your WordPress site into an MCP (Model Context Protocol) server. AI assistants like Claude, Cursor and Windsurf can then operate your site through natural language — building pages, editing Elementor, managing WooCommerce, media and SEO.' },
  { q: 'Is it safe to let an AI touch my site?', a: 'Every action runs through role-scoped API keys, approval gates, rate limits and a full activity log. Operations are validated and auto-fixed before they ever hit your database, and you choose exactly which categories a key can use.' },
  { q: 'Do I need to write any code?', a: 'No. Install the plugin, create an API key in WP Admin, and paste the URL + key into your AI client. From there everything is plain English. There is also a Claude Code plugin with ready-made skills.' },
  { q: 'Does it work with my page builder?', a: 'MCPWP has full Elementor support — build, edit, templates and theme builder — plus Gutenberg blocks and patterns. It works in both Elementor container and classic layout modes, with automatic CSS regeneration and cache purging.' },
  { q: 'How should I choose a plan?', a: 'Start by proving the connection with scoped access, then choose the package that matches the workflows you need: core content operations, builder workflows, or multi-site agency operations.' },
  { q: 'Can I manage multiple client sites?', a: 'Yes. The agency operating model is built for running many WordPress installs with repeatable workflows, centralized key management and setup support.' },
];

function Pricing() {
  return (
    <section className="section" id="pricing">
      <div className="wrap">
        <window.Reveal className="section-head" >
          <span className="eyebrow">Pricing</span>
          <h2>Start scoped. <span className="grad-text">Expand when the workflow proves itself.</span></h2>
          <p>Pick the level of AI control your WordPress site needs: connect, operate, or scale across client sites.</p>
        </window.Reveal>
        <div className="plans">
          {PLANS.map((p, i) => (
            <window.Reveal key={p.name} className={"plan card" + (p.highlight ? ' plan-hi' : '')} style={{ transitionDelay: (i*80)+'ms' }}>
              {p.highlight && <span className="plan-badge mono">Most popular</span>}
              <h3 className="plan-name">{p.name}</h3>
              <p className="plan-blurb">{p.blurb}</p>
              <div className="plan-price">
                <span className="plan-amt">{p.price}</span>
                <span className="plan-cad mono">{p.cadence}</span>
              </div>
              <a href="https://mcpwp.net/pricing" className={"btn " + (p.highlight ? 'btn-primary' : 'btn-ghost') + " plan-cta"}>{p.cta}</a>
              <ul className="plan-feats">
                {p.feats.map((f) => (
                  <li key={f}><span className="ck mono">✓</span>{f}</li>
                ))}
              </ul>
            </window.Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}
window.Pricing = Pricing;

function FAQ() {
  const [open, setOpen] = useState(0);
  return (
    <section className="section section-alt" id="faq">
      <div className="wrap faq-wrap">
        <window.Reveal className="section-head faq-head">
          <span className="eyebrow">FAQ</span>
          <h2>Questions, answered.</h2>
        </window.Reveal>
        <div className="faq-list">
          {FAQS.map((f, i) => (
            <window.Reveal key={i} className={"faq-item" + (open === i ? ' open' : '')} style={{ transitionDelay: (i*40)+'ms' }}>
              <button className="faq-q" onClick={() => setOpen(open === i ? -1 : i)}>
                <span>{f.q}</span>
                <span className="faq-ic mono">{open === i ? '−' : '+'}</span>
              </button>
              <div className="faq-a-wrap"><div className="faq-a"><p>{f.a}</p></div></div>
            </window.Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}
window.FAQ = FAQ;

function FinalCTA() {
  return (
    <section className="section final" id="get-started">
      <div className="wrap">
        <window.Reveal className="final-card">
          <div className="final-glow" aria-hidden />
          <span className="eyebrow">Get started</span>
          <h2>Tell your AI what to build.<br/><span className="grad-text">MCPWP does the WordPress part.</span></h2>
          <p>Install in minutes, connect your assistant, and start operating your site in plain English — pages, Elementor, WooCommerce, SEO and more.</p>
          <div className="final-actions">
            <a href="https://mcpwp.net/pricing" className="btn btn-primary btn-lg">See current plans</a>
            <a href="#install" className="btn btn-ghost btn-lg">Install MCPWP →</a>
          </div>
          <div className="final-cmd card" id="install">
            <span className="final-cmd-label mono">install</span>
            <code className="mono">Install MCPWP, create a scoped API key, then connect https://your-site.com/wp-json/site-pilot-ai/v1/mcp</code>
          </div>
        </window.Reveal>
      </div>
    </section>
  );
}
window.FinalCTA = FinalCTA;
