<div class="wrap" id="apester-admin">
    <h1>Apester Plugin</h1>
    <?php
    // Load settings
    $apester_options = get_option( 'qmerce-settings-admin' );

    // Set default tab
    $apester_active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'embed';
    ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo urlencode( $this->get_option_name() ); ?>&tab=embed"      class="nav-tab <?php echo $apester_active_tab == 'embed'      ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Embedding', 'apester' ); ?></a>
        <a href="?page=<?php echo urlencode( $this->get_option_name() ); ?>&tab=settings"      class="nav-tab <?php echo $apester_active_tab == 'settings'      ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'User Settings',   'apester' ); ?></a>
        <a href="?page=<?php echo urlencode( $this->get_option_name() ); ?>&tab=feedback"   class="nav-tab <?php echo $apester_active_tab == 'feedback'   ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Feedback',        'apester' ); ?></a>
    </h2>

    <?php if( $apester_active_tab == 'embed' ) { ?>

        <div class="apester-settings-tab-content embed-tab">
            <div class="pull-left ape-controls">
                <h3>Embed Apester Interactions</h3>
                <ol class="embed-list">
                    <li>
                        <p><?php esc_html_e( 'Create a new post/page and click the red Apester button in the visual editor', 'apester' ); ?></p>
                    </li>
                    <li>
                        <p><?php esc_html_e( 'Search and/or sort for any created item or select one of your own.', 'apester' ); ?></p>
                    </li>
                    <li>
                        <p><?php esc_html_e( 'To embed the item to your post click on the “Embed” button.', 'apester' ); ?></p>
                    </li>
                </ol>
            </div>
            <div class="pull-left apester-right-column">
                <img src="<?php echo esc_url( plugins_url( 'public/img/embedding@2x.png', dirname(__FILE__) ) ); ?>" alt="Embedding" class="settings-img">
            </div>
        </div>


    <?php } elseif( $apester_active_tab == 'settings' ) { ?>
        <form id="settingsTab" method="post" action="options.php">

            <?php
            // This prints out all hidden setting fields
            settings_fields('qmerce-settings-fields');
            ?>
            <script>
                (jQuery)(function($){
                    var $addTokenButton,
                        $tokenInput,
                        $tokensList,
                        $errorMsg;

	                /**
                     * validate token on the client
                     * @param token
                     * @returns {boolean} isValid
	                 */
                    var isValidToken = function(token) {
                        return /^[0-9a-fA-F]{24}$/.test(token);
                    };

                    var attachCallbackToRemoveTokenButton = function() {
                        $('body').on('click', '.ape-btn-remove-token', function (event) {
                            event.preventDefault();
                            var $currentButton = $(event.target || event.currentTarget);
                            $currentButton.parent().remove();
                        });
                    };

                    var init = function() {
                        attachCallbackToRemoveTokenButton();
                        $addTokenButton = $('.btn-add-token');
                        $tokenInput = $('#auth_token');
                        $tokensList = $('.tokens');
                        $errorMsg = $('.token-error-msg');
                    };

                    init();

                    // hide error message when focusing the input
                    $tokenInput.focus(function(event){
                        $errorMsg.hide();
                    });

                    // add-token button click handler
                    $addTokenButton.click(function(event){
                        event.preventDefault();

                        var token = $tokenInput.val();
                        var lastTokenIndex = 0;

                        if (token.trim() === '') {
                            return;
                        }

                        if ( ! isValidToken(token) ) {
                            $errorMsg.show();
                            return;
                        }

                        // if there are tokens already, get the last one's index and continue from there with current one
                        if ($tokensList.children().length > 0) {
                            // get the index portion from the 'name' property value
                            lastTokenIndex = $tokensList.find('li:last-child input').prop('name').slice(-2,-1) || 0;
                            lastTokenIndex = parseInt(lastTokenIndex);
                        }

                        // add the new token to the list
                        $tokensList.append(
                            '<li>' +
                            '<button class="ic icon-cross ape-btn-remove-token"></button>' +
                            '<input type="hidden" name="qmerce-settings-admin[auth_token][' + (++lastTokenIndex) + ']" value="' + token + '">' +
                            '<span>' + token + '</span>' +
                            '</li>');

                        // clear the input
                        $tokenInput.val('');
                    });
                    
                    // update tokens changes on event collector
                    $("#settingsTab").submit(function(){
                        var collectNewTokens = function(formValues) {
                            var newTokens = [];
                            var token;
                            for (var i=0; i < formValues.length; i++) {
                                if (formValues[i].name.indexOf('qmerce-settings-admin[auth_token]') >= 0) {
                                    token = formValues[i].value.toString().trim();
                                    if (token.length > 0 && isValidToken(token)) {
                                        newTokens.push(formValues[i].value);
                                    }
                                }
                            }

                            return newTokens;
                        };

                        var isInArray = function(needle, haystack) {
                            return haystack.indexOf(needle) > -1;
                        };

                        var sendEvents = function(addedTokens, removedTokens) {
                            var eventNames = {
                                'added': 'wordpress_plugin_token_added',
                                'removed': 'wordpress_plugin_token_removed'
                            };
                            var sendEvent = function(eventName, token) {
                                var baseUrl = 'http://gcp-events.apester.com/event';
                                var payload = {
                                    event: eventName,
                                    properties: {
                                        pluginProvider: 'WordPress',
                                        pluginVersion: window.apester_plugin_version,
                                        channelToken: token
                                    },
                                    metadata: {
                                        referrer: encodeURIComponent(document.referrer),
                                        current_url: encodeURIComponent(window.location.href),
                                        screen_height: window.screen.height.toString(),
                                        screen_width: window.screen.width.toString(),
                                        language: window.navigator.userLanguage || window.navigator.language
                                    }};
                                $.ajax({
                                    type: 'POST',
                                    url: baseUrl,
                                    contentType: 'application/json; charset=UTF-8',
                                    data: JSON.stringify(payload),
                                    async: false
                                });
                            };

                            var updateSingleTokenToWP = function(channelToken, isWp) {
                                var baseUrl = 'http://users.apester.com/publisher/token/',
                                    urlSuffix = '/publish-settings',
                                    data = { 'type': isWp ? 'wordpress' : 'jsEmbed'};


                                $.ajax({
                                    type: 'PATCH',
                                    url: baseUrl + channelToken + urlSuffix,
                                    contentType: 'application/json; charset=UTF-8',
                                    data: JSON.stringify(data),
                                    async: false
                                });
                            };

                            var i;

                            for (i=0; i < addedTokens.length; i++) {
                                sendEvent(eventNames.added, addedTokens[i]);
                                updateSingleTokenToWP(addedTokens[i], true);
                            }

                            for (i=0; i < removedTokens.length; i++) {
                                sendEvent(eventNames.removed, removedTokens[i]);
                                updateSingleTokenToWP(removedTokens[i], false);
                            }
                        };

                        var formValues = $(this).serializeArray();
                        
                        // collect new tokens that are about to be sent to server
                        var newTokens = collectNewTokens(formValues);
                        var removedTokens = [],
                            addedTokens = [];

                        // using window.apester_tokens - a global on server side exposed only in CMS (editing) mode, not on actual final page/post/etc.
                        var wp_option_apester_tokens = window.apester_tokens || [];
                        for (var i=0; i < newTokens.length; i++) {
                            if ( ! isInArray(newTokens[i], wp_option_apester_tokens) ) {
                                addedTokens.push(newTokens[i]);
                            }
                        }

                        for (var j=0; j < wp_option_apester_tokens.length; j++) {
                            if ( ! isInArray(wp_option_apester_tokens[j], newTokens)) {
                                removedTokens.push(wp_option_apester_tokens[j]);
                            }
                        }

                        sendEvents(addedTokens, removedTokens);
                    });

                });
            </script>


            <div class="apester-settings-tab-content">
                <div class="row">
                    <div class="pull-left ape-controls">
                        <h3>Channel Token</h3>
                        <p>You can find your channel token by going to your Channel settings page at <a href="https://app.apester.com/settings/publisher" target="_blank">Apester.com</a>
                            dashboard. Copy your channel token. If you have not set up a channel you will need to do so.
                        </p>
                        <div class="clearfix input-wrapper">
                            <input type="text" id="auth_token" name="qmerce-settings-admin[auth_token][0]"
                                   class="form-control ape-input ape-token-input" value="" size="28">
                            <button class="ic icon-plus btn-add-token"></button>
                            <span class="token-error-msg" style="display: none;">Invalid Token</span>
                        </div>
                        <h3 class="tokens-entered">Tokens Entered</h3>
                        <ul class="tokens">
                            <?php
                            $tokens = $apester_options['auth_token'];
                            if (isset( $tokens )) {
                                $tokens = is_array( $tokens ) ? $tokens : array( $tokens );
                                $index = 1;
                                foreach ( $tokens as $token ) {
                                    ?>
                                    <li>
                                        <button class="ic icon-cross ape-btn-remove-token"></button>
                                        <input type="hidden" name="qmerce-settings-admin[auth_token][<?php echo $index++ ?>]" value="<?php esc_html_e( $token ); ?>">
                                        <span><?php esc_html_e( trim( $token ) ); ?></span>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="pull-left apester-right-column">
                        <img src="<?php echo esc_url( plugins_url( 'public/img/usersettings@2x.png', dirname(__FILE__) ) ); ?>" alt="Token location" class="settings-img">
                    </div>
                </div>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="apester-submit-btn" value="Save Changes">
                </p>

                <div class="divider"></div>

                <div class="row">
                    <div class="pull-left ape-controls">

                        <h3>How to set up a channel</h3>
                        <p>
                            From the <a href="https://app.apester.com/dashboard" target="_blank">Apester dashboard</a>, click on the icon in the right hand side of the screen and select Settings from the drop down.
                            <br>On the left side of the page, you’ll find the "Create a Publisher" function. Click this and create a publisher channel with your company name and the URL of your website.
                        </p>
                    </div>
                    <div class="pull-left apester-right-column">
                        <img src="<?php echo esc_url( plugins_url( 'public/img/channels.jpg', dirname(__FILE__) ) ); ?>" alt="Channel setup" class="settings-img channel-setup">
                    </div>
                </div>

                <table class="form-table ape-extra-controls">
                    <tbody>
                    <tr>
                        <th scope="row">
                            Post Types with automated Apester interactive widget below the main content
                        </th>
                        <td>
                            <?php
                            $this->automationPostTypeCb();
                            ?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </form>
    <?php } elseif( $apester_active_tab == 'feedback' ) { ?>
        <div class="apester-settings-tab-content apester-feedback-tab">
            <div class="pull-left ape-controls">
                <h3><?php esc_html_e( 'We want to hear your feedback!', 'apester' ); ?></h3>

                <p><?php esc_html_e( 'Tell us how you enjoyed your experience with Apester\'s WordPress plugin.', 'apester' ); ?></p>

                <form id="apester-feedback-form" method="post">
                    <input type="text" name="feedbackName" id="apester-feedback-name" class="ape-input" placeholder="<?php esc_attr_e( 'Name', 'apester' ); ?>">
                    <input type="text" name="feedbackEmail" id="apester-feedback-email" class="ape-input" value="<?php echo esc_attr( get_bloginfo( 'admin_email' ) ); ?>">
                    <textarea name="feedbackMessage" id="apester-feedback-message" class="ape-input ape-textarea" placeholder="<?php esc_attr_e( 'Message', 'apester' ); ?>"></textarea>
                    <input type="hidden" name="subject" value="<?php esc_attr_e( 'WordPress plugin feedback', 'apester' ); ?>">
                    <input type="button" name="button" id="apester-feedback-submit" class="apester-submit-btn" value="<?php esc_attr_e( 'Submit', 'apester' ); ?>">
                    <p class="ape-msg-sent">Message Sent</p>
                </form>
            </div>
            <div class="pull-left apester-right-column">
                <h3><?php esc_html_e( 'Rate Us!', 'apester' ); ?></h3>
                <p><?php printf( __( '<a class="apester-red-link" href="%s" target="_blank">Rate us</a> on the WordPress to share the Apester plugin experience with the world!', 'apester' ), 'https://wordpress.org/support/plugin/apester-interactive-content/reviews/#new-post' ); ?></p>

                <h3><?php esc_html_e( 'Follow us', 'apester' ); ?></h3>
                <ul class="share-buttons">
                    <li>
                        <a href="https://www.facebook.com/apester.co" target="_blank" class="share-btn ic icon-facebook"><span>Facebook</span></a>
                    </li>
                    <li>
                        <a href="https://twitter.com/ApesterMag" target="_blank" class="share-btn ic icon-twitter"><span>Twitter</span></a>
                    </li>
                    <li>
                        <a href="https://www.linkedin.com/company/4854183" target="_blank" class="share-btn ic icon-linkedin"><span>LinkedIn</span></a>
                    </li>
                </ul>
            </div>

            <script>
                (jQuery)(function($){
                    var $submitBtn = $('#apester-feedback-submit'),
                        $nameInput = $('#apester-feedback-name'),
                        $emailInput = $('#apester-feedback-email'),
                        $MessageInput = $('#apester-feedback-message'),
                        $feedbackForm = $('#apester-feedback-form');

                    // apester feedback form submitting
                    $submitBtn.click( function () {

                        //TODO: fix  form url & handle success & failure properly
                        if ( ( $nameInput.val().length > 0 ) && ( $emailInput.val().length > 0 ) && ( $MessageInput.val().length > 0 ) ) {
                            ($).ajax({
                                type    : "POST",
                                url     : "http://www.apester.com/contactus",
                                data    : $feedbackForm.serialize(),
                                success : function ( data ) {
                                    $('.ape-msg-sent').addClass('success');
                                    var to = setTimeout(function() {
                                        $('.ape-msg-sent').removeClass('success')
                                        clearTimeout(to);
                                    }, 2000);
                                },
                                error   : function ( data ) {
                                    $('.ape-msg-sent').addClass('success');
                                    var to = setTimeout(function() {
                                        $('.ape-msg-sent').removeClass('success')
                                        clearTimeout(to);
                                    }, 2000);
                                }
                            });
                        } else {
                            // TODO: highlight invalid fields
                        }
                    });
                });
            </script>
        </div>
    <?php } ?>
</div>
