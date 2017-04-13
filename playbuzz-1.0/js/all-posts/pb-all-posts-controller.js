
function PbAllPostsController(jQuery) {
    this.$ = jQuery;
}

PbAllPostsController.prototype.init = function () {
    this.addStoryButton();
};

/**
 * create & add the 'Add New Story' button to the dome
 */
PbAllPostsController.prototype.addStoryButton = function () {

    var $newPost = this.$('.page-title-action');

    var $newStory = $newPost.clone().attr({
        'id': 'pb-page-title-action',
        'href': 'post-new.php?pb-story=true'
    });

    //TODO -- translation
    $newStory.text('Add New Story');

    $newPost.after($newStory);
};

window.PbAllPostsController = PbAllPostsController;