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
        add_shortcode( 'interaction', array( $this, 'interactionRenderer' ) );
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
}

$qmerceShortCodes = new Qmerce_ShortCodes();
