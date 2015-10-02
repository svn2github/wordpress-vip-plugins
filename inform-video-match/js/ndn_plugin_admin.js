// Javascript for admin pages
( function($) {
  'use strict';

  // Media Wizard
  jQuery( document ).ready( function() {
    // Initialize Google Analytics
    initializeGA();

    // Event Listeners for plugin actions
    ndnChangedResponsiveCheckbox();
    jQuery( '.ndn-responsive-checkbox' ).change( ndnChangedResponsiveCheckbox );
    jQuery( '.ndn-featured-image-checkbox').change( ndnChangedFeaturedImageCheckbox );

    // Login Toggle
    jQuery( '.ndn-login-form-type' ).change( ndnChangeLoginForm );

    // Input Validators
    jQuery( '#ndn-plugin-first-time-login' ).submit( ndnValidateCompleteLoginForm );
    jQuery( 'input[name="ndn-plugin-default-tracking-group"]' ).keyup( ndnValidateInput );
    jQuery( 'input[name="ndn-plugin-default-width"]' ).keyup( ndnValidateInput );

    // Add Link Attributes
    jQuery( '.ndn-notify-credentials' ).attr('analytics-category', 'WPSettings');
    jQuery( '.ndn-notify-credentials' ).attr('analytics-label', 'SettingsPage');
    jQuery( '.ndn-notify-settings' ).attr('analytics-category', 'WPSettings');
    jQuery( '.ndn-notify-settings' ).attr('analytics-label', 'SettingsPage');

    // Google Analytics
    jQuery( '.ndn-email-help' ).on('click', ndnGAClickEvent);
    jQuery( 'form[name="ndn-plugin-login-form"]' ).on('submit', ndnGASubmitEvent);
    jQuery( 'form[name="ndn-plugin-default-settings-form"]' ).on('submit', ndnGASubmitEvent);
    jQuery( '.ndn-notify-credentials' ).on('click', ndnGAClickEvent);
    jQuery( '.ndn-notify-settings' ).on('click', ndnGAClickEvent);

    // Register functions
    window.addEventListener( 'videoSelected' , assignFeaturedImage, false );
  });

  /**
   * On change responsive checkbox
   */
  function ndnChangedResponsiveCheckbox() {
    if ($( '.ndn-responsive-checkbox' ).is( ':checked' )) {
      $( 'input[name=ndn-plugin-default-width]' ).prop( 'disabled', true );
      $( '.ndn-default-width-disabled' ).prop( 'disabled', false );
      $( '.ndn-responsive-checkbox-disabled' ).prop( 'disabled', true );
    } else {
      $( 'input[name=ndn-plugin-default-width]' ).prop( 'disabled', false );
      $( '.ndn-default-width-disabled' ).prop( 'disabled', true );
      $( '.ndn-responsive-checkbox-disabled' ).prop( 'disabled', false );
    }
  }

  /**
   * On change featured image checkbox
   */
  function ndnChangedFeaturedImageCheckbox() {
    if ($( '.ndn-featured-image-checkbox' ).is( ':checked' )) {
      $( '.ndn-featured-image-checkbox-disabled' ).prop( 'disabled', true );
    } else {
      $( '.ndn-featured-image-checkbox-disabled' ).prop( 'disabled', false );
    }
  }

  /**
   * Change between first time login and returning login
   */
  function ndnChangeLoginForm() {
    $( '#ndn-plugin-first-time-login' ).toggle();
    $( '#ndn-plugin-returning-login' ).toggle();
  }

  /**
   * Validate Completed Login Form
   * @return {bool} Returns boolean whether the form was completed or not
   */
  function ndnValidateCompleteLoginForm() {
    /**
     * Highlight the field that is not complete
     */
    function ndnHighlightIncompleteFields() {
      $('#ndn-plugin-first-time-login input').each(function() {
        if ( !$(this).val() ) {
          $(this).addClass( 'ndn-input-invalid' );
        } else {
          $( this ).removeClass( 'ndn-input-invalid' );
        }
      });
    }

    if ( $.trim( $( '#ndn-plugin-login-username' ).val() ) === '' || $.trim( $( '#ndn-plugin-login-password' ).val() ) === '' || $.trim( $( '#ndn-plugin-login-company-name' ).val() ) === '' || $.trim( $( '#ndn-plugin-login-contact-name' ).val() ) === '' || $.trim( $( '#ndn-plugin-login-contact-email' ).val() ) === '' ) {
      if ( event ) {
        event.preventDefault();
      }

      ndnHighlightIncompleteFields();
      return false;
    }
  }

  /**
   * Validates input with regular expressions
   */
  function ndnValidateInput() {
    /*jshint validthis:true */
    var self = this,
      name = $( self ).attr( 'name' ),
      invalidElement = $( self ).siblings( '.invalid-input' );

    validateTrackingGroup( name, invalidElement );
  }

  /**
   * Validates the tracking group input
   * @param  {string} inputName       Name of the input
   * @param  {element} invalidDiv   Element of the invalid message div class
   */
  function validateTrackingGroup( inputName, invalidDiv ) {
    var regex = new RegExp( /^[0-9]*$/ ),
      value = $( 'input[name="' + inputName + '"]' ).val();
    if (!regex.test( value )) {
      invalidDiv.show();
      $( '.submit-settings-form' ).attr( 'disabled','disabled' );
    } else {
      invalidDiv.hide();
      $( '.submit-settings-form' ).removeAttr( 'disabled' );
    }
  }

  /**
   * Intialize Google Analytics
   */
  function initializeGA() {
     /* jshint ignore:start */
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-65160109-1', 'auto');
    ga('send', 'pageview', 'wordpress-plugin');

     /* jshint ignore:end */
  }

  /**
   * Attach a link click event listener for GA
   * @param  {element} element (optional) element object
   */
  function ndnGAClickEvent( element ) {
    var link,
      href,
      target,
      category,
      label;

    /*jshint validthis:true */
    if (!this) {
      link = $( element );
    } else {
      link = $( this );
    }

    href = link.attr( 'href' );
		target = link.attr( 'target' );
    category = link.attr( 'analytics-category' );
    label = link.attr( 'analytics-label' );

    ga('send', 'event', category, 'click', label);
  }

  /**
   * Attach a form submit event listener for GA
   * @param  {object} event event object
   */
  function ndnGASubmitEvent() {
    var jqForm,
      category,
      label;
    /*jshint validthis:true */
    jqForm = $( this );
    category = jqForm.attr( 'analytics-category' );
    label = jqForm.attr( 'analytics-label' );

    ga('send', 'event', category, 'submit', label);
  }

  /**
   * assign featured image to Post
   * @param  {String} url link to the thumbnail
   */
  function assignFeaturedImage ( event ) {
    // Create data object
    var data = {
      action: 'set_featured_image',
      url: event.detail.src,
      description: event.detail.alt,
      postID: NDNAjax.postID
    };

    /**
     * After image has been assigned, replace div with HTML response from Server
     * @param  {string} response html response for replacing postimagediv
     */
    function onImageAssigned ( response ) {
      if ( response ) {
        if (jQuery( '#postimagediv .hide-if-no-js' ).length > 1 ) {
          jQuery( '#postimagediv .hide-if-no-js' ).remove();
          jQuery( '#postimagediv .inside').append( response );
        } else {
          jQuery( '#postimagediv .hide-if-no-js' ).replaceWith( response );
        }
      }
      // End Spinner
      tb_remove();
    }
    // Start Spinner
    tb_click();
    jQuery.post( NDNAjax.ajaxUrl, data, onImageAssigned );
  }

})( jQuery );
