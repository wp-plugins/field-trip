<?php

/**
 * Adds functionality to embed a map based on the geolocation in the post.
 */
class FieldTrip_Post_Maps {

	/**
	 * Sets up actions, hooks, and shortcodes used by the in post maps component.
	 */
	public function __construct() {
		add_shortcode( 'map', array( $this, 'render_map' ) );
		add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );
		add_filter( 'mce_buttons', array( $this, 'mce_buttons' ) );
		add_action( 'admin_footer', array( $this, 'mce_template' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 15 );
	}

	/**
	 * Returns a link to a google maps page for the specified latitude & longitude
	 *
	 * @param string $latitude
	 * @param string $longitude
	 *
	 * @return string The url to the map
	 */
	public function get_map_link( $latitude, $longitude ) {
		$url = 'https://www.google.com/maps/preview?q=loc:' . urlencode( $latitude . ',' . $longitude ) . '&t=m&z=18';

		return $url;
	}

	/**
	 * Render the map shortcode.
	 *
	 * @param array $atts The attributes for the shortcode.
	 * @param string $content The content between the opening and closing shortcode tag.
	 *
	 * @return string The HTML for the map shortcode.
	 */
	public function render_map( $atts, $content = '' ) {
		$defaults = array(
			'align' => '', // left, center, right
			'size' => 'thumbnail', // thumbnail, medium, large or custom size
			'width' => false, // Only used for the 'custom' size
			'height' => false, // Only used for the 'custom' size
		);

		$atts = shortcode_atts( $defaults, $atts );

		$post_id = get_the_ID();

		$verified = get_post_meta( $post_id, '_field_trip_data_verified', true );

		// We need a default value to return
		$map = '';

		// Get information on the requested image size
		if ( 'custom' == $atts['size'] && false !== $atts['width'] && false !== $atts['height'] ) {
			$size_details = array(
				'label' => 'Custom', // Not used here, but for data structure consistency
				'width' => intval( $atts['width'] ),
				'height' => intval( $atts['height'] ),
			);
		} else {
			$size_details = $this->get_image_size_details( $atts['size'] );
		}

		if ( $verified && $size_details && isset( $size_details['width'] ) && isset( $size_details['height'] ) ) {
			// Get the location data
			$location_meta = get_post_meta( $post_id, '_field_trip_location_meta', true );

			// Determine the class to use for alignment
			switch( $atts['align'] ) {
				case 'left':
					$class = 'alignleft';
					break;
				case 'center':
					$class = 'aligncenter';
					break;
				case 'right':
					$class = 'alignright';
					break;
				default:
					$class = '';
					break;
			}

			$map = '<a href="' . esc_url( $this->get_map_link( $location_meta['lat'], $location_meta['lng'] ) ) . '"><img class="field-trip-map-view ' . esc_attr( $class ) . '" src="http://maps.googleapis.com/maps/api/staticmap?zoom=13&size=' . intval( $size_details['width'] ) . 'x' . intval( $size_details['height'] ) . '&maptype=roadmap&markers=color:red%7c' . urlencode( $location_meta['lat'] ) . ',' . urlencode( $location_meta['lng'] ) .'&sensor=false" /></a>';
		}

		return $map;
	}

	/**
	 * Add the map button to TinyMCE.
	 *
	 * @param array $buttons Current TinyMCE Buttons.
	 *
	 * @return array TinyMCE Buttons.
	 */
	public function mce_buttons( $buttons ) {
		if ( in_array( get_post_type(), FieldTrip_WP::get_supported_content_types() ) ) {
			array_push( $buttons, 'ft-map' );
		}

		return $buttons;
	}

	/**
	 * Load the TinyMCE plugin for handling the map button.
	 *
	 * @param array $plugins Current TinyMCE plugins.
	 *
	 * @return array TinyMCE plugins.
	 */
	public function mce_external_plugins( $plugins ) {
		if ( in_array( get_post_type(), FieldTrip_WP::get_supported_content_types() ) ) {
			$plugins['ftmap'] = plugins_url( '/js/ft-tinymce.js', dirname( __FILE__ ) );
		}

		return $plugins;
	}

	/**
	 * Outputs the template for the TinyMCE button window
	 */
	public function mce_template() {
		if ( ! in_array( get_post_type(), FieldTrip_WP::get_supported_content_types() ) ) {
			return;
		}

		$sizes = $this->get_image_sizes();

		add_thickbox();
		?>
		<div id="ft-mce-embed-map" style="display:none">
			<div class="ftmap-container">
				<div class="ft-instructions">
					<?php _e( "Embed a map into the post based on the geolocation specified below. Configure the size and alignment options, and click 'Insert'", 'fieldtrip' ); ?>
				</div>
				<div class="ftmap-size">
					<label for="ftmap-size"><?php _e( 'Size', 'fieldtrip' ); ?></label>
					<select class="widefat" name="ftmap-size" id="ftmap-size">
						<?php
						foreach ( $sizes as $size => $details ) {
							?><option value="<?php echo esc_attr( $size ); ?>"><?php echo esc_html( $details['label'] ); ?></option><?php
						}
						?>
						<option value="custom">Custom</option>
					</select>
				</div>
				<div class="ftmap-custom-size hidden" id="fieldtrip-custom-size-container">
					<label for="ftmap-width"><?php _e( 'Width', 'fieldtrip' ); ?></label>
					<input type="text" id="ftmap-width" name="ftmap-width" />

					<label for="ftmap-height"><?php _e( 'Height', 'fieldtrip' ); ?></label>
					<input type="text" id="ftmap-height" name="ftmap-height" />
				</div>
				<div class="ftmap-align">
					<label for="ftmap-align"><?php _e( 'Alignment', 'fieldtrip' ); ?></label>
					<select class="widefat" name="ftmap-align" id="ftmap-align">
						<option value=""></option>
						<option value="left"><?php _e( 'Left', 'fieldtrip' ); ?></option>
						<option value="center"><?php _e( 'Center', 'fieldtrip' ) ?></option>
						<option value="right"><?php _e( 'Right', 'fieldtrip' ); ?></option>
					</select>
				</div>
				<div class="button button-primary alignright" id="ft-insert-map"><?php _e( 'Insert', 'fieldtrip' ); ?></div>
			</div>
		</div>
	<?php
	}

	/**
	 * Localize the strings used in the javascript
	 */
	public function admin_enqueue_scripts() {
		if ( ! in_array( get_post_type(), FieldTrip_WP::get_supported_content_types() ) ) {
			return;
		}
		
		$strings = array(
			'Insert Map' => __( 'Insert Map', 'fieldtrip' ),
			'You must specify a width and height for custom sizes' => __( 'You must specify a width and height for custom sizes', 'fieldtrip' )
		);

		wp_localize_script( 'fieldtrip-admin', 'FTMapsStrings', $strings );
	}


	/* Helpers */

	/**
	 * Returns supported image sizes.
	 *
	 * @return array Image Sizes
	 */
	public function get_image_sizes() {
		$available_sizes = array();

		$registered_sizes = get_intermediate_image_sizes();

		foreach ( $registered_sizes as $size ) {
			// Skip 'post-thumbnail' - because its height is something crazy like 9999
			if ( 'post-thumbnail' == $size ) {
				continue;
			}

			$details = $this->get_image_size_details( $size );
			if ( false !== $details ) {
				$available_sizes[ $size ] = $details;
			}
		}

		return apply_filters( 'fieldtrip_image_sizes', $available_sizes );
	}

	/**
	 * Returns the dimensions of the image.
	 *
	 * @param string $image_size The image size identifier.
	 *
	 * @return array The image dimensions.
	 */
	public function get_image_size_details( $image_size ) {
		global $_wp_additional_image_sizes;

		switch( $image_size ) {
			case 'thumbnail':
				$dimensions = array(
					'label' => 'Thumbnail',
					'width' => '150',
					'height' => '100',
				);
				break;
			case 'medium':
				$dimensions = array(
					'label' => 'Medium',
					'width' => '300',
					'height' => '200',
				);
				break;
			case 'large':
				$dimensions = array(
					'label' => 'Large',
					'width' => '640',
					'height' => '425',
				);
				break;
			default:
				// This whole section should just gracefully fail if WP ever changes the way that images are stored internally
				// Hopefully, there is one day support for getting information about image sizes via standard WP Functions
				if (
					! isset( $_wp_additional_image_sizes ) ||
					! is_array( $_wp_additional_image_sizes ) ||
					! isset( $_wp_additional_image_sizes[ $image_size ] ) ||
					! isset( $_wp_additional_image_sizes[ $image_size ][ 'width' ] ) ||
					! isset( $_wp_additional_image_sizes[ $image_size ][ 'height' ] )
				) {
					$dimensions = false;
				} else {
					$dimensions = array(
						'label' => ucfirst( str_replace( array( '-', '_' ), ' ', $image_size ) ),// Convert the internal size name to something more friendly to users
						'width' => $_wp_additional_image_sizes[ $image_size ][ 'width' ],
						'height' => $_wp_additional_image_sizes[ $image_size ][ 'height' ],
					);
				}

				break;
		}

		return $dimensions;
	}

}

