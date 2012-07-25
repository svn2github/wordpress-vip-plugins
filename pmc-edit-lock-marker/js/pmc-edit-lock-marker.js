/**
 * Part of PMC Edit Lock Marker plugin
 * 
 * @since 2012-07-02 Amit Gupta
 * @version 2012-07-20 Amit Gupta
 * 
 **/

jQuery(document).ready(function($) {
	
	var fail_threshold = 15;	//number of times failure is allowed, lets be a little lenient
	pmc_edit_lock_marker.fails = 0;	//init the failed attempt counter
	
	function pmc_check_post_edit_lock() {
		var post_ids = [];		//array to store post IDs on the page
		$("#the-list tr[id*=post-]").each(function(index) {
			var elem_id = jQuery(this).attr('id');
			//make sure ID has value & the type of value that we need
			if( elem_id && elem_id.substr(0,5) == "post-" ) {
				elem_id = elem_id.split("-");
				post_ids.push( parseInt( elem_id[1] ) );	//put the post ID in our array
			}
			elem_id = null;
		});
		//proceed only if there are any post IDs in our array
		if( post_ids.length > 0 ) {
			post_ids = post_ids.join(",");	//convert the array to a string to send to server
			// time to check for edit locks
			$.post(
				ajaxurl,
				{
					action: 'pmc-post-edit-lock-marker',
					_pmc_elm_ajax_nonce: pmc_edit_lock_marker.nonce,
					post_ids: post_ids
				},
				function(data) {
					//remove edit-lock marker class from any existing post rows
					$("#the-list tr[id*=post-]").removeClass("pmc-edit-lock");
					
					if( ! data || ! data.nonce ) {
						//no data, not good, log as failed attempt & bail
						pmc_edit_lock_marker.fails += 1;
						return false;
					}
					
					pmc_edit_lock_marker.nonce = data.nonce;	//new nonce came in through, update it in the var from where we pick it up
					pmc_edit_lock_marker.fails = 0;		//reset the failed attempts counter as we log continous failures only
					
					if( ! data.posts ) {
						//no locked posts, bail
						return false;
					}
					
					var locked_posts = data.posts.split(",");	//convert locked post IDs to an array
					if( locked_posts.length < 1 ) {
						//locked post ID array is blank, bail
						return false;
					}
					
					for( var i=0; i < locked_posts.length; i++ ) {
						$("#" + "post-" + locked_posts[i]).addClass("pmc-edit-lock");	//add edit-lock marker class to post row
					}
				},
				"json"
			);
		}
		
		//poll again only if we're still within the failed attempts threshold
		if( pmc_edit_lock_marker.fails < fail_threshold ) {
			//now set it to poll again after 30 seconds
			setTimeout( pmc_check_post_edit_lock, 30000 );
		}
	}
	
	//execute only if we are on the edit.php
	if( adminpage && adminpage == "edit-php" ) {
		pmc_check_post_edit_lock();	//lets call it initially
	}
	
});

//EOF