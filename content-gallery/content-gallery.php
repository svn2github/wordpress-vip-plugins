<?php
/*
Plugin Name: Featured Content Gallery
Plugin URI: http://www.wpelements.com
Description: Used to create a fully automated featured content gallery anywhere within your wordpress theme.
Version: 2.0-WPCOM
Author: Jason Schuller
Author URI: http://www.wpelements.com
*/

/* Adds our admin options under "Options" */
function gallery_options_page() {
	add_options_page('Featured Content Gallery Options', 'Featured Content Gallery', 8, basename(__FILE__), 'gallery_options_form');
}

function gallery_options_form() {
        if (!empty($_POST)) {
		update_option("gallery-width", $_POST["gallery-width"]);
		update_option("gallery-height", $_POST["gallery-height"]);
		update_option("gallery-info", $_POST["gallery-info"]);
		update_option("gallery-category", $_POST["gallery-category"]);
		update_option("gallery-items", $_POST["gallery-items"]);

		echo '<div id="message" class="updated fade"><p>Options saved.</p></div>';
        }
?>

<div class="wrap">
	<h2>Featured Content Gallery Configuration</h2>
	<p>Use the fields below to customize your gallery width, height, text overlay height, the category you want to use for gallery content as well as the number of gallery items you want to be displayed.</p>

	<div style="margin-left:0px;">
	<form method="post" id="gallery_form" name="gallery_form" action="">
	<?php wp_nonce_field('update-options'); ?>
	<fieldset name="general_options" class="options">

        Gallery Width in Pixels:<br />
	<div style="margin:0;padding:0;">
        <input name="gallery-width" id="gallery-width" size="25" value="<?php echo get_option('gallery-width'); ?>"></input>
        </div><br />

        Gallery Height in Pixels:<br />
	<div style="margin:0;padding:0;">
        <input name="gallery-height" id="gallery-height" size="25" value="<?php echo get_option('gallery-height'); ?>"></input> 
        </div><br />

        Text Overlay Height in Pixels:<br />
	<div style="margin:0;padding:0;">
        <input name="gallery-info" id="gallery-info" size="25" value="<?php echo get_option('gallery-info'); ?>"></input> 
        </div><br />

        Category Name:<br />
	<div style="margin:0;padding:0;">
        <input name="gallery-category" id="gallery-category" size="25" value="<?php echo get_option('gallery-category'); ?>"></input>   
        </div><br />

        Number of Items to Display:<br />
	<div style="margin:0;padding:0;">
        <input name="gallery-items" id="gallery-items" size="25" value="<?php echo get_option('gallery-items'); ?>"></input>   
        </div><br />
                
	</fieldset>
	<p class="submit"><input type="submit" name="Submit" value="<?php _e('Update Options') ?>" /></p>
	</form>      
</div>
<?php
}

function gallery_styles() {
    /* The next lines figures out where the javascripts and images and CSS are installed,
    relative to your wordpress server's root: */
    $gallery_path = get_settings('siteurl') . "/wp-content/themes/vip/plugins/content-gallery/";

    /* The xhtml header code needed for gallery to work: */
	$galleryscript = "
	<!-- begin gallery scripts -->
    	<link rel=\"stylesheet\" href=\"".$gallery_path."css/jd.gallery.css\" type=\"text/css\" media=\"screen\" charset=\"utf-8\"/>
	<script type=\"text/javascript\" src=\"".$gallery_path."scripts/mootools.v1.11.js\"></script>
	<script type=\"text/javascript\" src=\"".$gallery_path."scripts/jd.gallery.js\"></script>
	<!-- end gallery scripts -->\n";
	/* Output $galleryscript as text for our web pages: */
	echo($galleryscript);
}

// Add the custom field box on the post page
add_action( 'admin_menu', 'gallery_meta_box' );
function gallery_meta_box() {
        add_meta_box( 'articleimg', 'Featured Article Image', 'articleimg_meta_box', 'post', 'normal' );
}

// Outputs the Article Image text form
function articleimg_meta_box( $post, $meta_box ) {
        if ( $post_id = (int) $post->ID )
                $articleimg = (string) get_post_meta( $post_id, 'articleimg', true );
        else
                $articleimg = '';
        $articleimg = format_to_edit( $articleimg );
?>
<p><label class="hidden" for="articleimg">Featured Article Image (Optional)</label><input type="text" name="articleimg" id="articleimg" value="<?php echo $articleimg; ?>" /></p>
        <p><label for="articleimg">Image used for the Featured Gallery.</label></p>

<?php
        wp_nonce_field( 'articleimg', 'articleimg_nonce', false );
}

// Saves the entered Article Image text
add_action( 'save_post', 'articleimg_save_meta' );
function articleimg_save_meta( $post_id ) {
        // Checks to see if we're POSTing
        if ( 'post' !== strtolower( $_SERVER['REQUEST_METHOD'] ) || !isset($_POST['articleimg']) )
                return;

        // Checks to make sure we came from the right page
        if ( !wp_verify_nonce( $_POST['articleimg_nonce'], 'articleimg' ) )
                return;

        // Checks user caps
        if ( !current_user_can( 'edit_post', $post_id ) )
                return;

        // Already have a articleimg?
        $old_articleimg = get_post_meta( $post_id, 'articleimg', true );

        // Sanitize
        $articleimg = wp_filter_post_kses( $_POST['articleimg'] );
        $articleimg = trim( stripslashes( $articleimg ) );


        // nothing new, and we're not deleting the old
        if ( !$articleimg && !$old_articleimg )
                return;

        // Nothing new, and we're deleting the old
        if ( !$articleimg && $old_articleimg ) {
                delete_post_meta( $post_id, 'articleimg' );
                return;
        }

        // Nothing to change
        if ( $articleimg === $old_articleimg )
                return;

        // Save the articleimg
        if ( $old_articleimg ) {
                update_post_meta( $post_id, 'articleimg', $articleimg );
        } else {
                if ( !add_post_meta( $post_id, 'articleimg', $articleimg, true ) )
                        update_post_meta( $post_id, 'articleimg', $articleimg ); // Just in case it was deleted and saved as ""
        }
}

/* we want to add the above xhtml to the header of our pages: */
add_action('wp_head', 'gallery_styles');
add_action('admin_menu', 'gallery_options_page');

?>
