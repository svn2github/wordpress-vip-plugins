<?php

class Qmerce_Admin_Box
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initHooks();
    }

    /**
     * Init admin box hooks
     */
    protected function initHooks()
    {
//        add_action( 'add_meta_boxes', array( $this, 'addMetaBox' ));
        add_action( 'save_post', array( $this, 'savePost' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'addStyles' ));
        add_action( 'admin_head', array( $this, 'addAuthCode' ));
    }

    /**
     * Adds auth code as script to head
     */
    public function addAuthCode()
    {
        $authToken = $this->getAccessToken();

        if ( !$authToken ) {
            return;
        }

        echo '<script>window.authToken = ' . wp_json_encode( $authToken ) . ';</script>';
    }

    /**
     * Adds styles
     */
    public function addStyles()
    {
        $configuration = array(
          'editorBaseUrl' => QMERCE_EDITOR_BASEURL,
        );
        wp_register_style( 'qmerce_metabox', plugins_url( '/public/css/metabox.css', QMERCE__PLUGIN_FILE ) );
        wp_enqueue_style( 'qmerce_metabox' );

        wp_register_style( 'qmerce_modal', plugins_url( '/public/css/modal.css', QMERCE__PLUGIN_FILE ) );
        wp_enqueue_style( 'qmerce_modal' );

        wp_register_script( 'qmerce_metabox_scripts', plugins_url( '/public/js/metabox.js', QMERCE__PLUGIN_FILE ) );
        wp_enqueue_script( 'qmerce_metabox_scripts' );
        wp_localize_script( 'qmerce_metabox_scripts', 'configuration', $configuration);

        wp_register_script( 'qmerce_modal_scripts', plugins_url( '/public/js/modal.js', QMERCE__PLUGIN_FILE ) );
        wp_enqueue_script( 'qmerce_modal_scripts' );
        wp_localize_script( 'qmerce_modal_scripts', 'configuration', $configuration);

    }

    /**
     * Adds meta box for allowed post types
     * @param $postType
     */
    public function addMetaBox($postType)
    {
        $allowedPostTypes = $this->getPostTypes();

        if ( !in_array( $postType, $allowedPostTypes ) ) {
            return;
        }

        add_meta_box( 'qmerce_challenges', __( 'Apester Challenges', 'qmerce_text' ), array( $this, 'renderMetaBoxContent' ), $postType, 'advanced', 'high' );
    }

    /**
     * Retrieves persisted accessToken
     * @return string
     */
    private function getAccessToken()
    {
        $qmerceSettings = get_option( 'qmerce-settings-admin' );

        return $qmerceSettings['auth_token'];
    }

    /**
     * Retrieves available post types
     * @return array
     */
    private function getPostTypes() {
        $qmerceSettings = get_option( 'qmerce-settings-admin' );

        if ( !$qmerceSettings['post_types'] ) {
            return array( 'post', 'page' );
        }

        return $qmerceSettings['post_types'];

    }

    /**
     * Renders meta box to content.
     *
     * @param $post
     */
    public function renderMetaBoxContent($post)
    {
        $authToken = $this->getAccessToken();

        if ( !$authToken ) {
            echo '<p>Please go to <a href="options-general.php?page=qmerce-settings-admin">Apester Settings</a> and update your access token</p>';
            return;
        }

        echo '<iframe id="qmerce_meta_box_suggestions" src="' . esc_url( QMERCE_EDITOR_BASEURL . '/#/editor-suggestions/wordpress?access_token=' . urlencode( $authToken ) ) . '" width="100%" height="135" scrolling="no"></iframe>';
    }

    public function savePost($postId)
    {

    }
}

$qmerce_admin_box = new Qmerce_Admin_Box();
