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

export async function addSite(agencyId: string, entry: SiteEntry, env: Env): Promise<void> {
  const sites = await getSites(agencyId, env);
  const idx = sites.findIndex((s) => s.site_id === entry.site_id);
  if (idx >= 0) {
    sites[idx] = entry;
  } else {
    sites.push(entry);
  }
  await env.AGENCY_KV.put(`agency:sites:${agencyId}`, JSON.stringify(sites));
}

export async function removeSite(agencyId: string, siteId: string, env: Env): Promise<boolean> {
  const sites = await getSites(agencyId, env);
  const filtered = sites.filter((s) => s.site_id !== siteId);
  if (filtered.length === sites.length) return false;
  await env.AGENCY_KV.put(`agency:sites:${agencyId}`, JSON.stringify(filtered));
  return true;
}
