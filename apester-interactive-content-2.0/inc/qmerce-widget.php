<?php

class QmerceWidget extends WP_Widget {

    /**
     * @var QmerceTagComposer
     */
    private $tagComposer;

    /**
     * Constructor function
     */
    public function __construct() {
        $this->tagComposer = new QmerceTagComposer();
        parent::__construct('qmerce_widget', 'Apester Challenge Widget', array('description' => 'Chosen automated Apester challenge from your `My Stuff` inventory'));
    }

    /**
     * Hook callback for widget view
     * @param array $args
     * @param array $instance
     */
    public function widget( $args, $instance ) {
        echo $this->tagComposer->composeAutomationTag();
    }
}
