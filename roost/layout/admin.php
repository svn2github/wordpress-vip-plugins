<?php
    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }
?>

<div id="rooster">
    <div id="roost-header">
        <?php if ( $roost_active_key ) { ?>
            <div class="roost-wrapper">
                <div id="roost-header-right">
                    <form action="" method="post">
                        <input type="Submit" id="roost-log-out" name="clearkey" value="Log Out" />
                    </form>
                    <span id="roost-username">
                        <span id="roost-user-logo">
                            <?php echo get_avatar( $roost_server_settings['ownerEmail'], 25 ); ?>
                        </span>
                        <?php
                            echo esc_html( $roost_server_settings['ownerEmail'] );
                        ?>
                    </span>
                </div>
                <img src="<?php echo esc_url( ROOST_URL . 'layout/images/roost-red-logo.png' ); ?>" />
                <?php if ( $roost_active_key ) { ?>
                    <div id="roost-site-name"><?php echo esc_html( $roost_server_settings['name'] ); ?></div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
    <?php if ( ! empty( $first_time ) ) { ?>
        <div class="updated roost-wrapper" id="roost-first-time-setup">
            <div id="roost-notice-text">
                <h3>Welcome to Roost, the plugin is up and running!</h3>
                <h4>Customize your site on the settings tab below.</h4>
            </div>
            <div id="roost-notice-target">
                <a href="#" id="roost-notice-CTA" ><span id="roost-notice-CTA-highlight"></span>Dismiss</a>
            </div>
        </div>
    <?php } ?>
    <?php if ( isset( $status ) && empty( $first_time ) ){ ?>
        <div id="rooster-status"><span id="rooster-status-text"><?php echo esc_html( $status ); ?></span><span id="rooster-status-close">Dismiss</span></div>
    <?php } ?>
        <!--BEGIN ADMIN TABS-->
        <?php if ( $roost_active_key ) { ?>

            <div id="roost-tabs" class="roost-wrapper">
                <ul>
                    <li class="active">Dashboard</li>
                    <li>Send a notification</li>
                    <li>Settings</li>
                </ul>
            </div>
        <?php } ?>
        <!--END ADMIN TABS-->
        <div id="roost-pre-wrap" class="<?php echo ! empty( $roost_active_key ) ? 'roost-white':''; ?>">
            <div id="roost-main-wrapper">
                    <!--BEGIN USER LOGIN SECTION-->
                    <?php if ( ! $roost_active_key ) { ?>
                        <form action="" method="post">
                            <div id="roost-login-wrapper">
                                <?php if ( empty( $roost_sites ) ){ ?>
                                    <div id="roost-signup-wrapper">
                                        <div id="roost-signup-inner">
                                            <img src="<?php echo esc_url( ROOST_URL . 'layout/images/roost_logo.png' ); ?>" alt="Roost Logo" />
                                            <h2>Create your account</h2>
                                            <p>
                                                Welcome! Creating an account only takes a few seconds and will give you access
                                                to additional features like our analytics dashboard at goroost.com
                                            </p>
                                            <a href="<?php echo esc_url( Roost::registration_url() ); ?>" id="roost-create-account" class="roost-signin-link"><img src="<?php echo esc_url( ROOST_URL . 'layout/images/roost-arrow-white.png' ); ?>" />Create an account</a>
                                            <div id="roost-bottom-right">Already have an account? <span class="roost-signup">Sign in</span></div>
                                        </div>
                                    </div>
                                <?php } ?>
                                <div id="roost-signin-wrapper" class="roost-login-account">
                                    <div id="roost-primary-logo">
                                        <img src="<?php echo esc_url( ROOST_URL . 'layout/images/roost_logo.png' ); ?>" alt="" />
                                    </div>
                                    <div class="roost-primary-heading">
                                        <span class="roost-primary-cta">Welcome! Log in to your Roost account below.</span>
                                        <span class="roost-secondary-cta">If you don’t have a Roost account <a href="<?php echo esc_url( Roost::registration_url() ); ?>" class="roost-signin-link">sign up now!</a></span>
                                    </div>
                                    <div class="roost-section-content">
                                        <!--USER NAME-->
                                        <div class="roost-login-input">
                                            <span class="roost-label">Email:</span>
                                            <input name="roostuserlogin" type="text" class="roost-control-login" value="<?php echo isset( $_POST['roostuserlogin'] ) ? esc_attr( $_POST['roostuserlogin'] ) : '' ?>" size="50" tabindex="1" />
                                        </div>
                                        <div class="roost-login-input">
                                            <!--PASSWORD-->
                                            <span class="roost-label">Password:</span>
                                            <input name="roostpasslogin" type="password" class="roost-control-login" value="<?php echo isset( $_POST['roostpasslogin'] ) ? esc_attr( $_POST['roostpasslogin'] ) : '' ?>" size="50" tabindex="2" />
                                        </div>
                                        <?php if ( isset( $roost_sites ) ) { ?>
                                            <!--CONFIGS-->
                                            <div class="roost-login-input">

                                                <span class="roost-label">Choose a configurations to use:</span>

                                                <select id="roostsites" name="roostsites" class="roost-site-select">
                                                    <option value="none" selected="selected">-- Choose Site --</option>
                                                    <?php
                                                        for($i = 0; $i < count( $roost_sites ); $i++ ) {
                                                    ?>
                                                        <option value="<?php echo esc_attr( $roost_sites[$i]['key'] ) . '|' . esc_attr( $roost_sites[$i]['secret'] ); ?>"><?php echo esc_html( $roost_sites[$i]['name'] ); ?></option>
                                                    <?php
                                                        }
                                                    ?>
                                                </select>
                                                <span class="roost-disclaimer">
                                                    To switch configurations after you log in, you will need to log out and choose a different configuration.
                                                </span>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <div class="roost-primary-footer">
                                        <input type="hidden" id="roost-timezone-offset" name="roost-timezone-offset" value="" />
                                        <input type="submit" id="roost-middle-save" class="roost-login" name="<?php echo isset($roost_sites) ? 'roostconfigselect' : 'roostlogin' ?>" value="<?php echo isset( $roost_sites ) ? 'Choose Site' : 'Login' ?>" tabindex="3" />
                                        <?php submit_button( 'Cancel', 'delete', 'cancel', false, array( 'tabindex' => '4' ) ); ?>
                                        <span class="roost-left-link"><a href="https://dashboard.goroost.com/" target="_blank">forget password?</a></span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php } ?>
                    <!--END USER LOGIN SECTION-->

                    <!--BEGIN ALL TIME STATS SECTION-->
                    <?php if ( $roost_active_key ) { ?>
                        <div id="roost-activity" class="roost-admin-section">
                            <div id="roost-all-stats">
                                <div class="roost-no-collapse">
                                    <div class="roostStats">
                                        <div class="roost-stats-metric">
                                            <span class="roost-stat"><?php echo number_format( ( esc_html( $roost_stats['registrations'] ) - esc_html( $roost_stats['unsubscribes'] ) ) ) ; ?></span>
                                            <hr />
                                            <span class="roost-stat-label">Total subscribers on <?php echo esc_html( $roost_server_settings['name'] ); ?></span>
                                        </div>
                                        <div class="roost-stats-metric">
                                            <span class="roost-stat"><?php echo number_format( esc_html( $roost_stats['notifications'] ) ); ?></span>
                                            <hr />
                                            <span class="roost-stat-label">Total notifications sent to your subscribers</span>
                                        </div>
                                        <div class="roost-stats-metric">
                                            <span class="roost-stat"><?php echo number_format( esc_html( $roost_stats['read'] ) ); ?></span>
                                            <hr />
                                            <span class="roost-stat-label">Total notifications read by your subscribers</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php } ?>
                    <!--END ALL TIME STATS SECTION-->

                    <!--BEGIN RECENT ACTIVITY SECTION-->
                    <?php if ( $roost_active_key ) { ?>
                            <div class="roost-section-wrapper">
                                <div class="roost-section-heading" id="roost-chart-heading">
                                    Recent Activity
                                    <div id="roost-time-period">
                                        <span class="roost-chart-range-toggle roost-chart-reload"><span class="load-chart" data-type="APP" data-range="DAY">Day</span></span>
                                        <span class="roost-chart-range-toggle roost-chart-reload active"><span class="load-chart" data-type="APP" data-range="WEEK">Week</span></span>
                                        <span class="roost-chart-range-toggle roost-chart-reload"><span class="load-chart" data-type="APP" data-range="MONTH">Month</span></span>
                                    </div>
                                    <div id="roost-metric-options">
                                        <ul>
                                            <li class="roost-chart-metric-toggle roost-chart-reload active"><span class="chart-value" data-value="s">Subscribes</span></li>
                                            <li class="roost-chart-metric-toggle roost-chart-reload"><span class="chart-value" data-value="n">Notifications</span></li>
                                            <li class="roost-chart-metric-toggle roost-chart-reload"><span class="chart-value" data-value="r">Reads</span></li>
                                            <li class="roost-chart-metric-toggle roost-chart-reload"><span class="chart-value" data-value="u">Unsubscribes</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="roost-section-content roost-section-secondary" id="roost-recent-activity">
                                    <div class="roost-no-collapse">
                                        <div id="roost-curtain">
                                            <div id="roost-curtain-notice">Graphs will appear once you have some subscribers.</div>
                                        </div>
                                        <div id="roostchart-dynamic" class="roostStats">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <!--END RECENT ACTIVITY SECTION-->

                    <!--BEGIN MANUAL PUSH SECTION-->
                    <?php if ( $roost_active_key ) { ?>
                        <form action="" method="post" id="manual-push-form">
                            <div id="roost-manual-push" class="roost-admin-section">
                                <div class="roost-section-wrapper">
                                    <span class="roost-section-heading">Send a manual push notification</span>
                                    <div class="roost-section-content roost-section-secondary" id="roost-manual-send-section">
                                        <div class="roost-no-collapse">
                                            <div id="roost-manual-send-wrapper">
                                                <div class="roost-send-type roost-send-active" id="roost-send-with-link" data-related="1">
                                                    <div class="roost-input-text">
                                                        <div class="roost-label">Notification text:</div>
                                                        <div class="roost-input-wrapper">
                                                            <span id="roost-manual-note-count"><span id="roost-manual-note-count-int">0</span> / 70 (reccommended)</span>
                                                            <input name="manualtext" type="text" class="roost-control-secondary" id="roost-manual-note" value="" size="50" />
                                                            <span class="roost-input-caption">Enter the text for the notification you would like to send your subscribers.</span>
                                                        </div>
                                                    </div>
                                                    <div class="roost-input-text">
                                                        <div class="roost-label">Notification link:</div>
                                                        <div class="roost-input-wrapper">
                                                            <input name="manuallink" type="text" class="roost-control-secondary" value="" size="50" />
                                                            <span class="roost-input-caption">Enter a website link (URL) that your subscribers will be sent to upon clicking the notification.</span>
                                                        </div>
                                                    </div>
                                                    <input type="Submit" class="roost-control-secondary" name="manualpush" id="manualpush" value="Send notification" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php } ?>
                    <!--END MANUAL PUSH SECTION-->

                    <!--BEGIN SETTINGS SECTION-->
                    <?php if ( $roost_active_key ) { ?>
                        <form action="" method="post">
                            <div id="roost-settings" class="roost-admin-section">
                                <?php if ( empty( $chrome_error_dismiss ) ) {  ?>
                                    <div id="roost-chrome-instructions">
                                        <span class="roost-section-heading">***NOTICE About Chrome Notifications***</span>
                                        <div id="roost-chrome-caption" class="roost-subsetting" style="display: block;">
                                            <div>
                                                &bull; Google has designed Chrome to require a valid SSL certificate for push notifications. If you do not use HTTPS, Chrome will not work.
                                            </div>
                                            <div>
                                                &bull; For detailed instructions view our <a href="https://goroost.com/best-practices/chrome-integration-guide" target="_blank">Chrome Integration Guide</a>. Still need help? <a href="mailto:support@goroost.com" target="_blank">Contact Roost support</a>.
                                            </div>
                                            <div id="chrome-install-dismiss"><a>Close</a></div>
                                        </div>
                                    </div>
                                <?php } ?>
                                <div class="roost-section-wrapper">
                                    <span class="roost-section-heading">Settings</span>
                                    <div class="roost-section-content roost-section-secondary">
                                        <div class="roost-no-collapse">
                                            <div class="roost-block">
                                                <div class="roost-setting-wrapper">
                                                    <span class="roost-label">Auto Push:</span>
                                                    <input type="checkbox" name="autoPush" class="roost-control-secondary" id="roost-push-control" value="1" <?php checked( $roost_settings['autoPush'], 1 ); ?> />
                                                    <span class="roost-setting-caption">Automatically send a push notification to your subscribers every time you publish a new post.</span>
                                                    <div class="roost-subsetting" id="roost-available-categories">
                                                        <div>Exclude Categories for Auto Push? <span class="light-weight">(If checked, posts published in these categories will <b>not</b> be sent.)</span></div>
                                                        <ul>
                                                            <?php foreach( $cats as $cat ) { ?>
                                                                <li><input type="checkbox" name="roost-categories[]" value="<?php echo esc_attr( $cat->cat_ID ); ?>" <?php checked( true, in_array( $cat->cat_ID, $roost_settings['categories'] ) ); ?> /> <?php echo esc_attr( $cat->cat_name ); ?></li>
                                                            <?php } ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div class="roost-setting-wrapper">
                                                    <span class="roost-label long-label">Notification prompt options:</span>
                                                    <input type="checkbox" name="roost-prompt-min" id="roost-prompt-min" value="1" <?php checked( $roost_settings['prompt_min'], 1 ); ?> />
                                                    <span class="roost-setting-caption" id="roost-settings-lift">Prompt visitors for notifications when they visit the site <input name="roost-prompt-visits" type="text" id="roost-min-visits" value="<?php echo esc_attr( $roost_settings['prompt_visits'] ); ?>" <?php disabled( empty( $roost_settings['prompt_min'] ) ) ?>/>times.</span>
                                                </div>
                                                <div class="roost-setting-wrapper">
                                                    <span class="roost-label"></span>
                                                    <input type="checkbox" name="roost-prompt-event" id="roost-prompt-event" value="1" <?php checked( $roost_settings['prompt_event'], 1 ); ?> />
                                                    <span class="roost-setting-caption">Prompt visitors for notifications once they complete an action (clicking a button or link).</span>
                                                    <div id="roost-event-hints" class="roost-subsetting">
                                                        <div>
                                                            &bull; Assign the class <span class="roost-code">"roost-prompt-wp"</span> to any element to prompt the visitor on click.
                                                            <span id="roost-hint-code-line">Example: <span class="roost-code">&lt;a href="#" class="roost-prompt-wp"&gt;Receive Desktop Notifications&lt;/a&gt;</span></span>
                                                        </div>

                                                        &bull; You could also create a <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">menu item</a> and add the same class, to trigger the prompt.
                                                    </div>
                                                    <span id="roost-event-hints-disclaimer">*Links or buttons with this class will be hidden to visitors already subscribed, or using a browser that does not support push notifications.</span>
                                                </div>
                                                <hr />
                                                <a id="roost-advanced-settings-control">Show Advanced Settings</a>
                                                <div id="roost-advanced-settings">
                                                    <div class="roost-setting-wrapper">
                                                        <span class="roost-label">Use Segmented Send:</span>
                                                        <input type="checkbox" name="roost-segment-send" value="1" id="roost-segment-send" <?php checked( $roost_settings['segment_send'], 1 ); ?> />
                                                        <span class="roost-setting-caption">Use WordPress categories to target notifications based on Roost segments.<br /> <strong>***DISCLAIMER***</strong> You must be assigning users segments to send notifications.</span>
                                                    </div>
                                                </div>
                                                <div class="roost-setting-wrapper">
                                                    <input type="Submit" class="roost-control-secondary" id="roost-settings-save" name="savesettings" value="Save Settings" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php } ?>
                    <!--END SETTINGS SECTION-->
                <div id="roost-support-tag">Have questions, comments, or need a hand? Hit us up at <a href="mailto:support@goroost.com" target="_blank">support@goroost.com</a> We're here to help.</div>
            </div>
        </div>
    <script>
        (function( $ ){
            $( '#rooster-status-close' ).click( function() {
                $( '#rooster-status' ).css( 'display', 'none' );
            });
            $( '#roost-notice-CTA' ).click(function( e ) {
                e.preventDefault();
                $( '#roost-first-time-setup' ).css( 'display', 'none' );
            });
            var timeZoneOffset = new Date().getTimezoneOffset();
            $( '#roost-timezone-offset' ).val( timeZoneOffset );
            <?php if ( $roost_active_key ) { ?>
                $( '.roost-chart-range-toggle, .roost-chart-metric-toggle' ).on( 'click', function() {
                    $( this ).parent().find( '.active' ).removeClass( 'active' );
                    $( this ).addClass( 'active' );
                });
            <?php if ( 0 !== $roost_stats['registrations'] ) { ?>
                    $( '#roost-curtain' ).hide();
                    var chart;
                    var data = {
                        type: $( '.roost-chart-range-toggle.active span' ).data( 'type' ),
                        range: $( '.roost-chart-range-toggle.active span' ).data( 'range' ),
                        value: $( '.roost-chart-metric-toggle.active span' ).data( 'value' ),
                        offset: new Date().getTimezoneOffset(),
                        action: 'graph_reload',
                    };
                    $( '.roost-chart-reload' ).on( 'click', function( e ) {
                        e.preventDefault();
                        $( '#roostchart-dynamic' ).html( "" );
                        data = {
                            type: $( '.roost-chart-range-toggle.active span' ).data( 'type' ),
                            range: $( '.roost-chart-range-toggle.active span' ).data( 'range' ),
                            value: $( '.roost-chart-metric-toggle.active span' ).data( 'value' ),
                            offset: new Date().getTimezoneOffset(),
                            action: 'graph_reload',
                        };

                        graphDataRequest( data );
                    });
                    function graphDataRequest( data ) {
                        $.post( ajaxurl, data, function( response ) {
                            loadGraph( response );
                        });
                    }
                    function loadGraph( data ) {
                        $( '#roostchart-dynamic' ).html( '' );
                        chart = new Morris.Bar({
                            element: $( '#roostchart-dynamic' ),
                            data: data,
                            barColors: ['#e25351'],
                            xkey: 'label',
                            ykeys: ['value'],
                            labels: ['Value'],
                            hideHover: 'auto',
                            barRatio: 0.4,
                            xLabelAngle: 20
                        });
                    }
                    $( window ).resize( function() {
                        chart.redraw();
                    });
                    graphDataRequest( data );
                <?php } ?>
            <?php } ?>
        })( jQuery );
        <?php if ( isset( $roost_sites ) ){ ?>
            jQuery( '.roost-control-login' ).attr( 'disabled', 'disabled' );
        <?php } ?>
        <?php if ( $roost_active_key ) { ?>
            (function( $ ){
                function confirmMessage() {
                    if ( ! confirm( 'Are you sure you would like to send a notification?' ) ) {
                        return false;
                    } else {
                        return true;
                    }
                }
                $( '#manualpush' ).on( 'click', function( e ) {
                    e.preventDefault();
                    var subscribers = <?php echo wp_json_encode( $roost_stats['registrations'] ); ?>;
                    if ( 0 === subscribers ) {
                        var resub;
                        $.post( ajaxurl, { action: 'subs_check' }, function( response ) {
                            resub = response;
                            if ( 0 === resub ) {
                                alert('You must have one visitor subscribed to your site to send notifications');
                                return;
                            } else {
                                if ( true === confirmMessage() ) {
                                     $( '#manualpush' ).unbind( 'click' ).trigger( 'click' );
                                }
                            }
                        });
                    } else {
                        if ( true === confirmMessage() ) {
                             $( '#manualpush' ).unbind( 'click' ).trigger( 'click' );
                        }
                    }
                });
                $( '#chrome-install-dismiss' ).on( 'click', function( e ) {
                    e.preventDefault();
                    $.post( ajaxurl, { action: 'chrome_dismiss' }, function( response ) {
                        $( '#roost-chrome-instructions' ).hide();
                        console.log('Chrome Install Dismissed');
                    });
                });
            })( jQuery );
        <?php } ?>
        <?php if ( empty( $roost_sites ) ){ ?>
            (function( $ ){
                if ( $( '#roost-login-wrapper' ).length ) {
                    var signup = $( '#roost-signup-wrapper' );
                    var signin = $( '#roost-signin-wrapper' );
                    signin.hide();
                    $( '.roost-signup' ).on( 'click', function() {
                        signup.toggle();
                        signin.toggle();
                    });
                }
            })( jQuery );
        <?php } ?>
    </script>
</div>
