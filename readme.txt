=== Plugin Name ===
Contributors: nianticlabs, 10up
Tags: fieldtrip, geolocation, rss, map
Requires at least: 3.0.1
Tested up to: 4.1.2
Stable tag: 1.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin adds the ability to set a location and other data for a post that is compatible with Field Trip.
== Description ==

[Field Trip](http://www.fieldtripper.com/) is an Android/iOS app that is a guide to the cool, hidden, and unique things in the world around you. Field Trip can help you learn about everything from local history to the latest and best places to shop, eat, and have fun. You select the local feeds you like and the information pops up on your phone automatically, as you walk next to those places.

This Wordpress plugin adds the ability to set a location and other data for a post that is compatible with Field Trip.

The additional tags that will be added to your post and feed are:

* Location - this can either be a latitude/longitude or a street address
* Images - this is a list of images to be shown in the Field Trip image carousel
* Date to stop showing card in Field Trip
* Date to start showing card in Field Trip, entering no date will show card as soon as it is published in Field Trip

If you are currently not a publisher for Field Trip, you can submit your site for inclusion in Field Trip through the Field Trip Wordpress plugin settings page.
== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. To request inclusion in the Field Trip app, go to the Settings menu, select Field Trip Settings, and click on Submit to Field Trip.

== Changelog ==

= 1.1.3 =
* Fix: Security update adding esc_url_raw() to feed submission url using add_query_arg( ).

= 1.1.2 =
* New: Added ability to specify a custom FeedBurner URL if using one of the FeedBurner Redirect plugins to prevent Field Trip from breaking.

= 1.1.1 =
* Fix: Make sure that all error messages that would be triggered when clicking save/update post are also displayed when previewing the map via AJAX calls.

= 1.1.0 =
* Fix: Ensure that PHP libraries are present before using them to generate the xml feed.
* New: Added a 'map' shortcode to embed a map of the location associated with the post.
* New: Location preview now updates without having to save the post.

= 1.0.2 =
* Increase the maximum acceptable distance for locations

= 1.0.1 =
* Moves the setting for whether to include paragraph/newline HTML tags in description from per-post to a per-blog option in the Field Trip Settings menu.

= 1.0 =
* Initial release of the plugin

= 1.0.0-beta.06.20.2013 =
*Update: Omit the <georss:point> and <fieldtrip:address> tags if there's no associated location.

= 1.0.0-beta.06.18.2013 =
*Update: allow feed to return all posts, including those that are not geotagged.
*Fix: the_content_rss() depricated. Replaced with the_content_feed().

= 1.0.0-beta.06.14.2013 =
*Fix: Proper escaping of image title. Ex: © 1<2 & 2>1 should be &quot;©&quot; 1&lt;2 &amp; 2&gt;1
*Fix: If you select a time zone in the start date and click Done, once you click the start date field again, it reverts back to the GMT -11:00 time zone. Timezone data needed to be in offset number in minutes.
*Fix: Do not show optional attributes if empty.

= 1.0.0-beta.06.07.2013 =
*Fix: Special Characters would show as strange glyphs. Proper encoding for fieldtrip:image title.
*Fix: If no start date or end specified fieldtrip:startDate and fieldtrip:endDate would show date as 00:00:00 UTC on 1 January 1970.

= 1.0.0-beta.06.04.2013 =
*Fix: Title for images in feed sometimes isn't populated. Fallback to alt attribute if title attribute doesn't exist on image.

== Upgrade Notice ==
= 1.1.2 =
Added ability to specify a custom FeedBurner URL if using one of the FeedBurner Redirect plugins to prevent Field Trip from breaking.
