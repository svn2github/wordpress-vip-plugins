function PbAllPostsController(jQuery) {
	this.$ = jQuery;
}

PbAllPostsController.prototype.init = function () {
	this.addStoryButton();
};

/**
 * create & add the 'Add New Playbuzz Item' button to the dome
 */
PbAllPostsController.prototype.addStoryButton = function () {

	var $newPost = this.$( '.page-title-action' );

	var $newStory = $newPost.clone().attr(
		{
			'id': 'pb-page-title-action',
			'href': 'post-new.php?pb-story=true'
		}
	);

	//TODO -- translation
	$newStory.html( '<span class="pb-icon pb-icon-blue"></span><span class="pb-menu-name">New Playbuzz</span>' );

	$newPost.after( $newStory );
};

window.PbAllPostsController = PbAllPostsController;
