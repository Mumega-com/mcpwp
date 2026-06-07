<wizard-report>
# PostHog post-wizard report

The wizard has completed a deep integration of PostHog analytics into the MCPWP WordPress plugin admin interface. PostHog is initialized via the `posthog-js` async snippet in `spai-admin.js`, which is loaded on the Setup, Library, Settings, and Integrations admin pages. The Integrations page reads its token from the `spaiIntegrations` localized object (also injected with the PostHog config). The Tools admin page receives a dedicated inline PostHog init script injected via `wp_add_inline_script`. All PostHog configuration (public token and host) is passed from PHP to JavaScript via `wp_localize_script`, with PHP-constant overrides supported (`SPAI_POSTHOG_TOKEN`, `SPAI_POSTHOG_HOST`). Environment variables are stored in `.env`.

| Event | Description | File |
|---|---|---|
| `api_key_copied` | User copied an API key (master or scoped) from the setup page | `site-pilot-ai/admin/js/spai-admin.js` |
| `welcome_banner_dismissed` | User dismissed the first-time welcome banner after initial activation | `site-pilot-ai/admin/js/spai-admin.js` |
| `connection_tested` | User clicked Test Connection — captures `result: success/failure` | `site-pilot-ai/admin/js/spai-admin.js` |
| `upgrade_link_clicked` | User clicked the Manage pricing or Upgrade link — conversion intent | `site-pilot-ai/admin/js/spai-admin.js` |
| `ai_client_tab_switched` | User switched between AI client tabs (Claude Code, Desktop, Cursor, Windsurf) — captures `client` | `site-pilot-ai/admin/partials/spai-setup-display.php` |
| `scoped_key_created` | User submitted the form to create a role-based API key — captures `role` | `site-pilot-ai/admin/partials/spai-setup-display.php` |
| `scoped_key_revoked` | User revoked an active scoped API key | `site-pilot-ai/admin/partials/spai-setup-display.php` |
| `integration_saved` | User saved an AI integration — captures `provider` | `site-pilot-ai/admin/partials/spai-integrations-display.php` |
| `integration_removed` | User removed a configured AI integration — captures `provider` | `site-pilot-ai/admin/partials/spai-integrations-display.php` |
| `tool_category_toggled` | User enabled or disabled an MCP tool category — captures `category` and `enabled` | `site-pilot-ai/admin/partials/spai-tools-display.php` |

## Next steps

We've built some insights and a dashboard for you to keep an eye on user behavior, based on the events we just instrumented:

- [Analytics basics dashboard](/dashboard/1618608)
- [Key Admin Actions Over Time](/insights/P2MnPstF) — trend of API key copies, connection tests, and integration saves
- [Upgrade Intent Clicks](/insights/UrhE4oNN) — daily unique users clicking pricing/upgrade links
- [Integration Adoption by Provider](/insights/nzcOtHeX) — which integrations (OpenAI, Gemini, Figma, etc.) users configure most
- [AI Client Tab Preference](/insights/gcuCRsgM) — which AI client (Claude Code, Desktop, Cursor, Windsurf) users select most
- [Connection Test Success Rate](/insights/nOob0fXN) — success vs failure rate of connection tests over time

### Agent skill

We've left an agent skill folder in your project. You can use this context for further agent development when using Claude Code. This will help ensure the model provides the most up-to-date approaches for integrating PostHog.

</wizard-report>
