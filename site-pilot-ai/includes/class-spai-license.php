<?php
/**
 * License management for MCPWP.
 *
 * Paid plans and trials are managed through Freemius in the production build.
 * A legacy local license store remains for backwards compatibility.
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
	 * Cached license data.
	 *
	 * @var array|null
	 */
	private $license_data = null;

	/**
	 * Trial duration in days.
	 *
	 * @var int
	 */
	const TRIAL_DAYS = 14;

	/**
	 * Option key for license.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'spai_pro_license';

	/**
	 * Option key for trial start.
	 *
	 * @var string
	 */
	const TRIAL_KEY = 'spai_trial_started';

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
	 * 1. Valid license key is stored and not expired, OR
	 * 2. Trial period is active (14 days from first activation), OR
	 * 3. MUMCP_PRO constant is defined (developer override)
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

		// Check stored license.
		$license = $this->get_license_data();
		if ( ! empty( $license['key'] ) && ! empty( $license['valid'] ) && ! $this->is_expired() ) {
			return true;
		}

		// Check trial.
		if ( $this->is_trial_active() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if user is paying (has a license, not trial).
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
			if ( is_object( $fs ) && method_exists( $fs, 'is_trial' ) && $fs->is_trial() ) {
				return true;
			}
			if ( is_object( $fs ) && method_exists( $fs, 'is_paying' ) && $fs->is_paying() ) {
				return true;
			}
		}
		$license = $this->get_license_data();
		return ! empty( $license['key'] ) && ! empty( $license['valid'] ) && ! $this->is_expired();
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

		if ( $this->is_trial_active() && ! $this->is_paying() ) {
			return 'trial';
		}

		$freemius_plan = $this->get_freemius_plan();
		if ( '' !== $freemius_plan ) {
			return $freemius_plan;
		}

		$license = $this->get_license_data();
		return ! empty( $license['plan'] ) ? $license['plan'] : 'pro';
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
		// 'free' is the Freemius slug for the unpaid tier. When Freemius reports
		// the free plan, treat it as "no Freemius plan" so get_plan() can fall
		// through to the stored license data (Lemon Squeezy / MUMCP_PRO) instead
		// of returning 'free' while pro_active is simultaneously true — the
		// contradiction reported in GitHub issue #319.
		if ( 'free' === $raw_plan ) {
			$raw_plan = '';
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
	 * Check if trial is active.
	 *
	 * @return bool
	 */
	public function is_trial_active() {
		$trial_started = get_option( self::TRIAL_KEY, '' );
		if ( empty( $trial_started ) ) {
			return false;
		}
		$elapsed = time() - (int) $trial_started;
		return $elapsed < ( self::TRIAL_DAYS * DAY_IN_SECONDS );
	}

	/**
	 * Get trial days remaining.
	 *
	 * @return int Days remaining, 0 if expired or not started.
	 */
	public function get_trial_days_remaining() {
		$trial_started = get_option( self::TRIAL_KEY, '' );
		if ( empty( $trial_started ) ) {
			return 0;
		}
		$elapsed   = time() - (int) $trial_started;
		$remaining = ( self::TRIAL_DAYS * DAY_IN_SECONDS ) - $elapsed;
		return max( 0, (int) ceil( $remaining / DAY_IN_SECONDS ) );
	}

	/**
	 * Start free trial.
	 *
	 * @return array Result.
	 */
	public function start_trial() {
		$existing = get_option( self::TRIAL_KEY, '' );
		if ( ! empty( $existing ) ) {
			return array(
				'success' => false,
				'message' => __( 'Trial already started.', 'mumega-mcp' ),
				'days_remaining' => $this->get_trial_days_remaining(),
			);
		}
		update_option( self::TRIAL_KEY, time() );
		return array(
			'success' => true,
			/* translators: %d: number of trial days */
			'message' => sprintf( __( '%d-day Pro trial started. All integrations unlocked.', 'mumega-mcp' ), self::TRIAL_DAYS ),
			'days_remaining' => self::TRIAL_DAYS,
		);
	}

	/**
	 * Get stored license data.
	 *
	 * @return array License data or empty array.
	 */
	private function get_license_data() {
		if ( null === $this->license_data ) {
			$this->license_data = get_option( self::OPTION_KEY, array() );
			if ( ! is_array( $this->license_data ) ) {
				$this->license_data = array();
			}
		}
		return $this->license_data;
	}

	/**
	 * Get license key.
	 *
	 * @return string|null
	 */
	public function get_license_key() {
		$license = $this->get_license_data();
		return ! empty( $license['key'] ) ? $license['key'] : null;
	}

	/**
	 * Get expiration date.
	 *
	 * @return string|null ISO date or null.
	 */
	public function get_expiration() {
		$license = $this->get_license_data();
		return ! empty( $license['expires_at'] ) ? $license['expires_at'] : null;
	}

	/**
	 * Check if license is expired.
	 *
	 * @return bool
	 */
	public function is_expired() {
		$expires = $this->get_expiration();
		if ( empty( $expires ) ) {
			return false; // No expiration = lifetime.
		}
		return strtotime( $expires ) < time();
	}

	/**
	 * Get site limit.
	 *
	 * @return int|null Null = unlimited.
	 */
	public function get_site_limit() {
		$license = $this->get_license_data();
		return isset( $license['site_limit'] ) ? (int) $license['site_limit'] : null;
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
	 * Activate a license key.
	 *
	 * Validates against Lemon Squeezy API and stores locally.
	 *
	 * @param string $license_key License key.
	 * @return array Result with success, message, plan.
	 */
	public function activate( $license_key ) {
		$license_key = sanitize_text_field( trim( $license_key ) );
		if ( empty( $license_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'License key is required.', 'mumega-mcp' ),
			);
		}

		// Validate with Lemon Squeezy.
		$response = wp_remote_post( 'https://api.lemonsqueezy.com/v1/licenses/validate', array(
			'timeout' => 15,
			'body'    => array(
				'license_key'   => $license_key,
				'instance_name' => home_url(),
			),
		) );

		if ( is_wp_error( $response ) ) {
			// Network error — accept key locally with a warning.
			$data = array(
				'key'        => $license_key,
				'valid'      => true,
				'plan'       => 'pro',
				'offline'    => true,
				'activated'  => current_time( 'mysql' ),
			);
			update_option( self::OPTION_KEY, $data );
			$this->license_data = $data;

			return array(
				'success' => true,
				'message' => __( 'License saved (offline validation — will verify on next check).', 'mumega-mcp' ),
				'plan'    => 'pro',
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$valid = isset( $body['valid'] ) && $body['valid'];

		if ( ! $valid ) {
			$error = isset( $body['error'] ) ? $body['error'] : __( 'Invalid license key.', 'mumega-mcp' );
			return array(
				'success' => false,
				'message' => $error,
			);
		}

		// Determine plan from Lemon Squeezy meta.
		$meta       = isset( $body['meta'] ) ? $body['meta'] : array();
		$variant    = isset( $meta['variant_name'] ) ? strtolower( $meta['variant_name'] ) : '';
		$plan       = ( false !== strpos( $variant, 'agency' ) ) ? 'agency' : 'pro';
		$expires_at = isset( $body['license_key']['expires_at'] ) ? $body['license_key']['expires_at'] : null;
		$site_limit = isset( $meta['activation_limit'] ) ? (int) $meta['activation_limit'] : null;

		$data = array(
			'key'        => $license_key,
			'valid'      => true,
			'plan'       => $plan,
			'expires_at' => $expires_at,
			'site_limit' => $site_limit,
			'activated'  => current_time( 'mysql' ),
		);

		update_option( self::OPTION_KEY, $data );
		$this->license_data = $data;

		return array(
			'success' => true,
			/* translators: %s: license plan name */
			'message' => sprintf( __( 'License activated. Plan: %s', 'mumega-mcp' ), ucfirst( $plan ) ),
			'plan'    => $plan,
		);
	}

	/**
	 * Deactivate license.
	 *
	 * @return array Result.
	 */
	public function deactivate() {
		$license = $this->get_license_data();
		if ( ! empty( $license['key'] ) ) {
			// Notify Lemon Squeezy (best effort).
			wp_remote_post( 'https://api.lemonsqueezy.com/v1/licenses/deactivate', array(
				'timeout' => 10,
				'body'    => array(
					'license_key'   => $license['key'],
					'instance_id'   => md5( home_url() ),
				),
			) );
		}

		delete_option( self::OPTION_KEY );
		$this->license_data = null;

		return array(
			'success' => true,
			'message' => __( 'License deactivated. Paid features disabled.', 'mumega-mcp' ),
		);
	}

	/**
	 * Get license info for API responses.
	 *
	 * @return array
	 */
	public function get_info() {
		return array(
			'provider'        => 'lemon_squeezy',
			'is_paying'       => $this->is_paying(),
			'plan'            => $this->get_plan(),
			'is_pro'          => $this->is_pro(),
			'is_agency'       => $this->is_agency(),
			'license_key'     => $this->get_license_key() ? substr( $this->get_license_key(), 0, 8 ) . '...' : null,
			'expiration'      => $this->get_expiration(),
			'is_expired'      => $this->is_expired(),
			'site_limit'      => $this->get_site_limit(),
			'trial_active'    => $this->is_trial_active(),
			'trial_remaining' => $this->get_trial_days_remaining(),
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
