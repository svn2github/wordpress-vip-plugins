<?php

class Qmerce_ShortCodes
{

    /**
     * @var QmerceTagComposer
     */
    private $tagComposer;

    /**
     * Constructor function
     */
    public function __construct()
    {
        $this->tagComposer = new QmerceTagComposer();
        // add interaction shortcode
        add_shortcode( 'interaction', array( $this, 'interactionRenderer' ) );
        // add random playlist shortcode
        add_shortcode( 'apester-playlist', array( $this, 'playlistRenderer' ) );
    }

    /**
     * renders the interaction tag
     * @param string $attrs
     * @return string
     */
    public function interactionRenderer($attrs)
    {
        return $this->tagComposer->composeInteractionTag($attrs['id']);
    }

    /**
     * renders the random playlist interaction tag
     * @param string $attrs
     * @return string
     */
    public function playlistRenderer($attrs)
    {
        return $this->tagComposer->composeAutomationTag($attrs['channeltoken']);
    }
}

$qmerceShortCodes = new Qmerce_ShortCodes();
