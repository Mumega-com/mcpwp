/* global React, ReactDOM, SiteNav, SiteFooter, SiteTweaks, useSiteTheme */

const { useState } = React;

const CATS = ['All', 'Tutorials', 'Product', 'Engineering', 'Agency', 'MCP'];

const FEATURED = {
  cat: 'Tutorials', read: '8 min read',
  title: 'Build a full landing page with one prompt — start to finish',
  excerpt: 'A walkthrough of going from a single sentence to a published, styled Elementor page using MCPWP blueprints — hero, features, pricing and a working contact form.',
  author: 'Mumega Team', role: 'May 26, 2026', av: 'M',
};

const POSTS = [
  { cat: 'Product', v: '', read: '5 min', title: 'Introducing role-scoped API keys: 5 ways to safely hand the keys to AI', excerpt: 'Admin, designer, editor, author, or a custom set of categories — scope exactly what an assistant can do on your site.', author: 'Mumega', date: 'May 22, 2026' },
  { cat: 'Engineering', v: 'v2', read: '11 min', title: 'How MCPWP validates and auto-fixes Elementor before it touches your DB', excerpt: 'Inside the validation layer: missing IDs, wrong widget keys, nesting errors and fuzzy matching for typos.', author: 'Mumega', date: 'May 14, 2026' },
  { cat: 'MCP', v: 'v3', read: '6 min', title: 'What is the Model Context Protocol, and why does WordPress need it?', excerpt: 'A plain-English primer on MCP, JSON-RPC, and what it means to expose your site as a set of callable tools.', author: 'Mumega', date: 'May 8, 2026' },
  { cat: 'Agency', v: '', read: '7 min', title: 'Running 40 client sites from one chat window', excerpt: 'How agencies use the Agency plan dashboard and centralized keys to operate many WordPress installs at once.', author: 'Mumega', date: 'Apr 30, 2026' },
  { cat: 'Tutorials', v: 'v2', read: '9 min', title: 'Connect Claude, Cursor and Windsurf to your site in under 5 minutes', excerpt: 'The exact config blocks for every supported client, plus how to test your first tool call.', author: 'Mumega', date: 'Apr 24, 2026' },
  { cat: 'Product', v: 'v3', read: '4 min', title: 'WooCommerce, meet your AI: 21 tools for products, orders and analytics', excerpt: 'Create products, fulfill orders and pull store analytics — all through natural language.', author: 'Mumega', date: 'Apr 18, 2026' },
];

function PostCard({ p, i }) {
  return (
    <a className="post-card" href="Article.html">
      <div className={"post-thumb " + (p.v || '')}></div>
      <div className="post-card-body">
        <div className="tag-row"><span className="cat-tag">{p.cat}</span><span className="read-time">{p.read}</span></div>
        <h3>{p.title}</h3>
        <p>{p.excerpt}</p>
        <div className="post-meta"><span>{p.author}</span><span>{p.date}</span></div>
      </div>
    </a>
  );
}

function Blog() {
  const [t, set] = useSiteTheme();
  const [cat, setCat] = useState('All');
  const visible = cat === 'All' ? POSTS : POSTS.filter((p) => p.cat === cat);
  return (
    <>
      <div className="bg-grid" aria-hidden />
      <SiteNav active="Blog" />
      <section className="page-hero">
        <div className="hero-glow" aria-hidden />
        <div className="wrap">
          <div className="crumbs"><a href="MCPWP Offer Site.html">Home</a> <span>/</span> <span>Blog</span></div>
          <span className="eyebrow">The MCPWP blog</span>
          <h1>Builds, deep dives & the MCP&nbsp;era of WordPress.</h1>
          <p>Tutorials, product news and engineering notes on operating WordPress with AI — written by the team behind MCPWP.</p>
        </div>
      </section>

      <div className="wrap" style={{ paddingBottom: '40px' }}>
        <div className="blog-tabs">
          {CATS.map((c) => (
            <button key={c} className={"blog-tab" + (cat === c ? ' on' : '')} onClick={() => setCat(c)}>{c}</button>
          ))}
        </div>

        {cat === 'All' && (
          <a className="featured" href="Article.html">
            <div className="featured-img"><span className="ph-label">// featured cover — drop a screenshot</span></div>
            <div className="featured-body">
              <div className="tag-row"><span className="cat-tag">{FEATURED.cat}</span><span className="read-time">{FEATURED.read}</span></div>
              <h2>{FEATURED.title}</h2>
              <p>{FEATURED.excerpt}</p>
              <div className="post-author">
                <span className="avatar">{FEATURED.av}</span>
                <span className="who"><b>{FEATURED.author}</b><span>{FEATURED.role}</span></span>
              </div>
            </div>
          </a>
        )}

        <div className="post-grid">
          {visible.map((p, i) => <PostCard p={p} i={i} key={p.title} />)}
        </div>

        <div className="news-band">
          <div>
            <h3>Get the next deep dive in your inbox</h3>
            <p>One email when we ship something worth reading — tutorials, releases and field notes. No spam, unsubscribe anytime.</p>
          </div>
          <form className="news-form" onSubmit={(e) => e.preventDefault()}>
            <input className="news-input" type="email" placeholder="you@studio.com" />
            <button className="btn btn-primary" type="submit">Subscribe</button>
          </form>
        </div>
      </div>

      <SiteFooter />
      <SiteTweaks t={t} set={set} />
    </>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<Blog />);
