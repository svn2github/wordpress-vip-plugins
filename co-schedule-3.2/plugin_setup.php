<?php if ( current_user_can( 'manage_options' ) ) { ?>
    <script type='text/javascript'>
        var tm_cos_token = "<?php echo esc_js( get_option( 'tm_coschedule_token' ) ); ?>";
    </script>
    <!--[if IE 9]>
    <style>
        .coschedule .ie-only {
            /*display: block !important;*/
        }

        .coschedule .not-ie {
            /*display: none !important;*/
        }
    </style>
    <![endif]-->



    <div class="coschedule">
        <div class="cos-wrapper cos-setup">
            <div class="setup-box">

                <div class="status-bar">
                    <div class="status-part">
                        <span id='status-install-plugin' class="status done">Install Plugin</span>
                    </div>
                    <div class="status-part">
                        <span id='status-sign-in' class="status current">Sign In</span>
                    </div>
                    <div class="status-part">
                        <span id='status-choose-calendar' class="status">Choose Calendar</span>
                    </div>
                    <div class="status-part">
                        <span id='status-finish' class="status">Finish</span>
                    </div>
                </div>

                <!-- Calendar Setup Area -->
                <div id="tm_form_calendar_setup" style="display: none;">
                    <div class="cos-installer-loading">
                        Loading your calendar....
                    </div>
                </div>


                <div class="sign-in setup-section">

                    <div class="logo"></div>

                    <div class="header">
                        <h4>Sign in to CoSchedule to Connect Your Site</h4>
                    </div>

                    <div class="form marg-t">
                        <label class="form-label text-left marg-t">Email</label>
                        <input class="input input-block-level" type="text" name="tm_coschedule_email" id="tm_coschedule_email" placeholder="name@website.com"><br>

                        <label class="form-label text-left">Password</label>
                        <input class="input input-block-level" type="password" name="tm_coschedule_password" id="tm_coschedule_password" placeholder="••••••••"><br>

                        <button type="submit" class="btn btn-orange btn-block default" id="tm_activate_button">Sign In</button>

                        <div class="links">
                            <a target="_blank" rel="noopener" class="sign-up" href="https://coschedule.com/signup">Need an account?</a>
                            <a target="_blank" rel="noopener" class="forgot-password" href="https://app.coschedule.com/#/forgot_password">Forgot your password?</a>
                        </div>
                    </div>
                </div>

                <div class="calendar-pick setup-section hide">

                    <div class="header">
                        <h4>Choose the calendar you want to connect this site to</h4>
                    </div>

                    <div class="calendar-container">
                        <!-- Calendars get inserted into calendar-options-wrapper. -->
                        <div class="calendar-options-wrapper"></div>
                    </div>

                    <div class="links">
                        <a target="_blank" rel="noopener" class="calendar-create" href="https://app.coschedule.com/#/login">Need to create a calendar?</a>
                        <a class="change-login" onclick="window.location.reload();">Login as a different CoSchedule user</a>
                    </div>
                </div>

                <div id="tm_coschedule_alert" class="marg-all-lg alert small" style="display:none;"></div>

                <div class="calendar-create setup-section hide">
                    <div class="header">
                        <h4>Create Your CoSchedule Calendar</h4>
                    </div>

                    <div class="create-container">
                        <div class="input-area">
                            <input class="input input-block-level" type="text" name="" id="new-calendar-name-create" placeholder="Calendar Name">
                        </div>

                        <button type="submit" class="btn btn-orange btn-block default create-calendar-button" id="">Create Calendar</button>
                        <a class='cancel-flow' class="#">Cancel</a>

                    </div>

                </div>

                <div class="calendar-error setup-section hide">
                    <div class="header">
                        <h4>Oops! This Site Is Already Connected To A Calendar</h4>

                        <p>This WordPress site is already connected to the calendar listed below. However, your account doesn't have permission to access it.</p>
                    </div>

                    <div class="cal">
                        <div class="calendar-color-dot">
                            <div class="dot color-background color-dark"></div>
                        </div>
                        <span class="name"></span>
                    </div>

                    <!-- Request Access to calendar.  -->
                    <div class="request-access-body">
                        <button type="submit" class="btn btn-orange btn-block default" id="request-access-button">Request Access</button>
                        <div class="request-response hide">Request Sent.</div>
                    </div>
                    <a class='cancel-flow' class="#">Retry</a>
                </div>

                <div class="connecting setup-section hide">
                    <div class="header">
                        <h4>Connecting to CoSchedule</h4>
                    </div>

                    <div class="loading-icon marg-t-lg marg-b-lg"></div>

                    <span id='tm_connection_msg' class="small gray-text"></span>
                </div>


                <div class="connection-error setup-section hide">
                    <div class="header">
                        <h4>Could Not Connect to CoSchedule</h4>

                        <p class="error-message"></p>
                    </div>

                    <button type="submit" class="btn btn-orange btn-block default marg-t-lg" id="retry-connection-tests"><span class="icon-loop marg-r"></span>Try Again</button>
                    <a class='cancel-flow' href="#">Cancel</a>
                </div>


                <div class="finished setup-section hide">
                    <div class="header">
                        <h4 class="marg-b">Connection Successful! 🙌</h4>

                        <p class="marg-b-lg marg-t-lg">You've successfully connected your WordPress Site to your calendar. The next step? Learn how to publish to WordPress with CoSchedule using this <a href="https://help.coschedule.com/hc/en-us/articles/214278898">handy guide</a> OR by signing up for a <a href="https://coschedule.com/demo?search=getting-started">demo</a>.</p>
                    </div>
                    <!-- finish-button's href gets set dynamically. -->
                    <a id="finish-button"><button type="submit" class="btn btn-orange btn-block default marg-t-lg" id="">View Calendar</button></a>
                </div>

            </div>
        </div>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            // add enter key trigger submit action //
            $('input[type=text],input[type=password]').keypress(function (e) {
                if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
                    $(e.target).parents('.sign-in')
                        .find('button[type=submit].default').click();
                    return false;
                }
                return true;
            });
        });
    </script>
    <?php
} else {
    include( plugin_dir_path( __FILE__ ) . '_access-denied.php' );
}
