<?php
/**
 * @var $conatinerId
 */
?>
<div id="<?php echo esc_attr( $containerId ) ?>"
     class="<?php echo esc_attr( WPTinypass::WP_SHORTCODE_MY_ACCOUNT ) ?>"></div>
<script type="text/javascript">
	tp = window["tp"] || [];
	tp.push(["init", function () {
		tp.myaccount.show({
			containerSelector: '#' + <?php json_encode($containerId) ?>
		});
	}]);
</script>