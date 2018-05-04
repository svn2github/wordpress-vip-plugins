<?php

class Skyword_Shortcode {

    function __construct() {
        add_shortcode('cf', array($this, 'customfields_shortcode'));
        add_shortcode('skyword_tracking', array(
            $this,
            'skyword_tracking'
        ));
        add_shortcode('skyword_iframe', array(
            $this,
            'skyword_iframe'
        ));
    }

    function customfields_shortcode($atts, $text) {
        global $post;

        return get_post_meta($post->ID, $text, true);
    }

    function skyword_tracking($atts) {
        global $post;

        if (!isset($atts['id'])) {
            $atts['id'] = get_post_meta($post->ID, 'skyword_content_id', true);
        }
        return "<script async='' type='text/javascript' src='//tracking.skyword.com/tracker.js?contentId={$atts['id']}'></script>";
    }

    private function checkSource($val) {
        try {
            $validsrc = [
                "facebook.com",
                "www.facebook.com",
                "instagram.com",
                "www.instagram.com",
                "youtube.com",
                "www.youtube.com"
            ];
            $parsedurl = parse_url($val)["host"];
            if (in_array($parsedurl, $validsrc, true)) {
                return true;
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * Creates a short code such as:
     * [skyword_iframe id="id" class="...classes" style="...styles" src="https://www.instagram.com/p/key/embed/captioned/" height="745" width="658"]
     *
     * Bail and return an empty string if the source isn't valid.
     *
     * @param $atts
     * @return string
     */
    function skyword_iframe($atts) {
        $validattrs = ["src", "max-height", "max-width", "height", "width", "frameborder", "class", "id", "style"];
        $iframeattrs = "";
        $validsource = false;
        foreach ($atts as $k => $v) {
            if ("src" === $k) {
                $validsource = $this->checkSource($v);
                if (false === $validsource) {
                    break;
                }
            }
            if (in_array($k, $validattrs, true) && isset($v)) {
                $iframeattrs .= " " . $k . "=\"" . $v . "\"";
            }
        }
        return (true === $validsource) ? "<iframe $iframeattrs ></iframe>" : "";
    }
}

global $custom_shortcodes;
$custom_shortcodes = new Skyword_Shortcode;
