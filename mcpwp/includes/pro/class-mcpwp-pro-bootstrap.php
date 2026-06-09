<?php
/**
 * Pro Module Bootstrap
 *
 * Loads additional endpoints/features for licensed paid plans and trials.
 *
 * @package MCPWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pro bootstrap class.
 */
class Mcpwp_Pro_Bootstrap {

	/**
	 * Register hooks for pro features.
	 */
	public static function init() {
		add_action( 'mcpwp_register_rest_routes', array( __CLASS__, 'register_routes' ) );
		add_filter( 'mcpwp_site_capabilities', array( __CLASS__, 'add_pro_capabilities' ) );
	}

	/**
	 * Register Pro REST API routes.
	 */
	public static function register_routes() {
		if ( ! class_exists( 'Mcpwp_REST_API' ) ) {
			return;
		}

		// Core handlers.
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/core/class-mcpwp-elementor-pro.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/core/class-mcpwp-seo.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/core/class-mcpwp-forms.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/core/class-mcpwp-site-manager.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/core/class-mcpwp-theme-builder.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/core/class-mcpwp-users.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/core/class-mcpwp-widgets.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/core/class-mcpwp-themes.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/core/class-mcpwp-woocommerce.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/core/class-mcpwp-multilang.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/core/class-mcpwp-page-builder.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/core/class-mcpwp-google-indexing.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/core/class-mcpwp-learnpress.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/core/class-mcpwp-events.php';

		// REST controllers.
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/api/class-mcpwp-rest-elementor-pro.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/api/class-mcpwp-rest-seo.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/api/class-mcpwp-rest-forms.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/api/class-mcpwp-rest-site-manager.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/api/class-mcpwp-rest-theme-builder.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/api/class-mcpwp-rest-users.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/api/class-mcpwp-rest-widgets.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/api/class-mcpwp-rest-themes.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/api/class-mcpwp-rest-woocommerce.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/api/class-mcpwp-rest-multilang.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/api/class-mcpwp-rest-google-indexing.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/api/class-mcpwp-rest-learnpress.php';
		require_once MCPWP_PLUGIN_DIR . 'includes/pro/api/class-mcpwp-rest-events.php';

		$elementor_pro = new Mcpwp_Elementor_Pro();
		$seo           = new Mcpwp_SEO();
		$forms         = new Mcpwp_Forms();
		$site_manager  = new Mcpwp_Site_Manager();
		$theme_builder = new Mcpwp_Theme_Builder();
		$users         = new Mcpwp_Users();
		$widgets       = new Mcpwp_Widgets();
		$themes        = new Mcpwp_Themes();
		$woocommerce   = new Mcpwp_WooCommerce();
		$multilang     = new Mcpwp_Multilang();
		$google_indexing = new Mcpwp_Google_Indexing();
		$learnpress      = new Mcpwp_LearnPress();
		$events          = new Mcpwp_Events();

		( new Mcpwp_REST_Elementor_Pro( $elementor_pro ) )->register_routes();
		( new Mcpwp_REST_SEO( $seo ) )->register_routes();
		( new Mcpwp_REST_Forms( $forms ) )->register_routes();
		( new Mcpwp_REST_Site_Manager( $site_manager ) )->register_routes();
		( new Mcpwp_REST_Theme_Builder( $theme_builder ) )->register_routes();
		( new Mcpwp_REST_Users( $users ) )->register_routes();
		( new Mcpwp_REST_Widgets( $widgets ) )->register_routes();
		( new Mcpwp_REST_Themes( $themes ) )->register_routes();
		( new Mcpwp_REST_WooCommerce( $woocommerce ) )->register_routes();
		( new Mcpwp_REST_Multilang( $multilang ) )->register_routes();
		( new Mcpwp_REST_Google_Indexing( $google_indexing ) )->register_routes();

		// LearnPress LMS — only register if LearnPress is active.
		if ( class_exists( 'LearnPress' ) || post_type_exists( 'lp_course' ) ) {
			( new Mcpwp_REST_LearnPress( $learnpress ) )->register_routes();
		}

		// TP Events — only register if tp_event post type exists.
		if ( post_type_exists( 'tp_event' ) ) {
			( new Mcpwp_REST_Events( $events ) )->register_routes();
		}
	}

	/**
	 * Add Pro capabilities to the capabilities array.
	 *
	 * @param array $capabilities Capabilities array.
	 * @return array
	 */
	public static function add_pro_capabilities( $capabilities ) {
		$license = class_exists( 'Mcpwp_License' ) ? Mcpwp_License::get_instance() : null;
		$capabilities['pro_active']  = $license ? $license->is_pro() : false;
		$capabilities['plan']        = $license ? $license->get_plan() : 'unlicensed';
		$capabilities['learnpress']  = class_exists( 'LearnPress' ) || post_type_exists( 'lp_course' );
		$capabilities['tp_events']   = post_type_exists( 'tp_event' );
		return $capabilities;
	}
}
