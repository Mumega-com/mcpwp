<?php

/**
 * PHPUnit bootstrap with lightweight WordPress stubs.
 */

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (! defined('SPAI_VERSION')) {
    define('SPAI_VERSION', 'test');
}

if (! defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

$GLOBALS['spai_test_options'] = array();
$GLOBALS['spai_test_transients'] = array();
$GLOBALS['spai_test_current_user'] = 0;
$GLOBALS['spai_test_users'] = array(
    (object) array( 'ID' => 2, 'roles' => array( 'spai_api_agent' ) ),
);

class WP_Error
{
    private $code;
    private $message;
    private $data;

    public function __construct($code = '', $message = '', $data = null)
    {
        $this->code    = $code;
        $this->message = $message;
        $this->data    = $data;
    }

    public function get_error_code()
    {
        return $this->code;
    }

    public function get_error_message()
    {
        return $this->message;
    }

    public function get_error_data()
    {
        return $this->data;
    }
}

class WP_REST_Response
{
    private $data;
    private $status;
    private $headers = array();

    public function __construct($data = null, $status = 200)
    {
        $this->data   = $data;
        $this->status = $status;
    }

    public function get_data()
    {
        return $this->data;
    }

    public function set_data($data)
    {
        $this->data = $data;
    }

    public function get_status()
    {
        return $this->status;
    }

    public function header($name, $value)
    {
        $this->headers[ $name ] = $value;
    }

    public function get_headers()
    {
        return $this->headers;
    }
}

class WP_REST_Request
{
    private $method;
    private $route;
    private $params;
    private $headers;
    private $json_params;

    public function __construct(
        $method = 'GET',
        $route = '/',
        $params = array(),
        $headers = array(),
        $json_params = null
    ) {
        $this->method     = strtoupper($method);
        $this->route      = $route;
        $this->params     = is_array($params) ? $params : array();
        $this->headers    = is_array($headers) ? $headers : array();
        $this->json_params = $json_params;
    }

    public function get_method()
    {
        return $this->method;
    }

    public function get_route()
    {
        return $this->route;
    }

    public function get_param($key)
    {
        return isset($this->params[ $key ]) ? $this->params[ $key ] : null;
    }

    public function set_param($key, $value)
    {
        $this->params[ $key ] = $value;
    }

    public function get_params()
    {
        return $this->params;
    }

    public function get_header($name)
    {
        $target = strtolower($name);
        foreach ($this->headers as $header => $value) {
            if (strtolower($header) === $target) {
                return $value;
            }
        }
        return null;
    }

    public function set_header($name, $value)
    {
        $this->headers[ $name ] = $value;
    }

    public function get_json_params()
    {
        return $this->json_params;
    }
}

class WP_REST_Server
{
    public const READABLE  = 'GET';
    public const CREATABLE = 'POST';
    public const EDITABLE  = 'POST,PUT,PATCH';
    public const DELETABLE = 'DELETE';
}

function __($text)
{
    return $text;
}

function _n($single, $plural, $number)
{
    return 1 === (int) $number ? $single : $plural;
}

function is_wp_error($value)
{
    return $value instanceof WP_Error;
}

function sanitize_text_field($value)
{
    return trim((string) $value);
}

function sanitize_key($value)
{
    $value = strtolower((string) $value);
    return preg_replace('/[^a-z0-9_\\-]/', '', $value);
}

function sanitize_title($value)
{
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim($value, '-');
}

function sanitize_email($value)
{
    return filter_var($value, FILTER_SANITIZE_EMAIL);
}

function sanitize_user($value)
{
    return preg_replace('/[^a-zA-Z0-9_\\-.@]/', '', (string) $value);
}

function absint($value)
{
    return abs((int) $value);
}

function esc_url_raw($value)
{
    return filter_var((string) $value, FILTER_SANITIZE_URL);
}

function is_email($value)
{
    return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
}

function wp_unslash($value)
{
    return is_string($value) ? stripslashes($value) : $value;
}

function wp_set_current_user($user_id)
{
    $GLOBALS['spai_test_current_user'] = (int) $user_id;
}

function get_current_user_id()
{
    return (int) $GLOBALS['spai_test_current_user'];
}

function get_userdata($user_id)
{
    foreach ($GLOBALS['spai_test_users'] as $user) {
        if ((int) $user->ID === (int) $user_id) {
            if (empty($user->user_login)) {
                $user->user_login = 'spai_test_user';
            }
            return $user;
        }
    }
    return false;
}

function get_users($args = array())
{
    $users = isset($GLOBALS['spai_test_users']) ? $GLOBALS['spai_test_users'] : array();
    if (empty($args['role'])) {
        return $users;
    }

    $filtered = array();
    foreach ($users as $user) {
        if (! empty($user->roles) && in_array($args['role'], $user->roles, true)) {
            $filtered[] = $user;
        }
    }

    return $filtered;
}

function current_user_can($capability)
{
    // Test harness grants all permissions to service user.
    return 0 !== (int) $GLOBALS['spai_test_current_user'];
}

function get_option($name, $default = false)
{
    return array_key_exists($name, $GLOBALS['spai_test_options']) ? $GLOBALS['spai_test_options'][ $name ] : $default;
}

function update_option($name, $value)
{
    $GLOBALS['spai_test_options'][ $name ] = $value;
    return true;
}

function delete_option($name)
{
    unset($GLOBALS['spai_test_options'][ $name ]);
    return true;
}

function set_transient($name, $value, $expiration)
{
    $GLOBALS['spai_test_transients'][ $name ] = array(
        'value'   => $value,
        'expires' => time() + (int) $expiration,
    );
    return true;
}

function get_transient($name)
{
    if (! isset($GLOBALS['spai_test_transients'][ $name ])) {
        return false;
    }

    $item = $GLOBALS['spai_test_transients'][ $name ];
    if ($item['expires'] <= time()) {
        unset($GLOBALS['spai_test_transients'][ $name ]);
        return false;
    }

    return $item['value'];
}

function delete_transient($name)
{
    unset($GLOBALS['spai_test_transients'][ $name ]);
    return true;
}

// -------------------------------------------------------------------------
// Minimal hooks system (filters/actions) for unit tests.
// -------------------------------------------------------------------------

$GLOBALS['spai_test_filters'] = array();
$GLOBALS['spai_test_actions'] = array();

function add_filter($tag, $callback, $priority = 10, $accepted_args = 1)
{
    $GLOBALS['spai_test_filters'][ $tag ][ (int) $priority ][] = array(
        'callback'      => $callback,
        'accepted_args' => (int) $accepted_args,
    );
    return true;
}

function apply_filters($tag, $value)
{
    $args = func_get_args();
    array_shift($args); // $tag
    $value = array_shift($args); // $value

    if (empty($GLOBALS['spai_test_filters'][ $tag ])) {
        return $value;
    }

    $by_priority = $GLOBALS['spai_test_filters'][ $tag ];
    ksort($by_priority);
    foreach ($by_priority as $callbacks) {
        foreach ($callbacks as $item) {
            $cb_args = array_merge(array( $value ), array_slice($args, 0, $item['accepted_args'] - 1));
            $value   = call_user_func_array($item['callback'], $cb_args);
        }
    }

    return $value;
}

function add_action($tag, $callback, $priority = 10, $accepted_args = 1)
{
    $GLOBALS['spai_test_actions'][ $tag ][ (int) $priority ][] = array(
        'callback'      => $callback,
        'accepted_args' => (int) $accepted_args,
    );
    return true;
}

function do_action($tag)
{
    $args = func_get_args();
    array_shift($args);

    if (empty($GLOBALS['spai_test_actions'][ $tag ])) {
        return;
    }

    $by_priority = $GLOBALS['spai_test_actions'][ $tag ];
    ksort($by_priority);
    foreach ($by_priority as $callbacks) {
        foreach ($callbacks as $item) {
            $cb_args = array_slice($args, 0, $item['accepted_args']);
            call_user_func_array($item['callback'], $cb_args);
        }
    }
}

function current_time($type)
{
    if ('mysql' === $type) {
        return gmdate('Y-m-d H:i:s');
    }
    if ('c' === $type) {
        return gmdate('c');
    }
    return time();
}

function get_site_url()
{
    return 'https://example.com';
}

function get_bloginfo($show = '')
{
    if ('version' === $show) {
        return '6.9.4';
    }
    if ('name' === $show) {
        return 'Example Site';
    }
    return '';
}

function is_multisite()
{
    return false;
}

function wp_generate_uuid4()
{
    return '00000000-0000-4000-8000-' . str_pad((string) mt_rand(1, 999999999999), 12, '0', STR_PAD_LEFT);
}

function wp_hash_password($password)
{
    return password_hash((string) $password, PASSWORD_BCRYPT);
}

function wp_check_password($password, $hash)
{
    $hash = (string) $hash;
    if (0 === strpos($hash, '$2') || 0 === strpos($hash, '$argon')) {
        return password_verify((string) $password, $hash);
    }
    return hash_equals($hash, (string) $password);
}

function wp_json_encode($value, $flags = 0)
{
    return json_encode($value, $flags);
}

function wp_parse_args($args, $defaults = array())
{
    $args = is_array($args) ? $args : array();
    return array_merge($defaults, $args);
}

function register_rest_route()
{
    return true;
}

function rest_do_request()
{
    return new WP_REST_Response(array( 'ok' => true ), 200);
}

// ── Post / meta stubs (controllable via globals) ─────────────────────────

$GLOBALS['_spai_test_posts'] = array();
$GLOBALS['_spai_test_meta']  = array();
$GLOBALS['_spai_test_menu_page_ids'] = array();

function get_post($id)
{
    $id = (int) $id;
    return isset($GLOBALS['_spai_test_posts'][ $id ]) ? $GLOBALS['_spai_test_posts'][ $id ] : null;
}

function get_posts($args = array())
{
    $posts = array_values($GLOBALS['_spai_test_posts']);
    $types = isset($args['post_type']) ? (array) $args['post_type'] : array();
    $statuses = isset($args['post_status']) ? (array) $args['post_status'] : array();

    $posts = array_values(array_filter($posts, function ($post) use ($types, $statuses) {
        if (! empty($types) && ! in_array($post->post_type, $types, true)) {
            return false;
        }
        if (! empty($statuses) && ! in_array($post->post_status, $statuses, true)) {
            return false;
        }
        return true;
    }));

    $limit = isset($args['posts_per_page']) ? (int) $args['posts_per_page'] : -1;
    if ($limit > -1) {
        $posts = array_slice($posts, 0, $limit);
    }

    if (isset($args['fields']) && 'ids' === $args['fields']) {
        return array_map(function ($post) {
            return (int) $post->ID;
        }, $posts);
    }

    return $posts;
}

function post_type_exists($post_type)
{
    $known = array( 'post', 'page', 'wp_block', 'elementor_library', 'elementor_snippet' );
    return in_array((string) $post_type, $known, true);
}

function get_post_type_object($post_type)
{
    if (! post_type_exists($post_type)) {
        return null;
    }
    return (object) array(
        'name'   => $post_type,
        'public' => in_array((string) $post_type, array( 'post', 'page' ), true),
    );
}

function wp_insert_post($postarr, $wp_error = false)
{
    $next_id = empty($GLOBALS['_spai_test_posts']) ? 1 : max(array_map('intval', array_keys($GLOBALS['_spai_test_posts']))) + 1;
    $post_id = isset($postarr['ID']) ? (int) $postarr['ID'] : $next_id;

    $GLOBALS['_spai_test_posts'][ $post_id ] = (object) array(
        'ID'                => $post_id,
        'post_type'         => isset($postarr['post_type']) ? $postarr['post_type'] : 'post',
        'post_title'        => isset($postarr['post_title']) ? $postarr['post_title'] : '',
        'post_name'         => isset($postarr['post_name']) ? $postarr['post_name'] : sanitize_title(isset($postarr['post_title']) ? $postarr['post_title'] : ''),
        'post_status'       => isset($postarr['post_status']) ? $postarr['post_status'] : 'draft',
        'post_date'         => '2026-06-06 00:00:00',
        'post_modified'     => '2026-06-06 00:00:00',
        'post_modified_gmt' => '2026-06-06 00:00:00',
        'post_parent'       => 0,
        'menu_order'        => 0,
        'post_author'       => 1,
        'post_content'      => isset($postarr['post_content']) ? $postarr['post_content'] : '',
        'post_excerpt'      => isset($postarr['post_excerpt']) ? $postarr['post_excerpt'] : '',
    );

    return $post_id;
}

function wp_update_post($postarr, $wp_error = false)
{
    if (empty($postarr['ID']) || empty($GLOBALS['_spai_test_posts'][ (int) $postarr['ID'] ])) {
        return $wp_error ? new WP_Error('not_found', 'Post not found.', array( 'status' => 404 )) : 0;
    }

    $post = $GLOBALS['_spai_test_posts'][ (int) $postarr['ID'] ];
    foreach ($postarr as $key => $value) {
        if ('ID' === $key) {
            continue;
        }
        $post->{$key} = $value;
    }
    $post->post_modified = '2026-06-06 01:00:00';
    $post->post_modified_gmt = '2026-06-06 01:00:00';
    $GLOBALS['_spai_test_posts'][ (int) $postarr['ID'] ] = $post;

    return (int) $postarr['ID'];
}

function wp_count_posts($type)
{
    $counts = array(
        'publish' => 0,
        'draft'   => 0,
        'private' => 0,
        'trash'   => 0,
    );

    foreach ($GLOBALS['_spai_test_posts'] as $post) {
        if ($post->post_type === $type && isset($counts[ $post->post_status ])) {
            $counts[ $post->post_status ]++;
        }
    }

    return (object) $counts;
}

function wp_strip_all_tags($value)
{
    return strip_tags((string) $value);
}

function get_post_modified_time($format, $gmt, $post)
{
    $timestamp = isset($post->modified_ts) ? (int) $post->modified_ts : time();
    if ('U' === $format) {
        return $timestamp;
    }
    return gmdate('c', $timestamp);
}

function get_the_title($post)
{
    if (is_object($post)) {
        return isset($post->post_title) ? $post->post_title : '';
    }
    $post = get_post($post);
    return $post ? $post->post_title : '';
}

function get_permalink($post)
{
    $post_id = is_object($post) ? (int) $post->ID : (int) $post;
    return 'https://example.com/?p=' . $post_id;
}

function wp_get_nav_menus()
{
    return empty($GLOBALS['_spai_test_menu_page_ids']) ? array() : array((object) array( 'term_id' => 1 ));
}

function wp_get_nav_menu_items($term_id)
{
    return array_map(function ($post_id) {
        return (object) array(
            'object'    => 'page',
            'object_id' => (int) $post_id,
        );
    }, $GLOBALS['_spai_test_menu_page_ids']);
}

function get_post_meta($post_id, $key = '', $single = false)
{
    $post_id = (int) $post_id;
    if ('' === $key) {
        return isset($GLOBALS['_spai_test_meta'][ $post_id ]) ? $GLOBALS['_spai_test_meta'][ $post_id ] : array();
    }
    if (isset($GLOBALS['_spai_test_meta'][ $post_id ][ $key ])) {
        return $GLOBALS['_spai_test_meta'][ $post_id ][ $key ];
    }
    return $single ? '' : array();
}

function update_post_meta($post_id, $key, $value)
{
    $GLOBALS['_spai_test_meta'][ (int) $post_id ][ $key ] = $value;
    return true;
}

function sanitize_html_class($class)
{
    return preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $class);
}

function admin_url($path = '')
{
    return 'https://example.com/wp-admin/' . ltrim($path, '/');
}

function wp_slash($value)
{
    return is_string($value) ? addslashes($value) : $value;
}

require_once dirname(__DIR__) . '/includes/traits/trait-spai-api-auth.php';
require_once dirname(__DIR__) . '/includes/traits/trait-spai-sanitization.php';
require_once dirname(__DIR__) . '/includes/traits/trait-spai-logging.php';
require_once dirname(__DIR__) . '/includes/class-spai-rate-limiter.php';
require_once dirname(__DIR__) . '/includes/core/class-spai-event-store.php';
require_once dirname(__DIR__) . '/includes/core/class-spai-site-state.php';
require_once dirname(__DIR__) . '/includes/core/class-spai-agent-playbooks.php';
require_once dirname(__DIR__) . '/includes/core/class-spai-content-coherence.php';
require_once dirname(__DIR__) . '/includes/core/class-spai-seo-audit-store.php';
require_once dirname(__DIR__) . '/includes/core/class-spai-seo-autofix.php';
require_once dirname(__DIR__) . '/includes/core/class-spai-search-performance.php';
require_once dirname(__DIR__) . '/includes/core/class-spai-woocommerce-seo.php';
require_once dirname(__DIR__) . '/includes/api/class-spai-rest-api.php';
require_once dirname(__DIR__) . '/includes/mcp/class-spai-mcp-tool-registry.php';
require_once dirname(__DIR__) . '/includes/mcp/class-spai-mcp-free-tools.php';
require_once dirname(__DIR__) . '/includes/mcp/class-spai-mcp-pro-tools.php';
require_once dirname(__DIR__) . '/includes/mcp/class-spai-integration.php';
require_once dirname(__DIR__) . '/includes/api/class-spai-rest-mcp.php';
require_once dirname(__DIR__) . '/includes/api/class-spai-rest-menus.php';
require_once dirname(__DIR__) . '/includes/core/class-spai-elementor-basic.php';
require_once dirname(__DIR__) . '/includes/pro/api/class-spai-rest-elementor-pro.php';
