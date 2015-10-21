<?php
/**
 * @var TinypassContentSettings $contentSettings
 * @var string $truncatedContent
 * @var TinypassInternal $this
 */
?>
tp = window['tp'] || [];
<?php if ( WPTinypass::canDebug() ): ?>
if ( typeof tinypassDebugMeter != 'function' ) {
	tinypassDebugMeter = function ( meter ) {
		jQuery( function ( $ ) {
			$( '#tp-debugger-paywall_max_views' ).text( '' ).append( meter.max_views );
			$( '#tp-debugger-paywall_views_left' ).text( '' ).append( meter.views_left );
			$( '#tp-debugger-paywall_state' ).text( '' ).append( meter.views_left );
			switch ( meter.state ) {
				case <?php echo json_encode( $this::PAYWALL_STATE_ACCESS_GRANTED ) ?>:
					$( '#tp-debugger-paywall_state' ).text( '' ).append( <?php echo json_encode( esc_html__( 'Access granted', 'tinypass' ) ) ?> );
					break;
				case <?php echo json_encode( $this::PAYWALL_STATE_METER_ACTIVE ) ?>:
					$( '#tp-debugger-paywall_state' ).text( '' ).append( <?php echo json_encode( esc_html__( 'Active', 'tinypass' ) ) ?> );
					break;
				case <?php echo json_encode( $this::PAYWALL_STATE_EXPIRED ) ?>:
					$( '#tp-debugger-paywall_state' ).text( '' ).append( <?php echo json_encode( esc_html__( 'Expired', 'tinypass' ) ) ?> );
					break;
			}
		} );
	}
}
<?php endif ?>
if ( 'function' != typeof tinypassMeter<?php echo esc_js( $contentSettings->id() ) ?> ) {
	tinypassMeter<?php echo esc_js( $contentSettings->id() ) ?> = function () {
		tp.push( [
			'init', function () {
				tp.meter.init( {
					paywallID: <?php echo json_encode( $this->paywallId() ) ?>,
					trackPageview: true,
					<?php if ( $this->displayMode() == $this::DISPLAY_MODE_MODAL ): ?>
					displayMode: 'modal',
					<?php else: ?>
					displayMode: 'inline',
					containerSelector: '#' + <?php echo json_encode( $contentSettings->getHTMLContainerId() ) ?> + '-meter',
					<?php endif ?>
					onMeterExpired: function ( meter ) {
						<?php if ( WPTinypass::canDebug() ): ?>
						tinypassDebugMeter( meter );
						<?php endif ?>
						// Hide content
						var selector = '#' + <?php echo json_encode( $contentSettings->getHTMLContainerId() ) ?>;
						jQuery( function ( $ ) {
							$( '#' + <?php echo json_encode( $contentSettings->getHTMLContainerId() ) ?> ).text( '' ).append( <?php echo json_encode( wp_kses_post( $truncatedContent ) ) ?> );
						} );
					},
					close: function () {
						tp.api.callApi( '/access/check', {rid: <?php echo json_encode( $this::PAYWALL_RESOURCE_PREFIX . $this->paywallId() ) ?> }, function ( data ) {
							if ( ( typeof data.code != "undefined" ) && ( data.code == 0 ) && ( data.access.granted ) ) {
								window.location.reload();
								return;
							}
						} );
					}
					<?php if ( $this->nativeUsersAvailable() ): ?>
					, customEvent: function ( event ) {
						switch ( event.eventName ) {
							case 'login':
								window.location.href = <?php echo json_encode( esc_url( wp_login_url( get_permalink() ) ) ) ?>;
								break;
						}
					},
					loginRequired: function ( event ) {
						window.location.href = <?php echo json_encode( esc_url( wp_login_url( get_permalink() ) ) ) ?>;
						return false;
					}
					<?php endif ?>
					<?php if ( WPTinypass::canDebug() ): ?>
					, onMeterActive: function ( meter ) {
						tinypassDebugMeter( meter );
					},
					onAccessGranted: function ( meter ) {
						tinypassDebugMeter( meter );
					}
					<?php endif ?>
				} );
			}
		] );
	}
	tinypassMeter<?php echo esc_js( $contentSettings->id() ) ?>();
}