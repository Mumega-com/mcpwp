/* global React, useTweaks, TweaksPanel, TweakSection, TweakColor, TweakToggle, TweakRadio */
// chrome.jsx — shared site nav, footer, and theme persistence across all pages.
// Exports: window.SiteNav, window.SiteFooter, window.SiteTweaks, window.useSiteTheme, window.ACCENTS

const ACCENTS = {
  "#5b76f7": 264,
  "#3bb4f5": 232,
  "#22c993": 162,
  "#e0683c": 44,
  "#b15cf0": 300,
};
window.ACCENTS = ACCENTS;

const FONTS = {
  grotesk: { display: '"Space Grotesk", sans-serif', body: '"IBM Plex Sans", sans-serif' },
  mono:    { display: '"JetBrains Mono", monospace', body: '"IBM Plex Sans", sans-serif' },
  serif:   { display: '"Fraunces", serif', body: '"IBM Plex Sans", sans-serif' },
};

const THEME_DEFAULTS = { accent: "#5b76f7", theme: "dark", fontPair: "grotesk" };

function loadTheme() {
  try {
    const raw = localStorage.getItem('mcpwp-theme');
    if (raw) return { ...THEME_DEFAULTS, ...JSON.parse(raw) };
  } catch (e) {}
  return { ...THEME_DEFAULTS };
}

function applyTheme(t) {
  const root = document.documentElement;
  root.setAttribute('data-theme', t.theme);
  root.style.setProperty('--accent-h', ACCENTS[t.accent] ?? 264);
  const f = FONTS[t.fontPair] || FONTS.grotesk;
  root.style.setProperty('--font-display', f.display);
  root.style.setProperty('--font-body', f.body);
}

// apply immediately on script load to avoid flash
applyTheme(loadTheme());

function useSiteTheme() {
  const [t, setT] = React.useState(loadTheme);
  React.useEffect(() => { applyTheme(t); try { localStorage.setItem('mcpwp-theme', JSON.stringify(t)); } catch(e){} }, [t]);
  const set = (k, v) => setT((p) => ({ ...p, [k]: v }));
  return [t, set];
}
window.useSiteTheme = useSiteTheme;

const NAV = [
  { label: 'Product', href: 'MCPWP Offer Site.html#tools' },
  { label: 'Docs', href: 'Docs.html' },
  { label: 'Blog', href: 'Blog.html' },
  { label: 'Use cases', href: 'Use Cases.html' },
  { label: 'Pricing', href: 'MCPWP Offer Site.html#pricing' },
  { label: 'Changelog', href: 'Changelog.html' },
];

function SiteNav({ active }) {
  const [scrolled, setScrolled] = React.useState(false);
  const [open, setOpen] = React.useState(false);
  React.useEffect(() => {
    const on = () => setScrolled(window.scrollY > 12);
    on();
    window.addEventListener('scroll', on, { passive: true });
    return () => window.removeEventListener('scroll', on);
  }, []);
  return (
    <nav className={"nav" + (scrolled ? " scrolled" : "")}>
      <div className="nav-inner">
        <a className="brand" href="MCPWP Offer Site.html"><span className="brand-mark">M</span> MCPWP</a>
        <div className="nav-links">
          {NAV.map((l) => (
            <a key={l.label} href={l.href} className={active === l.label ? 'nav-active' : ''}>{l.label}</a>
          ))}
        </div>
        <div className="nav-cta">
          <a className="nav-gh" href="https://github.com/Mumega-com/mcpwp" target="_blank" rel="noreferrer">★ GitHub</a>
          <a className="btn btn-primary" href="MCPWP Offer Site.html#pricing">See current plans</a>
          <button className="nav-burger" aria-label="Menu" onClick={() => setOpen(!open)}>
            <span /><span /><span />
          </button>
        </div>
      </div>
      {open && (
        <div className="nav-mobile">
          {NAV.map((l) => <a key={l.label} href={l.href}>{l.label}</a>)}
          <a href="https://github.com/Mumega-com/mcpwp" target="_blank" rel="noreferrer">GitHub ↗</a>
        </div>
      )}
    </nav>
  );
}
window.SiteNav = SiteNav;

function SiteFooter() {
  return (
    <footer className="footer">
      <div className="wrap">
        <div className="footer-inner">
          <div>
            <a className="brand" href="MCPWP Offer Site.html"><span className="brand-mark">M</span> MCPWP</a>
            <p className="footer-tag">AI operations for WordPress through MCP. Built for agencies, builders, and site operators.</p>
          </div>
          <div className="footer-cols">
            <div className="footer-col">
              <h4>Product</h4>
              <a href="MCPWP Offer Site.html#tools">Tools</a>
              <a href="MCPWP Offer Site.html#blueprints">Blueprints</a>
              <a href="MCPWP Offer Site.html#pricing">Pricing</a>
              <a href="Changelog.html">Changelog</a>
            </div>
            <div className="footer-col">
              <h4>Learn</h4>
              <a href="Docs.html">Documentation</a>
              <a href="Blog.html">Blog</a>
              <a href="Resources.html">Resources</a>
              <a href="Use Cases.html">Use cases</a>
            </div>
            <div className="footer-col">
              <h4>Connect</h4>
              <a href="https://github.com/Mumega-com/mcpwp" target="_blank" rel="noreferrer">GitHub</a>
              <a href="https://mcpwp.net" target="_blank" rel="noreferrer">mcpwp.net</a>
              <a href="https://github.com/Mumega-com/mcpwp" target="_blank" rel="noreferrer">MCPWP repository</a>
              <a href="https://mumega.com" target="_blank" rel="noreferrer">Mumega</a>
            </div>
          </div>
        </div>
        <div className="footer-bottom">
          <span>© 2026 Mumega · GPL v2 or later</span>
          <span className="mono">Built for the MCP era</span>
        </div>
      </div>
    </footer>
  );
}
window.SiteFooter = SiteFooter;

function SiteTweaks({ t, set }) {
  return (
    <TweaksPanel>
      <TweakSection label="Theme" />
      <TweakToggle label="Dark mode" value={t.theme === 'dark'} onChange={(v) => set('theme', v ? 'dark' : 'light')} />
      <TweakColor label="Accent" value={t.accent} options={Object.keys(ACCENTS)} onChange={(v) => set('accent', v)} />
      <TweakSection label="Typography" />
      <TweakRadio label="Headings" value={t.fontPair} options={['grotesk','mono','serif']} onChange={(v) => set('fontPair', v)} />
    </TweaksPanel>
  );
}
window.SiteTweaks = SiteTweaks;
