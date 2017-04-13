

function PbStoryCreatorController(model, view, jQuery, PbAlert, wp, pbLogger){

    this.model = model;
    this.view = view;
    this.$ = jQuery;
    this.alert = PbAlert;
    this.wp = wp;
    this.editPost = document.location.pathname.indexOf('post.php') > -1;
    this.logger = new pbLogger({
        environment: 'production',
        serviceName: 'wp-plugin'
    });
}
/**
 * listen to dom click events for pb buttons events
 * and call the customMenu function to mark 'Add New Story' at the admin menu as selected
 */
PbStoryCreatorController.prototype.init = function () {

    var _this = this;

    this.manipulateDom();

    //disable wordpress autosave feature.
    this.disableWPAutoSave();

    //initialize creator via creator sdk.
    this.model.pbCreator.initialize()

        .then(function (res) {


            // enable all action buttons
            _this.changeAllButtonsStatus('disabled', false);


        },  _this.errorHandler);


    // listen to click event on post-preview button
    _this.view.postPreview.listen(function (sender, e) {

        _this.actionRunning(_this.view.publish.sender.pb);

        //_this.model.savePost(sender.wp).then(_this.postSuccessHandler.bind(_this), _this.errorHandler.bind(_this));
        _this.model.previewPost(sender.wp).then(_this.postSuccessHandler.bind(_this), _this.errorHandler.bind(_this));

    });

    // listen to click event on publish button
    _this.view.publish.listen(function (sender, e) {
        _this.actionRunning(sender.pb);

        _this.model.publishPost(sender.wp).then(_this.postSuccessHandler.bind(_this), _this.errorHandler.bind(_this));

    });

    // listen to click event on save button
    _this.view.savePost.listen(function (sender, e) {
        _this.actionRunning(sender.pb);

        _this.model.savePost(sender.wp).then(_this.postSuccessHandler.bind(_this), _this.errorHandler.bind(_this));

    });

    //listen to changes on title input.
    _this.view.title.listen(function (sender, e) {
        _this.setTitleToCreator(sender.wp.val());
    });

};

/**
 * disable autosave by wordpress.
 */

PbStoryCreatorController.prototype.disableWPAutoSave = function () {
    this.wp.autosave.local.suspend();
    this.wp.autosave.server.suspend();
};

/**
 * show spinne and disable action buttons.
 * @param button
 */

PbStoryCreatorController.prototype.actionRunning = function (button) {

    this.changeLoaderStatus(button.siblings('.spinner'), 'show');
    this.changeAllButtonsStatus('disabled', true);
};

/**
 * hide spinner and enable action buttons.
 * @param button
 */

PbStoryCreatorController.prototype.actionStopped = function () {

    this.changeLoaderStatus(this.$('.spinner'), 'hide');
    this.changeAllButtonsStatus('disabled', false);
};


/**
 * set the creator via, creator sdk, with the given properties.
 * @param properties
 */
PbStoryCreatorController.prototype.setPropertiesToCreator = function (properties) {

    var _this = this;
    var deferred = this.$.Deferred();

    this.model.setProperties(properties)

        .then(function (res) {
            deferred.resolve(res);
        })

        .fail(function () {
            _this.errorHandler();
            deferred.reject(res);
        });

   return deferred.promise();

};

PbStoryCreatorController.prototype.setTitleToCreator = function (title) {

    var _this = this;
    var deferred = this.$.Deferred();

    this.model.setTitle(title)

        .then(function (res) {
            deferred.resolve(res);
        })

        .fail(function () {
            _this.errorHandler();
            deferred.reject(res);
        });

   return deferred.promise();

};

/**
 * handle success response from creator
 * @param res
 */
PbStoryCreatorController.prototype.postSuccessHandler =  function (res) {

    this.actionStopped();
};

/**
 * handle error response from creator.
 * @param err
 */
PbStoryCreatorController.prototype.errorHandler =  function (err) {

    this.logger.error(err);

    this.alert.notify(err, {
        type: 'error',
        placeholder: this.$('#post')
    });

    this.actionStopped();

};


/**
 * change dom elements.
 */
PbStoryCreatorController.prototype.manipulateDom = function () {

    this.markPage();

    this.customMenu();

    this.customTitle();

    this.removeWpEditor();

    this.changeButtonsStatus([this.view.postPreview.sender.pb, this.view.publish.sender.pb, this.view.savePost.sender.pb], 'disabled', true);

};


PbStoryCreatorController.prototype.getAddNewButton = function () {

    //TODO -- translation
    var button = this.$('.page-title-action').clone();
    var href = button.attr('href');

    button.text('Add New Story').attr('href', href + '?pb-story=true').addClass('show');

    return button;

};
/**
 *   add class to the body to id the page.
 */
PbStoryCreatorController.prototype.markPage = function () {

    this.$('body').attr('id', 'pb-story-creator-page');
};

/**
 * mark 'Add New Story' at the admin menu as selected
 * and remove mark from 'Add New'.
 */
PbStoryCreatorController.prototype.customMenu = function () {

    var $menuPosts = this.$('#menu-posts');
    var currentClass = 'current';

    if($menuPosts.find('li.current > a').attr('href') === 'post-new.php'){
        $menuPosts.find('li.current').removeClass(currentClass);
        $menuPosts.find('a[href$="post-new.php?pb-story=true"]').addClass(currentClass).parent().addClass(currentClass);
    }

};

/**
 * change the title text
 */
PbStoryCreatorController.prototype.customTitle = function(){

    //TODO -- translation
    var title = this.editPost ? 'Edit Story' : 'Add New Story';
    var titleInput = 'Enter story title here';
    var button = this.editPost ? this.getAddNewButton() : null;


    this.$('.wrap > h1').text('').append(title).append(button).addClass('show');
    this.$('#title-prompt-text').text(titleInput).addClass('show');
};

/**
 * Remove wp editor - by default we load the editor with display:none.
 */
PbStoryCreatorController.prototype.removeWpEditor = function () {
    this.$('#postdivrich').remove();
};

/**
 * hide / show wp loader.
 * @param loader
 * @param status
 */
PbStoryCreatorController.prototype.changeLoaderStatus = function (loader, status) {

    if(status === 'show'){
        loader.addClass('is-active');
    } else{
        loader.removeClass('is-active');
    }
};

/**
 * change buttons status - example - disabled: true-false
 * @param buttons
 * @param statusName
 * @param status
 */
PbStoryCreatorController.prototype.changeButtonsStatus = function(buttons, statusName, status){

    if(this.$.isArray(buttons)){

        for(var i = 0; i < buttons.length; i++){
            buttons[i].attr(statusName, status);
        }

    }
    else{
        buttons.attr(statusName, status);
    }

};

/**
 * change all buttons status - example - disabled: true-false
 * @param statusName
 * @param status
 */
PbStoryCreatorController.prototype.changeAllButtonsStatus = function (statusName, status) {
    this.changeButtonsStatus([this.view.postPreview.sender.pb, this.view.publish.sender.pb, this.view.savePost.sender.pb], statusName, status);
};




window.PbStoryCreatorController = PbStoryCreatorController;