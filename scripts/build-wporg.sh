#!/usr/bin/env bash
# Build WP.org free zip — strips pro/, dev files, tests, docs.
# Output: /tmp/mcpwp-wporg-<version>.zip
# Usage: bash scripts/build-wporg.sh [--dry-run]

set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)/mcpwp"
PLUGIN_SLUG="mcpwp"
VERSION="$(grep "^ \* Version:" "$PLUGIN_DIR/mcpwp.php" | awk '{print $3}')"
BUILD_DIR="/tmp/${PLUGIN_SLUG}-wporg-build"
OUT_ZIP="/tmp/${PLUGIN_SLUG}-wporg-${VERSION}.zip"
DRY_RUN=0

[[ "${1:-}" == "--dry-run" ]] && DRY_RUN=1

echo "Building MCPWP v${VERSION} WP.org zip..."
echo "Source: $PLUGIN_DIR"
echo "Output: $OUT_ZIP"
[[ $DRY_RUN == 1 ]] && echo "(DRY RUN — no zip created)"

# Clean build dir
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"

# Copy plugin files
rsync -a --delete \
  --exclude='.git/' \
  --exclude='.github/' \
  --exclude='tests/' \
  --exclude='docs/' \
  --exclude='scripts/' \
  --exclude='composer.json' \
  --exclude='composer.lock' \
  --exclude='vendor/' \
  --exclude='node_modules/' \
  --exclude='CHANGELOG.md' \
  --exclude='MCP_IMPLEMENTATION.md' \
  --exclude='CLAUDE.md' \
  --exclude='CLAUDE_DESKTOP_SETUP.md' \
  --exclude='*.sh' \
  --exclude='includes/pro/' \
  "$PLUGIN_DIR/" "$BUILD_DIR/$PLUGIN_SLUG/"

echo ""
echo "Included files:"
find "$BUILD_DIR/$PLUGIN_SLUG" -type f | sort | sed "s|$BUILD_DIR/$PLUGIN_SLUG/||"

echo ""
echo "File count: $(find "$BUILD_DIR/$PLUGIN_SLUG" -type f | wc -l)"
echo "Size: $(du -sh "$BUILD_DIR/$PLUGIN_SLUG" | cut -f1)"

# Verify version consistency
HEADER_VER="$(grep "^ \* Version:" "$BUILD_DIR/$PLUGIN_SLUG/mcpwp.php" | awk '{print $3}')"
CONST_VER="$(grep "define( 'MCPWP_VERSION'" "$BUILD_DIR/$PLUGIN_SLUG/mcpwp.php" | grep -o "'[0-9.]*'" | tr -d "'")"
README_VER="$(grep "^Stable tag:" "$BUILD_DIR/$PLUGIN_SLUG/readme.txt" | awk '{print $3}')"

echo ""
echo "Version check:"
echo "  Header:    $HEADER_VER"
echo "  Constant:  $CONST_VER"
echo "  readme.txt: $README_VER"

if [[ "$HEADER_VER" != "$CONST_VER" || "$HEADER_VER" != "$README_VER" ]]; then
  echo "ERROR: Version mismatch — fix before submitting to WP.org"
  exit 1
fi
echo "  All match ✓"

# Check for required readme sections
echo ""
echo "Readme sections check:"
for section in "== Description ==" "== Installation ==" "== Changelog ==" "== Third Party Services =="; do
  if grep -q "$section" "$BUILD_DIR/$PLUGIN_SLUG/readme.txt"; then
    echo "  $section ✓"
  else
    echo "  $section MISSING ✗"
    [[ "$section" == "== Third Party Services ==" ]] && echo "    → Add per docs/WP-ORG-AUDIT.md before submitting"
  fi
done

if [[ $DRY_RUN == 0 ]]; then
  # Create zip
  rm -f "$OUT_ZIP"
  cd "$BUILD_DIR"
  zip -r "$OUT_ZIP" "$PLUGIN_SLUG/" -x "*.DS_Store" -x "__MACOSX/*"
  echo ""
  echo "Created: $OUT_ZIP ($(du -sh "$OUT_ZIP" | cut -f1))"
fi

# Cleanup
rm -rf "$BUILD_DIR"
echo ""
echo "Done. Next: upload $OUT_ZIP to https://wordpress.org/plugins/add/"
