# wp_enqueue_media Override

Beat WordPress to the punch in enqueueing media, and do so more performantly.

The main function here is ripped from core, with a couple of major differences in how
it queries for "has_audio", "has_video", and media months. These queries are
extremely slow on sites with as much media as this site has, and this is a
band-aid to speed things up. WordPress doesn't currently offer a way to
intercept these queries or override this function, but the function does
check if `did_action( 'wp_enqueue_media' )` and returns if true. Therefore,
if we were to fire that action before WordPress does, we can safely override
the functionality.

This plugin is a stopgap and can be deprecated once Trac tickets [32264](https://core.trac.wordpress.org/ticket/32264) and [31071](https://core.trac.wordpress.org/ticket/31071) are closed.

Most of this code is copied from WordPress core, with most of
the performance updates coming from @philipjohn, as posted on
https://core.trac.wordpress.org/ticket/32264.
