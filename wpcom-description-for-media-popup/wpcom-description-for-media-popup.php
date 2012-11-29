/**
 * Plugin Name: WPCOM Description for Media Popup
 * Description: Allows you to restore the Description field in the media popup.
 *
 * @see http://core.trac.wordpress.org/ticket/22642
 * @props Andrew Nacin
 */

if ( is_admin() ) {
	add_filter( 'attachment_fields_to_edit', function( $fields, $post ) {
		$fields['post_content'] = array(
				'label'      => __( 'Description' ),
				'value'      => $post->post_content,
				'input'      => 'textarea'
		);
		return $fields;
	}, 10, 2 );
}
