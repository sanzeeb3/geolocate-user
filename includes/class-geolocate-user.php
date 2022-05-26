<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
	// Exit if accessed directly.
}

/**
 * Main Geolocate_User Class.
 *
 * @class   Geolocate_User
 * @version 1.0.0
 */
final class Geolocate_User {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * API endpoints for looking up user IP address.
	 *
	 * @var array
	 */
	private static $ip_lookup_apis = array(
		'ipify'             => 'http://api.ipify.org/',
		'ipecho'            => 'http://ipecho.net/plain',
		'ident'             => 'http://ident.me',
		'whatismyipaddress' => 'http://bot.whatismyipaddress.com',
	);

	/**
	 * API endpoints for geolocating an IP address
	 *
	 * @var array
	 */
	private static $geoip_apis = array(
		'ipapi.co'   => 'https://ipapi.co/%s/json',
		'ipinfo.io'  => 'https://ipinfo.io/%s/json',
		'ip-api.com' => 'http://ip-api.com/json/%s',
	);

	/**
	 * An instance of this class
	 *
	 * @return Object An instance
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'geolocate-user' ), '1.0' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'geolocate-user' ), '1.0' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Geolocate User Constructor.
	 */
	public function __construct() {

		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		$this->define_constants();
		$this->init();
		$this->includes();

		do_action( 'geolocate_user_loaded' );
	}

	/**
	 * Define FT Constants.
	 */
	private function define_constants() {
		$this->define( 'GU_ABSPATH', dirname( GEOLOCATE_USER_PLUGIN_FILE ) . '/' );
		$this->define( 'GU_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'GU', $this->version );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name The Constant Name.
	 * @param string|bool $value The Constant Value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 *
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/geolocate-user/geolocate-user-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/geolocate-user-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'geolocate-user' );

		load_textdomain( 'geolocate-user', WP_LANG_DIR . '/geolocate-user/geolocate-user-' . $locale . '.mo' );
		load_plugin_textdomain( 'geolocate-user', false, plugin_basename( dirname( GEOLOCATE_USER_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Includes.
	 */
	private function includes() {
		include_once GU_ABSPATH . '/includes/functions.php';
	}

	/**
	 * Init WPForms Entres when WordPress Initialises.
	 */
	public function init() {

		// Before init action.
		do_action( 'before_geolocate_user_init' );

		// Do things. For now nothing. Relax.

		// Init action.
		do_action( 'geolocate_user_init' );
	}

	/**
	 * Get current user IP Address.
	 *
	 * @return string
	 */
	public static function get_ip_address() {
		if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			// WPCS: input var ok, CSRF ok.
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
			// WPCS: input var ok, CSRF ok.
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// WPCS: input var ok, CSRF ok.
			// Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
			// Make sure we always only send through the first IP in the list which should always be the client IP.
			return (string) rest_is_ip_address( trim( current( preg_split( '/[,:]/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) );
			// WPCS: input var ok, CSRF ok.
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) { // @codingStandardsIgnoreLine
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ); // @codingStandardsIgnoreLine
		}
		return '';
	}

	/**
	 * Get user IP Address using an external service.
	 * This is used mainly as a fallback for users on localhost where
	 * get_ip_address() will be a local IP and non-geolocatable.
	 *
	 * @return string
	 */
	public static function get_external_ip_address() {
		$external_ip_address = '0.0.0.0';

		if ( '' !== self::get_ip_address() ) {
			$transient_name      = 'external_ip_address_' . self::get_ip_address();
			$external_ip_address = get_transient( $transient_name );
		}

		if ( false === $external_ip_address ) {
			$external_ip_address     = '0.0.0.0';
			$ip_lookup_services      = apply_filters( 'gu_geolocation_ip_lookup_apis', self::$ip_lookup_apis );
			$ip_lookup_services_keys = array_keys( $ip_lookup_services );
			shuffle( $ip_lookup_services_keys );

			foreach ( $ip_lookup_services_keys as $service_name ) {
				$service_endpoint = $ip_lookup_services[ $service_name ];
				$response         = wp_safe_remote_get( $service_endpoint, array( 'timeout' => 2 ) );

				if ( ! is_wp_error( $response ) && rest_is_ip_address( $response['body'] ) ) {
					$external_ip_address = apply_filters( 'gu_ip_lookup_api_response', $response['body'], $service_name );
					break;
				}
			}

			set_transient( $transient_name, $external_ip_address, WEEK_IN_SECONDS );
		}

		return $external_ip_address;
	}

	/**
	 * Geolocate an IP address.
	 *
	 * @param  string $ip_address   IP Address.
	 * @param  bool   $fallback     If true, fallbacks to alternative IP detection (can be slower).
	 * @param  bool   $api_fallback If true, uses geolocation APIs if the database file doesn't exist (can be slower).
	 * @return array
	 */
	public static function geolocate_ip( $ip_address = '', $fallback = true, $api_fallback = true ) {
		// Filter to allow custom geolocation of the IP address.
		$location_data = apply_filters( 'gu_geolocate_ip', array(), $ip_address, $fallback, $api_fallback );

		if ( empty( $location_data ) ) {
			$ip_address = $ip_address ? $ip_address : self::get_ip_address();

			if ( $api_fallback ) {
				$location_data = self::geolocate_via_api( $ip_address );
			} else {
				$location_data = array();
			}

			if ( empty( $location_data['country'] ) && $fallback ) {
				// May be a local environment - find external IP.
				return self::geolocate_ip( self::get_external_ip_address(), false, $api_fallback );
			}
		}

		return $location_data;
	}

	/**
	 * Use APIs to Geolocate the user.
	 *
	 * @param  string $ip_address IP address.
	 * @return string|bool
	 */
	public static function geolocate_via_api( $ip_address ) { //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.MaxExceeded
		$location_data = get_transient( 'geoip_' . $ip_address );

		if ( false === $location_data ) {
			$geoip_data          = array();
			$geoip_services      = apply_filters( 'gu_geolocation_geoip_apis', self::$geoip_apis );
			$geoip_services_keys = array_keys( $geoip_services );
			shuffle( $geoip_services_keys );

			foreach ( $geoip_services_keys as $service_name ) {
				$service_endpoint = $geoip_services[ $service_name ];
				$response         = wp_safe_remote_get( sprintf( $service_endpoint, $ip_address ), array( 'timeout' => 2 ) );

				if ( ! is_wp_error( $response ) && $response['body'] ) {
					switch ( $service_name ) {
						case 'ipinfo.io-':
							$data                       = json_decode( $response['body'] );
							$lat_log                    = isset( $data->loc ) ? explode( ',', $data->loc ) : array();
							$geoip_data['country']      = isset( $data->country ) ? $data->country : '';
							$geoip_data['country_code'] = isset( $data->country ) ? $data->country : '';
							$geoip_data['city']         = isset( $data->city ) ? $data->city : '';
							$geoip_data['region']       = isset( $data->region ) ? $data->region : '';
							$geoip_data['postal']       = isset( $data->postal ) ? $data->postal : '';
							$geoip_data['latitude']     = isset( $lat_log[0] ) ? $lat_log[0] : '';
							$geoip_data['longitude']    = isset( $lat_log[1] ) ? $lat_log[1] : '';
							break;
						case 'ip-api.com':
							$data                       = json_decode( $response['body'] );
							$geoip_data['country']      = isset( $data->country ) ? $data->country : '';
							$geoip_data['country_code'] = isset( $data->countryCode ) ? $data->countryCode : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
							$geoip_data['city']         = isset( $data->city ) ? $data->city : '';
							$geoip_data['region']       = isset( $data->regionName ) ? $data->regionName : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
							$geoip_data['postal']       = isset( $data->postal ) ? $data->postal : '';
							$geoip_data['latitude']     = isset( $data->lat ) ? $data->lat : '';
							$geoip_data['longitude']    = isset( $data->lon ) ? $data->lon : '';
							break;
						case 'ipapi.co-':
							$data                       = json_decode( $response['body'] );
							$geoip_data['country']      = isset( $data->country_name ) ? $data->country_name : '';
							$geoip_data['country_code'] = isset( $data->country ) ? $data->country : '';
							$geoip_data['city']         = isset( $data->city ) ? $data->city : '';
							$geoip_data['region']       = isset( $data->region ) ? $data->region : '';
							$geoip_data['postal']       = isset( $data->postal ) ? $data->postal : '';
							$geoip_data['latitude']     = isset( $data->latitude ) ? $data->latitude : '';
							$geoip_data['longitude']    = isset( $data->longitude ) ? $data->longitude : '';
							break;
						default:
							$geoip_data = apply_filters( 'gu_geolocation_geoip_response_' . $service_name, array(), $response['body'] );
							break;
					}//end switch

					$location_data = array_map( 'sanitize_text_field', $geoip_data );

					if ( ! empty( $location_data['country'] ) ) {
						break;
					}
				}//end if
			}//end foreach

			set_transient( 'geoip_' . $ip_address, $location_data, WEEK_IN_SECONDS );
		}//end if

		return $location_data;
	}
}
