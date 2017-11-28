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

        if (!$this->isDisplayable($post, $content)) {
            return $content;
        }

        if ($this->isPlaylistExcluded($content)) {
            return str_replace('[apester-exclude-playlist]', '', $content);
        }

        return $this->concatenateTag($content);
    }

    /**
     * Concatenates the content with our tag separated by <br>
     * @param $content
     * @return string
     */
    protected function concatenateTag($content) {
        $apester_options = get_option( 'qmerce-settings-admin' );
        $playlistPosition = $apester_options['playlist_position'];
        $enrichedContent = '';

        switch ($playlistPosition) {
//            default:
            case 'bottom':
                $enrichedContent = $this->insertInBottom($content);
                break;
            case 'top':
                $enrichedContent = $this->tagComposer->composeAutomationTag() . '<br>' . $content;
                break;
            case 'middle':
                $enrichedContent = $this->insertPlaylistInMiddle($content);
                break;
        }

        return $enrichedContent;
    }

    protected function insertInBottom($content) {
        return $content . '<br>' . $this->tagComposer->composeAutomationTag();
    }

    protected function insertPlaylistInMiddle($content) {
        $enrichedContent = '';
        $playlistTag = '<br>' . $this->tagComposer->composeAutomationTag() . '<br>';

        $matches = array();

        $matches = explode("</p>", $content);

        // explode returns count of 1 (array with one item containing the original string) in case nothing was found, so we normalize that
        $matchesCount = count($matches) - 1;

        if ( $matchesCount === 0 ) {
            return $this->insertInBottom($content);
        }

        $insertPosition = ( $matchesCount % 2 ) > 0 ? round( $matchesCount / 2 ) - 1 : $matchesCount / 2;

        array_splice( $matches, $insertPosition, 0, $playlistTag );

        $enrichedContent = implode('</p>', $matches);

        return $enrichedContent;
    }

    /**
     * Determines weather interaction should be rendered
     * @param $post
     * @return bool
     */
    protected function isDisplayable($post, $content)
    {
        return is_singular() && $this->isPostTypeRegistered($post->post_type);
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
     * Checks if the post has the '[apester-exclude-playlist]' shortcode
     * @param $content - the article's content
     * @return bool - whether the shorcode is present or not
     */
    private function isPlaylistExcluded($content)
    {
        $matches = array();
        preg_match_all('/\[apester-exclude-playlist\]/', $content, $matches);
        return count($matches[0]) > 0;
    }

    /**
     * returns chosen allowed posts
     * @return array
     */
    private function getAllowedPostTypes()
    {
        $qmerceSettings = get_option( 'qmerce-settings-admin' );

        if ( ! isset( $qmerceSettings['automation_post_types'] ) ) {
            return array();
        }

        return $qmerceSettings['automation_post_types'];
    }
}
