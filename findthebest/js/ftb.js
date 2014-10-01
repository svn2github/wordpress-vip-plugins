function supportsSVG() {
	if ( ! document.createElementNS ) {
		return false;
	}

	var standard = 'http://www.w3.org/2000/svg';
	return ! ! document.createElementNS( standard, 'svg' ).createSVGRect;
}

// Initializes the admin meta box using the FTB (FindTheBest) namespace
// functionality.
jQuery( document ).ready( function( $ ) {
	if ( supportsSVG() ) {
		$( '#ftb-sidebar .no-svg' ).hide();
		$( '#ftb-sidebar .svg' ).attr( 'style', function( i, s ) {
			if ( 'undefined' === typeof s ) {
				return 'display: inline !important;';
			}

			return s + 'display: inline !important;';
		} );
	}

	$( '#ftb-search' ).click( function() {
		FTBWP.searchSuggestions();
		return false;
	} );

	$( '#ftb-term' ).keypress( function( event ) {
		if ( 13 === event.which ) {
			FTBWP.searchSuggestions();
			return false;
		}

		return true;
	} );

	$( '.wp-switch-editor' ).click( function() {
		FTBWP.hideEditButton();
	} );

	$( '#ftb-edit' ).click( function() {
		FTBWP.startEdit();
	} );

	$( '#content' ).bind( 'keyup click', function() {
		FTBWP.checkHighlightedText();
	} );

	try {
		FTBWP.preferences = $.parseJSON( ftbData.widgetDesignerPrefs );
	} catch ( exception ) {
		FTBWP.preferences = { };
	}
} );

// Defines the main FindTheBest plugin functionality.
( function( $ ) {
	FTBWP = { };

	FTBWP.lastQuery = null;
	FTBWP.pendingWidgetId = null;
	FTBWP.pendingWidgetTinyMCENode = null;
	FTBWP.pendingWidgetTextRange = null;
	FTBWP.prePendingWidgetTinyMCENode = null;
	FTBWP.preferences = { };
	FTBWP.xhr = null;

	FTBWP.checkHighlightedText = function() {
		var widgetId = FTBWP.textHighlightedWidgetId();
		if ( null === widgetId ) {
			FTBWP.hideEditButton();
			return;
		}

		FTBWP.showEditButton( widgetId );
	};

	FTBWP.clearTextRange = function() {
		var textRange = FTBWP.pendingWidgetTextRange;
		if ( null === textRange ) {
			return false;
		}

		var $content = $( '#content' );
		var value = $content.val();
		var newValue = value.substr( 0, textRange[ 0 ] );
		newValue += value.substr( textRange[ 1 ] + 1 );
		$content.val( newValue );

		$content.caretPosition( textRange[ 0 ] );

		FTBWP.pendingWidgetTextRange = null;

		return true;
	};

	FTBWP.fetchSuggestions = function( search ) {
		if ( FTBWP.xhr ) {
			FTBWP.xhr.abort();
		}

		$( '#ftb-info' ).hide();
		$( '#ftb-no-results' ).hide();
		$( '#ftb-suggestions' ).show();
		$( '#ftb-loading' ).show();
		$( '#ftb #ftb-search' ).addClass( 'disabled' );

		var data = { };
		var length;

		data.form_id = 'api_wordpress_form';
		data.form_token = '7bc782c9f310560473ea95ecf055fadf';
		data.form_build_id = 'form-a-js20D03aHn24OpG1Gl07DdFkwrYQLW2KbPMXbRBco';

		data.is_content = 0;
		data.term = search;
		length = data.term.length;
		FTBWP.lastQuery = search;

		if ( length < 2 ) {
			$( '#ftb-loading' ).hide();
			$( '#ftb #ftb-search' ).removeClass( 'disabled' );
			FTBWP.showNoResults();
			return;
		}

		FTBWP.xhr = $.ajax( {
			data: data,
			dataType: 'json',
			type: 'POST',
			url: ftbData.remoteRoot + '/api/wordpress'
		} );

		FTBWP.xhr.done( function( data ) {
			FTBWP.updateSuggestions( data );
		} );

		FTBWP.xhr.fail( FTBWP.checkIfRateLimited );

		FTBWP.xhr.fail( function( xhr, textStatus ) {
			if ( 'abort' === textStatus ) {
				return;
			}

			FTBWP.updateSuggestions( null );
		} );
	};

	FTBWP.checkIfRateLimited = function( xhr, textStatus ) {
		// Only handle 403 requests
		if ( +xhr.status !== 403 ) {
			return;
		}

		// Hide any existing boxes
		BOX.hide();

		// Show unblock form if its a 403
		BOX.show( {
			iframe: ftbData.remoteRoot + '/block_request_form',
			w: 650,
			h: 650,
			pad: 20
		} );

	};

	FTBWP.getBody = function() {
		var content;

		if ( FTBWP.isTinyMCEActive() && tinyMCE.activeEditor ) {
			content = tinyMCE.activeEditor.getContent();
		} else {
			content = $( '#content' ).val();
		}

		if ( ! content ) {
			return '';
		}

		// Remove WordPress square bracket tags.
		content = content.replace( /\[[^\[\]]*\]\s*/g, '' );

		var maxLength = 15000;
		if ( content.length > maxLength ) {
			content = content.substr( content.length - maxLength );
			var trimSpot = Math.max( 0, content.indexOf( ' ' ) );
			content = content.substr( trimSpot );
		}

		return content;
	};

	FTBWP.getTags = function() {
		var $tagContainers = $( '#tagsdiv-post_tag .tagchecklist span' );
		var tags = $tagContainers.map( function( index, element ) {
			return $( this ).contents().filter( function() {
				// Checks if the element node is of type TEXT_NODE.
				return this.nodeType === 3;
			} ).text().trim();
		} ).get();

		return tags.join();
	};

	FTBWP.getTitle = function() {
		return $( 'input[name=post_title]' ).val();
	};

	FTBWP.hideEditButton = function () {
		FTBWP.pendingWidgetId = null;
		FTBWP.pendingWidgetTextRange = null;
		FTBWP.pendingWidgetTinyMCENode = null;
		$( '#ftb-edit' ).hide();
	};

	FTBWP.insertPressed = function( preferences, options ) {
		FTBWP.updatePreferences( preferences );

		FTBWP.removeOldTinyMCENode();
		var replacingOldShortcode = FTBWP.clearTextRange();

		FTBWP.pendingWidgetId = null;

		options.title = FTBWP.shortcodeSanitize( options.title );

		var shortcode = '[findthebest id="' + options.id + '" name="' +
			options.title + '" width="' + options.width + '" height="' +
			options.height + '" link="' + options.link + '" url="' + options.url +
			'"]';

		if ( ! replacingOldShortcode ) {
			shortcode += '\n\n';
		}

		window.send_to_editor( shortcode );
		BOX.hide();

		FTBWP.checkHighlightedText();
	};

	FTBWP.isTinyMCEActive = function() {
		if ( ! FTBWP.tinyMCEExists() ) {
			return false;
		}

		return $( '#wp-content-wrap' ).hasClass( 'tmce-active' );
	};

	FTBWP.itemImage = function( image, alignRight ) {
		if ( ! image ) {
			return null;
		}

		var $imageContainer = $( '<span>', {
			'class': alignRight ? 'image-container right' : 'image-container'
		} );

		var $image = $( '<div>', {
			'class': 'image'
		} ).appendTo( $imageContainer );

		$( '<img>', {
			src: image
		} ).appendTo( $image );

		return $imageContainer;
	};

	FTBWP.launchDesigner = function( $item ) {
		if ( FTBWP.isTinyMCEActive() ) {
			tinyMCE.activeEditor.selection.collapse();
		}

		var designerOptions = {};

		var url = ftbData.remoteRoot + '/api/wordpress_widget_designer?';
		if ( 'string' === typeof $item ) {
			url += 'wid=' + encodeURIComponent( $item ) + '&';
		} else if ( $item.data( 'info' ) ) {
			$.extend( designerOptions, $item.data( 'info' ).widgetDesignerArgs );
		}
		$.extend( designerOptions, FTBWP.preferences );
		url += 'options=' + encodeURIComponent( JSON.stringify( designerOptions ) );

		var html = '<div id="ftb-wd-load"><img src="' + ftbData.loadingImagePath
			+ '"><br>' + ftbData.loadingMessage + '</div><iframe name="ftb-wd-box" '
			+ 'id="ftb-wd" src="' + url + '" style="position: absolute; '
			+ 'top: -10000px;"></iframe>';

		BOX.show( {
			bg: 'transparent',
			html: html,
			overflow: 'hidden',
			top: 50
		} );

		var loadPromise = FTBWP.load = $.Deferred().fail( FTBWP.loadFailed );
		setTimeout( loadPromise.reject, 8000 ); // Timeout after 10 seconds

		// Need protocol for receiveMessage interface
		var receiveMessageRoot = window.location.protocol + ftbData.remoteRoot;
		$.receiveMessage( FTBWP.widgetDesignerHandler, receiveMessageRoot );

		window.scrollTo( 0, 0 );
	};

	FTBWP.loadFailed = function() {
		// Initiate a dummy uncached request to check if the user was rate limited
		var url = ftbData.remoteRoot + '/api/wordpress_widget_check?d=' + (new Date().getTime());
		$.get( url ).fail( FTBWP.checkIfRateLimited );
	};

	FTBWP.removeOldTinyMCENode = function() {
		var node = FTBWP.pendingWidgetTinyMCENode;
		if ( null === node ) {
			return;
		}

		FTBWP.pendingWidgetTinyMCENode = null;

		if ( ! FTBWP.isTinyMCEActive() ) {
			return;
		}

		tinyMCE.activeEditor.dom.remove( node );
	};

	FTBWP.reportAnalytics = function() {
		// To be overridden.
	};

	FTBWP.searchSuggestions = function( useAutosuggestTags ) {
		FTBWP.fetchSuggestions( $( '#ftb-term' ).val() );
	};

	FTBWP.shortcodeSanitize = function( text ) {
		return text
			.replace( /\[/g, '(' )
			.replace( /\]/g, ')' )
			.replace( /\"/g, '\'' );
	};

	FTBWP.showEditButton = function( id ) {
		FTBWP.pendingWidgetId = id;
		$( '#ftb-edit' ).show();
	};

	FTBWP.showNoResults = function() {
		$( '#ftb-no-results' ).text( ftbData.noContentSearchMessage );
		$( '#ftb-no-results' ).show();
		$( '#ftb-suggestions' ).hide();
		$( '#ftb-suggestions' ).empty();
		$( '#ftb-suggestions' ).scrollTop( 0 );
	};

	FTBWP.startEdit = function( id ) {
		if ( 'undefined' !== typeof id ) {
			FTBWP.pendingWidgetId = id;
		}

		if ( null === FTBWP.pendingWidgetId ) {
			return;
		}

		if ( null !== FTBWP.prePendingWidgetTinyMCENode ) {
			var node = FTBWP.prePendingWidgetTinyMCENode;
			FTBWP.prePendingWidgetTinyMCENode = null;
			FTBWP.pendingWidgetTinyMCENode = node;
		}

		FTBWP.launchDesigner( FTBWP.pendingWidgetId );
	};

	FTBWP.textHighlightedWidgetId = function() {
		var position = $( '#content' ).caretPosition();
		if ( position.start < 0 || position.end < 0 ) {
			return null;
		}

		var value = $( '#content' ).val();

		var tagOpenPosition = value.lastIndexOf( '[findthebest', position.start );
		if ( -1 === tagOpenPosition ) {
			return null;
		}

		var tagClosePosition = value.indexOf( ']', tagOpenPosition );
		var cursorStartWithin = position.start >= tagOpenPosition;
		var cursorEndWithin = tagClosePosition >= position.end - 1;
		if ( ! cursorStartWithin || ! cursorEndWithin ) {
			return null;
		}

		var idOpenPosition = value.indexOf( ' id="', tagOpenPosition );
		if ( -1 === idOpenPosition ) {
			return null;
		}

		idOpenPosition += 5;

		var idClosePosition = value.indexOf( '"', idOpenPosition );
		if ( -1 === idClosePosition ) {
			return null;
		}

		FTBWP.pendingWidgetTextRange = [ tagOpenPosition, tagClosePosition ];
		var idLength = idClosePosition - idOpenPosition;

		return value.substr( idOpenPosition, idLength );
	};

	FTBWP.tinyMCEExists = function() {
		return 'undefined' !== typeof tinyMCE;
	};

	FTBWP.updatePreferences = function( preferences ) {
		FTBWP.preferences = preferences;

		if ( null === FTBWP.preferences ) {
			FTBWP.preferences = { };
			return;
		}

		var json = JSON.stringify( FTBWP.preferences );
		var data = {
			action: 'ftb_save_prefs',
			prefs: json
		};

		$.post( ftbData.ajaxPath, data );
	};

	FTBWP.updateSuggestions = function( data ) {
		FTBWP.xhr = null;

		$( '#ftb-suggestions' ).empty().scrollTop( 0 );
		$( '#ftb-loading' ).hide();
		$( '#ftb #ftb-search' ).removeClass( 'disabled' );

		if ( ! data ) {
			FTBWP.showNoResults();
			return;
		}

		if ( ! data.hasOwnProperty( 'order' ) ) {
			return;
		}

		var totalItems = 0;
		for ( var index = 0; index < data.order.length; index++ ) {
			var section = data.order[ index ];
			if ( ! data.hasOwnProperty( section.object ) ) {
				continue;
			}

			totalItems += data[section.object].length;

			FTBWP.updateSuggestionsForSection( section, data[ section.object ] );
		}

		if ( 0 === totalItems ) {
			FTBWP.showNoResults();
			return;
		}

		$( '#ftb-suggestions .item' ).click( function() {
			var $item = $( this );
			var info = $item.data( 'info' );
			if ( info && ! info.widgetDesignerArgs ) {
				var newWindow = window.open( info.url, '_blank' );
				newWindow && newWindow.focus();
				return;
			}

			FTBWP.launchDesigner( $item );

			if ( ! FTBWP.lastQuery ) {
				return;
			}

			FTBWP.reportAnalytics( 'launched_designer', {
				desc: FTBWP.lastQuery,
				purpose: info.widgetDesignerArgs,
				url: info.url
			} );
		} );
	};

	FTBWP.updateSuggestionsForSection = function( section, items ) {
		if ( 0 === items.length ) {
			return;
		}

		if ( section.name ) {
			var $section = $( '<div>', { 'class': 'section' } );

			$( '<div>', {
				'class': 'section-text',
				text: section.name
			} ).appendTo( $section );

			$section.appendTo( '#ftb-suggestions' );
		}

		for ( var index = 0; index < items.length; index++ ) {
			var item = items[ index ];

			var $itemContainer = $( '<div>', { 'class': 'item' } );

			$itemContainer.data( 'info', item );
			$itemContainer.data( 'section', section.object );

			var $itemContent = $( '<div>', {
				'class': 'item-content'
			} ).appendTo( $itemContainer );

			var imageCount = item.images ? item.images.length : 0;
			var image = imageCount >= 1 && item.images[ 0 ] ? item.images[ 0 ] : null;

			$imageContainer = FTBWP.itemImage( image, false );
			if ( $imageContainer ) {
				$imageContainer.appendTo( $itemContent );
			}

			$( '<span>', {
				'class': 'label',
				html: item.labelHTML
			} ).appendTo( $itemContent );

			$itemContainer.appendTo( '#ftb-suggestions' );

			if ( imageCount < 2 ) {
				continue;
			}

			$imageContainer = FTBWP.itemImage( item.images[ 1 ], true );
			if ( $imageContainer ) {
				$imageContainer.appendTo( $itemContent );
			}
		}
	};

	FTBWP.widgetDesignerHandler = function( event ) {
		var data = JSON.parse( event.data );
		var method = data.method;

		switch ( method ) {
			case 'resize':
				$( '#ftb-wd-load' ).hide();
				$( '#ftb-wd' ).css('position', 'static');
				$( '#ftb-wd' ).css('top', 'auto');
				$( '#ftb-wd' ).width( data.width );
				$( '#ftb-wd' ).height( data.height );
				BOX.size( data.width, data.height, true );
				break;

			case 'insert':
				FTBWP.insertPressed( data.preferences, data.options );
				break;

			case 'load':
				FTBWP.load && FTBWP.load.resolve();
				break;
		}
	};
} )( jQuery );
