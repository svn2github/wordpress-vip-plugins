(function ( $, window, undefined ) {
	'use strict';

	$( '#apple-news-publish-submit' ).click(function ( e ) {
		$( '#apple-news-publish-action' ).val( apple_news_meta_boxes.publish_action );
		$( '#post' ).submit();
	});

})( jQuery, window );
