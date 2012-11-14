<?php
// Get only X posts for carousel
query_posts(array(
    'showposts' => mysiteapp_homepage_carousel_posts_num()
));

// Start buffering
ob_start();
$all_posts = array();

/** Carousel items */
$carouselPosts = array();
while (have_posts()) {
    the_post();
    $carouselPosts[] = uppsite_process_post();
    mysiteapp_homepage_add_post(get_the_ID());
}
$all_posts[] = array(
    'id' => 0,
    'posts' => $carouselPosts,
    'category' => ' _Carousel' // Carousel is getting a special category name, that will precede all other cat names.
);

/** Categories with posts */
$cats_array = array_splice(uppsite_homepage_get_categories(), 0, 15); // Restrict maximum categories to iterate over.

foreach ($cats_array as $cat) {
    // Perform query for posts in this category
    $cat_query = array(
        'cat' => $cat,
        'posts_per_page' => mysiteapp_homepage_cat_posts()
    );
    if (!mysiteapp_is_fresh_wordpress_installation()) {
        $cat_query['post__not_in'] = mysiteapp_homepage_get_excluded_posts();
    }
    $query = mysiteapp_set_current_query($cat_query);

    // Print only categories that exist
    if ($query->have_posts()) {
        $current_cat = get_category_by_slug($query->get('category_name'));
        while ($query->have_posts()) {
            $query->the_post(); // Will populate $GLOBALS['post']
            $cur_post = uppsite_process_post();

            // Make sure we won't get the same post again.
            mysiteapp_homepage_add_post(get_the_ID());

            $cur_post['category'] = $current_cat->name;
            $cur_post['category_link'] = get_category_link($cat);

            $all_posts[] = $cur_post;
        }
    }
}
// End buffering
ob_end_clean();

// Print in a format for the store
print json_encode(
    array(
        'root' => $all_posts
    )
);