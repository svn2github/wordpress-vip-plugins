<?php

include('Article.php');

class Livefyre_Site {
    private $id;
    private $domain;
    
    public function __construct($id, $domain) {
        $this->id = $id;
        $this->domain = $domain;
    }
    
    public function article($article_id) {
        return new Livefyre_Article($article_id, $this);
    }
    
    public function get_domain() {
        return $this->domain;
    }
    
    public function get_id() {
        return $this->id;
    }
}

?>