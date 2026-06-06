/* global React, ReactDOM, SiteNav, SiteFooter, SiteTweaks, useSiteTheme */

const RELATED = [
  { cat: 'Tutorials', v: 'v2', read: '9 min', title: 'Connect Claude, Cursor and Windsurf in under 5 minutes', excerpt: 'The exact config blocks for every supported client.', author: 'Mumega', date: 'Apr 24, 2026' },
  { cat: 'Product', v: '', read: '5 min', title: 'Introducing role-scoped API keys', excerpt: 'Scope exactly what an assistant can do on your site.', author: 'Mumega', date: 'May 22, 2026' },
  { cat: 'MCP', v: 'v3', read: '6 min', title: 'What is the Model Context Protocol?', excerpt: 'A plain-English primer on MCP and JSON-RPC.', author: 'Mumega', date: 'May 8, 2026' },
];

function Article() {
  const [t, set] = useSiteTheme();
  return (
    <>
      <div className="bg-grid" aria-hidden />
      <SiteNav active="Blog" />

      <article className="section" style={{ paddingTop: 'clamp(48px,8vw,90px)', paddingBottom: 0 }}>
        <div className="wrap article-wrap">
          <div className="article-head">
            <div className="tag-row" style={{ justifyContent: 'center' }}>
              <span className="cat-tag">Tutorials</span><span className="read-time">8 min read</span>
            </div>
            <h1>Build a full landing page with one prompt — start to finish</h1>
            <p className="article-lead">From a single sentence to a published, styled Elementor page — hero, features, pricing and a working contact form, without touching the editor.</p>
            <div className="article-byline">
              <span className="avatar">M</span>
              <span>Mumega Team</span><span>·</span><span>May 26, 2026</span>
            </div>
          </div>

          <div className="article-cover"><span className="ph-label">// hero cover image — drop a screenshot of the built page</span></div>

          <div className="prose">
            <p>The promise of MCPWP is simple: describe what you want, and your AI assistant builds it on a real WordPress site. In this walkthrough we go from one sentence to a complete, published landing page — and look at exactly which tools fire along the way.</p>

            <h2 id="the-prompt">Start with one sentence</h2>
            <p>Open your connected client — Claude, Cursor, or Windsurf — and describe the page in plain language. There's no need to name tools or pass IDs; MCPWP resolves all of that for you.</p>
            <blockquote>"Build a landing page for my yoga studio with a hero, three feature cards and a pricing section."</blockquote>
            <p>Behind the scenes the assistant calls <code className="mono" style={{color:'var(--accent-bright)'}}>wp_build_page</code> with a list of blueprint sections. Each blueprint expands into a fully-styled Elementor structure.</p>

            <div className="code">
              <div className="code-top"><span className="tl tl-r"></span><span className="tl tl-y"></span><span className="tl tl-g"></span><span className="code-lang">tool call</span></div>
              <pre><code>{`wp_build_page(title: "Serenity Yoga", sections: [
  {type: "hero",     heading: "Find your calm", button_text: "Book a class"},
  {type: "features", columns: 3, items: [ ... ]},
  {type: "pricing",  plans: 3 }
])`}</code></pre>
            </div>

            <div className="callout">
              <span className="callout-ic">i</span>
              <p>Every blueprint ships responsive by default — flex grids, shadows and hover states are applied automatically, so you never start from a blank canvas.</p>
            </div>

            <h2 id="validation">Validation happens for free</h2>
            <p>Before anything is written to your database, MCPWP runs each operation through a validation layer. It auto-fixes missing element IDs, corrects wrong widget keys, repairs nesting errors, and even fuzzy-matches typos in widget types.</p>
            <ul>
              <li><strong>Missing IDs</strong> are generated and back-filled.</li>
              <li><strong>Wrong widget keys</strong> are mapped to the closest valid type.</li>
              <li><strong>Nesting errors</strong> are restructured into valid containers.</li>
            </ul>

            <h2 id="publish">Publish and regenerate CSS</h2>
            <p>Once the structure is valid, MCPWP saves the document and forces a direct meta overwrite to guarantee persistence. It then regenerates Elementor's CSS and purges common caches — SiteGround, WP Rocket and LiteSpeed — so the page is live and styled the moment the call returns.</p>

            <div className="code">
              <div className="code-top"><span className="tl tl-r"></span><span className="tl tl-y"></span><span className="tl tl-g"></span><span className="code-lang">result</span></div>
              <pre><code>{`# Page published
url:    "/serenity-yoga"
status: "publish"
css:    "regenerated"  // caches purged`}</code></pre>
            </div>

            <p>That's the whole loop: one sentence in, a published page out. From here you can refine any single widget with <code className="mono" style={{color:'var(--accent-bright)'}}>wp_edit_widget</code>, swap imagery with the media tools, or layer on SEO — all in the same conversation.</p>
          </div>

          <div className="article-foot">
            <div className="post-author">
              <span className="avatar">M</span>
              <span className="who"><b>Mumega Team</b><span>Builders of MCPWP</span></span>
            </div>
            <div className="share-row">
              <a className="share-btn" href="#" aria-label="Share on X">X</a>
              <a className="share-btn" href="#" aria-label="Copy link">↗</a>
              <a className="share-btn" href="#" aria-label="Share">in</a>
            </div>
          </div>
        </div>
      </article>

      <section className="section related">
        <div className="wrap">
          <div className="section-head" style={{ marginBottom: 0 }}>
            <span className="eyebrow">Keep reading</span>
            <h2 style={{ fontSize: '32px', margin: '14px 0 0' }}>Related articles</h2>
          </div>
          <div className="post-grid">
            {RELATED.map((p) => (
              <a className="post-card" href="Article.html" key={p.title}>
                <div className={"post-thumb " + p.v}></div>
                <div className="post-card-body">
                  <div className="tag-row"><span className="cat-tag">{p.cat}</span><span className="read-time">{p.read}</span></div>
                  <h3>{p.title}</h3>
                  <p>{p.excerpt}</p>
                  <div className="post-meta"><span>{p.author}</span><span>{p.date}</span></div>
                </div>
              </a>
            ))}
          </div>
        </div>
      </section>

      <SiteFooter />
      <SiteTweaks t={t} set={set} />
    </>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<Article />);
