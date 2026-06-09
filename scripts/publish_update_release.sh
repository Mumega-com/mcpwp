#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_DIR="$ROOT_DIR/mcpwp"
PLUGIN_MAIN="$PLUGIN_DIR/mcpwp.php"
README_FILE="$PLUGIN_DIR/readme.txt"
MANIFEST_FILE="$ROOT_DIR/version.json"
BUILD_SCRIPT="$PLUGIN_DIR/scripts/build-selfhosted.sh"
STATIC_DIR="/var/www/mcp-updates"
STATIC_ZIP="$STATIC_DIR/mcpwp-latest.zip"
STATIC_MANIFEST="$STATIC_DIR/version.json"
WORKER_FILE="${MCPWP_WORKER_FILE:-$HOME/projects/sitepilotai/mcpwp-updates-worker/src/index.js}"

BUILD=0
DEPLOY_WORKER=0
VERIFY_ONLY=0

usage() {
	cat <<'EOF'
Usage:
  scripts/publish_update_release.sh [options]

Options:
  --build          Build the ZIP before publishing
  --deploy-worker  Also sync the legacy worker source file (optional)
  --verify-only    Run consistency and live artifact checks without publishing
  -h, --help       Show help

Notes:
  - The canonical updater source is https://mumega.com/mcp-updates/version.json
  - This channel serves the self-hosted package with the legacy updater included
  - The worker is optional and no longer part of the critical path
EOF
}

require_cmd() {
	command -v "$1" >/dev/null 2>&1 || {
		echo "Missing required command: $1" >&2
		exit 1
	}
}

while [[ $# -gt 0 ]]; do
	case "$1" in
	--build)
		BUILD=1
		shift
		;;
	--deploy-worker)
		DEPLOY_WORKER=1
		shift
		;;
	--verify-only)
		VERIFY_ONLY=1
		shift
		;;
	-h | --help)
		usage
		exit 0
		;;
	*)
		echo "Unknown argument: $1" >&2
		usage
		exit 1
		;;
	esac
done

require_cmd python3
require_cmd curl
require_cmd unzip

PLUGIN_VERSION="$(grep -m1 "Version:" "$PLUGIN_MAIN" | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"
DEFINE_VERSION="$(grep -E "^define\( 'MCPWP_VERSION'" "$PLUGIN_MAIN" | sed -E "s/.*'([0-9.]+)'.*/\1/")"
README_VERSION="$(grep -m1 '^Stable tag:' "$README_FILE" | sed 's/Stable tag:[[:space:]]*//')"
MANIFEST_VERSION="$(python3 - "$MANIFEST_FILE" <<'PY'
import json, sys
with open(sys.argv[1], 'r', encoding='utf-8') as fh:
    data = json.load(fh)
print(data["version"])
PY
)"

if [[ "$PLUGIN_VERSION" != "$DEFINE_VERSION" || "$PLUGIN_VERSION" != "$README_VERSION" || "$PLUGIN_VERSION" != "$MANIFEST_VERSION" ]]; then
	echo "Version mismatch detected:" >&2
	echo "  plugin header: $PLUGIN_VERSION" >&2
	echo "  MCPWP_VERSION:  $DEFINE_VERSION" >&2
	echo "  readme tag:    $README_VERSION" >&2
	echo "  version.json:  $MANIFEST_VERSION" >&2
	exit 1
fi

VERSION="$PLUGIN_VERSION"
ZIP_PATH="$PLUGIN_DIR/scripts/mcpwp-selfhosted-$VERSION.zip"

if [[ "$BUILD" -eq 1 ]]; then
	bash "$BUILD_SCRIPT" --version "$VERSION"
fi

if [[ ! -f "$ZIP_PATH" ]]; then
	echo "Missing ZIP artifact: $ZIP_PATH" >&2
	echo "Run with --build or build it first." >&2
	exit 1
fi

ZIP_ROOT="$(unzip -Z1 "$ZIP_PATH" | sed -n '1p' | cut -d/ -f1)"
ZIP_VERSION="$(unzip -p "$ZIP_PATH" "$ZIP_ROOT/mcpwp.php" | grep -m1 'Version:' | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"
if [[ "$ZIP_VERSION" != "$VERSION" ]]; then
	echo "ZIP version mismatch: expected $VERSION, got $ZIP_VERSION" >&2
	exit 1
fi

# grep -q closes the pipe early, causing SIGPIPE (141) under pipefail — use grep -c instead.
UPDATER_COUNT=$(unzip -Z1 "$ZIP_PATH" | grep -c 'includes/class-mcpwp-updater.php' || true)
if [[ "$UPDATER_COUNT" -eq 0 ]]; then
	echo "Updater missing from canonical self-hosted ZIP: $ZIP_PATH" >&2
	exit 1
fi

if [[ "$VERIFY_ONLY" -eq 0 ]]; then
	sudo -n cp "$ZIP_PATH" "$STATIC_ZIP"
	sudo -n cp "$MANIFEST_FILE" "$STATIC_MANIFEST"
	# Keep mcpwp-latest.zip symlink pointing at the canonical zip (resolves #339).
	sudo -n ln -sf "$STATIC_ZIP" "$STATIC_DIR/mcpwp-latest.zip"

	# Upload to Cloudflare R2 — CF Worker serves from R2, not from nginx alias.
	# --remote targets production R2; without it wrangler writes to local miniflare.
	WORKER_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)/mcpwp-updates-worker"
	if [[ -d "$WORKER_DIR" ]] && command -v npx >/dev/null 2>&1; then
		CF_TOKEN="${CLOUDFLARE_API_TOKEN:-$(grep CLOUDFLARE_API_TOKEN ~/.env.secrets 2>/dev/null | cut -d= -f2)}"
		if [[ -n "$CF_TOKEN" ]]; then
			CLOUDFLARE_API_TOKEN="$CF_TOKEN" npx --prefix "$WORKER_DIR" wrangler r2 object put mcpwp-updates/version.json \
				--file="$STATIC_MANIFEST" --content-type="application/json" --remote 2>&1 | grep -v "^$" || true
			CLOUDFLARE_API_TOKEN="$CF_TOKEN" npx --prefix "$WORKER_DIR" wrangler r2 object put mcpwp-updates/mcpwp-latest.zip \
				--file="$STATIC_ZIP" --content-type="application/zip" --remote 2>&1 | grep -v "^$" || true
			# The manifest's download_url is mcpwp-latest.zip. The CF Worker serves from R2,
			# not the nginx symlink above, so this key MUST exist in R2 or downloads 404.
			CLOUDFLARE_API_TOKEN="$CF_TOKEN" npx --prefix "$WORKER_DIR" wrangler r2 object put mcpwp-updates/mcpwp-latest.zip \
				--file="$STATIC_ZIP" --content-type="application/zip" --remote 2>&1 | grep -v "^$" || true
			echo "  R2 upload: version.json + mcpwp-latest.zip + mcpwp-latest.zip → mcpwp-updates bucket"
		else
			echo "Note: CLOUDFLARE_API_TOKEN not set — skipping R2 upload (CF Worker will serve stale version)" >&2
		fi
	fi
fi

if [[ "$DEPLOY_WORKER" -eq 1 ]]; then
	if [[ ! -f "$WORKER_FILE" ]]; then
		echo "Worker file not found: $WORKER_FILE" >&2
		exit 1
	fi

	python3 - "$MANIFEST_FILE" "$WORKER_FILE" <<'PY'
import json, pathlib, re, sys
manifest_path = pathlib.Path(sys.argv[1])
worker_path = pathlib.Path(sys.argv[2])
manifest = json.loads(manifest_path.read_text(encoding='utf-8'))
worker = worker_path.read_text(encoding='utf-8')
replacement = "const VERSION_INFO = " + json.dumps(manifest, indent=2) + ";"
updated, count = re.subn(r"const VERSION_INFO = \{.*?\n\};", replacement, worker, flags=re.S)
if count != 1:
    raise SystemExit("Could not update VERSION_INFO in worker source")
worker_path.write_text(updated, encoding='utf-8')
PY
fi

LIVE_STATIC_JSON="$(curl -fsSL https://mumega.com/mcp-updates/version.json)" || true
LIVE_STATIC_VERSION="$(python3 -c 'import json,sys; print(json.load(sys.stdin)["version"])' <<<"$LIVE_STATIC_JSON" 2>/dev/null || echo "unknown")"

if [[ "$LIVE_STATIC_VERSION" != "$VERSION" ]]; then
	echo "Note: live manifest still shows $LIVE_STATIC_VERSION (R2 upload may have failed or CF propagation in progress)" >&2
fi

# Verify the ACTUAL download_url advertised in the manifest resolves — not a
# hardcoded name. The two diverged once (manifest said mcpwp-latest.zip while only
# mcpwp-latest.zip was uploaded to R2), silently 404ing every site's download.
DOWNLOAD_URL="$(python3 -c 'import json,sys; print(json.load(sys.stdin).get("download_url",""))' <<<"$LIVE_STATIC_JSON" 2>/dev/null || echo "")"
if [[ -n "$DOWNLOAD_URL" ]]; then
	ZIP_STATUS="$(curl -o /dev/null -s -w '%{http_code}' "$DOWNLOAD_URL" 2>/dev/null || echo "000")"
	if [[ "$ZIP_STATUS" != "200" ]]; then
		echo "Note: manifest download_url ($DOWNLOAD_URL) returned $ZIP_STATUS (CF cache, or the R2 key does not match download_url)" >&2
	fi
fi

echo "Published Site Pilot AI $VERSION"
echo "  static manifest: https://mumega.com/mcp-updates/version.json"
echo "  static zip:      https://mumega.com/mcp-updates/mcpwp-latest.zip"
if [[ "$DEPLOY_WORKER" -eq 1 ]]; then
	echo "  worker source synced: $WORKER_FILE"
fi
