<?php
	$scout    = get_option( 'sailthru_scout_options' );
	$sailthru = get_option( 'sailthru_setup_options' );

	// check if this is an SPM widget or a Scout Widget
if ( sailthru_spm_ready() ) {
	$use_spm = true;
	$section = empty( $instance['sailthru_spm_section'] ) ? ' ' :  $instance['sailthru_spm_section'];
} else {
	$use_spm = false;
	$title   = empty( $instance['title'] ) ? ' ' : apply_filters( 'widget_title', $instance['title'] );
}

	/** This filter is documented in class-sailthru-scout.php */
if ( ! isset( $scout['sailthru_scout_is_on'] ) || ! $scout['sailthru_scout_is_on'] || ! apply_filters( 'sailthru_scout_on', true ) ) {
	// do nothing, get outta here
	return;
}
	// Grab the settings from $instance and fill out default values as needed.
	$title = empty( $instance['title'] ) ? ' ' : apply_filters( 'widget_title', $instance['title'] );

?>
<?php if ( $use_spm ) : ?>
	<div class="sailthru-spm-widget">
		 <div id="<?php echo esc_attr( $this->id ); ?>-container"></div>
		  <script type="text/javascript">
              window.addEventListener('load', function() {
                  Sailthru.personalize({
                      sections: [
                          {
                              id: "<?php echo esc_js( $section );?>",
                              selector: "#<?php echo esc_js( $this->id );?>-container",
                          }]
                  });
		 });
	</script>

	</div>
 <div class="sailthru-recommends-widget">
	</div>
<?php else : ?>
	<?php
	// title
	if ( ! empty( $title ) ) {
		if ( ! isset( $before_title ) ) {
			$before_title = '';
		}
		if ( ! isset( $after_title ) ) {
			$after_title = '';
		}
		echo $before_title . wp_kses_post( trim( $title ) ). $after_title;
	}
	?>

    <div id="sailthru-scout"><div class="loading"></div></div>
<?php endif; ?>
