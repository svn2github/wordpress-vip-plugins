/*global tb_show, lpGlobal */
(function ( $ ) {
    $( function () {

      // This file is used for validation of user inputs on Google Analytics Fields and send GA events.
      var user_tracking_status =
        jQuery('input[name="laterpay_user_tracking_data[laterpay_ga_personal_enabled_status]"]');
      var user_ua_id           = jQuery('input[name="laterpay_user_tracking_data[laterpay_ga_personal_ua_id]"]');

      var laterpay_tracking_status = jQuery('input[name="laterpay_tracking_data[laterpay_ga_enabled_status]"]');
      var laterpay_ua_id           = jQuery('input[name="laterpay_tracking_data[laterpay_ga_ua_id]"]');

      // Store value of each setting to be sent to GA.
      var lp_main_color  = jQuery('input[name="laterpay_main_color"]').val();
      var lp_hover_color = jQuery('input[name="laterpay_hover_color"]').val();
      var lp_teaser_content_word_count = jQuery('input[name="laterpay_teaser_content_word_count"]').val();
      var lp_percentage_of_content = jQuery('input[name="laterpay_preview_excerpt_percentage_of_content"]').val();
      var lp_preview_excerpt_word_count_min = jQuery('input[name="laterpay_preview_excerpt_word_count_min"]').val();
      var lp_excerpt_word_count_max = jQuery('input[name="laterpay_preview_excerpt_word_count_max"]').val();
      var lp_caching_enabled = jQuery('input[name="laterpay_caching_compatibility"]').prop('checked') ? 1 : 0;
      var lp_require_login = jQuery('input[name="laterpay_require_login"]').prop('checked') ? 1 :  0;
      var lp_api_enabled_homepage = jQuery('input[name="laterpay_api_enabled_on_homepage"]').prop('checked') ? 1 : 0;
      var lp_ga_enabled  = laterpay_tracking_status.prop('checked') ? 1 : 0;
      var user_ga_enabled = user_tracking_status.prop('checked') ? 1 : 0;
      var lp_fallback_behaviour = jQuery('#lp_js_laterpayApiFallbackSelect :selected').text();

      var modalDisableTracking = jQuery( 'button.lp_js_disableTracking' );
      var modalClose           = jQuery( 'button.lp_js_ga_cancel' );

      var settingSave = jQuery('input[name="submit"]');

      var messageSpan = jQuery('<span/>', {
        id    : 'lp_ga_msg_span',
        class : 'lp_ga_span',
      });

      // Validation For Personal Google Analytics Setting.
      user_tracking_status.on('click', function() {
          if ( true === user_tracking_status.prop('checked') ) {
            if ( user_ua_id.val() === '' ) {
              user_tracking_status.removeProp( 'checked' );
              messageSpan.text( lpVars.i18n.alertEmptyCode );
              user_ua_id.after(messageSpan);
              return true;
            }
            if ( false === validateGAId(user_ua_id.val()) ) {
              user_tracking_status.removeProp( 'checked' );
              messageSpan.text( lpVars.i18n.invalidCode );
              user_ua_id.after(messageSpan);
              return true;
            }
            user_tracking_status.attr( 'checked', 'checked' );
            user_tracking_status.val( 1 );
            return true;
          }
      });

      user_ua_id.on('keyup', function() {
        if (user_ua_id.val() === '') {
          user_tracking_status.removeProp('checked');
        } else {
          messageSpan.remove();
        }

      });

      user_ua_id.focusout(function() {
        if ( false === validateGAId(user_ua_id.val()) ) {
          messageSpan.text( lpVars.i18n.invalidCode );
          user_ua_id.after(messageSpan);
        }
      });

      // Validation For LaterPay Google Analytics Setting.
      laterpay_tracking_status.on('click', function() {
        if ( true === laterpay_tracking_status.prop('checked') ) {
          if ( laterpay_ua_id.val() === '' ) {
            laterpay_tracking_status.removeProp( 'checked' );
            messageSpan.text( lpVars.i18n.alertEmptyCode );
            laterpay_ua_id.after(messageSpan);
            return true;
          }
          if ( false === validateGAId(laterpay_ua_id.val()) ) {
            laterpay_tracking_status.removeProp( 'checked' );
            messageSpan.text( lpVars.i18n.invalidCode );
            laterpay_ua_id.after(messageSpan);
            return true;
          }
          laterpay_tracking_status.attr( 'checked', 'checked' );
          laterpay_tracking_status.val( 1 );
          return true;
        }

        if ( false === laterpay_tracking_status.prop('checked') ) {
          laterpay_tracking_status.attr( 'checked', 'checked' );
          if ( typeof tb_show === 'function' ) {
              tb_show( lpVars.modal.title, '#TB_inline?inlineId=' + lpVars.modal.id + '&height=185&width=375');
          }
        }

      });

      // Modal Events.
      modalClose.click(function(){
        laterpay_tracking_status.attr( 'checked', 'checked' );
        laterpay_tracking_status.val( 1 );
        jQuery('#TB_closeWindowButton').click();
      });

      modalDisableTracking.click(function(){
        laterpay_tracking_status.removeProp( 'checked' );
        laterpay_tracking_status.val( 1 );
        jQuery('#TB_closeWindowButton').click();
      });

      // Validation Before Saving Settings.
      settingSave.click(function(){
        if ( user_ua_id.val() === '' || false === validateGAId(user_ua_id.val()) ) {
          user_tracking_status.removeProp( 'checked' );
          user_ua_id.val('');
        }

        var commonLabel = lpVars.gaData.sandbox_merchant_id + ' | ';
        var eveCategory = 'LP WP Settings';
        var eveAction = 'Modify LaterPay Settings';

        // Send GA Events for Settings.
        lpGlobal.sendLPGAEvent( 'Main color', eveCategory, commonLabel + lp_main_color );
        lpGlobal.sendLPGAEvent( 'Hover color', eveCategory, commonLabel + lp_hover_color );
        lpGlobal.sendLPGAEvent( eveAction, eveCategory, commonLabel + 'Merchant GA Enabled', user_ga_enabled );
        lpGlobal.sendLPGAEvent( eveAction, eveCategory, commonLabel + 'LaterPay GA Enabled', lp_ga_enabled );
        lpGlobal.sendLPGAEvent( eveAction, eveCategory, commonLabel + 'Unlimited Access', getEnabledRoles() );
        lpGlobal.sendLPGAEvent( eveAction, eveCategory, commonLabel + 'Require LaterPay Login', lp_require_login );
        lpGlobal.sendLPGAEvent( eveAction, eveCategory, commonLabel + 'Caching Compatibility Mode On',
            lp_caching_enabled );
        lpGlobal.sendLPGAEvent( eveAction, eveCategory, commonLabel + 'Teaser Default Word Count',
            lp_teaser_content_word_count );
        lpGlobal.sendLPGAEvent( eveAction, eveCategory, commonLabel + 'Percentage of Post Content',
            lp_percentage_of_content );
        lpGlobal.sendLPGAEvent( eveAction, eveCategory, commonLabel + 'Minimum Number of Words',
            lp_preview_excerpt_word_count_min );
        lpGlobal.sendLPGAEvent( eveAction, eveCategory, commonLabel + 'Maximum Number of Words',
            lp_excerpt_word_count_max );
        lpGlobal.sendLPGAEvent( eveAction, eveCategory, commonLabel + 'LaterPay API | ' + lp_fallback_behaviour,
            lp_api_enabled_homepage );

      });

      // Validate Google Tracking ID.
      function validateGAId(id) {
        return /^(UA|YT|MO)-\d+-\d+$/i.test(id);
      }

      // Return count of enabled roles with unlimited access.
      function getEnabledRoles () {

          var countRoles = 0;
          var getOutOfLoop = false;

          jQuery.each( lpVars.gaData.custom_roles, function( i ) {

              var roleBox = jQuery('input[name="laterpay_unlimited_access[' + lpVars.gaData.custom_roles[i] + '][]"]');

              jQuery.each(roleBox, function(j){
                  if ( roleBox[j].checked && 'none' !== roleBox[j].value ) {
                      getOutOfLoop = true;
                      return;
                  }
              });

              if ( getOutOfLoop === true ) {
                  countRoles++;
                  getOutOfLoop = false;
                  return;
              }

          });

          return countRoles;
      }

    } );
})( jQuery );
