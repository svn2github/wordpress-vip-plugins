<?php

class QmerceTagComposer {

    /**
     * Composes an attribute in the form of data-[attributeName] with it's value
     * Assuming $attributeName to be the same name for the data attribute and the same property name in $qmerceSettings
     * @param $qmerceSettings - the plugins saved settings
     * @param $attributeName - the attribute name to add
     *
     * @return string - the attribute string that can be added to the playlist html tag
     */
    public function composeSimpleAttribute($qmerceSettings, $attributeName)
    {
        $attributeText = '';
        $attributeValue = $qmerceSettings[$attributeName];

        if ( ! isset($attributeValue) ) {
            return $attributeText;
        }

        return $attributeText .= ' data-' . $attributeName . '="'. esc_attr( $attributeValue ? 'true' : 'false' ) . '" ';
    }

    /**
     * Composes the data-tags="x,x" attribute based on saved tags
     * @param $qmerceSettings - the plugins saved settings
     *
     * @return string - the data-tags attribute string to be added to the playlist html tag
     */
    public function composeTagsAttribute($qmerceSettings)
    {
        $attributeText = '';
        $comma = ',';

        $tags = $qmerceSettings['apester_tags'];

        if ( ! isset($tags) || count($tags) === 0 ) {
            return $attributeText;
        }

        $attributeText = ' data-tags="';

        foreach ( $tags as $tagIndex => $tagValue ) {

            if ( $tagIndex !== 0 ) {
                $attributeText .= $comma;
            }

            $attributeText .= esc_attr( $tagValue );
        }

        $attributeText .= '" ';

        return $attributeText;
    }

    /**
     * composes interaction tag with given id.
     * @param $interactionId
     * @return string
     */
    public function composeInteractionTag($interactionId)
    {
        qmerce_add_sdk_for_shortcode();
        return '<div class="apester-media" data-media-id="' . $interactionId . '"></div>';
    }

    /**
     * composes automation tag (with userId taken by token)
     * @param string $channelToken
     * @return string
     */
    public function composeAutomationTag($channelToken = '')
    {
        $qmerceSettings = get_option( 'qmerce-settings-admin' );
        $tags = $this->composeTagsAttribute($qmerceSettings);

        // if no token is passed use the first token we have
        $channelTokenToRefer = $channelToken != '' ? $channelToken : $this->getAuthToken();

        // only insert playlist tag if a token was returned
        if ( isset($channelTokenToRefer) ) {
            qmerce_add_sdk_for_shortcode();
            return '<div class="apester-media" data-random="'
                   . $channelTokenToRefer . '"'
                   . $tags
                   . $this->composeSimpleAttribute($qmerceSettings, 'context')
                   . $this->composeSimpleAttribute($qmerceSettings, 'fallback')
                   . '></div>';
        }
    }

    /**
     * Returns the publisher token from the DB.
     * @return string
     */
    private function getAuthToken() {
        $selectedToken = '';

        $qmerceSettings = get_option( 'qmerce-settings-admin' );

        $allTokens = $qmerceSettings['apester_tokens'];

        $playlistEnabledTokens = array_filter($allTokens, function($value) {
            return $value['isPlaylistEnabled'] == '1';
        });

        if ( isset($playlistEnabledTokens) ) {
            $selectedToken = array_rand($playlistEnabledTokens);
        }

        return $selectedToken;
    }
}
