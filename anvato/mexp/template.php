<?php

/**
 * Backbone templates for various views for the Anvato service.
 */
class MEXP_Anvato_Template extends MEXP_Template
{

	private $anv_settings = array();

	public function __construct()
	{
		$this->anv_settings = Anvato_Settings::get_mcp_options();
	}

	/**
	 * Outputs the Backbone template for an item within search results.
	 *
	 * @param string $id  The template ID.
	 * @param string $tab The tab ID.
	 */
	public function item($id, $tab)
	{
	?>
		<div id="mexp-item-<?php echo esc_attr($tab); ?>-{{ data.id }}" class="mexp-item-area 
			<# if ( data.meta.type == 'playlist' ) {#>anvato-playlist-item<# }
			else { if ( data.meta.type == 'live'){ #>anvato-channel-item<# }
			else { #>anvato-video-item<#}}#>" data-id="{{ data.id }}">
			<div class="mexp-item-container clearfix">
	                                
				<div class="mexp-item-thumb" style="background-image: url({{ data.thumbnail }})"
					class="thickbox">
					<img src="<?php echo esc_url(ANVATO_URL . 'img/play.png') ?>"
						onclick="anv_preview('<?php echo esc_js($this->anv_settings['mcp']['id']) ?>',
						'{{ data.id }}', '{{ data.meta.type }}', '{{data.meta.accesskey}}');"
					/>
					<# if ( data.meta.duration  ) { #>
						<span>{{ data.meta.duration }}</span><# 
					} #>
				</div>
	                                 
				<div class="mexp-item-main">
					<div class="mexp-item-content">
						<span class="anv-title">{{ data.content }}</span>
							<# if(data.meta.description) { #>
								<span class="anv-desc">{{ data.meta.description }}</span><# 
							}#>
					</div>
					<# if ( data.meta.video_count ) { #>
						<div class="mexp-item-meta">
							<span>
							<#if ( data.meta.video_count == 0 ) {#>
								No Video in Playlist
							<#}
							else{
								#>{{ data.meta.video_count }} video<#if( data.meta.video_count >1 ){#>s
								<#}#> in playlist<#
							}#>
							</span>
						</div>
					<# } #>
					<# if ( data.meta.category  ) { #>
						<div class="mexp-item-meta">
							<span>Category:</span>{{ data.meta.category }}
						</div>
					<# } #>
					<# if ( data.meta.category  ) { #>
						<div class="mexp-item-meta">
						<span>Published:</span>{{ data.date }}
						</div>
						<div class="mexp-item-meta-dfp-flags" align="left"
							style="<# if ( data.meta.type === 'playlist' ) {#>
								display: none<# }#>; padding-top:5px" >
							<span style="padding-right: 2px">Disable Pre-roll:</span>
							<span>
								<input id="dfp_flag_{{ data.id }}" type="checkbox" value="yes"/>
							</span>
						</div>					
					<# } #>
				</div>
			</div>
			<a href="#" id="mexp-check-{{ data.id }}" data-id="{{ data.embed_id }}"
				class="check" title="<?php esc_attr_e('Deselect', 'mexp'); ?>">
				<div class="media-modal-icon"></div>
			</a>
		</div>
	<?php
	}

	/**
	 * Outputs the Backbone template for a select item's thumbnail in the footer toolbar.
	 *
	 * @param string $id The template ID.
	 */
	public function thumbnail($id) {
		if($id!==''):?>
		<div class="mexp-item-thumb">
			<img src="{{ data.thumbnail }}">
		</div>
		<?php
                endif;
	}

	/**
	 * Outputs the Backbone template for a tab's search fields.
	 *
	 * @param string $id  The template ID.
	 * @param string $tab The tab ID.
	 */
	public function search($id, $tab) 
	{   
		$pref = array('type'=>'vod', 'station' => 0);
		if( isset($_COOKIE['anv_user_preferences']) )
		{
			$pref = json_decode( stripslashes_deep(sanitize_text_field($_COOKIE['anv_user_preferences'])) , TRUE );
		}
	?>
		<form action="#" class="mexp-toolbar-container clearfix tab-all" id='anv_search_form'>
			<input type="text" name="q" value="{{ data.params.q }}"
				class="mexp-input-text mexp-input-search" 
				size="40" placeholder="<?php esc_attr_e('Search for videos', 'mexp'); ?>"
			>
			<select name="station">
				<option value=''>Select Station</option>
				<?php
					if ( !empty( $this->anv_settings ) && !empty( $this->anv_settings['owners'] ) ) {
						usort( $this->anv_settings['owners'], array( $this, 'sortbylabel' ) );

						foreach( $this->anv_settings['owners'] as $item ) {
							echo '<option value="' . esc_attr( $item['id'] ) . '" ' .
								selected( $item['label'], $pref['station'], false ) .
								'>' . esc_html( $item['label'] ) . '</option>';
						}
					} // if not-empty anv_serrings owners
				?>
			</select>
			<select onchange="anv_type_select(this)" name='type'>
				<option <?php echo ($pref['type'] === 'vod' ? 'selected' : '') ?>
					value='vod'>Video clips</option>
				<option <?php echo ($pref['type'] === 'playlist' ? 'selected' : '') ?>
						value='playlist'>Playlists</option>
				<option <?php echo ($pref['type'] === 'live' ? 'selected' : '') ?>
					value='live'>Live channel</option>
			</select>
			<input class="button button-large" type="submit" id="mexp-button"
				value="<?php esc_attr_e('Search', 'mexp'); ?>"
			>

			<div class="spinner"></div>
			<div class="anv-logo">
				<img src="<?php echo esc_url(ANVATO_URL . 'img/logo_small.png') ?>"
					alt="<?php esc_attr_e('Anvato Video Plugin', ANVATO_DOMAIN_SLUG); ?>" />
			</div>
		</form>
	<?php
	}
        
	function sortbylabel($a, $b)
	{
		return strcmp($a['label'],$b['label']);
	}

}