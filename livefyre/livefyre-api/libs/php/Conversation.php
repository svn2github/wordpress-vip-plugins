<?php

class Livefyre_Conversation {
    private $id;
    private $article;
    private $delegates;
    
    public function __construct( $conv_id = null, $article = null ) {
        $this->id = $conv_id;
        $this->article = $article;
        $this->delegates = array();
    }
    
    public function add_js_delegate( $delegate_name, $code ) {
        $this->delegates[ $delegate_name ] = $code;
    }
    
    public function render_js_delegates( ) {
        $str_out = '';
        if ( $this->delegates ) {
            $str_out = "var livefyreConvDelegates = {\n";
            foreach ($this->delegates as $handler => $code) {
                $str_out .= "    handle_$handler: " . $code . ", \n";
            }
            $str_out .= "}\nLF.ready( function() { LF.Dispatcher.addListener(livefyreConvDelegates); } )";
        }
        return $str_out;
    }

    public function to_initjs( $user = null, $display_name = null, $backplane = false ) {
        // When called, this will render all delegates added thru add_js_delegate
        $profile_domain = $this->article->get_site()->get_domain()->get_host();
        $config = array(
            'site_id' => $this->article->get_site()->get_id(),
            'article_id' => $this->article->get_id()
        );
        $builds_token = true;
        if ( $profile_domain != LF_DEFAULT_PROFILE_DOMAIN ) {
            $config[ 'domain' ] = $profile_domain;
        } else {
            // nobody but Livefyre can build tokens for livefyre.com profiles
            $builds_token = false;
        }
        if ( $backplane ) {
            $add_backplane = 'if ( typeof(Backplane) != \'undefined\' ) { lf_config.backplane = Backplane; };';
        } else {
            $add_backplane = '';
        }
        $login_js = '';
        if ( $user && $builds_token ) {
            $login_json = array( 'token' => $user->token( ), 'profile' => array('display_name' => $display_name) );
            $login_json_str = json_encode( $login_json );
            $login_js = "LF.ready( function() {LF.login($login_json_str);} );";
        }
        return '<script type="text/javascript" src="http://zor.' . LF_DEFAULT_TLD . '/wjs/v1.0/javascripts/livefyre_init.js"></script>
        <script type="text/javascript">
            var lf_config = ' . json_encode( $config ) . ';
            ' . $add_backplane . '
            var conv = LF(lf_config);
            ' . $login_js . '
            ' . $this->render_js_delegates() . '
        </script>';
    }

    public function to_html( ) {
        assert('$this->article != null /* Article is necessary to get HTML */');
        $site_id = $this->article->get_site()->get_id();
        $article_id = $this->article->get_id();
        $domain = $this->article->get_site()->get_domain()->get_host();
        return file_get_contents("http://bootstrap.$domain/api/v1.1/public/bootstrap/html/$site_id/".urlencode(base64_encode($article_id)).".html");
    }
}

?>
