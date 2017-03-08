<?php

class QmerceTagComposer {

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
        // if no token is passed use the first token we have
        $channelTokenToRefer = $channelToken != '' ? $channelToken : $this->getAuthToken();
        qmerce_add_sdk_for_shortcode();
        return '<div class="apester-media" data-random="' . $channelTokenToRefer . '"></div>';
    }

    /**
     * Returns the publisher token from the DB.
     * @return string
     */
    private function getAuthToken() {
        $qmerceSettings = get_option( 'qmerce-settings-admin' );
        $authToken = $qmerceSettings['auth_token'];

        if ( isset($authToken) ) {
            $authToken = is_array($authToken) ? $authToken[0] : $authToken;
        }
        else {
            $authToken = '';
        }

        return $authToken;
    }
}
