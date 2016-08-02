( function( $ ) {

	var graphiqSearchData = window.graphiqSearchData || {};
	var version = +graphiqSearchData.wpVersion;
	var graphiqView;

	$.each( graphiqSearchData.shortcodes, function( i, shortcodeLabel ) {
		// View API changed significantly in 4.2
		if (version >= 4.2) {

			graphiqView = {
				template: wp.media.template( 'editor-graphiq' ),
				getContent: function () {
					return this.template( this.shortcode.attrs.named );
				}
			};

			// 3.9 - 4.1, use old view API
		} else {

			graphiqView = $.extend( {}, wp.mce.media, {
				shortcode: shortcodeLabel,
				edit: $.noop, // Override wp.mce.media
				View: wp.mce.View.extend({
					className: 'editor-graphiq',
					template: wp.media.template( 'editor-graphiq' ),
					postID: $( '#post_ID' ).val(),
					initialize: function ( options ) {
						this.shortcode = options.shortcode;
					},
					getHtml: function () {
						return this.template( this.shortcode.attrs.named );
					}
				})
			} );

		}

		wp.mce.views.register( shortcodeLabel, graphiqView );

	} );

} )( jQuery );