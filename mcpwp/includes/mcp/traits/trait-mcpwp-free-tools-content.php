<?php
/**
 * Free-tier tool definitions — content category group.
 *
 * Carved verbatim from Mcpwp_MCP_Free_Tools::get_tools() (G2 split). Behavior-identical.
 *
 * @package MCPWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * content free tool providers. Mixed into Mcpwp_MCP_Free_Tools.
 */
trait Mcpwp_Free_Tools_Content_Trait {

	/**
	 * @return array
	 */
	private function get_posts_tools() {
		$tools = array();
		// Posts
		$tools[] = $this->define_tool(
			'wp_list_posts',
			'List posts with optional filters. Supports custom post types including wp_block (reusable blocks/synced patterns). Use ids to fetch specific posts and fields to control which data is returned (e.g. fields=id,title,word_count to get word counts without full content).',
			array(
				'post_type' => array(
					'type'        => 'string',
					'description' => 'Post type (default: post). Use wp_block for reusable blocks/synced patterns, or any public custom post type.',
					'default'     => 'post',
				),
				'per_page' => array(
					'type'        => 'number',
					'description' => 'Number of posts per page (1-100)',
					'default'     => 10,
				),
				'page'     => array(
					'type'        => 'number',
					'description' => 'Current page number',
					'default'     => 1,
				),
				'status'   => array(
					'type'        => 'string',
					'description' => 'Post status filter (publish, draft, pending, private)',
					'default'     => 'publish',
				),
				'category' => array(
					'type'        => 'number',
					'description' => 'Category ID filter',
				),
				'search'   => array(
					'type'        => 'string',
					'description' => 'Search term',
				),
				'ids'      => array(
					'type'        => 'string',
					'description' => 'Comma-separated post IDs to fetch (e.g. "41,42,43")',
				),
				'fields'   => array(
					'type'        => 'string',
					'description' => 'Comma-separated field names to return (e.g. "id,title,word_count,content"). id is always included. Use "content" or "word_count" to include full content and word counts.',
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_create_post',
			'Create a new post. Supports custom post types: use post_type=wp_block for reusable blocks, post_type=elementor_snippet for Elementor Custom Code (requires Elementor Pro).',
			array(
				'title'              => array(
					'type'        => 'string',
					'description' => 'Post title',
					'required'    => true,
				),
				'content'            => array(
					'type'        => 'string',
					'description' => 'Post content (HTML or Gutenberg block markup)',
					'default'     => '',
				),
				'status'             => array(
					'type'        => 'string',
					'description' => 'Post status (publish, draft, pending, private)',
					'default'     => 'draft',
				),
				'post_type'          => array(
					'type'        => 'string',
					'description' => 'Post type (default: post). Use wp_block for reusable blocks, elementor_snippet for Elementor Custom Code.',
					'default'     => 'post',
				),
				'excerpt'            => array(
					'type'        => 'string',
					'description' => 'Post excerpt',
					'default'     => '',
				),
				'slug'               => array(
					'type'        => 'string',
					'description' => 'Post URL slug (e.g. "my-post")',
				),
				'elementor_location' => array(
					'type'        => 'string',
					'description' => 'For elementor_snippet only: injection location (head, body_start, body_end). Default: head.',
					'default'     => 'head',
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_update_post',
			'Update an existing blog post: title, content, status, categories, tags, excerpt, or featured image.',
			array(
				'id'      => array(
					'type'        => 'number',
					'description' => 'Post ID',
					'required'    => true,
				),
				'title'   => array(
					'type'        => 'string',
					'description' => 'Post title',
				),
				'content' => array(
					'type'        => 'string',
					'description' => 'Post content (HTML)',
				),
				'status'  => array(
					'type'        => 'string',
					'description' => 'Post status (publish, draft, pending, private)',
				),
				'excerpt' => array(
					'type'        => 'string',
					'description' => 'Post excerpt',
				),
				'slug'    => array(
					'type'        => 'string',
					'description' => 'Post URL slug (e.g. "my-post")',
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_delete_post',
			'Delete a blog post. Moves to trash by default; set force=true to permanently delete.',
			array(
				'id'    => array(
					'type'        => 'number',
					'description' => 'Post ID',
					'required'    => true,
				),
				'force' => array(
					'type'        => 'boolean',
					'description' => 'Force permanent deletion (bypass trash)',
					'default'     => false,
				),
			)
		);

		return $tools;
	}

	/**
	 * @return array
	 */
	private function get_pages_tools() {
		$tools = array();
		// Pages
		$tools[] = $this->define_tool(
			'wp_list_pages',
			'List pages with optional filters for status, search, and pagination. Use ids to fetch specific pages and fields to control which data is returned (e.g. fields=id,title,word_count).',
			array(
				'per_page' => array(
					'type'        => 'number',
					'description' => 'Number of pages per page (1-100)',
					'default'     => 10,
				),
				'page'     => array(
					'type'        => 'number',
					'description' => 'Current page number',
					'default'     => 1,
				),
				'status'   => array(
					'type'        => 'string',
					'description' => 'Page status filter (publish, draft, pending, private)',
					'default'     => 'publish',
				),
				'search'   => array(
					'type'        => 'string',
					'description' => 'Search term',
				),
				'ids'      => array(
					'type'        => 'string',
					'description' => 'Comma-separated page IDs to fetch (e.g. "95,33,34")',
				),
				'fields'   => array(
					'type'        => 'string',
					'description' => 'Comma-separated field names to return (e.g. "id,title,word_count,content"). id is always included. Use "content" or "word_count" to include full content and word counts.',
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_create_page',
			'Create a new WordPress page. Defaults to draft status — confirm before publishing.',
			array(
				'title'   => array(
					'type'        => 'string',
					'description' => 'Page title',
					'required'    => true,
				),
				'content' => array(
					'type'        => 'string',
					'description' => 'Page content (HTML)',
					'default'     => '',
				),
				'status'  => array(
					'type'        => 'string',
					'description' => 'Page status (publish, draft, pending, private)',
					'default'     => 'draft',
				),
				'slug'    => array(
					'type'        => 'string',
					'description' => 'Page URL slug (e.g. "about-us")',
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_update_page',
			'Update an existing WordPress page: title, content, status, parent, or template.',
			array(
				'id'      => array(
					'type'        => 'number',
					'description' => 'Page ID',
					'required'    => true,
				),
				'title'   => array(
					'type'        => 'string',
					'description' => 'Page title',
				),
				'content' => array(
					'type'        => 'string',
					'description' => 'Page content (HTML)',
				),
				'status'  => array(
					'type'        => 'string',
					'description' => 'Page status (publish, draft, pending, private)',
				),
				'slug'    => array(
					'type'        => 'string',
					'description' => 'Page URL slug (e.g. "about-us")',
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_delete_page',
			'Delete a page (moves to trash by default, use force for permanent deletion)',
			array(
				'id'    => array(
					'type'        => 'number',
					'description' => 'Page ID',
					'required'    => true,
				),
				'force' => array(
					'type'        => 'boolean',
					'description' => 'Force permanent deletion (bypass trash)',
					'default'     => false,
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_clone_page',
			'Duplicate a page including its content, Elementor data, template, and featured image',
			array(
				'id'     => array(
					'type'        => 'number',
					'description' => 'Page ID to clone',
					'required'    => true,
				),
				'title'  => array(
					'type'        => 'string',
					'description' => 'Title for the cloned page (defaults to original with Copy suffix)',
				),
				'status' => array(
					'type'        => 'string',
					'description' => 'Status for cloned page (publish, draft, pending, private)',
					'default'     => 'draft',
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_get_page_by_slug',
			'Fetch a WordPress page by its URL slug (e.g., "about", "contact"). Use when you know the page URL but not the ID.',
			array(
				'slug' => array(
					'type'        => 'string',
					'description' => 'Page URL slug',
					'required'    => true,
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_set_featured_image',
			'Set or remove the featured image (thumbnail) for a post or page',
			array(
				'id'       => array(
					'type'        => 'number',
					'description' => 'Post or page ID',
					'required'    => true,
				),
				'media_id' => array(
					'type'        => 'number',
					'description' => 'Media attachment ID. Use 0 to remove featured image.',
					'required'    => true,
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_list_categories',
			'List post categories with IDs, names, slugs, and post counts',
			array(
				'per_page' => array(
					'type'        => 'number',
					'description' => 'Results per page (1-200)',
					'default'     => 100,
				),
				'search'   => array(
					'type'        => 'string',
					'description' => 'Search term',
				),
				'parent'   => array(
					'type'        => 'number',
					'description' => 'Parent category ID to list children',
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_list_tags',
			'List all post tags with IDs, names, slugs, and post counts. Use to find tag IDs before assigning them to posts.',
			array(
				'per_page' => array(
					'type'        => 'number',
					'description' => 'Results per page (1-200)',
					'default'     => 100,
				),
				'search'   => array(
					'type'        => 'string',
					'description' => 'Search term',
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_batch_update',
			'Execute multiple REST API operations in a single request (max 25). Operations run sequentially and each returns {index, status, data}. Example: [{"method":"PUT","path":"/posts/42","body":{"title":"New Title"}}, {"method":"GET","path":"/pages"}]. Use this to reduce round-trips when making many changes.',
			array(
				'operations' => array(
					'type'        => 'array',
					'description' => 'Array of operation objects. Each must have: method (GET/POST/PUT/DELETE), path (relative to /mcpwp/v1/, e.g. "/pages/42"), body (optional object — used as request body for POST/PUT, query params for GET/DELETE)',
					'required'    => true,
				),
			)
		);

		return $tools;
	}

	/**
	 * @return array
	 */
	private function get_drafts_tools() {
		$tools = array();
		// Drafts
		$tools[] = $this->define_tool(
			'wp_list_drafts',
			'List all draft posts and pages. Use to review unpublished content or find work in progress.',
			array(
				'type' => array(
					'type'        => 'string',
					'description' => 'Post type filter (post, page, all)',
					'default'     => 'all',
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_delete_all_drafts',
			'Bulk delete all draft posts and pages. Permanent — cannot be undone. Always confirm with user first.',
			array(
				'type'  => array(
					'type'        => 'string',
					'description' => 'Post type filter (post, page, all)',
					'default'     => 'all',
				),
				'force' => array(
					'type'        => 'boolean',
					'description' => 'Force permanent deletion',
					'default'     => false,
				),
			)
		);

		return $tools;
	}

	/**
	 * @return array
	 */
	private function get_post_meta_tools() {
		$tools = array();
		// Post Meta
		$tools[] = $this->define_tool(
			'wp_get_post_meta',
			'Get post meta for a post or page. Returns a single key or all non-sensitive meta.',
			array(
				'id' => array(
					'type'        => 'number',
					'description' => 'Post or page ID',
					'required'    => true,
				),
				'key' => array(
					'type'        => 'string',
					'description' => 'Specific meta key to retrieve (omit to get all)',
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_set_post_meta',
			'Set a single post meta value. Blocked keys (passwords, secrets, internal WP keys) are rejected.',
			array(
				'id' => array(
					'type'        => 'number',
					'description' => 'Post or page ID',
					'required'    => true,
				),
				'key' => array(
					'type'        => 'string',
					'description' => 'Meta key to set',
					'required'    => true,
				),
				'value' => array(
					'type'        => 'string',
					'description' => 'Meta value to set',
					'required'    => true,
				),
			)
		);

		return $tools;
	}

	/**
	 * @return array
	 */
	private function get_option_mgmt_tools() {
		$tools = array();
		// Option Management
		$tools[] = $this->define_tool(
			'wp_get_option',
			'Get a single WordPress option by key. Supports core WP options (blogname, show_on_front, etc.) and plugin prefixes: elementor_*, wpseo_*, rank_math_*, astra_*, theme_mods_*, widget_*, woocommerce_*, mcpwp_*. Sensitive keys (passwords, tokens, secrets) are always blocked.',
			array(
				'key' => array(
					'type'        => 'string',
					'description' => 'Option key (e.g., blogname, show_on_front, elementor_active_kit, wpseo_titles)',
					'required'    => true,
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_update_option',
			'Update a single WordPress option by key. Supports core WP options and plugin prefixes: elementor_*, wpseo_*, rank_math_*, astra_*, theme_mods_*, widget_*, woocommerce_*, mcpwp_*. Sensitive keys (passwords, tokens, secrets) are always blocked.',
			array(
				'key' => array(
					'type'        => 'string',
					'description' => 'Option key to update',
					'required'    => true,
				),
				'value' => array(
					'type'        => 'string',
					'description' => 'New value for the option',
					'required'    => true,
				),
			)
		);

		return $tools;
	}

	/**
	 * @return array
	 */
	private function get_bulk_create_pages_tools() {
		$tools = array();
		// Bulk Create Pages
		$tools[] = $this->define_tool(
			'wp_bulk_create_pages',
			'Create multiple pages in one call. Returns array of created pages with IDs and slugs.',
			array(
				'pages' => array(
					'type'        => 'array',
					'description' => 'Array of page objects with: title (required), content, status (default: draft), slug, parent, template',
					'required'    => true,
				),
			)
		);

		return $tools;
	}

	/**
	 * @return array
	 */
	private function get_bulk_create_posts_tools() {
		$tools = array();
		// Bulk Create Posts
		$tools[] = $this->define_tool(
			'wp_bulk_create_posts',
			'Create multiple blog posts in one call. Returns array of created posts with IDs and slugs.',
			array(
				'posts' => array(
					'type'        => 'array',
					'description' => 'Array of post objects with: title (required), content, status (default: draft), categories (array of IDs), tags (array of strings), excerpt, slug, post_type',
					'required'    => true,
				),
			)
		);

		return $tools;
	}

	/**
	 * @return array
	 */
	private function get_bulk_update_posts_tools() {
		$tools = array();
		// Bulk Update Posts
		$tools[] = $this->define_tool(
			'wp_bulk_update_posts',
			'Update multiple posts in one call. Each item must include id plus fields to update. Returns array of updated posts and any errors.',
			array(
				'posts' => array(
					'type'        => 'array',
					'description' => 'Array of post objects with: id (required), title, content, status, excerpt, slug, categories, tags',
					'required'    => true,
				),
			)
		);

		return $tools;
	}

	/**
	 * @return array
	 */
	private function get_bulk_update_pages_tools() {
		$tools = array();
		// Bulk Update Pages
		$tools[] = $this->define_tool(
			'wp_bulk_update_pages',
			'Update multiple pages in one call. Each item must include id plus fields to update. Returns array of updated pages and any errors.',
			array(
				'pages' => array(
					'type'        => 'array',
					'description' => 'Array of page objects with: id (required), title, content, status, slug, parent, template',
					'required'    => true,
				),
			)
		);

		return $tools;
	}

	/**
	 * @return array
	 */
	private function get_taxonomy_tools() {
		$tools = array();
		// Taxonomy Management
		$tools[] = $this->define_tool(
			'wp_create_term',
			'Create a new taxonomy term (category, tag, or custom taxonomy)',
			array(
				'taxonomy' => array(
					'type'        => 'string',
					'description' => 'Taxonomy name (category, post_tag, or custom)',
					'required'    => true,
				),
				'name'     => array(
					'type'        => 'string',
					'description' => 'Term name',
					'required'    => true,
				),
				'slug'     => array(
					'type'        => 'string',
					'description' => 'Term URL slug',
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'Term description',
				),
				'parent'   => array(
					'type'        => 'number',
					'description' => 'Parent term ID (for hierarchical taxonomies)',
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_update_term',
			'Update an existing taxonomy term (rename, change slug, update description)',
			array(
				'id'       => array(
					'type'        => 'number',
					'description' => 'Term ID',
					'required'    => true,
				),
				'taxonomy' => array(
					'type'        => 'string',
					'description' => 'Taxonomy name (category, post_tag, or custom)',
					'required'    => true,
				),
				'name'     => array(
					'type'        => 'string',
					'description' => 'New term name',
				),
				'slug'     => array(
					'type'        => 'string',
					'description' => 'New term slug',
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'New term description',
				),
			)
		);

		$tools[] = $this->define_tool(
			'wp_delete_term',
			'Delete a WordPress taxonomy term (category or tag) by ID. Does not delete associated posts.',
			array(
				'id'       => array(
					'type'        => 'number',
					'description' => 'Term ID',
					'required'    => true,
				),
				'taxonomy' => array(
					'type'        => 'string',
					'description' => 'Taxonomy name (category, post_tag, or custom)',
					'required'    => true,
				),
			)
		);

		return $tools;
	}
}
