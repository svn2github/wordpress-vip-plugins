<?php

/**
 * Search Results Modal for Admin Posts Page.
 */

?>

<link rel="stylesheet" href="<?php echo esc_url( NDN_PLUGIN_DIR . '/css/ndn_plugin_admin_search_results.css' ) ?>" type="text/css" />

<script type="text/javascript" async src="https://launch.newsinc.com/js/embed.js" id="_nw2e-js"></script>
<script src="<?php echo esc_url( NDN_PLUGIN_DIR . '/js/ndn_plugin_admin_search_results.js' ) ?>"></script>

<div class="ndn-search-results-container">
  <header class="ndn-search-tagline">
    <div class="ndn-search-query-history">
      <img src="<?php echo esc_url( NDN_PLUGIN_DIR . '/assets/informLogo_93x42.png' ) ?>" alt="ndn logo" /><br />
      <span>Search Results for:&nbsp;<strong><?php echo esc_html( self::$search_query ); ?></strong></span>
    </div>
    <div class="ndn-search-input">
      <form name="ndn-search" class="ndn-search-form" action="<?php echo esc_attr( 'admin.php' ) ?>" method="post" accept-charset="UTF-8" analytics-category="WPSearch" analytics-label="SearchAgain" novalidate>
        <label for="ndn-search-input"></label>
        <input class="ndn-search-query-input" name="query" type="text" placeholder="" />

        <input type="hidden" name="search-action" value="1" />
        <input class="button" name="submit" type="submit" value="<?php echo esc_attr( 'Search' ) ?>" />
      </form>
    </div>
    <div style="clear:both;"></div>
  </header>

  <?php

  /**
   * Rounds the timestamp by 1000
   * @param  int $timestamp time stamp ISO 8601
   * @return int            time stamp rounded by 1000
   */
  function round_timestamp($timestamp)
  {
      return round($timestamp / 1000);
  }

  // Loop through every video entry
  foreach ( self::$search_results as $key => $video ) {
    $update_date = date( 'M j, Y g:i A', round_timestamp($video->update_date));
    $create_date = date( 'M j, Y g:i A', round_timestamp($video->create_date));
    $publish_date = date( 'M j, Y g:i A', round_timestamp($video->publish_date));
    $duration = gmdate( 'i:s', $video->duration);

    ?>

    <article class="ndn-search">
      <section>
        <div class="ndn-search-image">
          <img class="ndn-search-screenshot" video-id="<?php echo esc_attr( $video->id ) ?>" src="<?php echo esc_attr( $video->thumbnail_small ) ?>" width="160" height="90" alt="<?php echo esc_attr( htmlspecialchars( $video->description ) ) ?>" analytics-category="WPPreview" analytics-label="PreviewImage" />
          <p><span><?php echo esc_html( $video->owner ) ?>&nbsp;&nbsp;|&nbsp;&nbsp;<?php echo esc_html( $duration ) ?></span></p>
        </div>
        <div class="ndn-search-attributes">
            <header><span class="ndn-search-video-title" style="display:none;" video-id="<?php echo esc_attr( $video->id ) ?>" analytics-category="WPPreview" analytics-label="PreviewTitle"><?php echo esc_html( $video->title ) ?></span></header>
            <div class="ndn-search-description" style="display:none;" ><span><?php echo esc_html( $video->description ) ?></span></div>
            <div class="ndn-search-date"><span class="ndn-search-publish-date"><?php echo esc_html( $publish_date ) ?></span></div>

            <div class="ndn-search-media-buttons">
              <form class="ndn-search-insert-video" name="ndn-search-insert-video" action="admin.php" method="post" accept-charset="UTF-8" novalidate analytics-category="WPEmbed" analytics-label="InsertVideo" analytics-value="<?php echo esc_attr($key + 1) ?>" style="margin-bottom: 0;vertical-align: top;">
                <input class="button ndn-insert-video-button" type="submit" name="ndn-insert-video-button" value="<?php echo esc_attr( 'Insert Video' ) ?>" />
                <input type="hidden" name="ndn-insert-video" value="1" />
                <input type="hidden" name="ndn-video-id" value="<?php echo esc_attr( $video->id ) ?>" />
                <input type="hidden" name="ndn-video-thumbnail" value="<?php echo esc_attr( $video->thumbnail ) ?>" />
                <input type="hidden" name="ndn-video-description" value="<?php echo esc_attr( htmlspecialchars($video ->description) ) ?>" />
                <input type="hidden" name="ndn-video-element-class" value="<?php echo esc_attr( get_option( ' ndn_default_div_class' ) ? get_option( 'ndn_default_div_class' ) : '' ) ?>" />
                <input type="hidden" name="ndn-tracking-group" value="<?php echo esc_attr( get_option( ' ndn_default_tracking_group' ) ? get_option( 'ndn_default_tracking_group' ) : '' ) ?>" />
                <input type="hidden" name="ndn-site-section-id" value="<?php echo esc_attr( get_option( ' ndn_default_site_section' ) ? get_option( 'ndn_default_site_section' ) : 'inform_wordpress_plugin' ) ?>" />
              </form>

              <input class="button ndn-video-settings" type="button" name="video-settings" value="<?php echo esc_attr( 'Configure Settings' ) ?>" video-id="<?php echo esc_attr( $video->id ) ?>" analytics-category="WPPreview" analytics-label="PreviewConfigure" />
            </div>
        </div>
      </section>
      <section class="ndn-video-preview" style="display: none;">
        <div class="ndn-configuration-exit">
          <button video-id=<?php echo esc_html( $video->id ) ?> >x</button>
        </div>
        <div style="clear: both;"></div>
        <div class="ndn-video-container" style="padding-right: 10px;width:468px;height:260px;">
          <div
            class="ndn_embed"
            data-config-width="100%"
            data-config-height="9/16w"
            data-config-widget-id="4"
            data-config-type="VideoPlayer/Single"
            data-config-tracking-group="<?php echo esc_attr( get_option( ' ndn_default_tracking_group' ) ? get_option( 'ndn_default_tracking_group' ) : '10557' ) ?>"
            data-config-video-id="<?php echo esc_attr( $video->id ) ?>"
            data-config-site-section="site_section_id"
            ></div>
        </div>
        <div class="ndn-video-insert-settings">
          <header class="ndn-video-configuration-header"><span><strong>Configuration Settings</strong></span></header>
          <form name="ndn-video-settings-form">
            <section class="ndn-video-settings-image">
              <fieldset>
                <label name="<?php echo esc_attr( self::$custom_form_options['ndn_featured_image'] ) ?>">
                  <input class="ndn-featured-image-checkbox" type="checkbox" class="ndn-featured-image" name="<?php echo esc_attr( self::$custom_form_options['ndn_featured_image'] )?>" value="1" <?php echo ( get_option( 'ndn_default_featured_image' ) == 1 ? esc_attr( 'checked' ) : '' ) ?> analytics-category="WPPreview" analytics-label="ConfigureChange" />
                  <input class="ndn-featured-image-checkbox-disabled" type='hidden' name='<?php echo esc_attr( self::$custom_form_options['ndn_featured_image'] ) ?>' value="not_checked">
                  <span>Set As Featured Image&nbsp;</span>
                </label>
              </fieldset>
            </section>

            <section class="ndn-video-settings-player">
              <label class="ndn-video-settings-responsive-label" name="<?php echo esc_attr( self::$custom_form_options['ndn_responsive'] ) ?>">
                <input class="ndn-responsive-checkbox" type="checkbox" name="<?php echo esc_attr( self::$custom_form_options['ndn_responsive'] ) ?>" value="1" <?php echo ( get_option( 'ndn_default_responsive' ) == '1' ? esc_attr( 'checked' ) : '' ) ?> analytics-category="WPPreview" analytics-label="ConfigureChange" />
                <input class="ndn-responsive-checkbox-disabled" type='hidden' name='<?php echo esc_attr( self::$custom_form_options['ndn_responsive'] ) ?>' value="not_checked">
                <span>Responsive Player&nbsp;</span>
              </label>
              <br />

              <div class="ndn-manual-sizing">
                <label class="ndn-video-settings-width-label" name="<?php echo esc_attr( self::$custom_form_options['ndn_video_width'] ) ?>">
                  <div>
                    <span>Width:</span>
                  </div>
                  <div>
                    <input class="ndn-video-width" name="<?php echo esc_attr( self::$custom_form_options['ndn_video_width'] ) ?>" type="number" min="300" max="640" value="<?php echo esc_attr( get_option( 'ndn_default_width' ) ? get_option( 'ndn_default_width' ) : '425' ) ?>" placeholder="Width" analytics-category="WPPreview" analytics-label="ConfigureChange" />
                    <input class="ndn-video-width-disabled" name="<?php echo esc_attr( self::$settings_form_options['ndn_default_width'] ) ?>" type="hidden" value="0" />
                  </div>
                </label>

                <label>
                  <span>Dimensions:&nbsp;<span class="video-dimensions-attr"><span class="video-width-display"></span> x <span class="video-calculated-height"></span> px</span></span>
                </label>
              </div>

            </section>

            <section class="ndn-video-settings-behavior">
              <fieldset>
                <label name="<?php echo esc_attr( self::$custom_form_options['ndn_video_start_behavior'] ) ?>">
                  <div>
                    <span>Video Start Action:</span>
                  </div>
                  <div>
                    <select class="ndn-video-start-behavior" name="<?php echo esc_attr( self::$custom_form_options['ndn_video_start_behavior'] ) ?>" analytics-category="WPPreview" analytics-label="ConfigureChange">
                      <?php foreach (self::$start_behavior_options as $selection) { ?>
                        <option value="<?php echo esc_attr($selection['value']) ?>" <?php echo ( get_option( 'ndn_default_start_behavior' ) == $selection['value'] ? esc_attr( 'selected="selected"' ) : '' ) ?>><?php echo esc_html($selection['name']) ?></option>
                      <?php } ?> <!-- End FOR -->
                    </select>
                  </div>
                </label>
              </fieldset>
            </section>

            <section>
              <fieldset style="display:none;">
                <label name="<?php echo esc_attr( self::$custom_form_options['ndn_video_position'] ) ?>">Video Position:</label><br />
                <select class="ndn-video-position" name="<?php echo esc_attr( self::$custom_form_options['ndn_video_position'] ) ?>" analytics-category="WPPreview" analytics-label="ConfigureChange">
                  <?php foreach (self::$video_position_options as $selection) { ?>
                    <option value="<?php echo esc_attr($selection['value']) ?>" <?php echo ( get_option( 'ndn_default_video_position' ) == $selection['value'] ? esc_attr( 'selected="selected"' ) : '' ) ?>><?php echo esc_html($selection['name']) ?></option>
                  <?php } ?> <!-- End FOR -->
                </select><br />
              </fieldset>
            </section>

          </form>
        </div>
      </section>
    </article>
  <?php

 }
  ?>
</div>
