<?php
/**
 * Site REST Controller
 *
 * @package MumegaMCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site info REST controller.
 */
class Spai_REST_Site extends Spai_REST_API {

	/**
	 * Core handler.
	 *
	 * @var Spai_Core
	 */
	private $core;

	/**
	 * Design reference library.
	 *
	 * @var Spai_Design_References
	 */
	private $design_references;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->core              = new Spai_Core();
		$this->design_references = new Spai_Design_References();
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		// Site info
		register_rest_route(
			$this->namespace,
			'/site-info',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_site_info' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Introspection (self-describing API / MCP metadata).
		register_rest_route(
			$this->namespace,
			'/introspect',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_introspect' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// AI onboarding briefing.
		register_rest_route(
			$this->namespace,
			'/onboard',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_onboard' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Design references library.
		register_rest_route(
			$this->namespace,
			'/design-references',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_design_references' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array_merge(
						$this->get_pagination_args(),
						array(
							'query' => array(
								'description' => __( 'Optional text query.', 'mumega-mcp' ),
								'type'        => 'string',
							),
							'page_intent' => array(
								'description' => __( 'Optional page intent filter.', 'mumega-mcp' ),
								'type'        => 'string',
							),
							'archetype_class' => array(
								'description' => __( 'Optional archetype class filter.', 'mumega-mcp' ),
								'type'        => 'string',
							),
							'style' => array(
								'description' => __( 'Optional style filter.', 'mumega-mcp' ),
								'type'        => 'string',
							),
							'source_type' => array(
								'description' => __( 'Optional source type filter.', 'mumega-mcp' ),
								'type'        => 'string',
							),
						)
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_design_reference' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/design-references/(?P<id>[A-Za-z0-9_-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_design_reference' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_design_reference' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Settings (GET and PUT)
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Plugin settings (GET and PUT).
		register_rest_route(
			$this->namespace,
			'/plugin-settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_plugin_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_plugin_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Options (front page, blog page, reading settings)
		register_rest_route(
			$this->namespace,
			'/options',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_options' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_options' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Favicon (site icon)
		register_rest_route(
			$this->namespace,
			'/favicon',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_favicon' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_favicon' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_favicon' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Analytics
		register_rest_route(
			$this->namespace,
			'/analytics',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_analytics' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'days' => array(
							'description' => __( 'Number of days.', 'mumega-mcp' ),
							'type'        => 'integer',
							'default'     => 30,
							'minimum'     => 1,
							'maximum'     => 365,
						),
					),
				),
			)
		);

		// Plugin detection
		register_rest_route(
			$this->namespace,
			'/plugins',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_plugins' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Content search (posts/pages)
		register_rest_route(
			$this->namespace,
			'/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_content' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'query'    => array(
							'description' => __( 'Search query string.', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'q'        => array(
							'description' => __( 'Alias for query string.', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'type'     => array(
							'description' => __( 'Content type filter (post, page, or any).', 'mumega-mcp' ),
							'type'        => 'string',
							'default'     => 'any',
						),
						'status'   => array(
							'description' => __( 'Post status filter.', 'mumega-mcp' ),
							'type'        => 'string',
							'default'     => 'publish',
						),
						'per_page' => array(
							'description' => __( 'Results per page.', 'mumega-mcp' ),
							'type'        => 'integer',
							'default'     => 10,
							'minimum'     => 1,
							'maximum'     => 50,
						),
						'page'     => array(
							'description' => __( 'Current page.', 'mumega-mcp' ),
							'type'        => 'integer',
							'default'     => 1,
							'minimum'     => 1,
						),
					),
				),
			)
		);

		// Content fetch by ID or URL
		register_rest_route(
			$this->namespace,
			'/fetch',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'fetch_content' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id'              => array(
							'description' => __( 'Post or page ID.', 'mumega-mcp' ),
							'type'        => 'integer',
						),
						'url'             => array(
							'description' => __( 'Canonical post/page URL.', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'type'            => array(
							'description' => __( 'Expected content type (post, page, or any).', 'mumega-mcp' ),
							'type'        => 'string',
							'default'     => 'any',
						),
						'include_content' => array(
							'description' => __( 'Include full content body in response.', 'mumega-mcp' ),
							'type'        => 'boolean',
							'default'     => true,
						),
					),
				),
			)
		);

		// Guides (list topics or get specific topic via ?topic=...)
		register_rest_route(
			$this->namespace,
			'/guides',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_guides' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'topic' => array(
							'description' => __( 'Guide topic slug. Omit to list all available topics.', 'mumega-mcp' ),
							'type'        => 'string',
						),
					),
				),
			)
		);

		// Guides (get specific topic via path)
		register_rest_route(
			$this->namespace,
			'/guides/(?P<topic>[a-z_]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_guide_topic' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'topic' => array(
							'description' => __( 'Guide topic slug.', 'mumega-mcp' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
			)
		);

		// Workflows (get specific workflow)
		register_rest_route(
			$this->namespace,
			'/workflows/(?P<name>[a-z_]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_workflow' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'name' => array(
							'description' => __( 'Workflow name.', 'mumega-mcp' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
			)
		);

		// Deterministic agent playbook contracts.
		register_rest_route(
			$this->namespace,
			'/agent-playbooks',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_agent_playbook' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'name' => array(
							'description'       => __( 'Optional playbook name.', 'mumega-mcp' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
			)
		);

		// Content coherence score and recommendations.
		register_rest_route(
			$this->namespace,
			'/content-coherence',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_content_coherence' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Categories
		register_rest_route(
			$this->namespace,
			'/categories',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_categories' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'per_page' => array(
							'description' => __( 'Results per page.', 'mumega-mcp' ),
							'type'        => 'integer',
							'default'     => 100,
							'minimum'     => 1,
							'maximum'     => 200,
						),
						'search'   => array(
							'description' => __( 'Search term.', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'parent'   => array(
							'description' => __( 'Parent category ID.', 'mumega-mcp' ),
							'type'        => 'integer',
						),
					),
				),
			)
		);

		// Tags
		register_rest_route(
			$this->namespace,
			'/tags',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_tags' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'per_page' => array(
							'description' => __( 'Results per page.', 'mumega-mcp' ),
							'type'        => 'integer',
							'default'     => 100,
							'minimum'     => 1,
							'maximum'     => 200,
						),
						'search'   => array(
							'description' => __( 'Search term.', 'mumega-mcp' ),
							'type'        => 'string',
						),
					),
				),
			)
		);

		// OAuth token issuance (client credentials)
		register_rest_route(
			$this->namespace,
			'/oauth/token',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'issue_oauth_token' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'grant_type'    => array(
							'description' => __( 'OAuth grant type.', 'mumega-mcp' ),
							'type'        => 'string',
							'default'     => 'client_credentials',
						),
						'client_id'     => array(
							'description' => __( 'OAuth client ID.', 'mumega-mcp' ),
							'type'        => 'string',
							'required'    => true,
						),
						'client_secret' => array(
							'description' => __( 'OAuth client secret.', 'mumega-mcp' ),
							'type'        => 'string',
							'required'    => true,
						),
						'scope'         => array(
							'description' => __( 'Space-separated scopes (read write admin).', 'mumega-mcp' ),
							'type'        => 'string',
						),
					),
				),
			)
		);

		// Rate limit status
		register_rest_route(
			$this->namespace,
			'/rate-limit',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_rate_limit_status' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_rate_limit_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'enabled'             => array(
							'description' => __( 'Enable or disable rate limiting.', 'mumega-mcp' ),
							'type'        => 'boolean',
						),
						'requests_per_minute' => array(
							'description' => __( 'Requests allowed per minute.', 'mumega-mcp' ),
							'type'        => 'integer',
							'minimum'     => 1,
						),
						'requests_per_hour'   => array(
							'description' => __( 'Requests allowed per hour.', 'mumega-mcp' ),
							'type'        => 'integer',
							'minimum'     => 1,
						),
						'burst_limit'         => array(
							'description' => __( 'Requests allowed in short burst window.', 'mumega-mcp' ),
							'type'        => 'integer',
							'minimum'     => 1,
						),
						'whitelist'           => array(
							'description' => __( 'Identifiers to bypass rate limiting.', 'mumega-mcp' ),
							'type'        => 'array',
							'items'       => array(
								'type' => 'string',
							),
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/rate-limit/reset',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'reset_rate_limit' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'identifier' => array(
							'description' => __( 'Rate-limit identifier to reset (for example: key:<id> or IP).', 'mumega-mcp' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
			)
		);

		// Scoped API keys (admin scope/capability required)
		register_rest_route(
			$this->namespace,
			'/api-keys',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_api_keys' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'include_revoked' => array(
							'description' => __( 'Include revoked keys.', 'mumega-mcp' ),
							'type'        => 'boolean',
							'default'     => false,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_api_key' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'label'  => array(
							'description' => __( 'Key label.', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'scopes' => array(
							'description' => __( 'Scopes for key (read, write, admin).', 'mumega-mcp' ),
							'type'        => 'array',
							'items'       => array(
								'type' => 'string',
							),
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/api-keys/(?P<id>[a-z0-9\\-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'revoke_api_key' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Self-update check and trigger (#87)
		register_rest_route(
			$this->namespace,
			'/update',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'check_update' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'trigger_update' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		if ( ! defined( 'SPAI_WPORG_BUILD' ) ) {
			// Custom CSS endpoints (not available in WP.org build).
			register_rest_route(
				$this->namespace,
				'/custom-css',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_custom_css' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'set_custom_css' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => array(
							'css'  => array(
								'description' => __( 'CSS code to set or append.', 'mumega-mcp' ),
								'type'        => 'string',
								'required'    => true,
							),
							'mode' => array(
								'description' => __( 'How to apply: "replace" overwrites all CSS, "append" adds to existing.', 'mumega-mcp' ),
								'type'        => 'string',
								'default'     => 'append',
								'enum'        => array( 'replace', 'append' ),
							),
						),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_custom_css' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
				)
			);

			// Custom CSS length (lightweight check without full CSS body).
			register_rest_route(
				$this->namespace,
				'/custom-css/length',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_css_length' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
				)
			);
		} // end if ( ! defined( 'SPAI_WPORG_BUILD' ) )

		// Post meta
		register_rest_route(
			$this->namespace,
			'/post-meta/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_post_meta_handler' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id'  => array(
							'description' => __( 'Post or page ID.', 'mumega-mcp' ),
							'type'        => 'integer',
							'required'    => true,
						),
						'key' => array(
							'description' => __( 'Specific meta key to retrieve.', 'mumega-mcp' ),
							'type'        => 'string',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'set_post_meta_handler' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id'    => array(
							'description' => __( 'Post or page ID.', 'mumega-mcp' ),
							'type'        => 'integer',
							'required'    => true,
						),
						'key'   => array(
							'description' => __( 'Meta key to set.', 'mumega-mcp' ),
							'type'        => 'string',
							'required'    => true,
						),
						'value' => array(
							'description' => __( 'Meta value to set.', 'mumega-mcp' ),
							'required'    => true,
						),
					),
				),
			)
		);

		// Site Context (AI brief / style guide).
		register_rest_route(
			$this->namespace,
			'/site-context',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_site_context' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'scope' => array(
							'description' => __( 'Optional context scope such as page or product.', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'archetype_class' => array(
							'description' => __( 'Optional archetype class for inherited context lookup.', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'style' => array(
							'description' => __( 'Optional archetype style for inherited context lookup.', 'mumega-mcp' ),
							'type'        => 'string',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'set_site_context' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'context' => array(
							'description' => __( 'Site context markdown text (AI brief, style guide, rules).', 'mumega-mcp' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
			)
		);

		// Coherent site-state snapshot for deterministic agent starts.
		register_rest_route(
			$this->namespace,
			'/site-state',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_site_state' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'graph_limit' => array(
							'description'       => __( 'Maximum content records to inspect for graph health.', 'mumega-mcp' ),
							'type'              => 'integer',
							'default'           => 100,
							'minimum'           => 1,
							'maximum'           => 250,
							'sanitize_callback' => 'absint',
						),
						'event_limit' => array(
							'description'       => __( 'Maximum recent events to include.', 'mumega-mcp' ),
							'type'              => 'integer',
							'default'           => 20,
							'minimum'           => 1,
							'maximum'           => 50,
							'sanitize_callback' => 'absint',
						),
						'include_drafts' => array(
							'description'       => __( 'Include draft/private content in graph health.', 'mumega-mcp' ),
							'type'              => 'boolean',
							'default'           => false,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
						'include_plugins' => array(
							'description'       => __( 'Include active plugin file names in capability output.', 'mumega-mcp' ),
							'type'              => 'boolean',
							'default'           => false,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);

		// Internal content graph for SEO and internal linking workflows.
		register_rest_route(
			$this->namespace,
			'/content-graph',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_content_graph' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'post_types' => array(
							'description' => __( 'Comma-separated post types to include.', 'mumega-mcp' ),
							'type'        => 'string',
							'default'     => 'page,post',
						),
						'limit' => array(
							'description'       => __( 'Maximum number of content nodes.', 'mumega-mcp' ),
							'type'              => 'integer',
							'default'           => 100,
							'minimum'           => 1,
							'maximum'           => 500,
							'sanitize_callback' => 'absint',
						),
						'include_drafts' => array(
							'description'       => __( 'Include draft/private content nodes.', 'mumega-mcp' ),
							'type'              => 'boolean',
							'default'           => false,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);

		// Internal link suggestions from the content graph.
		register_rest_route(
			$this->namespace,
			'/content-graph/suggestions',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_internal_link_suggestions' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'source_id' => array(
							'description'       => __( 'Source post or page ID that needs internal links.', 'mumega-mcp' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'post_types' => array(
							'description' => __( 'Comma-separated post types to include.', 'mumega-mcp' ),
							'type'        => 'string',
							'default'     => 'page,post',
						),
						'limit' => array(
							'description'       => __( 'Maximum number of graph nodes to inspect.', 'mumega-mcp' ),
							'type'              => 'integer',
							'default'           => 100,
							'minimum'           => 1,
							'maximum'           => 500,
							'sanitize_callback' => 'absint',
						),
						'max_suggestions' => array(
							'description'       => __( 'Maximum suggestions to return.', 'mumega-mcp' ),
							'type'              => 'integer',
							'default'           => 5,
							'minimum'           => 1,
							'maximum'           => 20,
							'sanitize_callback' => 'absint',
						),
						'include_drafts' => array(
							'description'       => __( 'Include draft/private content candidates.', 'mumega-mcp' ),
							'type'              => 'boolean',
							'default'           => false,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);

		// Approval-first internal link application from graph targets.
		register_rest_route(
			$this->namespace,
			'/content-graph/apply-link',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'apply_internal_link' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'source_id' => array(
							'description'       => __( 'Source post or page ID to update.', 'mumega-mcp' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'target_id' => array(
							'description'       => __( 'Existing graph target post or page ID.', 'mumega-mcp' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'anchor' => array(
							'description' => __( 'Optional link anchor text. Defaults to target title.', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'approval_required' => array(
							'description'       => __( 'Create an approval request instead of saving immediately. Defaults to true.', 'mumega-mcp' ),
							'type'              => 'boolean',
							'default'           => true,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
						'approval_note' => array(
							'description' => __( 'Optional human review note.', 'mumega-mcp' ),
							'type'        => 'string',
						),
					),
				),
			)
		);

		// Read-only internal link validation for SEO and publishing checks.
		register_rest_route(
			$this->namespace,
			'/content-graph/validate-links',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'validate_internal_links' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'post_types' => array(
							'description' => __( 'Comma-separated post types to include.', 'mumega-mcp' ),
							'type'        => 'string',
							'default'     => 'page,post',
						),
						'limit' => array(
							'description'       => __( 'Maximum number of content nodes to inspect.', 'mumega-mcp' ),
							'type'              => 'integer',
							'default'           => 100,
							'minimum'           => 1,
							'maximum'           => 500,
							'sanitize_callback' => 'absint',
						),
						'include_drafts' => array(
							'description'       => __( 'Include draft/private source content.', 'mumega-mcp' ),
							'type'              => 'boolean',
							'default'           => false,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);

		// SEO pre-publish readiness checks.
		register_rest_route(
			$this->namespace,
			'/seo/readiness/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'validate_seo_readiness' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'description'       => __( 'Post or page ID to validate.', 'mumega-mcp' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// SEO site audit summary.
		register_rest_route(
			$this->namespace,
			'/seo/audit',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'audit_seo_site' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'post_types' => array(
							'description' => __( 'Comma-separated post types to include.', 'mumega-mcp' ),
							'type'        => 'string',
							'default'     => 'page,post',
						),
						'limit' => array(
							'description'       => __( 'Maximum number of URLs to audit.', 'mumega-mcp' ),
							'type'              => 'integer',
							'default'           => 25,
							'minimum'           => 1,
							'maximum'           => 50,
							'sanitize_callback' => 'absint',
						),
						'include_drafts' => array(
							'description'       => __( 'Include draft/private content.', 'mumega-mcp' ),
							'type'              => 'boolean',
							'default'           => false,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
						'store' => array(
							'description'       => __( 'Store this audit run and normalized issue records.', 'mumega-mcp' ),
							'type'              => 'boolean',
							'default'           => false,
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);

		// Stored SEO issues.
		register_rest_route(
			$this->namespace,
			'/seo/issues',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_seo_issues' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'status' => array(
							'description'       => __( 'Issue status filter: open or resolved.', 'mumega-mcp' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						),
						'severity' => array(
							'description'       => __( 'Severity filter: error, warning, or info.', 'mumega-mcp' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						),
						'category' => array(
							'description'       => __( 'Issue category filter.', 'mumega-mcp' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						),
						'post_id' => array(
							'description'       => __( 'Post ID filter.', 'mumega-mcp' ),
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'run_id' => array(
							'description'       => __( 'Audit run ID filter.', 'mumega-mcp' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						),
						'limit' => array(
							'description'       => __( 'Maximum issues to return.', 'mumega-mcp' ),
							'type'              => 'integer',
							'default'           => 50,
							'minimum'           => 1,
							'maximum'           => 200,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Approval-safe SEO autofix plan.
		register_rest_route(
			$this->namespace,
			'/seo/autofix-plan',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_seo_autofix_plan' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'severity' => array(
							'description'       => __( 'Severity filter: error, warning, or info.', 'mumega-mcp' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						),
						'category' => array(
							'description'       => __( 'Issue category filter.', 'mumega-mcp' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						),
						'post_id' => array(
							'description'       => __( 'Post ID filter.', 'mumega-mcp' ),
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'run_id' => array(
							'description'       => __( 'Audit run ID filter.', 'mumega-mcp' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						),
						'issue_id' => array(
							'description'       => __( 'Specific stored issue ID.', 'mumega-mcp' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						),
						'limit' => array(
							'description'       => __( 'Maximum issues to inspect.', 'mumega-mcp' ),
							'type'              => 'integer',
							'default'           => 50,
							'minimum'           => 1,
							'maximum'           => 200,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Search Console/Bing/manual search performance import.
		register_rest_route(
			$this->namespace,
			'/seo/search-performance/import',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_search_performance' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'provider' => array(
							'description'       => __( 'Provider slug such as google_search_console, bing_webmaster, or manual.', 'mumega-mcp' ),
							'type'              => 'string',
							'default'           => 'manual',
							'sanitize_callback' => 'sanitize_key',
						),
						'source' => array(
							'description'       => __( 'Optional source label for the export or import.', 'mumega-mcp' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		// Search performance trends and reporting.
		register_rest_route(
			$this->namespace,
			'/seo/search-performance',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_search_performance' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'provider' => array(
							'description'       => __( 'Provider filter.', 'mumega-mcp' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						),
						'url' => array(
							'description'       => __( 'Exact URL filter.', 'mumega-mcp' ),
							'type'              => 'string',
							'sanitize_callback' => 'esc_url_raw',
						),
						'query' => array(
							'description'       => __( 'Search query contains filter.', 'mumega-mcp' ),
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'days' => array(
							'description'       => __( 'Lookback window in days.', 'mumega-mcp' ),
							'type'              => 'integer',
							'default'           => 90,
							'minimum'           => 1,
							'maximum'           => 365,
							'sanitize_callback' => 'absint',
						),
						'limit' => array(
							'description'       => __( 'Maximum grouped rows to return.', 'mumega-mcp' ),
							'type'              => 'integer',
							'default'           => 20,
							'minimum'           => 1,
							'maximum'           => 100,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// WooCommerce SEO intelligence.
		register_rest_route(
			$this->namespace,
			'/seo/woocommerce',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_woocommerce_seo_report' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'status' => array(
							'description'       => __( 'Product status filter: publish, draft, private, or any.', 'mumega-mcp' ),
							'type'              => 'string',
							'default'           => 'publish',
							'sanitize_callback' => 'sanitize_key',
						),
						'limit' => array(
							'description'       => __( 'Maximum products to inspect.', 'mumega-mcp' ),
							'type'              => 'integer',
							'default'           => 25,
							'minimum'           => 1,
							'maximum'           => 100,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Content quality and AI-search citation readiness audit.
		register_rest_route(
			$this->namespace,
			'/seo/content-quality/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'audit_content_quality' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'description'       => __( 'Post or page ID to audit.', 'mumega-mcp' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Structured data inventory and validation.
		register_rest_route(
			$this->namespace,
			'/seo/structured-data/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'validate_structured_data' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'description'       => __( 'Post or page ID to validate.', 'mumega-mcp' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Media SEO audit.
		register_rest_route(
			$this->namespace,
			'/seo/media/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'audit_media_seo' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'description'       => __( 'Post or page ID to audit.', 'mumega-mcp' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Theme info
		register_rest_route(
			$this->namespace,
			'/theme-info',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_theme_info' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Flush permalinks
		register_rest_route(
			$this->namespace,
			'/flush-permalinks',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'flush_permalinks' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Site health
		register_rest_route(
			$this->namespace,
			'/site-health',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_site_health' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Taxonomy terms CRUD
		register_rest_route(
			$this->namespace,
			'/terms',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_term' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'taxonomy'    => array(
							'description' => __( 'Taxonomy name.', 'mumega-mcp' ),
							'type'        => 'string',
							'required'    => true,
						),
						'name'        => array(
							'description' => __( 'Term name.', 'mumega-mcp' ),
							'type'        => 'string',
							'required'    => true,
						),
						'slug'        => array(
							'description' => __( 'Term slug.', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'description' => array(
							'description' => __( 'Term description.', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'parent'      => array(
							'description' => __( 'Parent term ID.', 'mumega-mcp' ),
							'type'        => 'integer',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/terms/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_term' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'taxonomy'    => array(
							'description' => __( 'Taxonomy name.', 'mumega-mcp' ),
							'type'        => 'string',
							'required'    => true,
						),
						'name'        => array(
							'description' => __( 'New term name.', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'slug'        => array(
							'description' => __( 'New term slug.', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'description' => array(
							'description' => __( 'New term description.', 'mumega-mcp' ),
							'type'        => 'string',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_term' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'taxonomy' => array(
							'description' => __( 'Taxonomy name.', 'mumega-mcp' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
			)
		);

		// Rendered HTML (fetch what the browser sees).
		register_rest_route(
			$this->namespace,
			'/rendered-html',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_rendered_html' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id'        => array(
							'description' => __( 'Post or page ID to fetch rendered HTML for.', 'mumega-mcp' ),
							'type'        => 'integer',
						),
						'url'       => array(
							'description' => __( 'URL to fetch (same-host only for SSRF safety).', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'selector'  => array(
							'description' => __( 'CSS selector to extract (tag, .class, or #id).', 'mumega-mcp' ),
							'type'        => 'string',
						),
						'max_bytes' => array(
							'description' => __( 'Maximum response size in bytes (default 51200, max 204800).', 'mumega-mcp' ),
							'type'        => 'integer',
							'default'     => 51200,
						),
					),
				),
			)
		);

		// Single option get/update (whitelisted keys only).
		register_rest_route(
			$this->namespace,
			'/option',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_option_handler' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'key' => array(
							'description' => __( 'Option key to retrieve.', 'mumega-mcp' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_option_handler' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'key'   => array(
							'description' => __( 'Option key to update.', 'mumega-mcp' ),
							'type'        => 'string',
							'required'    => true,
						),
						'value' => array(
							'description' => __( 'Option value to set.', 'mumega-mcp' ),
							'required'    => true,
						),
					),
				),
			)
		);

		// Multisite routes (only registered on multisite installations).
		if ( is_multisite() ) {
			register_rest_route(
				$this->namespace,
				'/network/sites',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_network_sites' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => array(
							'per_page' => array(
								'description' => __( 'Results per page.', 'mumega-mcp' ),
								'type'        => 'integer',
								'default'     => 50,
								'minimum'     => 1,
								'maximum'     => 200,
							),
							'search'   => array(
								'description' => __( 'Search term.', 'mumega-mcp' ),
								'type'        => 'string',
							),
						),
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/network/switch',
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'switch_site' ),
						'permission_callback' => array( $this, 'check_permission' ),
						'args'                => array(
							'blog_id' => array(
								'description' => __( 'Blog ID to switch to.', 'mumega-mcp' ),
								'type'        => 'integer',
								'required'    => true,
							),
						),
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/network/stats',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_network_stats' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
				)
			);
		}
	}

	/**
	 * Get site info.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_site_info( $request ) {
		$this->log_activity( 'site_info', $request );

		$info = $this->core->get_site_info();

		return $this->success_response( $info );
	}

	/**
	 * Get API/MCP introspection data to help clients self-configure.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_introspect( $request ) {
		$this->log_activity( 'introspect', $request );

		if ( ! class_exists( 'Spai_REST_MCP' ) ) {
			return $this->success_response(
				array(
					'plugin'  => array(
						'name'    => 'Mumega MCP',
						'version' => defined( 'SPAI_VERSION' ) ? SPAI_VERSION : null,
					),
					'message' => 'MCP controller not available.',
				)
			);
		}

		$mcp = new Spai_REST_MCP();
		if ( ! method_exists( $mcp, 'get_introspection_data' ) ) {
			return $this->success_response(
				array(
					'plugin'  => array(
						'name'    => 'Mumega MCP',
						'version' => defined( 'SPAI_VERSION' ) ? SPAI_VERSION : null,
					),
					'message' => 'Introspection is not supported in this version.',
				)
			);
		}

		$data = $mcp->get_introspection_data();

		// Include site context if set.
		$site_context = get_option( 'spai_site_context', '' );
		if ( '' !== $site_context ) {
			$data['site_context'] = $site_context;
		}

		return $this->success_response( $data );
	}

	/**
	 * Get AI onboarding briefing.
	 *
	 * Returns a comprehensive first-connection package including site identity,
	 * content inventory, active integrations, available tools, site context,
	 * recommended first actions, and a quick reference card.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_onboard( $request ) {
		$this->log_activity( 'onboard', $request );

		$site_info    = $this->core->get_site_info();
		$capabilities = $site_info['capabilities'] ?? array();

		// 1. Site identity.
		$identity = array(
			'name'        => $site_info['name'] ?? get_bloginfo( 'name' ),
			'description' => $site_info['description'] ?? get_bloginfo( 'description' ),
			'url'         => $site_info['url'] ?? home_url(),
			'admin_url'   => $site_info['admin_url'] ?? admin_url(),
			'language'    => $site_info['language'] ?? get_locale(),
			'timezone'    => $site_info['timezone'] ?? wp_timezone_string(),
			'is_rtl'      => $site_info['is_rtl'] ?? false,
			'wp_version'  => $site_info['wp_version'] ?? $GLOBALS['wp_version'],
			'theme'       => $site_info['theme'] ?? array(),
			'plugin'      => $site_info['plugin'] ?? array(
				'name'    => 'Mumega MCP',
				'version' => defined( 'SPAI_VERSION' ) ? SPAI_VERSION : null,
			),
		);

		// 2. Content inventory.
		$counts     = wp_count_posts( 'post' );
		$page_counts = wp_count_posts( 'page' );

		$inventory = array(
			'posts'   => array(
				'published' => (int) ( $counts->publish ?? 0 ),
				'drafts'    => (int) ( $counts->draft ?? 0 ),
				'total'     => (int) ( ( $counts->publish ?? 0 ) + ( $counts->draft ?? 0 ) + ( $counts->private ?? 0 ) ),
			),
			'pages'   => array(
				'published' => (int) ( $page_counts->publish ?? 0 ),
				'drafts'    => (int) ( $page_counts->draft ?? 0 ),
				'total'     => (int) ( ( $page_counts->publish ?? 0 ) + ( $page_counts->draft ?? 0 ) + ( $page_counts->private ?? 0 ) ),
			),
			'media'   => array_sum( array_map( 'intval', (array) wp_count_attachments() ) ),
			'categories' => (int) wp_count_terms( 'category' ),
			'tags'       => (int) wp_count_terms( 'post_tag' ),
		);

		// Add product count if WooCommerce is active.
		if ( ! empty( $capabilities['woocommerce'] ) && post_type_exists( 'product' ) ) {
			$product_counts          = wp_count_posts( 'product' );
			$inventory['products'] = array(
				'published' => (int) ( $product_counts->publish ?? 0 ),
				'drafts'    => (int) ( $product_counts->draft ?? 0 ),
			);
		}

		// Recent updates (last 5 modified posts/pages).
		$recent_posts = get_posts(
			array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'posts_per_page' => 5,
			)
		);

		$recent_updates = array();
		foreach ( $recent_posts as $p ) {
			$recent_updates[] = array(
				'id'       => $p->ID,
				'title'    => $p->post_title,
				'type'     => $p->post_type,
				'status'   => $p->post_status,
				'modified' => $p->post_modified,
			);
		}
		$inventory['recent_updates'] = $recent_updates;
		$design_reference_inventory  = $this->design_references->list_references(
			array(
				'per_page' => 3,
				'page'     => 1,
			)
		);
		$inventory['design_references'] = array(
			'total'  => (int) ( $design_reference_inventory['total'] ?? 0 ),
			'recent' => $design_reference_inventory['references'] ?? array(),
		);

		// 3. Active integrations.
		$integrations = array();

		if ( ! empty( $capabilities['elementor'] ) ) {
			$integrations['elementor'] = array(
				'active'      => true,
				'version'     => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : 'unknown',
				'pro'         => ! empty( $capabilities['elementor_pro'] ),
				'layout_mode' => $capabilities['elementor_layout_mode'] ?? 'section',
			);
		}

		if ( ! empty( $capabilities['gutenberg'] ) ) {
			$integrations['gutenberg'] = array(
				'active' => true,
			);
		}

		// SEO plugin detection.
		$seo_plugins = array(
			'yoast'    => 'Yoast SEO',
			'rankmath' => 'RankMath',
			'aioseo'   => 'AIOSEO',
			'seopress' => 'SEOPress',
		);
		foreach ( $seo_plugins as $key => $label ) {
			if ( ! empty( $capabilities[ $key ] ) ) {
				$integrations['seo'] = array(
					'active' => true,
					'plugin' => $label,
				);
				break;
			}
		}

		// Forms plugin detection.
		$form_plugins = array(
			'cf7'          => 'Contact Form 7',
			'wpforms'      => 'WPForms',
			'gravityforms' => 'Gravity Forms',
			'ninjaforms'   => 'Ninja Forms',
		);
		foreach ( $form_plugins as $key => $label ) {
			if ( ! empty( $capabilities[ $key ] ) ) {
				$integrations['forms'] = array(
					'active' => true,
					'plugin' => $label,
				);
				break;
			}
		}

		if ( ! empty( $capabilities['woocommerce'] ) ) {
			$integrations['woocommerce'] = array(
				'active' => true,
			);
		}

		$is_pro = class_exists( 'Spai_License' ) && Spai_License::get_instance()->is_pro();

		// 4. Available tools grouped by category.
		$tools_by_category = array();
		if ( class_exists( 'Spai_REST_MCP' ) ) {
			$mcp       = new Spai_REST_MCP();
			$all_tools = method_exists( $mcp, 'get_introspection_data' )
				? ( $mcp->get_introspection_data()['tools'] ?? array() )
				: array();

			foreach ( $all_tools as $tool ) {
				$cat  = $tool['annotations']['category'] ?? 'site';
				$name = $tool['name'] ?? '';
				if ( '' === $name ) {
					continue;
				}
				if ( ! isset( $tools_by_category[ $cat ] ) ) {
					$tools_by_category[ $cat ] = array();
				}
				$tools_by_category[ $cat ][] = array(
					'name'        => $name,
					'description' => $tool['description'] ?? '',
					'tier'        => ( ! empty( $tool['annotations']['tier'] ) ) ? $tool['annotations']['tier'] : 'free',
				);
			}
		}

		// 5. Site context.
		$site_context    = get_option( 'spai_site_context', '' );
		$context_section = array(
			'configured' => '' !== $site_context,
		);
		if ( '' !== $site_context ) {
			$context_section['context'] = $site_context;
		} else {
			$context_section['hint'] = 'No site context configured. Use wp_set_site_context to define design rules, color palette, typography, and layout guidelines.';
		}

		// 6. Recommended first actions.
		$actions = array();

		if ( '' === $site_context ) {
			$actions[] = array(
				'action'      => 'Set up site context',
				'tool'        => 'wp_set_site_context',
				'description' => 'Define your site design rules, color palette, typography, and layout guidelines so AI assistants follow your brand.',
			);
		}

		if ( $inventory['pages']['published'] === 0 ) {
			$actions[] = array(
				'action'      => 'Create your first page',
				'tool'        => 'wp_create_page',
				'description' => 'No published pages found. Create a homepage or landing page to get started.',
			);
		}

		if ( ! empty( $capabilities['elementor'] ) ) {
			$actions[] = array(
				'action'      => 'Review Elementor status',
				'tool'        => 'wp_elementor_status',
				'description' => 'Check which pages use Elementor and the current layout mode.',
			);
		}

		if ( isset( $integrations['seo'] ) ) {
			$actions[] = array(
				'action'      => 'Audit SEO metadata',
				'tool'        => 'wp_seo_status',
				'description' => 'Check SEO coverage across your pages and identify missing meta descriptions.',
			);
		}

		if ( $inventory['posts']['published'] > 0 ) {
			$actions[] = array(
				'action'      => 'Review recent content',
				'tool'        => 'wp_list_posts',
				'description' => 'Browse existing blog posts to understand current content.',
			);
		}

		// 7. Quick reference card — top 10 most-used tools.
		$quick_reference = array(
			array( 'tool' => 'wp_onboard', 'use' => 'First-connection site briefing (you are here)' ),
			array( 'tool' => 'wp_get_site_context', 'use' => 'Read site design rules and style guide' ),
			array( 'tool' => 'wp_list_pages', 'use' => 'List all pages with status and IDs' ),
			array( 'tool' => 'wp_list_posts', 'use' => 'List blog posts with filters' ),
			array( 'tool' => 'wp_create_page', 'use' => 'Create a new page' ),
			array( 'tool' => 'wp_search', 'use' => 'Search posts and pages by keyword' ),
			array( 'tool' => 'wp_fetch', 'use' => 'Get full content for a post or page by ID' ),
			array( 'tool' => 'wp_upload_media_from_url', 'use' => 'Upload an image from a URL' ),
			array( 'tool' => 'wp_detect_plugins', 'use' => 'Discover active plugins and capabilities' ),
			array( 'tool' => 'wp_site_info', 'use' => 'Get site name, URL, theme, and version info' ),
		);

		// Add Elementor tools to quick reference if active.
		if ( ! empty( $capabilities['elementor'] ) ) {
			$quick_reference[] = array( 'tool' => 'wp_get_elementor', 'use' => 'Read Elementor page builder data' );
			$quick_reference[] = array( 'tool' => 'wp_set_elementor', 'use' => 'Update Elementor page builder data' );
		}

		$data = array(
			'site_identity'       => $identity,
			'content_inventory'   => $inventory,
			'active_integrations' => $integrations,
			'available_tools'     => $tools_by_category,
			'site_context'        => $context_section,
			'recommended_actions' => $actions,
			'quick_reference'     => $quick_reference,
			'pro_active'          => $is_pro,
		);

		return $this->success_response( $data );
	}

	/**
	 * Get a lightweight internal content graph for agents.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_content_graph( $request ) {
		$this->log_activity( 'get_content_graph', $request );

		$post_types = $this->parse_graph_post_types( (string) $request->get_param( 'post_types' ) );
		$limit      = min( 500, max( 1, absint( $request->get_param( 'limit' ) ) ) );
		$graph      = $this->build_content_graph_data( $post_types, $limit, rest_sanitize_boolean( $request->get_param( 'include_drafts' ) ) );

		return $this->success_response( $graph );
	}

	/**
	 * Get coherent site state for deterministic agent starts.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_site_state( $request ) {
		$this->log_activity( 'get_site_state', $request );

		$snapshot = class_exists( 'Spai_Site_State' )
			? Spai_Site_State::get_snapshot(
				array(
					'graph_limit'     => min( 250, max( 1, absint( $request->get_param( 'graph_limit' ) ) ) ),
					'event_limit'     => min( 50, max( 1, absint( $request->get_param( 'event_limit' ) ) ) ),
					'include_drafts'  => rest_sanitize_boolean( $request->get_param( 'include_drafts' ) ),
					'include_plugins' => rest_sanitize_boolean( $request->get_param( 'include_plugins' ) ),
				)
			)
			: array();

		return $this->success_response( $snapshot );
	}

	/**
	 * Get internal link suggestions from the graph.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function get_internal_link_suggestions( $request ) {
		$source_id = absint( $request->get_param( 'source_id' ) );
		$source    = get_post( $source_id );

		if ( ! $source ) {
			return $this->error_response( 'not_found', 'Source post not found.', 404 );
		}

		$this->log_activity( 'suggest_internal_links', $request, array( 'source_id' => $source_id ) );

		$post_types      = $this->parse_graph_post_types( (string) $request->get_param( 'post_types' ) );
		$limit           = min( 500, max( 1, absint( $request->get_param( 'limit' ) ) ) );
		$max_suggestions = min( 20, max( 1, absint( $request->get_param( 'max_suggestions' ) ) ) );
		$graph           = $this->build_content_graph_data( $post_types, $limit, rest_sanitize_boolean( $request->get_param( 'include_drafts' ) ) );
		$nodes_by_id     = array();

		foreach ( $graph['nodes'] as $node ) {
			$nodes_by_id[ (int) $node['id'] ] = $node;
		}

		if ( empty( $nodes_by_id[ $source_id ] ) ) {
			return $this->error_response( 'source_not_in_graph', 'Source post is not included in the content graph.', 404 );
		}

		$already_linked = array();
		foreach ( $graph['edges'] as $edge ) {
			if ( 'content_link' === $edge['type'] && (int) $edge['from'] === $source_id ) {
				$already_linked[] = (int) $edge['to'];
			}
		}

		$source_node   = $nodes_by_id[ $source_id ];
		$source_tokens = $this->build_link_suggestion_tokens( $source_node, $source->post_content );
		$suggestions   = array();

		foreach ( $nodes_by_id as $candidate_id => $candidate ) {
			if ( $candidate_id === $source_id || in_array( $candidate_id, $already_linked, true ) || 'publish' !== $candidate['status'] ) {
				continue;
			}

			$candidate_post   = get_post( $candidate_id );
			$candidate_tokens = $this->build_link_suggestion_tokens( $candidate, $candidate_post ? $candidate_post->post_content : '' );
			$overlap          = array_values( array_intersect( $source_tokens, $candidate_tokens ) );
			$shared_terms     = array_values( array_intersect( $source_node['terms'], $candidate['terms'] ) );
			$score            = ( count( $overlap ) * 2 ) + ( count( $shared_terms ) * 3 );

			if ( $source_node['type'] === $candidate['type'] ) {
				$score++;
			}

			if ( 0 === (int) $candidate['inbound_count'] ) {
				$score += 2;
			}

			if ( $score < 3 ) {
				continue;
			}

			$anchor = $this->choose_internal_link_anchor( $candidate, $overlap );

			$suggestions[] = array(
				'target_id'         => $candidate_id,
				'title'             => $candidate['title'],
				'url'               => $candidate['url'],
				'anchor'            => $anchor,
				'score'             => $score,
				'reasons'           => array_values(
					array_filter(
						array(
							! empty( $overlap ) ? 'Shared topic terms: ' . implode( ', ', array_slice( $overlap, 0, 5 ) ) : '',
							! empty( $shared_terms ) ? 'Shared taxonomy terms: ' . implode( ', ', array_slice( $shared_terms, 0, 5 ) ) : '',
							0 === (int) $candidate['inbound_count'] ? 'Candidate has no inbound links in the current graph.' : '',
						)
					)
				),
				'approval_diff'     => array(
					'action'      => 'insert_internal_link',
					'source_id'   => $source_id,
					'target_id'   => $candidate_id,
					'link_html'   => sprintf( '<a href="%s">%s</a>', esc_url( $candidate['url'] ), esc_html( $anchor ) ),
					'insert_hint' => 'Place this link where the anchor topic is already discussed. Do not add unrelated or repeated links.',
				),
				'approval_required' => true,
			);
		}

		usort(
			$suggestions,
			function ( $a, $b ) {
				return (int) $b['score'] <=> (int) $a['score'];
			}
		);

		return $this->success_response(
			array(
				'source'      => array(
					'id'    => $source_id,
					'title' => $source_node['title'],
					'url'   => $source_node['url'],
				),
				'suggestions' => array_slice( $suggestions, 0, $max_suggestions ),
				'workflow'    => array(
					'read'     => 'Review suggested links and anchors before editing content.',
					'apply'    => 'Use wp_patch_block_section or wp_set_blocks with approval_required=true to add accepted links.',
					'guardrail' => 'Suggestions use existing graph URLs only; agents should not invent internal URLs.',
				),
			)
		);
	}

	/**
	 * Apply an accepted internal link suggestion through approval-first content update.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function apply_internal_link( $request ) {
		$source_id = absint( $request->get_param( 'source_id' ) );
		$target_id = absint( $request->get_param( 'target_id' ) );
		$source    = get_post( $source_id );
		$target    = get_post( $target_id );

		if ( ! $source ) {
			return $this->error_response( 'not_found', 'Source post not found.', 404 );
		}

		if ( ! $target || 'publish' !== $target->post_status ) {
			return $this->error_response( 'target_not_found', 'Target post must be an existing published graph node.', 404 );
		}

		if ( $source_id === $target_id ) {
			return $this->error_response( 'self_link_rejected', 'Internal link target cannot be the source post.', 400 );
		}

		$target_url = get_permalink( $target );
		if ( ! $target_url ) {
			return $this->error_response( 'target_url_unavailable', 'Target post does not have a permalink.', 400 );
		}

		$url_to_id = array(
			$this->normalize_internal_graph_url( $target_url ) => $target_id,
		);
		$existing_links = $this->extract_internal_links_from_content( $source->post_content, $url_to_id );
		if ( ! empty( $existing_links ) ) {
			return $this->error_response( 'duplicate_internal_link', 'Source post already links to this target.', 409 );
		}

		$anchor = trim( (string) $request->get_param( 'anchor' ) );
		if ( '' === $anchor ) {
			$anchor = get_the_title( $target );
		}

		$anchor = wp_strip_all_tags( $anchor );
		if ( '' === $anchor ) {
			return $this->error_response( 'missing_anchor', 'Internal link anchor cannot be empty.', 400 );
		}

		$link_block       = $this->build_internal_link_paragraph_block( $target_url, $anchor );
		$patched_content = rtrim( (string) $source->post_content ) . "\n\n" . $link_block;
		$approval_required = null === $request->get_param( 'approval_required' )
			? true
			: rest_sanitize_boolean( $request->get_param( 'approval_required' ) );
		$approval_note = (string) $request->get_param( 'approval_note' );

		if ( $approval_required ) {
			if ( ! class_exists( 'Spai_Approvals' ) ) {
				return $this->error_response( 'approvals_unavailable', 'Approval pipeline is not available.', 500 );
			}

			$approval = Spai_Approvals::create_post_content_request(
				$source_id,
				$patched_content,
				array(
					'title'    => sprintf( 'Add internal link from #%d to #%d', (int) $source_id, (int) $target_id ),
					'note'     => $approval_note,
					'tool'     => 'wp_apply_internal_link',
					'metadata' => array(
						'source_id' => $source_id,
						'target_id' => $target_id,
						'target_url' => $target_url,
						'anchor'    => $anchor,
						'placement' => 'append_related_paragraph',
					),
				)
			);

			if ( is_wp_error( $approval ) ) {
				return $approval;
			}

			$this->log_activity( 'request_internal_link_approval', $request, array( 'source_id' => $source_id, 'target_id' => $target_id, 'approval_id' => $approval['id'] ) );

			return $this->success_response(
				array(
					'success'  => true,
					'status'   => 'approval_required',
					'source_id' => $source_id,
					'target_id' => $target_id,
					'link'      => array(
						'url'    => $target_url,
						'anchor' => $anchor,
						'html'   => sprintf( '<a href="%s">%s</a>', esc_url( $target_url ), esc_html( $anchor ) ),
					),
					'approval' => $approval,
				),
				202
			);
		}

		$result = wp_update_post(
			array(
				'ID'           => $source_id,
				'post_content' => $patched_content,
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return $this->error_response( 'update_failed', $result->get_error_message(), 500 );
		}

		$this->log_activity( 'apply_internal_link', $request, array( 'source_id' => $source_id, 'target_id' => $target_id ) );

		return $this->success_response(
			array(
				'success'  => true,
				'source_id' => $source_id,
				'target_id' => $target_id,
				'link'      => array(
					'url'    => $target_url,
					'anchor' => $anchor,
					'html'   => sprintf( '<a href="%s">%s</a>', esc_url( $target_url ), esc_html( $anchor ) ),
				),
			)
		);
	}

	/**
	 * Validate existing internal links without mutating content.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function validate_internal_links( $request ) {
		$this->log_activity( 'validate_internal_links', $request );

		$post_types     = $this->parse_graph_post_types( (string) $request->get_param( 'post_types' ) );
		$limit          = min( 500, max( 1, absint( $request->get_param( 'limit' ) ) ) );
		$include_drafts = rest_sanitize_boolean( $request->get_param( 'include_drafts' ) );
		$graph          = $this->build_content_graph_data( $post_types, $limit, $include_drafts );
		$url_to_id      = array();
		$nodes_by_id    = array();
		$issues         = array();

		foreach ( $graph['nodes'] as $node ) {
			$nodes_by_id[ (int) $node['id'] ] = $node;
			$url_to_id[ $this->normalize_internal_graph_url( $node['url'] ) ] = (int) $node['id'];
		}

		foreach ( array_keys( $nodes_by_id ) as $source_id ) {
			$source = get_post( $source_id );
			if ( ! $source ) {
				continue;
			}

			$seen_targets = array();
			$links        = $this->extract_raw_anchor_links( $source->post_content );

			foreach ( $links as $link ) {
				$resolved = $this->resolve_internal_link_for_validation( $link['href'], $url_to_id );
				if ( null === $resolved ) {
					continue;
				}

				$target_id = (int) $resolved['target_id'];
				$target    = $target_id ? get_post( $target_id ) : null;

				if ( '' === trim( $link['anchor'] ) ) {
					$issues[] = $this->make_internal_link_issue( 'empty_anchor', 'warning', $source_id, $target_id, $link, __( 'Use descriptive anchor text for internal links.', 'mumega-mcp' ) );
				} elseif ( $this->is_weak_internal_link_anchor( $link['anchor'] ) ) {
					$issues[] = $this->make_internal_link_issue( 'weak_anchor', 'warning', $source_id, $target_id, $link, __( 'Replace generic anchor text with a descriptive phrase.', 'mumega-mcp' ) );
				}

				if ( ! $target ) {
					$issues[] = $this->make_internal_link_issue( 'missing_target', 'error', $source_id, 0, $link, __( 'Link points to an internal URL that does not resolve to known content.', 'mumega-mcp' ) );
					continue;
				}

				if ( $source_id === $target_id ) {
					$issues[] = $this->make_internal_link_issue( 'self_link', 'warning', $source_id, $target_id, $link, __( 'Remove self-links unless they point to a useful in-page anchor.', 'mumega-mcp' ) );
				}

				if ( 'publish' !== $target->post_status ) {
					$issues[] = $this->make_internal_link_issue( 'unpublished_target', 'error', $source_id, $target_id, $link, __( 'Link target is not published.', 'mumega-mcp' ) );
				}

				if ( isset( $seen_targets[ $target_id ] ) ) {
					$issues[] = $this->make_internal_link_issue( 'duplicate_target', 'warning', $source_id, $target_id, $link, __( 'Source content links to the same internal target more than once.', 'mumega-mcp' ) );
				}
				$seen_targets[ $target_id ] = true;

				$canonical = get_permalink( $target );
				if ( $canonical && $this->normalize_internal_graph_url( $canonical ) !== $this->normalize_internal_graph_url( $resolved['absolute'] ) ) {
					$issues[] = $this->make_internal_link_issue( 'non_canonical_url', 'warning', $source_id, $target_id, $link, __( 'Use the canonical permalink for this internal target.', 'mumega-mcp' ), array( 'canonical_url' => $canonical ) );
				}
			}
		}

		$error_count   = 0;
		$warning_count = 0;
		foreach ( $issues as $issue ) {
			if ( 'error' === $issue['severity'] ) {
				$error_count++;
			} elseif ( 'warning' === $issue['severity'] ) {
				$warning_count++;
			}
		}

		return $this->success_response(
			array(
				'summary' => array(
					'status'        => 0 === $error_count ? ( $warning_count > 0 ? 'warn' : 'pass' ) : 'fail',
					'issue_count'   => count( $issues ),
					'error_count'   => $error_count,
					'warning_count' => $warning_count,
					'node_count'    => count( $nodes_by_id ),
				),
				'issues'  => $issues,
				'workflow' => array(
					'read'  => 'Use before publishing or applying internal link suggestions.',
					'fix'   => 'Use approval-first Gutenberg edits to fix accepted issues.',
					'guard' => 'This endpoint is read-only and does not mutate content.',
				),
			)
		);
	}

	/**
	 * Validate SEO readiness for a single post before publishing/updating.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function validate_seo_readiness( $request ) {
		$post_id = absint( $request->get_param( 'id' ) );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return $this->error_response( 'not_found', 'Post not found.', 404 );
		}

		$this->log_activity( 'validate_seo_readiness', $request, array( 'post_id' => $post_id ) );

		$issues       = array();
		$content      = (string) $post->post_content;
		$text_content = trim( wp_strip_all_tags( $content ) );
		$title        = trim( get_the_title( $post ) );
		$headings     = $this->extract_heading_texts( $content );
		$h1_count     = 0;
		$last_level   = 0;

		if ( '' === $title ) {
			$issues[] = $this->make_seo_readiness_issue( 'missing_title', 'error', __( 'Post title is missing.', 'mumega-mcp' ), __( 'Add a clear, descriptive title.', 'mumega-mcp' ) );
		} elseif ( strlen( $title ) < 10 ) {
			$issues[] = $this->make_seo_readiness_issue( 'short_title', 'warning', __( 'Post title is very short.', 'mumega-mcp' ), __( 'Use a descriptive title that sets search intent clearly.', 'mumega-mcp' ) );
		}

		if ( '' === trim( (string) $post->post_name ) ) {
			$issues[] = $this->make_seo_readiness_issue( 'missing_slug', 'error', __( 'Slug is missing.', 'mumega-mcp' ), __( 'Set a readable URL slug before publishing.', 'mumega-mcp' ) );
		}

		if ( str_word_count( $text_content ) < 80 ) {
			$issues[] = $this->make_seo_readiness_issue( 'thin_content', 'warning', __( 'Content is thin.', 'mumega-mcp' ), __( 'Add enough useful copy to answer the page intent.', 'mumega-mcp' ) );
		}

		foreach ( $headings as $heading ) {
			if ( 1 === (int) $heading['level'] ) {
				$h1_count++;
			}

			if ( $last_level > 0 && (int) $heading['level'] > $last_level + 1 ) {
				$issues[] = $this->make_seo_readiness_issue( 'heading_order_skip', 'warning', __( 'Heading levels skip in the page outline.', 'mumega-mcp' ), __( 'Use a logical heading hierarchy without jumping levels.', 'mumega-mcp' ) );
				break;
			}

			$last_level = (int) $heading['level'];
		}

		if ( 0 === $h1_count ) {
			$issues[] = $this->make_seo_readiness_issue( 'missing_h1', 'warning', __( 'No H1 heading found in the content.', 'mumega-mcp' ), __( 'Add one visible H1 or confirm the theme renders the post title as H1.', 'mumega-mcp' ) );
		} elseif ( $h1_count > 1 ) {
			$issues[] = $this->make_seo_readiness_issue( 'multiple_h1', 'warning', __( 'Multiple H1 headings found.', 'mumega-mcp' ), __( 'Use one primary H1 and demote secondary headings.', 'mumega-mcp' ) );
		}

		$meta_description = $this->get_seo_meta_description( $post_id );
		if ( '' === $meta_description ) {
			$issues[] = $this->make_seo_readiness_issue( 'missing_meta_description', 'warning', __( 'Meta description is missing.', 'mumega-mcp' ), __( 'Add a concise meta description through the active SEO plugin or supported post meta.', 'mumega-mcp' ) );
		} elseif ( strlen( $meta_description ) < 50 || strlen( $meta_description ) > 170 ) {
			$issues[] = $this->make_seo_readiness_issue( 'meta_description_length', 'warning', __( 'Meta description length is outside the recommended range.', 'mumega-mcp' ), __( 'Aim for roughly 50-170 characters that summarize the page accurately.', 'mumega-mcp' ) );
		}

		foreach ( $this->extract_image_ids_from_content( $content ) as $image_id ) {
			if ( '' === trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ) ) {
				$issues[] = $this->make_seo_readiness_issue( 'missing_image_alt', 'warning', __( 'An image is missing alt text.', 'mumega-mcp' ), __( 'Add descriptive alt text or mark decorative images intentionally.', 'mumega-mcp' ), array( 'attachment_id' => $image_id ) );
			}
		}

		$graph_post_types = array_values( array_unique( array( 'page', 'post', $post->post_type ) ) );
		$graph            = $this->build_content_graph_data( $graph_post_types, 500, 'publish' !== $post->post_status );
		$node  = null;
		foreach ( $graph['nodes'] as $candidate ) {
			if ( (int) $candidate['id'] === $post_id ) {
				$node = $candidate;
				break;
			}
		}

		if ( $node && 0 === (int) $node['outbound_count'] ) {
			$issues[] = $this->make_seo_readiness_issue( 'no_internal_outbound_links', 'warning', __( 'No internal outbound links found.', 'mumega-mcp' ), __( 'Add relevant internal links from the content graph where useful.', 'mumega-mcp' ) );
		}

		if ( $node && 'publish' === $post->post_status && 'none' !== $node['orphan_severity'] ) {
			$issues[] = $this->make_seo_readiness_issue( 'orphan_content', 'warning', __( 'Published content appears orphaned in the graph.', 'mumega-mcp' ), __( 'Link to this page from a relevant hub, menu, archive, or related page.', 'mumega-mcp' ), array( 'orphan_severity' => $node['orphan_severity'] ) );
		}

		if ( $this->is_post_noindex( $post_id ) ) {
			$issues[] = $this->make_seo_readiness_issue( 'noindex_enabled', 'error', __( 'Post appears to be marked noindex.', 'mumega-mcp' ), __( 'Remove noindex before publishing content intended for search.', 'mumega-mcp' ) );
		}

		$canonical = $this->get_seo_canonical_url( $post_id );
		if ( '' !== $canonical && $this->normalize_internal_graph_url( $canonical ) !== $this->normalize_internal_graph_url( get_permalink( $post ) ) ) {
			$issues[] = $this->make_seo_readiness_issue( 'canonical_override', 'warning', __( 'Canonical URL points somewhere other than this permalink.', 'mumega-mcp' ), __( 'Confirm this canonical is intentional before publishing.', 'mumega-mcp' ), array( 'canonical_url' => $canonical ) );
		}

		$robots_txt = $this->get_robots_txt();
		if ( false !== stripos( $robots_txt, 'Disallow: /' ) ) {
			$issues[] = $this->make_seo_readiness_issue( 'robots_disallow_root', 'error', __( 'robots.txt appears to disallow the whole site.', 'mumega-mcp' ), __( 'Review robots.txt before publishing search-facing content.', 'mumega-mcp' ) );
		}

		if ( ! $this->site_has_sitemap_hint() ) {
			$issues[] = $this->make_seo_readiness_issue( 'sitemap_not_detected', 'info', __( 'Sitemap endpoint was not detected locally.', 'mumega-mcp' ), __( 'Confirm XML sitemaps are enabled in WordPress or your SEO plugin.', 'mumega-mcp' ) );
		}

		if ( ! $this->content_has_schema_hint( $content ) ) {
			$issues[] = $this->make_seo_readiness_issue( 'schema_hint_missing', 'info', __( 'No structured-data hint found in content.', 'mumega-mcp' ), __( 'Consider schema only when it accurately matches visible content.', 'mumega-mcp' ) );
		}

		$summary = $this->summarize_seo_readiness_issues( $issues );

		return $this->success_response(
			array(
				'post'    => array(
					'id'     => $post_id,
					'title'  => $title,
					'type'   => $post->post_type,
					'status' => $post->post_status,
					'url'    => get_permalink( $post ),
				),
				'summary' => $summary,
				'issues'  => $issues,
				'checks'  => array(
					'word_count'         => str_word_count( $text_content ),
					'heading_count'      => count( $headings ),
					'h1_count'           => $h1_count,
					'meta_description'   => $meta_description,
					'canonical_url'      => $canonical,
					'robots_txt_checked' => '' !== $robots_txt,
					'sitemap_hint'       => $this->site_has_sitemap_hint(),
				),
				'workflow' => array(
					'read'  => 'Use before publishing or after major agent edits.',
					'fix'   => 'Fix accepted issues through approval-first Gutenberg or SEO metadata edits.',
					'guard' => 'This endpoint is read-only and does not mutate content.',
				),
			)
		);
	}

	/**
	 * Run a read-only SEO site audit summary.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function audit_seo_site( $request ) {
		$this->log_activity( 'audit_seo_site', $request );

		$post_types     = $this->parse_graph_post_types( (string) $request->get_param( 'post_types' ) );
		$limit          = min( 50, max( 1, absint( $request->get_param( 'limit' ) ) ) );
		$include_drafts = rest_sanitize_boolean( $request->get_param( 'include_drafts' ) );
		$store          = rest_sanitize_boolean( $request->get_param( 'store' ) );
		$statuses       = $include_drafts ? array( 'publish', 'draft', 'private' ) : array( 'publish' );
		$posts          = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => $statuses,
				'posts_per_page' => $limit,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);

		$urls             = array();
		$issue_codes      = array();
		$category_counts  = array(
			'readiness'       => 0,
			'structured_data' => 0,
			'media'           => 0,
			'content_quality' => 0,
		);
		$total_errors     = 0;
		$total_warnings   = 0;
		$total_info       = 0;
		$critical_urls    = 0;
		$needs_review     = 0;

		foreach ( $posts as $post_id ) {
			$readiness       = $this->run_post_validator( 'validate_seo_readiness', $post_id, '/seo/readiness/' );
			$structured_data = $this->run_post_validator( 'validate_structured_data', $post_id, '/seo/structured-data/' );
			$media           = $this->run_post_validator( 'audit_media_seo', $post_id, '/seo/media/' );
			$content_quality = $this->run_post_validator( 'audit_content_quality', $post_id, '/seo/content-quality/' );
			$combined_issues = array_merge(
				$this->tag_seo_audit_issues( $readiness['issues'], 'readiness' ),
				$this->tag_seo_audit_issues( $structured_data['issues'], 'structured_data' ),
				$this->tag_seo_audit_issues( $media['issues'], 'media' ),
				$this->tag_seo_audit_issues( $content_quality['issues'], 'content_quality' )
			);
			$counts          = $this->count_issues_by_severity( $combined_issues );
			$score           = ( $counts['error'] * 100 ) + ( $counts['warning'] * 20 ) + ( $counts['info'] * 5 );
			$post            = get_post( $post_id );

			if ( $counts['error'] > 0 ) {
				$critical_urls++;
			} elseif ( $counts['warning'] > 0 ) {
				$needs_review++;
			}

			$total_errors   += $counts['error'];
			$total_warnings += $counts['warning'];
			$total_info     += $counts['info'];

			foreach ( $combined_issues as $issue ) {
				$code = $issue['code'];
				if ( ! isset( $issue_codes[ $code ] ) ) {
					$issue_codes[ $code ] = array(
						'code'     => $code,
						'count'    => 0,
						'severity' => $issue['severity'],
					);
				}
				$issue_codes[ $code ]['count']++;

				if ( isset( $issue['category'], $category_counts[ $issue['category'] ] ) ) {
					$category_counts[ $issue['category'] ]++;
				}
			}

			$urls[] = array(
				'id'           => (int) $post_id,
				'title'        => get_the_title( $post_id ),
				'type'         => $post ? $post->post_type : '',
				'status'       => $post ? $post->post_status : '',
				'url'          => get_permalink( $post_id ),
				'score'        => $score,
				'status_label' => $this->seo_audit_status_label( $counts ),
				'counts'       => $counts,
				'top_issues'   => array_slice( $combined_issues, 0, 8 ),
			);
		}

		usort(
			$urls,
			static function ( $a, $b ) {
				return (int) $b['score'] <=> (int) $a['score'];
			}
		);

		$top_issue_codes = array_values( $issue_codes );
		usort(
			$top_issue_codes,
			static function ( $a, $b ) {
				return (int) $b['count'] <=> (int) $a['count'];
			}
		);

		$payload = array(
			'summary' => array(
				'status'        => $total_errors > 0 ? 'fail' : ( $total_warnings > 0 ? 'warn' : 'pass' ),
				'audited_count' => count( $urls ),
				'critical_urls' => $critical_urls,
				'needs_review'  => $needs_review,
				'pass_urls'     => max( 0, count( $urls ) - $critical_urls - $needs_review ),
				'error_count'   => $total_errors,
				'warning_count' => $total_warnings,
				'info_count'    => $total_info,
			),
			'category_counts' => $category_counts,
			'top_issue_codes' => array_slice( $top_issue_codes, 0, 12 ),
			'urls'            => $urls,
			'workflow'        => array(
				'read'  => 'Use to prioritize URLs before running targeted per-page SEO tools.',
				'fix'   => 'Open the highest-scoring URLs and fix issues through approval-first Gutenberg, media, or SEO metadata edits.',
				'guard' => 'This endpoint is read-only and does not mutate content, media, or SEO settings.',
			),
		);

		if ( $store && class_exists( 'Spai_SEO_Audit_Store' ) ) {
			$payload['stored_run'] = Spai_SEO_Audit_Store::store_run(
				$payload,
				array(
					'post_types'     => $post_types,
					'limit'          => $limit,
					'include_drafts' => $include_drafts,
				)
			);
		}

		return $this->success_response(
			$payload
		);
	}

	/**
	 * Get stored SEO issues.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_seo_issues( $request ) {
		$this->log_activity( 'get_seo_issues', $request );

		if ( ! class_exists( 'Spai_SEO_Audit_Store' ) ) {
			return $this->success_response(
				array(
					'summary' => array(
						'total'    => 0,
						'open'     => 0,
						'resolved' => 0,
						'error'    => 0,
						'warning'  => 0,
						'info'     => 0,
					),
					'issues'  => array(),
				)
			);
		}

		return $this->success_response(
			Spai_SEO_Audit_Store::list_issues(
				array(
					'status'   => $request->get_param( 'status' ),
					'severity' => $request->get_param( 'severity' ),
					'category' => $request->get_param( 'category' ),
					'post_id'  => $request->get_param( 'post_id' ),
					'run_id'   => $request->get_param( 'run_id' ),
					'limit'    => $request->get_param( 'limit' ),
				)
			)
		);
	}

	/**
	 * Validate structured data for a single post/page without mutating content.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function validate_structured_data( $request ) {
		$post_id = absint( $request->get_param( 'id' ) );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return $this->error_response( 'not_found', 'Post not found.', 404 );
		}

		$this->log_activity( 'validate_structured_data', $request, array( 'post_id' => $post_id ) );

		$content         = (string) $post->post_content;
		$json_ld_items   = $this->extract_json_ld_items( $content );
		$microdata_items = $this->extract_microdata_items( $content );
		$issues          = array();
		$schema_types    = array();

		foreach ( $json_ld_items as $index => $item ) {
			if ( ! empty( $item['error'] ) ) {
				$issues[] = $this->make_structured_data_issue(
					'invalid_json_ld',
					'error',
					__( 'A JSON-LD block could not be parsed.', 'mumega-mcp' ),
					__( 'Fix the JSON syntax or remove the invalid structured data block.', 'mumega-mcp' ),
					array(
						'format' => 'json-ld',
						'index'  => $index,
						'error'  => $item['error'],
					)
				);
				continue;
			}

			foreach ( $this->flatten_schema_entities( $item['data'] ) as $entity ) {
				$type = $this->normalize_schema_type( $entity['@type'] ?? '' );
				if ( '' !== $type ) {
					$schema_types[] = $type;
				}

				if ( empty( $entity['@context'] ) ) {
					$issues[] = $this->make_structured_data_issue( 'missing_context', 'warning', __( 'JSON-LD entity is missing @context.', 'mumega-mcp' ), __( 'Use a schema.org @context for valid JSON-LD.', 'mumega-mcp' ), array( 'index' => $index ) );
				}

				if ( '' === $type ) {
					$issues[] = $this->make_structured_data_issue( 'missing_type', 'warning', __( 'JSON-LD entity is missing @type.', 'mumega-mcp' ), __( 'Add a concrete schema type that matches the visible content.', 'mumega-mcp' ), array( 'index' => $index ) );
				}

				$issues = array_merge( $issues, $this->validate_schema_entity_shape( $entity, $type, $post ) );
			}
		}

		foreach ( $microdata_items as $item ) {
			$type = $this->normalize_schema_type( $item['type'] );
			if ( '' !== $type ) {
				$schema_types[] = $type;
			}
		}

		$schema_types    = array_values( array_unique( array_filter( $schema_types ) ) );
		$recommendations = $this->recommend_schema_types_for_post( $post, $content, $schema_types );

		if ( empty( $json_ld_items ) && empty( $microdata_items ) ) {
			$issues[] = $this->make_structured_data_issue( 'no_structured_data', 'info', __( 'No structured data was detected in the post content.', 'mumega-mcp' ), __( 'Add schema only when it accurately describes visible content or can be handled by the active SEO plugin.', 'mumega-mcp' ) );
		}

		if ( ! empty( $recommendations ) ) {
			$issues[] = $this->make_structured_data_issue( 'schema_recommendation_available', 'info', __( 'Page-appropriate schema recommendations are available.', 'mumega-mcp' ), __( 'Review the recommendations and add schema through an SEO plugin or approved block-native workflow only when supported by visible content.', 'mumega-mcp' ) );
		}

		$summary = $this->summarize_structured_data_issues( $issues );

		return $this->success_response(
			array(
				'post'            => array(
					'id'     => $post_id,
					'title'  => get_the_title( $post ),
					'type'   => $post->post_type,
					'status' => $post->post_status,
					'url'    => get_permalink( $post ),
				),
				'summary'         => $summary,
				'inventory'       => array(
					'json_ld_count'    => count( $json_ld_items ),
					'microdata_count'  => count( $microdata_items ),
					'schema_org_hints' => substr_count( strtolower( $content ), 'schema.org' ),
					'types'            => $schema_types,
				),
				'json_ld'         => $this->summarize_json_ld_items( $json_ld_items ),
				'microdata'       => $microdata_items,
				'issues'          => $issues,
				'recommendations' => $recommendations,
				'workflow'        => array(
					'read'  => 'Use before publishing or when auditing AI/search citation readiness.',
					'fix'   => 'Add or correct schema only through approved SEO plugin fields or visible-content-backed markup.',
					'guard' => 'This endpoint is read-only and does not inject structured data.',
				),
			)
		);
	}

	/**
	 * Audit media SEO for a single post/page without mutating content.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function audit_media_seo( $request ) {
		$post_id = absint( $request->get_param( 'id' ) );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return $this->error_response( 'not_found', 'Post not found.', 404 );
		}

		$this->log_activity( 'audit_media_seo', $request, array( 'post_id' => $post_id ) );

		$content       = (string) $post->post_content;
		$featured_id   = get_post_thumbnail_id( $post );
		$content_media = $this->extract_content_image_inventory( $content );
		$media_items   = array();
		$issues        = array();
		$seen_ids      = array();

		if ( $featured_id ) {
			$featured_item = $this->build_media_seo_item( (int) $featured_id, 'featured_image', null );
			$media_items[] = $featured_item;
			$issues        = array_merge( $issues, $this->validate_media_seo_item( $featured_item ) );
			$seen_ids[]    = (int) $featured_id;
		} elseif ( in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			$issues[] = $this->make_media_seo_issue( 'missing_featured_image', 'info', __( 'No featured image is set.', 'mumega-mcp' ), __( 'Set a featured image when the content needs social/search previews.', 'mumega-mcp' ) );
		}

		foreach ( $content_media as $content_item ) {
			$attachment_id = (int) $content_item['attachment_id'];
			$item          = $attachment_id > 0 ? $this->build_media_seo_item( $attachment_id, 'content_image', $content_item ) : $this->build_external_media_seo_item( $content_item );
			$media_items[] = $item;
			$issues        = array_merge( $issues, $this->validate_media_seo_item( $item ) );

			if ( $attachment_id > 0 ) {
				if ( in_array( $attachment_id, $seen_ids, true ) ) {
					$issues[] = $this->make_media_seo_issue( 'duplicate_image_use', 'info', __( 'An image is reused on this page.', 'mumega-mcp' ), __( 'Confirm repeated image use is intentional and not a duplicated block.', 'mumega-mcp' ), array( 'attachment_id' => $attachment_id ) );
				}
				$seen_ids[] = $attachment_id;
			}
		}

		if ( empty( $media_items ) ) {
			$issues[] = $this->make_media_seo_issue( 'no_images_found', 'info', __( 'No images were found in the post content.', 'mumega-mcp' ), __( 'No action is required unless the page needs visual search, social, or conversion media.', 'mumega-mcp' ) );
		}

		$summary = $this->summarize_media_seo_issues( $issues );

		return $this->success_response(
			array(
				'post'       => array(
					'id'     => $post_id,
					'title'  => get_the_title( $post ),
					'type'   => $post->post_type,
					'status' => $post->post_status,
					'url'    => get_permalink( $post ),
				),
				'summary'    => $summary,
				'inventory'  => array(
					'image_count'          => count( $media_items ),
					'content_image_count'  => count( $content_media ),
					'featured_image_id'    => $featured_id ? (int) $featured_id : 0,
					'attachment_id_count'  => count( array_unique( array_filter( array_map( 'absint', wp_list_pluck( $media_items, 'attachment_id' ) ) ) ) ),
					'external_image_count' => count( array_filter( $media_items, static function ( $item ) { return empty( $item['attachment_id'] ); } ) ),
				),
				'media'      => $media_items,
				'issues'     => $issues,
				'workflow'   => array(
					'read'  => 'Use before publishing or after image-heavy agent edits.',
					'fix'   => 'Fix alt text, filenames, image choice, and large media through approved media or block edits.',
					'guard' => 'This endpoint is read-only and does not mutate media or content.',
				),
			)
		);
	}

	/**
	 * Get approval-safe SEO autofix plan.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_seo_autofix_plan( $request ) {
		$this->log_activity( 'get_seo_autofix_plan', $request );

		$plan = class_exists( 'Spai_SEO_Autofix' ) ? Spai_SEO_Autofix::get_plan(
			array(
				'severity' => $request->get_param( 'severity' ),
				'category' => $request->get_param( 'category' ),
				'post_id'  => $request->get_param( 'post_id' ),
				'run_id'   => $request->get_param( 'run_id' ),
				'issue_id' => $request->get_param( 'issue_id' ),
				'limit'    => $request->get_param( 'limit' ),
			)
		) : array(
			'schema_version' => '2026-05-20',
			'summary'        => array(
				'issues_inspected' => 0,
				'actions'          => 0,
				'can_prepare'      => 0,
				'can_auto_apply'   => 0,
				'needs_approval'   => 0,
				'manual_review'    => 0,
				'by_strategy'      => array(),
			),
			'filters'        => array(),
			'actions'        => array(),
		);

		return $this->success_response( $plan );
	}

	/**
	 * Import provider-neutral search performance rows.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function import_search_performance( $request ) {
		$this->log_activity( 'import_search_performance', $request );

		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = $request->get_params();
		}

		$result = class_exists( 'Spai_Search_Performance' ) ? Spai_Search_Performance::import_rows(
			array(
				'provider' => $params['provider'] ?? $request->get_param( 'provider' ),
				'source'   => $params['source'] ?? $request->get_param( 'source' ),
				'rows'     => isset( $params['rows'] ) && is_array( $params['rows'] ) ? $params['rows'] : array(),
			)
		) : array(
			'id'          => '',
			'provider'    => sanitize_key( (string) $request->get_param( 'provider' ) ),
			'source'      => sanitize_text_field( (string) $request->get_param( 'source' ) ),
			'imported_at' => gmdate( 'c' ),
			'row_count'   => 0,
			'ignored'     => 0,
		);

		return $this->success_response( $result, 201 );
	}

	/**
	 * Get search performance trends.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_search_performance( $request ) {
		$this->log_activity( 'get_search_performance', $request );

		$report = class_exists( 'Spai_Search_Performance' ) ? Spai_Search_Performance::get_report(
			array(
				'provider' => $request->get_param( 'provider' ),
				'url'      => $request->get_param( 'url' ),
				'query'    => $request->get_param( 'query' ),
				'days'     => $request->get_param( 'days' ),
				'limit'    => $request->get_param( 'limit' ),
			)
		) : array(
			'schema_version' => '2026-05-20',
			'summary'        => array(
				'rows'        => 0,
				'clicks'      => 0,
				'impressions' => 0,
				'ctr'         => 0,
				'position'    => 0,
				'providers'   => array(),
			),
			'imports'        => array(),
			'top_queries'    => array(),
			'top_urls'       => array(),
			'daily'          => array(),
		);

		return $this->success_response( $report );
	}

	/**
	 * Get WooCommerce SEO intelligence report.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_woocommerce_seo_report( $request ) {
		$this->log_activity( 'get_woocommerce_seo_report', $request );

		$report = class_exists( 'Spai_WooCommerce_SEO' ) ? Spai_WooCommerce_SEO::get_report(
			array(
				'status' => $request->get_param( 'status' ),
				'limit'  => $request->get_param( 'limit' ),
			)
		) : array(
			'schema_version' => '2026-05-20',
			'summary'        => array(
				'woocommerce_detected' => false,
				'products_inspected'   => 0,
				'error_count'          => 0,
				'warning_count'        => 0,
				'opportunity_count'    => 0,
				'search_clicks'        => 0,
				'search_impressions'   => 0,
				'search_ctr'           => 0,
			),
			'products'       => array(),
		);

		return $this->success_response( $report );
	}

	/**
	 * Audit content quality and AI-search citation readiness.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function audit_content_quality( $request ) {
		$post_id = absint( $request->get_param( 'id' ) );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return $this->error_response( 'not_found', 'Post not found.', 404 );
		}

		$this->log_activity( 'audit_content_quality', $request, array( 'post_id' => $post_id ) );

		$content          = (string) $post->post_content;
		$text             = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $content ) ) );
		$word_count       = str_word_count( $text );
		$headings         = $this->extract_heading_texts( $content );
		$question_count   = $this->count_question_signals( $text, $headings );
		$entity_names     = $this->extract_content_entity_names( $text );
		$external_links   = $this->count_external_reference_links( $content );
		$trust_signals    = $this->detect_content_trust_signals( $text );
		$modified_ts      = (int) get_post_modified_time( 'U', true, $post );
		$freshness_days   = $modified_ts > 0 ? max( 0, floor( ( time() - $modified_ts ) / DAY_IN_SECONDS ) ) : null;
		$issues           = array();

		if ( $word_count < 180 ) {
			$issues[] = $this->make_content_quality_issue( 'low_answer_depth', 'warning', __( 'Content may not have enough depth to answer the topic.', 'mumega-mcp' ), __( 'Add clear, useful detail that satisfies the page intent before publishing.', 'mumega-mcp' ) );
		}

		if ( ! $this->has_summary_intro( $text ) ) {
			$issues[] = $this->make_content_quality_issue( 'missing_summary_intro', 'info', __( 'The page does not appear to open with a concise summary.', 'mumega-mcp' ), __( 'Add a short intro that states who the page is for and what it answers.', 'mumega-mcp' ) );
		}

		if ( 0 === $question_count ) {
			$issues[] = $this->make_content_quality_issue( 'no_question_coverage', 'info', __( 'No explicit question coverage was detected.', 'mumega-mcp' ), __( 'Add question-style headings or FAQ content only when it matches real user intent.', 'mumega-mcp' ) );
		}

		if ( count( $entity_names ) < 3 ) {
			$issues[] = $this->make_content_quality_issue( 'low_entity_coverage', 'info', __( 'Few entity-like names were detected.', 'mumega-mcp' ), __( 'Mention relevant products, people, organizations, places, standards, tools, or concepts naturally where useful.', 'mumega-mcp' ) );
		}

		if ( null !== $freshness_days && $freshness_days > 365 ) {
			$issues[] = $this->make_content_quality_issue( 'stale_content', 'warning', __( 'Content has not been updated in over a year.', 'mumega-mcp' ), __( 'Review facts, screenshots, product names, and links before relying on this page.', 'mumega-mcp' ), array( 'freshness_days' => $freshness_days ) );
		}

		if ( 0 === count( $trust_signals ) ) {
			$issues[] = $this->make_content_quality_issue( 'missing_trust_signals', 'info', __( 'No obvious trust signals were detected.', 'mumega-mcp' ), __( 'Add visible author, date, source, policy, contact, proof, or process details where appropriate.', 'mumega-mcp' ) );
		}

		if ( 0 === $external_links ) {
			$issues[] = $this->make_content_quality_issue( 'no_reference_hints', 'info', __( 'No external reference links were detected.', 'mumega-mcp' ), __( 'Use references only when they help users verify claims; do not invent citations.', 'mumega-mcp' ) );
		}

		$score   = $this->calculate_content_quality_score( $word_count, $question_count, count( $entity_names ), count( $trust_signals ), $external_links, $freshness_days );
		$summary = $this->summarize_content_quality_issues( $issues, $score );

		return $this->success_response(
			array(
				'post'       => array(
					'id'     => $post_id,
					'title'  => get_the_title( $post ),
					'type'   => $post->post_type,
					'status' => $post->post_status,
					'url'    => get_permalink( $post ),
				),
				'summary'    => $summary,
				'signals'    => array(
					'word_count'          => $word_count,
					'heading_count'       => count( $headings ),
					'question_count'      => $question_count,
					'entity_name_count'   => count( $entity_names ),
					'entity_names'        => array_slice( $entity_names, 0, 12 ),
					'external_references' => $external_links,
					'trust_signals'       => $trust_signals,
					'freshness_days'      => $freshness_days,
				),
				'issues'     => $issues,
				'workflow'   => array(
					'read'  => 'Use before publishing answer-oriented or AI-search-sensitive content.',
					'fix'   => 'Improve visible content through approval-first Gutenberg edits; do not invent facts, entities, or citations.',
					'guard' => 'This endpoint is read-only and does not mutate content.',
				),
			)
		);
	}

	/**
	 * Build internal content graph data.
	 *
	 * @param array $post_types     Post types.
	 * @param int   $limit          Maximum nodes.
	 * @param bool  $include_drafts Include drafts/private posts.
	 * @return array Graph data.
	 */
	private function build_content_graph_data( $post_types, $limit, $include_drafts = false ) {
		$statuses = $include_drafts ? array( 'publish', 'draft', 'private' ) : array( 'publish' );

		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => $statuses,
				'posts_per_page' => $limit,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		$nodes_by_id       = array();
		$url_to_id         = array();
		$menu_post_depths  = $this->get_menu_post_depths();
		$menu_post_ids     = array_keys( $menu_post_depths );
		$terms_to_post_ids = array();
		$now               = time();

		foreach ( $posts as $post ) {
			$permalink  = get_permalink( $post );
			$term_names = $this->get_graph_term_names( $post );
			$headings   = $this->extract_heading_texts( $post->post_content );
			$modified_ts = (int) get_post_modified_time( 'U', true, $post );
			$freshness_days = $modified_ts > 0 ? max( 0, floor( ( $now - $modified_ts ) / DAY_IN_SECONDS ) ) : null;

			$node = array(
				'id'             => (int) $post->ID,
				'type'           => $post->post_type,
				'status'         => $post->post_status,
				'title'          => get_the_title( $post ),
				'url'            => $permalink,
				'slug'           => $post->post_name,
				'excerpt'        => has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 32 ),
				'modified'       => get_post_modified_time( DATE_ATOM, true, $post ),
				'parent_id'      => (int) $post->post_parent,
				'in_menu'        => in_array( (int) $post->ID, $menu_post_ids, true ),
				'menu_depth'     => isset( $menu_post_depths[ (int) $post->ID ] ) ? (int) $menu_post_depths[ (int) $post->ID ] : null,
				'terms'          => $term_names,
				'headings'       => $headings,
				'word_count'     => str_word_count( wp_strip_all_tags( $post->post_content ) ),
				'freshness_days' => $freshness_days,
				'freshness_score' => $this->calculate_freshness_score( $freshness_days ),
				'inbound_count'  => 0,
				'outbound_count' => 0,
				'hub_score'      => 0,
				'orphan_severity' => 'none',
				'rank_score'     => 0,
				'anchors_in'     => array(),
				'anchors_out'    => array(),
			);

			$nodes_by_id[ (int) $post->ID ] = $node;
			$url_to_id[ $this->normalize_internal_graph_url( $permalink ) ] = (int) $post->ID;

			foreach ( $term_names as $term_name ) {
				$term_key = sanitize_title( $term_name );
				if ( '' === $term_key ) {
					continue;
				}

				if ( empty( $terms_to_post_ids[ $term_key ] ) ) {
					$terms_to_post_ids[ $term_key ] = array(
						'name' => $term_name,
						'ids'  => array(),
					);
				}
				$terms_to_post_ids[ $term_key ]['ids'][] = (int) $post->ID;
			}
		}

		$edges = array();

		foreach ( $posts as $post ) {
			$from_id = (int) $post->ID;
			$links   = $this->extract_internal_links_from_content( $post->post_content, $url_to_id );

			foreach ( $links as $link ) {
				$to_id = $link['target_id'];
				if ( $from_id === $to_id || ! isset( $nodes_by_id[ $to_id ] ) ) {
					continue;
				}

				$edges[] = array(
					'from'   => $from_id,
					'to'     => $to_id,
					'type'   => 'content_link',
					'anchor' => $link['anchor'],
					'url'    => $link['url'],
				);

				$nodes_by_id[ $from_id ]['outbound_count']++;
				$nodes_by_id[ $to_id ]['inbound_count']++;
				$nodes_by_id[ $from_id ]['anchors_out'][] = $link['anchor'];
				$nodes_by_id[ $to_id ]['anchors_in'][]    = $link['anchor'];
			}

			if ( $post->post_parent && isset( $nodes_by_id[ (int) $post->post_parent ] ) ) {
				$edges[] = array(
					'from' => (int) $post->post_parent,
					'to'   => $from_id,
					'type' => 'parent_child',
				);
			}
		}

		foreach ( $terms_to_post_ids as $term ) {
			$term_ids = array_values( array_unique( $term['ids'] ) );
			sort( $term_ids );

			for ( $i = 0; $i < count( $term_ids ); $i++ ) {
				for ( $j = $i + 1; $j < count( $term_ids ); $j++ ) {
					$edges[] = array(
						'from'   => $term_ids[ $i ],
						'to'     => $term_ids[ $j ],
						'type'   => 'shared_taxonomy',
						'term'   => $term['name'],
						'weight' => 2,
					);
				}
			}
		}

		$front_page_id = (int) get_option( 'page_on_front' );
		$orphans       = array();
		$page_rank     = $this->calculate_graph_page_rank( array_keys( $nodes_by_id ), $edges );

		foreach ( $nodes_by_id as $id => $node ) {
			$nodes_by_id[ $id ]['anchors_in']  = array_values( array_unique( array_filter( $node['anchors_in'] ) ) );
			$nodes_by_id[ $id ]['anchors_out'] = array_values( array_unique( array_filter( $node['anchors_out'] ) ) );
			$nodes_by_id[ $id ]['hub_score']   = $this->calculate_hub_score( $nodes_by_id[ $id ] );
			$nodes_by_id[ $id ]['rank_score']  = isset( $page_rank[ $id ] ) ? $page_rank[ $id ] : 0;

			if ( 'publish' === $node['status'] && 0 === $node['inbound_count'] && ! $node['in_menu'] && $front_page_id !== $id ) {
				$severity = $this->calculate_orphan_severity( $nodes_by_id[ $id ] );
				$nodes_by_id[ $id ]['orphan_severity'] = $severity;
				$orphans[] = array(
					'id'    => $id,
					'title' => $node['title'],
					'type'  => $node['type'],
					'url'   => $node['url'],
					'severity' => $severity,
				);
			}
		}

		$nodes = array_values( $nodes_by_id );

		return array(
			'summary' => array(
				'node_count'   => count( $nodes ),
				'edge_count'   => count( $edges ),
				'orphan_count' => count( $orphans ),
				'post_types'   => $post_types,
				'statuses'     => $statuses,
				'signals'      => array( 'hub_score', 'rank_score', 'freshness_score', 'orphan_severity', 'menu_depth', 'shared_taxonomy' ),
			),
			'nodes'   => $nodes,
			'edges'   => $edges,
			'orphans' => $orphans,
			'workflow' => array(
				'read'     => 'Use this graph before creating pages or adding internal links.',
				'suggest'  => 'Choose candidate links from nodes and edges; do not invent internal URLs.',
				'approval' => 'Return a link diff before applying links to page content.',
			),
		);
	}

	/**
	 * Parse requested graph post types and keep only public/queryable types.
	 *
	 * @param string $post_types Raw comma-separated post types.
	 * @return array Post types.
	 */
	private function parse_graph_post_types( $post_types ) {
		$requested = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $post_types ) ) ) );
		if ( empty( $requested ) ) {
			$requested = array( 'page', 'post' );
		}

		$allowed = array();
		foreach ( $requested as $post_type ) {
			$obj = get_post_type_object( $post_type );
			if ( $obj && ( ! empty( $obj->public ) || ! empty( $obj->publicly_queryable ) ) ) {
				$allowed[] = $post_type;
			}
		}

		return empty( $allowed ) ? array( 'page', 'post' ) : array_values( array_unique( $allowed ) );
	}

	/**
	 * Get graph-safe term names for a post.
	 *
	 * @param WP_Post $post Post.
	 * @return array Term names.
	 */
	private function get_graph_term_names( $post ) {
		$names      = array();
		$taxonomies = get_object_taxonomies( $post->post_type );

		foreach ( $taxonomies as $taxonomy ) {
			$term_names = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'names' ) );
			if ( is_wp_error( $term_names ) || empty( $term_names ) ) {
				continue;
			}

			$names = array_merge( $names, $term_names );
		}

		return array_values( array_unique( array_filter( array_map( 'trim', $names ) ) ) );
	}

	/**
	 * Get post IDs included in navigation menus.
	 *
	 * @return array Menu post IDs.
	 */
	private function get_menu_post_ids() {
		return array_keys( $this->get_menu_post_depths() );
	}

	/**
	 * Get post IDs included in navigation menus with shallowest menu depth.
	 *
	 * @return array Post ID to depth map.
	 */
	private function get_menu_post_depths() {
		$depths    = array();
		$locations = get_nav_menu_locations();

		foreach ( $locations as $menu_id ) {
			$items = wp_get_nav_menu_items( $menu_id );
			if ( empty( $items ) || is_wp_error( $items ) ) {
				continue;
			}

			foreach ( $items as $item ) {
				if ( 'post_type' === $item->type && ! empty( $item->object_id ) ) {
					$depth   = $this->get_menu_item_depth( $item, $items );
					$post_id = (int) $item->object_id;

					if ( ! isset( $depths[ $post_id ] ) || $depth < $depths[ $post_id ] ) {
						$depths[ $post_id ] = $depth;
					}
				}
			}
		}

		return $depths;
	}

	/**
	 * Get depth for a nav menu item.
	 *
	 * @param WP_Post $item  Menu item.
	 * @param array   $items Menu items.
	 * @return int Depth.
	 */
	private function get_menu_item_depth( $item, $items ) {
		$parent_id = (int) $item->menu_item_parent;
		$depth     = 0;
		$seen      = array();

		while ( $parent_id > 0 && ! isset( $seen[ $parent_id ] ) ) {
			$seen[ $parent_id ] = true;
			$depth++;
			$next_parent = 0;

			foreach ( $items as $candidate ) {
				if ( (int) $candidate->ID === $parent_id ) {
					$next_parent = (int) $candidate->menu_item_parent;
					break;
				}
			}

			$parent_id = $next_parent;
		}

		return $depth;
	}

	/**
	 * Calculate freshness score from age in days.
	 *
	 * @param int|null $freshness_days Days since modified.
	 * @return float Score from 0-1.
	 */
	private function calculate_freshness_score( $freshness_days ) {
		if ( null === $freshness_days ) {
			return 0.0;
		}

		if ( $freshness_days <= 30 ) {
			return 1.0;
		}

		if ( $freshness_days >= 730 ) {
			return 0.0;
		}

		return round( max( 0, 1 - ( ( $freshness_days - 30 ) / 700 ) ), 3 );
	}

	/**
	 * Calculate a simple hub score for graph nodes.
	 *
	 * @param array $node Graph node.
	 * @return float Score.
	 */
	private function calculate_hub_score( $node ) {
		$score = ( (int) $node['inbound_count'] * 2 ) + (int) $node['outbound_count'];

		if ( ! empty( $node['in_menu'] ) ) {
			$score += null === $node['menu_depth'] ? 2 : max( 1, 3 - (int) $node['menu_depth'] );
		}

		if ( ! empty( $node['parent_id'] ) ) {
			$score += 1;
		}

		$score += min( 3, count( $node['terms'] ) );

		return round( $score, 3 );
	}

	/**
	 * Calculate orphan severity for nodes.
	 *
	 * @param array $node Graph node.
	 * @return string Severity.
	 */
	private function calculate_orphan_severity( $node ) {
		if ( ! empty( $node['in_menu'] ) || (int) $node['inbound_count'] > 0 ) {
			return 'none';
		}

		if ( 'page' === $node['type'] && (int) $node['word_count'] >= 300 ) {
			return 'high';
		}

		if ( (int) $node['word_count'] >= 150 ) {
			return 'medium';
		}

		return 'low';
	}

	/**
	 * Calculate a compact PageRank-style score for content-link edges.
	 *
	 * @param array $node_ids Node IDs.
	 * @param array $edges    Graph edges.
	 * @return array Scores.
	 */
	private function calculate_graph_page_rank( $node_ids, $edges ) {
		$count = count( $node_ids );
		if ( 0 === $count ) {
			return array();
		}

		$ranks    = array_fill_keys( $node_ids, 1 / $count );
		$outgoing = array_fill_keys( $node_ids, array() );

		foreach ( $edges as $edge ) {
			if ( 'content_link' !== $edge['type'] || ! array_key_exists( $edge['from'], $outgoing ) ) {
				continue;
			}

			if ( isset( $outgoing[ $edge['from'] ], $ranks[ $edge['to'] ] ) ) {
				$outgoing[ $edge['from'] ][] = (int) $edge['to'];
			}
		}

		for ( $i = 0; $i < 8; $i++ ) {
			$next = array_fill_keys( $node_ids, ( 1 - 0.85 ) / $count );

			foreach ( $node_ids as $node_id ) {
				$targets = array_values( array_unique( $outgoing[ $node_id ] ) );
				if ( empty( $targets ) ) {
					continue;
				}

				$share = ( $ranks[ $node_id ] * 0.85 ) / count( $targets );
				foreach ( $targets as $target_id ) {
					$next[ $target_id ] += $share;
				}
			}

			$ranks = $next;
		}

		foreach ( $ranks as $id => $score ) {
			$ranks[ $id ] = round( $score, 6 );
		}

		return $ranks;
	}

	/**
	 * Extract heading texts from block/raw content.
	 *
	 * @param string $content Post content.
	 * @return array Heading texts.
	 */
	private function extract_heading_texts( $content ) {
		$headings = array();

		if ( preg_match_all( '/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$text = trim( wp_strip_all_tags( $match[2] ) );
				if ( '' !== $text ) {
					$headings[] = array(
						'level' => (int) $match[1],
						'text'  => $text,
					);
				}
			}
		}

		return $headings;
	}

	/**
	 * Extract internal links from post content.
	 *
	 * @param string $content   Post content.
	 * @param array  $url_to_id Normalized URL to post ID map.
	 * @return array Links.
	 */
	private function extract_internal_links_from_content( $content, $url_to_id ) {
		$links = array();

		if ( preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$link = $this->resolve_internal_graph_link( $match[1], wp_strip_all_tags( $match[2] ), $url_to_id );
				if ( $link ) {
					$links[] = $link;
				}
			}
		}

		return $links;
	}

	/**
	 * Extract raw anchor links from content.
	 *
	 * @param string $content Post content.
	 * @return array Links.
	 */
	private function extract_raw_anchor_links( $content ) {
		$links = array();

		if ( preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$links[] = array(
					'href'   => trim( html_entity_decode( $match[1], ENT_QUOTES, get_bloginfo( 'charset' ) ) ),
					'anchor' => trim( wp_strip_all_tags( $match[2] ) ),
				);
			}
		}

		return $links;
	}

	/**
	 * Resolve an internal link for validation.
	 *
	 * @param string $href      Raw href.
	 * @param array  $url_to_id Normalized URL map.
	 * @return array|null Resolved link, null for external/non-content links.
	 */
	private function resolve_internal_link_for_validation( $href, $url_to_id ) {
		$href = trim( $href );
		if ( '' === $href || 0 === strpos( $href, '#' ) || preg_match( '/^(mailto|tel|sms|javascript):/i', $href ) ) {
			return null;
		}

		$absolute = wp_http_validate_url( $href ) ? $href : home_url( $href );
		$home     = wp_parse_url( home_url() );
		$target   = wp_parse_url( $absolute );

		if ( empty( $target['host'] ) || empty( $home['host'] ) || strtolower( $target['host'] ) !== strtolower( $home['host'] ) ) {
			return null;
		}

		$normalized = $this->normalize_internal_graph_url( $absolute );
		$target_id  = isset( $url_to_id[ $normalized ] ) ? (int) $url_to_id[ $normalized ] : url_to_postid( $absolute );

		return array(
			'target_id'  => $target_id,
			'absolute'   => $absolute,
			'normalized' => $normalized,
		);
	}

	/**
	 * Determine whether anchor text is too generic.
	 *
	 * @param string $anchor Anchor text.
	 * @return bool True when weak.
	 */
	private function is_weak_internal_link_anchor( $anchor ) {
		$anchor = strtolower( trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $anchor ) ) ) );
		return in_array(
			$anchor,
			array(
				'click here',
				'here',
				'learn more',
				'read more',
				'more',
				'this',
				'link',
				'page',
			),
			true
		);
	}

	/**
	 * Create a normalized internal link issue.
	 *
	 * @param string $code           Issue code.
	 * @param string $severity       Severity.
	 * @param int    $source_id      Source post ID.
	 * @param int    $target_id      Target post ID.
	 * @param array  $link           Link data.
	 * @param string $recommendation Recommendation.
	 * @param array  $extra          Extra fields.
	 * @return array Issue.
	 */
	private function make_internal_link_issue( $code, $severity, $source_id, $target_id, $link, $recommendation, $extra = array() ) {
		return array_merge(
			array(
				'code'              => $code,
				'severity'          => $severity,
				'source_id'         => (int) $source_id,
				'target_id'         => (int) $target_id,
				'url'               => (string) $link['href'],
				'anchor'            => (string) $link['anchor'],
				'recommendation'    => $recommendation,
				'approval_required' => true,
			),
			$extra
		);
	}

	/**
	 * Create a normalized SEO readiness issue.
	 *
	 * @param string $code           Issue code.
	 * @param string $severity       Severity.
	 * @param string $message        Message.
	 * @param string $recommendation Recommendation.
	 * @param array  $extra          Extra fields.
	 * @return array Issue.
	 */
	private function make_seo_readiness_issue( $code, $severity, $message, $recommendation, $extra = array() ) {
		return array_merge(
			array(
				'code'              => $code,
				'severity'          => $severity,
				'message'           => $message,
				'recommendation'    => $recommendation,
				'approval_required' => in_array( $severity, array( 'error', 'warning' ), true ),
			),
			$extra
		);
	}

	/**
	 * Summarize SEO readiness issues.
	 *
	 * @param array $issues Issues.
	 * @return array Summary.
	 */
	private function summarize_seo_readiness_issues( $issues ) {
		$error_count   = 0;
		$warning_count = 0;
		$info_count    = 0;

		foreach ( $issues as $issue ) {
			if ( 'error' === $issue['severity'] ) {
				$error_count++;
			} elseif ( 'warning' === $issue['severity'] ) {
				$warning_count++;
			} elseif ( 'info' === $issue['severity'] ) {
				$info_count++;
			}
		}

		return array(
			'status'        => 0 === $error_count ? ( $warning_count > 0 ? 'warn' : 'pass' ) : 'fail',
			'issue_count'   => count( $issues ),
			'error_count'   => $error_count,
			'warning_count' => $warning_count,
			'info_count'    => $info_count,
		);
	}

	/**
	 * Run a post-level validator and return its payload.
	 *
	 * @param string $method Method name.
	 * @param int    $post_id Post ID.
	 * @param string $route_prefix Route prefix.
	 * @return array Payload.
	 */
	private function run_post_validator( $method, $post_id, $route_prefix ) {
		$request = new WP_REST_Request( 'GET', '/site-pilot-ai/v1' . $route_prefix . $post_id );
		$request->set_param( 'id', $post_id );

		$response = $this->$method( $request );
		if ( is_wp_error( $response ) ) {
			return array( 'issues' => array() );
		}

		$data = $response->get_data();
		return is_array( $data ) ? $data : array( 'issues' => array() );
	}

	/**
	 * Attach a category to audit issues.
	 *
	 * @param array  $issues Issues.
	 * @param string $category Category.
	 * @return array Tagged issues.
	 */
	private function tag_seo_audit_issues( $issues, $category ) {
		$tagged = array();

		foreach ( $issues as $issue ) {
			$issue['category'] = $category;
			$tagged[]          = $issue;
		}

		return $tagged;
	}

	/**
	 * Count issues by severity.
	 *
	 * @param array $issues Issues.
	 * @return array Counts.
	 */
	private function count_issues_by_severity( $issues ) {
		$counts = array(
			'error'   => 0,
			'warning' => 0,
			'info'    => 0,
			'total'   => count( $issues ),
		);

		foreach ( $issues as $issue ) {
			if ( isset( $counts[ $issue['severity'] ] ) ) {
				$counts[ $issue['severity'] ]++;
			}
		}

		return $counts;
	}

	/**
	 * Convert issue counts to a URL audit status.
	 *
	 * @param array $counts Counts.
	 * @return string Status.
	 */
	private function seo_audit_status_label( $counts ) {
		if ( $counts['error'] > 0 ) {
			return 'critical';
		}

		if ( $counts['warning'] > 0 ) {
			return 'needs_review';
		}

		return 'pass';
	}

	/**
	 * Create a normalized structured data issue.
	 *
	 * @param string $code           Issue code.
	 * @param string $severity       Severity.
	 * @param string $message        Message.
	 * @param string $recommendation Recommendation.
	 * @param array  $extra          Extra fields.
	 * @return array Issue.
	 */
	private function make_structured_data_issue( $code, $severity, $message, $recommendation, $extra = array() ) {
		return array_merge(
			array(
				'code'              => $code,
				'severity'          => $severity,
				'message'           => $message,
				'recommendation'    => $recommendation,
				'approval_required' => in_array( $severity, array( 'error', 'warning' ), true ),
			),
			$extra
		);
	}

	/**
	 * Summarize structured data issues.
	 *
	 * @param array $issues Issues.
	 * @return array Summary.
	 */
	private function summarize_structured_data_issues( $issues ) {
		$error_count   = 0;
		$warning_count = 0;
		$info_count    = 0;

		foreach ( $issues as $issue ) {
			if ( 'error' === $issue['severity'] ) {
				$error_count++;
			} elseif ( 'warning' === $issue['severity'] ) {
				$warning_count++;
			} elseif ( 'info' === $issue['severity'] ) {
				$info_count++;
			}
		}

		return array(
			'status'        => 0 === $error_count ? ( $warning_count > 0 ? 'warn' : 'pass' ) : 'fail',
			'issue_count'   => count( $issues ),
			'error_count'   => $error_count,
			'warning_count' => $warning_count,
			'info_count'    => $info_count,
		);
	}

	/**
	 * Extract JSON-LD script blocks from content.
	 *
	 * @param string $content Content.
	 * @return array JSON-LD items.
	 */
	private function extract_json_ld_items( $content ) {
		$items = array();

		if ( ! preg_match_all( '/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $content, $matches ) ) {
			return $items;
		}

		foreach ( $matches[1] as $raw_json ) {
			$raw_json = trim( html_entity_decode( $raw_json, ENT_QUOTES, get_bloginfo( 'charset' ) ) );
			$data     = json_decode( $raw_json, true );
			$error    = json_last_error();

			$items[] = array(
				'raw'   => $raw_json,
				'data'  => JSON_ERROR_NONE === $error ? $data : null,
				'error' => JSON_ERROR_NONE === $error ? '' : json_last_error_msg(),
			);
		}

		return $items;
	}

	/**
	 * Summarize JSON-LD items without returning full raw payloads.
	 *
	 * @param array $items JSON-LD items.
	 * @return array Summary items.
	 */
	private function summarize_json_ld_items( $items ) {
		$summary = array();

		foreach ( $items as $index => $item ) {
			$types = array();
			if ( empty( $item['error'] ) ) {
				foreach ( $this->flatten_schema_entities( $item['data'] ) as $entity ) {
					$type = $this->normalize_schema_type( $entity['@type'] ?? '' );
					if ( '' !== $type ) {
						$types[] = $type;
					}
				}
			}

			$summary[] = array(
				'index'   => $index,
				'valid'   => empty( $item['error'] ),
				'types'   => array_values( array_unique( $types ) ),
				'preview' => substr( wp_strip_all_tags( (string) $item['raw'] ), 0, 300 ),
				'error'   => $item['error'],
			);
		}

		return $summary;
	}

	/**
	 * Extract microdata/schema.org hints from content.
	 *
	 * @param string $content Content.
	 * @return array Microdata items.
	 */
	private function extract_microdata_items( $content ) {
		$items = array();

		if ( preg_match_all( '/itemscope[^>]*itemtype=["\']([^"\']+)["\']/is', $content, $matches ) ) {
			foreach ( $matches[1] as $url ) {
				$items[] = array(
					'format' => 'microdata',
					'type'   => $this->normalize_schema_type( $url ),
					'url'    => esc_url_raw( $url ),
				);
			}
		}

		return array_values( array_unique( $items, SORT_REGULAR ) );
	}

	/**
	 * Flatten JSON-LD into entity arrays.
	 *
	 * @param mixed $data JSON-LD data.
	 * @return array Entities.
	 */
	private function flatten_schema_entities( $data ) {
		$entities = array();

		if ( ! is_array( $data ) ) {
			return $entities;
		}

		if ( isset( $data['@graph'] ) && is_array( $data['@graph'] ) ) {
			foreach ( $data['@graph'] as $entity ) {
				if ( is_array( $entity ) ) {
					if ( empty( $entity['@context'] ) && ! empty( $data['@context'] ) ) {
						$entity['@context'] = $data['@context'];
					}
					$entities[] = $entity;
				}
			}
			return $entities;
		}

		if ( $this->is_list_array( $data ) ) {
			foreach ( $data as $entity ) {
				if ( is_array( $entity ) ) {
					$entities[] = $entity;
				}
			}
			return $entities;
		}

		return array( $data );
	}

	/**
	 * Determine whether an array uses contiguous numeric keys.
	 *
	 * @param array $value Array value.
	 * @return bool True when list-like.
	 */
	private function is_list_array( $value ) {
		if ( array() === $value ) {
			return true;
		}

		return array_keys( $value ) === range( 0, count( $value ) - 1 );
	}

	/**
	 * Normalize a schema type from URL/string/array forms.
	 *
	 * @param mixed $type Schema type.
	 * @return string Normalized type.
	 */
	private function normalize_schema_type( $type ) {
		if ( is_array( $type ) ) {
			$type = reset( $type );
		}

		$type = trim( (string) $type );
		if ( '' === $type ) {
			return '';
		}

		$type = preg_replace( '#^https?://schema\.org/#i', '', $type );
		$type = preg_replace( '#^schema:#i', '', $type );

		return sanitize_key( $type );
	}

	/**
	 * Validate basic schema shape against visible WordPress content.
	 *
	 * @param array   $entity Schema entity.
	 * @param string  $type   Schema type.
	 * @param WP_Post $post   Post object.
	 * @return array Issues.
	 */
	private function validate_schema_entity_shape( $entity, $type, $post ) {
		$issues = array();

		if ( in_array( $type, array( 'article', 'blogposting', 'newsarticle' ), true ) ) {
			if ( empty( $entity['headline'] ) && empty( $entity['name'] ) ) {
				$issues[] = $this->make_structured_data_issue( 'article_missing_headline', 'warning', __( 'Article schema is missing headline/name.', 'mumega-mcp' ), __( 'Use the visible post title as the schema headline.', 'mumega-mcp' ), array( 'schema_type' => $type ) );
			}
			if ( empty( $entity['datePublished'] ) ) {
				$issues[] = $this->make_structured_data_issue( 'article_missing_date_published', 'warning', __( 'Article schema is missing datePublished.', 'mumega-mcp' ), __( 'Include the published date when Article schema is used.', 'mumega-mcp' ), array( 'schema_type' => $type ) );
			}
		}

		if ( 'faqpage' === $type && empty( $entity['mainEntity'] ) ) {
			$issues[] = $this->make_structured_data_issue( 'faq_missing_questions', 'warning', __( 'FAQPage schema is missing questions.', 'mumega-mcp' ), __( 'Only use FAQPage schema when the visible content contains matching questions and answers.', 'mumega-mcp' ), array( 'schema_type' => $type ) );
		}

		if ( 'product' === $type && 'product' !== $post->post_type ) {
			$issues[] = $this->make_structured_data_issue( 'product_schema_on_non_product', 'warning', __( 'Product schema appears on non-product content.', 'mumega-mcp' ), __( 'Use Product schema only for visible product detail pages.', 'mumega-mcp' ), array( 'schema_type' => $type ) );
		}

		if ( in_array( $type, array( 'webpage', 'article', 'blogposting', 'newsarticle' ), true ) && empty( $entity['url'] ) && empty( $entity['mainEntityOfPage'] ) ) {
			$issues[] = $this->make_structured_data_issue( 'schema_missing_url', 'info', __( 'Schema entity does not include URL/mainEntityOfPage.', 'mumega-mcp' ), __( 'Connect schema to the canonical page URL where supported.', 'mumega-mcp' ), array( 'schema_type' => $type ) );
		}

		return $issues;
	}

	/**
	 * Recommend schema types that fit visible WordPress content.
	 *
	 * @param WP_Post $post           Post object.
	 * @param string  $content        Content.
	 * @param array   $existing_types Existing schema types.
	 * @return array Recommendations.
	 */
	private function recommend_schema_types_for_post( $post, $content, $existing_types ) {
		$recommendations = array();
		$base_type       = 'post' === $post->post_type ? 'Article' : 'WebPage';

		if ( ! in_array( strtolower( $base_type ), $existing_types, true ) && ! in_array( 'blogposting', $existing_types, true ) && ! in_array( 'newsarticle', $existing_types, true ) ) {
			$recommendations[] = array(
				'type'       => $base_type,
				'confidence' => 'high',
				'reason'     => 'Matches the WordPress post type and visible page title/content.',
				'guardrail'  => 'Prefer SEO plugin schema output when available; do not duplicate existing schema.',
			);
		}

		if ( $this->content_looks_like_faq( $content ) && ! in_array( 'faqpage', $existing_types, true ) ) {
			$recommendations[] = array(
				'type'       => 'FAQPage',
				'confidence' => 'medium',
				'reason'     => 'Content has question-like headings.',
				'guardrail'  => 'Only use if matching answers are visible on the page.',
			);
		}

		return $recommendations;
	}

	/**
	 * Detect FAQ-like visible content.
	 *
	 * @param string $content Content.
	 * @return bool True when FAQ-like.
	 */
	private function content_looks_like_faq( $content ) {
		foreach ( $this->extract_heading_texts( $content ) as $heading ) {
			if ( false !== strpos( (string) $heading['text'], '?' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Create a normalized media SEO issue.
	 *
	 * @param string $code           Issue code.
	 * @param string $severity       Severity.
	 * @param string $message        Message.
	 * @param string $recommendation Recommendation.
	 * @param array  $extra          Extra fields.
	 * @return array Issue.
	 */
	private function make_media_seo_issue( $code, $severity, $message, $recommendation, $extra = array() ) {
		return array_merge(
			array(
				'code'              => $code,
				'severity'          => $severity,
				'message'           => $message,
				'recommendation'    => $recommendation,
				'approval_required' => in_array( $severity, array( 'error', 'warning' ), true ),
			),
			$extra
		);
	}

	/**
	 * Summarize media SEO issues.
	 *
	 * @param array $issues Issues.
	 * @return array Summary.
	 */
	private function summarize_media_seo_issues( $issues ) {
		$error_count   = 0;
		$warning_count = 0;
		$info_count    = 0;

		foreach ( $issues as $issue ) {
			if ( 'error' === $issue['severity'] ) {
				$error_count++;
			} elseif ( 'warning' === $issue['severity'] ) {
				$warning_count++;
			} elseif ( 'info' === $issue['severity'] ) {
				$info_count++;
			}
		}

		return array(
			'status'        => 0 === $error_count ? ( $warning_count > 0 ? 'warn' : 'pass' ) : 'fail',
			'issue_count'   => count( $issues ),
			'error_count'   => $error_count,
			'warning_count' => $warning_count,
			'info_count'    => $info_count,
		);
	}

	/**
	 * Extract image inventory from Gutenberg image blocks and rendered image tags.
	 *
	 * @param string $content Content.
	 * @return array Image inventory.
	 */
	private function extract_content_image_inventory( $content ) {
		$items = array();

		if ( preg_match_all( '/<img\b[^>]*>/i', $content, $matches ) ) {
			foreach ( $matches[0] as $img_tag ) {
				$attrs = $this->parse_html_tag_attributes( $img_tag );
				$id    = 0;

				if ( ! empty( $attrs['class'] ) && preg_match( '/wp-image-(\d+)/i', $attrs['class'], $id_match ) ) {
					$id = absint( $id_match[1] );
				}

				$items[] = array(
					'attachment_id' => $id,
					'source'        => 'img_tag',
					'src'           => isset( $attrs['src'] ) ? esc_url_raw( $attrs['src'] ) : '',
					'alt'           => isset( $attrs['alt'] ) ? trim( wp_strip_all_tags( $attrs['alt'] ) ) : '',
					'loading'       => isset( $attrs['loading'] ) ? sanitize_key( $attrs['loading'] ) : '',
					'width'         => isset( $attrs['width'] ) ? absint( $attrs['width'] ) : 0,
					'height'        => isset( $attrs['height'] ) ? absint( $attrs['height'] ) : 0,
				);
			}
		}

		if ( preg_match_all( '/<!--\s+wp:image\s+({.*?})\s+-->/is', $content, $matches ) ) {
			foreach ( $matches[1] as $json ) {
				$attrs = json_decode( html_entity_decode( $json, ENT_QUOTES, get_bloginfo( 'charset' ) ), true );
				if ( ! empty( $attrs['id'] ) ) {
					$items[] = array(
						'attachment_id' => absint( $attrs['id'] ),
						'source'        => 'image_block',
						'src'           => '',
						'alt'           => '',
						'loading'       => '',
						'width'         => 0,
						'height'        => 0,
					);
				}
			}
		}

		return array_values( array_unique( $items, SORT_REGULAR ) );
	}

	/**
	 * Parse simple HTML tag attributes.
	 *
	 * @param string $tag HTML tag.
	 * @return array Attributes.
	 */
	private function parse_html_tag_attributes( $tag ) {
		$attrs = array();

		if ( preg_match_all( '/([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*(["\'])(.*?)\2/s', $tag, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$attrs[ strtolower( $match[1] ) ] = html_entity_decode( $match[3], ENT_QUOTES, get_bloginfo( 'charset' ) );
			}
		}

		return $attrs;
	}

	/**
	 * Build media SEO item for a WordPress attachment.
	 *
	 * @param int        $attachment_id Attachment ID.
	 * @param string     $role          Media role.
	 * @param array|null $content_item  Optional content image item.
	 * @return array Media item.
	 */
	private function build_media_seo_item( $attachment_id, $role, $content_item = null ) {
		$metadata = wp_get_attachment_metadata( $attachment_id );
		$file     = get_attached_file( $attachment_id );
		$url      = wp_get_attachment_url( $attachment_id );
		$alt      = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );

		if ( '' === $alt && is_array( $content_item ) && '' !== $content_item['alt'] ) {
			$alt = $content_item['alt'];
		}

		return array(
			'attachment_id' => $attachment_id,
			'role'          => $role,
			'url'           => $url ? esc_url_raw( $url ) : '',
			'filename'      => $file ? basename( $file ) : '',
			'alt'           => $alt,
			'title'         => get_the_title( $attachment_id ),
			'mime_type'     => get_post_mime_type( $attachment_id ),
			'width'         => is_array( $metadata ) && ! empty( $metadata['width'] ) ? absint( $metadata['width'] ) : ( is_array( $content_item ) ? absint( $content_item['width'] ) : 0 ),
			'height'        => is_array( $metadata ) && ! empty( $metadata['height'] ) ? absint( $metadata['height'] ) : ( is_array( $content_item ) ? absint( $content_item['height'] ) : 0 ),
			'filesize'      => $file && file_exists( $file ) ? filesize( $file ) : 0,
			'loading'       => is_array( $content_item ) ? $content_item['loading'] : '',
			'source'        => is_array( $content_item ) ? $content_item['source'] : 'attachment',
		);
	}

	/**
	 * Build media SEO item for a non-attachment image.
	 *
	 * @param array $content_item Content item.
	 * @return array Media item.
	 */
	private function build_external_media_seo_item( $content_item ) {
		return array(
			'attachment_id' => 0,
			'role'          => 'content_image',
			'url'           => $content_item['src'],
			'filename'      => '' !== $content_item['src'] ? basename( wp_parse_url( $content_item['src'], PHP_URL_PATH ) ) : '',
			'alt'           => $content_item['alt'],
			'title'         => '',
			'mime_type'     => '',
			'width'         => absint( $content_item['width'] ),
			'height'        => absint( $content_item['height'] ),
			'filesize'      => 0,
			'loading'       => $content_item['loading'],
			'source'        => $content_item['source'],
		);
	}

	/**
	 * Validate one media SEO item.
	 *
	 * @param array $item Media item.
	 * @return array Issues.
	 */
	private function validate_media_seo_item( $item ) {
		$issues = array();

		if ( 'content_image' === $item['role'] && '' === trim( (string) $item['alt'] ) ) {
			$issues[] = $this->make_media_seo_issue( 'missing_image_alt', 'warning', __( 'A content image is missing alt text.', 'mumega-mcp' ), __( 'Add useful alt text, or mark the image decorative in an approved workflow.', 'mumega-mcp' ), array( 'attachment_id' => $item['attachment_id'], 'url' => $item['url'] ) );
		}

		if ( 'featured_image' === $item['role'] && '' === trim( (string) $item['alt'] ) ) {
			$issues[] = $this->make_media_seo_issue( 'featured_image_missing_alt', 'warning', __( 'The featured image is missing alt text.', 'mumega-mcp' ), __( 'Add alt text if the featured image conveys meaning.', 'mumega-mcp' ), array( 'attachment_id' => $item['attachment_id'] ) );
		}

		if ( '' !== $item['filename'] && preg_match( '/^(image|img|photo|screenshot|untitled|dsc)[-_]?\d*\./i', $item['filename'] ) ) {
			$issues[] = $this->make_media_seo_issue( 'weak_image_filename', 'info', __( 'An image filename is generic.', 'mumega-mcp' ), __( 'Use descriptive filenames before upload when practical; do not rename live media blindly.', 'mumega-mcp' ), array( 'attachment_id' => $item['attachment_id'], 'filename' => $item['filename'] ) );
		}

		if ( $item['filesize'] > 0 && $item['filesize'] > 512000 ) {
			$issues[] = $this->make_media_seo_issue( 'large_image_file', 'warning', __( 'An image file is large.', 'mumega-mcp' ), __( 'Compress or replace oversized images to improve page experience.', 'mumega-mcp' ), array( 'attachment_id' => $item['attachment_id'], 'filesize' => $item['filesize'] ) );
		}

		if ( 'content_image' === $item['role'] && ( 0 === (int) $item['width'] || 0 === (int) $item['height'] ) ) {
			$issues[] = $this->make_media_seo_issue( 'missing_image_dimensions', 'info', __( 'A content image is missing explicit dimensions.', 'mumega-mcp' ), __( 'Use WordPress image blocks or markup that preserves width and height to reduce layout shift.', 'mumega-mcp' ), array( 'attachment_id' => $item['attachment_id'], 'url' => $item['url'] ) );
		}

		if ( 'content_image' === $item['role'] && '' === $item['loading'] ) {
			$issues[] = $this->make_media_seo_issue( 'missing_lazy_loading_hint', 'info', __( 'A content image has no loading attribute in markup.', 'mumega-mcp' ), __( 'Confirm WordPress or the theme adds lazy loading, especially for below-the-fold images.', 'mumega-mcp' ), array( 'attachment_id' => $item['attachment_id'], 'url' => $item['url'] ) );
		}

		return $issues;
	}

	/**
	 * Create a normalized content quality issue.
	 *
	 * @param string $code           Issue code.
	 * @param string $severity       Severity.
	 * @param string $message        Message.
	 * @param string $recommendation Recommendation.
	 * @param array  $extra          Extra fields.
	 * @return array Issue.
	 */
	private function make_content_quality_issue( $code, $severity, $message, $recommendation, $extra = array() ) {
		return array_merge(
			array(
				'code'              => $code,
				'severity'          => $severity,
				'message'           => $message,
				'recommendation'    => $recommendation,
				'approval_required' => in_array( $severity, array( 'error', 'warning' ), true ),
			),
			$extra
		);
	}

	/**
	 * Summarize content quality issues.
	 *
	 * @param array $issues Issues.
	 * @param int   $score  Quality score.
	 * @return array Summary.
	 */
	private function summarize_content_quality_issues( $issues, $score ) {
		$error_count   = 0;
		$warning_count = 0;
		$info_count    = 0;

		foreach ( $issues as $issue ) {
			if ( 'error' === $issue['severity'] ) {
				$error_count++;
			} elseif ( 'warning' === $issue['severity'] ) {
				$warning_count++;
			} elseif ( 'info' === $issue['severity'] ) {
				$info_count++;
			}
		}

		return array(
			'status'        => 0 === $error_count ? ( $warning_count > 0 || $score < 70 ? 'warn' : 'pass' ) : 'fail',
			'quality_score' => $score,
			'issue_count'   => count( $issues ),
			'error_count'   => $error_count,
			'warning_count' => $warning_count,
			'info_count'    => $info_count,
		);
	}

	/**
	 * Count question signals from headings and body text.
	 *
	 * @param string $text     Plain text.
	 * @param array  $headings Heading records.
	 * @return int Question count.
	 */
	private function count_question_signals( $text, $headings ) {
		$count = substr_count( $text, '?' );

		foreach ( $headings as $heading ) {
			if ( preg_match( '/^(what|why|how|when|where|who|can|should|is|are|does|do)\b/i', (string) $heading['text'] ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Detect whether content opens with a useful summary.
	 *
	 * @param string $text Plain text.
	 * @return bool True when summary-like.
	 */
	private function has_summary_intro( $text ) {
		$words = preg_split( '/\s+/', trim( $text ) );
		if ( ! is_array( $words ) || count( $words ) < 18 ) {
			return false;
		}

		$intro = strtolower( implode( ' ', array_slice( $words, 0, 45 ) ) );
		return (bool) preg_match( '/\b(this|guide|page|article|post|overview|learn|explains|covers|helps|shows)\b/', $intro );
	}

	/**
	 * Extract simple entity-like names.
	 *
	 * @param string $text Plain text.
	 * @return array Entity-like names.
	 */
	private function extract_content_entity_names( $text ) {
		$entities = array();

		if ( preg_match_all( '/\b([A-Z][A-Za-z0-9]+(?:\s+[A-Z][A-Za-z0-9]+){0,3})\b/', $text, $matches ) ) {
			foreach ( $matches[1] as $match ) {
				$match = trim( $match );
				if ( strlen( $match ) < 3 || in_array( strtolower( $match ), array( 'the', 'this', 'that', 'and', 'or' ), true ) ) {
					continue;
				}
				$entities[] = $match;
			}
		}

		return array_values( array_unique( array_slice( $entities, 0, 40 ) ) );
	}

	/**
	 * Count external reference links.
	 *
	 * @param string $content Content.
	 * @return int External link count.
	 */
	private function count_external_reference_links( $content ) {
		$count     = 0;
		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );

		foreach ( $this->extract_raw_anchor_links( $content ) as $link ) {
			$href = trim( (string) $link['href'] );
			if ( ! wp_http_validate_url( $href ) ) {
				continue;
			}

			$host = wp_parse_url( $href, PHP_URL_HOST );
			if ( $host && $home_host && strtolower( $host ) !== strtolower( $home_host ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Detect trust-signal keywords.
	 *
	 * @param string $text Plain text.
	 * @return array Signals.
	 */
	private function detect_content_trust_signals( $text ) {
		$signals = array();
		$map     = array(
			'author'  => '/\b(author|written by|reviewed by|editor)\b/i',
			'date'    => '/\b(updated|published|last updated|reviewed on)\b/i',
			'contact' => '/\b(contact|support|email|phone|address)\b/i',
			'proof'   => '/\b(case study|testimonial|example|source|research|data|study)\b/i',
			'policy'  => '/\b(policy|privacy|terms|refund|guarantee)\b/i',
		);

		foreach ( $map as $signal => $pattern ) {
			if ( preg_match( $pattern, $text ) ) {
				$signals[] = $signal;
			}
		}

		return $signals;
	}

	/**
	 * Calculate a simple content quality score.
	 *
	 * @param int      $word_count     Word count.
	 * @param int      $question_count Question count.
	 * @param int      $entity_count   Entity count.
	 * @param int      $trust_count    Trust signal count.
	 * @param int      $external_links External link count.
	 * @param int|null $freshness_days Freshness days.
	 * @return int Score.
	 */
	private function calculate_content_quality_score( $word_count, $question_count, $entity_count, $trust_count, $external_links, $freshness_days ) {
		$score = 30;
		$score += min( 30, (int) floor( $word_count / 20 ) );
		$score += min( 10, $question_count * 5 );
		$score += min( 15, $entity_count * 3 );
		$score += min( 10, $trust_count * 5 );
		$score += min( 5, $external_links * 2 );

		if ( null !== $freshness_days && $freshness_days > 365 ) {
			$score -= 10;
		}

		return max( 0, min( 100, $score ) );
	}

	/**
	 * Get supported SEO meta description value.
	 *
	 * @param int $post_id Post ID.
	 * @return string Description.
	 */
	private function get_seo_meta_description( $post_id ) {
		$keys = array(
			'_yoast_wpseo_metadesc',
			'rank_math_description',
			'_aioseo_description',
			'_seopress_titles_desc',
			'spai_meta_description',
		);

		foreach ( $keys as $key ) {
			$value = trim( (string) get_post_meta( $post_id, $key, true ) );
			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Get supported SEO canonical value.
	 *
	 * @param int $post_id Post ID.
	 * @return string Canonical URL.
	 */
	private function get_seo_canonical_url( $post_id ) {
		$keys = array(
			'_yoast_wpseo_canonical',
			'rank_math_canonical_url',
			'_aioseo_canonical_url',
			'_seopress_robots_canonical',
		);

		foreach ( $keys as $key ) {
			$value = trim( (string) get_post_meta( $post_id, $key, true ) );
			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Determine whether common SEO meta marks a post noindex.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True when noindex.
	 */
	private function is_post_noindex( $post_id ) {
		$values = array(
			get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true ),
			get_post_meta( $post_id, 'rank_math_robots', true ),
			get_post_meta( $post_id, '_aioseo_robots_noindex', true ),
			get_post_meta( $post_id, '_seopress_robots_index', true ),
		);

		foreach ( $values as $value ) {
			if ( is_array( $value ) && in_array( 'noindex', array_map( 'strtolower', $value ), true ) ) {
				return true;
			}

			$value = strtolower( trim( (string) $value ) );
			if ( in_array( $value, array( '1', 'true', 'yes', 'noindex' ), true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extract image attachment IDs from block/image markup.
	 *
	 * @param string $content Content.
	 * @return array Attachment IDs.
	 */
	private function extract_image_ids_from_content( $content ) {
		$image_ids = array();

		if ( preg_match_all( '/<!--\s+wp:image\s+({.*?})\s+-->/is', $content, $matches ) ) {
			foreach ( $matches[1] as $json ) {
				$attrs = json_decode( html_entity_decode( $json, ENT_QUOTES, get_bloginfo( 'charset' ) ), true );
				if ( ! empty( $attrs['id'] ) ) {
					$image_ids[] = (int) $attrs['id'];
				}
			}
		}

		if ( preg_match_all( '/wp-image-(\d+)/i', $content, $matches ) ) {
			foreach ( $matches[1] as $id ) {
				$image_ids[] = (int) $id;
			}
		}

		return array_values( array_unique( array_filter( $image_ids ) ) );
	}

	/**
	 * Get robots.txt output.
	 *
	 * @return string Robots content.
	 */
	private function get_robots_txt() {
		$public = (bool) get_option( 'blog_public' );
		$output = "User-agent: *\n";

		if ( $public ) {
			$output .= "Disallow:\n";
		} else {
			$output .= "Disallow: /\n";
		}

		return (string) apply_filters( 'robots_txt', $output, $public );
	}

	/**
	 * Check whether a sitemap endpoint is likely available.
	 *
	 * @return bool True when sitemap is likely.
	 */
	private function site_has_sitemap_hint() {
		return function_exists( 'wp_sitemaps_get_server' ) || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
	}

	/**
	 * Check for structured data hints in content.
	 *
	 * @param string $content Content.
	 * @return bool True when schema hint exists.
	 */
	private function content_has_schema_hint( $content ) {
		return false !== stripos( $content, 'application/ld+json' )
			|| false !== stripos( $content, 'itemscope' )
			|| false !== stripos( $content, 'schema.org' );
	}

	/**
	 * Build normalized tokens for link suggestion scoring.
	 *
	 * @param array  $node    Graph node.
	 * @param string $content Raw content.
	 * @return array Tokens.
	 */
	private function build_link_suggestion_tokens( $node, $content = '' ) {
		$text_parts = array(
			$node['title'] ?? '',
			$node['slug'] ?? '',
			$node['excerpt'] ?? '',
			wp_strip_all_tags( $content ),
		);

		if ( ! empty( $node['terms'] ) ) {
			$text_parts[] = implode( ' ', $node['terms'] );
		}

		if ( ! empty( $node['headings'] ) ) {
			foreach ( $node['headings'] as $heading ) {
				if ( ! empty( $heading['text'] ) ) {
					$text_parts[] = $heading['text'];
				}
			}
		}

		$text   = strtolower( html_entity_decode( implode( ' ', $text_parts ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
		$tokens = preg_split( '/[^a-z0-9]+/', $text );
		$stop   = array(
			'a',
			'an',
			'and',
			'are',
			'as',
			'at',
			'be',
			'by',
			'for',
			'from',
			'how',
			'in',
			'is',
			'it',
			'of',
			'on',
			'or',
			'our',
			'the',
			'this',
			'to',
			'we',
			'with',
			'you',
			'your',
		);

		$tokens = array_filter(
			$tokens,
			function ( $token ) use ( $stop ) {
				return strlen( $token ) >= 4 && ! in_array( $token, $stop, true );
			}
		);

		return array_values( array_unique( $tokens ) );
	}

	/**
	 * Choose a conservative anchor for a suggested internal link.
	 *
	 * @param array $candidate Candidate node.
	 * @param array $overlap   Shared tokens.
	 * @return string Anchor.
	 */
	private function choose_internal_link_anchor( $candidate, $overlap ) {
		$title = trim( (string) ( $candidate['title'] ?? '' ) );
		if ( '' !== $title ) {
			return $title;
		}

		if ( ! empty( $overlap ) ) {
			return implode( ' ', array_slice( $overlap, 0, 3 ) );
		}

		return trim( (string) ( $candidate['slug'] ?? __( 'Related content', 'mumega-mcp' ) ) );
	}

	/**
	 * Build a native Gutenberg paragraph containing a deterministic internal link.
	 *
	 * @param string $url    Target URL.
	 * @param string $anchor Anchor text.
	 * @return string Block markup.
	 */
	private function build_internal_link_paragraph_block( $url, $anchor ) {
		$link = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $anchor ) );
		return sprintf(
			'<!-- wp:paragraph --><p>%s %s</p><!-- /wp:paragraph -->',
			esc_html__( 'Related:', 'mumega-mcp' ),
			$link
		);
	}

	/**
	 * Resolve a URL to a graph link if it points to known local content.
	 *
	 * @param string $href      Raw href.
	 * @param string $anchor    Anchor text.
	 * @param array  $url_to_id Normalized URL to post ID map.
	 * @return array|null Link record.
	 */
	private function resolve_internal_graph_link( $href, $anchor, $url_to_id ) {
		$href = trim( $href );
		if ( '' === $href || 0 === strpos( $href, '#' ) || preg_match( '/^(mailto|tel|sms|javascript):/i', $href ) ) {
			return null;
		}

		$absolute = wp_http_validate_url( $href ) ? $href : home_url( $href );
		$home     = wp_parse_url( home_url() );
		$target   = wp_parse_url( $absolute );

		if ( empty( $target['host'] ) || empty( $home['host'] ) || strtolower( $target['host'] ) !== strtolower( $home['host'] ) ) {
			return null;
		}

		$normalized = $this->normalize_internal_graph_url( $absolute );
		$target_id  = isset( $url_to_id[ $normalized ] ) ? (int) $url_to_id[ $normalized ] : url_to_postid( $absolute );

		if ( ! $target_id ) {
			return null;
		}

		return array(
			'target_id' => $target_id,
			'url'       => $absolute,
			'anchor'    => trim( $anchor ),
		);
	}

	/**
	 * Normalize internal URLs for graph lookup.
	 *
	 * @param string $url URL.
	 * @return string Normalized URL.
	 */
	private function normalize_internal_graph_url( $url ) {
		$parts = wp_parse_url( $url );
		$path  = isset( $parts['path'] ) ? untrailingslashit( $parts['path'] ) : '';
		$query = isset( $parts['query'] ) ? '?' . $parts['query'] : '';

		return strtolower( $path . $query );
	}

	/**
	 * Get site context (AI brief / style guide).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_site_context( $request ) {
		$this->log_activity( 'get_site_context', $request );

		$context         = get_option( 'spai_site_context', '' );
		$scope           = sanitize_key( (string) $request->get_param( 'scope' ) );
		$archetype_class = sanitize_key( (string) $request->get_param( 'archetype_class' ) );
		$style           = sanitize_text_field( (string) $request->get_param( 'style' ) );
		$effective       = $this->build_effective_site_context( $context, $scope, $archetype_class, $style );

		return $this->success_response(
			array(
				'context'           => $context,
				'effective_context' => $effective['effective_context'],
				'inheritance'       => $effective['inheritance'],
				'updated_at'        => get_option( 'spai_site_context_updated', '' ),
				'hint'              => '' === $context
					? 'No site context configured. Use wp_set_site_context to define your site style guide, header/footer rules, predefined sections, and page structure guidelines. This will be included in wp_introspect so AI assistants automatically follow your design rules.'
					: null,
			)
		);
	}

	/**
	 * Build an effective site context from the global brief and an optional archetype override.
	 *
	 * @param string $base_context    Global site context.
	 * @param string $scope           Optional scope.
	 * @param string $archetype_class Optional archetype class.
	 * @param string $style           Optional style.
	 * @return array
	 */
	private function build_effective_site_context( $base_context, $scope, $archetype_class, $style ) {
		$inheritance = array(
			'scope'           => $scope,
			'archetype_class' => $archetype_class,
			'style'           => $style,
			'matched'         => false,
		);

		if ( '' === $scope || '' === $archetype_class ) {
			return array(
				'effective_context' => $base_context,
				'inheritance'       => $inheritance,
			);
		}

		$matched = $this->find_context_override( $scope, $archetype_class, $style );
		if ( empty( $matched['brief'] ) ) {
			return array(
				'effective_context' => $base_context,
				'inheritance'       => $inheritance,
			);
		}

		$effective = trim( (string) $base_context );
		if ( '' !== $effective ) {
			$effective .= "\n\n";
		}

		$effective .= "## Page-Type Override\n";
		$effective .= '- Scope: ' . $scope . "\n";
		$effective .= '- Class: ' . $archetype_class . "\n";
		if ( '' !== $style ) {
			$effective .= '- Style: ' . $style . "\n";
		}
		if ( ! empty( $matched['title'] ) ) {
			$effective .= '- Source: ' . $matched['title'] . "\n";
		}
		$effective .= "\n" . trim( (string) $matched['brief'] );

		$inheritance['matched'] = true;
		$inheritance['source']  = $matched;

		return array(
			'effective_context' => $effective,
			'inheritance'       => $inheritance,
		);
	}

	/**
	 * Resolve a matching override source by scope.
	 *
	 * @param string $scope           Scope such as page or product.
	 * @param string $archetype_class Archetype class.
	 * @param string $style           Optional style.
	 * @return array
	 */
	private function find_context_override( $scope, $archetype_class, $style ) {
		if ( 'product' === $scope ) {
			return $this->find_product_context_override( $archetype_class, $style );
		}

		return $this->find_page_context_override( $scope, $archetype_class, $style );
	}

	/**
	 * Find a matching Elementor page archetype with override brief.
	 *
	 * @param string $scope           Scope.
	 * @param string $archetype_class Archetype class.
	 * @param string $style           Optional style.
	 * @return array
	 */
	private function find_page_context_override( $scope, $archetype_class, $style ) {
		if ( ! class_exists( 'Spai_Elementor_Pro' ) ) {
			return array();
		}

		$elementor  = new Spai_Elementor_Pro();
		$archetypes = $elementor->get_archetypes(
			array(
				'scope'           => $scope,
				'archetype_class' => $archetype_class,
				'style'           => $style,
				'posts_per_page'  => 1,
			)
		);

		if ( empty( $archetypes ) || ! is_array( $archetypes ) ) {
			return array();
		}

		$item = reset( $archetypes );
		if ( empty( $item['archetype_brief'] ) ) {
			return array();
		}

		return array(
			'type'  => 'elementor_archetype',
			'id'    => isset( $item['id'] ) ? (int) $item['id'] : 0,
			'title' => isset( $item['title'] ) ? (string) $item['title'] : '',
			'brief' => (string) $item['archetype_brief'],
		);
	}

	/**
	 * Find a matching Woo product archetype with override brief.
	 *
	 * @param string $archetype_class Archetype class.
	 * @param string $style           Optional style.
	 * @return array
	 */
	private function find_product_context_override( $archetype_class, $style ) {
		$items = get_option( 'spai_wc_product_archetypes', array() );
		if ( ! is_array( $items ) ) {
			return array();
		}

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( $archetype_class !== (string) ( $item['archetype_class'] ?? '' ) ) {
				continue;
			}
			if ( '' !== $style && $style !== (string) ( $item['archetype_style'] ?? '' ) ) {
				continue;
			}
			if ( empty( $item['brief'] ) ) {
				continue;
			}

			return array(
				'type'  => 'product_archetype',
				'id'    => isset( $item['id'] ) ? (int) $item['id'] : 0,
				'title' => isset( $item['name'] ) ? (string) $item['name'] : '',
				'brief' => (string) $item['brief'],
			);
		}

		return array();
	}

	/**
	 * Set site context (AI brief / style guide).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function set_site_context( $request ) {
		$this->log_activity( 'set_site_context', $request );

		$context = $request->get_param( 'context' );

		if ( null === $context ) {
			return $this->error_response( 'missing_context', 'The context parameter is required.', 400 );
		}

		// Limit to 50KB to prevent abuse.
		if ( strlen( $context ) > 51200 ) {
			return $this->error_response( 'context_too_large', 'Site context must be under 50KB.', 400 );
		}

		update_option( 'spai_site_context', $context );
		update_option( 'spai_site_context_updated', gmdate( 'Y-m-d H:i:s' ) );

		return $this->success_response(
			array(
				'success'    => true,
				'length'     => strlen( $context ),
				'updated_at' => get_option( 'spai_site_context_updated' ),
			)
		);
	}

	/**
	 * Get analytics.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_analytics( $request ) {
		$this->log_activity( 'analytics', $request );

		$days      = $request->get_param( 'days' );
		$analytics = $this->core->get_analytics( $days );

		return $this->success_response( $analytics );
	}

	/**
	 * Get detected plugins.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_plugins( $request ) {
		$this->log_activity( 'plugins', $request );

		$plugins = $this->core->detect_plugins();

		return $this->success_response(
			array(
				'plugins'      => $plugins,
				'capabilities' => $this->core->get_capabilities(),
			)
		);
	}

	/**
	 * Search posts/pages by query string.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function search_content( $request ) {
		$this->log_activity( 'search_content', $request );

		if ( ! class_exists( 'WP_Query' ) ) {
			return $this->error_response(
				'search_unavailable',
				__( 'Search is not available in this environment.', 'mumega-mcp' ),
				500
			);
		}

		$query = (string) $request->get_param( 'query' );
		if ( '' === trim( $query ) ) {
			$query = (string) $request->get_param( 'q' );
		}
		$query = sanitize_text_field( $query );

		if ( '' === $query ) {
			return $this->error_response(
				'missing_query',
				__( 'Search query is required.', 'mumega-mcp' ),
				400
			);
		}

		$type = sanitize_key( (string) $request->get_param( 'type' ) );
		if ( ! in_array( $type, array( 'post', 'page', 'any' ), true ) ) {
			$type = 'any';
		}

		$status = sanitize_key( (string) $request->get_param( 'status' ) );
		if ( '' === $status ) {
			$status = 'publish';
		}

		$per_page = min( 50, max( 1, absint( $request->get_param( 'per_page' ) ?: 10 ) ) );
		$page     = max( 1, absint( $request->get_param( 'page' ) ?: 1 ) );

		$post_types = 'any' === $type ? array( 'post', 'page' ) : array( $type );

		$search_query = new WP_Query(
			array(
				'post_type'           => $post_types,
				'post_status'         => $status,
				's'                   => $query,
				'posts_per_page'      => $per_page,
				'paged'               => $page,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => false,
			)
		);

		$items = array();
		foreach ( $search_query->posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$items[] = $this->format_content_item( $post, false );
			}
		}

		return $this->success_response(
			array(
				'query'      => $query,
				'type'       => $type,
				'status'     => $status,
				'items'      => $items,
				'pagination' => array(
					'page'        => $page,
					'per_page'    => $per_page,
					'total'       => (int) $search_query->found_posts,
					'total_pages' => (int) $search_query->max_num_pages,
				),
			)
		);
	}

	/**
	 * Fetch a single post/page by ID or URL.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function fetch_content( $request ) {
		$this->log_activity( 'fetch_content', $request );

		$id  = absint( $request->get_param( 'id' ) );
		$url = esc_url_raw( (string) $request->get_param( 'url' ) );

		if ( 0 === $id && '' === $url ) {
			return $this->error_response(
				'missing_identifier',
				__( 'Provide either id or url to fetch content.', 'mumega-mcp' ),
				400
			);
		}

		$type = sanitize_key( (string) $request->get_param( 'type' ) );
		if ( ! in_array( $type, array( 'post', 'page', 'any' ), true ) ) {
			$type = 'any';
		}

		$post = null;
		if ( $id > 0 ) {
			$post = get_post( $id );
		} elseif ( '' !== $url ) {
			$resolved_id = $this->resolve_content_id_from_url( $url, $type );
			if ( $resolved_id > 0 ) {
				$post = get_post( $resolved_id );
			}
		}

		if ( ! $post instanceof WP_Post ) {
			return $this->error_response(
				'not_found',
				__( 'Content not found.', 'mumega-mcp' ),
				404
			);
		}

		if ( 'any' !== $type && $type !== $post->post_type ) {
			return $this->error_response(
				'not_found',
				__( 'Content not found for the requested type.', 'mumega-mcp' ),
				404
			);
		}

		$include_content = $request->get_param( 'include_content' );
		$include_content = null === $include_content ? true : (bool) $include_content;

		return $this->success_response(
			$this->format_content_item( $post, $include_content )
		);
	}

	/**
	 * Issue OAuth access token via client credentials grant.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function issue_oauth_token( $request ) {
		$this->log_activity( 'oauth_token', $request );

		$rate_limit_check = $this->check_rate_limit( 'oauth-client:' . $this->get_client_ip() );
		if ( is_wp_error( $rate_limit_check ) ) {
			return $rate_limit_check;
		}

		$oauth_settings = $this->get_oauth_settings();
		if ( empty( $oauth_settings['oauth_enabled'] ) ) {
			return $this->error_response(
				'oauth_disabled',
				__( 'OAuth token endpoint is disabled.', 'mumega-mcp' ),
				503
			);
		}

		$grant_type = sanitize_key( (string) $request->get_param( 'grant_type' ) );
		if ( 'client_credentials' !== $grant_type ) {
			return $this->error_response(
				'unsupported_grant_type',
				__( 'Only client_credentials grant type is supported.', 'mumega-mcp' ),
				400
			);
		}

		$client_id     = sanitize_key( (string) $request->get_param( 'client_id' ) );
		$client_secret = (string) $request->get_param( 'client_secret' );

		if ( ! $this->verify_oauth_client_credentials( $client_id, $client_secret ) ) {
			return $this->error_response(
				'invalid_client',
				__( 'Invalid OAuth client credentials.', 'mumega-mcp' ),
				401
			);
		}

		$scope_string = (string) $request->get_param( 'scope' );
		$scopes       = $this->parse_requested_oauth_scopes( $scope_string );
		$token_data   = $this->issue_oauth_access_token( $scopes, $oauth_settings['oauth_token_ttl'] );

		return $this->success_response( $token_data, 200 );
	}

	/**
	 * Get site settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_settings( $request ) {
		$this->log_activity( 'get_settings', $request );

		$settings = array(
			'title'               => get_option( 'blogname' ),
			'tagline'             => get_option( 'blogdescription' ),
			'url'                 => get_option( 'siteurl' ),
			'home'                => get_option( 'home' ),
			'admin_email'         => get_option( 'admin_email' ),
			'timezone'            => get_option( 'timezone_string' ) ?: 'UTC',
			'date_format'         => get_option( 'date_format' ),
			'time_format'         => get_option( 'time_format' ),
			'language'            => get_option( 'WPLANG' ) ?: 'en_US',
			'posts_per_page'      => (int) get_option( 'posts_per_page' ),
			'permalink_structure' => get_option( 'permalink_structure' ),
			'show_on_front'       => get_option( 'show_on_front' ),
			'page_on_front'       => (int) get_option( 'page_on_front' ),
			'page_for_posts'      => (int) get_option( 'page_for_posts' ),
		);

		return $this->success_response( $settings );
	}

	/**
	 * Update site settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function update_settings( $request ) {
		$this->log_activity( 'update_settings', $request );

		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		if ( empty( $params ) ) {
			return $this->error_response(
				'missing_settings',
				__( 'Settings data is required.', 'mumega-mcp' ),
				400
			);
		}

		$updated = array();

		// Allowed settings to update
		$allowed = array(
			'title'          => 'blogname',
			'tagline'        => 'blogdescription',
			'timezone'       => 'timezone_string',
			'date_format'    => 'date_format',
			'time_format'    => 'time_format',
			'admin_email'    => 'admin_email',
			'posts_per_page' => 'posts_per_page',
		);

		foreach ( $allowed as $key => $option ) {
			if ( isset( $params[ $key ] ) ) {
				$value = $params[ $key ];

				// Sanitize based on type
				if ( 'admin_email' === $key ) {
					$value = sanitize_email( $value );
					if ( ! is_email( $value ) ) {
						continue;
					}
				} elseif ( 'posts_per_page' === $key ) {
					$value = absint( $value );
				} else {
					$value = sanitize_text_field( $value );
				}

				update_option( $option, $value );
				$updated[ $key ] = $value;
			}
		}

		if ( empty( $updated ) ) {
			return $this->error_response(
				'no_valid_settings',
				__( 'No valid settings provided to update.', 'mumega-mcp' ),
				400
			);
		}

		return $this->success_response(
			array(
				'updated'  => $updated,
				'settings' => $this->get_settings( $request )->get_data(),
			)
		);
	}

	/**
	 * Get Mumega MCP plugin settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_plugin_settings( $request ) {
		$this->log_activity( 'get_plugin_settings', $request );

		$settings = get_option( 'spai_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// Redact sensitive values — show presence but not the value.
		$safe = array();
		$secret_keys = array( 'oauth_client_secret_hash', 'github_token', 'screenshot_worker_token' );
		foreach ( $settings as $key => $value ) {
			if ( in_array( $key, $secret_keys, true ) ) {
				$safe[ $key ] = ! empty( $value ) ? '***configured***' : '';
			} else {
				$safe[ $key ] = $value;
			}
		}

		return $this->success_response( $safe );
	}

	/**
	 * Update Mumega MCP plugin settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function update_plugin_settings( $request ) {
		$this->log_activity( 'update_plugin_settings', $request );

		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			// Fallback to body params (for internal MCP dispatch).
			$params = $request->get_body_params();
		}
		if ( empty( $params ) ) {
			return $this->error_response(
				'missing_settings',
				__( 'Settings data is required.', 'mumega-mcp' ),
				400
			);
		}

		$current = get_option( 'spai_settings', array() );
		if ( ! is_array( $current ) ) {
			$current = array();
		}

		$updated = array();

		// Allowed keys with their sanitization rules.
		$allowed = array(
			'enable_logging'          => 'bool',
			'log_retention_days'      => 'int:1:365',
			'log_store_response_data' => 'bool',
			'allowed_origins'         => 'text',
			'oauth_enabled'           => 'bool',
			'oauth_client_id'         => 'key',
			'oauth_client_secret'     => 'secret',
			'oauth_token_ttl'         => 'int:60:86400',
			'alerts_enabled'          => 'bool',
			'alerts_window_minutes'   => 'int:1:60',
			'alerts_cooldown_minutes' => 'int:1:1440',
			'alerts_5xx_threshold'    => 'int:1:10000',
			'alerts_auth_threshold'   => 'int:1:10000',
			'github_token'            => 'secret',
			'github_repo'             => 'text',
		);

		foreach ( $allowed as $key => $rule ) {
			if ( ! isset( $params[ $key ] ) ) {
				continue;
			}

			$value = $params[ $key ];

			if ( 'bool' === $rule ) {
				$current[ $key ] = (bool) $value;
			} elseif ( 'text' === $rule ) {
				$current[ $key ] = sanitize_text_field( (string) $value );
			} elseif ( 'key' === $rule ) {
				$current[ $key ] = sanitize_key( (string) $value );
			} elseif ( 'secret' === $rule ) {
				$val = trim( (string) $value );
				if ( '' !== $val ) {
					if ( 'oauth_client_secret' === $key ) {
						$current['oauth_client_secret_hash'] = wp_hash_password( $val );
					} else {
						$current[ $key ] = sanitize_text_field( $val );
					}
				}
			} elseif ( 0 === strpos( $rule, 'int:' ) ) {
				$parts = explode( ':', $rule );
				$min   = (int) $parts[1];
				$max   = (int) $parts[2];
				$current[ $key ] = max( $min, min( $max, absint( $value ) ) );
			}

			$updated[] = $key;
		}

		if ( empty( $updated ) ) {
			return $this->error_response(
				'no_valid_settings',
				sprintf(
					'No valid settings provided. Allowed keys: %s',
					implode( ', ', array_keys( $allowed ) )
				),
				400
			);
		}

		update_option( 'spai_settings', $current );

		return $this->success_response( array(
			'updated' => $updated,
			'message' => sprintf( 'Updated %d setting(s).', count( $updated ) ),
		) );
	}

	/**
	 * Get WordPress options (front page, reading settings).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_options( $request ) {
		$this->log_activity( 'get_options', $request );

		$options = array(
			'show_on_front'  => get_option( 'show_on_front' ),
			'page_on_front'  => (int) get_option( 'page_on_front' ),
			'page_for_posts' => (int) get_option( 'page_for_posts' ),
			'posts_per_page' => (int) get_option( 'posts_per_page' ),
			'posts_per_rss'  => (int) get_option( 'posts_per_rss' ),
			'blog_public'    => (int) get_option( 'blog_public' ),
		);

		// Include page names for context
		if ( $options['page_on_front'] ) {
			$page                           = get_post( $options['page_on_front'] );
			$options['page_on_front_title'] = $page ? $page->post_title : null;
		}

		if ( $options['page_for_posts'] ) {
			$page                            = get_post( $options['page_for_posts'] );
			$options['page_for_posts_title'] = $page ? $page->post_title : null;
		}

		return $this->success_response( $options );
	}

	/**
	 * Update WordPress options.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function update_options( $request ) {
		$this->log_activity( 'update_options', $request );

		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		if ( empty( $params ) ) {
			return $this->error_response(
				'missing_options',
				__( 'Options data is required.', 'mumega-mcp' ),
				400
			);
		}

		$updated = array();

		// show_on_front: 'posts' or 'page'
		if ( isset( $params['show_on_front'] ) ) {
			$value = sanitize_key( $params['show_on_front'] );
			if ( in_array( $value, array( 'posts', 'page' ), true ) ) {
				update_option( 'show_on_front', $value );
				$updated['show_on_front'] = $value;
			}
		}

		// page_on_front: ID of front page
		if ( isset( $params['page_on_front'] ) ) {
			$page_id = absint( $params['page_on_front'] );
			if ( 0 === $page_id || get_post( $page_id ) ) {
				update_option( 'page_on_front', $page_id );
				$updated['page_on_front'] = $page_id;
			}
		}

		// page_for_posts: ID of posts page
		if ( isset( $params['page_for_posts'] ) ) {
			$page_id = absint( $params['page_for_posts'] );
			if ( 0 === $page_id || get_post( $page_id ) ) {
				update_option( 'page_for_posts', $page_id );
				$updated['page_for_posts'] = $page_id;
			}
		}

		// posts_per_page
		if ( isset( $params['posts_per_page'] ) ) {
			$value = absint( $params['posts_per_page'] );
			if ( $value > 0 ) {
				update_option( 'posts_per_page', $value );
				$updated['posts_per_page'] = $value;
			}
		}

		// posts_per_rss
		if ( isset( $params['posts_per_rss'] ) ) {
			$value = absint( $params['posts_per_rss'] );
			if ( $value > 0 ) {
				update_option( 'posts_per_rss', $value );
				$updated['posts_per_rss'] = $value;
			}
		}

		// blog_public (search engine visibility)
		if ( isset( $params['blog_public'] ) ) {
			$value = $params['blog_public'] ? 1 : 0;
			update_option( 'blog_public', $value );
			$updated['blog_public'] = $value;
		}

		if ( empty( $updated ) ) {
			return $this->error_response(
				'no_valid_options',
				__( 'No valid options provided to update.', 'mumega-mcp' ),
				400
			);
		}

		return $this->success_response(
			array(
				'updated' => $updated,
				'options' => $this->get_options( $request )->get_data(),
			)
		);
	}

	/**
	 * Get site favicon (site icon).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_favicon( $request ) {
		$this->log_activity( 'get_favicon', $request );

		$site_icon_id = get_option( 'site_icon' );

		if ( ! $site_icon_id ) {
			return $this->success_response(
				array(
					'has_favicon' => false,
					'id'          => null,
					'url'         => null,
					'sizes'       => array(),
				)
			);
		}

		$sizes      = array();
		$icon_sizes = array( 32, 180, 192, 270, 512 );

		foreach ( $icon_sizes as $size ) {
			$icon_url = get_site_icon_url( $size );
			if ( $icon_url ) {
				$sizes[ $size ] = $icon_url;
			}
		}

		return $this->success_response(
			array(
				'has_favicon' => true,
				'id'          => (int) $site_icon_id,
				'url'         => get_site_icon_url( 512 ),
				'sizes'       => $sizes,
			)
		);
	}

	/**
	 * Update site favicon.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function update_favicon( $request ) {
		$this->log_activity( 'update_favicon', $request );

		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		// Option 1: Set by media ID
		if ( ! empty( $params['id'] ) ) {
			$attachment_id = absint( $params['id'] );
			$attachment    = get_post( $attachment_id );

			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				return $this->error_response(
					'invalid_attachment',
					__( 'Invalid media ID.', 'mumega-mcp' ),
					400
				);
			}

			// Verify it's an image
			if ( ! wp_attachment_is_image( $attachment_id ) ) {
				return $this->error_response(
					'not_image',
					__( 'Attachment must be an image.', 'mumega-mcp' ),
					400
				);
			}

			update_option( 'site_icon', $attachment_id );

			return $this->success_response(
				array(
					'updated' => true,
					'favicon' => $this->get_favicon( $request )->get_data(),
				)
			);
		}

		// Option 2: Upload from URL
		if ( ! empty( $params['url'] ) ) {
			if ( ! function_exists( 'media_handle_sideload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/media.php';
			}
			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
			}

			$url = esc_url_raw( $params['url'] );

			// SSRF protection: block internal/private URLs.
			if ( class_exists( 'Spai_Security' ) ) {
				$ssrf_check = Spai_Security::validate_external_url( $url );
				if ( is_wp_error( $ssrf_check ) ) {
					return $ssrf_check;
				}
			}

			// Download and sideload the image
			$tmp = download_url( $url );

			if ( is_wp_error( $tmp ) ) {
				return $this->error_response(
					'download_failed',
					$tmp->get_error_message(),
					400
				);
			}

			$file_array = array(
				'name'     => basename( wp_parse_url( $url, PHP_URL_PATH ) ),
				'tmp_name' => $tmp,
			);

			$attachment_id = media_handle_sideload( $file_array, 0, __( 'Site Icon', 'mumega-mcp' ) );

			if ( is_wp_error( $attachment_id ) ) {
				wp_delete_file( $tmp );
				return $this->error_response(
					'upload_failed',
					$attachment_id->get_error_message(),
					400
				);
			}

			update_option( 'site_icon', $attachment_id );

			return $this->success_response(
				array(
					'updated'  => true,
					'uploaded' => true,
					'favicon'  => $this->get_favicon( $request )->get_data(),
				),
				201
			);
		}

		return $this->error_response(
			'missing_param',
			__( 'Provide either "id" (media ID) or "url" (image URL).', 'mumega-mcp' ),
			400
		);
	}

	/**
	 * Delete (remove) site favicon.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function delete_favicon( $request ) {
		$this->log_activity( 'delete_favicon', $request );

		$site_icon_id = get_option( 'site_icon' );

		if ( ! $site_icon_id ) {
			return $this->success_response(
				array(
					'deleted' => false,
					'message' => __( 'No favicon was set.', 'mumega-mcp' ),
				)
			);
		}

		delete_option( 'site_icon' );

		return $this->success_response(
			array(
				'deleted'     => true,
				'previous_id' => (int) $site_icon_id,
			)
		);
	}

	/**
	 * Get rate limit status for current client.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_rate_limit_status( $request ) {
		$this->log_activity( 'rate_limit_status', $request );

		if ( ! class_exists( 'Spai_Rate_Limiter' ) ) {
			return $this->success_response(
				array(
					'enabled' => false,
					'message' => __( 'Rate limiting is not available.', 'mumega-mcp' ),
				)
			);
		}

		$limiter  = Spai_Rate_Limiter::get_instance();
		$settings = $limiter->get_settings();
		$usage    = $limiter->get_usage();

		return $this->success_response(
			array(
				'enabled' => $settings['enabled'],
				'limits'  => array(
					'burst'      => $settings['burst_limit'],
					'per_minute' => $settings['requests_per_minute'],
					'per_hour'   => $settings['requests_per_hour'],
				),
				'usage'   => $usage,
			)
		);
	}

	/**
	 * Update rate limit settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function update_rate_limit_settings( $request ) {
		$this->log_activity( 'update_rate_limit_settings', $request );

		if ( ! $this->can_manage_api_keys() ) {
			return $this->error_response(
				'forbidden',
				__( 'You do not have permission to manage rate limiting.', 'mumega-mcp' ),
				403
			);
		}

		if ( ! class_exists( 'Spai_Rate_Limiter' ) ) {
			return $this->error_response(
				'rate_limiter_unavailable',
				__( 'Rate limiting is not available.', 'mumega-mcp' ),
				500
			);
		}

		$params = $request->get_json_params();
		if ( ! is_array( $params ) || empty( $params ) ) {
			$params = $request->get_params();
		}

		$allowed  = array( 'enabled', 'requests_per_minute', 'requests_per_hour', 'burst_limit', 'whitelist' );
		$settings = array();
		foreach ( $allowed as $key ) {
			if ( array_key_exists( $key, $params ) ) {
				$settings[ $key ] = $params[ $key ];
			}
		}

		if ( empty( $settings ) ) {
			return $this->error_response(
				'missing_settings',
				__( 'No rate-limit settings provided.', 'mumega-mcp' ),
				400
			);
		}

		$limiter = Spai_Rate_Limiter::get_instance();
		$updated = $limiter->update_settings( $settings );

		if ( ! $updated ) {
			return $this->error_response(
				'update_failed',
				__( 'Failed to update rate-limit settings.', 'mumega-mcp' ),
				500
			);
		}

		return $this->success_response(
			array(
				'updated'  => true,
				'settings' => $limiter->get_settings(),
			)
		);
	}

	/**
	 * Reset rate-limit counters for an identifier.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function reset_rate_limit( $request ) {
		$this->log_activity( 'reset_rate_limit', $request );

		if ( ! $this->can_manage_api_keys() ) {
			return $this->error_response(
				'forbidden',
				__( 'You do not have permission to reset rate limits.', 'mumega-mcp' ),
				403
			);
		}

		if ( ! class_exists( 'Spai_Rate_Limiter' ) ) {
			return $this->error_response(
				'rate_limiter_unavailable',
				__( 'Rate limiting is not available.', 'mumega-mcp' ),
				500
			);
		}

		$identifier = sanitize_text_field( (string) $request->get_param( 'identifier' ) );
		if ( '' === $identifier ) {
			return $this->error_response(
				'missing_identifier',
				__( 'Identifier is required.', 'mumega-mcp' ),
				400
			);
		}

		Spai_Rate_Limiter::get_instance()->reset_limit( $identifier );

		return $this->success_response(
			array(
				'reset'      => true,
				'identifier' => $identifier,
			)
		);
	}

	/**
	 * List scoped API keys.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function list_api_keys( $request ) {
		$this->log_activity( 'list_api_keys', $request );

		if ( ! $this->can_manage_api_keys() ) {
			return $this->error_response(
				'forbidden',
				__( 'You do not have permission to manage API keys.', 'mumega-mcp' ),
				403
			);
		}

		$include_revoked = (bool) $request->get_param( 'include_revoked' );
		$keys            = $this->list_scoped_api_keys( $include_revoked );

		return $this->success_response(
			array(
				'keys'  => $keys,
				'total' => count( $keys ),
			)
		);
	}

	/**
	 * Create scoped API key.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function create_api_key( $request ) {
		$this->log_activity( 'create_api_key', $request );

		if ( ! $this->can_manage_api_keys() ) {
			return $this->error_response(
				'forbidden',
				__( 'You do not have permission to manage API keys.', 'mumega-mcp' ),
				403
			);
		}

		$label           = (string) $request->get_param( 'label' );
		$scopes          = $request->get_param( 'scopes' );
		$scopes          = is_array( $scopes ) ? $scopes : array();
		$role            = (string) $request->get_param( 'role' );
		$tool_categories = $request->get_param( 'tool_categories' );
		$tool_categories = is_array( $tool_categories ) ? $tool_categories : array();

		if ( '' === $role ) {
			$role = 'admin';
		}

		$created = $this->create_scoped_api_key( $label, $scopes, $role, $tool_categories );

		return $this->success_response(
			array(
				'api_key' => $created,
			),
			201
		);
	}

	/**
	 * Revoke scoped API key.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function revoke_api_key( $request ) {
		$this->log_activity( 'revoke_api_key', $request );

		if ( ! $this->can_manage_api_keys() ) {
			return $this->error_response(
				'forbidden',
				__( 'You do not have permission to manage API keys.', 'mumega-mcp' ),
				403
			);
		}

		$key_id  = (string) $request->get_param( 'id' );
		$revoked = $this->revoke_scoped_api_key( $key_id );

		if ( ! $revoked ) {
			return $this->error_response(
				'not_found',
				__( 'API key not found or already revoked.', 'mumega-mcp' ),
				404
			);
		}

		return $this->success_response(
			array(
				'revoked' => true,
				'id'      => sanitize_key( $key_id ),
			)
		);
	}

	/**
	 * Check for available plugin update.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function check_update( $request ) {
		$this->log_activity( 'check_update', $request );

		$current_version = defined( 'SPAI_VERSION' ) ? SPAI_VERSION : '0.0.0';
		$option_version  = null;
		$remote_version  = null;
		$selected_source = null;

		$option_data = get_option( 'spai_update_info' );
		if ( is_string( $option_data ) ) {
			$option_data = json_decode( $option_data, true );
		}
		if ( is_array( $option_data ) && ! empty( $option_data['version'] ) ) {
			$option_version = (string) $option_data['version'];
		}

		$version_url = get_option( 'spai_version_url', 'https://mumega.com/mcp-updates/version.json' );
		if ( empty( $version_url ) ) {
			$version_url = 'https://mumega.com/mcp-updates/version.json';
		}
		$remote_response = wp_remote_get(
			$version_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);
		if ( ! is_wp_error( $remote_response ) && 200 === wp_remote_retrieve_response_code( $remote_response ) ) {
			$remote_body = json_decode( wp_remote_retrieve_body( $remote_response ), true );
			if ( is_array( $remote_body ) && ! empty( $remote_body['version'] ) ) {
				$remote_version = (string) $remote_body['version'];
			}
		}

		// Clear update caches and force a fresh check.
		delete_site_transient( 'update_plugins' );
		delete_transient( 'spai_update_check' );

		if ( function_exists( 'wp_update_plugins' ) ) {
			wp_update_plugins();
		}

		$update_plugins = get_site_transient( 'update_plugins' );
		$plugin_file    = defined( 'SPAI_PLUGIN_BASENAME' ) ? SPAI_PLUGIN_BASENAME : 'site-pilot-ai/site-pilot-ai.php';

		$update_available = false;
		$new_version      = null;
		$package          = null;

		if ( ! empty( $update_plugins->response[ $plugin_file ] ) ) {
			$plugin_update    = $update_plugins->response[ $plugin_file ];
			$new_version      = is_object( $plugin_update ) ? $plugin_update->new_version : null;
			$package          = is_object( $plugin_update ) ? $plugin_update->package : null;
			$update_available = ! empty( $new_version ) && version_compare( $new_version, $current_version, '>' );
		}

		if ( ! empty( $new_version ) ) {
			if ( ! empty( $remote_version ) && version_compare( $new_version, $remote_version, '=' ) ) {
				$selected_source = 'remote';
			} elseif ( ! empty( $option_version ) && version_compare( $new_version, $option_version, '=' ) ) {
				$selected_source = 'option';
			}
		} elseif ( ! empty( $remote_version ) || ! empty( $option_version ) ) {
			if ( ! empty( $remote_version ) && ( empty( $option_version ) || version_compare( $remote_version, $option_version, '>=' ) ) ) {
				$selected_source = 'remote';
			} else {
				$selected_source = 'option';
			}
		}

		return $this->success_response(
			array(
				'current_version'  => $current_version,
				'update_available' => $update_available,
				'new_version'      => $new_version,
				'has_package'      => ! empty( $package ),
				'source'           => $selected_source,
				'option_version'   => $option_version,
				'remote_version'   => $remote_version,
			)
		);
	}

	/**
	 * Trigger plugin self-update.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function trigger_update( $request ) {
		$this->log_activity( 'trigger_update', $request );

		if ( ! $this->can_manage_api_keys() ) {
			return $this->error_response(
				'forbidden',
				__( 'You do not have permission to update the plugin.', 'mumega-mcp' ),
				403
			);
		}

		$plugin_file = defined( 'SPAI_PLUGIN_BASENAME' ) ? SPAI_PLUGIN_BASENAME : 'site-pilot-ai/site-pilot-ai.php';
		$package_url = $request->get_param( 'package_url' );

		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$clear_update_state = static function () {
			delete_option( 'spai_update_info' );
			delete_transient( 'spai_update_check' );
			delete_site_transient( 'update_plugins' );
		};

		// If a package_url is provided, install directly from that URL.
		if ( ! empty( $package_url ) ) {
			// Inject into transient so WP_Upgrader finds it.
			$update_plugins = get_site_transient( 'update_plugins' );
			if ( ! is_object( $update_plugins ) ) {
				$update_plugins = new stdClass();
				$update_plugins->response = array();
			}

			$inject              = new stdClass();
			$inject->slug        = 'site-pilot-ai';
			$inject->plugin      = $plugin_file;
			$inject->new_version = '999.0.0';
			$inject->package     = esc_url_raw( $package_url );
			$update_plugins->response[ $plugin_file ] = $inject;
			set_site_transient( 'update_plugins', $update_plugins );

			$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
			$result   = $upgrader->upgrade( $plugin_file );

			if ( is_wp_error( $result ) ) {
				return $this->error_response( 'update_failed', $result->get_error_message(), 500 );
			}

			if ( true !== $result ) {
				return $this->error_response( 'update_failed', __( 'Plugin update failed.', 'mumega-mcp' ), 500 );
			}

			if ( ! is_plugin_active( $plugin_file ) ) {
				activate_plugin( $plugin_file );
			}

			$clear_update_state();

			return $this->success_response(
				array(
					'updated'     => true,
					'source'      => 'package_url',
					'message'     => __( 'Plugin updated from provided URL.', 'mumega-mcp' ),
				)
			);
		}

		// Standard flow: check for updates via WP transient.
		delete_site_transient( 'update_plugins' );
		delete_transient( 'spai_update_check' );

		if ( function_exists( 'wp_update_plugins' ) ) {
			wp_update_plugins();
		}

		$update_plugins = get_site_transient( 'update_plugins' );

		if ( empty( $update_plugins->response[ $plugin_file ] ) ) {
			return $this->success_response(
				array(
					'updated' => false,
					'message' => __( 'No update available.', 'mumega-mcp' ),
					'version' => defined( 'SPAI_VERSION' ) ? SPAI_VERSION : null,
				)
			);
		}

		$plugin_update = $update_plugins->response[ $plugin_file ];
		if ( empty( $plugin_update->package ) ) {
			return $this->error_response(
				'no_package',
				__( 'Update package URL is not available.', 'mumega-mcp' ),
				400
			);
		}

		$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
		$result   = $upgrader->upgrade( $plugin_file );

		if ( is_wp_error( $result ) ) {
			return $this->error_response(
				'update_failed',
				$result->get_error_message(),
				500
			);
		}

		if ( true !== $result ) {
			return $this->error_response(
				'update_failed',
				__( 'Plugin update failed.', 'mumega-mcp' ),
				500
			);
		}

		// Reactivate if needed.
		if ( ! is_plugin_active( $plugin_file ) ) {
			activate_plugin( $plugin_file );
		}

		$clear_update_state();

		return $this->success_response(
			array(
				'updated'     => true,
				'new_version' => is_object( $plugin_update ) ? $plugin_update->new_version : null,
				'message'     => __( 'Plugin updated successfully.', 'mumega-mcp' ),
			)
		);
	}

	/**
	 * List categories.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function list_categories( $request ) {
		$this->log_activity( 'list_categories', $request );

		$args = array(
			'taxonomy'   => 'category',
			'number'     => min( 200, max( 1, absint( $request->get_param( 'per_page' ) ?: 100 ) ) ),
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		$search = $request->get_param( 'search' );
		if ( ! empty( $search ) ) {
			$args['search'] = sanitize_text_field( $search );
		}

		$parent = $request->get_param( 'parent' );
		if ( null !== $parent ) {
			$args['parent'] = absint( $parent );
		}

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}

		$items = array_map( array( $this, 'format_term' ), $terms );

		return $this->success_response(
			array(
				'categories' => $items,
				'total'      => count( $items ),
			)
		);
	}

	/**
	 * List tags.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function list_tags( $request ) {
		$this->log_activity( 'list_tags', $request );

		$args = array(
			'taxonomy'   => 'post_tag',
			'number'     => min( 200, max( 1, absint( $request->get_param( 'per_page' ) ?: 100 ) ) ),
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		$search = $request->get_param( 'search' );
		if ( ! empty( $search ) ) {
			$args['search'] = sanitize_text_field( $search );
		}

		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}

		$items = array_map( array( $this, 'format_term' ), $terms );

		return $this->success_response(
			array(
				'tags'  => $items,
				'total' => count( $items ),
			)
		);
	}

	/**
	 * Get post meta.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function get_post_meta_handler( $request ) {
		$this->log_activity( 'get_post_meta', $request );

		$post_id = absint( $request->get_param( 'id' ) );
		$post    = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return $this->error_response( 'not_found', __( 'Post not found.', 'mumega-mcp' ), 404 );
		}

		$key = $request->get_param( 'key' );

		if ( ! empty( $key ) ) {
			$key = sanitize_key( $key );

			if ( $this->is_blocked_meta_key( $key ) ) {
				return $this->error_response( 'forbidden_meta_key', __( 'This meta key is not accessible via API.', 'mumega-mcp' ), 403 );
			}

			$value = get_post_meta( $post_id, $key, true );

			return $this->success_response(
				array(
					'id'    => $post_id,
					'key'   => $key,
					'value' => $value,
				)
			);
		}

		// Return all non-blocked meta
		$all_meta  = get_post_meta( $post_id );
		$safe_meta = array();

		foreach ( $all_meta as $meta_key => $meta_values ) {
			if ( ! $this->is_blocked_meta_key( $meta_key ) ) {
				$safe_meta[ $meta_key ] = count( $meta_values ) === 1 ? $meta_values[0] : $meta_values;
			}
		}

		return $this->success_response(
			array(
				'id'   => $post_id,
				'meta' => $safe_meta,
			)
		);
	}

	/**
	 * Set post meta.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function set_post_meta_handler( $request ) {
		$this->log_activity( 'set_post_meta', $request );

		$post_id = absint( $request->get_param( 'id' ) );
		$post    = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return $this->error_response( 'not_found', __( 'Post not found.', 'mumega-mcp' ), 404 );
		}

		$key   = sanitize_key( (string) $request->get_param( 'key' ) );
		$value = $request->get_param( 'value' );

		if ( '' === $key ) {
			return $this->error_response( 'missing_key', __( 'Meta key is required.', 'mumega-mcp' ), 400 );
		}

		if ( $this->is_blocked_meta_key( $key ) ) {
			// Provide specific guidance for Elementor meta keys.
			if ( '_elementor_data' === $key ) {
				return $this->error_response(
					'use_elementor_endpoint',
					__( 'Use wp_set_elementor (POST /elementor/{id}) to set Elementor page data.', 'mumega-mcp' ),
					400
				);
			}
			if ( '_elementor_page_settings' === $key ) {
				return $this->error_response(
					'use_elementor_endpoint',
					__( 'Use wp_set_elementor with the page_settings parameter to update Elementor page settings.', 'mumega-mcp' ),
					400
				);
			}
			if ( 0 === strpos( $key, '_elementor' ) ) {
				return $this->error_response(
					'use_elementor_endpoint',
					__( 'Elementor meta keys cannot be set via wp_set_post_meta. Use the /elementor/{id} endpoints instead.', 'mumega-mcp' ),
					400
				);
			}
			return $this->error_response( 'forbidden_meta_key', __( 'This meta key is not accessible via API.', 'mumega-mcp' ), 403 );
		}

		// Sanitize value — decode JSON objects/arrays to PHP arrays so WordPress
		// serializes them properly (instead of storing raw JSON strings).
		if ( is_string( $value ) ) {
			$trimmed = ltrim( $value );
			if ( ( '{' === substr( $trimmed, 0, 1 ) || '[' === substr( $trimmed, 0, 1 ) ) && null !== json_decode( $value ) ) {
				// Decode JSON to a PHP array — WordPress will auto-serialize it.
				$value = json_decode( $value, true );
			} else {
				$value = sanitize_text_field( $value );
			}
		}

		$result = update_post_meta( $post_id, $key, $value );

		return $this->success_response(
			array(
				'id'      => $post_id,
				'key'     => $key,
				'value'   => $value,
				'updated' => false !== $result,
			)
		);
	}

	/**
	 * Check if a meta key is blocked from API access.
	 *
	 * @param string $meta_key Meta key to check.
	 * @return bool True if blocked.
	 */
	private function is_blocked_meta_key( $meta_key ) {
		$blocked_prefixes = array( '_wp_', 'spai_api_key', '_edit_lock', '_edit_last', '_elementor_' );
		$blocked_keys     = array( '_wp_page_template', '_thumbnail_id', '_elementor_data', '_elementor_page_settings', '_elementor_css', '_elementor_edit_mode' );

		// Allow these specific WordPress keys (non-Elementor).
		$allowed_keys = array( '_wp_page_template', '_thumbnail_id' );

		if ( in_array( $meta_key, $allowed_keys, true ) ) {
			return false;
		}

		if ( in_array( $meta_key, $blocked_keys, true ) ) {
			return true;
		}

		foreach ( $blocked_prefixes as $prefix ) {
			if ( 0 === strpos( $meta_key, $prefix ) ) {
				return true;
			}
		}

		// Block secret-looking keys
		if ( preg_match( '/(password|secret|token|auth|credential)/i', $meta_key ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Whitelist of option keys allowed via API.
	 *
	 * @return array Allowed option keys.
	 */
	private function get_allowed_option_keys() {
		return array(
			// WordPress core.
			'blogname',
			'blogdescription',
			'siteurl',
			'home',
			'admin_email',
			'timezone_string',
			'date_format',
			'time_format',
			'WPLANG',
			'posts_per_page',
			'posts_per_rss',
			'show_on_front',
			'page_on_front',
			'page_for_posts',
			'blog_public',
			'permalink_structure',
			'default_category',
			'default_post_format',
			'thumbnail_size_w',
			'thumbnail_size_h',
			'medium_size_w',
			'medium_size_h',
			'large_size_w',
			'large_size_h',
			'site_icon',
			// Theme settings (hyphenated keys that don't match prefix rules).
			'astra-settings',
			'generate_settings',
			// Mumega MCP.
			'spai_site_context',
			'spai_site_context_updated',
			// Elementor.
			'elementor_pro_theme_builder_conditions',
		);
	}

	/**
	 * Allowed option key prefixes.
	 *
	 * Any option starting with one of these prefixes is allowed unless
	 * it matches a sensitive pattern from get_blocked_option_patterns().
	 *
	 * @return array Prefix strings.
	 */
	private function get_allowed_option_prefixes() {
		return array(
			'elementor_',
			'wpseo_',
			'rank_math_',
			'astra_',
			'ocean_',
			'theme_mods_',
			'widget_',
			'nav_menu_',
			'sidebars_',
			'spai_',
			'woocommerce_',
			'wp_page_for_privacy_policy',
		);
	}

	/**
	 * Blocked option key patterns (substrings).
	 *
	 * Options matching any of these patterns are NEVER accessible,
	 * even if they match an allowed prefix.
	 *
	 * @return array Blocked substring patterns.
	 */
	private function get_blocked_option_patterns() {
		return array(
			'password',
			'secret',
			'token',
			'auth_key',
			'auth_salt',
			'logged_in_key',
			'logged_in_salt',
			'nonce_key',
			'nonce_salt',
			'secure_auth',
			'credential',
			'api_key',
			'private_key',
			'license_key',
			'stripe_',
			'paypal_',
			'_session',
		);
	}

	/**
	 * Check if an option key is allowed via API.
	 *
	 * Matches exact keys, then allowed prefixes, then blocks sensitive patterns.
	 *
	 * @param string $key Option key.
	 * @return bool True if allowed.
	 */
	private function is_option_allowed( $key ) {
		// Exact match always wins.
		if ( in_array( $key, $this->get_allowed_option_keys(), true ) ) {
			return true;
		}

		// Check blocked patterns first — these override prefix matches.
		$lower_key = strtolower( $key );
		foreach ( $this->get_blocked_option_patterns() as $pattern ) {
			if ( false !== strpos( $lower_key, $pattern ) ) {
				return false;
			}
		}

		// Check allowed prefixes.
		foreach ( $this->get_allowed_option_prefixes() as $prefix ) {
			if ( 0 === strpos( $key, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a single WordPress option by key.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function get_option_handler( $request ) {
		$this->log_activity( 'get_option', $request );

		$key = sanitize_key( (string) $request->get_param( 'key' ) );

		if ( ! $this->is_option_allowed( $key ) ) {
			return $this->error_response(
				'forbidden_option',
				/* translators: %s: option key */
				sprintf( __( 'Option "%s" is not accessible via API. Allowed: core WP options and prefixes: elementor_*, wpseo_*, rank_math_*, astra_*, theme_mods_*, widget_*, woocommerce_*, spai_*. Sensitive keys (passwords, tokens, secrets) are always blocked.', 'mumega-mcp' ), $key ),
				403
			);
		}

		$value = get_option( $key );

		return $this->success_response(
			array(
				'key'   => $key,
				'value' => $value,
			)
		);
	}

	/**
	 * Update a single WordPress option by key.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function update_option_handler( $request ) {
		$this->log_activity( 'update_option', $request );

		$key   = sanitize_key( (string) $request->get_param( 'key' ) );
		$value = $request->get_param( 'value' );

		if ( ! $this->is_option_allowed( $key ) ) {
			return $this->error_response(
				'forbidden_option',
				/* translators: %s: option key */
				sprintf( __( 'Option "%s" is not accessible via API. Allowed: core WP options and prefixes: elementor_*, wpseo_*, rank_math_*, astra_*, theme_mods_*, widget_*, woocommerce_*, spai_*. Sensitive keys (passwords, tokens, secrets) are always blocked.', 'mumega-mcp' ), $key ),
				403
			);
		}

		// Type-specific sanitization
		if ( 'admin_email' === $key ) {
			$value = sanitize_email( $value );
			if ( ! is_email( $value ) ) {
				return $this->error_response( 'invalid_email', __( 'Invalid email address.', 'mumega-mcp' ), 400 );
			}
		} elseif ( in_array( $key, array( 'posts_per_page', 'posts_per_rss', 'page_on_front', 'page_for_posts', 'default_category', 'thumbnail_size_w', 'thumbnail_size_h', 'medium_size_w', 'medium_size_h', 'large_size_w', 'large_size_h', 'site_icon' ), true ) ) {
			$value = absint( $value );
		} elseif ( 'blog_public' === $key ) {
			$value = $value ? 1 : 0;
		} elseif ( is_string( $value ) ) {
			$value = sanitize_text_field( $value );
		}

		$old_value = get_option( $key );

		// Wrap in try/catch: plugins like Elementor hook into update_option
		// and may throw exceptions in REST context (e.g. AJAX auth checks).
		try {
			update_option( $key, $value );
		} catch ( \Exception $e ) {
			// Option was likely still updated before the hook threw.
			// Verify by re-reading.
			$current = get_option( $key );
			if ( $current !== $value ) {
				return $this->error_response(
					'update_failed',
					$e->getMessage(),
					500
				);
			}
		}

		return $this->success_response(
			array(
				'key'       => $key,
				'value'     => get_option( $key ),
				'old_value' => $old_value,
				'updated'   => true,
			)
		);
	}

	/**
	 * Format a term for API response.
	 *
	 * @param WP_Term $term Term object.
	 * @return array Formatted term.
	 */
	private function format_term( $term ) {
		return array(
			'id'          => $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'count'       => $term->count,
			'parent'      => $term->parent,
		);
	}

	/**
	 * Resolve a content ID from a canonical URL.
	 *
	 * @param string $url  Canonical URL.
	 * @param string $type Expected content type (post|page|any).
	 * @return int Resolved content ID or 0.
	 */
	private function resolve_content_id_from_url( $url, $type ) {
		$post_id = function_exists( 'url_to_postid' ) ? absint( url_to_postid( $url ) ) : 0;
		if ( $post_id > 0 ) {
			return $post_id;
		}

		$path = wp_parse_url( $url, PHP_URL_PATH );

		if ( ! is_string( $path ) || '' === trim( $path ) ) {
			return 0;
		}

		$path = trim( $path, '/' );
		if ( '' === $path || ! function_exists( 'get_page_by_path' ) ) {
			return 0;
		}

		$post_types = 'any' === $type ? array( 'post', 'page' ) : array( $type );

		// First try published posts (default behavior).
		$post = get_page_by_path( $path, OBJECT, $post_types );
		if ( $post instanceof WP_Post ) {
			return (int) $post->ID;
		}

		// Also check private, draft, and pending posts (admin API key has full access).
		$non_public_statuses = array( 'private', 'draft', 'pending' );
		foreach ( $non_public_statuses as $status ) {
			$found = get_posts(
				array(
					'name'           => basename( $path ),
					'post_type'      => $post_types,
					'post_status'    => $status,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			if ( ! empty( $found ) ) {
				return (int) $found[0];
			}
		}

		return 0;
	}

	/**
	 * Format a post/page record for search/fetch responses.
	 *
	 * @param WP_Post $post            Post object.
	 * @param bool    $include_content Whether to include full content payload.
	 * @return array Formatted record.
	 */
	private function format_content_item( $post, $include_content ) {
		$excerpt = (string) $post->post_excerpt;
		if ( '' === trim( $excerpt ) ) {
			$excerpt = function_exists( 'wp_trim_words' )
				? wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 40, '...' )
				: '';
		}

		$item = array(
			'id'           => (int) $post->ID,
			'type'         => (string) $post->post_type,
			'status'       => (string) $post->post_status,
			'slug'         => (string) $post->post_name,
			'title'        => get_the_title( $post ),
			'url'          => (string) get_permalink( $post ),
			'excerpt'      => $excerpt,
			'date_gmt'     => (string) $post->post_date_gmt,
			'modified_gmt' => (string) $post->post_modified_gmt,
		);

		if ( $include_content ) {
			$raw_content     = (string) $post->post_content;
			$item['content'] = array(
				'raw'      => $raw_content,
				'rendered' => apply_filters( 'the_content', $raw_content ),
			);

			// Flag Elementor pages so callers know to use wp_get_elementor.
			$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
			if ( ! empty( $elementor_data ) ) {
				$item['elementor'] = true;
				if ( '' === trim( $raw_content ) ) {
					$item['content']['note'] = 'This page is built with Elementor. Use wp_get_elementor to retrieve the layout data.';
				}
			}
		}

		return $item;
	}

	/**
	 * Parse requested OAuth scope string.
	 *
	 * @param string $scope_string Space-separated scope string.
	 * @return array Sanitized scope list.
	 */
	private function parse_requested_oauth_scopes( $scope_string ) {
		$scope_string = trim( (string) $scope_string );
		if ( '' === $scope_string ) {
			return array( 'read' );
		}

		$requested = preg_split( '/\s+/', $scope_string );
		$requested = array_map( 'sanitize_key', (array) $requested );
		$requested = array_values( array_intersect( $requested, array( 'read', 'write', 'admin' ) ) );

		if ( empty( $requested ) ) {
			return array( 'read' );
		}

		if ( in_array( 'admin', $requested, true ) ) {
			return array( 'read', 'write', 'admin' );
		}

		if ( in_array( 'write', $requested, true ) && ! in_array( 'read', $requested, true ) ) {
			$requested[] = 'read';
		}

		return array_values( array_unique( $requested ) );
	}

	/**
	 * Check capability for managing scoped API keys.
	 *
	 * @return bool True if current user can manage keys.
	 */
	private function can_manage_api_keys() {
		return function_exists( 'current_user_can' ) && current_user_can( 'spai_manage_settings' );
	}

	/**
	 * Get the Additional CSS from the Customizer.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_custom_css( $request ) {
		$this->log_activity( 'get_custom_css', $request );

		$css = wp_get_custom_css();

		return $this->success_response(
			array(
				'css'    => $css,
				'length' => strlen( $css ),
			)
		);
	}

	/**
	 * Set or append to the Additional CSS in the Customizer.
	 * Not available in WP.org build (SPAI_WPORG_BUILD).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function set_custom_css( $request ) {
		if ( defined( 'SPAI_WPORG_BUILD' ) ) {
			return $this->error_response( 'not_available', __( 'This endpoint is not available in this build.', 'mumega-mcp' ), 403 );
		}
		$this->log_activity( 'set_custom_css', $request );

		$new_css = $request->get_param( 'css' );
		$mode    = $request->get_param( 'mode' ) ?: 'append';

		if ( 'append' === $mode ) {
			$existing = wp_get_custom_css();
			$css      = $existing . "\n\n" . $new_css;
		} else {
			$css = $new_css;
		}

		$result = wp_update_custom_css_post( $css );

		if ( is_wp_error( $result ) ) {
			return $this->error_response(
				'css_update_failed',
				$result->get_error_message(),
				500
			);
		}

		$response = array(
			'css'    => $css,
			'length' => strlen( $css ),
			'mode'   => $mode,
		);

		// Check for themes with known custom CSS systems that may override Customizer CSS.
		$theme = wp_get_theme();
		$theme_name = $theme->get( 'Name' );
		$theme_warning = null;

		// Eduma / ThimPress themes use their own CSS option — dual-write so CSS actually renders.
		$thim_css_option = get_option( 'thim_custom_css' );
		if ( false !== $thim_css_option || $this->is_eduma_theme() ) {
			update_option( 'thim_custom_css', $css );
			$response['thim_custom_css_synced'] = true;
			$theme_warning = sprintf(
				/* translators: %s: theme name */
				__( "Theme '%s' uses its own CSS system (thim_custom_css). CSS has been dual-written to both WordPress Customizer and Eduma's custom CSS option.", 'mumega-mcp' ),
				$theme_name
			);
		} elseif ( false !== stripos( $theme_name, 'flavor' ) ) {
			$theme_warning = sprintf(
				/* translators: %s: theme name */
				__( "Theme '%s' may use its own CSS system. CSS saved via WordPress Customizer but may not render. Check theme settings.", 'mumega-mcp' ),
				$theme_name
			);
		}

		if ( $theme_warning ) {
			$response['theme_warning'] = $theme_warning;
		}

		// Detect if the theme has removed the wp_custom_css_cb callback from wp_head.
		// This callback is what outputs the <style id="wp-custom-css"> tag on the frontend.
		$css_callback_hooked = has_action( 'wp_head', 'wp_custom_css_cb' );
		$response['wp_custom_css_cb_active'] = (bool) $css_callback_hooked;

		if ( ! $css_callback_hooked ) {
			$response['warning'] = __(
				'CSS saved but may not render on this theme. The active theme does not have the wp_custom_css_cb callback hooked to wp_head, which means WordPress Additional CSS will not be output. Consider using Elementor Custom CSS or a code snippets plugin as an alternative.',
				'mumega-mcp'
			);
		}

		// CSS rendering verification via loopback.
		$verification = array( 'checked' => false );
		$loopback     = wp_remote_get(
			add_query_arg( 'nocache', wp_rand(), home_url( '/' ) ),
			array(
				'timeout'   => 5,
				'sslverify' => false,
			)
		);

		if ( is_wp_error( $loopback ) ) {
			$verification['reason'] = $loopback->get_error_message();
		} else {
			$body = wp_remote_retrieve_body( $loopback );
			$verification['checked']         = true;
			$verification['style_tag_found'] = false !== strpos( $body, '<style id="wp-custom-css">' );
			$snippet                         = substr( trim( $css ), 0, 50 );
			$verification['snippet_found']   = ! empty( $snippet ) && false !== strpos( $body, $snippet );
			$verification['verified']        = $verification['style_tag_found'] && $verification['snippet_found'];

			if ( ! $verification['verified'] && $css_callback_hooked ) {
				$verification['warning'] = __( 'CSS was saved but could not be confirmed in the rendered page. It may be overridden by theme or caching.', 'mumega-mcp' );
			} elseif ( ! $verification['verified'] && ! $css_callback_hooked ) {
				$verification['warning'] = __(
					'CSS saved but may not render on this theme. The active theme may not support WordPress Additional CSS (wp_custom_css_cb is not hooked). Consider using Elementor Custom CSS or a code snippets plugin as an alternative.',
					'mumega-mcp'
				);
			}
		}

		// If CSS rendering could not be verified, provide actionable alternatives.
		if ( ! empty( $verification['checked'] ) && empty( $verification['verified'] ) ) {
			$response['alternatives'] = array(
				'Use wp_set_elementor with page_settings.custom_css for page-specific CSS',
				'Use wp_set_option to write to thim_custom_css directly for Eduma themes',
				'Add CSS via Elementor Custom Code (elementor_snippet post type)',
			);
		}

		$response['verification'] = $verification;

		return $this->success_response( $response );
	}

	/**
	 * List design references.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function list_design_references( $request ) {
		$this->log_activity( 'list_design_references', $request );

		$result = $this->design_references->list_references(
			array(
				'query'           => $request->get_param( 'query' ),
				'page_intent'     => $request->get_param( 'page_intent' ),
				'archetype_class' => $request->get_param( 'archetype_class' ),
				'style'           => $request->get_param( 'style' ),
				'source_type'     => $request->get_param( 'source_type' ),
				'per_page'        => $request->get_param( 'per_page' ),
				'page'            => $request->get_param( 'page' ),
			)
		);

		return $this->success_response( $result );
	}

	/**
	 * Get one design reference.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_design_reference( $request ) {
		$this->log_activity( 'get_design_reference', $request );

		$result = $this->design_references->get_reference( $request['id'] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->success_response( $result );
	}

	/**
	 * Create a design reference.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_design_reference( $request ) {
		$this->log_activity( 'create_design_reference', $request );

		$result = $this->design_references->create_reference( $request->get_json_params() ?: $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->success_response( $result, 201 );
	}

	/**
	 * Update a design reference.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_design_reference( $request ) {
		$this->log_activity( 'update_design_reference', $request );

		$result = $this->design_references->update_reference( $request['id'], $request->get_json_params() ?: $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->success_response( $result );
	}

	/**
	 * Delete all custom CSS (clear the Customizer Additional CSS).
	 * Not available in WP.org build (SPAI_WPORG_BUILD).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function delete_custom_css( $request ) {
		if ( defined( 'SPAI_WPORG_BUILD' ) ) {
			return $this->error_response( 'not_available', __( 'This endpoint is not available in this build.', 'mumega-mcp' ), 403 );
		}
		$this->log_activity( 'delete_custom_css', $request );

		$previous_length = strlen( wp_get_custom_css() );
		$result          = wp_update_custom_css_post( '' );

		if ( is_wp_error( $result ) ) {
			return $this->error_response(
				'css_delete_failed',
				$result->get_error_message(),
				500
			);
		}

		return $this->success_response(
			array(
				'deleted'         => true,
				'previous_length' => $previous_length,
				'message'         => __( 'All custom CSS has been removed.', 'mumega-mcp' ),
			)
		);
	}

	/**
	 * Get the length of custom CSS without returning the full body.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_css_length( $request ) {
		$this->log_activity( 'get_css_length', $request );

		$css = wp_get_custom_css();

		return $this->success_response(
			array(
				'length'     => strlen( $css ),
				'line_count' => $css ? substr_count( $css, "\n" ) + 1 : 0,
				'has_css'    => strlen( $css ) > 0,
			)
		);
	}

	/**
	 * Check if the active theme is Eduma or a ThimPress theme.
	 *
	 * @return bool True if the theme is Eduma/ThimPress.
	 */
	private function is_eduma_theme() {
		$theme    = wp_get_theme();
		$name     = strtolower( $theme->get( 'Name' ) );
		$template = strtolower( $theme->get_template() );

		return false !== strpos( $name, 'eduma' )
			|| false !== strpos( $template, 'eduma' )
			|| false !== strpos( $name, 'thimpress' )
			|| false !== strpos( $template, 'thim' );
	}

	/**
	 * Get detailed theme information.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_theme_info( $request ) {
		$this->log_activity( 'get_theme_info', $request );

		$theme  = wp_get_theme();
		$parent = $theme->parent();

		$info = array(
			'name'           => $theme->get( 'Name' ),
			'version'        => $theme->get( 'Version' ),
			'author'         => $theme->get( 'Author' ),
			'author_uri'     => $theme->get( 'AuthorURI' ),
			'theme_uri'      => $theme->get( 'ThemeURI' ),
			'description'    => $theme->get( 'Description' ),
			'text_domain'    => $theme->get( 'TextDomain' ),
			'is_child'       => (bool) $parent,
			'parent'         => $parent ? array(
				'name'    => $parent->get( 'Name' ),
				'version' => $parent->get( 'Version' ),
			) : null,
			'is_block_theme' => function_exists( 'wp_is_block_theme' ) && wp_is_block_theme(),
			'template'       => $theme->get_template(),
			'stylesheet'     => $theme->get_stylesheet(),
		);

		// Page templates
		$templates              = $theme->get_page_templates();
		$info['page_templates'] = array();
		foreach ( $templates as $slug => $name ) {
			$info['page_templates'][] = array(
				'slug' => $slug,
				'name' => $name,
			);
		}

		// Elementor compatibility
		$info['elementor'] = array(
			'active'      => defined( 'ELEMENTOR_VERSION' ),
			'version'     => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : null,
			'layout_mode' => 'section',
		);

		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$experiment = get_option( 'elementor_experiment-container' );
			if ( 'active' === $experiment || 'default' === $experiment ) {
				$info['elementor']['layout_mode'] = 'container';
			}
		}

		return $this->success_response( $info );
	}

	/**
	 * Flush permalink rewrite rules.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function flush_permalinks( $request ) {
		$this->log_activity( 'flush_permalinks', $request );

		flush_rewrite_rules();

		return $this->success_response(
			array(
				'success'   => true,
				'message'   => __( 'Permalink rewrite rules flushed.', 'mumega-mcp' ),
				'structure' => get_option( 'permalink_structure' ),
			)
		);
	}

	/**
	 * Get site health snapshot.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_site_health( $request ) {
		$this->log_activity( 'get_site_health', $request );

		// Content counts by status.
		$post_counts = array();
		foreach ( array( 'post', 'page' ) as $type ) {
			$counts               = (array) wp_count_posts( $type );
			$post_counts[ $type ] = array();
			foreach ( $counts as $status => $count ) {
				if ( (int) $count > 0 ) {
					$post_counts[ $type ][ $status ] = (int) $count;
				}
			}
		}

		// Pages not in any menu.
		$menu_page_ids = array();
		$menus         = wp_get_nav_menus();
		foreach ( $menus as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			if ( $items ) {
				foreach ( $items as $item ) {
					if ( 'page' === $item->object ) {
						$menu_page_ids[] = (int) $item->object_id;
					}
				}
			}
		}
		$menu_page_ids = array_unique( $menu_page_ids );

		$all_pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$orphan_pages = array_diff( $all_pages, $menu_page_ids );
		$orphan_list  = array();
		foreach ( array_slice( $orphan_pages, 0, 20 ) as $pid ) {
			$orphan_list[] = array(
				'id'    => $pid,
				'title' => get_the_title( $pid ),
				'slug'  => get_post_field( 'post_name', $pid ),
			);
		}

		// Pages missing featured images.
		$no_thumb    = array();
		$pages_query = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'NOT EXISTS',
				),
				),
			)
		);
		foreach ( array_slice( $pages_query, 0, 20 ) as $pid ) {
			$no_thumb[] = array(
				'id'    => $pid,
				'title' => get_the_title( $pid ),
			);
		}

		// Active plugins.
		$active_plugins = get_option( 'active_plugins', array() );
		$plugins_list   = array();
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();
		foreach ( $active_plugins as $plugin_file ) {
			if ( isset( $all_plugins[ $plugin_file ] ) ) {
				$plugins_list[] = array(
					'name'    => $all_plugins[ $plugin_file ]['Name'],
					'version' => $all_plugins[ $plugin_file ]['Version'],
				);
			}
		}

		return $this->success_response(
			array(
				'content_counts'          => $post_counts,
				'orphan_pages'            => array(
					'count' => count( $orphan_pages ),
					'items' => $orphan_list,
				),
				'pages_missing_thumbnail' => array(
					'count' => count( $pages_query ),
					'items' => $no_thumb,
				),
				'active_plugins'          => $plugins_list,
				'wp_version'              => get_bloginfo( 'version' ),
				'php_version'             => PHP_VERSION,
				'permalink_structure'     => get_option( 'permalink_structure' ),
			)
		);
	}

	/**
	 * Create a taxonomy term.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function create_term( $request ) {
		$this->log_activity( 'create_term', $request );

		$taxonomy = sanitize_key( $request->get_param( 'taxonomy' ) );
		$name     = sanitize_text_field( $request->get_param( 'name' ) );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return $this->error_response( 'invalid_taxonomy', __( 'Taxonomy does not exist.', 'mumega-mcp' ), 400 );
		}

		$args = array();
		if ( $request->get_param( 'slug' ) ) {
			$args['slug'] = sanitize_title( $request->get_param( 'slug' ) );
		}
		if ( $request->get_param( 'description' ) ) {
			$args['description'] = sanitize_textarea_field( $request->get_param( 'description' ) );
		}
		if ( $request->get_param( 'parent' ) ) {
			$args['parent'] = absint( $request->get_param( 'parent' ) );
		}

		$result = wp_insert_term( $name, $taxonomy, $args );

		if ( is_wp_error( $result ) ) {
			return $this->error_response( $result->get_error_code(), $result->get_error_message(), 400 );
		}

		$term = get_term( $result['term_id'], $taxonomy );

		return $this->success_response(
			array(
				'id'          => $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'taxonomy'    => $term->taxonomy,
				'description' => $term->description,
				'parent'      => $term->parent,
				'count'       => $term->count,
			),
			201
		);
	}

	/**
	 * Update a taxonomy term.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function update_term( $request ) {
		$this->log_activity( 'update_term', $request );

		$term_id  = absint( $request->get_param( 'id' ) );
		$taxonomy = sanitize_key( $request->get_param( 'taxonomy' ) );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return $this->error_response( 'invalid_taxonomy', __( 'Taxonomy does not exist.', 'mumega-mcp' ), 400 );
		}

		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return $this->error_response( 'not_found', __( 'Term not found.', 'mumega-mcp' ), 404 );
		}

		$args = array();
		if ( $request->get_param( 'name' ) ) {
			$args['name'] = sanitize_text_field( $request->get_param( 'name' ) );
		}
		if ( $request->get_param( 'slug' ) ) {
			$args['slug'] = sanitize_title( $request->get_param( 'slug' ) );
		}
		if ( null !== $request->get_param( 'description' ) ) {
			$args['description'] = sanitize_textarea_field( $request->get_param( 'description' ) );
		}

		if ( empty( $args ) ) {
			return $this->error_response( 'no_changes', __( 'No fields to update.', 'mumega-mcp' ), 400 );
		}

		$result = wp_update_term( $term_id, $taxonomy, $args );

		if ( is_wp_error( $result ) ) {
			return $this->error_response( $result->get_error_code(), $result->get_error_message(), 400 );
		}

		$term = get_term( $result['term_id'], $taxonomy );

		return $this->success_response(
			array(
				'id'          => $term->term_id,
				'name'        => $term->name,
				'slug'        => $term->slug,
				'taxonomy'    => $term->taxonomy,
				'description' => $term->description,
				'parent'      => $term->parent,
				'count'       => $term->count,
			)
		);
	}

	/**
	 * Delete a taxonomy term.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function delete_term( $request ) {
		$this->log_activity( 'delete_term', $request );

		$term_id  = absint( $request->get_param( 'id' ) );
		$taxonomy = sanitize_key( $request->get_param( 'taxonomy' ) );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return $this->error_response( 'invalid_taxonomy', __( 'Taxonomy does not exist.', 'mumega-mcp' ), 400 );
		}

		$term = get_term( $term_id, $taxonomy );
		if ( ! $term || is_wp_error( $term ) ) {
			return $this->error_response( 'not_found', __( 'Term not found.', 'mumega-mcp' ), 404 );
		}

		$result = wp_delete_term( $term_id, $taxonomy );

		if ( is_wp_error( $result ) ) {
			return $this->error_response( $result->get_error_code(), $result->get_error_message(), 400 );
		}

		if ( false === $result ) {
			return $this->error_response( 'delete_failed', __( 'Failed to delete term.', 'mumega-mcp' ), 500 );
		}

		return $this->success_response(
			array(
				'deleted' => true,
				'term_id' => $term_id,
			)
		);
	}

	/**
	 * Get rendered HTML for a page or URL (same-host only).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response.
	 */
	public function get_rendered_html( $request ) {
		$this->log_activity( 'get_rendered_html', $request );

		$post_id   = $request->get_param( 'id' );
		$url       = $request->get_param( 'url' );
		$selector  = $request->get_param( 'selector' );
		$max_bytes = min( absint( $request->get_param( 'max_bytes' ) ?: 51200 ), 204800 );

		// Resolve URL from post ID.
		if ( $post_id ) {
			$post = get_post( absint( $post_id ) );
			if ( ! $post ) {
				return $this->error_response( 'not_found', __( 'Post not found.', 'mumega-mcp' ), 404 );
			}
			if ( 'publish' === $post->post_status ) {
				$url = get_permalink( $post_id );
			} else {
				// Draft/pending — use Elementor preview URL or plain preview.
				$url = add_query_arg( 'elementor-preview', $post_id, get_permalink( $post_id ) );
			}
		}

		if ( empty( $url ) ) {
			return $this->error_response( 'missing_param', __( 'Either id or url is required.', 'mumega-mcp' ), 400 );
		}

		// SSRF guard: only allow same-host URLs.
		$site_host    = wp_parse_url( home_url(), PHP_URL_HOST );
		$request_host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! $request_host || strtolower( $request_host ) !== strtolower( $site_host ) ) {
			return $this->error_response(
				'ssrf_blocked',
				sprintf(
					/* translators: %s: allowed host */
					__( 'Only same-host URLs are allowed. Expected host: %s', 'mumega-mcp' ),
					$site_host
				),
				403
			);
		}

		// Fetch the page.
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 15,
				'sslverify' => false,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->error_response(
				'fetch_failed',
				$response->get_error_message(),
				502
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$html        = wp_remote_retrieve_body( $response );

		$selector_used  = null;
		$selector_found = true;

		// Extract by CSS selector if provided.
		if ( $selector && $html ) {
			$extracted = $this->extract_html_by_selector( $html, $selector );
			if ( null !== $extracted ) {
				$html           = $extracted;
				$selector_used  = $selector;
			} else {
				$selector_found = false;
				$selector_used  = $selector;
			}
		}

		// Truncate if needed.
		$truncated = false;
		if ( strlen( $html ) > $max_bytes ) {
			$html      = substr( $html, 0, $max_bytes );
			$truncated = true;
		}

		return $this->success_response(
			array(
				'url'            => $url,
				'status_code'    => $status_code,
				'html'           => $html,
				'length'         => strlen( $html ),
				'truncated'      => $truncated,
				'selector_used'  => $selector_used,
				'selector_found' => $selector_found,
			)
		);
	}

	/**
	 * Extract HTML content by a simple CSS selector (tag, .class, #id).
	 *
	 * @param string $html     Full HTML string.
	 * @param string $selector CSS selector (tag name, .class, or #id).
	 * @return string|null Extracted HTML or null if not found.
	 */
	private function extract_html_by_selector( $html, $selector ) {
		$doc = new DOMDocument();
		// Suppress warnings for malformed HTML.
		$prev = libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_use_internal_errors( $prev );

		$xpath = new DOMXPath( $doc );

		// Build XPath from simple CSS selector.
		if ( '#' === $selector[0] ) {
			// ID selector.
			$id         = substr( $selector, 1 );
			$xpath_expr = sprintf( '//*[@id="%s"]', $id );
		} elseif ( '.' === $selector[0] ) {
			// Class selector.
			$class      = substr( $selector, 1 );
			$xpath_expr = sprintf( '//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $class );
		} else {
			// Tag selector.
			$xpath_expr = '//' . $selector;
		}

		$nodes = $xpath->query( $xpath_expr );

		if ( ! $nodes || 0 === $nodes->length ) {
			return null;
		}

		$result = '';
		foreach ( $nodes as $node ) {
			$result .= $doc->saveHTML( $node );
		}

		return $result;
	}

	/**
	 * Get available guide topics or a specific guide.
	 *
	 * When called with a topic parameter, returns the full guide.
	 * When called without, returns the list of available topics.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_guides( $request ) {
		$topic = $request->get_param( 'topic' );

		if ( ! empty( $topic ) ) {
			$guide = Spai_Guides::get_guide( $topic );
			if ( is_wp_error( $guide ) ) {
				return $guide;
			}
			return $this->success_response( $guide );
		}

		$topics = Spai_Guides::get_topics();
		return $this->success_response( array(
			'description' => 'Available guide topics. Use wp_get_guide(topic="...") to get the full guide for a topic.',
			'topics'      => $topics,
		) );
	}

	/**
	 * Get a specific guide by topic.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_guide_topic( $request ) {
		$topic = $request->get_param( 'topic' );
		$guide = Spai_Guides::get_guide( $topic );

		if ( is_wp_error( $guide ) ) {
			return $guide;
		}

		return $this->success_response( $guide );
	}

	/**
	 * Get a specific workflow by name.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_workflow( $request ) {
		$name     = $request->get_param( 'name' );
		$workflow = Spai_Workflows::get_workflow( $name );

		if ( is_wp_error( $workflow ) ) {
			return $workflow;
		}

		return $this->success_response( $workflow );
	}

	/**
	 * Get deterministic agent playbook contract.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_agent_playbook( $request ) {
		$name     = $request->get_param( 'name' );
		$playbook = class_exists( 'Spai_Agent_Playbooks' ) ? Spai_Agent_Playbooks::get_playbook( $name ) : array();

		if ( is_wp_error( $playbook ) ) {
			return $playbook;
		}

		$this->log_activity( 'get_agent_playbook', $request, array( 'name' => sanitize_key( (string) $name ) ) );

		return $this->success_response( $playbook );
	}

	/**
	 * Get content coherence report.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_content_coherence( $request ) {
		$this->log_activity( 'get_content_coherence', $request );

		$report = class_exists( 'Spai_Content_Coherence' ) ? Spai_Content_Coherence::get_report() : array();

		return $this->success_response( $report );
	}

	/**
	 * Get all sites in the multisite network.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_network_sites( $request ) {
		if ( ! is_multisite() ) {
			return $this->error_response( 'not_multisite', 'This is not a multisite installation.', 400 );
		}

		$this->log_activity( 'network_sites', $request );

		$per_page = $request->get_param( 'per_page' ) ?: 50;
		$search   = $request->get_param( 'search' );

		$args = array(
			'number' => $per_page,
			'fields' => 'ids',
		);
		if ( $search ) {
			$args['search'] = $search;
		}

		$site_ids = get_sites( $args );
		$sites    = array();

		foreach ( $site_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			$sites[] = array(
				'blog_id'        => (int) $blog_id,
				'name'           => get_bloginfo( 'name' ),
				'url'            => get_bloginfo( 'url' ),
				'admin_url'      => admin_url(),
				'plugin_active'  => is_plugin_active( SPAI_PLUGIN_BASENAME )
					|| ( function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( SPAI_PLUGIN_BASENAME ) ),
				'plugin_version' => defined( 'SPAI_VERSION' ) ? SPAI_VERSION : null,
				'has_api_key'    => ! empty( get_option( 'spai_api_key' ) ) || ! empty( get_option( 'spai_api_keys' ) ),
			);
			restore_current_blog();
		}

		return $this->success_response(
			array(
				'sites'        => $sites,
				'total'        => count( $sites ),
				'is_multisite' => true,
			)
		);
	}

	/**
	 * Get MCP connection details for a specific site in the network.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function switch_site( $request ) {
		if ( ! is_multisite() ) {
			return $this->error_response( 'not_multisite', 'Not a multisite installation.', 400 );
		}

		$this->log_activity( 'network_switch', $request );

		$blog_id = absint( $request->get_param( 'blog_id' ) );
		$blog    = get_blog_details( $blog_id );

		if ( ! $blog ) {
			return $this->error_response( 'invalid_site', 'Site not found.', 404 );
		}

		// Get target site details.
		switch_to_blog( $blog_id );
		$site_url = get_bloginfo( 'url' );
		$has_key  = ! empty( get_option( 'spai_api_key' ) ) || ! empty( get_option( 'spai_api_keys' ) );
		restore_current_blog();

		return $this->success_response(
			array(
				'blog_id'      => $blog_id,
				'name'         => $blog->blogname,
				'url'          => $site_url,
				'mcp_endpoint' => trailingslashit( $site_url ) . 'wp-json/site-pilot-ai/v1/mcp',
				'has_api_key'  => $has_key,
				'hint'         => $has_key
					? 'Connect to the MCP endpoint above with the site\'s API key.'
					: 'This site needs an API key. Generate one from WP Admin > Mumega MCP.',
			)
		);
	}

	/**
	 * Get content statistics across all sites in the network.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function get_network_stats( $request ) {
		if ( ! is_multisite() ) {
			return $this->error_response( 'not_multisite', 'Not a multisite installation.', 400 );
		}

		$this->log_activity( 'network_stats', $request );

		$site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 100,
			)
		);

		$stats = array(
			'site_count' => count( $site_ids ),
			'sites'      => array(),
		);

		foreach ( $site_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			$stats['sites'][] = array(
				'blog_id' => (int) $blog_id,
				'name'    => get_bloginfo( 'name' ),
				'posts'   => (int) wp_count_posts( 'post' )->publish,
				'pages'   => (int) wp_count_posts( 'page' )->publish,
				'media'   => array_sum( array_map( 'intval', (array) wp_count_attachments() ) ),
			);
			restore_current_blog();
		}

		return $this->success_response( $stats );
	}
}
