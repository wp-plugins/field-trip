<?php
class FieldTrip_Geocode {

	/**
	 * Max feet between opposite corners of the bounds of the address, if they are provided in the response from google
	 *
	 * @var int
	 */
	static $max_bound_distance = 2500;

	/**
	 * A blank constructor
	 */
	public function __construct() {}

	/**
	 * Check if data is a valid lat and long value.
	 */
	public static function maybe_lng_lat( $maybe_geolocation ) {

		$geo_data = array();

		// Latitude ranges between 0º to 90º North and South.
		// Longitude ranges from 0º to 180º East and West.
		$maybe_lng_lat = preg_match( '/^((-?[0-8]?[0-9](\.\d*)?)|-?90(\.[0]*)?),\s*((-?([1]?[0-7][1-9]|[1-9]?[0-9])?(\.\d*)?)|-?180(\.[0]*)?)$/', $maybe_geolocation, $matches );

		// If not between proper ranges location not valid
		if ( ! $maybe_lng_lat )
			return false;

		$geo_data[ 'geographic_location' ] = $matches[0];
		$geo_data[ 'lat' ]                 = $matches[1];
		$geo_data[ 'lng' ]                 = $matches[5];

		return $geo_data;
	}

	/**
	 * Send address to Geocode API and return result.
	 */
	public static function retrieve_address_data( $address ) {

		$address_data_url = "http://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode( $address ) . "&sensor=true";
		$adress_data = wp_remote_get( $address_data_url );
		$adress_data_body = wp_remote_retrieve_body( $adress_data );

		$result = json_decode( $adress_data_body );

		return $result;
	}

	/**
	 * Verify Address, detect if any possible errors.
	 */
	public static function verify_address_data( $address, $address_data ) {

		if ( ! $address )
			return;

		// Check address data returned is an object.
		if ( is_object( $address_data ) ) {

			$result_count = ( isset( $address_data->results ) ) ? count( $address_data->results ) : 0;

			if ( $result_count === 0 ) {

				// No results were found
				$data[ 'error_code' ] = "001";
				$data[ 'geographic_location' ] = sanitize_text_field( $address );

			} elseif ( $result_count > 1 ) {

				// More than one result was found.
				$data[ 'error_code' ] = "002";

				$possible_locations = $address_data->results;

				// Generate list of possible locations that user may have been searching for.
				for ( $i = 0; $i < count( $possible_locations ); ++$i ) {
					$data[ 'possible_locations' ][$i] = sanitize_text_field( $possible_locations[$i]->formatted_address );
				}

				$data[ 'geographic_location' ] = sanitize_text_field( $address );

			} else {

				// We got it.
				$data[ 'geographic_location' ] = sanitize_text_field( $address_data->results[0]->formatted_address );
				$data[ 'lat' ]                 = (float) $address_data->results[0]->geometry->location->lat;
				$data[ 'lng' ]                 = (float) $address_data->results[0]->geometry->location->lng;

				// Location is too non-specific Ex: 'Central Park, NY' (locations exsist within)
				if ( isset( $address_data->results[0]->geometry->bounds ) ) {
					$bounds = (array)$address_data->results[0]->geometry->bounds;
					$point1 = array_shift( $bounds );
					$point2 = array_shift( $bounds );

					$distance = self::calculate_distance( $point1, $point2 );//distance in miles
					$distance_feet = $distance * 5280; //convert to feet
					if ( $distance_feet > self::$max_bound_distance ) { //allow up to max_bound_distance (in feet) between opposite corners of the address, or else say too unspecific.
						$data[ 'error_code' ] = "003";
					}
				}

			}

		} else {

			// Something may have went wrong with the request.
			$data[ 'error_code' ] = "000";
			$data[ 'geographic_location' ] = sanitize_text_field( $address );

		}

		return $data;

	}

	/**
	 * Calculate the distance between two latitude & longitude points in miles
	 *
	 * @return float Distance between the points, in miles
	 */
	public static function calculate_distance( $point_1, $point_2 ) {
		$earthRadius = 3959; //radius of earth, miles
		$latFrom = deg2rad( $point_1->lat );
		$lngFrom = deg2rad( $point_1->lng );
		$latTo = deg2rad( $point_2->lat );
		$lngTo = deg2rad( $point_2->lng );

		$latDelta = $latTo - $latFrom;
		$lngDelta = $lngTo - $lngFrom;

		$angle = 2 * asin( sqrt( pow( sin( $latDelta / 2 ), 2 ) + cos( $latFrom ) * cos( $latTo ) * pow( sin( $lngDelta / 2 ), 2 ) ) );
		$distance = $angle * $earthRadius;

		return $distance;
	}

	/**
	 * Return what we found out about the address.
	 */
	public static function address_data( $address ) {

		$address_data = self::retrieve_address_data( $address );

		$data = self::verify_address_data( $address, $address_data );

		return $data;

	}
}