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