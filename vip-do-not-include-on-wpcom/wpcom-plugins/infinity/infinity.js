(function($){ // Open closure

// Local vars
var Scroller, ajaxurl, stats;

/**
 * Loads new posts when users scroll near the bottom of the page.
 */
Scroller = function( id ) {
	var self = this;

	// Initialize our variables
	this.id       = id;
	this.body     = $( document.body );
	this.window   = $( window );
	this.element  = $( '#' + id );
	this.ready    = true;
	this.disabled = false;
	this.page     = 1;
	this.throttle = false;

	// Bind refresh to the scroll event
	// Throttle to check for such case every 300ms

	// On event the case becomes a fact
	this.window.bind( 'scroll.infinity', function() {
		this.throttle = true;
	});

	setInterval( function() {
		if ( this.throttle ) {
			// Once the case is the case, the action occurs and the fact is no more
			this.throttle = false;
			// Fire the refresh
			self.refresh();
		}
	}, 300 );
};

/**
 * Check whether we should fetch any additional posts.
 *
 * By default, checks whether the bottom of the viewport is within one
 * viewport-height of the bottom of the content.
 */
Scroller.prototype.check = function() {
	var bottom = this.window.scrollTop() + this.window.height(),
		threshold = this.element.offset().top + this.element.outerHeight() - this.window.height();

	return bottom > threshold;
};

/**
 * Renders the results from a successful response.
 */
Scroller.prototype.render = function( response ) {
	this.body.addClass( 'infinity-success' );
	this.element.append( '<div class="infinite-wrap infinite-view-' + ( this.page - 1 ) + '">' + response.html + '</div>' );
	this.body.trigger( 'post-load' );
	this.ready = true;
};

/**
 * Returns the object used to query for new posts.
 */
Scroller.prototype.query = function() {
	return {
		page: this.page
	};
};

/**
 * Controls the flow of the refresh. Don't mess.
 */
Scroller.prototype.refresh = function() {
	var	self   = this,
		query, jqxhr, load, loader;

	// If we're disabled, ready, or don't pass the check, bail.
	if ( this.disabled || ! this.ready || ! this.check() )
		return;

	// Let's get going -- set ready to false to prevent
	// multiple refreshes from occurring at once.
	this.ready = false;

	// Create a loader element to show it's working.
	loader = '<span class="infinite-loader">Loading…</span>';
	this.element.append( loader );

	loader = this.element.find( '.infinite-loader' );

	// Generate our query vars.
	query = $.extend({
		action: 'infinite_scroll'
	}, this.query() );

	// Fire the ajax request.
	jqxhr = $.post( ajaxurl, query );

	// Allow refreshes to occur again if an error is triggered.
	jqxhr.fail( function() {
		loader.hide();
		self.ready = true;
	});

	// Success handler
	jqxhr.done( function( response ) {

			// On success, let's hide the loader circle.
			loader.hide();

			// Check for and parse our response.
			if ( ! response )
				return;

			response = $.parseJSON( response );

			if ( ! response || ! response.type )
				return;

			// If there are no remaining posts...
			if ( response.type == 'empty' ) {
				// Disable the scroller.
				self.disabled = true;
				// Update body classes, allowing the footer to return to static positioning
				self.body.addClass( 'infinity-end' ).removeClass( 'infinity-success' );

			// If we've succeeded...
			} else if ( response.type == 'success' ) {
				// Increment the page number
				self.page++;

				// Render the results
				self.render.apply( self, arguments );
			}
		});

	return jqxhr;
};

/**
 * Ready, set, go!
 */
$( document ).ready( function() {
	// Check for our variables
	if ( ! infiniteScroll )
		return;

	// Set ajaxurl (for brevity)
	ajaxurl = infiniteScroll.settings.ajaxurl;

	// Set stats, used for tracking stats
	stats = infiniteScroll.settings.stats;

	// Initialize the scroller (with the ID of the element from the theme)
	infiniteScroll.scroller = new Scroller( infiniteScroll.settings.id );
});


})(jQuery); // Close closure
