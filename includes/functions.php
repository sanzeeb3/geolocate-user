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

	// Bail if no user ID is found.
	if( empty( $user_id ) ) {
		return;
	}

	$ip_address   = Geolocate_User::get_ip_address();
	$geo_location = Geolocate_User::geolocate_ip( $ip_address, true );

	update_user_meta( $user_id, 'gu_geolocation_data', $geo_location );
}

add_action( 'user_register', 'gu_save_geolocation_data' );

/**
 * Show Geolocation data on user profile
 *
 * @param  object $profileuser A WP_User object
 * @return void
 */
function gu_display_geolocation_map( $profileuser ) {
	$location = get_user_meta( $profileuser->ID, 'gu_geolocation_data', true );

	if( empty( $location ) ) {
		return;
	}

	?>
		<table class="form-table">
			<tr>
				<th>
					<label for="user_location"><?php esc_html_e( 'Geo Location Map', 'geolocate-user' ); ?></label>
				</th>
				<td>
					<?php

						$google_map_url = '';

						if ( ! empty( $location ) ) {
							$google_map_url = add_query_arg(
								array(
									'q'      => $location['city'] . ',' . isset( $location['region'] ) ? $location['region'] : $location['region'] ,
									'll'     => $location['latitude'] . ',' . $location['longitude'],
									'z'      => apply_filters( 'gu_geolocation_map_zoom', '6' ),
									'output' => 'embed',
								),
								'https://maps.google.com/maps'
							);
						}
					?><iframe frameborder="2" style="border:2px solid white" src="<?php echo esc_url( $google_map_url ); ?>" style="margin-left:10px;width:100%;height:320px;"></iframe>
				</td>
			</tr>
		</table>
		<table class ="form-table">
			<tr>
				<th>
						<label><?php esc_html_e( 'Geo Location Data', 'geolocate-user' ); ?></label>
				</th>
					<td>
						<table>
						<?php foreach( $location as $index => $dec_val ) {
								echo '<tr><td>'. gu_geolocate_user_shorthands( esc_html( $index ) ) . '</td><td>' . esc_html( $dec_val ) . '</td></tr>';
							}
						?>
						</table>
					</td>
				</th>
			</tr>
		</table>
	<?php
}

add_action( 'show_user_profile', 'gu_display_geolocation_map', 10, 1 );
add_action( 'edit_user_profile', 'gu_display_geolocation_map', 10, 1 );


/**
 * Updates the geolocation data when user logs in and location doesnot exits already.
 *
 * @param  $user_login Username
 * @param  $user       User Object
 */
function gu_update_location_on_login( $user_login, $user ) {
	$location = get_user_meta( $user->ID, 'gu_geolocation_data', true );

	if( ! empty( $location ) ) {
		return;
	} else {
		gu_save_geolocation_data( $user->ID );
	}

}
add_action( 'wp_login', 'gu_update_location_on_login', 10, 2 );

/**
 * Filter shorthands
 *
 * @param  $shorthands Shorthand to filter
 * @return string Actual String
 */
function gu_geolocate_user_shorthands( $shorthands ) {

	switch( $shorthands ) {
		case 'country':
			return 'Country';
		case 'country_code':
			return 'Country Code';
		case 'region':
			return 'Region';
		case 'postal':
			return 'Postal Code';
		case 'latitude':
			return 'Latitude';
		case 'longitude':
			return 'Longitude';
		case 'city':
			return 'City';
		case 'state':
			return 'State';
		default:
			return $shorthands;
	}

	return $shorthands;
}
