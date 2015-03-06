<?php
header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=' . get_option( 'blog_charset' ), true );

echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?' . '>'; ?>
<rss version="2.0"
    xmlns:georss="http://www.georss.org/georss"
    xmlns:fieldtrip="http://www.fieldtripper.com/fieldtrip_rss">
 <channel>
		<title><?php bloginfo_rss( 'name' ); wp_title_rss(); ?></title>
		<link><?php bloginfo_rss( 'url' ) ?></link>
		<description><?php bloginfo_rss( 'description' ) ?></description>
		<generator>Field Trip Plugin for WordPress 1.1.2</generator>

		<pubDate><?php echo apply_filters( 'fieldtrip_feed_pubdate', mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ) ); ?></pubDate>
		<lastBuildDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ); ?></lastBuildDate>

		<language><?php bloginfo_rss( 'language' ); ?></language>

		<?php while( have_posts() ) : the_post();

			$location_meta = get_post_meta( get_the_ID(), '_field_trip_location_meta', true );

			$location_meta = wp_parse_args(
				$location_meta,
				array(
					'geographic_location' => '',
					'start_date' => '',
					'end_date' => '',
					'lat' => '',
					'lng' => '',
				)
			);

			?>
			<item>
				<title><?php the_title_rss() ?></title>

				<?php if ( ! empty( $location_meta['lat'] ) && ! empty( $location_meta['lng'] ) ) { ?>
					<georss:point><?php echo (float) $location_meta['lat'] . ' ' . (float) $location_meta['lng']; ?></georss:point>
				<?php } ?>
				<?php if ( ! empty( $location_meta['geographic_location'] ) ) { ?>
					<fieldtrip:address><?php echo esc_html( $location_meta['geographic_location'] ); ?></fieldtrip:address>
				<?php } ?>

				<link><?php the_permalink_rss() ?></link>
				<guid isPermaLink="false"><?php the_guid(); ?></guid>

				<?php if ( get_option('fieldtrip_content_filter') == 1 ) : ?>
					<description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
				<?php else : ?>
					<description><![CDATA[<?php the_content_feed(); ?>]]></description>
				<?php endif; ?>

				<pubDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ); ?></pubDate>

				<?php if ( has_post_thumbnail() ) {

					$featured_image_id   = get_post_thumbnail_id();
					$featured_image_url  = wp_get_attachment_url( $featured_image_id, 'full' );

					$featured_image      = get_posts( array(
													'p'         => $featured_image_id,
													'post_type' => 'attachment'
												)
											);

					?>

					<?php if ( $featured_image ) { ?>
						<fieldtrip:image>
							<url><?php echo esc_url( apply_filters( 'the_permalink_rss', $featured_image_url ) ); ?></url>
							<?php if ( isset( $thumbnail_image->post_title ) ) { ?>
								<title><?php echo esc_html( $thumbnail_image->post_title ); ?></title>
							<?php } ?>
						</fieldtrip:image>
					<?php } ?>

				<?php } ?>

				<?php
					if ( get_post_field( 'post_content', get_the_ID() ) ) {

						$post_content = get_post_field( 'post_content', get_the_ID() );
						if ( function_exists( 'mb_convert_encoding' ) ) {
							$post_content = mb_convert_encoding( $post_content, 'HTML-ENTITIES', get_option( 'blog_charset' ) );
						}

						if ( class_exists( 'DOMDocument' ) ) {
							$post_content_dom = new DOMDocument();
							$post_content_dom->loadHTML( $post_content );

							$images = $post_content_dom->getElementsByTagName( 'img' );

							if ( $images ) {

								foreach ( $images as $image ) {

									$title = $image->getAttribute( 'title' );

									if ( ! $title )
										$title = $image->getAttribute( 'alt' );

									?>
									<fieldtrip:image>
										<url><?php echo esc_url( apply_filters( 'the_permalink_rss', $image->getAttribute( 'src' ) ) ); ?></url>
										<title><?php echo esc_html( $title ); ?></title>
									</fieldtrip:image>
								<?php }

							}
						}

					}
				?>
				<?php if ( ! empty( $location_meta['start_date'] ) ) { ?>
					<fieldtrip:startDate><?php echo date( 'D, d M Y H:i:s O', strtotime( $location_meta['start_date'] ) ); ?></fieldtrip:startDate>
				<?php } ?>
				<?php if ( ! empty( $location_meta['end_date'] ) ) { ?>
					<fieldtrip:endDate><?php echo date( 'D, d M Y H:i:s O', strtotime( $location_meta['end_date'] ) ); ?></fieldtrip:endDate>
				<?php } ?>

				<author><?php the_author_meta( 'user_email' ); ?></author>

				<?php rss_enclosure(); ?>

			</item>
		<?php endwhile; ?>
	</channel>
</rss>
