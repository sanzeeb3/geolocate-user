<?php
/**
 * Plugin Name: Geolocate User
 * Description: Stores geolocation data of WordPress users. Displays google map and allows user to mark their location via goolge map integration on their profile.
 * Version: 1.0.0
 * Author: Sanjeev Aryal
 * Author URI: http://www.sanjeebaryal.com.np
 * Text Domain: geolocate-user
 * Domain Path: /languages/
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define GEOLOCATE_USER_PLUGIN_FILE.
if ( ! defined( 'GEOLOCATE_USER_PLUGIN_FILE' ) ) {
	define( 'GEOLOCATE_USER_PLUGIN_FILE', __FILE__ );
}

// Include the main Geolocate_User class.
if ( ! class_exists( 'Geolocate_User' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-geolocate-user.php';
}

// Initialize the plugin.
add_action( 'plugins_loaded', array( 'Geolocate_User', 'get_instance' ) );
