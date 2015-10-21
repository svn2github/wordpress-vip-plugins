<?php
/**
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
if ( typeof tinypassMeterTick != 'function' ) {
	tinypassMeterTick = function () {
		tp.push( [
			'init', function () {
				tp.meter.init( {
					paywallID: <?php echo json_encode( $this->paywallId() ) ?>,
					trackPageview: true,
					onMeterExpired: function ( meter ) {
						<?php if ( WPTinypass::canDebug() ): ?>
						tinypassDebugMeter( meter );
						<?php endif ?>
					},
					<?php if ( WPTinypass::canDebug() ): ?>
					onMeterActive: function ( meter ) {
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
	tinypassMeterTick();
}