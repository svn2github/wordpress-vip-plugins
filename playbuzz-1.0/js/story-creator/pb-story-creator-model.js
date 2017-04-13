
function PbStoryCreatorModel(jQuery, pbCreator, PbApi, pbGlobal){

    this.$ = jQuery;
    this.api = PbApi;
    this.global = pbGlobal;
    this.url = window.ajaxurl;
    this.pbCreator = new PbCreator('pb-story-creator' , this.getPbCreatorOptions());
    this.savePostAction = 'save_post_action';
    this.data = {
        postId: this.global.post.ID,
        itemId: '',
        nonce: this.$('#pb-story-creator-nonce').val()
    };

}

/**
 * return options needed for PbCreator.
 * @returns {object}
 */
PbStoryCreatorModel.prototype.getPbCreatorOptions = function () {

    var options = {};
    var environment = this.global.options['pb-env'];

    options.itemId = this.global.itemId;
    options.channelId = this.global.options.pb_channel_id;
    options.locale = this.global.options.locale || 'en-US';


    if(environment && environment !== ""){ options.environment =  environment; }


    return options;
};

/**
 * perform publish / save action
 * steps:
 * 1. perform given action (save/ publish) via creator sdk- return item id
 * 2. save returned item id via wordpress creator plugin.
 * 3. perform given action (save/ publish) via wordpress
 * @param action
 * @param actionOnWP
 * @returns {Promise}
 */
PbStoryCreatorModel.prototype.performAction = function (action, actionOnWP, paramsToAction) {

    var _this = this;
    var deferred = this.$.Deferred();

    //perform action item via creator sdk.
    action(paramsToAction)

        //save item id via wordpress creator plugin.
        .then(function (res) {

            _this.data.itemId = res.itemId;

           return _this.api.post(_this.data, {action: _this.savePostAction, url: _this.url});

        })

        //perform action  to wordpress.
        .then(function (res) {

            actionOnWP.click();
            deferred.resolve(res);
        })

        .catch(function (err) {

            //err.error - response from creator / cdk
            //err.statusText - response from wordpress.
            var error = err.message || err.statusText;

            deferred.reject(error);
        });


    return deferred.promise();

};

/**
 * Publish post via creator sdk
 * @param publishButton - a button to perform click on
 * @returns {Promise}
 */
PbStoryCreatorModel.prototype.publishPost = function (publishButton) {

    return this.performAction(this.pbCreator.publish, publishButton);

};

/**
 * Save post via creator sdk
 * @param saveButton - a button to perform click on
 * @returns {Promise}
 */
PbStoryCreatorModel.prototype.savePost = function (saveButton) {

    return this.performAction(this.pbCreator.save, saveButton);

};
/**
 * Preview post via creator sdk
 * call the save method with the redirect = true.
 * @param previewButton - a button to perform click on
 * @returns {Promise}
 */
PbStoryCreatorModel.prototype.previewPost = function (previewButton) {

    var redirect = true;

    return this.performAction(this.pbCreator.save , previewButton, redirect);

};

/**
 * Set properties via creator sdk
 * @param properties
 * @returns {Promise}
 */
PbStoryCreatorModel.prototype.setProperties = function (properties) {

    var _this = this;
    var deferred = this.$.Deferred();

    this.pbCreator.setItemProperties(properties)

    .then(function (res) {

        deferred.resolve(res);

    })

    .catch(function (err) {

        deferred.reject(err);
    });

    return deferred.promise();

};

/**
 * Set item title via creator sdk
 * @param title
 * @returns {Promise}
 */
PbStoryCreatorModel.prototype.setTitle = function (title) {

    var _this = this;
    var deferred = this.$.Deferred();

    this.pbCreator.setTitle(title)

    .then(function (res) {

        deferred.resolve(res);

    })

    .catch(function (err) {

        deferred.reject(err);
    });

    return deferred.promise();

};


window.PbStoryCreatorModel = PbStoryCreatorModel;