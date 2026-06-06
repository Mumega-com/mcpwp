/* global React */
// sections.jsx — content sections. Exports several components to window.

const TOOL_CATS = [
  { name: 'content', badge: 'live', what: 'Pages, posts, drafts, bulk ops, search' },
  { name: 'site', badge: 'live', what: 'Menus, options, CSS, design refs, guides' },
  { name: 'woocommerce', badge: 'plugin', what: 'Products, orders, categories, analytics' },
  { name: 'learnpress', badge: 'plugin', what: 'Courses, lessons, quizzes, curriculum' },
  { name: 'admin', badge: 'secure', what: 'API keys, rate limits, settings, updates' },
  { name: 'elementor-templates', badge: 'builder', what: 'Templates, archetypes, reusable parts' },
  { name: 'elementor', badge: 'builder', what: 'Get/set data, edit sections & widgets' },
  { name: 'elementor-theme', badge: 'builder', what: 'Theme builder, conditions, custom code' },
  { name: 'seo', badge: 'site', what: 'Meta tags, analysis, bulk SEO, indexing' },
  { name: 'elementor-build', badge: 'flow', what: 'Build pages from reusable blueprints' },
  { name: 'media', badge: 'assets', what: 'Upload file / URL / base64, screenshot' },
  { name: 'webhooks', badge: 'events', what: 'Create, test, monitor deliveries' },
  { name: 'elementor-info', badge: 'schema', what: 'Widget schemas, help, CSS regen' },
  { name: 'taxonomy', badge: 'content', what: 'Categories, tags, custom terms' },
  { name: 'gutenberg', badge: 'editorial', what: 'Blocks, patterns, block types' },
];

const BLUEPRINTS = [
  'hero','features','cta','pricing','faq','testimonials','team','portfolio',
  'blog_grid','services','about','process_steps','social_proof','product_showcase',
  'before_after','newsletter','stats','gallery','text','map','countdown',
  'logo_grid','video','contact_form',
];

const CLIENTS = ['Claude Code','Claude Desktop','Cursor','Windsurf','Gemini','GPT'];

const COMPARE = {
  cols: ['MCPWP', 'MCP Adapter', 'Royal MCP', 'InstaWP'],
  rows: [
    ['MCP tools', ['Dynamic discovery', true], 'Limited', 'Fixed list', 'External'],
    ['Page blueprints', ['Reusable patterns', true], 'No', 'No', 'No'],
    ['Elementor', ['Full build + edit', true], 'No', 'No', 'No'],
    ['WooCommerce', ['Plugin-aware', true], 'No', 'No', 'No'],
    ['LearnPress', ['Plugin-aware', true], 'No', 'No', 'No'],
    ['Role-scoped keys', ['Yes', true], 'No', 'No', 'No'],
    ['Validation + auto-fix', ['Yes', true], 'No', 'No', 'No'],
    ['Install', ['WP plugin', false], 'Abilities API', 'WP plugin', 'External Node'],
  ],
};

/* ---------- Reveal wrapper ---------- */
function Reveal({ children, className = '', style }) {
  const ref = React.useRef(null);
  React.useEffect(() => {
    const el = ref.current;
    const io = new IntersectionObserver(([e]) => {
      if (e.isIntersecting) { el.classList.add('in'); io.disconnect(); }
    }, { threshold: 0.12 });
    io.observe(el);
    return () => io.disconnect();
  }, []);
  return <div ref={ref} className={"reveal " + className} style={style}>{children}</div>;
}
window.Reveal = Reveal;

/* ---------- AI clients marquee ---------- */
function Clients() {
  return (
    <div className="clients wrap">
      <p className="clients-label mono">Works with the AI tools you already use</p>
      <div className="clients-row">
        {CLIENTS.map((c) => (
          <div className="client" key={c}>
            <span className="client-glyph mono">{c.split(' ').map(w=>w[0]).join('')}</span>
            <span>{c}</span>
          </div>
        ))}
      </div>
    </div>
  );
}
window.Clients = Clients;

/* ---------- How it works ---------- */
function HowItWorks() {
  const nodes = [
    { t: 'AI Assistant', d: 'Claude · Cursor · Windsurf · Gemini', tag: 'natural language' },
    { t: 'MCPWP Plugin', d: 'MCP server · auth · validation', tag: 'JSON-RPC' },
    { t: 'Your WordPress', d: 'Pages · Elementor · WooCommerce · media', tag: 'REST + Document API' },
  ];
  return (
    <section className="section" id="how">
      <div className="wrap">
        <Reveal className="section-head">
          <span className="eyebrow">How it works</span>
          <h2>Your site becomes a tool the AI can&nbsp;operate.</h2>
          <p>MCPWP exposes WordPress as a Model Context Protocol server. The assistant speaks plain English; the plugin translates it into safe, validated operations on your site.</p>
        </Reveal>
        <div className="flow">
          {nodes.map((n, i) => (
            <React.Fragment key={i}>
              <Reveal className="flow-node card" style={{ transitionDelay: (i*90)+'ms' }}>
                <span className="flow-step mono">0{i+1}</span>
                <h3>{n.t}</h3>
                <p>{n.d}</p>
                <span className="flow-tag mono">{n.tag}</span>
              </Reveal>
              {i < nodes.length - 1 && <div className="flow-arrow mono" aria-hidden>→</div>}
            </React.Fragment>
          ))}
        </div>
      </div>
    </section>
  );
}
window.HowItWorks = HowItWorks;

/* ---------- Tools ---------- */
function Tools() {
  return (
    <section className="section section-alt" id="tools">
      <div className="wrap">
        <Reveal className="section-head">
          <span className="eyebrow">The toolbox</span>
          <h2><span className="grad-text">Dynamic tools</span> across your WordPress stack.</h2>
          <p>One install, an entire operations layer. The live list adapts to your active plugins, license plan, and role-scoped keys.</p>
        </Reveal>
        <div className="tool-grid">
          {TOOL_CATS.map((c, i) => (
            <Reveal className="tool-cat card" key={c.name} style={{ transitionDelay: (i%3*60)+'ms' }}>
              <div className="tool-cat-top">
                <span className="tool-cat-name mono">{c.name}</span>
                <span className="tool-cat-n">{c.badge}</span>
              </div>
              <p>{c.what}</p>
              <span className="tool-bar" style={{ '--w': '78%' }} />
            </Reveal>
          ))}
          <Reveal className="tool-cat card tool-cat-sum" style={{ transitionDelay: '120ms' }}>
            <span className="tool-cat-n big">live</span>
            <p>callable operations adapt to installed plugins, license plan and scoped keys.</p>
          </Reveal>
        </div>
      </div>
    </section>
  );
}
window.Tools = Tools;

/* ---------- Blueprints ---------- */
function Blueprints() {
  return (
    <section className="section" id="blueprints">
      <div className="wrap">
        <Reveal className="section-head">
          <span className="eyebrow">Build pages in one call</span>
          <h2>Reusable blueprints. <span className="grad-text">Whole pages, safely.</span></h2>
          <p>Skip the blank canvas. Ask for a section type and MCPWP assembles a styled, responsive Elementor layout — shadows, grids, hover states and all.</p>
        </Reveal>
        <div className="bp-grid">
          {BLUEPRINTS.map((b, i) => (
            <Reveal key={b} className="bp-chip" style={{ transitionDelay: (i%6*35)+'ms' }}>
              <span className="bp-num mono">{String(i+1).padStart(2,'0')}</span>
              <span className="bp-name mono">{b}</span>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}
window.Blueprints = Blueprints;

/* ---------- Comparison ---------- */
function Compare() {
  return (
    <section className="section section-alt" id="compare">
      <div className="wrap">
        <Reveal className="section-head">
          <span className="eyebrow">Why MCPWP</span>
          <h2>Not the only WordPress MCP. <span className="grad-text">By far the deepest.</span></h2>
        </Reveal>
        <Reveal className="cmp-wrap">
          <table className="cmp">
            <thead>
              <tr>
                <th></th>
                {COMPARE.cols.map((c, i) => (
                  <th key={c} className={i===0 ? 'cmp-us' : ''}>{c}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {COMPARE.rows.map((row) => (
                <tr key={row[0]}>
                  <td className="cmp-feat">{row[0]}</td>
                  {row.slice(1).map((cell, ci) => {
                    const isUs = ci === 0;
                    const val = Array.isArray(cell) ? cell[0] : cell;
                    const good = Array.isArray(cell) ? cell[1] : false;
                    const no = val === 'No' || val === '0';
                    return (
                      <td key={ci} className={(isUs ? 'cmp-us ' : '') + (no ? 'cmp-no' : '')}>
                        {isUs && good && <span className="cmp-check mono">✓</span>}
                        {val}
                      </td>
                    );
                  })}
                </tr>
              ))}
            </tbody>
          </table>
        </Reveal>
      </div>
    </section>
  );
}
window.Compare = Compare;
