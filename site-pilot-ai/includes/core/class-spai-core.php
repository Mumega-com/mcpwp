<?php
/**
 * Core functionality
 *
 * @package MumegaMCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core class with shared functionality.
 */
class Spai_Core {

	use Spai_Api_Auth;
	use Spai_Sanitization;
	use Spai_Logging;

	/**
	 * Get site information.
	 *
	 * @return array Site info.
	 */
	public function get_site_info() {
		global $wp_version;

		$theme = wp_get_theme();

		$info = array(
			'name'         => get_bloginfo( 'name' ),
			'description'  => get_bloginfo( 'description' ),
			'url'          => home_url(),
			'admin_url'    => admin_url(),
			'wp_version'   => $wp_version,
			'php_version'  => PHP_VERSION,
			'theme'        => array(
				'name'    => $theme->get( 'Name' ),
				'version' => $theme->get( 'Version' ),
			),
			'timezone'     => wp_timezone_string(),
			'language'     => get_locale(),
			'is_rtl'       => function_exists( 'is_rtl' ) ? is_rtl() : false,
			'text_direction' => ( function_exists( 'is_rtl' ) && is_rtl() ) ? 'rtl' : 'ltr',
			'capabilities' => $this->get_capabilities(),
			'plugin'       => array(
				'name'    => 'MCPWP',
				'version' => SPAI_VERSION,
			),
		);

		$license_info = class_exists( 'Spai_License' )
			? Spai_License::get_instance()->get_license_info()
			: array( 'plan' => 'unlicensed', 'is_pro' => false );

		$info['license'] = array(
			'plan'   => $license_info['plan'],
			'is_pro' => $license_info['is_pro'],
		);

		return $info;
	}

	/**
	 * Get site capabilities (detected plugins).
	 *
	 * @return array Capabilities.
	 */
	public function get_capabilities() {
		$cached = get_transient( 'spai_capabilities_cache' );
		if ( false !== $cached ) {
			if ( $this->capabilities_cache_matches_license( $cached ) ) {
				return $cached;
			}

			delete_transient( 'spai_capabilities_cache' );
		}

		$rankmath_active = defined( 'RANK_MATH_VERSION' )
			|| defined( 'RANK_MATH_FILE' )
			|| class_exists( 'RankMath\\Helper' )
			|| class_exists( 'RankMath\\Loader' );

		// Detect Elementor layout mode (container vs section).
		$elementor_layout = 'section';
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			// Elementor 3.15+ uses containers by default; check experiment setting.
			$experiments = get_option( 'elementor_experiment-container', '' );
			if ( 'active' === $experiments || 'default' === $experiments ) {
				$elementor_layout = 'container';
			}
		}

		// Detect Gutenberg (block editor) availability.
		// Gutenberg is built into WP 5.0+. It's unavailable only when the
		// Classic Editor plugin forces classic mode for all post types.
		$gutenberg_active = function_exists( 'register_block_type' );
		if ( $gutenberg_active ) {
			$classic_option = get_option( 'classic-editor-replace', 'no-replace' );
			if ( 'replace' === $classic_option ) {
				$gutenberg_active = false;
			}
		}

		$capabilities = array(
			'gutenberg'             => $gutenberg_active,
			'elementor'             => defined( 'ELEMENTOR_VERSION' ),
			'elementor_pro'         => defined( 'ELEMENTOR_PRO_VERSION' ),
			'elementor_layout_mode' => $elementor_layout,
			'woocommerce'    => class_exists( 'WooCommerce' ),
			'yoast'          => defined( 'WPSEO_VERSION' ),
			'rankmath'       => $rankmath_active,
			'aioseo'         => defined( 'AIOSEO_VERSION' ),
			'seopress'       => defined( 'SEOPRESS_VERSION' ),
			'cf7'            => class_exists( 'WPCF7' ),
			'wpforms'        => class_exists( 'WPForms' ),
			'gravityforms'   => class_exists( 'GFForms' ),
			'ninjaforms'     => class_exists( 'Ninja_Forms' ),
			'learnpress'          => defined( 'LEARNPRESS_VERSION' ) || class_exists( 'LP' ),
			'is_multisite'        => is_multisite(),
			'network_site_count'  => is_multisite() ? get_blog_count() : null,
		);

		// Allow premium package to extend capabilities (e.g., pro-module-only flags).
		if ( function_exists( 'apply_filters' ) ) {
			$capabilities = apply_filters( 'spai_site_capabilities', $capabilities );
		}

		// Single source of truth for plan / pro_active. Derive these from the
		// canonical license accessor AFTER the filter so they cannot be made
		// inconsistent by other consumers (see issue #319).
		if ( class_exists( 'Spai_License' ) ) {
			$license_info               = Spai_License::get_instance()->get_license_info();
			$capabilities['plan']       = $license_info['plan'];
			$capabilities['pro_active'] = $license_info['is_pro'];
		} else {
			$capabilities['plan']       = 'unlicensed';
			$capabilities['pro_active'] = false;
		}

		// Merge capabilities from third-party integrations.
		if ( class_exists( 'Spai_Integration' ) ) {
			foreach ( Spai_Integration::resolve_all() as $integration ) {
				$capabilities = array_merge( $capabilities, $integration->get_capabilities() );
			}
		}

		// Cache for 1 hour
		set_transient( 'spai_capabilities_cache', $capabilities, HOUR_IN_SECONDS );

		return $capabilities;
	}

	/**
	 * Check whether cached capabilities still match the current license state.
	 *
	 * @param mixed $cached Cached capabilities.
	 * @return bool True when cache can be reused.
	 */
	private function capabilities_cache_matches_license( $cached ) {
		if ( ! is_array( $cached ) || ! class_exists( 'Spai_License' ) ) {
			return true;
		}

		$license = Spai_License::get_instance();
		if ( ! $license ) {
			return true;
		}

		$license_info   = $license->get_license_info();
		$current_plan   = $license_info['plan'];
		$current_is_pro = $license_info['is_pro'];

		if ( isset( $cached['plan'] ) && $cached['plan'] !== $current_plan ) {
			return false;
		}

		if ( isset( $cached['pro_active'] ) && (bool) $cached['pro_active'] !== (bool) $current_is_pro ) {
			return false;
		}

		return true;
	}

	/**
	 * Get analytics data.
	 *
	 * @param int $days Number of days.
	 * @return array Analytics data.
	 */
	public function get_analytics( $days = 30 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'spai_activity_log';
		$since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix.
		$total_requests = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE created_at >= %s",
				$since
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix.
		$by_action = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT action, COUNT(*) as count FROM $table WHERE created_at >= %s GROUP BY action ORDER BY count DESC LIMIT 10",
				$since
			),
			ARRAY_A
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix.
		$by_day = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) as date, COUNT(*) as count FROM $table WHERE created_at >= %s GROUP BY DATE(created_at) ORDER BY date DESC",
				$since
			),
			ARRAY_A
		);

		return array(
			'period_days'    => $days,
			'total_requests' => (int) $total_requests,
			'by_action'      => $by_action,
			'by_day'         => $by_day,
		);
	}

	/**
	 * Detect installed plugins with capabilities.
	 *
	 * @return array Plugin info.
	 */
	public function detect_plugins() {
		$plugins = array();
		$capabilities = $this->get_capabilities();

		// Elementor
		if ( $capabilities['elementor'] ) {
			$plugins['elementor'] = array(
				'name'    => 'Elementor',
				'version' => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : 'unknown',
				'pro'     => $capabilities['elementor_pro'],
			);
		}

		// SEO plugins
		$seo_plugins = array(
			'yoast'    => array( 'name' => 'Yoast SEO', 'const' => 'WPSEO_VERSION' ),
			'rankmath' => array( 'name' => 'RankMath', 'const' => 'RANK_MATH_VERSION' ),
			'aioseo'   => array( 'name' => 'All in One SEO', 'const' => 'AIOSEO_VERSION' ),
			'seopress' => array( 'name' => 'SEOPress', 'const' => 'SEOPRESS_VERSION' ),
		);

		foreach ( $seo_plugins as $key => $info ) {
			if ( $capabilities[ $key ] ) {
				$version = 'unknown';
				if ( isset( $info['const'] ) && defined( $info['const'] ) ) {
					$version = constant( $info['const'] );
				}
				$plugins['seo'] = array(
					'name'    => $info['name'],
					'version' => $version,
					'slug'    => $key,
				);
				break;
			}
		}

		// Form plugins
		$form_plugins = array(
			'cf7'          => array( 'name' => 'Contact Form 7', 'const' => 'WPCF7_VERSION' ),
			'wpforms'      => array( 'name' => 'WPForms', 'const' => 'WPFORMS_VERSION' ),
			'gravityforms' => array( 'name' => 'Gravity Forms', 'class' => 'GFForms' ),
			'ninjaforms'   => array( 'name' => 'Ninja Forms', 'const' => 'NINJA_FORMS_VERSION' ),
		);

		$plugins['forms'] = array();
		foreach ( $form_plugins as $key => $info ) {
			if ( $capabilities[ $key ] ) {
				$version = 'unknown';
				if ( isset( $info['const'] ) && defined( $info['const'] ) ) {
					$version = constant( $info['const'] );
				}
				$plugins['forms'][] = array(
					'name'    => $info['name'],
					'version' => $version,
					'slug'    => $key,
				);
			}
		}

		// WooCommerce
		if ( $capabilities['woocommerce'] ) {
			$plugins['woocommerce'] = array(
				'name'    => 'WooCommerce',
				'version' => defined( 'WC_VERSION' ) ? WC_VERSION : 'unknown',
			);
		}

		// LearnPress
		if ( ! empty( $capabilities['learnpress'] ) ) {
			$plugins['learnpress'] = array(
				'name'    => 'LearnPress',
				'version' => defined( 'LEARNPRESS_VERSION' ) ? LEARNPRESS_VERSION : 'unknown',
			);
		}

		return $plugins;
	}
}
