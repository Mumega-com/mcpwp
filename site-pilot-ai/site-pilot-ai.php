<?php
/**
 * MCPWP
 *
 * Connect WordPress to AI assistants via the Model Context Protocol (MCP).
 * Expose your WordPress site's functionality to AI assistants like Claude.
 *
 * @package           MumegaMCP
 * @author            Mumega
 * @copyright         2026 Mumega
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       MCPWP
 * Plugin URI:        https://mcpwp.net/
 * Description:       Connect WordPress to AI assistants via the Model Context Protocol (MCP). Manage posts, pages, media, and Elementor through natural language.
 * Version:           2.8.37
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Mumega
 * Author URI:        https://mumega.com/
 * Text Domain:       mumega-mcp
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 */
define( 'SPAI_VERSION', '2.8.37' );

/**
 * Plugin directory path.
 */
define( 'SPAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'SPAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'SPAI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Minimum WordPress version.
 */
define( 'SPAI_MIN_WP_VERSION', '5.0' );

/**
 * Minimum PHP version.
 */
define( 'SPAI_MIN_PHP_VERSION', '7.4' );

/**
 * Default PostHog values.
 */
if ( ! defined( 'SPAI_POSTHOG_DEFAULT_TOKEN' ) ) {
	define( 'SPAI_POSTHOG_DEFAULT_TOKEN', 'phc_vdyUDJrQpNCCfHyi3EFUPDq9avBfZ87tUTYqNC6CiXwX' );
}

if ( ! defined( 'SPAI_POSTHOG_DEFAULT_HOST' ) ) {
	define( 'SPAI_POSTHOG_DEFAULT_HOST', 'https://us.i.posthog.com' );
}

/**
 * Read an environment variable with fallback.
 *
 * @param string $name    Environment key.
 * @param string $default Fallback value.
 * @return string
 */
if ( ! function_exists( 'spai_env_var' ) ) {
	function spai_env_var( $name, $default = '' ) {
		$source = array( getenv( $name ) );
		if ( isset( $_ENV[ $name ] ) ) {
			$source[] = $_ENV[ $name ];
		}
		if ( isset( $_SERVER[ $name ] ) ) {
			$source[] = $_SERVER[ $name ];
		}

		foreach ( $source as $value ) {
			if ( is_string( $value ) ) {
				$value = trim( $value );
				if ( '' !== $value ) {
					return $value;
				}
			}
		}

		return $default;
	}
}

/**
 * PostHog configuration.
 */
if ( ! defined( 'SPAI_POSTHOG_TOKEN' ) ) {
	define( 'SPAI_POSTHOG_TOKEN', spai_env_var( 'POSTHOG_PUBLIC_TOKEN', SPAI_POSTHOG_DEFAULT_TOKEN ) );
}

if ( ! defined( 'SPAI_POSTHOG_HOST' ) ) {
	define( 'SPAI_POSTHOG_HOST', spai_env_var( 'POSTHOG_HOST', SPAI_POSTHOG_DEFAULT_HOST ) );
}

/**
 * Check requirements before loading.
 *
 * @return bool True if requirements met.
 */
if ( ! function_exists( 'spai_requirements_met' ) ) {
	function spai_requirements_met() {
	global $wp_version;

	if ( version_compare( PHP_VERSION, SPAI_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'spai_php_version_notice' );
		return false;
	}

	if ( version_compare( $wp_version, SPAI_MIN_WP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'spai_wp_version_notice' );
		return false;
	}

	return true;
	}
}

/**
 * PHP version notice.
 */
if ( ! function_exists( 'spai_php_version_notice' ) ) {
	function spai_php_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required PHP version 2: Current PHP version */
				esc_html__( 'MCPWP requires PHP %1$s or higher. You are running PHP %2$s.', 'mumega-mcp' ),
				esc_html( SPAI_MIN_PHP_VERSION ),
				esc_html( PHP_VERSION )
			);
			?>
		</p>
	</div>
	<?php
	}
}

/**
 * WordPress version notice.
 */
if ( ! function_exists( 'spai_wp_version_notice' ) ) {
	function spai_wp_version_notice() {
	global $wp_version;
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: 1: Required WP version 2: Current WP version */
				esc_html__( 'MCPWP requires WordPress %1$s or higher. You are running WordPress %2$s.', 'mumega-mcp' ),
				esc_html( SPAI_MIN_WP_VERSION ),
				esc_html( $wp_version )
			);
			?>
		</p>
	</div>
	<?php
	}
}

/**
 * Load plugin files.
 */
if ( ! function_exists( 'spai_load_plugin' ) ) {
	function spai_load_plugin() {
	// Check requirements
	if ( ! spai_requirements_met() ) {
		return;
	}

	// Load traits first
	require_once SPAI_PLUGIN_DIR . 'includes/traits/trait-spai-api-auth.php';
	require_once SPAI_PLUGIN_DIR . 'includes/traits/trait-spai-sanitization.php';
	require_once SPAI_PLUGIN_DIR . 'includes/traits/trait-spai-logging.php';

	// Load core classes
	require_once SPAI_PLUGIN_DIR . 'includes/class-spai-loader.php';
	require_once SPAI_PLUGIN_DIR . 'includes/class-spai-i18n.php';
	require_once SPAI_PLUGIN_DIR . 'includes/class-spai-activator.php';
	require_once SPAI_PLUGIN_DIR . 'includes/class-spai-deactivator.php';
	require_once SPAI_PLUGIN_DIR . 'includes/class-spai-rate-limiter.php';
	require_once SPAI_PLUGIN_DIR . 'includes/class-spai-error-hints.php';
	require_once SPAI_PLUGIN_DIR . 'includes/class-spai-security.php';
	require_once SPAI_PLUGIN_DIR . 'includes/class-spai-webhooks.php';
	require_once SPAI_PLUGIN_DIR . 'includes/class-spai-alerts.php';
	require_once SPAI_PLUGIN_DIR . 'includes/class-spai-license.php';
	if ( defined( 'SPAI_FREEMIUS_BUILD' ) && SPAI_FREEMIUS_BUILD && file_exists( SPAI_PLUGIN_DIR . 'includes/freemius-init.php' ) ) {
		require_once SPAI_PLUGIN_DIR . 'includes/freemius-init.php';
	}
	// Self-updater excluded from WP.org builds — only loaded when present.
	if ( file_exists( SPAI_PLUGIN_DIR . 'includes/class-spai-updater.php' ) ) {
		require_once SPAI_PLUGIN_DIR . 'includes/class-spai-updater.php';
	}
	require_once SPAI_PLUGIN_DIR . 'includes/class-spai-ai-presence.php';

	// Load core functionality
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-core.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-posts.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-pages.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-media.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-drafts.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-elementor-basic.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-elementor-widgets.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-screenshot.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-guides.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-workflows.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-feedback.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-encryption.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-integration-manager.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-design-references.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-event-store.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-site-state.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-agent-playbooks.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-content-coherence.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-approvals.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-seo-audit-store.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-seo-autofix.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-search-performance.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-woocommerce-seo.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-figma.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-provider-openai.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-provider-gemini.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-provider-elevenlabs.php';
	require_once SPAI_PLUGIN_DIR . 'includes/core/class-spai-provider-pexels.php';

	// Load MCP tool registries
	require_once SPAI_PLUGIN_DIR . 'includes/mcp/class-spai-mcp-tool-registry.php';
	require_once SPAI_PLUGIN_DIR . 'includes/mcp/class-spai-integration.php';
	require_once SPAI_PLUGIN_DIR . 'includes/mcp/class-spai-mcp-free-tools.php';
	require_once SPAI_PLUGIN_DIR . 'includes/mcp/class-spai-mcp-pro-tools.php';
	require_once SPAI_PLUGIN_DIR . 'includes/mcp/class-spai-mcp-ai-integration.php';
	require_once SPAI_PLUGIN_DIR . 'includes/mcp/class-spai-mcp-figma-integration.php';

	// Load Pro modules only for non-WP.org builds with an active entitlement.
	$pro_bootstrap = SPAI_PLUGIN_DIR . 'includes/pro/class-spai-pro-bootstrap.php';
	$pro_active    = ! defined( 'SPAI_WPORG_BUILD' )
		&& class_exists( 'Spai_License' )
		&& Spai_License::get_instance()->is_pro();
	if ( $pro_active && file_exists( $pro_bootstrap ) ) {
		require_once $pro_bootstrap;
		if ( class_exists( 'Spai_Pro_Bootstrap' ) ) {
			Spai_Pro_Bootstrap::init();
		}
	}

	// Load REST API
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-api.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-posts.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-pages.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-media.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-site.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-content-graph.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-seo-audit.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-content-quality.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-menus.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-content.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-elementor.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-webhooks.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-screenshot.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-feedback.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-blocks.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-approvals.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-mcp.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-batch.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-integrations.php';
	require_once SPAI_PLUGIN_DIR . 'includes/api/class-spai-rest-figma.php';

	// Load admin
	require_once SPAI_PLUGIN_DIR . 'includes/admin/class-spai-admin.php';
	require_once SPAI_PLUGIN_DIR . 'includes/admin/class-spai-activity-log.php';
	require_once SPAI_PLUGIN_DIR . 'includes/admin/class-spai-settings.php';
	require_once SPAI_PLUGIN_DIR . 'includes/admin/class-spai-integrations-admin.php';
	require_once SPAI_PLUGIN_DIR . 'includes/admin/class-spai-tools-admin.php';

	// Check if database needs updating
	$installed_db_version = get_option( 'spai_db_version', '0' );
	if ( version_compare( $installed_db_version, SPAI_VERSION, '<' ) ) {
		require_once SPAI_PLUGIN_DIR . 'includes/class-spai-activator.php';
		Spai_Activator::activate();
		update_option( 'spai_db_version', SPAI_VERSION );
	}

	// Initialize the plugin
	$loader = new Spai_Loader();
	$loader->run();

	// Self-hosted update checker (excluded from WP.org builds).
	if ( class_exists( 'Spai_Updater' ) ) {
		new Spai_Updater();
	}

	}
}

/**
 * Activation hook — supports network-wide activation on multisite.
 *
 * @param bool $network_wide True when activated network-wide.
 */
if ( ! function_exists( 'spai_activate' ) ) {
	function spai_activate( $network_wide = false ) {
	require_once SPAI_PLUGIN_DIR . 'includes/class-spai-activator.php';

	if ( $network_wide && function_exists( 'is_multisite' ) && is_multisite() ) {
		$sites = get_sites( array( 'fields' => 'ids' ) );
		foreach ( $sites as $blog_id ) {
			switch_to_blog( $blog_id );
			Spai_Activator::activate();
			restore_current_blog();
		}
	} else {
		Spai_Activator::activate();
	}
	}
}

/**
 * Provision MCPWP tables/options when a new site is created in a multisite network.
 *
 * @param WP_Site $new_site New site object.
 */
if ( ! function_exists( 'spai_on_new_site' ) ) {
	function spai_on_new_site( $new_site ) {
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// Only run if MCPWP is network-activated.
	if ( ! is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
		return;
	}

	require_once SPAI_PLUGIN_DIR . 'includes/class-spai-activator.php';

	switch_to_blog( $new_site->blog_id );
	Spai_Activator::activate();
	restore_current_blog();
	}
}
add_action( 'wp_insert_site', 'spai_on_new_site' );

/**
 * Deactivation hook.
 */
if ( ! function_exists( 'spai_deactivate' ) ) {
	function spai_deactivate() {
	require_once SPAI_PLUGIN_DIR . 'includes/class-spai-deactivator.php';
	Spai_Deactivator::deactivate();
	}
}

// Register hooks
register_activation_hook( __FILE__, 'spai_activate' );
register_deactivation_hook( __FILE__, 'spai_deactivate' );

// Load plugin after WordPress is loaded
add_action( 'plugins_loaded', 'spai_load_plugin' );
