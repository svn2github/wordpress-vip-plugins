/*global tb_show */
(function ( $ ) {
    $( function () {

      // This file is used for validation of user inputs on Google Analytics Fields.
      var user_tracking_status =
        jQuery('input[name="laterpay_user_tracking_data[laterpay_ga_personal_enabled_status]"]');
      var user_ua_id           = jQuery('input[name="laterpay_user_tracking_data[laterpay_ga_personal_ua_id]"]');

      var laterpay_tracking_status = jQuery('input[name="laterpay_tracking_data[laterpay_ga_enabled_status]"]');
      var laterpay_ua_id           = jQuery('input[name="laterpay_tracking_data[laterpay_ga_ua_id]"]');

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
      });

      // Validate Google Tracking ID.
      function validateGAId(id) {
        return /^(UA|YT|MO)-\d+-\d+$/i.test(id);
      }

    } );
})( jQuery );
