(function($) {
	$( document ).ready( responsive_images_init );
	$( 'body' ).bind( 'post-load', responsive_images_init ); // Work with WP.com infinite scroll

	function responsive_images_init() {
		var $window = $(window),
			screen_width = $window.width(),
			screen_height = $window.height();

		jQuery( 'img[data-full-src]' ).each( function( i ) {
			var img = this,
				$img = $(img),
				src = $img.attr( 'data-full-src' ),
				max_width = $img.attr( 'data-full-width' ),
				max_height = $img.attr( 'data-full-height' );

			$img.hide()
				.removeAttr( 'data-full-src' )
				.attr( 'data-responsive-loaded', 'true' );

			// if the image doesn't have a given dimension, set to screen dimension.
			// if the image does have a dimension, set to screen if image dimension is bigger.
			// otherwise default to image dimension.
			var img_width = ! max_width || max_width > screen_width ? screen_width : max_width;
			var img_height = max_height ? max_height : null;

			if ( img_width )
				src = responsive_add_query_arg( 'w', img_width, src );

			if ( img_height )
				src = responsive_add_query_arg( 'h', img_height, src );

			img.src = src;

			$img.fadeIn(); // bring it in smooth and super sexy-like
		} );
	}
})(jQuery);

// This is a lame and fragile way to add params to a URL but it works for now
// by annakata at http://stackoverflow.com/a/487049/169478
function responsive_add_query_arg( key, value, url ) {
    key = escape( key );
	value = escape( value );

    var kvp = url.split( '&' ),
		i = kvp.length,
		x;

	while(i--) {
		x = kvp[i].split('=');

		if ( x[0] == key ) {
				x[1] = value;
				kvp[i] = x.join( '=' );
				break;
		}
	}

    if( i < 0 )
		kvp[kvp.length] = [key,value].join( '=' );

	url = kvp.shift();
	if( -1 == url.indexOf( '?' ) && kvp.length )
		url += '?';
	else if ( kvp.length )
		url += '&';

    url += kvp.join( '&' );

	return url;
}