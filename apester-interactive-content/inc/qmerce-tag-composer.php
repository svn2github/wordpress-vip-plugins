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
        return '<interaction id="' . $interactionId . '"></interaction>';
    }

    /**
     * composes automation tag (with userId taken by token)
     * @return string
     */
    public function composeAutomationTag()
    {
        qmerce_add_sdk_for_shortcode();
        return '<interaction data-random="' . $this->getAuthToken() . '"></interaction>';
    }

    /**
     * Returns the publisher token from the DB.
     * @return string
     */
    private function getAuthToken() {
        $qmerceSettings = get_option( 'qmerce-settings-admin' );
        return $qmerceSettings['auth_token'];
    }
}
