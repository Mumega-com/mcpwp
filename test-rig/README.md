# MCPWP Local Test Rig

Night-run e2e infrastructure for the MCPWP plugin. Zero production writes.

## Services

| Container         | Port           | Purpose                                      |
|-------------------|----------------|----------------------------------------------|
| wp-rig-wp-dev     | 127.0.0.1:8086 | Dev WP + live bind-mount of mcpwp/ source    |
| wp-rig-wp-upgrade | 127.0.0.1:8087 | Upgrade WP — no mount, real Plugin_Upgrader  |
| wp-rig-channel    | 127.0.0.1:8088 | Local update channel (nginx static files)    |
| wp-rig-db         | (internal)     | MariaDB LTS, 128M innodb buffer pool         |

## Credentials

| Instance    | Admin URL                              | User  | Password |
|-------------|----------------------------------------|-------|----------|
| wp-dev      | http://127.0.0.1:8086/wp-admin/        | admin | admin    |
| wp-upgrade  | http://127.0.0.1:8087/wp-admin/        | admin | admin    |

API keys are stored after provisioning:
- `./mcpwp-dev-key`   — MCPWP v3 key (mcpwp_ prefix) for wp-dev
- `./.spai-upgrade-key` — Legacy 2.8.56 key (spai_ prefix) for wp-upgrade

## Usage

```bash
cd /mnt/HC_Volume_104325311/projects/sitepilotai/wp-rig

# Start all services
bash rig.sh up

# Install WP + plugins + generate keys (idempotent)
bash rig.sh provision

# Check service status + HTTP health
bash rig.sh status

# Stop (volumes kept)
bash rig.sh down

# Nuke everything + fresh start
bash rig.sh reset
```

## WP-CLI shortcuts

```bash
# wp-dev
docker exec wp-rig-wp-dev wp plugin list --allow-root
docker exec wp-rig-wp-dev wp option get mcpwp_version --allow-root

# wp-upgrade
docker exec wp-rig-wp-upgrade wp plugin list --allow-root
docker exec wp-rig-wp-upgrade wp option get spai_version_url --allow-root
```

## Quick API verify

```bash
KEY=$(cat /mnt/HC_Volume_104325311/projects/sitepilotai/wp-rig/.mcpwp-dev-key)

# Site info
curl -s http://127.0.0.1:8086/wp-json/mcpwp/v1/site-info -H "X-API-Key: $KEY" | jq .site_name

# Channel manifest
curl -s http://127.0.0.1:8088/version.json | jq .version
```

## Local channel

The channel at `./channel/` serves `version.json` and plugin zips via nginx.
It is reachable both from the host (http://127.0.0.1:8088/) and from containers
on the rignet network as `http://channel/`.

To seed a new upgrade target (e.g. bridge 3.1):
1. Drop the new zip into `./channel/`
2. Edit `./channel/version.json` — bump `version`, set `download_url` to `http://channel/<new-zip-name>.zip`
3. No container restart needed (nginx serves the directory live).

## Multisite

Multisite is NOT enabled by default to reduce memory overhead.
To enable on wp-dev:
1. Edit `docker-compose.yml` and uncomment the 3 `define(...)` lines in `WORDPRESS_CONFIG_EXTRA`
2. Run `docker compose up -d wp-dev` to restart with new env
3. Run `docker exec wp-rig-wp-dev wp core multisite-convert --allow-root`

## Cloudflared tunnel (for external Mac tester Dara)

NOT configured — requires an auth token. When you have one, run:

```bash
# Install cloudflared on this server if not present
# Then:
cloudflared tunnel --url http://127.0.0.1:8086
```

This will print a temporary *.trycloudflare.com URL. Give that URL to Dara.
For persistent tunnels, create a named tunnel via `cloudflared tunnel create mcpwp-dev`
and configure `~/.cloudflared/config.yml`.

## Volume locations

All data lands on the second disk under this directory:
```
./volumes/db/              — MariaDB data
./volumes/dev-uploads/     — wp-dev media uploads
./volumes/upgrade-uploads/ — wp-upgrade media uploads
./volumes/upgrade-plugins/ — wp-upgrade plugins (written by Plugin_Upgrader)
```

## Key option names by plugin version

| Plugin   | Version | API key option    | Scoped keys option | Update URL option  |
|----------|---------|-------------------|--------------------|--------------------|
| mcpwp    | 3.x     | mcpwp_api_key     | mcpwp_api_keys     | mcpwp_version_url  |
| site-pilot-ai | 2.8.x | spai_api_key  | spai_api_keys      | spai_version_url   |
