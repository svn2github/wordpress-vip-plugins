<categories>
<?php
    if (!mysiteapp_homepage_is_only_show_posts()) {
        $cats_ar = isset($_REQUEST['cats_ar']) ? $_REQUEST['cats_ar'] : mysiteapp_homepage_get_popular_categories();
        if (!is_array($cats_ar)) { return; }

        // Restrict maximum categories to iterate over.
        $cats_ar = array_splice($cats_ar, 0, 15);
		$cats_ar = array_map( 'sanitize_text_field', $cats_ar );

        foreach ($cats_ar as $cat) {
            $cat_query = array(
                'cat' => $cat,
                'posts_per_page' =>  mysiteapp_homepage_cat_posts()
            );

            if (!mysiteapp_is_fresh_wordpress_installation()) {
                $cat_query['post__not_in'] = mysiteapp_homepage_get_excluded_posts();
            }
            $query = mysiteapp_set_current_query($cat_query);

            if ($query->post_count > 0) {
                // Print only categories that exist
                $catText = wp_list_categories(array( 'include' => $cat, 'echo' => 0 ));
                print str_replace("</category>", "", $catText);
                get_template_part('the_loop');
                print "</category>";
            }
        }
    }
    ?>
</categories>
