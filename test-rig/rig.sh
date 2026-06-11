#!/usr/bin/env bash
# MCPWP Test Rig control script
# Usage: rig.sh <up|down|status|reset|provision>
set -euo pipefail

RIG_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COMPOSE="docker compose -f ${RIG_DIR}/docker-compose.yml --project-name wp-rig"

DEV_CONTAINER="wp-rig-wp-dev"
UPGRADE_CONTAINER="wp-rig-wp-upgrade"

DEV_URL="http://127.0.0.1:8086"
UPGRADE_URL="http://127.0.0.1:8087"
CHANNEL_URL="http://127.0.0.1:8088"

WP_DEV="docker exec ${DEV_CONTAINER} wp --allow-root"
WP_UPG="docker exec ${UPGRADE_CONTAINER} wp --allow-root"

#----------------------------------------------------------------------
# install_wpcli — idempotent, reinstalls if missing after container restart
#----------------------------------------------------------------------
install_wpcli() {
  for ctr in "${DEV_CONTAINER}" "${UPGRADE_CONTAINER}"; do
    if ! docker exec "$ctr" wp --version --allow-root >/dev/null 2>&1; then
      echo "  [wpcli] Installing WP-CLI in $ctr..."
      docker exec "$ctr" bash -c \
        'curl -sS https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp && chmod +x /usr/local/bin/wp' 2>&1
    fi
  done
}

#----------------------------------------------------------------------
# up
#----------------------------------------------------------------------
cmd_up() {
  echo "==> Starting rig..."
  ${COMPOSE} up -d
  echo "==> Waiting for DB healthy..."
  local i=0
  until docker inspect wp-rig-db --format '{{.State.Health.Status}}' 2>/dev/null | grep -q healthy; do
    sleep 3
    i=$((i+1))
    if [ "$i" -ge 30 ]; then echo "ERROR: DB never became healthy"; exit 1; fi
  done
  echo "==> Installing WP-CLI (idempotent)..."
  install_wpcli
  echo "==> Services up. Ports: dev=${DEV_URL}  upgrade=${UPGRADE_URL}  channel=${CHANNEL_URL}"
}

#----------------------------------------------------------------------
# down
#----------------------------------------------------------------------
cmd_down() {
  echo "==> Stopping rig (volumes preserved)..."
  ${COMPOSE} down
}

#----------------------------------------------------------------------
# status
#----------------------------------------------------------------------
cmd_status() {
  echo "=== Compose status ==="
  ${COMPOSE} ps
  echo ""
  echo "=== HTTP health ==="
  for url in "${DEV_URL}/wp-login.php" "${UPGRADE_URL}/wp-login.php" "${CHANNEL_URL}/version.json"; do
    code=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 4 "$url" 2>/dev/null || echo "CONN_ERR")
    echo "  ${url}  →  HTTP ${code}"
  done
}

#----------------------------------------------------------------------
# reset  (nuke volumes, fresh slate)
#----------------------------------------------------------------------
cmd_reset() {
  echo "==> RESET: destroying volumes and reprovisioning..."
  ${COMPOSE} down -v 2>/dev/null || true
  # Wipe bind-mount dirs
  rm -rf "${RIG_DIR}/volumes/db" "${RIG_DIR}/volumes/dev-uploads" \
         "${RIG_DIR}/volumes/upgrade-uploads" "${RIG_DIR}/volumes/upgrade-plugins"
  mkdir -p "${RIG_DIR}/volumes/db" "${RIG_DIR}/volumes/dev-uploads" \
           "${RIG_DIR}/volumes/upgrade-uploads" "${RIG_DIR}/volumes/upgrade-plugins"
  cmd_up
  cmd_provision
}

#----------------------------------------------------------------------
# provision (idempotent — safe to run on a running rig)
#----------------------------------------------------------------------
cmd_provision() {
  # Ensure WP-CLI is present (survives container restarts)
  install_wpcli

  echo ""
  echo "=============================="
  echo "  PROVISION: wp-dev (8086)"
  echo "=============================="

  # Wait for WP to be reachable
  local tries=0
  until curl -s -o /dev/null -w "%{http_code}" "${DEV_URL}/wp-login.php" 2>/dev/null | grep -qE "^(200|302)"; do
    sleep 4
    tries=$((tries+1))
    if [ "$tries" -ge 30 ]; then echo "ERROR: wp-dev never responded"; exit 1; fi
  done

  # Core install (idempotent)
  if ! ${WP_DEV} core is-installed 2>/dev/null; then
    ${WP_DEV} core install \
      --url="${DEV_URL}" \
      --title="MCPWP Dev Rig" \
      --admin_user=admin \
      --admin_password=admin \
      --admin_email=admin@example.com \
      --skip-email
    echo "  [wp-dev] Core installed."
  else
    echo "  [wp-dev] Core already installed, skipping."
  fi

  # Install + activate plugins
  for slug in elementor woocommerce; do
    if ${WP_DEV} plugin is-installed "$slug" 2>/dev/null; then
      echo "  [wp-dev] $slug already installed."
    else
      echo "  [wp-dev] Installing $slug..."
      ${WP_DEV} plugin install "$slug" --activate 2>&1 | tail -2 || echo "  WARNING: $slug install failed"
    fi
    ${WP_DEV} plugin activate "$slug" 2>/dev/null || true
  done

  # LearnPress (optional — don't fail rig if it errors)
  if ! ${WP_DEV} plugin is-installed learnpress 2>/dev/null; then
    echo "  [wp-dev] Attempting LearnPress install (optional)..."
    ${WP_DEV} plugin install learnpress --activate 2>&1 | tail -2 || echo "  NOTE: learnpress install failed — non-blocking."
  else
    ${WP_DEV} plugin activate learnpress 2>/dev/null || true
  fi

  # Activate mcpwp (bind-mounted)
  if ${WP_DEV} plugin is-installed mcpwp 2>/dev/null; then
    ${WP_DEV} plugin activate mcpwp 2>/dev/null && echo "  [wp-dev] mcpwp activated." || true
  else
    echo "  WARNING: mcpwp plugin not found at bind-mount path — check docker-compose.yml volume."
  fi

  # Generate MCPWP API key (scoped store) only if no key file exists
  if [ -f "${RIG_DIR}/.mcpwp-dev-key" ]; then
    MCPWP_KEY=$(cat "${RIG_DIR}/.mcpwp-dev-key")
    echo "  [wp-dev] Using existing key from .mcpwp-dev-key"
  else
    echo "  [wp-dev] Generating MCPWP API key..."
    MCPWP_KEY=$(docker exec "${DEV_CONTAINER}" bash -c 'php -r "
require_once \"/var/www/html/wp-load.php\";
\$plain = \"mcpwp_\" . bin2hex(random_bytes(24));
\$hash  = wp_hash_password(\$plain);
\$key_id = wp_generate_uuid4();
\$now    = current_time(\"mysql\");
\$record = array(
  \"id\"           => \$key_id,
  \"label\"        => \"Rig Dev Key\",
  \"hash\"         => \$hash,
  \"scopes\"       => array(\"read\",\"write\",\"admin\"),
  \"role\"         => \"admin\",
  \"tool_categories\" => array(),
  \"created_at\"   => \$now,
  \"last_used_at\" => null,
  \"revoked_at\"   => null,
);
\$existing = get_option(\"mcpwp_api_keys\", array());
if (!is_array(\$existing)) \$existing = array();
\$existing[] = \$record;
update_option(\"mcpwp_api_keys\", \$existing);
update_option(\"mcpwp_api_key\", \$hash);
echo \$plain;
"' 2>/dev/null)

    echo "${MCPWP_KEY}" > "${RIG_DIR}/.mcpwp-dev-key"
  fi

  echo "  [wp-dev] MCPWP API Key: ${MCPWP_KEY}"

  # Verify with curl
  echo "  [wp-dev] Verifying site-info endpoint..."
  SITE_INFO=$(curl -s -w "\nHTTP:%{http_code}" "${DEV_URL}/wp-json/mcpwp/v1/site-info" -H "X-API-Key: ${MCPWP_KEY}" 2>/dev/null)
  HTTP_CODE=$(echo "${SITE_INFO}" | grep "^HTTP:" | cut -d: -f2)
  if [ "${HTTP_CODE}" = "200" ]; then
    echo "  [wp-dev] VERIFIED: site-info HTTP 200"
  else
    echo "  WARNING: site-info returned HTTP ${HTTP_CODE} — check plugin activation"
    echo "  Response: $(echo "${SITE_INFO}" | head -c 300)"
  fi
  echo "  [wp-dev] Key saved to: ${RIG_DIR}/.mcpwp-dev-key"

  echo ""
  echo "=============================="
  echo "  PROVISION: wp-upgrade (8087)"
  echo "=============================="

  # Wait for wp-upgrade
  tries=0
  until curl -s -o /dev/null -w "%{http_code}" "${UPGRADE_URL}/wp-login.php" 2>/dev/null | grep -qE "^(200|302)"; do
    sleep 4
    tries=$((tries+1))
    if [ "$tries" -ge 30 ]; then echo "ERROR: wp-upgrade never responded"; exit 1; fi
  done

  if ! ${WP_UPG} core is-installed 2>/dev/null; then
    ${WP_UPG} core install \
      --url="${UPGRADE_URL}" \
      --title="MCPWP Upgrade Rig" \
      --admin_user=admin \
      --admin_password=admin \
      --admin_email=admin@example.com \
      --skip-email
    echo "  [wp-upgrade] Core installed."
  else
    echo "  [wp-upgrade] Core already installed, skipping."
  fi

  # Install elementor + woocommerce on upgrade instance
  for slug in elementor woocommerce; do
    if ${WP_UPG} plugin is-installed "$slug" 2>/dev/null; then
      echo "  [wp-upgrade] $slug already installed."
    else
      echo "  [wp-upgrade] Installing $slug..."
      ${WP_UPG} plugin install "$slug" --activate 2>&1 | tail -2 || echo "  WARNING: $slug install failed"
    fi
    ${WP_UPG} plugin activate "$slug" 2>/dev/null || true
  done

  # Copy + install 2.8.56 customer zip
  SPAI_ZIP="/mnt/HC_Volume_104325311/projects/sitepilotai/wp-ai-operator/site-pilot-ai/scripts/mumega-mcp-selfhosted-2.8.56.zip"
  if [ -f "${SPAI_ZIP}" ]; then
    docker cp "${SPAI_ZIP}" "${UPGRADE_CONTAINER}:/tmp/mumega-mcp-selfhosted-2.8.56.zip"
    if ${WP_UPG} plugin is-installed site-pilot-ai 2>/dev/null; then
      echo "  [wp-upgrade] site-pilot-ai already installed."
    else
      echo "  [wp-upgrade] Installing 2.8.56 from local zip..."
      ${WP_UPG} plugin install /tmp/mumega-mcp-selfhosted-2.8.56.zip --activate 2>&1
    fi
    ${WP_UPG} plugin activate site-pilot-ai 2>/dev/null || true
  else
    echo "  ERROR: 2.8.56 zip not found at ${SPAI_ZIP}"
  fi

  # Point update channel to local channel container
  ${WP_UPG} option update spai_version_url "http://channel/version.json" 2>/dev/null
  echo "  [wp-upgrade] spai_version_url set to http://channel/version.json"

  # Generate 2.8.x-style API key only if no key file exists
  if [ -f "${RIG_DIR}/.spai-upgrade-key" ]; then
    SPAI_KEY=$(cat "${RIG_DIR}/.spai-upgrade-key")
    echo "  [wp-upgrade] Using existing key from .spai-upgrade-key"
  else
    echo "  [wp-upgrade] Generating spai_ API key..."
    SPAI_KEY=$(docker exec "${UPGRADE_CONTAINER}" bash -c 'php -r "
require_once \"/var/www/html/wp-load.php\";
\$plain = \"spai_\" . bin2hex(random_bytes(24));
\$hash  = wp_hash_password(\$plain);
\$key_id = wp_generate_uuid4();
\$now    = current_time(\"mysql\");
\$record = array(
  \"id\"           => \$key_id,
  \"label\"        => \"Rig Upgrade Key\",
  \"hash\"         => \$hash,
  \"scopes\"       => array(\"read\",\"write\",\"admin\"),
  \"role\"         => \"admin\",
  \"tool_categories\" => array(),
  \"created_at\"   => \$now,
  \"last_used_at\" => null,
  \"revoked_at\"   => null,
);
\$existing = get_option(\"spai_api_keys\", array());
if (!is_array(\$existing)) \$existing = array();
\$existing[] = \$record;
update_option(\"spai_api_keys\", \$existing);
update_option(\"spai_api_key\", \$hash);
echo \$plain;
"' 2>/dev/null)

    echo "${SPAI_KEY}" > "${RIG_DIR}/.spai-upgrade-key"
  fi

  echo "  [wp-upgrade] SPAI API Key: ${SPAI_KEY}"
  echo "  [wp-upgrade] Key saved to: ${RIG_DIR}/.spai-upgrade-key"

  # Confirm plugin list
  echo "  [wp-upgrade] Plugin list:"
  ${WP_UPG} plugin list --fields=name,status,version 2>/dev/null | grep -E "site-pilot-ai|NAME"

  echo ""
  echo "=== PROVISION COMPLETE ==="
  echo "  wp-dev  mcpwp key : ${MCPWP_KEY}"
  echo "  wp-dev  key file  : ${RIG_DIR}/.mcpwp-dev-key"
  echo "  wp-upgrade spai key: ${SPAI_KEY}"
  echo "  wp-upgrade key file: ${RIG_DIR}/.spai-upgrade-key"
  echo ""
  echo "  Quick verify:"
  echo "    curl -s ${DEV_URL}/wp-json/mcpwp/v1/site-info -H \"X-API-Key: \$(cat ${RIG_DIR}/.mcpwp-dev-key)\" | jq .name"
  echo "    curl -s ${CHANNEL_URL}/version.json | jq .version"
}

#----------------------------------------------------------------------
# dispatch
#----------------------------------------------------------------------
CMD="${1:-}"
case "$CMD" in
  up)        cmd_up ;;
  down)      cmd_down ;;
  status)    cmd_status ;;
  reset)     cmd_reset ;;
  provision) cmd_provision ;;
  *)
    echo "Usage: $(basename "$0") <up|down|status|reset|provision>"
    echo ""
    echo "  up        — start all services + install WP-CLI"
    echo "  down      — stop all services (volumes preserved)"
    echo "  status    — show compose ps + HTTP health"
    echo "  reset     — nuke volumes + reprovision (clean slate)"
    echo "  provision — install WP, plugins, keys (idempotent)"
    exit 1
    ;;
esac
