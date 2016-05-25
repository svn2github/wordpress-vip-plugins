<div id="lfapps-general-metabox-holder" class="metabox-holder clearfix">
    <?php
    wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
    wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
            if (typeof postboxes !== 'undefined')
                postboxes.add_postbox_toggles('plugins_page_livefyre_designer');
        });
    </script>    
    <div class="postbox-container postbox-large">
        <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            <div id="referrers" class="postbox ">
                <div class="handlediv" title="Click to toggle"><br></div>
                <h3 class="hndle"><span><?php esc_html_e('Visualization Apps Settings', 'lfapps-designer'); ?></span></h3>
                <form name="livefyre_comments_designer" id="livefyre_designer_general" action="options.php" method="POST">
                    <?php settings_fields('livefyre_apps_settings_designer'); ?>
                    <div class='inside'>
                        <table cellspacing="0" class="lfapps-form-table">
                            <tr>
                                <th align="left" scope="row" style="width: 40%">
                                    <?php esc_html_e('Enable Visualization Apps on', 'lfapps-designer'); ?>
                                    <span class="info"><?php esc_html_e('(Select the types of posts on which you wish to enable the Visualization Apps Shortcode)', 'lfapps-designer'); ?></span>
                                </th>
                                <td align="left" valign="top">
                                    <?php
                                    $excludes = array( '_builtin' => false );
                                    $post_types = get_post_types( $args = $excludes );
                                    $post_types = array_merge(array('post'=>'post', 'page'=>'page'), $post_types);
                                    
                                    foreach ($post_types as $post_type ) {
                                        $post_type_name = 'livefyre_designer_display_' .$post_type;
                                        ?>
                                        <input type="checkbox" id="<?php echo esc_attr('livefyre_apps-'.$post_type_name); ?>" name="<?php echo esc_attr('livefyre_apps-'.$post_type_name); ?>" value="true" <?php checked( get_option('livefyre_apps-'.$post_type_name), "true"); ?>/>
                                        <label for="<?php echo esc_attr('livefyre_apps-'.$post_type_name); ?>"><?php echo esc_html_e($post_type, 'lfapps-designer'); ?></label><br/>
                                        <?php
                                    }
                                    ?>
                                    
                                </td>
                            </tr>
                            <tr>                               
                                <?php
                                $available_versions = Livefyre_Apps::get_available_package_versions('designer');
                                if (empty($available_versions)) {
                                    $available_versions = array(LFAPPS_Designer::$default_package_version);
                                }
                                $available_versions['latest'] = 'latest';
                                $available_versions = array_reverse($available_versions);
                                ?>
                                <th align="left" scope="row" style="width: 40%">
                                    <?php esc_html_e('Package version', 'lfapps-designer'); ?><br/>
                                    <span class="info"><?php esc_html_e('(If necessary you can revert back to an older version if available)', 'lfapps-designer'); ?></span>
                                </th>
                                <td align="left" valign="top">
                                    <select name="livefyre_apps-livefyre_designer_version">
                                        <?php foreach ($available_versions as $available_version): ?>
                                            <?php $selected_version = get_option('livefyre_apps-livefyre_designer_version', 'latest') == $available_version ? 'selected="selected"' : ''; ?>
                                            <option value="<?php echo esc_attr($available_version); ?>" <?php echo selected( get_option('livefyre_apps-livefyre_designer_version', 'latest'), $available_version); ?>>
                                                <?php echo ucfirst(esc_html($available_version)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>   
                            <tr>
                                <td colspan='2'>
                                    <strong>Visualization Apps Configuration Options:</strong>
                                    <p>You can configure your Visualization Apps in the <a href="https://<?php $exploded = explode(".", get_option('livefyre_apps-livefyre_domain_name')); echo $exploded[0]?>.admin.fyre.co/v3/apps" target="_blank">Livefyre Studio</a>.
                                    Visualization Apps include:<br /> 
                                    Media Wall<br />
                                    Mosaic<br />
                                    Carousel<br />
                                    Feature Card<br />
                                    Map<br />
                                    Storify 2<br />
                                    Post Button<br />
                                    Poll<br />
									Trending<br />
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div id="major-publishing-actions">									
                        <div id="publishing-action">
                            <?php submit_button(); ?>
                        </div>
                        <div class="clear"></div>
                    </div>
                </form>                
            </div>
        </div>
    </div>
    <div class="postbox-container postbox-large">
        <div id="normal-sortables" class="meta-box-sortables ui-sortable">
            <div id="referrers" class="postbox ">
                <div class="handlediv" title="Click to toggle"><br></div>
                <h3 class="hndle"><span><?php esc_html_e('Visualization Apps Shortcode', 'lfapps-designer'); ?></span></h3>
                <div class='inside'>
                    <p>To activate Visualization Apps, you must add a shortcode to your content.</p>
                    <p>The shortcode usage is pretty simple. Let's say we wish to generate a Visualization App inside post content. We could enter something like this
                        inside the content editor:</p>
                    <p class='code'>[livefyre_designer app_id="123"]</p>
                    <p><strong>Note:</strong> You will need to swap out the App ID appropriately.  To find the App ID, go to:  <a href="https://studio.livefyre.com/<?php echo get_option('livefyre_apps-livefyre_domain_name', 'livefyre.com');?>/apps">Livefyre Studio.</a> Select the App you wish to render > Find the App ID in the Developer Info on the App details page.</p><p>If you copy and paste the App ID into the Visual tab of the WP post page, it will copy over formatting that will prevent the post from loading. You must go into the Text tab to remove the formatting.</p>
                	<p><strong>Note:</strong> After publishing an app, may take a couple minutes before it appears on the wordpress post</p>
                </div> 
            </div>
        </div>
    </div>     
</div>