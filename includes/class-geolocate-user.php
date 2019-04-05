<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
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
	 * @var string
	 */
	public $version = '1.0.0';


	/**
	 * Instance of this class.
	 * @var object
	 */
	protected static $_instance = null;

	/*
	 * Return an instance of this class.
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'geolocate-user' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'geolocate-user' ), '1.0' );
	}

	/**
	 * Entries For WPForms Constructor.
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
	 * @param string      $name
	 * @param string|bool $value
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
			case 'admin' :
				return is_admin();
			case 'ajax' :
				return defined( 'DOING_AJAX' );
			case 'cron' :
				return defined( 'DOING_CRON' );
			case 'frontend' :
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
		load_plugin_textdomain( 'geolocate-user', false, plugin_basename( dirname( WPFORMS_ENTRIES_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Includes.
	 */
	private function includes() {
		include_once GU_ABSPATH . '/functions.php';
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
}
