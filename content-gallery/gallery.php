<div id="featured">
<style>
#myGallery, #myGallerySet, #flickrGallery {
width: <?php echo get_option('gallery-width'); ?>px;
height: <?php echo get_option('gallery-height'); ?>px;
z-index:5;
border: 1px solid #000;
}

.jdGallery .slideInfoZone
{
position: absolute;
z-index: 10;
width: 100%;
margin: 0px;
left: 0;
bottom: 0;
height: <?php echo get_option('gallery-info'); ?>px;
background: #000;
color: #fff;
text-indent: 0;
overflow: hidden;
}
</style>
	<script type="text/javascript">
        function startGallery() {
            var myGallery = new gallery($('myGallery'), {
                timed: true
            });
        }
        window.addEvent('domready',startGallery);
    </script>

    <div id="myGallery">
    	<?php $temp_query = $wp_query; ?>
        <?php query_posts('category_name=' . get_option('gallery-category') . '&showposts=' . get_option('gallery-items')); ?>
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <div class="imageElement">
            <h2><?php the_title() ?></h2>
            <p><?php the_content_rss('', TRUE, '', 30); ?></p>
            <a href="<?php the_permalink() ?>" title="Read More" class="open"></a>
            <img src="<?php $key="articleimg"; echo get_post_meta($post->ID, $key, true); ?>" class="full" />
            <img src="<?php $key="articleimg"; echo get_post_meta($post->ID, $key, true); ?>" class="thumbnail" />
        </div>
        <?php endwhile; else: ?>
        <?php endif; ?>
        <?php $wp_query = $temp_query; ?>
    </div>
</div>
