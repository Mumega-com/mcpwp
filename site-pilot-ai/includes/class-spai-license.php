<?php
/**
 * License management for MCPWP.
 *
 * Freemius is the single source of truth for entitlement. Paid plans and trials
 * are resolved entirely through the Freemius SDK in the production build, with
 * two developer/distribution overrides:
 *   - SPAI_WPORG_BUILD defined => always free (WP.org build).
 *   - MUMCP_PRO constant true  => always pro (developer override).
 *
 * @package MumegaMCP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * License handler.
 */
class Spai_License {

	/**
	 * Singleton instance.
	 *
	 * @var Spai_License
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Spai_License
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {}

	/**
	 * Check if licensed features are active.
	 *
	 * Pro is active when:
	 * 1. SPAI_WPORG_BUILD is not defined (free build always returns false), AND
	 * 2. MUMCP_PRO constant is true (developer override), OR
	 * 3. Freemius reports premium access, a paying plan, or an active trial.
	 *
	 * @return bool
	 */
	public function is_pro() {
		if ( defined( 'SPAI_WPORG_BUILD' ) ) {
			return false;
		}

		// Developer override.
		if ( defined( 'MUMCP_PRO' ) && MUMCP_PRO ) {
			return true;
		}

		if ( function_exists( 'spai_get_fs_instance' ) ) {
			$fs = spai_get_fs_instance();
			if ( is_object( $fs ) ) {
				if ( method_exists( $fs, 'can_use_premium_code' ) && $fs->can_use_premium_code() ) {
					return true;
				}
				if ( method_exists( $fs, 'is_paying' ) && $fs->is_paying() ) {
					return true;
				}
				if ( method_exists( $fs, 'is_trial' ) && $fs->is_trial() ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if user is paying (has a paid plan, not a trial).
	 *
	 * @return bool
	 */
	public function is_paying() {
		if ( defined( 'SPAI_WPORG_BUILD' ) ) {
			return false;
		}

		if ( defined( 'MUMCP_PRO' ) && MUMCP_PRO ) {
			return true;
		}
		if ( function_exists( 'spai_get_fs_instance' ) ) {
			$fs = spai_get_fs_instance();
			if ( is_object( $fs ) && method_exists( $fs, 'is_paying' ) && $fs->is_paying() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if agency tier.
	 *
	 * @return bool
	 */
	public function is_agency() {
		return 'agency' === $this->get_plan();
	}

	/**
	 * Get current plan.
	 *
	 * @return string 'unlicensed', 'pro', 'agency', or 'trial'
	 */
	public function get_plan() {
		if ( ! $this->is_pro() ) {
			return 'unlicensed';
		}

		$freemius_plan = $this->get_freemius_plan();
		if ( '' !== $freemius_plan ) {
			return $freemius_plan;
		}

		// MUMCP_PRO developer override with no Freemius instance.
		return 'pro';
	}

	/**
	 * Get the normalized Freemius plan slug when available.
	 *
	 * @return string Plan slug, or empty string when unknown.
	 */
	private function get_freemius_plan() {
		if ( ! function_exists( 'spai_get_fs_instance' ) ) {
			return '';
		}

		$fs = spai_get_fs_instance();
		if ( ! is_object( $fs ) ) {
			return '';
		}

		if ( method_exists( $fs, 'is_trial' ) && $fs->is_trial() && ( ! method_exists( $fs, 'is_paying' ) || ! $fs->is_paying() ) ) {
			return 'trial';
		}

		$raw_plan = '';
		if ( method_exists( $fs, 'get_plan' ) ) {
			$plan = $fs->get_plan();
			if ( is_object( $plan ) ) {
				foreach ( array( 'name', 'title', 'slug' ) as $property ) {
					if ( isset( $plan->{$property} ) && is_scalar( $plan->{$property} ) ) {
						$raw_plan = (string) $plan->{$property};
						break;
					}
				}
				foreach ( array( 'get_name', 'get_title', 'get_slug' ) as $method ) {
					if ( '' === $raw_plan && method_exists( $plan, $method ) ) {
						$value = $plan->{$method}();
						if ( is_scalar( $value ) ) {
							$raw_plan = (string) $value;
						}
					}
				}
			} elseif ( is_string( $plan ) ) {
				$raw_plan = $plan;
			}
		}

		$raw_plan = strtolower( sanitize_key( $raw_plan ) );
		if ( false !== strpos( $raw_plan, 'agency' ) ) {
			return 'agency';
		}
		if ( false !== strpos( $raw_plan, 'trial' ) ) {
			return 'trial';
		}
		if ( '' !== $raw_plan ) {
			return $raw_plan;
		}
		if ( method_exists( $fs, 'is_paying' ) && $fs->is_paying() ) {
			return 'pro';
		}

		return '';
	}

	/**
	 * Check if a Freemius trial is active.
	 *
	 * @return bool
	 */
	public function is_trial_active() {
		if ( ! function_exists( 'spai_get_fs_instance' ) ) {
			return false;
		}

		$fs = spai_get_fs_instance();
		return is_object( $fs ) && method_exists( $fs, 'is_trial' ) && $fs->is_trial();
	}

	/**
	 * Upgrade URL.
	 *
	 * @return string
	 */
	public function get_upgrade_url() {
		return 'https://mcpwp.net/pricing/';
	}

	/**
	 * Account URL.
	 *
	 * @return string
	 */
	public function get_account_url() {
		return 'https://mcpwp.net/account/';
	}

	/**
	 * Canonical license/entitlement accessor.
	 *
	 * Returns a single, internally consistent snapshot of the entitlement state
	 * so every consumer (MCP / REST / CLI) reports the same plan and pro status.
	 * This is the single source of truth: plan and is_pro can never contradict.
	 *
	 * Consistency guarantees:
	 * - When is_pro is false, plan is always 'unlicensed'.
	 * - When is_pro is true, plan is always a non-free value ('pro', 'agency',
	 *   'trial', or a Freemius-provided slug). If the resolved plan is empty or
	 *   collapses to a free/unlicensed value, it is coerced to a sane paid value.
	 *
	 * @return array {
	 *     @type string $plan      Plan slug ('unlicensed', 'trial', 'pro', 'agency', ...).
	 *     @type bool   $is_pro    Whether licensed features are active.
	 *     @type bool   $is_paying Whether the site has a paid (non-trial) entitlement.
	 *     @type bool   $is_agency Whether the agency tier is active.
	 * }
	 */
	public function get_license_info() {
		$is_pro    = $this->is_pro();
		$is_paying = $this->is_paying();

		// When not pro, plan is unconditionally unlicensed regardless of stale data.
		if ( ! $is_pro ) {
			return array(
				'plan'      => 'unlicensed',
				'is_pro'    => false,
				'is_paying' => false,
				'is_agency' => false,
			);
		}

		$plan = $this->get_plan();

		// Self-validate: a pro entitlement must never resolve to a free/unlicensed plan.
		if ( '' === $plan || 'unlicensed' === $plan || 'free' === $plan ) {
			if ( $is_paying ) {
				$plan = 'pro';
			} elseif ( $this->is_trial_active() ) {
				$plan = 'trial';
			} else {
				$plan = 'pro';
			}
		}

		return array(
			'plan'      => $plan,
			'is_pro'    => true,
			'is_paying' => $is_paying,
			'is_agency' => ( 'agency' === $plan ) || $this->is_agency(),
		);
	}
}

/**
 * Get license instance.
 *
 * @return Spai_License
 */
function spai_license() {
	return Spai_License::get_instance();
}
