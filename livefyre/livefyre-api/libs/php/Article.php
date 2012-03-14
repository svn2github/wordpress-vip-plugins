<?php

include("Conversation.php");

class Livefyre_Article {
    private $id;
    private $site;
    
    public function __construct($id, $site) {
        $this->id = $id;
        $this->site = $site;
    }
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_site() {
        return $this->site;
    }
    
    public function conversation() {
        return new Livefyre_Conversation(null, $this);
    }
}

?>