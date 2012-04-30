<?php

add_shortcode('brightcove','brightcove_render_shortcode');

function brightcove_render_shortcode($atts) {
	GLOBAL $bcGlobalVariables;

	wp_enqueue_script( 'brightcove-script' );

	$defaults = array(
					'playerid' => '',
					'playerkey' => '',
					'playlistid' => '',
					'videoid' => '',
					'width' => $bcGlobalVariables['defaultWidth'],
					'height'  => $bcGlobalVariables['defaultHeight']
					);
	$combined_attr = shortcode_atts( $defaults, $atts );
	
	$width = sanitize_key( $combined_attr['width'] );        //Using key to allow for blanks
	$height = sanitize_key( $combined_attr['height'] );		//Using key to allow for blanks
	$playerid =	sanitize_key( $combined_attr['playerid'] );	
	$playerkey = sanitize_key( $combined_attr['playerkey'] );
	$videoid = sanitize_key( $combined_attr['videoid'] );
	$playlistid = sanitize_key( $combined_attr['playlistid'] );		

	$html = '<div style="display:none"></div>
	<object id="' . esc_attr( rand() ) .'" class="BrightcoveExperience">
  		<param name="bgcolor" value="#FFFFFF" />
  		<param name="wmode" value="transparent" />
  		<param name="width" value="' . esc_attr( $width ) . '" />
  		<param name="height" value="'. esc_attr( $height ) .'" />';
 	if ($playerid != '') {   
    	$html = $html . '<param name="playerID" value="'. esc_attr( $playerid ) .'" />';
  	}

  	if ($playerkey != '') {   
    	$html = $html . '<param name="playerKey" value="'. esc_attr( $playerkey ) .'"/>';
  	}
 	$html = $html .' <param name="isVid" value="true" />
  	<param name="isUI" value="true" />
  	<param name="dynamicStreaming" value="true" />';

  	if ($videoid != '')
  	{ 
    	$html = $html . '<param name="@videoPlayer" value="'.esc_attr( $videoid ) .'" />';
  	}
  	if ($playlistid != '')
  	{   
    	$html = $html . '<param name="@playlistTabs" value="'.esc_attr( $playlistid ).'" />';
    	$html = $html . '<param name="@videoList" value="'.esc_attr( $playlistid ).'" />';
    	$html = $html . '<param name="@playlistCombo" value="'.esc_attr( $playlistid ).'" />';
  	} 
	$html = $html . '</object>';

	return $html;
}


