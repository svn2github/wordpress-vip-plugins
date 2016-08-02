GraphiqSearchWP = ( function( $ ) {

	var recommender;

	function init() {

		// Set-up event handle on-readys
		$( document ).ready( function() {
			// Search from media button
			$( document ).on( 'click', '.graphiq-media-button', function() {
				launch();
			} );

			// Search from sidebar
			$( document ).on( 'click', '.graphiq-search-button', function() {
				launchFromSidebar();
			} );

			// Pressing enter in sidebar search
			$( '.graphiq-search-input' ).keypress( function( event ) {
				if ( 13 === event.which ) {
					launchFromSidebar();
					return false;
				}
				return true;
			} );
		} );
	}

	function getTitle() {
		return $( 'input[name=post_title]' ).val();
	}

	function getText() {
		var content;

		if ( isTinyMCEActive() && tinyMCE.get( 'content' ) ) {
			// Retrieve content from tinyMCE
			content = tinyMCE.get( 'content' ).getContent();
		} else {
			// Retrieve content from HTML editor textarea
			content = $( '#content' ).val();
		}

		if ( ! content ) {
			return '';
		}

		// Remove WordPress shortcode tags.
		content = content.replace( /\[[^\[\]]*\]\s*/g, '' );

		var maxLength = 15000;
		if ( content.length > maxLength ) {
			content = content.substr( content.length - maxLength );
			var trimSpot = Math.max( 0, content.indexOf( ' ' ) );
			content = content.substr( trimSpot );
		}

		return content;
	}

	function getTags() {
		var $tagContainers = $( '#tagsdiv-post_tag .tagchecklist span' );
		var tags = $tagContainers.map( function( index, element ) {
			return $( this ).contents().filter( function() {
				// Checks if the element node is of type TEXT_NODE.
				return this.nodeType === 3;
			} ).text().trim();
		} ).get();

		return tags.join();
	}

	function insertPressed( asset ) {
		if ( asset.type === 'story' ) {
			insertStory( asset );
		} else {
			insertVisualization( asset );
		}
	}

	function insertVisualization( asset ) {
		var properties = ['id', 'title', 'width', 'height', 'url', 'link', 'link_text', 'frozen'];

		var attributes = $.map( properties, function( property, i ) {
			if ( ! asset.hasOwnProperty( property ) || asset[ property ] === '' ) {
				return '';
			}
			return property + '="' + sanitizeShortcode( asset[ property ] ) + '"';
		} );

		var shortcode = '[graphiq ' + attributes.join( ' ' ) + ']\n\n';
		window.send_to_editor( shortcode );
	}

	function insertStory( asset ) {
		if ( asset.title ) {
			$( 'input[name=post_title]').focus().val( asset.title );
		}
		window.send_to_editor( asset.code );
	}

	function isTinyMCEActive() {
		if ( 'undefined' === typeof tinyMCE ) {
			return false;
		}

		return $( '#wp-content-wrap' ).hasClass( 'tmce-active' );
	}

	function sanitizeShortcode( text ) {
		text = '' + text; // Coerce to string
		return text
			.replace( /\[/g, '(' )
			.replace( /]/g,  ')' )
			.replace( /"/g,  '\'' );
	}

	function launchFromSidebar() {
		launch( $( '.graphiq-search-input' ).val() );
	}

	function launch( query ) {
		if ( !recommender ) {
			recommender = new GraphiqSearch({
				key: graphiqSearchData.apiKey,
				userID: graphiqSearchData.userID,
				userEmail: graphiqSearchData.userEmail,
				locale: graphiqSearchData.locale,
				client: 'wordpress',
				clientVersion: graphiqSearchData.pluginVersion,
				embedType: 'wordpress',
				title: getTitle,
				text: getText,
				tags: getTags
			});

			recommender.on( 'select', function(result) {
				insertPressed(result);
			} );
		}

		// Will automatically show the search and use the specified query if passed
		// (otherwise the default title/text/tags callbacks will be used)
		recommender.search( query );
	}

	init();

	// Exports
	return {
		launch: launch
	};

} )( jQuery );
