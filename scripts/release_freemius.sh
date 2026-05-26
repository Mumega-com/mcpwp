#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

usage() {
	cat <<'EOF'
Usage:
  scripts/release_freemius.sh --version X.Y.Z [options]

Required:
  --version X.Y.Z                Release version for the plugin

Options:
  --token TOKEN                  Freemius bearer token (or FREEMIUS_BEARER_TOKEN)
  --product-id ID                Freemius product id (default: 23824 or FREEMIUS_PRODUCT_ID)
  --release-mode MODE            release mode for tag update (default: released).
                                Allowed: released | pending | beta | all.
                                Alias: unreleased -> pending.
  --upload-premium-zip           Also upload a premium zip (legacy Freemius setup).
                                Default is single-plugin distribution (no premium zip).
  --skip-bump                    Do not edit plugin/readme/changelog versions
  --dry-run                      Build and print actions; skip Freemius API calls
  --keep-zips                    Keep generated zip files in repo root
  -h, --help                     Show help

Examples:
  FREEMIUS_BEARER_TOKEN=... scripts/release_freemius.sh --version 1.0.23
  scripts/release_freemius.sh --version 1.0.23 --token ... --dry-run
EOF
}

require_cmd() {
	command -v "$1" >/dev/null 2>&1 || {
		echo "Missing required command: $1" >&2
		exit 1
	}
}

replace_or_fail() {
	local file="$1"
	local pattern="$2"
	local replacement="$3"
	python3 - "$file" "$pattern" "$replacement" <<'PY'
from pathlib import Path
import re
import sys

path = Path(sys.argv[1])
pattern = sys.argv[2]
replacement = sys.argv[3]
content = path.read_text(encoding="utf-8")
updated, count = re.subn(pattern, replacement, content, count=1, flags=re.MULTILINE)
if count == 0:
    print(f"Pattern not found in {path}: {pattern}", file=sys.stderr)
    sys.exit(1)
path.write_text(updated, encoding="utf-8")
PY
}

VERSION=""
TOKEN="${FREEMIUS_BEARER_TOKEN:-}"
PRODUCT_ID="${FREEMIUS_PRODUCT_ID:-23824}"
RELEASE_MODE="released"
UPLOAD_PREMIUM_ZIP=0
SKIP_BUMP=0
DRY_RUN=0
KEEP_ZIPS=0

while [[ $# -gt 0 ]]; do
	case "$1" in
	--version)
		VERSION="${2:-}"
		shift 2
		;;
	--token)
		TOKEN="${2:-}"
		shift 2
		;;
	--product-id)
		PRODUCT_ID="${2:-}"
		shift 2
		;;
	--release-mode)
		RELEASE_MODE="${2:-}"
		shift 2
		;;
	--upload-premium-zip)
		UPLOAD_PREMIUM_ZIP=1
		shift
		;;
	--skip-bump)
		SKIP_BUMP=1
		shift
		;;
	--dry-run)
		DRY_RUN=1
		shift
		;;
	--keep-zips)
		KEEP_ZIPS=1
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

if [[ -z "$VERSION" ]]; then
	echo "--version is required" >&2
	usage
	exit 1
fi

if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
	echo "Invalid version: $VERSION (expected X.Y.Z)" >&2
	exit 1
fi

if [[ "$RELEASE_MODE" == "unreleased" ]]; then
	RELEASE_MODE="pending"
fi

if [[ ! "$RELEASE_MODE" =~ ^(released|pending|beta|all)$ ]]; then
	echo "Invalid --release-mode: $RELEASE_MODE (expected released|pending|beta|all)" >&2
	exit 1
fi

require_cmd zip
require_cmd curl
require_cmd grep
require_cmd python3

if [[ "$DRY_RUN" -eq 0 && -z "$TOKEN" ]]; then
	echo "Freemius bearer token is required (--token or FREEMIUS_BEARER_TOKEN)" >&2
	exit 1
fi

PLUGIN_MAIN_FILE="site-pilot-ai/site-pilot-ai.php"
PLUGIN_README_FILE="site-pilot-ai/readme.txt"
PLUGIN_CHANGELOG_FILE="site-pilot-ai/CHANGELOG.md"

if [[ "$SKIP_BUMP" -eq 0 ]]; then
	echo "Bumping plugin versions to $VERSION"

	replace_or_fail "$PLUGIN_MAIN_FILE" "^ \\* Version:\\s+.*$" " * Version:           $VERSION"
	replace_or_fail "$PLUGIN_MAIN_FILE" "^define\( 'SPAI_VERSION', '[^']+' \);$" "define( 'SPAI_VERSION', '$VERSION' );"
	replace_or_fail "$PLUGIN_README_FILE" "^Stable tag: .*$" "Stable tag: $VERSION"

	if ! grep -q "^## \[$VERSION\]" "$PLUGIN_CHANGELOG_FILE"; then
		DATE_TODAY="$(date +%Y-%m-%d)"
		python3 - "$PLUGIN_CHANGELOG_FILE" "$VERSION" "$DATE_TODAY" <<'PY'
from pathlib import Path
import re
import sys

path = Path(sys.argv[1])
version = sys.argv[2]
date_today = sys.argv[3]
content = path.read_text(encoding="utf-8")
entry = f"## [{version}] - {date_today}\n\n### Changed\n- Release automation update.\n\n"
updated, count = re.subn(r"^## \[", entry + "## [", content, count=1, flags=re.MULTILINE)
if count == 0:
    print(f"Could not find changelog insertion point in {path}", file=sys.stderr)
    sys.exit(1)
path.write_text(updated, encoding="utf-8")
PY
	fi
fi

FREEMIUS_ZIP="site-pilot-ai/scripts/site-pilot-ai-freemius-$VERSION.zip"
PREMIUM_ZIP="site-pilot-ai-premium-$VERSION.zip"

echo "Building Freemius paid/trial zip package"
bash site-pilot-ai/scripts/build-freemius.sh --version "$VERSION"

if [[ "$UPLOAD_PREMIUM_ZIP" -eq 1 ]]; then
	echo "Building legacy premium zip package"
	(
		BUILD_DIR="$(mktemp -d)"
		cp -R "site-pilot-ai" "$BUILD_DIR/site-pilot-ai-premium"
		cd "$BUILD_DIR"
		zip -qr "$ROOT_DIR/$PREMIUM_ZIP" "site-pilot-ai-premium"
		rm -rf "$BUILD_DIR" || true
	)
fi

if [[ "$DRY_RUN" -eq 1 ]]; then
	echo "Dry run complete."
	echo "- Freemius zip: $FREEMIUS_ZIP"
	if [[ "$UPLOAD_PREMIUM_ZIP" -eq 1 ]]; then
		echo "- Legacy premium zip: $PREMIUM_ZIP"
	fi
	echo "- API calls skipped"
	exit 0
fi

echo "Uploading $VERSION to Freemius product $PRODUCT_ID"
CREATE_RESPONSE="$(
	curl -sS -X POST "https://api.freemius.com/v1/products/$PRODUCT_ID/tags.json" \
		-H "Authorization: Bearer $TOKEN" \
		-F "file=@$FREEMIUS_ZIP;type=application/zip" \
		-F "add_contributor_to_rel=false"
)"

TAG_ID="$(printf '%s' "$CREATE_RESPONSE" | python3 -c 'import json,sys
try:
    data=json.load(sys.stdin)
except Exception:
    print("")
    sys.exit(0)
print(data.get("id",""))')"

if [[ -z "$TAG_ID" ]]; then
	echo "Freemius upload failed:"
	printf '%s\n' "$CREATE_RESPONSE"
	echo "Tip: If error is duplicate_plugin_version, bump --version and retry."
	exit 1
fi

echo "Uploaded tag id: $TAG_ID"

if [[ "$UPLOAD_PREMIUM_ZIP" -eq 1 ]]; then
	echo "Uploading premium package for tag $TAG_ID"
	PREMIUM_RESPONSE="$(
		curl -sS -X PUT "https://api.freemius.com/v1/products/$PRODUCT_ID/tags/$TAG_ID.json" \
			-H "Authorization: Bearer $TOKEN" \
			-F "file=@$PREMIUM_ZIP;type=application/zip" \
			-F "is_premium=true"
	)"

	# Best-effort sanity check.
	PREMIUM_OK="$(printf '%s' "$PREMIUM_RESPONSE" | python3 -c 'import json,sys
try:
    data=json.load(sys.stdin)
except Exception:
    print("")
    sys.exit(0)
print(data.get("version",""))')"
	if [[ "$PREMIUM_OK" != "$VERSION" ]]; then
		echo "Premium upload response:"
		printf '%s\n' "$PREMIUM_RESPONSE"
		echo "Premium upload failed or returned unexpected version" >&2
		exit 1
	fi
else
	echo "Skipping premium zip upload (single-plugin distribution)"
fi

echo "Setting release_mode=$RELEASE_MODE"
RELEASE_RESPONSE="$(
	curl -sS -X PUT "https://api.freemius.com/v1/products/$PRODUCT_ID/tags/$TAG_ID.json" \
		-H "Authorization: Bearer $TOKEN" \
		-H "Content-Type: application/x-www-form-urlencoded" \
		--data "release_mode=$RELEASE_MODE"
)"

FINAL_MODE="$(printf '%s' "$RELEASE_RESPONSE" | python3 -c 'import json,sys
try:
    data=json.load(sys.stdin)
except Exception:
    print("")
    sys.exit(0)
print(data.get("release_mode",""))')"

if [[ "$FINAL_MODE" != "$RELEASE_MODE" ]]; then
	echo "Release mode update response:"
	printf '%s\n' "$RELEASE_RESPONSE"
	echo "Expected release_mode=$RELEASE_MODE but got '$FINAL_MODE'" >&2
	exit 1
fi

echo "Freemius release successful"
echo "- product_id:   $PRODUCT_ID"
echo "- version:      $VERSION"
echo "- tag_id:       $TAG_ID"
echo "- release_mode: $FINAL_MODE"

if [[ "$KEEP_ZIPS" -eq 0 ]]; then
	rm -f "$FREEMIUS_ZIP"
	if [[ "$UPLOAD_PREMIUM_ZIP" -eq 1 ]]; then
		rm -f "$PREMIUM_ZIP"
	fi
	echo "Cleaned local zip artifacts"
else
	echo "Kept local zip artifacts"
fi
