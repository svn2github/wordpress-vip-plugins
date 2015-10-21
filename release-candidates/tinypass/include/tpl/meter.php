<?php
/**
 * @var string $content
 * @var TinypassContentSettings $contentSettings
 */
?>
<div id="<?php echo esc_attr( $contentSettings->getHTMLContainerId() ) ?>"><?php echo wp_kses_post( $content ) ?></div>
<div id="<?php echo esc_attr( $contentSettings->getHTMLContainerId() ) ?>-meter"></div>