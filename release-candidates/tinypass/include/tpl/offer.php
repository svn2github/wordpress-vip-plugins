<?php
/**
 * @var string $content
 * @var TinypassContentSettings $contentSettings
 */
?>
<?php echo wp_kses_post($content) ?>
<div id="<?php echo esc_attr( $contentSettings->getHTMLContainerId() ) ?>"></div>