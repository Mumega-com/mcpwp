#!/usr/bin/env bash
#
# Build script for Mumega MCP WordPress plugin
#
# Creates two local distribution zips:
#   1. site-pilot-ai.zip - paid/self-hosted package with licensed modules
#   2. site-pilot-ai-wporg.zip - WP.org-compatible package without licensed modules
#
# Both zips exclude patterns defined in .distignore
#
# Usage: ./build.sh

set -e  # Exit on error

# Color output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Directories
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="${REPO_ROOT}/site-pilot-ai"
DIST_DIR="${REPO_ROOT}/dist"
BUILD_DIR="${REPO_ROOT}/.build-tmp"

# Plugin main file
PLUGIN_FILE="${PLUGIN_DIR}/site-pilot-ai.php"

# Distignore file
DISTIGNORE="${REPO_ROOT}/.distignore"

echo -e "${GREEN}=== Mumega MCP Build Script ===${NC}"
echo ""

# Step 1: Extract version from plugin main file
echo -e "${YELLOW}[1/6] Extracting version from ${PLUGIN_FILE}...${NC}"
if [ ! -f "$PLUGIN_FILE" ]; then
    echo -e "${RED}Error: Plugin file not found: ${PLUGIN_FILE}${NC}"
    exit 1
fi

VERSION=$(grep -m 1 "Version:" "$PLUGIN_FILE" | sed -E 's/.*Version:[[:space:]]*([0-9.]+).*/\1/')

if [ -z "$VERSION" ]; then
    echo -e "${RED}Error: Could not extract version from ${PLUGIN_FILE}${NC}"
    exit 1
fi

echo -e "${GREEN}Version: ${VERSION}${NC}"
echo ""

# Step 2: Create dist directory
echo -e "${YELLOW}[2/6] Creating dist directory...${NC}"
mkdir -p "$DIST_DIR"
echo -e "${GREEN}Created: ${DIST_DIR}${NC}"
echo ""

# Step 3: Clean up any previous build temp directory
echo -e "${YELLOW}[3/6] Cleaning up previous builds...${NC}"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"
echo -e "${GREEN}Cleaned${NC}"
echo ""

# Function to copy plugin files excluding patterns from .distignore
copy_plugin_files() {
    local src="$1"
    local dest="$2"
    local exclude_pro="$3"  # "yes" for WP.org-compatible package, "no" for paid/self-hosted package

    echo "  Copying files from ${src} to ${dest}..."

    # Copy the entire plugin directory
    cp -r "$src" "$dest"

    # Remove files/directories matching .distignore patterns
    if [ -f "$DISTIGNORE" ]; then
        echo "  Applying .distignore exclusions..."

        # Read .distignore line by line
        while IFS= read -r pattern; do
            # Skip empty lines and comments
            [[ -z "$pattern" || "$pattern" =~ ^[[:space:]]*# ]] && continue

            # Handle negation patterns (starting with !)
            if [[ "$pattern" =~ ^! ]]; then
                continue  # Skip negation patterns in removal phase
            fi

            # Remove trailing slashes for consistency
            pattern="${pattern%/}"

            # Find and remove matching files/directories inside the copied plugin
            if [[ "$pattern" == *"*"* ]]; then
                # Pattern with wildcard
                find "$dest/site-pilot-ai" -name "${pattern}" -exec rm -rf {} + 2>/dev/null || true
            else
                # Exact path pattern - check if it exists relative to plugin root
                if [ -e "$dest/site-pilot-ai/$pattern" ]; then
                    rm -rf "$dest/site-pilot-ai/$pattern"
                fi
            fi
        done < "$DISTIGNORE"
    fi

    # Remove licensed modules if building the WP.org-compatible package.
    if [ "$exclude_pro" = "yes" ]; then
        echo "  Removing includes/pro/ directory (WP.org-compatible package)..."
        rm -rf "$dest/site-pilot-ai/includes/pro"
    fi
}

# Step 4: Build paid/self-hosted package (with licensed modules)
echo -e "${YELLOW}[4/6] Building paid/self-hosted package (site-pilot-ai.zip)...${NC}"
PAID_BUILD_DIR="${BUILD_DIR}/paid"
mkdir -p "$PAID_BUILD_DIR"
copy_plugin_files "$PLUGIN_DIR" "$PAID_BUILD_DIR" "no"

# Create paid/self-hosted zip
PAID_ZIP="${DIST_DIR}/site-pilot-ai.zip"
cd "$PAID_BUILD_DIR"
zip -r -q "$PAID_ZIP" site-pilot-ai/
cd "$REPO_ROOT"

echo -e "${GREEN}Created: ${PAID_ZIP}${NC}"
echo ""

# Step 5: Build WP.org-compatible package (without licensed modules)
echo -e "${YELLOW}[5/6] Building WP.org-compatible package (site-pilot-ai-wporg.zip)...${NC}"
WPORG_BUILD_DIR="${BUILD_DIR}/wporg"
mkdir -p "$WPORG_BUILD_DIR"
copy_plugin_files "$PLUGIN_DIR" "$WPORG_BUILD_DIR" "yes"

# Create WP.org-compatible zip
WPORG_ZIP="${DIST_DIR}/site-pilot-ai-wporg.zip"
cd "$WPORG_BUILD_DIR"
zip -r -q "$WPORG_ZIP" site-pilot-ai/
cd "$REPO_ROOT"

echo -e "${GREEN}Created: ${WPORG_ZIP}${NC}"
echo ""

# Step 6: Clean up build temp directory
echo -e "${YELLOW}[6/6] Cleaning up temporary files...${NC}"
rm -rf "$BUILD_DIR"
echo -e "${GREEN}Cleaned${NC}"
echo ""

# Summary
echo -e "${GREEN}=== Build Complete ===${NC}"
echo ""
echo "Version: ${VERSION}"
echo ""
echo "Output files:"
echo "  Paid/self-hosted: ${PAID_ZIP}"
echo "  WP.org-compatible: ${WPORG_ZIP}"
echo ""

# Show file sizes
if command -v du &> /dev/null; then
    PAID_SIZE=$(du -h "$PAID_ZIP" | cut -f1)
    WPORG_SIZE=$(du -h "$WPORG_ZIP" | cut -f1)
    echo "File sizes:"
    echo "  Paid/self-hosted: ${PAID_SIZE}"
    echo "  WP.org-compatible: ${WPORG_SIZE}"
    echo ""
fi

echo -e "${GREEN}Done!${NC}"
