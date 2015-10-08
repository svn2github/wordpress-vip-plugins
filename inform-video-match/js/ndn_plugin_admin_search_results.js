( function( $ ) {

  // ndnq variable initialization
  var _ndnq = _ndnq || [],
    // Keep track of all video player previews that been initialized
    videoPlayerPreviewContainerIds = [];

  /**
   * Pushes each video container id to the array, tracking them for maniuplation later
   */
  (function() {
    _ndnq.push( ['hook', '*/widgetEmbedStart', function( event ) {
      videoPlayerPreviewContainerIds.push( event.containerElement.id );
    }] );
  })();

  $( document ).ready( function() {
    // Convert all search results descriptions and titles <em> raw text tags to actual tags
    replaceEmphases( '.ndn-search-video-title' );
    replaceEmphases( '.ndn-search-description' );
  });

  /**
   * Callbacks hooked upon initialization of the page
   */
  $(function() {
    // preview video callbacks
    $( '.ndn-search-screenshot' ).on( 'click', ndnPreviewVideo );
    $( '.ndn-search-attributes header span' ).on( 'click', ndnPreviewVideo );
    $( '.ndn-video-settings' ).on( 'click', ndnPreviewVideo );
    $( '.ndn-configuration-exit button' ).on( 'click', ndnClosePreviewVideo );

    // on responsive change
    $( '.ndn-responsive-checkbox' ).on( 'change', ndnChangedResponsiveCheckbox );

    // on featured image change
    $( '.ndn-featured-image-checkbox' ).on( 'change', ndnChangedFeaturedImageCheckbox );

    // on width change
    $( '.ndn-video-width' ).on( 'input', ndnChangeVideoWidth );

    // select video callback
    $( '.ndn-search-insert-video' ).on( 'submit', ndnSelectVideo );

    // Google Analytics
    $( 'form[name="ndn-search"]' ).on('submit', ndnGASubmitEvent);
    $( '.ndn-responsive-checkbox' ).on('change', ndnGAChangeConfiguration);
    $( '.ndn-video-width' ).on('change', ndnGAChangeConfiguration);
    $( '.ndn-video-start-behavior' ).on('change', ndnGAChangeConfiguration);
    $( '.ndn-video-position' ).on('change', ndnGAChangeConfiguration);
  });

  /**
   * Selects each element and replaces the text with <em> placed manually back in
   * @param  {string} selector Group of text blocks needing reselection of emphases
   */
  function replaceEmphases(selector) {
    $( selector ).each(function() {
      var that = $( this );
      var regex = /(.*)<em>(.+)<\/em>(.*)/;
      var content = $( that ).text().replace( regex, '$1<em>$2</em>$3' );
      $( that ).html( content );
      $( that ).css({
        'display': 'inherit'
      });
    });
  }

  /**
   * Toggle video preview panel and pause all other videos that might be playing, but are hidden
   * @param self  element of the search result item div container
   */
  function togglePreviewPanel(self) {
    /**
     * Function for pausing all video player previews
     */
    function ndnPauseAllVideos() {
      var i;
      for ( i = 0; i < videoPlayerPreviewContainerIds.length; i++ ) {
        var containerId = videoPlayerPreviewContainerIds[i];

        _ndnq.push( ['command', containerId + '/videoPause'] );
      }
    }

    // Pause all other video player previews
    ndnPauseAllVideos();

    // grab video-id class
    var id = $( self ).attr( 'video-id' );

    // Toggle the video preview panel on/off
    $( '.ndn_embed[data-config-video-id="' + id + '"]' ).parent().parent().toggle();
  }

  /**
   * Toggle the preview video and configuration settings on/off
   * @return Pauses all videos, toggles the video preview on/off, grabs height of video
   */
  function ndnPreviewVideo() {
    var self = this;

    // Google Analytics
    ndnGAClickEvent( $(self) );

    /**
     * Initialize the height text in the span for initial view
     */
    function initializeHeightText() {
      // Get the width to determine the height of the video
      var width = $( self ).closest( '.ndn-search' ).find( '.ndn-video-width' ).val(),
        height = ndnCalculateHeight( width );

      $( self ).closest( '.ndn-search' ).find( '.video-width-display' ).text( width );
      // Find the video calculated height of this toggled preview video
      $( self ).closest( '.ndn-search' ).find( '.video-calculated-height' ).text( height );
    }

    /**
     * Initial responsive checkbox check for disabling other fields
     * TODO refactor
     * @return [type] [description]
     */
    function checkInitialResponsiveCheckbox() {
      if ($( '.ndn-responsive-checkbox' ).is( ':checked' )) {
        $( 'input[name=ndn-video-width]' ).prop( 'disabled', true );
        $( '.ndn-manual-sizing' ).hide();
        $( '.ndn-video-width-disabled' ).prop( 'disabled', false );
        $( '.ndn-responsive-checkbox-disabled' ).prop( 'disabled', true );
      } else {
        $( 'input[name=ndn-video-width]' ).prop( 'disabled', false);
        $( '.ndn-video-width-disabled' ).prop( 'disabled', true);
        $( '.ndn-responsive-checkbox-disabled' ).prop( 'disabled', false);
      }
    }

    /**
     * Initial featured image checkbox check
     * TODO refactor
     */
    function checkInitialFeaturedImageCheckbox() {
      if ( $( '.ndn-featured-image-checkbox' ).is( ':checked' ) ){
        $( '.ndn-featured-image-checkbox-disabled' ).prop( 'disabled', true);
      } else {
        $( '.ndn-featured-image-checkbox-disabled' ).prop( 'disabled', false);
      }
    }

    togglePreviewPanel(self);
    initializeHeightText();
    checkInitialResponsiveCheckbox();
    checkInitialFeaturedImageCheckbox();
  }

  /**
   * Close video preview
   * @return Toggles preview panel given element attribute video-id
   */
  function ndnClosePreviewVideo() {
    var self = this;
    togglePreviewPanel(self);
  }

  /**
   * On change responsive checkbox
   * @return jQuery change attributes
   */
  function ndnChangedResponsiveCheckbox() {
    if ( this.checked ) {
      $( 'input[name=ndn-video-width]' ).prop( 'disabled', true);
      $( '.ndn-video-width-disabled' ).prop( 'disabled', false);
      $( '.ndn-responsive-checkbox-disabled' ).prop( 'disabled', true);
    } else {
      $( 'input[name=ndn-video-width]' ).prop( 'disabled', false);
      $( '.ndn-video-width-disabled' ).prop( 'disabled', true);
      $( '.ndn-responsive-checkbox-disabled' ).prop( 'disabled', false);
    }
    $( '.ndn-manual-sizing' ).toggle();
  }

  /**
   * On change featured image checkbox
   * @return jQuery change attributes
   */
  function ndnChangedFeaturedImageCheckbox() {
    if ( this.checked ) {
      $( '.ndn-featured-image-checkbox-disabled' ).prop( 'disabled', true);
    } else {
      $( '.ndn-featured-image-checkbox-disabled' ).prop( 'disabled', false);
    }
  }

  /**
   * Calculates the height of the video player
   * @param string  Width of the video player, given by the input
   * @return int    Height of the video player
   */
  function ndnCalculateHeight(width) {
    return Math.round((9 * parseInt(width, 10)) / 16);
  }

  /**
   * Append new width & height to span
   */
  function ndnChangeVideoWidth() {
    var self = this,
      newWidth = $(this).val(),
      newHeight = ndnCalculateHeight(newWidth);

    // Change the new width value on the span element
    $( self ).closest( '.ndn-search' ).find( '.video-width-display' ).text( newWidth );
    // Change the new height value on the span element
    $( self ).closest( '.ndn-search' ).find( '.video-calculated-height' ).text( newHeight );
  }

  /**
   * Attach a link click event listener for GA
   * @param  {element} element (optional) element object
   */
  function ndnGAClickEvent( element ) {
    var link,
      category,
      label;

    /*jshint validthis:true */
    link = !!element ? element : $( this );

    category = link.attr( 'analytics-category' );
    label = link.attr( 'analytics-label' );

    ga('send', 'event', category, 'click', label);
  }

  /**
   * Attach a form submit event listener for GA
   */
  function ndnGASubmitEvent() {
    var jqForm,
      category,
      label,
      value;
    /*jshint validthis:true */
    jqForm = $( this );
    category = jqForm.attr( 'analytics-category' );
    label = jqForm.attr( 'analytics-label' );
    value = jqForm.attr( 'analytics-value' );

    if (value) {
      ga('send', 'event', category, 'submit', label, value);
    } else {
      ga('send', 'event', category, 'submit', label);
    }
  }

  /**
   * Attach a change configuration event listener for GA
   */
  function ndnGAChangeConfiguration() {
    var setting,
      category,
      label;

    setting = $( this );
    category = setting.attr( 'analytics-category' );
    label = setting.attr( 'analytics-label' );

    ga('send', 'event', category, 'changeConfiguration', label);
  }

  /**
   * Selects the video from the search results
   */
  function ndnSelectVideo(e) {

    // Prevent default
    if (e.preventDefault) {
      e.preventDefault();
    }

    // Google Analytics
    ndnGAClickEvent( $(this) );

    /**
     *	Parses HTML and strips tags
     * @param string string HTML string
     */
    function parseHTML(string) {
      var html = string,
        div = document.createElement( 'div' );

      div.innerHTML = html;
      return div.textContent || div.innerText || '';
    }

    var media = self.parent.wp.media,
      values = {};

    // Find each hidden input that contains video metadata and include it in the values object
    $.each($( this ).find( 'input' ).serializeArray(), function(i, field) {
        values[field.name] = field.value;
    });

    // Include all form data from custom configurations
    values['ndn-responsive'] = $( this ).closest( '.ndn-search' ).find( '.ndn-responsive-checkbox' ).is( ':checked' ) ? 'true' : '' ;
    values['ndn-featured-image'] = $( this ).closest( '.ndn-search' ).find( '.ndn-featured-image-checkbox' ).is( ':checked' ) ? 'true' : '' ;
    values['ndn-video-width'] = $( this ).closest( '.ndn-search' ).find( '.ndn-video-width' ).val();
    values['ndn-video-height'] = ndnCalculateHeight(values['ndn-video-width']);
    values['ndn-video-start-behavior'] = $( this ).closest( '.ndn-search' ).find( '.ndn-video-start-behavior' ).val();
    values['ndn-video-position'] = $( this ).closest( '.ndn-search' ).find( '.ndn-video-position' ).val();

    // Check for config widget id (video player type)
    values['ndn-config-widget-id'] = values['ndn-video-start-behavior'] ? values['ndn-video-start-behavior'] : '1';

    // Close thickbox and do not allow the user to insert video if there is no tracking group
    if (!values['ndn-tracking-group']) {
      self.parent.tb_remove();
      throw new Error( 'Inform Tracking Group needs to be entered' );
    }

    // Take unnecessary HTML tags from the description
    values['ndn-video-description'] = parseHTML(values['ndn-video-description']);

    var buildHtml = $( '<img />' )
      .addClass( 'ndn-image-embed' )
      .css({
        'max-width': values['ndn-video-width'] + 'px',
        'max-height': values['ndn-video-height'] + 'px',
        'height': 'auto'
      })
      .attr({
        'src': values['ndn-video-thumbnail'],
        'alt': values['ndn-video-description'],
        'ndn-config-video-id': values['ndn-video-id'],
        'ndn-video-element-class': values['ndn-video-element-class'],
        'ndn-config-widget-id': values['ndn-config-widget-id'],
        'ndn-tracking-group': values['ndn-tracking-group'],
        'ndn-site-section-id': values['ndn-site-section-id'],
        'ndn-responsive':  values['ndn-responsive'],
        'ndn-video-width': values['ndn-video-width'],
        'ndn-video-height': values['ndn-video-height']
      });

    var html = buildHtml.prop('outerHTML');

    /**
     * Sets featured image from the video selected
     */
    function setFeaturedImage() {
      // Set featured image
      var eventPayload = {
        'detail': {
          src: values['ndn-video-thumbnail'],
          alt: values['ndn-video-description']
        }
      };
      var videoSelected = new CustomEvent( 'videoSelected', eventPayload );
      self.parent.dispatchEvent( videoSelected );
    }

    /**
     * Inserts select image to the visual editor
     * @return {[type]} [description]
     */
    function insertSelectedImage() {
      // if set featured image selected, set the featured image
      if ( values['ndn-featured-image'] ) {
        setFeaturedImage();
      }
      // Insert Image placeholder
      media.editor.insert(html);
      // Issue with inserting media. Fixing with adding a space.
      media.editor.insert( ' ' );
      // Remove thickbox
      self.parent.tb_remove();
    }

    // Wait for the image to load
    var img = new Image();
    img.onload = function() { insertSelectedImage(); }
    img.src = values['ndn-video-thumbnail'];
  }

})( jQuery );
