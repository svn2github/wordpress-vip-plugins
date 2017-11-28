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
        <a href="?page=<?php echo urlencode( $this->get_option_name() ); ?>&tab=settings"      class="nav-tab <?php echo $apester_active_tab == 'settings'      ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'User Settings', 'apester' ); ?></a>
        <a href="?page=<?php echo urlencode( $this->get_option_name() ); ?>&tab=playlist"   class="nav-tab new <?php echo $apester_active_tab == 'playlist'   ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Playlist', 'apester' ); ?></a>
        <a href="?page=<?php echo urlencode( $this->get_option_name() ); ?>&tab=feedback"   class="nav-tab <?php echo $apester_active_tab == 'feedback'   ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Feedback', 'apester' ); ?></a>
    </h2>
    <script>
        (jQuery)(function($) {
            // will hold utils used across settings tabs
            var apesterUtils = {};

            /**
             * validate token on the client
             * @param token
             * @returns {boolean} isValid
             */
            apesterUtils.isValidToken = function(token) {
                return /^[0-9a-fA-F]{24}$/.test(token);
            };

            apesterUtils.getChannelName = function(channelToken) {
                if (!apesterUtils.isValidToken(channelToken)) {
                    return ;
                }
                var baseUrl = 'https://users.apester.com/publisher/token/';
                return $.ajax({
                    type: 'GET',
                    url: baseUrl + channelToken,
                }).then(function(response){
                    return (typeof response.payload !== 'undefined' && response.payload !== null) ? response.payload.name : channelToken;
                });
            };

            // expose the utils to the other tabs - each tab then saves a copy of this variable,
            // so in case it is cahnge after pageload it won't break the code
            window.apesterUtils = apesterUtils;
        });
    </script>
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
                    var apesterUtils = window.apesterUtils;

                    var $addTokenButton,
                        $tokenInput,
                        $tokensList,
                        $errorMsg;

                    var attachCallbackToRemoveTokenButton = function() {
                        $('body').on('click', '.ape-btn-remove-token', function (event) {
                            event.preventDefault();
                            var $currentButton = $(event.target || event.currentTarget);
                            $currentButton.parent().remove();
                        });
                    };

                    var attachChannelNames = function() {
                        var tokenLabels = $('.token-label');
                        tokenLabels.each(function(){
                            var $this = $(this);
                            var token = $this.text();
                            apesterUtils.getChannelName(token).then(function(channelName) {
                                if (token !== channelName) { $this.text(token + ' - ' + channelName); }
                            });
                        });
                    };

                    var init = function() {
                        attachCallbackToRemoveTokenButton();
                        attachChannelNames();
                        $addTokenButton = $('.btn-add-token');
                        $tokenInput = $('#auth_token');
                        $tokensList = $('.tokens');
                        $errorMsg = $('.token-error-msg');
                    };

                    init();

                    // hide error message when focusing the input
                    $tokenInput.focus(function(event) {
                        $errorMsg.hide();
                    });

                    // add-token button click handler
                    $addTokenButton.click(function(event) {
                        event.preventDefault();

                        var token = $tokenInput.val();
                        var lastTokenIndex = 0;

                        if (token.trim() === '') {
                            return;
                        }

                        if ( ! apesterUtils.isValidToken(token) ) {
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
                            '<li class="clearfix">' +
                            '<button class="ic icon-cross ape-btn-remove-token pull-left"></button>' +
                            '<input type="hidden" name="qmerce-settings-admin[auth_token][' + (++lastTokenIndex) + ']" value="' + token + '">' +
                            '<span class="token-label pull-left" style="width: 90%;">' + token + '</span>' +
                            '</li>');

                        // clear the input
                        $tokenInput.val('');

                        // add the channel name next to the token
                        apesterUtils.getChannelName(token).then(function(channelName) {
                            var $addedItem = $tokensList.find('li:last-child .token-label');
                            if (token !== channelName) { $addedItem.text(token + ' - ' + channelName); }
                        });
                    });

                    // update tokens changes on event collector
                    $("#settingsTab").submit(function() {
                        var collectNewTokens = function(formValues) {
                            var newTokens = [];
                            var token;
                            for (var i=0; i < formValues.length; i++) {
                                if (formValues[i].name.indexOf('qmerce-settings-admin[auth_token]') >= 0) {
                                    token = formValues[i].value.toString().trim();
                                    if (token.length > 0 && apesterUtils.isValidToken(token)) {
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
                                var baseUrl = 'https://events.apester.com/event';
                                var payload = {
                                    event: eventName,
                                    properties: {
                                        pluginProvider: 'WordPress',
                                        pluginVersion: window.apester_plugin_version,
                                        channelToken: token,
                                        phpVersion: window.php_version,
                                        wpVersion: window.wp_version
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
                                var baseUrl = 'https://users.apester.com/publisher/token/',
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
                        var wp_option_apester_tokens = Object.keys(window.apester_tokens) || [];
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
                                    <li class="clearfix">
                                        <button class="ic icon-cross ape-btn-remove-token pull-left"></button>
                                        <input type="hidden" name="qmerce-settings-admin[auth_token][<?php echo $index++ ?>]" value="<?php esc_html_e( $token ); ?>">
                                        <span class="token-label pull-left" style="width: 90%;"><?php esc_html_e( trim( $token ) ); ?></span>
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

                <div class="hidden">
                    <ul>
                        <?php
                        // we insert the playlist inputs in hidden state, so they will be sent with the form of the
                        // user-settings tab, but won't be visible since they don't belong here, but in the playlist tab
                        $this->automationPostTypeCb();
                        ?>
                    </ul>
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
                                url     : (window.location.protocol) + "//www.apester.com/contactus",
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
    <?php } elseif( $apester_active_tab == 'playlist' ) { ?>
        <div class="apester-settings-tab-content apester-playlist-tab">
            <form id="playlistTabForm" class="playlist-tab-form" method="post" action="options.php">
                <div class="clearfix">
                    <div class="pull-left ape-controls">
                        <h3>Apester Playlist</h3>
                        <ul class="bullet-list">
                            <li><p><?php esc_html_e( 'The playlist is a one-time code that automatically rotates pre-selected monetizable Apester units directly into articles, or any other predefined location on your site.', 'apester' ); ?></p></li>
                            <li><p><?php esc_html_e( 'You start by creatinng an Apester item - as you normally would. Before publishing the unit, select the “Include in Playlist” checkbox. This will add the interaction to your playlist pool.', 'apester' ); ?></p></li>
                        </ul>
                        <h3 class="settings-form-header">Settings</h3>

                        <?php
                        // This prints out all hidden setting fields
                        settings_fields('qmerce-settings-fields');

                        $tokens = $apester_options['auth_token'];
                        if (isset( $tokens )) {
                            $tokens = is_array( $tokens ) ? $tokens : array( $tokens );
                            $index = 1;
                            foreach ( $tokens as $token ) {
                                ?>
                                <input type="hidden" name="qmerce-settings-admin[auth_token][<?php echo $index++ ?>]" value="<?php esc_html_e( $token ); ?>">
                                <?php
                            }
                        }

                        ?>

                        <div style="width: 70%;" class="pull-left">
                            <h4>Choose Channels</h4>
                            <?php
                            $playlist_tokens = $apester_options['apester_tokens'];
                            if ( isset( $playlist_tokens ) && ( ! empty($playlist_tokens) ) ) {
                                ?>
                                <ul class="tokens">
                                    <?php
                                    foreach ( $playlist_tokens as $playlist_token => $value ) {
                                        ?>
                                        <li class="clearfix">
                                            <input type="hidden" name="qmerce-settings-admin[playlist_enabled_tokens][<?php esc_html_e( $playlist_token ) ?>]" value="<?php esc_html_e( $value['isPlaylistEnabled'] == true ? '1' : '0' ); ?>">
                                            <input type="checkbox" id="cbx-token-<?php echo $playlist_token; ?>" class="playlist-checkbox pull-left" <?php esc_html_e( $value['isPlaylistEnabled'] == true ? ' checked ' : '' ); ?> >
                                            <label for="cbx-token-<?php echo $playlist_token; ?>" class="token-label pull-left"><?php esc_html_e( trim( $playlist_token ) ); ?></label>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                                <?php
                            }
                            else {
                                ?>
                                <div class="no-tokens-msg">
                                    It seems like you haven't entered any token(s). Go ahead and add one in the
                                    <a href="<?php echo esc_url('?page=qmerce-settings-admin&tab=settings') ?>">settings tab</a>
                                </div>
                                <?php
                            }
                            ?>
                        </div>

                        <div style="width: 30%;" class="pull-left">
                            <h4>Distribution</h4>
                            <ul>
                                <?php
                                $this->automationPostTypeCb();
                                ?>
                            </ul>
                        </div>
                        <script>
                            (jQuery)(function($){
                                var apesterUtils = window.apesterUtils;

                                var MAX_TAGS = 15;
                                var MAX_TAG_LENGTH = 20;

                                var $addTagButton;
                                var $advancedSettingsContent;
                                var $tagInput;
                                var $tagsList;
                                var $playlistRadiosWrapper;
                                var currentTags = [];

                                var tokenLabels = $('.token-label');
                                tokenLabels.each(function(){
                                    var $this = $(this);
                                    apesterUtils.getChannelName($this.text()).then(function(channelName) {
                                        $this.text(channelName);
                                    });
                                });

                                /**
                                 * Sets currentTags based on the values we got from the server
                                 *
                                 */
                                var setCurrentTags = function() {
                                    currentTags = !!window.apester_tags && $.isArray(window.apester_tags) ? window.apester_tags : [];
                                };

                                var attachCallbackToRemoveTagButton = function() {
                                    $('body').on('click', '.ape-btn-remove-tag', function (event) {
                                        event.preventDefault();
                                        var $currentButton = $(event.target || event.currentTarget);
                                        var tag = $currentButton.parent().text();
                                        var tagIndex = currentTags.indexOf(tag);

                                        currentTags.splice(tagIndex, 1);
                                        $currentButton.parent().fadeOut(300, function() {
                                            $currentButton.parent().remove();
                                        });
                                    });
                                };

                                var clearTagInput = function() {
                                    $tagInput.val('');
                                };

                                var addTag = function() {
                                    var newTags = $tagInput.val();
                                    var lastTokenIndex = 0;

                                    if (newTags.trim() === '') {
                                        return;
                                    }

                                    newTags = newTags.split(' ');

                                    // if one of the tags is longer than allowed, stop whole the operation
                                    for (var j = 0; j < newTags.length; j++) {
                                        if (newTags[j].length > MAX_TAG_LENGTH) {
                                            return;
                                        }
                                    }

                                    // add each tag
                                    for (var i = 0; i < newTags.length; i++) {
                                        var tag = '';

                                        if (currentTags.length === MAX_TAGS) {
                                            clearTagInput();
                                            return;
                                        }

                                        tag = newTags[i].trim();

                                        if (tag.trim() === '') {
                                            continue;
                                        }

                                        if ($.inArray(tag, currentTags) < 0) {
                                            currentTags.push(tag);

                                            $tagsList.append(
                                                '<li class="tag">' +
                                                '<input type="hidden" name="qmerce-settings-admin[apester_tags][]" value="' + tag + '">' +
                                                '<span>' + tag + '</span>' +
                                                '<i class="ic icon-cross ape-btn-remove-tag"></i>' +
                                                '</li>');
                                        }
                                    }

                                    clearTagInput();
                                };

                                var setInitialPlaylistPositionImage = function() {
                                    var startValue = $('.playlist-position-checkboxes input[type=radio]:checked').val();
                                    $playlistRadiosWrapper.addClass(startValue);
                                };

                                var attachListeners = function() {
                                    $('.playlist-position-checkboxes input[type=radio]').change(function(event) {
                                        var $currentInput = $(event.target || event.currentTarget);
                                        $playlistRadiosWrapper.removeClass('top middle bottom');
                                        $playlistRadiosWrapper.addClass($currentInput.val());
                                    });


                                    $(".advanced-settings-header").click(function(event) {
                                        $advancedSettingsContent.toggleClass('open');
                                        $(this).find('i').toggleClass('rotate-right');

                                        if ($advancedSettingsContent.hasClass('open')) {
                                            $('html, body').animate({ scrollTop: $(document).height() }, 'slow');
                                        }
                                    });

                                    $('.playlist-checkbox').change(function(e) {
                                        var $this = $(event.target || event.currentTarget);
                                        $this.prev().val(
                                            !!$(this).prop('checked') ? '1' : '0'
                                        );

                                        var $fallbackCheckbox = $('#checkbox-fallback');

                                        if ($this.attr('id') === 'checkbox-contextual') {
                                            $this.parent().toggleClass('checked');

                                            if ($this.prev().val() === '0') {
                                                $fallbackCheckbox[0].disabled = 'disabled';
                                                $fallbackCheckbox.attr('checked', false);
                                                $fallbackCheckbox.prev().val('0');
                                            } else {
                                                $fallbackCheckbox.removeAttr('disabled');
                                            }
                                        }
                                    });
                                };

                                var init = function() {
                                    $addTagButton = $('.add-tag-button');
                                    $advancedSettingsContent = $('.advanced-settings-content');
                                    $tagInput = $('#add-tag-input');
                                    $tagsList = $('.tags');
                                    $playlistRadiosWrapper = $('.playlist-position-checkboxes');

                                    setCurrentTags();
                                    attachCallbackToRemoveTagButton();
                                    setInitialPlaylistPositionImage();
                                    attachListeners();

                                    $addTagButton.click(addTag);

                                    // add tag on enter
                                    $tagInput.keypress(function(event) {
                                        if (event.which == 13 || event.keyCode == 13) {
                                            event.preventDefault();
                                            addTag();
                                        }
                                        return true;
                                    });
                                };

                                init();
                            });
                        </script>
                    </div>
                    <div class="pull-left apester-right-column">
                        <img src="<?php echo esc_url( plugins_url( 'public/img/playlist_wp.gif', dirname(__FILE__) ) ); ?>" alt="Embedding" class="settings-img">
                    </div>
                </div>
                <h3 class="advanced-settings-header">
                    <i class="icon-arrow rotate-right"></i>
                    Advanced Settings
                </h3>
                <div class="advanced-settings-content clearfix">
                <div class="divider"></div>
                    <div class="pull-left ape-controls">
                        <div class="clearfix">
                            <h3><?php esc_html_e( 'Smarter Embed', 'apester' ); ?></h3>
                            <div class="contextual-container checkbox-container clearfix <?php esc_html_e( $apester_options['context'] == true ? 'checked' : '' ); ?>">
                                <input type="hidden" name="qmerce-settings-admin[context]" value="<?php esc_html_e( $apester_options['context'] == true ? '1' : '0' ); ?>">
                                <input type="checkbox" id="checkbox-contextual" class="playlist-checkbox pull-left" <?php esc_html_e( $apester_options['context'] == true ? ' checked ' : '' ); ?> >
                                <label for="checkbox-contextual" class="pull-left">
                                    <div>
                                        <span class="bold">Contextual Matching</span>
                                        <span class="lora">(recommended)</span>
                                    </div>
                                    <div class="light">Automatically select units based on the page's content.</div>
                                </label>
                            </div>
                            <div class="tags-container">
                                <div class="tags-title">
                                    <i class="ic icon-tags"></i>
                                    <span class="tag-texts">
                                        <span class="title">Add Tags</span><br>
                                        <span class="comment light">Tags improve contextual matching.</span>
                                    </span>
                                </div>
                                <div class="add-tag">
                                    <input type="text" id="add-tag-input">
                                    <i class="ic icon-plus add-tag-button"></i>
                                </div>
                                <ul class="tags">
                                    <?php
                                    if ( isset($apester_options['apester_tags']) ) {
                                        foreach ( $apester_options['apester_tags'] as $tag ) {
                                            ?>

                                            <li class="tag">
                                                <input type="hidden" name="qmerce-settings-admin[apester_tags][]"
                                                       value="<?php esc_html_e( $tag ); ?>">
                                                <span><?php esc_html_e( $tag ); ?></span>
                                                <i class="ic icon-cross ape-btn-remove-tag"></i>
                                            </li>
                                            <?php
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                            <div class="fallback-container checkbox-container clearfix">
                                <input type="hidden" name="qmerce-settings-admin[fallback]" value="<?php esc_html_e( $apester_options['fallback'] == true ? '1' : '0' ); ?>">
                                <input type="checkbox" id="checkbox-fallback" class="playlist-checkbox pull-left" <?php esc_html_e( $apester_options['fallback'] == true ? ' checked ' : '' ); ?> >
                                <label for="checkbox-fallback" class="pull-left">
                                    <div>
                                        <span class="bold">Allow Fallback</span>
                                        <span class="lora">(recommended)</span>
                                    </div>
                                    <div class="light">Always show a unit for the playlist, even when there is no</br>contextual match available.</div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="pull-left apester-right-column">
                        <h3><?php esc_html_e( 'Playlist Position', 'apester' ); ?></h3>
                        <div class="playlist-position-checkboxes">
                            <label>
                                <input type="radio" name="qmerce-settings-admin[playlist_position]" value="top" <?php esc_html_e( $apester_options['playlist_position'] == 'top' ? ' checked="checked" ' : '' ); ?>>
                                <span class="">Top of the article</span>
                            </label>
                            <label>
                                <input type="radio" name="qmerce-settings-admin[playlist_position]" value="middle" <?php esc_html_e( $apester_options['playlist_position'] == 'middle' ? ' checked="checked" ' : '' ); ?>>
                                <span class="">Middle of the article</span>
                            </label>
                            <label>
                                <input type="radio" name="qmerce-settings-admin[playlist_position]" value="bottom" <?php esc_html_e( $apester_options['playlist_position'] == 'bottom' ? ' checked="checked" ' : '' ); ?> >
                                <span class="">Bottom of the article</span>
                            </label>
                        </div>
                        <div class="playlist-position-indicator"></div>
                    </div>
                </div>
                <div class="divider"></div>
                <p class="submit">
                    <input type="submit" name="submit" id="playlistSubmit" class="apester-submit-btn btn-submit-playlist" value="Start Using Playlist">
                </p>
            </form>
        </div>
    <?php } ?>
</div>
