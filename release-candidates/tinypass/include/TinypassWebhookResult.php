<?php

/**
 * Class TinypassWebhookResult
 *
 * Helper class representing the result of webhook procession with information of further actions
 *
 * @method TinypassWebhookResult|mixed action() action( $action = null )
 * @method string actionPropertyName() actionPropertyName()
 * @method TinypassWebhookResult|mixed id() id( $id = null )
 * @method string idPropertyName() idPropertyName()
 * @method TinypassWebhookResult|mixed key() key( $key = null )
 * @method string keyPropertyName() keyPropertyName()
 */
class TinypassWebhookResult extends TinypassBuildable {
	const ACTION_UPDATE_CONTENT_KEY = 'content_key';

	protected $action;
	protected $id;
	protected $key;

}