(function() {

	wp.media.view.Search = wp.media.view.Search.extend({

		events: {
			'input':   'maybeSearch',
			'keyup':   'maybeSearch',
			'change':  'maybeSearch',
			'search':  'maybeSearch',
			'keydown': 'maybeSearch' 
		},

		timer: null,

		maybeSearch: function( event ) {
			var self = this;
			clearTimeout( self.timer );
			
			if ( 'keydown' === event.type && 13 === event.keyCode ) {
				self.search( event );
			} else {
				self.timer = setTimeout( function() { self.search( event ); }, 500 );
			}
		}

	});
})();
