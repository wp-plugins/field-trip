<?php
$key = '_field_trip_location_meta';

$location_meta = get_post_meta( get_the_ID(), $key, true );

$is_verified = get_post_meta( get_the_ID(), '_field_trip_data_verified', true );

$defaults = array(
			'geographic_location' => '',
			'start_date' => '',
			'end_date' => '',
			'error_code' => NULL,
			'lat' => '',
			'lng' => '',
			'possible_locations' => array(
				'0' => '',
				'1' => '',
				'2' => '',
				'3' => '',
				'4' => '',
			),
		);

$default_location_meta = apply_filters( 'fieldtrip_location_metabox_defaults', $defaults );

$location_meta = wp_parse_args(
	$location_meta,
	$default_location_meta
);


if ( isset( $location_meta['error_code'] ) ) {

	switch( $location_meta['error_code'] ) {

		case "000":
			$error = __( "Error connecting to API, please try again.", 'fieldtrip' );
			break;

		case "001":
			$error = __( "No Results Returned", 'fieldtrip' );
			break;

		case "002":
			$error = __( "Several locations with this address have been found. Please be more specific.", 'fieldtrip' );
			break;

		case "003":
			$error = __( "Location is too non-specific for use in Field Trip.", 'fieldtrip' );
			break;

	}

}

echo '<div id="fieldtrip-map-container">';

if ( $is_verified )
	echo '<p class="field-trip-verified">&#x2713; Field Trip Verified</p>';

// Area is too non-specific.
if ( "003" === $location_meta['error_code'] || empty( $location_meta['error_code'] ) && $location_meta['lat'] && $location_meta['lng'] ) {

	echo '<img class="field-trip-map-view" src="http://maps.googleapis.com/maps/api/staticmap?zoom=13&size=600x300&maptype=roadmap&markers=color:red%7C' . urlencode( $location_meta['lat'] ) . ',' . urlencode( $location_meta['lng'] ) .'&sensor=false" />';
	echo '<p><small>' . (float) $location_meta['lat'] . ', ' . (float) $location_meta['lng'] . '</small></p>';

}

echo '</div>';

printf(
	'<p><label  for="%1$s_id">Geographic Location:</label>
			<input type="text" class="widefat geographic-location-field"  name="%1$s[geographic_location]" id="%1$s_id[geographic_location]" value="%2$s"/>
			</p>',
	$key,
	esc_attr( $location_meta['geographic_location'] )
);

if ( isset( $location_meta['error_code'] ) ) {

	echo '<div class="error inline"><p>E' . $location_meta['error_code'] . ': ' . $error . '</p></div>';

	// Several locations with address.
	if ( $location_meta['error_code'] === "002" ) {
		echo '<div class="postbox"><h3>Did you mean?</h3>';
		echo '<div class="inside"><ul class="possible-locations">';
		foreach ( $location_meta['possible_locations'] as $possible_location ) {
			echo '<li class="possible-location" data-possible-location="' . esc_attr( $possible_location ) . '">' . esc_attr( $possible_location ) . '</li>';
		}
		echo '</ul></div></div>';
	}
}


echo '<hr />';

printf(
	'<p><label for="%1$s_id"><i>(Optional) Date to stop showing card in Field Trip</i></label><br />
			<input type="text" class="datetimepicker clear"  name="%1$s[end_date]" id="%1$s_id[end_date]" value="%2$s"/>
			</p>',
	$key,
	esc_attr( $location_meta['end_date'] )
);

printf(
	'<p><label for="%1$s_id"><i>(Optional) Date to start showing card in Field Trip, entering no date will show card as soon as it is published in Field Trip</i></label><br />
			<input type="text" class="datetimepicker clear"  name="%1$s[start_date]" id="%1$s_id[start_date]" value="%2$s"/>
			</p>',
	$key,
	esc_attr( $location_meta['start_date'] )
);