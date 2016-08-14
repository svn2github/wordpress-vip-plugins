// Javascript for public pages
(function( $ ) {
	'use strict';
	
	// On DOM Ready:
	$(function() {
		// Need to select every image with ndn class
		$( '.ndn-image-embed' ).each( ndnConvertImageToVideo );
		
	});

  /**
   * get query variable of the uri
   * @param  {string} variable string query key you would like to match
   * @return {string || bool}          value of the key, or false
   */
	function ndnGetQueryVariable(variable) {
		var query = window.location.search.substring( 1 ),
		  vars = query.split( '&' ),
			i;
		for ( i = 0; i < vars.length; i++ ) {
			var pair = vars[i].split( '=' );

			if( pair[0] === variable ){
				return pair[1];
			}
		}
		return ( false );
	}

	/**
	 * Converts the image to the embed div for embed.js
	 * @return {void}
	 */
	function ndnConvertImageToVideo() {
		
		// This is where we will convert the images from the ndn class to ndn embed with configuration settings
		/*jshint validthis:true */
		var self = this;
		var attrs = {
			'elementClass': !!$( self ).attr( 'ndn-video-element-class' ) ? $( self ).attr( 'ndn-video-element-class' ) : '',
			'configVideoID': $( self ).attr( 'ndn-config-video-id' ),
			'configWidgetID': $( self ).attr( 'ndn-config-widget-id' ),
			'configBev': $( this ).attr( 'data-config-pb' ),
			'trackingGroup': $( self ).attr( 'ndn-tracking-group' ),
			'siteSectionID': $( self ).attr( 'ndn-site-section-id' ),
			'responsive': $( self ).attr( 'ndn-responsive' ),
			'width': $( self ).attr( 'ndn-video-width' ),
			'height': $( self ).attr( 'ndn-video-height' )
		};
		
		// Post preview mode (admin only)
		if ( ndnGetQueryVariable( 'preview' ) ) {
			attrs.configWidgetID = attrs.configWidgetID === '1' ? '4' : '17909';
		}
		var buildHtml = $('<div></div>');
		if (attrs.elementClass.indexOf('ndn_embed') >= 0) {
		  buildHtml.append('<div class="ndn_embed ndn_embed_wordpress"></div>');
		} else {
		  buildHtml.addClass( attrs.elementClass ).append('<div class="ndn_embed ndn_embed_wordpress"></div>');
		}
		if ( !attrs.responsive ) {
			buildHtml.css({
				'height': attrs.height,
				'width': attrs.width
			});
		}

		$( buildHtml ).find( '.ndn_embed' )
			.attr({
				'data-config-width': '100%',
				'data-config-height': '9/16w',
				'data-config-widget-id': attrs.configWidgetID,
				'data-config-type': 'VideoPlayer/Single',
				'data-config-video-id': attrs.configVideoID,
				'data-config-tracking-group': attrs.trackingGroup,
				'data-config-site-section': attrs.siteSectionID,
				'data-config-pb' : attrs.configBev
			});

		var html = buildHtml.prop('outerHTML');

	$( self ).replaceWith( html );
		var _informq = _informq || [];
		 _informq.push(['embed']);
		
	}

})( jQuery );
