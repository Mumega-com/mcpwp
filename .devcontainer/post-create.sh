#!/usr/bin/env bash
set -e

echo "Starting WordPress test environment..."
docker compose -f wp-test/docker-compose.yml up -d

echo "Waiting for WordPress to be ready..."
timeout 60 bash -c 'until docker exec wp-test-wordpress-1 wp core is-installed --allow-root 2>/dev/null; do sleep 3; done'

echo "Generating MCPWP API key..."
API_KEY=$(docker exec wp-test-wordpress-1 bash -c 'php -r "
require_once \"/var/www/html/wp-load.php\";
\$key = \"spai_\" . bin2hex(random_bytes(24));
update_option(\"spai_api_key\", wp_hash_password(\$key));
echo \$key;
"')

echo ""
echo "============================================"
echo "  MCPWP Dev Environment Ready"
echo "============================================"
echo "  WordPress:    http://localhost:8080"
echo "  WP Admin:     http://localhost:8080/wp-admin"
echo "  MCP Endpoint: http://localhost:8080/wp-json/site-pilot-ai/v1/mcp"
echo "  API Key:      $API_KEY"
echo ""
echo "  Test: curl -s http://localhost:8080/wp-json/site-pilot-ai/v1/mcp \\"
echo "    -H 'X-API-Key: \$API_KEY' \\"
echo "    -H 'Content-Type: application/json' \\"
echo "    -d '{\"jsonrpc\":\"2.0\",\"id\":1,\"method\":\"initialize\",\"params\":{\"protocolVersion\":\"2024-11-05\",\"capabilities\":{},\"clientInfo\":{\"name\":\"test\",\"version\":\"1.0\"}}}' | jq"
echo "============================================"
