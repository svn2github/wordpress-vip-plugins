<?php
ob_start();
$all_posts = array();
while (have_posts()) {
    the_post();
    if ( !uppsite_should_filter( get_permalink() ) ) {
        $all_posts[] = uppsite_process_post();
    }
}

$total_count = wp_count_posts()->publish;
ob_end_clean();

if (isset($_GET['noPagination'])) {
    print json_encode($all_posts);
} else {
    print json_encode(
        array(
            'root' => $all_posts,
            'total_count' => $total_count
        )
);
}