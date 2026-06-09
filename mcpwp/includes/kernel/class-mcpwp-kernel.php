<?php
/**
 * Microkernel — discovers, orders, gates, and boots modules.
 *
 * Small and stable: the kernel owns no domain logic. It hosts modules, each of
 * which registers its services, tools, routes, and admin pages, then boots.
 * The invariant: the kernel never imports a module; modules depend on the
 * kernel, never the reverse.
 *
 * v5.0-a status: ADDITIVE. The kernel exists and is fully unit-tested, but the
 * live plugin boot path (mcpwp.php) is untouched — nothing routes through the
 * kernel yet. The first live carve happens in v5.0-b. This guarantees v5.0-a
 * cannot change shipped v3.0.0 behavior.
 *
 * @package MCPWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The MCPWP microkernel.
 */
class Mcpwp_Kernel {

	/** @var Mcpwp_Module_Registry */
	private $registry;

	/** @var Mcpwp_Container */
	private $container;

	/** @var bool */
	private $booted = false;

	/** @var array Collected tool providers from booted modules. */
	private $tools = array();

	/** @var array Collected route providers from booted modules. */
	private $routes = array();

	/** @var array Collected admin page providers from booted modules. */
	private $admin = array();

	/** @var string[] Ids of modules skipped at boot (gated out). */
	private $skipped = array();

	/**
	 * Resolves the current tier: returns 'pro' or 'free'.
	 *
	 * @var callable
	 */
	private $tier_resolver;

	/**
	 * Returns whether a depends_on key (e.g. WP plugin basename) is satisfied.
	 *
	 * @var callable
	 */
	private $plugin_checker;

	/**
	 * @param Mcpwp_Container|null       $container Optional container.
	 * @param Mcpwp_Module_Registry|null $registry  Optional registry.
	 */
	public function __construct( $container = null, $registry = null ) {
		$this->container = $container instanceof Mcpwp_Container ? $container : new Mcpwp_Container();
		$this->registry  = $registry instanceof Mcpwp_Module_Registry ? $registry : new Mcpwp_Module_Registry();

		// Conservative defaults: everything free, no plugin deps satisfied unless
		// a real checker is injected. Callers override via the setters.
		$this->tier_resolver  = function () {
			return 'free';
		};
		$this->plugin_checker = function ( $key ) {
			return false;
		};
	}

	/**
	 * @return Mcpwp_Container
	 */
	public function container() {
		return $this->container;
	}

	/**
	 * @return Mcpwp_Module_Registry
	 */
	public function registry() {
		return $this->registry;
	}

	/**
	 * Set the tier resolver (returns 'pro' or 'free').
	 *
	 * @param callable $resolver Resolver.
	 * @return void
	 */
	public function set_tier_resolver( callable $resolver ) {
		$this->tier_resolver = $resolver;
	}

	/**
	 * Set the depends_on checker (receives a key, returns bool).
	 *
	 * @param callable $checker Checker.
	 * @return void
	 */
	public function set_plugin_checker( callable $checker ) {
		$this->plugin_checker = $checker;
	}

	/**
	 * Register a module from a manifest, an array, a Mcpwp_Module instance, or a
	 * path to a `module.php` that returns one of those.
	 *
	 * @param mixed $module Manifest|array|Mcpwp_Module|string(path).
	 * @return void
	 * @throws InvalidArgumentException On an unusable module value.
	 */
	public function register_module( $module ) {
		$this->registry->register( $this->to_manifest( $module ) );
	}

	/**
	 * Discover modules by scanning `$dir/<id>/module.php` and registering each.
	 *
	 * @param string $dir Directory containing module folders.
	 * @return void
	 */
	public function discover( $dir ) {
		$pattern = rtrim( $dir, '/\\' ) . '/*/module.php';
		$files   = glob( $pattern );
		if ( ! is_array( $files ) ) {
			return;
		}
		sort( $files );
		foreach ( $files as $file ) {
			$this->register_module( $file );
		}
	}

	/**
	 * Boot all registered modules in dependency order. Modules gated out by
	 * tier or unmet depends_on are skipped gracefully (no services/tools/routes
	 * registered for them). Idempotent: a second call is a no-op.
	 *
	 * @return void
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}

		foreach ( $this->registry->in_dependency_order() as $manifest ) {
			if ( ! $this->tier_satisfied( $manifest ) || ! $this->depends_satisfied( $manifest ) ) {
				$this->skipped[] = $manifest->id;
				continue;
			}

			foreach ( $manifest->services as $id => $factory ) {
				if ( is_callable( $factory ) ) {
					$this->container->set( $id, $factory );
				}
			}

			foreach ( $manifest->tools as $tool ) {
				$this->tools[] = $tool;
			}
			foreach ( $manifest->routes as $route ) {
				$this->routes[] = $route;
			}
			foreach ( $manifest->admin as $page ) {
				$this->admin[] = $page;
			}

			if ( is_callable( $manifest->boot ) ) {
				call_user_func( $manifest->boot, $this );
			}
		}

		$this->booted = true;
	}

	/**
	 * @return bool
	 */
	public function is_booted() {
		return $this->booted;
	}

	/**
	 * @return array Collected tool providers (only from booted modules).
	 */
	public function get_tools() {
		return $this->tools;
	}

	/**
	 * @return array Collected route providers.
	 */
	public function get_routes() {
		return $this->routes;
	}

	/**
	 * @return array Collected admin page providers.
	 */
	public function get_admin_pages() {
		return $this->admin;
	}

	/**
	 * @return string[] Ids of modules skipped at boot.
	 */
	public function get_skipped() {
		return $this->skipped;
	}

	/**
	 * Whether a pro module is allowed by the current tier.
	 *
	 * @param Mcpwp_Module_Manifest $manifest Manifest.
	 * @return bool
	 */
	private function tier_satisfied( Mcpwp_Module_Manifest $manifest ) {
		if ( 'pro' !== $manifest->tier ) {
			return true;
		}
		return 'pro' === call_user_func( $this->tier_resolver );
	}

	/**
	 * Whether every depends_on key is satisfied.
	 *
	 * @param Mcpwp_Module_Manifest $manifest Manifest.
	 * @return bool
	 */
	private function depends_satisfied( Mcpwp_Module_Manifest $manifest ) {
		foreach ( $manifest->depends_on as $key ) {
			if ( ! call_user_func( $this->plugin_checker, $key ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Normalize any accepted module form into a manifest.
	 *
	 * @param mixed $module Manifest|array|Mcpwp_Module|string(path).
	 * @return Mcpwp_Module_Manifest
	 * @throws InvalidArgumentException On an unusable value.
	 */
	private function to_manifest( $module ) {
		if ( is_string( $module ) ) {
			if ( ! is_file( $module ) ) {
				throw new InvalidArgumentException( 'Module file not found: ' . $module );
			}
			$module = require $module;
		}

		if ( $module instanceof Mcpwp_Module_Manifest ) {
			return $module;
		}
		if ( $module instanceof Mcpwp_Module ) {
			return $module->manifest();
		}
		if ( is_array( $module ) ) {
			return Mcpwp_Module_Manifest::from_array( $module );
		}

		throw new InvalidArgumentException( 'Unusable module value; expected manifest, array, Mcpwp_Module, or module.php path.' );
	}
}
