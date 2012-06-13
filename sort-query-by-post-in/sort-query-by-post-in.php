<?php
/**
 Plugin Name: Sort Query by Post In
 Plugin URI: http://www.get10up.com/plugins/sort-query-by-post-in-wordpress/
 Description: Allows post queries to sort the results by the order specified in the <em>post__in</em> parameter. Just set the <em>orderby</em> parameter to <em>post__in</em>! 
 Version: 1.2.2
 Author: Jake Goldman (10up)
 Author URI: http://www.get10up.com

    Plugin: Copyright 2011 10up  (email : jake@get10up.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_filter( 'posts_orderby', 'sort_query_by_post_in', 10, 2 );
	
function sort_query_by_post_in( $sortby, $thequery ) {
	if ( !empty($thequery->query['post__in']) && isset($thequery->query['orderby']) && $thequery->query['orderby'] == 'post__in' )
		$sortby = "find_in_set(ID, '" . implode( ',', $thequery->query['post__in'] ) . "')";
	
	return $sortby;
}
