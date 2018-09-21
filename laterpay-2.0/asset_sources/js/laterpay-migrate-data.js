/*global lp_i18n, migration_nonce */

(function ( $ ) {
	$( function () {

		var startMigrationButton = jQuery( '#lp_js_startDataMigration' );
		var migrationNoticeBox = jQuery( '#lp_migration_notice' );

		startMigrationButton.on( 'click', function () {

			migrationNoticeBox.removeClass( 'notice-error' ).addClass( 'notice-info' );

			migrationNoticeBox.html( '' ).append( lp_i18n.MigratingData );
			$( '<img />' )
				.attr( 'id', 'migration-loader' )
				.attr( 'src', '/wp-admin/images/loading.gif' )
				.appendTo( migrationNoticeBox );
			$( '<br />' ).appendTo( migrationNoticeBox );
			$( '<br />' ).appendTo( migrationNoticeBox );
			migrationNoticeBox.append( lp_i18n.MigratingSubscriptions );

			migrateIfNeeded( 'subscription', 0 );

		} );

        function migrateIfNeeded( migrate, offset ) {

            var data = {
                action: 'laterpay_start_migration',
                security: migration_nonce,
                migrate: migrate,
                offset: offset
            };

	        $.post( ajaxurl, data, function ( response ) {

		        if ( $.type( response ) === 'string' ) {
			        response = JSON.parse( response );
		        }

		        if ( 'subscription_migrated' in response && response.subscription_migrated !== true ) {
			        migrateIfNeeded( 'subscription', response.offset );
		        } else if ( 'subscription_migrated' in response && response.subscription_migrated === true ) {

			        $( '<span />' ).addClass( 'dashicons dashicons-yes' ).appendTo( migrationNoticeBox );
			        $( '<br />' ).appendTo( migrationNoticeBox );
			        migrationNoticeBox.append( lp_i18n.MigratingTimepasses );

			        migrateIfNeeded( 'time_pass', response.offset );

		        } else if ( 'time_pass_migrated' in response && response.time_pass_migrated !== true ) {

			        migrateIfNeeded( 'time_pass', response.offset );

		        } else if ( 'time_pass_migrated' in response && response.time_pass_migrated === true ) {

			        $( '<span />' ).addClass( 'dashicons dashicons-yes' ).appendTo( migrationNoticeBox );
			        $( '<br />' ).appendTo( migrationNoticeBox );
			        migrationNoticeBox.append( lp_i18n.MigratingCategoryPrices );

			        migrateIfNeeded( 'category_price', response.offset );

		        } else if ( 'category_price_migrated' in response && response.category_price_migrated !== true ) {

			        migrateIfNeeded( 'category_price', response.offset );

		        } else if ( 'category_price_migrated' in response && response.category_price_migrated === true ) {

			        $( '<span />' ).addClass( 'dashicons dashicons-yes' ).appendTo( migrationNoticeBox );
			        $( '<br />' ).appendTo( migrationNoticeBox );
			        $( '<br />' ).appendTo( migrationNoticeBox );
			        migrationNoticeBox.append( lp_i18n.MigrationCompleted );

			        $( '<button/>' ).attr( 'type', 'button' )
				        .addClass( 'notice-dismiss' )
				        .appendTo( migrationNoticeBox );
			        $( '#migration-loader' ).remove();
			        migrationNoticeBox.removeClass( 'notice-info' ).addClass( 'notice-success' );

			        $( '.notice-dismiss' ).click( function () {
				        migrationNoticeBox.remove();
			        } );
                }
	        } );
        }
    } );
})( jQuery );
