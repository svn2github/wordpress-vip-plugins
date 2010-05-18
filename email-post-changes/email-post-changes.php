<?php

/*
Plugin Name: Email Post Changes
Description: Whenever a change to a post or page is made, those changes are emailed to the blog's admin.
Plugin URI: http://wordpress.org/extend/plugins/email-post-changes/
Version: 0.5
Author: Michael D Adams
Author URI: http://blogwaffe.com/
*/

class Email_Post_Changes {
	var $defaults;

	var $left_post;
	var $right_post;

	var $text_diff;

	const ADMIN_PAGE = 'email_post_changes';
	const OPTION_GROUP = 'email_post_changes';
	const OPTION = 'email_post_changes';

	function &init() {
		static $instance = null;

		if ( $instance )
			return $instance;

		$class = __CLASS__;
		$instance = new $class;
	}

	function __construct() {
		$this->defaults = array(
			'emails' => array( get_option( 'admin_email' ) ),
			'post_types' => array( 'post', 'page' )
		);

		add_action( 'wp_insert_post', array( &$this, 'wp_insert_post' ), 10, 2 );
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
	}

	function get_post_types() {
		$post_types = get_post_types();
		if ( false !== $pos = array_search( 'revision', $post_types ) )
			unset( $post_types[$pos] );
		return $post_types;
	}

	function get_options( $just_defaults = false ) {
		if ( $just_defaults )
			return $this->defaults;

		return get_option( 'email_post_changes', $this->defaults );
	}

	// The meat of the plugin
	function wp_insert_post( $post_id, $post ) {
		$options = $this->get_options();

		if ( 'revision' == $post->post_type ) { // Revision is saved first
			if ( wp_is_post_autosave( $post ) )
				return;
			$this->left_post = $post;
		} elseif ( !empty( $this->left_post ) && $this->left_post->post_parent == $post->ID ) { // Then new post
			if ( !in_array( $post->post_type, $options['post_types'] ) )
				return;
			$this->right_post = $post;
		}

		if ( !$this->left_post || !$this->right_post )
			return;

		$html_diffs = array();
		$text_diffs = array();
		$identical = true;
		foreach ( _wp_post_revision_fields() as $field => $field_title ) {
			$left = apply_filters( "_wp_post_revision_field_$field", $this->left_post->$field, $field );
			$right = apply_filters( "_wp_post_revision_field_$field", $this->right_post->$field, $field );

			if ( !$diff = wp_text_diff( $left, $right ) )
				continue;
			$html_diffs[$field_title] = $diff;

			$left  = normalize_whitespace( $left );
			$right = normalize_whitespace( $right );

			$left_lines  = split( "\n", $left );
			$right_lines = split( "\n", $right );

			require_once( dirname( __FILE__ ) . '/unified.php' );

			$text_diff = new Text_Diff( $left_lines, $right_lines );
			$renderer  = new Text_Diff_Renderer_unified();
			$text_diffs[$field_title] = $renderer->render($text_diff);

			$identical = false;
		}

		if ( $identical )
			return;

		// Grab the meta data
		$the_author = get_the_author_meta( 'display_name', $this->left_post->post_author ); // The revision
		$the_title = get_the_title( $this->right_post->ID ); // New title (may be same as old title)
		$right_date = new DateTime( $this->right_post->post_modified_gmt, new DateTimeZone( 'UTC' ) ); // Modified time
		$the_date = $right_date->format( 'j F, Y \a\t G:i \U\T\C' );
		$the_permalink = clean_url( get_permalink( $this->right_post->ID ) );
		$the_edit_link = clean_url( get_edit_post_link( $this->right_post->ID ) );

		$left_title = __( 'Revision' );
		$right_title = sprintf( __( 'Current %s' ), $post_type = ucfirst( $this->right_post->post_type ) );

		$head_sprintf = __( '%s made the following changes to the %s %s on %s' );


		// HTML
		$html_diff_head  = '<h2>' . sprintf( __( '%s changed' ), $post_type ) . "</h2>\n";
		$html_diff_head .= '<p>' . sprintf( $head_sprintf,
			esc_html( $the_author ),
			sprintf( _x( '&#8220;%s&#8221; [%s]', '1 = link, 2 = "edit"' ),
				"<a href='$the_permalink'>" . esc_html( $the_title ) . '</a>',
				"<a href='$the_edit_link'>" . __( 'edit' ) . '</a>'
			),
			$this->right_post->post_type,
			$the_date
		) . "</p>\n\n";

		$html_diff_head .= "<table style='width: 100%; border-collapse: collapse; border: none;'><tr>\n";
		$html_diff_head .= "<td style='width: 50%; padding: 0; margin: 0;'>" . esc_html( $left_title ) . ' @ ' . esc_html( $this->left_post->post_date_gmt ) . "</td>\n";
		$html_diff_head .= "<td style='width: 50%; padding: 0; margin: 0;'>" . esc_html( $right_title ) . ' @ ' . esc_html( $this->right_post->post_modified_gmt ) . "</td>\n";
		$html_diff_head .= "</tr></table>\n\n";

		$html_diff = '';
		foreach ( $html_diffs as $field_title => $diff ) {
			$html_diff .= '<h3>' . esc_html( $field_title ) . "</h3>\n";
			$html_diff .= "$diff\n\n";
		}

		$html_diff = rtrim( $html_diff );

		// Replace classes with inline style
		$html_diff = str_replace( "class='diff'", 'style="width: 100%; border-collapse: collapse; border: none; white-space: pre-wrap; word-wrap: break-word; font-family: Consolas,Monaco,Courier,monospace;"', $html_diff );
		$html_diff = preg_replace( '#<col[^>]+/?>#i', '', $html_diff );
		$html_diff = str_replace( "class='diff-deletedline'", 'style="padding: 5px; width: 50%; background-color: #fdd;"', $html_diff );
		$html_diff = str_replace( "class='diff-addedline'", 'style="padding: 5px; width: 50%; background-color: #dfd;"', $html_diff );
		$html_diff = str_replace( "class='diff-context'", 'style="padding: 5px; width: 50%;"', $html_diff );
		$html_diff = str_replace( '<td>', '<td style="padding: 5px;">', $html_diff );
		$html_diff = str_replace( '<del>', '<del style="text-decoration: none; background-color: #f99;">', $html_diff );
		$html_diff = str_replace( '<ins>', '<ins style="text-decoration: none; background-color: #9f9;">', $html_diff );
		$html_diff = str_replace( array( '</td>', '</tr>', '</tbody>' ), array( "</td>\n", "</tr>\n", "</tbody>\n" ), $html_diff );

		$html_diff = $html_diff_head . $html_diff;


		// Refactor some of the meta data for TEXT
		$length = max( strlen( $left_title ), strlen( $right_title ) );
		$left_title = str_pad( $left_title, $length + 2 );
		$right_title = str_pad( $right_title, $length + 2 );

		// TEXT
		$text_diff  = sprintf( $head_sprintf, $the_author, '"' . $the_title . '"', $this->right_post->post_type, $the_date ) . "\n";
		$text_diff .= "URL:  $the_permalink\n";
		$text_diff .= "Edit: $the_edit_link\n\n";

		foreach ( $text_diffs as $field_title => $diff ) {
			$text_diff .= "$field_title\n";
			$text_diff .= "===================================================================\n";
			$text_diff .= "--- $left_title	({$this->left_post->post_date_gmt})\n";
			$text_diff .= "+++ $right_title	({$this->right_post->post_modified_gmt})\n";
			$text_diff .= "$diff\n\n";
		}

		$this->text_diff = $text_diff = rtrim( $text_diff );


		// Send email
		$charset = apply_filters( 'wp_mail_charset', get_option( 'blog_charset' ) );
		$blogname = html_entity_decode( get_option( 'blogname' ), ENT_QUOTES, $charset );
		$title = html_entity_decode( $the_title, ENT_QUOTES, $charset );

		add_action( 'phpmailer_init', array( &$this, 'phpmailer_init_once' ) );

		wp_mail(
			null, // see hack in ::phpmailer_init_once()
			sprintf( __( '[%s] %s changed: %s' ), $blogname, $post_type, $title ),
			$html_diff
		);
	}

	/* Email hook */
	function phpmailer_init_once( &$phpmailer ) {
		remove_action( 'phpmailer_init', array( &$this, 'phpmailer_init_once' ) );
		$phpmailer->AltBody = $this->text_diff;

		$phpmailer->to = array(); // Hack

		$options = $this->get_options();
		foreach ( $options['emails'] as $email )
			$phpmailer->AddBCC( $email );

		$phpmailer->AddReplyTo(
			get_the_author_meta( 'email', $this->right_post->post_author ),
			get_the_author_meta( 'display_name', $this->right_post->post_author )
		);
	}

	/* Admin */
	function admin_menu() {
		register_setting( self::OPTION_GROUP, self::OPTION, array( &$this, 'validate_options' ) );

		add_settings_section( self::ADMIN_PAGE, __( 'Email Post Changes' ), array( &$this, 'settings_section' ), self::ADMIN_PAGE );
		add_settings_field( self::ADMIN_PAGE . '_emails', __( 'Email Addresses' ), array( &$this, 'emails_setting' ), self::ADMIN_PAGE, self::ADMIN_PAGE );
		add_settings_field( self::ADMIN_PAGE . '_post_types', __( 'Post Types' ), array( &$this, 'post_types_setting' ), self::ADMIN_PAGE, self::ADMIN_PAGE );

		add_options_page( __( 'Email Post Changes' ), __( 'Email Post Changes' ), 'manage_options', self::ADMIN_PAGE, array( &$this, 'admin_page' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'plugin_action_links' ) );
	}

	function validate_options( $options ) {
		if ( !$options || !is_array( $options ) )
			return $this->defaults;

		$return = array();

		if ( empty( $options['emails'] ) ) {
			$return['emails'] = $this->defaults['emails'];
		} else {
			if ( is_string( $options['emails'] ) )
				$_emails = preg_split( '(\n|\r)', $options['emails'], -1, PREG_SPLIT_NO_EMPTY );
			$_emails = array_unique( (array) $_emails );
			$emails = array_filter( $_emails, 'is_email' );
			if ( $diff = array_diff( $_emails, $emails ) )
				$return['invalid_emails'] = $diff;
			$return['emails'] = $emails ? $emails : $this->defaults['emails'];
		}

		if ( empty( $options['post_types'] ) || !is_array( $options ) ) {
			$return['post_types'] = $this->defaults['post_types'];
		} else {
			$post_types = array_intersect( $options['post_types'], $this->get_post_types() );
			$return['post_types'] = $post_types ? $post_types : $this->defaults['post_types'];
		}

		return $return;
	}

	function admin_page() {
		$options = $this->get_options();
?>

<div class="wrap">
	<h2><?php _e( 'Email Post Changes' ); ?></h2>
<?php	if ( !empty( $options['invalid_emails'] ) ) : ?>

	<div class="error">
		<p><?php printf( _n( 'Invalid Email: %s', 'Invalid Emails: %s', count( $options['invalid_emails'] ) ), '<kbd>' . join( '</kbd>, <kbd>', array_map( 'esc_html', $options['invalid_emails'] ) ) ); ?></p>
	</div>
<?php	endif; ?>

	<form action="options.php" method="post">
		<?php settings_fields( self::OPTION_GROUP ); ?>
		<?php do_settings_sections( self::ADMIN_PAGE ); ?>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
		</p>
	</form>
</div>
<?php
	}

	function settings_section() {} // stub

	function emails_setting() {
		$options = $this->get_options();
?>
		<textarea rows="4" cols="40" style="width: 40em;" name="email_post_changes[emails]"><?php echo esc_html( join( "\n", $options['emails'] ) ); ?></textarea>
		<p class="description"><?php _e( 'Send emails to these addresses.  One per line.' ); ?></p>
		<p class="description"><?php printf(
			__( "If blank, send emails to this site&#8217;s <a href='%s'>admin email address</a>." ),
			clean_url( get_bloginfo( 'wpurl' ) . '/wp-admin/options-general.php#admin_email' )
		); ?></p>
<?php
	}

	function post_types_setting() {
		$options = $this->get_options();
?>

		<ul>
<?php		foreach ( $this->get_post_types() as $post_type ) : ?>

			<li><label><input type="checkbox" name="email_post_changes[post_types][]" value="<?php echo esc_attr( $post_type ); ?>"<?php checked( in_array( $post_type, $options['post_types'] ) ); ?> /> <?php echo esc_html( ucfirst( $post_type ) ); ?></label></li>
<?php		endforeach; ?>

		</ul>
		<p class="description"><?php _e( 'Send emails when a post of these post types changes.' ); ?></p>
		<p class="description"><?php _e( 'If none are selected, &#8220;post&#8221; and &#8220;page&#8221; will be checked by default.' ); ?></p>
<?php
	}

	function plugin_action_links( $links ) {
		array_unshift( $links, '<a href="options-general.php?page=' . self::ADMIN_PAGE . '">' . __( 'Settings' ) . "</a>" );
		return $links;
	}
}

add_action( 'init', array( 'Email_Post_Changes', 'init' ) );
