# MCPWP Website GTM Artifacts

This folder preserves the website go-to-market work applied to `https://mcpwp.net` through the WordPress MCP/Elementor workflow.

Contents:

- `offer-site/elementor/`: reproducible Node scripts used to deploy and verify Elementor pages, download flow, docs flow, header logo, blog/design cleanup, and site audits.
- `offer-site/blog/`: scripts used for commercial blog drafting/publishing workflows.
- `offer-site/assets/`: MCPWP logo/sign SVG assets used by the live header and brand work.
- `offer-site/screenshots/`: design/reference screenshots from the offer-site prototype.
- `offer-site/*.jsx`, `*.html`, `*.css`, `*.md`: Claude/offer-site prototype files and SEO/launch planning material.

Notes:

- The live WordPress site was updated through MCP tools, not by serving files directly from this repository.
- These artifacts are committed so the work can be reviewed, rerun, or adapted without relying on local-only files.
- The scripts expect a local MCP config outside this folder and should not commit API keys.
