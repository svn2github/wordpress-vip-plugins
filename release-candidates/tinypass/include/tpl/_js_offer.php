<?php
/**
 * @var $offerId
 * @var $termId
 * @var $templateId
 * @var $termIds
 * @var TinypassContentSettings $contentSettings
 * Rendered by @see TinypassInternal
 * @var TinypassInternal $this
 */
?>
tp = window['tp'] || [];
if ( 'function' != typeof tinypassOffer<?php echo esc_js( $contentSettings->id() ) ?> ) {
	tinypassOffer<?php echo esc_js( $contentSettings->id() ) ?> = function () {
		tp.push( [
			'init', function () {
				tp.offer.show( {
					<?php if ( $this->displayMode() == $this::DISPLAY_MODE_MODAL ): ?>
					displayMode: 'modal',
					<?php else: ?>
					displayMode: 'inline',
					containerSelector: '#' + <?php echo json_encode( $contentSettings->getHTMLContainerId() ) ?>,
					<?php endif ?>
					<?php if ( $templateId ): ?>templateId: <?php echo json_encode( $templateId ) ?>, <?php endif ?>
					offerId: <?php echo json_encode( $offerId ) ?>,
					<?php if ( $termId ): ?>termId: <?php echo json_encode( $termId ) ?>, <?php endif ?>
					<?php if ( $termIds ): ?>termIds: <?php echo json_encode( $termIds ) ?>, <?php endif ?>
					close: function () {
						var resourceIds = <?php echo json_encode( $contentSettings->getFilteredResourceIds() ) ?>;
						for ( var i in resourceIds ) {
							var rid = resourceIds[i];
							tp.api.callApi( '/access/check', {rid: rid}, function ( data ) {
								if ( ( typeof data.code != "undefined" ) && ( data.code == 0 ) && ( data.access.granted ) ) {
									window.location.reload();
									return;
								}
							} );
						}
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
				} );
			}
		] );
	};
	tinypassOffer<?php echo esc_js( $contentSettings->id() ) ?>();
}