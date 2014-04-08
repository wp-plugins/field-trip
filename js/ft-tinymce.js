(function() {
	tinymce.create('tinymce.plugins.Ftmap', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			ed.addButton('ft-map', {
				title : FTMapsStrings['Insert Map'],
				cmd : 'ftmap',
				image : url + '/ft-map-icon.png'
			});

			// Handle clicking on the 'map' button
			ed.addCommand( 'ftmap', function() {
				// Show the modal for inserting the map
				tb_show( FTMapsStrings['Insert Map'], "#TB_inline?inlineId=ft-mce-embed-map", null );

				var $ftoverlay = jQuery( '#TB_window'),
					$size = jQuery('#ftmap-size'),
					$customSize = jQuery('#fieldtrip-custom-size-container'),
					$width = jQuery('#ftmap-width'),
					$height = jQuery('#ftmap-height'),
					$align = jQuery('#ftmap-align');

				// Listen for the size to change - we may need to reveal the custom size inputs
				$ftoverlay.on( 'change', '#ftmap-size', function() {
					if ( 'custom' == jQuery(this).val() ) {
						$customSize.removeClass('hidden');
					} else {
						$customSize.addClass('hidden');
					}
				});

				// Listen for the 'insert' button
				$ftoverlay.on( 'click', '#ft-insert-map', function() {
					// Generate the shortcode
					var shortcodeOptions = {
						tag: 'map',
						attrs: {
							size: $size.val()
						},
						type: 'single'
					};

					// Add width & height only if this is a custom size
					if ( 'custom' == $size.val() ) {
						if ( $width.val().length > 0 && $width.val().length > 0 ) {
							shortcodeOptions.attrs.width = $width.val();
							shortcodeOptions.attrs.height = $height.val();
						} else {
							alert( FTMapsStrings["You must specify a width and height for custom sizes"] );
							return false;
						}
					}

					// Add the 'align' attribute only if it is set
					if ( $align.val().trim().length > 0 ) {
						shortcodeOptions.attrs.align = $align.val();
					}

					var shortcode = new wp.shortcode(shortcodeOptions);

					// Send the shortcode text to the editor
					ed.execCommand('mceInsertContent', 0, shortcode.string() );

					// Close the thickbox
					tb_remove();
				});
			});
		},

		/**
		 * Creates control instances based in the incomming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl : function(n, cm) {
			return null;
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : 'Field Trip Maps',
				author : '10up',
				authorurl : 'http://10up.com',
				infourl : 'http://10up.com',
				version : "0.1"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add( 'ftmap', tinymce.plugins.Ftmap );
})();