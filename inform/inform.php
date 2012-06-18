<?php
/***************************************************************************
 Plugin Name: Inform Tag
 Plugin URI: http://wordpressext.inform.com/extract/wpplugin/
 Version: 0.2.2
 Author: <a href="http://www.inform.com" target="_blank">Inform Technologies, Inc.</a>
 Description: Use <a href="http://www.inform.com" target="_blank">Inform</a>'s powerful categorization engine to tag your Wordpress content.
 ***************************************************************************/

if (!class_exists('inform_plugin')) {
	
	class inform_plugin {
		
		private $s_css = 'css/inform.css';
		private $s_delim_iab = 'IAB_TOPICS';
		private $s_delim_inform = 'INFORM_TAGS';
		private $s_delim_tag = '~';
		private $s_delim_tag_pair = '::';
		private $s_js = 'js/inform.js';
		private $s_search_prefix = 'www.inform.com/topic/';
		private $s_taxonomy = 'inform';
		
		/**
		 * init(): add action hooks
		 *
		 */
		
		public function init() {
			add_action('admin_init', array($this, 'admin_init'));
			add_action('admin_menu', array($this, 'admin_menu'));
			add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
			add_action('save_post', array($this, 'post_tags_save'), 1, 2);
			add_action('wp_ajax_inform_proxy', array($this, 'ajax_proxy'));
		}
		
		/**
		 * admin_init(): WP admin init action callback
		 *
		 */
		
		public function admin_init() {
			
			// set Inform auto-select setting default
			$s_setting = get_option('inform_tag_option');
			if (empty($s_setting)) {
				update_option('inform_tag_option', 'off');
			}
			
			// set IAB auto-select setting default
			$s_setting = get_option('inform_iab_tag_option');
			if (empty($s_setting)) {
				update_option('inform_iab_tag_option', 'on');
			}
			
			// save settings
			if (isset($_POST['action']) && $_POST['action'] === 'inform-options') {
				$this -> options_save();
			}
		}
		
		/**
		 * admin_menu(): WP admin menu action callback
		 *
		 * adds admin page components
		 *
		 */
		
		public function admin_menu() {
			
			// add admin page components
			add_meta_box($this -> s_taxonomy.'_metabox', '<strong class="inform-logo">Inform</strong> Article Curation Tool', array($this, 'metabox_render_inform'), 'post', 'normal', 'core');
			add_meta_box($this -> s_taxonomy.'_iab', 'IAB tags', array($this, 'metabox_render_iab'), 'post', 'side', 'default');
			
			// add options page
			add_options_page('Inform Tag Options', 'Inform Tag', 'manage_options', 'inform-options', array($this, 'options_page_render'));
		}
		
		/**
		 * admin_enqueue_scripts(): admin enqueue scriptsaction callback
		 *
		 * add required CSS and JS
		 *
		 */
		
		public function admin_enqueue_scripts($s_page) {
			// only needed on edit post page
			if (in_array($s_page, array('post.php', 'post-new.php'))) {
				wp_enqueue_style($this -> s_taxonomy.'-css', plugins_url($this -> s_css, __FILE__));
				wp_enqueue_script($this -> s_taxonomy.'-js', plugins_url($this -> s_js, __FILE__));
			}
		}
		
		/**
		 * ajax_proxy(): call proxy from admin AJAX
		 *
		 */
		
		public function ajax_proxy() {
			require dirname(__FILE__).'/proxy.php';
		}
		
		/**
		 * metabox_render_iab(): render iab tag meta box
		 *
		 */
		
		public function metabox_render_iab() {
			
			// get saved tags
			$a_iabs = isset($_GET['post']) ? $this -> tags($_GET['post'], 'iab', 0, TRUE) : array();
			
			?><div class="tagchecklist"><?php
				
				// show tags
				foreach ($a_iabs as $i => $a_tag) {
					?><span><a class="ntdelbutton">X</a>&nbsp;<?php echo esc_html($a_tag['label']); ?></span><?php
				}
				
			?></div><?php
			?><div id="inform-iabs" class="panel tags"><?php
				?><h4 class="title"><span class="inform-logo">Inform</span> suggested <a href="http://www.iab.net/" target="_blank">IAB</a> tags</h4><?php
				?><p class="empty">Process post to see suggestions</p><?php
			?></div><?php
		}
		
		/**
		 * metabox_render_inform(): render Inform ACT metabox
		 *
		 * contains nonce and hidden textarea inputs to store selected Inform/IAB tags
		 *
		 */
		
		public function metabox_render_inform() {
			
			?><div class="hide-if-js"><?php
			?><label style="vertical-align:top">Inform tags:</label> <?php
			?><textarea name="<?php echo $this -> s_taxonomy; ?>-tags"><?php
			
			// inform tags
			$a_tags = isset($_GET['post']) ? $this -> tags($_GET['post'], 'inform', 0, TRUE) : array();
			foreach ($a_tags as $i => $a_tag) {
				$a_tags[$i] = $a_tag['label'].$this -> s_delim_tag_pair.$a_tag['rel'];
			}
			echo esc_html(implode($this -> s_delim_tag, $a_tags));
			
			?></textarea><?php
			?>&nbsp;<?php
			?><label style="vertical-align:top">IAB tags:</label> <?php
			?><textarea name="<?php echo $this -> s_taxonomy; ?>-iabs"><?php
			
			// inform tags
			$a_tags = isset($_GET['post']) ? $this -> tags($_GET['post'], 'iab', 0, TRUE) : array();
			foreach ($a_tags as $i => $a_tag) {
				$a_tags[$i] = $a_tag['label'].$this -> s_delim_tag_pair.$a_tag['rel'];
			}
			echo esc_html(implode($this -> s_delim_tag, $a_tags));
			
			?></textarea><?php
			?></div><?php
			
			wp_nonce_field($this -> s_taxonomy, $this -> s_taxonomy.'_nonce', FALSE);
			
			?><p><input type="button" value="Get tags" class="button"/></p><?php
			?><script type="text/javascript">
				jQuery(document).ready(function () {
					'use strict';
					
					var informTagger = new InformTagger();
					
					// Inform settings
					informTagger.articles(10);
					informTagger.blogs(5);
					informTagger.iabDelim('<?php echo $this -> s_delim_iab; ?>');
					informTagger.iabTagsOn(<?php echo get_option('inform_iab_tag_option') === 'on' ? 'true' : 'false'; ?>);
					informTagger.informDelim('<?php echo $this -> s_delim_inform; ?>');
					informTagger.informTagsOn(<?php echo get_option('inform_tag_option') === 'on' ? 'true' : 'false'; ?>);
					informTagger.pairDelim('<?php echo $this -> s_delim_tag_pair; ?>');
					informTagger.searchPrefix('<?php echo $this -> s_search_prefix; ?>');
					informTagger.tagDelim('<?php echo $this -> s_delim_tag; ?>');
					informTagger.videos(5);
					
					// WP settings
					informTagger.wpAjaxProxy('inform_proxy');
					informTagger.wpMetaboxSelector('.postbox');
					informTagger.wpMetaboxContentsSelector('.inside');
					informTagger.wpTagChecklistSelector('.tagchecklist');
					informTagger.wpTagChecklistItemSelector('span');
					informTagger.wpUpdateTags(function () {
						tagBox.flushTags('.tagsdiv');
					});
					
					// DOM refs
					informTagger.btnProcess(jQuery('#inform_metabox :input[type = "button"]'));
					informTagger.inputTagsIab(jQuery('#inform_metabox :input[name $= "iabs"]'));
					informTagger.inputTagsInform(jQuery('#inform_metabox :input[name $= "tags"]'));
					informTagger.inputTagsWp(jQuery('#tax-input-post_tag'));
					informTagger.metaboxIab(jQuery('#inform_iab .inside'));
					informTagger.metaboxTags(jQuery('#tagsdiv-post_tag .inside'));
					informTagger.tagsIab(jQuery('#inform_iab #inform-iabs'));
					
					// go
					informTagger.init();
				});
			</script><?php
		}
		
		/**
		 * options_page_render(): render Inform settings page
		 *
		 */
		
		public function options_page_render() {
			
			// restrict
			if (!current_user_can('manage_options')) {
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
			
			// feedback
			if (!empty($_POST)) {
				?><div id="message" class="updated fade"><p><strong><?php _e('Options saved.'); ?></strong></p></div><?php
			}
			
			?><div class="wrap"><?php
			
			screen_icon();
			
			?><h2><?php _e('Inform Admin Options'); ?></h2>
			<form id="inform-options" method="post"><?php
				
				wp_nonce_field($this -> s_taxonomy, $this -> s_taxonomy.'_nonce', FALSE);
				
				?><input type="hidden" name="action" value="inform-options" />
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e('Auto-select Inform tags'); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e('Auto-select Inform tags'); ?></span></legend>
								<label><input type="radio" name="inform_tag_option" value="on"<?php echo get_option('inform_tag_option') === 'on' ? ' checked="checked"' : NULL; ?> /> <?php _e('On'); ?></label> <label><input type="radio" name="inform_tag_option" value="off"<?php echo get_option('inform_tag_option') !== 'on' ? ' checked="checked"' : NULL; ?> /> <?php _e('Off'); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Auto-select IAB tags'); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e('Auto-select IAB tags'); ?></span></legend>
								<label><input type="radio" name="inform_iab_tag_option" value="on"<?php echo get_option('inform_iab_tag_option') === 'on' ? ' checked="checked"' : NULL; ?> /> <?php _e('On'); ?></label> <label><input type="radio" name="inform_iab_tag_option" value="off"<?php echo get_option('inform_iab_tag_option') !== 'on' ? ' checked="checked"' : NULL; ?> /> <?php _e('Off'); ?></label>
							</fieldset>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input class="button-primary" type="submit" name="submit" value="<?php _e('Save Changes'); ?>" />
				</p>
			</form>
			</div><?php
		}
		
		/**
		 * options_save(): save options
		 *
		 */
		
		public function options_save() {
			
			// check nonce, permissions
			if (!wp_verify_nonce($_POST[$this -> s_taxonomy.'_nonce'], $this -> s_taxonomy) || !current_user_can('manage_options')) {
				return FALSE;
			}
			
			// update Inform auto-select setting
			if (isset($_POST['inform_tag_option'])) {
				update_option('inform_tag_option', $_POST['inform_tag_option'] === 'on' ? 'on' : 'off');
			}
			
			// update IAB auto-select setting
			if (isset($_POST['inform_iab_tag_option'])) {
				update_option('inform_iab_tag_option', $_POST['inform_iab_tag_option'] === 'on' ? 'on' : 'off');
			}
		}
		
		/**
		 * post_tags_save(): save Inform and IAB tags
		 *
		 * @param number $i_post_id id of post being edited
		 *
		 */
		
		public function post_tags_save($i_post_id) {
			
			global $post;
			
			// check nonce
			if (!isset($_POST[$this -> s_taxonomy.'_nonce']) ||
				!wp_verify_nonce($_POST[$this -> s_taxonomy.'_nonce'], $this -> s_taxonomy) ||
				!current_user_can('edit_post', $i_post_id) ||
				$post -> post_type == 'revision') {
				return FALSE;
			}
			
			// iab
			$this -> tags_save($i_post_id, 'iab', $_POST[$this -> s_taxonomy.'-iabs']);
			
			// inform
			$this -> tags_save($i_post_id, 'inform', $_POST[$this -> s_taxonomy.'-tags']);
		}
		
		/**
		 * tags(): get tags
		 *
		 * @param number $i_post_id             id of post
		 * @param string $s_type                type of tag (iab|inform)
		 * @param number $i_relevance_threshold minimum relevance score
		 * @param bool   $b_include_scores      include relevance scores in result array
		 *
		 * @return array tags
		 *
		 */
		
		function tags($i_post_id = NULL, $s_type = 'inform', $i_relevance_threshold = 0, $b_include_scores = FALSE) {
			
			global $post;
			
			// attempt to determine id
			if (!$i_post_id) {
				$i_post_id = get_the_ID();
				if (!$i_post_id && isset($post -> ID)) {
					$i_post_id = $post -> ID;
				}
				if (!$i_post_id) {
					return FALSE;
				}
			}
			
			// get tags
			$a_tags_saved = get_post_meta($i_post_id, '_'.$this -> s_taxonomy.'_'.$s_type, TRUE);
			
			// filter by relevance threshold
			$a_tags = array();
			if (is_array($a_tags_saved)) {
				foreach ($a_tags_saved as $a_tag) {
					if ($a_tag['rel'] >= $i_relevance_threshold) {
						$a_tags[] = $b_include_scores ? $a_tag : $a_tag['label'];
					}
				}
			}
			
			return $a_tags;
		}
		
		/**
		 * tags_save(): save tags
		 *
		 * @param number $i_post_id id of post being edited
		 * @param string $s_type    type of tag to save (iab|inform)
		 * @param string $s_input   string of delimited tags
		 *
		 */
		
		public function tags_save($i_post_id, $s_type, $s_input) {
			
			$a_tags = array();
			$s_tags_in = trim(stripslashes($s_input));
			
			if (!empty($s_tags_in)) {
				$a_tags_in = explode($this -> s_delim_tag, $s_tags_in);
				foreach ($a_tags_in as $s_pair) {
					$a_pair = explode($this -> s_delim_tag_pair, $s_pair);
					$a_tags[] = array('label' => $a_pair[0],
										'rel' => (int) $a_pair[1]);
				}
			}
			
			update_post_meta($i_post_id, '_'.$this -> s_taxonomy.'_'.$s_type, $a_tags);
		}
	}
	
	// instantiate plug-in
	$inform_plugin = new inform_plugin();
	$inform_plugin -> init();
	
	// API (localized)
	
	function inform_tags($i_post_id = NULL, $i_relevance_threshold = 0, $b_include_scores = FALSE) {
		global $inform_plugin;
		return $inform_plugin -> tags($i_post_id, 'inform', $i_relevance_threshold, $b_include_scores);
	}
	
	function inform_iabs($i_post_id = NULL, $i_relevance_threshold = 0, $b_include_scores = FALSE) {
		global $inform_plugin;
		return $inform_plugin -> tags($i_post_id, 'iab', $i_relevance_threshold, $b_include_scores);
	}
}

?>
