<?php

    /*
     * Grab the settings from $instance and fill out default
     * values as needed.
     */
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
    $first_name = empty($instance['first_name']) ? '' : $instance['first_name'];
    $last_name = empty($instance['last_name']) ? '' : $instance['last_name'];
    if( is_array( $instance['sailthru_list'] ) )
    {
        $sailthru_list = implode(',', $instance['sailthru_list'] );
    } else {
        $sailthru_list = $instance['sailthru_list'];
    }


    // display options
    $show_first_name = (isset($instance['show_first_name']) && $instance['show_first_name']) ? true : false;
    $show_last_name = (isset($instance['show_last_name']) && $instance['show_last_name']) ? true : false;

    // nonce
    $nonce = wp_create_nonce("add_subscriber_nonce"); 

 ?>
 <div class="sailthru-signup-widget">
     <div class="sailthru_form">

        <?php
            // title
            if (!empty($title)) {
                if(!isset($before_title)) {
                    $before_title = '';
                }
                if(!isset($after_title)) {
                    $after_title = '';
                }
                echo $before_title . esc_html(trim($title)) . $after_title;
            }
        ?>
         <form method="post" action="#" id="sailthru-add-subscriber-form">
            <div id="sailthru-add-subscriber-errors"></div>
            <?php if( $show_first_name ) { ?>
            <div class="sailthru_form_input">
                <label for="sailthru_first_name">First Name:</label>
                <input type="text" name="first_name" id="sailthru_first_name" value="" />
            </div>
            <?php } ?>

            <?php if( $show_last_name ) { ?>
            <div class="sailthru_form_input">
                <label for="sailthru_last_name">Last Name:</label>
                <input type="text" name="last_name" id="sailthru_last_name" value="" />
            </div>
            <?php } ?>

            <div class="sailthru_form_input">
                <label for="sailthru_email">Email:</label>
                <input type="text" name="email" id="sailthru_email" value="" />
            </div>

            <div class="sailthru_form_input">
                <input type="hidden" name="sailthru_nonce" value="<?php echo $nonce; ?>" />
                <input type="hidden" name="sailthru_email_list" value="<?php echo esc_attr($sailthru_list); ?>" />
                <input type="hidden" name="action" value="add_subscriber" />
                <input type="hidden" name="vars[source]" value="<?php bloginfo('url'); ?>" />
                <input type="submit" value="Subscribe" />
            </div>
        </form>
    </div>
</div>
