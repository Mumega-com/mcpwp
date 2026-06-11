// #544: UUID site_id + no silent overwrite
// site_id is now a UUID v4 generated server-side.
// The slug/label is display-only and does NOT affect lookup.
// addSite refuses to overwrite an existing site_id unless opts.update === true;
// callers that previously relied on slug-based collision now get a 409.

import type { Env, SiteEntry } from './types';

export async function getSites(agencyId: string, env: Env): Promise<SiteEntry[]> {
  const raw = await env.AGENCY_KV.get(`agency:sites:${agencyId}`);
  if (!raw) return [];
  try {
    return JSON.parse(raw) as SiteEntry[];
  } catch {
    return [];
  }
}

export type AddSiteResult =
  | { ok: true }
  | { ok: false; conflict: true; existing: SiteEntry };

// KV has no compare-and-swap; concurrent addSite calls for the same agency
// could race and overwrite each other. Acceptable for low-frequency site
// registration. Migrate to Durable Objects if concurrent writes become a concern.
export async function addSite(
  agencyId: string,
  entry: SiteEntry,
  env: Env,
  opts: { update?: boolean } = {}
): Promise<AddSiteResult> {
  const sites = await getSites(agencyId, env);
  const idx = sites.findIndex((s) => s.site_id === entry.site_id);

  if (idx >= 0) {
    if (!opts.update) {
      // #544: refuse silent overwrite
      return { ok: false, conflict: true, existing: sites[idx] };
    }
    sites[idx] = entry;
  } else {
    sites.push(entry);
  }

  await env.AGENCY_KV.put(`agency:sites:${agencyId}`, JSON.stringify(sites));
  return { ok: true };
}

export async function removeSite(agencyId: string, siteId: string, env: Env): Promise<boolean> {
  const sites = await getSites(agencyId, env);
  const filtered = sites.filter((s) => s.site_id !== siteId);
  if (filtered.length === sites.length) return false;
  await env.AGENCY_KV.put(`agency:sites:${agencyId}`, JSON.stringify(filtered));
  return true;
}
