<?php
ob_start();
$all_posts = array();
while (have_posts()) {
    the_post();
    $all_posts[] = uppsite_process_post();
}

$total_count = wp_count_posts()->publish;
ob_end_clean();

print json_encode(
    array(
        'root' => $all_posts,
        'total_count' => $total_count
    )
);