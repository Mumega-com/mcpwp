# Freemius Paid Plan Setup
**Date:** 2026-05-30  
**Product ID:** 23824  
**Dashboard:** https://dashboard.freemius.com/developer/products/23824/

---

## Status

- Plugin integration: already wired (`includes/freemius-init.php`)
- `has_paid_plans: true` — Freemius checkout will appear
- 14-day trial configured, payment required
- **Pending:** Create the actual plan pricing in Freemius dashboard

---

## Plans to Create

### Plan 1 — Pro
| Field | Value |
|-------|-------|
| Name | Pro |
| Billing | Annual |
| Price | $79/year |
| Sites allowed | 1 |
| Trial | 14 days (already configured in SDK) |
| Features | SEO integrations, WooCommerce, Elementor Pro, design references, archetypes, agent workflows, search performance, AI providers |

### Plan 2 — Agency *(hold — dashboard not built yet)*
| Field | Value |
|-------|-------|
| Name | Agency |
| Billing | Annual |
| Price | $249/year |
| Sites allowed | Unlimited |
| Status | Hold until agency dashboard ships |

---

## Dashboard Steps

1. Go to https://dashboard.freemius.com/developer/products/23824/
2. Navigate to **Pricing** → **Plans**
3. Click **Add Plan**
4. Fill in Pro plan fields above
5. Under **Pricing**, add Annual option at $79
6. Under **Licenses**, set "Sites per license" to 1
7. Save and test checkout flow

### Verify upgrade path
1. Install plugin on test site (free version)
2. In WP Admin → MCPWP, click **Upgrade to Pro**
3. Confirm Freemius checkout modal appears
4. Complete trial activation (no charge for 14 days)
5. Verify Pro features gate lifts after license activation

---

## Feature Gating (Already in Code)

The plugin gates Pro features via `mcpwp_fs()->is_paying()` and plan checks. Verify these work after plan creation:

```php
// In includes/core/class-mcpwp-core.php
if ( mcpwp_fs()->is_paying() ) {
    // Pro features
}
```

Pro features currently gated:
- SEO audit tools (Yoast, RankMath, AIOSEO, SEOPress)
- WooCommerce tools
- Elementor Pro templates + theme builder
- Design references (Figma)
- Archetypes and playbooks
- Search performance data
- AI provider integrations

---

## Post-Launch Checklist

- [ ] Pro plan created at $79/year in Freemius dashboard
- [ ] Trial flow tested end-to-end on staging site
- [ ] Upgrade modal appears correctly in WP Admin
- [ ] Pro features activate after trial/payment
- [ ] Freemius email sequences configured (welcome, trial expiry, renewal)
- [ ] Agency plan added when agency dashboard ships
