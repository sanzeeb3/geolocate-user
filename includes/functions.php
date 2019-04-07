<?php
/**
 * Geolocate User Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @package Geolocate User/Functions
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Save Geolocation data of the user at registration.
 *
 * @param  $user_id  User ID.
 * @return void
 */
function gu_save_geolocation_data( $user_id ) {
error_log(print_r( $user_id, true));
	// Bail if no user ID is found.
	if( empty( $user_id ) ) {
		return;
	}

	$ip_address   = Geolocate_User::get_ip_address();
	$geo_location = Geolocate_User::geolocate_ip( $ip_address, true );

	update_user_meta( $user_id, 'gu_geolocation_data', $geo_location );
}

add_action( 'user_register', 'gu_save_geolocation_data' );
