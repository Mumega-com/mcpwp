/* global React */
// hero-demo.jsx — animated "type a prompt → watch the AI build a page" demo.
// Exports: window.HeroDemo

const { useState, useEffect, useRef } = React;

const PROMPT = 'Build a landing page for my yoga studio — hero, 3 feature cards, and a pricing section.';

// the tool calls the "agent" streams
const TOOL_STEPS = [
  { tool: 'wp_build_page', arg: 'title: "Serenity Yoga"', ms: 700 },
  { tool: 'blueprint:hero', arg: 'heading, bg image, CTA', ms: 620 },
  { tool: 'blueprint:features', arg: 'columns: 3', ms: 620 },
  { tool: 'blueprint:pricing', arg: '3 plans', ms: 620 },
  { tool: 'wp_regenerate_css', arg: 'purge cache', ms: 520 },
];

// the page blocks that animate in, one per relevant tool step
const BLOCKS = [
  { kind: 'hero' },
  { kind: 'features' },
  { kind: 'pricing' },
];

function useInterval(cb, delay, on) {
  const ref = useRef(cb);
  ref.current = cb;
  useEffect(() => {
    if (!on) return;
    const id = setInterval(() => ref.current(), delay);
    return () => clearInterval(id);
  }, [delay, on]);
}

function HeroDemo() {
  // phases: typing -> sending -> tools -> building -> done -> (restart)
  const [typed, setTyped] = useState(0);
  const [phase, setPhase] = useState('typing');
  const [toolIdx, setToolIdx] = useState(-1);
  const [blocksShown, setBlocksShown] = useState(0);
  const [runKey, setRunKey] = useState(0);

  // typing effect
  useInterval(() => {
    setTyped((n) => {
      if (n >= PROMPT.length) { setPhase('sending'); return n; }
      return n + 1;
    });
  }, 26, phase === 'typing');

  // pause after typed, then start tools
  useEffect(() => {
    if (phase !== 'sending') return;
    const t = setTimeout(() => { setPhase('tools'); setToolIdx(0); }, 650);
    return () => clearTimeout(t);
  }, [phase]);

  // step through tool calls; reveal blocks as relevant tools fire
  useEffect(() => {
    if (phase !== 'tools' || toolIdx < 0) return;
    if (toolIdx >= TOOL_STEPS.length) {
      const t = setTimeout(() => setPhase('done'), 600);
      return () => clearTimeout(t);
    }
    const step = TOOL_STEPS[toolIdx];
    const t = setTimeout(() => {
      // tool indices 1,2,3 correspond to the 3 page blocks
      if (toolIdx >= 1 && toolIdx <= 3) setBlocksShown((b) => Math.max(b, toolIdx));
      setToolIdx((i) => i + 1);
    }, step.ms);
    return () => clearTimeout(t);
  }, [phase, toolIdx]);

  // auto restart
  useEffect(() => {
    if (phase !== 'done') return;
    const t = setTimeout(() => {
      setTyped(0); setToolIdx(-1); setBlocksShown(0);
      setPhase('typing'); setRunKey((k) => k + 1);
    }, 5200);
    return () => clearTimeout(t);
  }, [phase]);

  const promptText = PROMPT.slice(0, typed);
  const showCursor = phase === 'typing';

  return (
    <div className="demo">
      {/* LEFT — the conversation / agent */}
      <div className="demo-chat">
        <div className="demo-bar">
          <span className="tl tl-r" /><span className="tl tl-y" /><span className="tl tl-g" />
          <span className="demo-bar-title mono">claude — mcpwp</span>
        </div>

        <div className="demo-chat-body">
          <div className="msg msg-user">
            <span className="msg-role mono">you</span>
            <p>{promptText}<span className={"caret" + (showCursor ? " on" : "")} /></p>
          </div>

          {phase !== 'typing' && phase !== 'sending' && (
            <div className="msg msg-ai">
              <span className="msg-role mono accent">mcpwp</span>
              <div className="tool-stream">
                {TOOL_STEPS.map((s, i) => {
                  const active = i === toolIdx;
                  const done = i < toolIdx || phase === 'done';
                  if (i > toolIdx && phase !== 'done') return null;
                  return (
                    <div className={"tool-line" + (done ? " done" : "") + (active ? " active" : "")} key={i}>
                      <span className="tool-mark mono">{done ? '✓' : '→'}</span>
                      <span className="tool-name mono">{s.tool}</span>
                      <span className="tool-arg mono">{s.arg}</span>
                    </div>
                  );
                })}
                {phase === 'done' && (
                  <div className="tool-final">
                    <span className="mono">Page published</span>
                    <span className="tool-final-link mono">/serenity-yoga ↗</span>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* RIGHT — the page being built */}
      <div className="demo-page" key={runKey}>
        <div className="demo-bar demo-bar-page">
          <span className="url mono">serenityyoga.com</span>
          <span className={"build-tag mono" + (phase === 'done' ? ' ok' : '')}>
            {phase === 'done' ? 'live' : phase === 'typing' || phase === 'sending' ? 'idle' : 'building…'}
          </span>
        </div>
        <div className="demo-page-body">
          {blocksShown < 1 && phase !== 'done' && (
            <div className="page-empty mono">awaiting build…</div>
          )}

          <div className={"pb pb-hero" + (blocksShown >= 1 ? ' show' : '')}>
            <div className="pb-img" />
            <div className="pb-hero-txt">
              <span className="pb-h1" />
              <span className="pb-sub" />
              <span className="pb-cta" />
            </div>
          </div>

          <div className={"pb pb-features" + (blocksShown >= 2 ? ' show' : '')}>
            {[0,1,2].map(i => (
              <div className="pb-feat" key={i} style={{ transitionDelay: (i*80)+'ms' }}>
                <span className="pb-ic" /><span className="pb-l1" /><span className="pb-l2" />
              </div>
            ))}
          </div>

          <div className={"pb pb-pricing" + (blocksShown >= 3 ? ' show' : '')}>
            {[0,1,2].map(i => (
              <div className={"pb-price" + (i===1?' hi':'')} key={i} style={{ transitionDelay: (i*80)+'ms' }}>
                <span className="pb-tier" /><span className="pb-amt" />
                <span className="pb-row" /><span className="pb-row" /><span className="pb-row" />
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

window.HeroDemo = HeroDemo;
