<?php
/*
Plugin Name: Advanced Excerpt
Plugin URI: http://sparepencil.com/code/advanced-excerpt/
Description: Several improvements over WP's default excerpt. The size of the excerpt can be limited using character or word count, and HTML markup is not removed.
Version: 0.1.2
Author: Bas van Doren
Author URI: http://sparepencil.com/

Copyright 2007 Bas van Doren

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if(!class_exists('AdvancedExcerpt')) :
class AdvancedExcerpt
{
	var $name;
	
	function AdvancedExcerpt()
	{
		$this->name = strtolower(get_class($this));
		register_activation_hook(__FILE__, array(&$this, 'install'));
		register_deactivation_hook(__FILE__, array(&$this, 'uninstall'));
		
		add_action('admin_menu', array(&$this, 'add_pages'));
		
		// Replace the default filter (see /wp-includes/default-filters.php)
		remove_filter('get_the_excerpt', 'wp_trim_excerpt');
		add_filter('get_the_excerpt', array(&$this, 'filter'));
	}
	
	function __construct()
	{
		self::AdvancedExcerpt();
	}
	
	function filter($text, $length = null, $use_words = null, $ellipsis = null, $allowed_tags = null)
	{
		global $id, $post;
		
		// Only make the excerpt if it does not exist
		if('' == $text) {
			$length = (!is_null($length)) ? (int) $length : get_option($this->name . '_length');
			$use_words = (!is_null($use_words)) ? (int) (bool) $use_words : get_option($this->name . '_use_words');
			$ellipsis = (!is_null($ellipsis)) ? $ellipsis : get_option($this->name . '_ellipsis');
			
			$allowed_tags = (is_array($allowed_tags)) ? $allowed_tags : get_option($this->name . '_allowed_tags');
			$allowed_tags = implode('><', $allowed_tags);
			$allowed_tags = '<' . $allowed_tags . '>';
			
			$text = get_the_content('');
			
			// T'is important
			$text = apply_filters('the_content', $text);
			$text = str_replace(']]>', ']]&gt;', $text);
			$text = strip_tags($text, $allowed_tags);
			
			if(1 == $use_words)
			{
				// Count words, not HTML tags
				// Sometimes, the solution is easy
				if($length > count(preg_split('/[\s]+/', strip_tags($text), -1)))
					return $text;
				
				// Now we start counting
				$text_bits = preg_split('/([\s]+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
				$in_tag = false;
				$n_words = 0;
				$text = '';
				foreach($text_bits as $chunk)
				{
					// Determine whether a tag is opened (and not immediately closed) in this chunk
					if(0 < preg_match('/<[^>]*$/s', $chunk))
						$in_tag = true;
					elseif(0 < preg_match('/>[^<]*$/s', $chunk))
						$in_tag = false;
					
					// This should check if there is a word before the tag. I haven't thought of a reliable way to do this, though, so it's left out for now.
					//if($in_tag && substr($chunk, 0, 1) != '<')
						//$n_words++;
					
					// Is there a word?
					if(!$in_tag && '' != trim($chunk) && substr($chunk, -1, 1) != '>')
						$n_words++;
					
					$text .= $chunk;
					
					if($n_words >= $length && !$in_tag)
						break;
				}
				$text = $text . $ellipsis;
			}
			else
			{
				// Count characters, not whitespace, not those belonging to HTML tags
				// Sometimes, the solution is easy
				if($length > strlen(strip_tags($text)))
					return $text;
				
				$n_chars = 0;
				for($i = 0; ($n_chars < $length && $i < $length) || $in_tag; $i++)
				{
					// Is the character worth counting (ie. not part of an HTML tag)
					if(substr($text, $i, 1) == '<')
						$in_tag = true;
					elseif(substr($text, $i, 1) == '>')
						$in_tag = false;
					elseif(!$in_tag && '' != trim(substr($text, $i, 1)))
						$n_chars++;
				}
				$text = substr($text, 0, $i) . $ellipsis;
			}
			$text = force_balance_tags($text);
		}
		return $text;
	}
	
	function update_options()
	{
		$length = (int) $_POST[$this->name . '_length'];
		$use_words = ('on' == $_POST[$this->name . '_use_words']) ? 1 : 0 ;
		
		$ellipsis = (get_magic_quotes_gpc() == 1) ? stripslashes($_POST[$this->name . '_ellipsis']) : $_POST[$this->name . '_ellipsis'];
		$ellipsis = $ellipsis;
		
		$allowed_tags = (array) $_POST[$this->name . '_allowed_tags'];
		
		update_option($this->name . '_length', $length);
		update_option($this->name . '_use_words', $use_words);
		update_option($this->name . '_ellipsis', $ellipsis);
		update_option($this->name . '_allowed_tags', $allowed_tags);
	?>
	<div id="message" class="updated fade"><p>Options saved.</p></div>
	<?php
	}
	
	function page_options()
	{
		global $allowedposttags;
		
		if ('POST' == $_SERVER['REQUEST_METHOD'])
		{
			check_admin_referer($this->name . '_update_options');
			$this->update_options();
		}
		
		$length = get_option($this->name . '_length');
		$use_words = get_option($this->name . '_use_words');
		$ellipsis = htmlentities(get_option($this->name . '_ellipsis'));
		$allowed_tags = get_option($this->name . '_allowed_tags');
?>
<div class="wrap">
	<h2>Advanced Excerpt Options</h2>
	<form method="post" action="">
	<?php
		if ( function_exists('wp_nonce_field') )
			wp_nonce_field($this->name . '_update_options'); ?>
		
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Excerpt Length:</th>
				<td>
					<input name="<?php echo $this->name ?>_length" type="text" id="<?php echo $this->name ?>_length" value="<?php echo $length; ?>" size="2" />
					<input name="<?php echo $this->name ?>_use_words" type="checkbox" id="<?php echo $this->name ?>_use_words" value="on" <?php echo (1 == $use_words) ? 'checked="checked" ': ''; ?>/> Use words?
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Ellipsis:</th>
				<td>
					<input name="<?php echo $this->name ?>_ellipsis" type="text" id="<?php echo $this->name ?>_ellipsis" value="<?php echo $ellipsis; ?>" size="5" /> (use <a href="http://www.w3schools.com/tags/ref_entities.asp">HTML entities</a>)
					<br />
					Will substitute the part of the post that is omitted in the excerpt.
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Keep Markup:</th>
				<td>
					<table>
<?php
		$i = 0;
		foreach($allowedposttags as $tag => $spec) :
			if(1 == $i / 4) : ?><tr><?php endif;
			$i++;
		?>
					<td><input name="<?php echo $this->name ?>_allowed_tags[]" type="checkbox" id="<?php echo $this->name ?>_allow_<?php echo $tag; ?>" value="<?php echo $tag; ?>" <?php echo (in_array($tag, $allowed_tags)) ? 'checked="checked" ': ''; ?>/> <?php echo $tag; ?></td><?php if(1 == $i / 4) : $i = 0; ?></tr><?php endif;?>
<?php
		endforeach;
		if(1 != $i / 4) : ?><td colspan="<?php echo (4 - $i); ?>">&nbsp;</td></tr><?php endif;?>
					</table>
				</td>
			</tr>
		</table>
		<p class="submit"><input type="submit" name="Submit" value="Save Changes" /></p>
	</form>
</div>
	<?php
	}
	
	function add_pages()
	{
		add_options_page('Advanced Excerpt Options', 'Excerpt', 'manage_options', 'options-' . $this->name, array(&$this, 'page_options'));
	}
	
	function install()
	{
		global $allowedposttags;
		foreach($allowedposttags as $tag => $spec)
			$allowed_tags[] = $tag;
		add_option($this->name . '_length', 40);
		add_option($this->name . '_use_words', 1);
		add_option($this->name . '_ellipsis', '&hellip;');
		add_option($this->name . '_allowed_tags', $allowed_tags);
	}
	
	function uninstall()
	{
		delete_option($this->name . '_length');
		delete_option($this->name . '_use_words');
		delete_option($this->name . '_ellipsis');
		delete_option($this->name . '_allowed_tags');
	}
}

$advancedexcerpt = new AdvancedExcerpt();
endif;
