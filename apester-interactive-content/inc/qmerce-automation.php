<?php

class QmerceAutomation
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
    }

    /**
     * Manipulates the content and returns the updated one
     * @param $content
     * @return string
     */
    public function renderHtml($content)
    {
        global $post;

        if (!$this->isDisplayable($post)) {
            return $content;
        }

        return $this->concatenateTag($content);
    }

    /**
     * Concatenates the content with our tag separated by <br>
     * @param $content
     * @return string
     */
    protected function concatenateTag($content)
    {
        return $content . '<br>' . $this->tagComposer->composeAutomationTag();
    }

    /**
     * Determines weather interaction should be rendered
     * @param $post
     * @return bool
     */
    protected function isDisplayable($post)
    {
        return is_single() && $this->isPostTypeRegistered($post->post_type);
    }

    /**
     * Determines if given post type is registered
     * @param $postType
     * @return bool
     */
    private function isPostTypeRegistered($postType)
    {
        return in_array($postType, $this->getAllowedPostTypes());
    }

    /**
     * returns chosen allowed posts
     * @return array
     */
    private function getAllowedPostTypes()
    {
        $qmerceSettings = get_option( 'qmerce-settings-admin' );

        if ( !$qmerceSettings['automation_post_types'] ) {
            return array();
        }

        return $qmerceSettings['automation_post_types'];
    }
}
