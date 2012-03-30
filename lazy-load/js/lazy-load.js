(function($) {
	lazy_load_init();
	$( 'body' ).bind( 'post-load', lazy_load_init ); // Work with WP.com infinite scroll

	function lazy_load_init() {
		jQuery( 'img[data-lazy-src]' ).bind( 'scrollin', { distance: 200 }, function() {
			var img = this,
				$img = jQuery(img),
				src = $img.attr( 'data-lazy-src' );
			$img.unbind( 'scrollin' ) // remove event binding
				.hide()
				.removeAttr( 'data-lazy-src' )
				.attr( 'data-lazy-loaded', 'true' );;
			img.src = src;
			$img.fadeIn();
		});
	}
})(jQuery);