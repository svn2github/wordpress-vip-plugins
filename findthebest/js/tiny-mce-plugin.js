( function( $ ) {
	tinyMCE.PluginManager.add( 'findthebest', function( editor, url ) {
		var getAttribute = function( s, n ) {
			n = new RegExp( n + '=\"([^\"]+)\"', 'g' ).exec( s );
			return n ? tinyMCE.DOM.decode( n[ 1 ] ) : '';
		};

		var replaceShortcode = function( co ) {
			var replaceCallback = function( match, options ) {
				var name = getAttribute( options, 'name' );
				var id = getAttribute( options, 'id' );
				var width = getAttribute( options, 'width' );
				var height = getAttribute( options, 'height' );
				var style = '';

				if ( width.length > 0 && height.length > 0 ) {
					style = 'width: ' + width + 'px; height: ' + height + 'px;';
				}

				var title = ftbData.editWidgetMessage + name;

				return '<img class="ftb-tiny-mce-widget mceItem" ' +
					'data-code="findthebest' + tinyMCE.DOM.encode( options ) +
					'" data-id="' + id + '" data-title="' + name +
					'" title="' + title + '" style="' + style + '">';
			};

			return co.replace( /\[findthebest([^\]]*)\]/g, replaceCallback );
		};

		var emplaceShortcode = function( co ) {
			var replaceCallback = function( match, options ) {
				var cls = getAttribute( options, 'class' );

				if ( -1 !== cls.indexOf( 'ftb-tiny-mce-widget' ) ) {
					return '[' + getAttribute( options, 'data-code' ) + ']<br /><br />';
				}

				return match;
			};

			return co.replace( /(<img[^>]+>)/g, replaceCallback );
		};

		editor.onBeforeSetContent.add( function( editor, o ) {
			o.content = replaceShortcode( o.content );
		} );

		editor.onChange.add( function() {
			FTBWP.inputChanged = true;
		} );

		editor.onDblClick.add( function( editor, event ) {
			var $node = $( event.target );
			if ( ! $node.hasClass( 'ftb-tiny-mce-widget' ) ) {
				return;
			}

			FTBWP.startEdit( $node.attr( 'data-id' ) );
		} );

		editor.onExecCommand.add( function( editor, command ) {
			if ( 'mceInsertContent' === command ) {
				FTBWP.hideEditButton();
				var content = tinyMCE.activeEditor.getContent();
				tinyMCE.activeEditor.setContent( replaceShortcode( content ) );
			}
		} );

		editor.onNodeChange.add( function( editor, command, node ) {
			FTBWP.hideEditButton();

			var $node = $( node );
			if ( ! $node.hasClass( 'ftb-tiny-mce-widget' ) ) {
				return;
			}

			FTBWP.showEditButton( $node.attr( 'data-id' ) );
			FTBWP.prePendingWidgetTinyMCENode = node;

			editor.dom.bind( node, 'DOMNodeRemoved', function() {
				FTBWP.hideEditButton();
			} );
		} );

		editor.onPostProcess.add( function( editor, o ) {
			if ( o.get ) {
				o.content = emplaceShortcode( o.content );
			}
		} );
	} );
} )( jQuery );
