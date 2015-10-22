( function( $ ) {

	var ftbData = window.ftbData || {};
	var version = +ftbData.wpVersion;

	$.each( ['findthebest', 'graphiq'], function( i, shortcodeLabel ) {
		// View API changed significantly in 4.2
		if (version >= 4.2) {

			wp.mce.views.register( shortcodeLabel, {
				template: wp.media.template( 'editor-findthebest' ),
				getContent: function () {
					return this.template(this.shortcode.attrs.named);
				}
			});

			// 3.9 - 4.1, use old view API
		} else {

			wp.mce.views.register( shortcodeLabel, {
				View: {
					template: wp.media.template( 'editor-findthebest' ),
					postID: $('#post_ID').val(),
					initialize: function (options) {
						this.shortcode = options.shortcode;
					},
					getHtml: function () {
						return this.template(this.shortcode.attrs.named);
					}
				}
			});

		}
	} );

} )( jQuery );