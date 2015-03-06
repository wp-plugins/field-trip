<?php
if ( ! current_user_can( 'manage_options' ) )
	wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

?>
<style>
	.form-table td p {
		margin: 0;
		padding: 0;
	}
	.wp-core-ui .button-primary {
		font-size: 14px;
		height: auto;
		padding: 2px 6px;
		line-height: 1.5;
	}
	.form-table td p.description {
		width: 70%;
		max-width: 575px;
		min-width: 500px;
	}
</style>
<div class="wrap">
	<h2><?php _e( 'Field Trip Settings', 'field_trip' ); ?></h2>


	<form method="post" action="options.php">

		<?php settings_fields( 'field_trip_config' ); ?>

		<table class="form-table">
			<tr valign="top">
				<td>
					<h3>Format Settings</h3>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="fieldtrip_content_filter" id="fieldtrip_content_filter" value="1" <?php checked( get_option('fieldtrip_content_filter'), 1, true ); ?> />
					<label for="fieldtrip_content_filter">Only include paragraph/newline HTML tags in description</label>
				</td>
			</tr>
			<tr>
				<td>
					<h3>FeedBurner Redirect</h3>
				</td>
			</tr>
			<tr>
				<td colspan="1">
					<label for="fieldtrip_content_filter">If using a FeedBurner redirect plugin, we need to ensure FeedBurner does not alter our feed details.  Create a custom FeedBurner feed directed at the Field Trip Feed URL found below and enter that FeedBurner URL here.</label>
				</td>
			</tr>
			<tr valign="top">
				<td colspan="1">
					<input type="text" name="fieldtrip_rss_redirect" id="fieldtrip_rss_redirect" value="<?php echo get_option('fieldtrip_rss_redirect'); ?>" size="50" />
				</td>

			</tr>

			<tr valign="top">
				<td><?php submit_button( 'Save Settings' ); ?></td>
			</tr>
		</table>
	</form>

	<br><hr>

	<h3>Submit Your Site</h3>
	<form method="post" action="options.php">

		<?php settings_fields( 'field_trip_settings' ); ?>

		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="field_trip_feed_url"><?php _e( 'Field Trip Feed URL', 'field_trip' ); ?>:</label></th>
				<td><?php the_feed_link( get_feed_link( 'fieldtrip-feed' ), 'fieldtrip-feed' ) ?></td>
			</tr>

			<tr valign="top">
				<td colspan="2"><p class="description">When your content has been tagged with location using the Field Trip plugin, you can submit your site to be considered for inclusion in the Field Trip app.</p></td>
			</tr>

			<tr valign="top">
				<td><?php submit_button( 'Submit to Field Trip' ); ?></td>
			</tr>
		</table>
	</form>
</div>