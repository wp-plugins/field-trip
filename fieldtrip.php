<?php
/**
 * Plugin Name: Field Trip
 * Plugin URI: http://www.fieldtripper.com/
 * Description: This plugin adds the ability to set a location and other data for a post that is compatible with Field Trip.
 * Author: nianticlabs, 10up
 * Version: 1.1.3
 * Author URI: http://www.fieldtripper.com/
 * License: GPL2
 *
 */

 /*  Copyright 2013  nianticlabs (email : publishers@fieldtripper.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// include geocoding class
require_once 'includes/class-fieldtrip-geocode.php';
require_once 'includes/class-post-maps.php';

class FieldTrip_WP {

	public function __construct() {

		add_filter( 'feed_link',       array( &$this, 'feed_link' ), 10, 2 );
		add_filter( 'pre_get_posts',   array( &$this, 'feed_query'       ) );
		add_filter( 'the_excerpt_rss', array( &$this, 'feed_excerpt'     ) );

		add_action( 'init',          array( &$this, 'init'              ) );
		add_action( 'admin_init',    array( &$this, 'admin_init'        ) );
		add_action( 'admin_menu',    array( &$this, 'add_menu'          ) );
		add_action( 'admin_notices', array( &$this, 'validation_notice' ) );

		add_action( 'wp_ajax_fieldtrip_map_preview', array( $this, 'map_preview' ) );

		// This will trump Feedburner redirect plugins when a custom feedburner feed has been created for Field Trip
		if ( isset ( $_SERVER['HTTP_USER_AGENT'] ) ) {
			if (!preg_match("/feedburner|feedvalidator/i", $_SERVER['HTTP_USER_AGENT']) && ( false !== get_option( 'fieldtrip_rss_redirect' ) ) ) {
				add_action('template_redirect', array( &$this, 'fieldtrip_feed_redirect' ), 8 );
			}
		}

	}

	public static function activate() {

		add_option( 'fieldtrip_feed_added', false );

		flush_rewrite_rules();

	}

	public static function deactivate() {

		delete_option( 'fieldtrip_feed_added' );

		flush_rewrite_rules();

	}

	/**
	 * Returns the supported content types.
	 *
	 * Use this to make sure you get the filtered content types back
	 *
	 * @return array|mixed|void
	 */
	public static function get_supported_content_types() {
		$content_types = array( 'post'=> 'post', 'page' => 'page' );
		$content_types = apply_filters( 'fieldtrip_supported_content_types', $content_types );

		return $content_types;
	}

	public function init() {

		// Adds Field Trip's georss as an available feed in WordPress.
		add_feed( 'fieldtrip-feed', array( &$this, 'feed' ) );

		// Flush rules after feed has been added.
		self::add_feed_rewrite();

		add_action( 'save_post', array( &$this, 'save_location_meta' ) );

	}

	public function admin_init() {

		add_action( 'admin_enqueue_scripts', array( &$this, 'register_admin_scripts' ) );
		add_action( 'add_meta_boxes',        array( &$this, 'add_meta_boxes'         ) );

		// two settings, so we can have two separate forms on the page
		register_setting( 'field_trip_config', 'field_trip_config', array( &$this, 'feed_settings' ) );
		register_setting( 'field_trip_settings', 'field_trip_settings', array( &$this, 'feed_submission' ) );

	}

	/**
	 * Include scripts used in admin.
	 */
	public function register_admin_scripts( $hook ) {

		if ( $hook == 'post.php' || $hook == 'post-new.php' ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'jquery-ui-datepicker',
				'http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/themes/smoothness/jquery-ui.min.css'
			);

			wp_enqueue_script( 'fieldtrip-admin',
				plugins_url( '/js/admin.js' , __FILE__ ),
				array( 'jquery-ui-datepicker' )
			);
			wp_enqueue_style( 'fieldtrip-admin',
				plugins_url( '/css/admin.css' , __FILE__ )
			);

			wp_localize_script( 'fieldtrip-admin', 'Fieldtrip', array(
				'MapPreviewNonce' => wp_create_nonce( 'map-preview' )
			));
		}
	}


	/**
	 * Add meta box to all supported post types.
	 */
	public function add_meta_boxes() {

		$content_types = FieldTrip_WP::get_supported_content_types();

		foreach ( $content_types as $content_type ) {
			add_meta_box(
				'field-trip-location-meta',
				__( 'Location', 'fieldtrip' ),
				array( &$this, 'add_location_meta_box' ),
				$content_type
			);
		}

	}

	/**
	 * Outputs the FieldTrip metabox.
	 */
	public function add_location_meta_box() {

		include( dirname(__FILE__) . '/includes/metabox.php' );

	}

	/**
	 * Save data from meta box.
	 */
	public function save_location_meta( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		$key = '_field_trip_location_meta';

		if ( isset( $_POST[ $key ] ) && !empty( $_POST[ $key ] ) ) {

			$data = array();

			foreach ( $_POST[ $key ] as $k => $value ) {

				if ( $k == 'geographic_location' ) {

					$address = sanitize_text_field( $value );

					$maybe_lng_lat = FieldTrip_Geocode::maybe_lng_lat( $address );

					// If data is lat and lng value skip geoencoding.
					if ( $maybe_lng_lat ) {

						$data = $maybe_lng_lat;

						continue;
					}

					// Grab address info from geoencode API.
					$data = FieldTrip_Geocode::address_data( $address );

					continue;
				}

				$value = is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : sanitize_text_field( $value );
				$data[ $k ] = $value;
			}

			// If no error verify post. Verified posts will be sent through the GeoRSS feed.
			if ( ! isset( $data[ 'error_code' ] ) && $address )

				update_post_meta( $post_id, '_field_trip_data_verified', (bool) true );

			else

				delete_post_meta( $post_id, '_field_trip_data_verified' );


			update_post_meta( $post_id, $key, $data );

		} else {

			delete_post_meta( $post_id, '_field_trip_has_geodata' );
			delete_post_meta( $post_id, $key );

		}


	}

	/**
	 * Add FieldTrip's settings page under settings menu.
	 */
	public function add_menu() {

		add_submenu_page( 'options-general.php', 'Field Trip', 'Field Trip Settings', 'edit_theme_options', 'field-trip', array( &$this, 'options_page' ) );

	}

	/**
	 * Output settings page.
	 */
	public function options_page() {

		include( dirname(__FILE__) . '/includes/settings.php' );

	}

	/**
	 * Submits feed to publishers@fieldtripper.com
	 */
	public function feed_submission() {
		$headers[] = 'From: FieldTrip Submission <fieldtripsubmission@' . get_site_url() . '>';
		$subject   = 'WP Field Trip Submission - ' . get_site_url();
		$message   = 'Site URL: ' . get_site_url() . '
Site Description: ' . get_bloginfo( 'description' ) . '
Feed URL: ' . get_feed_link( 'fieldtrip-feed' ) . '
E-mail: ' . get_bloginfo( 'admin_email' );

		wp_mail( 'publishers@fieldtripper.com', $subject, $message, $headers );

		add_filter('wp_redirect', array( &$this, 'feed_submission_redirect_filter' ) );

		return;
	}

	public function feed_submission_redirect_filter( $url ) {
		$url = add_query_arg( array('feed-submitted' => 'true'), $url );
		return esc_url_raw( $url );
	}

	/**
	 * Redirects fieldtrip-feed to custom Feedburner feed when Feedburner Redirect plugins are in use.
	 * This allows the feed to be redirected and retain its custom structure.
	 */
	function fieldtrip_feed_redirect() {
		global $wp, $feedburner_settings, $feed, $withcomments;
		if (is_feed() && 'fieldtrip-feed' == $feed ) {
			if (function_exists('status_header')) status_header( 302 );
			header("Location:" . esc_url( get_option('fieldtrip_rss_redirect') ) );
			header("HTTP/1.1 302 Temporary Redirect");
			exit();
		}
	}

	public function feed_settings() {
		if ( isset( $_POST['fieldtrip_content_filter'] ) ) {
			update_option( 'fieldtrip_content_filter', sanitize_text_field( $_POST['fieldtrip_content_filter'] ) );
		} else {
			//not set, so make the option set to false
			update_option( 'fieldtrip_content_filter', false );
		}
		if ( ! empty( $_POST['fieldtrip_rss_redirect'] ) ) {
			update_option( 'fieldtrip_rss_redirect', esc_url_raw( $_POST['fieldtrip_rss_redirect'] ) );
		} else {
			delete_option( 'fieldtrip_rss_redirect' );
		}
	}

	/**
	 * Update "Settings saved." message to be "Field Trip feed submitted."
	 */
	public function validation_notice() {
	global $pagenow;

		if ( 'options-general.php' === $pagenow && 'field-trip' === $_GET['page'] ) {

			if ( ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] && 'true' === $_GET['feed-submitted'] ) ) {

				unset( $_GET['settings-updated'] );
				unset( $_GET['feed-submitted'] );

		  		$errors = get_settings_errors();

		  		add_settings_error( 'general ', 'settings_updated', 'Field Trip feed submitted.', 'updated' );

			}
	  	}

	}


	/**
	 * Flushes rules after feed has been added to ensure feed is accessible.
	 * Checks 'fieldtrip_feed_added' option to make sure we are only doing this once
	 * due costly performace of flush_rewrite_rules().
	 */
	public function add_feed_rewrite() {

		if ( get_option( 'fieldtrip_feed_added' ) != true ) {

			flush_rewrite_rules();

			update_option( 'fieldtrip_feed_added', true );

		}

	}

	/**
	 * Actual georss feed.
	 */
	public function feed() {

		load_template( plugin_dir_path(__FILE__) . '/feed-fieldtrip.php' );

	}


	/**
	 * Filter the feed to be simplier, removing '/feed/' from the url output.
	 */
	public function feed_link( $url, $feed ) {

			if ( 'fieldtrip-feed' != $feed )
				return $url;

			$parsed_url = parse_url( $url );

			// If query based url, just return. '/?feed=fieldtrip-feed'
			if ( isset( $parsed_url['query'] ) )
				return $url;

			$url = str_replace( '/feed/', '/', $url );

			return $url;
	}

	/**
	 * Query only verified posts to be used in the GeoRSS feed.
	 *
	 * @param $query WP_Query
	 *
	 * @return mixed
	 */
	public function feed_query( $query )  {

		if ( $query->is_feed( 'fieldtrip-feed' ) ) {

			$content_types = array( 'post'=> 'post', 'page' => 'page' );
			$content_types = apply_filters( 'fieldtrip_supported_content_types', $content_types );

			$query->set( 'post_type', $content_types );

		}

		return $query;

	}

	/**
	 * Format feed description for GeoRSS. Strips out all tags except p.
	 */
	public function feed_excerpt( $output ) {

			if ( ! is_feed( 'fieldtrip-feed' ) )
				return $output;

			$output = get_the_content();
			$output = apply_filters( 'the_content', $output );
			$output = strip_shortcodes( $output );
			$output = str_replace( '\]\]\>', ']]&gt;', $output );
			$output = preg_replace( '@<script[^>]*?>.*?</script>@si', '', $output );
			$output = preg_replace('#<p class="wp-caption-text">(.*?)</p>#', '', $output);
			$output = strip_tags( $output, '<p>' );

			return $output;
	}


	/**
	 * Ajax Handler for previewing the map on the post edit screen.
	 */
	public function map_preview() {
		if (
			! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'map-preview' ) ||
			! isset( $_POST['geographic_location'] ) || empty( $_POST['geographic_location'] )
		) {
			return;
		}

		$geographic_location = sanitize_text_field( $_POST['geographic_location'] );

		if ( $lat_lng = FieldTrip_Geocode::maybe_lng_lat( $geographic_location ) ) {
			$data = $lat_lng;
		} else {
			$data = FieldTrip_Geocode::address_data( $geographic_location );
		}

		if ( is_array( $data ) && isset( $data['error_code'] ) ) {
			$final_data = array(
				'success' => 'false',
				'message' => self::get_location_error_html( $data ),
			);
		} else {
			$final_data = is_array( $data ) ? array_map( 'sanitize_text_field', $data ) : sanitize_text_field( $data );
			$final_data['success'] = 'true';
		}

		$lat = is_array( $data ) && isset( $data['lat'] ) ? $data['lat'] : false;
		$lng = is_array( $data ) && isset( $data['lng'] ) ? $data['lng'] : false;

		if ( $lat && $lng ) {
			$map = '';

			// Only add the 'verified' if marked as success
			if ( isset( $final_data['success'] ) &&  'true' == $final_data['success'] ) {
				$map .= '<p class="field-trip-verified">&#x2713; Field Trip Verified</p>';
			}

			$map .= '<img class="field-trip-map-view" src="http://maps.googleapis.com/maps/api/staticmap?zoom=13&size=600x300&maptype=roadmap&markers=color:red%7C' . urlencode( $lat ) . ',' . urlencode( $lng ) .'&sensor=false" />';
			$map .= '<p><small>' . (float) $lat . ', ' . (float) $lng . '</small></p>';

			$final_data['lat'] = $lat;
			$final_data['lng'] = $lng;
			$final_data['map'] = $map;
		}

		// Not using wp_send_json to maintain compatibility with versions pre 3.5
		echo json_encode( $final_data );
		die();
	}

	/**
	 * Gets the error message for a given error code.
	 *
	 * @param string $error_code The error code the get the message for.
	 *
	 * @return string The localized error message.
	 */
	public static function get_error_message( $error_code ) {
		switch( $error_code ) {
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

		return $error;
	}

	/**
	 * Gets HTML for the error message section of the edit post screen. Used in metabox.php and the JS preview callback
	 *
	 * @param array $location_meta
	 *
	 * @return string HTML representing the full error message section
	 */
	public static function get_location_error_html( $location_meta ) {
		$html = '';

		$html .= '<div class="error inline"><p>E' . $location_meta['error_code'] . ': ' . FieldTrip_WP::get_error_message( $location_meta['error_code'] ) . '</p></div>';

		// Several locations with address.
		if ( $location_meta['error_code'] === "002" ) {
			$html .= '<div class="postbox"><h3>Did you mean?</h3>';
			$html .= '<div class="inside"><ul class="possible-locations">';
			foreach ( $location_meta['possible_locations'] as $possible_location ) {
				$html .= '<li class="possible-location" data-possible-location="' . esc_attr( $possible_location ) . '">' . esc_attr( $possible_location ) . '</li>';
			}
			$html .= '</ul></div></div>';
		}

		return $html;
	}


}

if ( class_exists( 'FieldTrip_WP' ) ) {

	register_activation_hook( __FILE__, array( 'FieldTrip_WP', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'FieldTrip_WP', 'deactivate' ) );

	// initiate
	$wp_plugin_template = new FieldTrip_WP();
	$post_maps = new FieldTrip_Post_Maps();
}
