<?php
/**
 * MCP Tools Admin Page
 *
 * Handles the admin UI for managing MCP tool categories.
 * Allows site owners to enable/disable entire tool categories
 * to reduce noise in the AI model's context.
 *
 * @package MumegaMCP
 * @since   1.1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin page for MCP tool category management.
 */
class Spai_Tools_Admin {

	/**
	 * Page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'site-pilot-ai-tools';

	/**
	 * Category metadata.
	 *
	 * @return array Category slug => display metadata.
	 */
	public static function get_category_meta() {
		return array(
			'content'    => array(
				'name'        => __( 'Content', 'mumega-mcp' ),
				'description' => __( 'Posts, pages, drafts, search, clone, bulk create', 'mumega-mcp' ),
				'icon'        => 'dashicons-admin-post',
			),
			'media'      => array(
				'name'        => __( 'Media', 'mumega-mcp' ),
				'description' => __( 'Upload files, import from URL, media library, screenshots', 'mumega-mcp' ),
				'icon'        => 'dashicons-admin-media',
			),
			'elementor'  => array(
				'name'        => __( 'Elementor', 'mumega-mcp' ),
				'description' => __( 'Get/set page data, templates, globals, widgets, CSS', 'mumega-mcp' ),
				'icon'        => 'dashicons-editor-kitchensink',
			),
			'seo'        => array(
				'name'        => __( 'SEO', 'mumega-mcp' ),
				'description' => __( 'Meta titles, descriptions, analysis, noindex', 'mumega-mcp' ),
				'icon'        => 'dashicons-search',
			),
			'forms'      => array(
				'name'        => __( 'Forms', 'mumega-mcp' ),
				'description' => __( 'List forms, view entries, plugin detection', 'mumega-mcp' ),
				'icon'        => 'dashicons-feedback',
			),
			'gutenberg'  => array(
				'name'        => __( 'Gutenberg', 'mumega-mcp' ),
				'description' => __( 'Block editor data, block types, patterns', 'mumega-mcp' ),
				'icon'        => 'dashicons-block-default',
			),
			'taxonomy'   => array(
				'name'        => __( 'Taxonomy', 'mumega-mcp' ),
				'description' => __( 'Categories, tags, custom taxonomy terms', 'mumega-mcp' ),
				'icon'        => 'dashicons-tag',
			),
			'site'       => array(
				'name'        => __( 'Site', 'mumega-mcp' ),
				'description' => __( 'Site info, plugins, theme, menus, options, health', 'mumega-mcp' ),
				'icon'        => 'dashicons-admin-site-alt3',
			),
			'webhooks'   => array(
				'name'        => __( 'Webhooks', 'mumega-mcp' ),
				'description' => __( 'Create, update, delete, test webhook subscriptions', 'mumega-mcp' ),
				'icon'        => 'dashicons-rest-api',
			),
			'admin'      => array(
				'name'        => __( 'Admin', 'mumega-mcp' ),
				'description' => __( 'API keys, rate limits, feedback', 'mumega-mcp' ),
				'icon'        => 'dashicons-admin-tools',
			),
			'ai'         => array(
				'name'        => __( 'AI', 'mumega-mcp' ),
				'description' => __( 'Stock photos, image generation, alt text, TTS', 'mumega-mcp' ),
				'icon'        => 'dashicons-lightbulb',
			),
		);
	}

	/**
	 * Render the admin page.
	 */
	public function render() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mumega-mcp' ) );
		}

		$disabled_categories = get_option( 'spai_disabled_tool_categories', array() );
		if ( ! is_array( $disabled_categories ) ) {
			$disabled_categories = array();
		}

		$category_meta = self::get_category_meta();

		// Count tools per category.
		$tool_counts = $this->count_tools_per_category();

		include SPAI_PLUGIN_DIR . 'admin/partials/spai-tools-display.php';
	}

	/**
	 * Enqueue admin assets for tools page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'site-pilot-ai_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'spai-admin',
			SPAI_PLUGIN_URL . 'admin/css/spai-admin.css',
			array(),
			SPAI_VERSION
		);

		$posthog          = Spai_Integration_Manager::get_instance()->get_posthog_config();
		$posthog_token    = $posthog['token'];
		$posthog_host     = $posthog['host'];
		wp_register_script( 'spai-posthog-tools', false, array(), SPAI_VERSION, true );
		wp_enqueue_script( 'spai-posthog-tools' );
		wp_add_inline_script(
			'spai-posthog-tools',
			'var MCPWP_PH_TOKEN=' . wp_json_encode( $posthog_token ) . ';var MCPWP_PH_HOST=' . wp_json_encode( $posthog_host ) . ';'
				. '!function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.crossOrigin="anonymous",p.async=\!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0\!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"\!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="init capture register register_once register_for_session unregister unregister_for_session getFeatureFlag getFeatureFlagPayload isFeatureEnabled reloadFeatureFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey getNextSurveyStep identify setPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException loadToolbar get_property getSessionProperty createPersonProfile opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing clear_opt_in_out_capturing debug".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);'
				. 'posthog.init(MCPWP_PH_TOKEN,{api_host:MCPWP_PH_HOST,defaults:"2026-01-30"});'
		);
	}

	/**
	 * Count tools per category from all registries.
	 *
	 * @return array Category slug => tool count.
	 */
	private function count_tools_per_category() {
		$counts = array();

		// Get all tools through the MCP controller.
		$mcp = new Spai_REST_MCP();
		$all_tools = method_exists( $mcp, 'get_introspection_data' )
			? $mcp->get_introspection_data()
			: array();

		$tools = isset( $all_tools['tools'] ) ? $all_tools['tools'] : array();

		foreach ( $tools as $tool ) {
			$cat = isset( $tool['annotations']['category'] ) ? $tool['annotations']['category'] : 'site';
			if ( ! isset( $counts[ $cat ] ) ) {
				$counts[ $cat ] = 0;
			}
			++$counts[ $cat ];
		}

		return $counts;
	}

	/**
	 * AJAX: Toggle tool category.
	 */
	public function ajax_toggle_category() {
		check_ajax_referer( 'spai_tools_nonce', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$category = isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : '';
		$enabled  = isset( $_POST['enabled'] ) ? sanitize_text_field( wp_unslash( $_POST['enabled'] ) ) : '1';

		if ( empty( $category ) ) {
			wp_send_json_error( array( 'message' => __( 'Category is required.', 'mumega-mcp' ) ) );
		}

		// Validate category slug.
		$valid_categories = array_keys( self::get_category_meta() );
		if ( ! in_array( $category, $valid_categories, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid category.', 'mumega-mcp' ) ) );
		}

		$disabled = get_option( 'spai_disabled_tool_categories', array() );
		if ( ! is_array( $disabled ) ) {
			$disabled = array();
		}

		if ( '1' === $enabled ) {
			// Enable: remove from disabled list.
			$disabled = array_values( array_diff( $disabled, array( $category ) ) );
		} else {
			// Disable: add to disabled list.
			if ( ! in_array( $category, $disabled, true ) ) {
				$disabled[] = $category;
			}
		}

		update_option( 'spai_disabled_tool_categories', $disabled );

		wp_send_json_success( array(
			'message'  => '1' === $enabled
				? sprintf(
					/* translators: %s: category name */
					__( '%s tools enabled.', 'mumega-mcp' ),
					$category
				)
				: sprintf(
					/* translators: %s: category name */
					__( '%s tools disabled.', 'mumega-mcp' ),
					$category
				),
			'disabled' => $disabled,
		) );
	}
}
