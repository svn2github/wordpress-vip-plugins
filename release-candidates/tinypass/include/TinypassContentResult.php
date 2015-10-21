<?php

/**
 * Class TinypassContentResult
 *
 * Helper class which contains result of checking for content access
 *
 * @method TinypassContentResult|mixed content() content( $content = null )
 * @method string contentPropertyName() contentPropertyName()
 * @method TinypassContentResult|mixed javascript() javascript( $javascript = null )
 * @method string javascriptPropertyName() javascriptPropertyName()
 * @method TinypassContentResult|mixed isKeyed() isKeyed( $isKeyed = null )
 * @method string isKeyedPropertyName() isKeyedPropertyName()
 */
class TinypassContentResult extends TinypassBuildable {

	protected $content;
	protected $javascript;
	protected $isKeyed;


}