


function PbStoryCreatorView(jQuery, PbEvent){

    var elementsToCreate = ['#save-post', '#post-preview', '#publish'];
    var elementsToBind = ['#title'];

    this.$ = jQuery;
    this.newElements = this.generateElements(elementsToCreate, true);
    this.elements = this.generateElements(elementsToBind);
    this.Event = PbEvent;

    this.init();

}

/**
 * create button elements and add them to the dom.
 * @param elements
 * @returns {{}}
 */
PbStoryCreatorView.prototype.generateElements = function (elements, createNewElement) {

    var _elements = {};
    var _this = this;

    for(var i = 0; i < elements.length; i++){

        var element = elements[i].substring(1);
        var wp = _this.$(elements[i]);

        var pb = createNewElement ? wp.clone().prependTo(wp.parent()).attr('id', 'pb-' + element) : null;

        _elements[element] = {
            wp: wp,
            pb: pb
        }
    }


    return _elements;
};


PbStoryCreatorView.prototype.init = function () {

    var _this = this;

    this.savePost = new this.Event(this.newElements['save-post']);
    this.postPreview = new this.Event( this.newElements['post-preview']);
    this.publish = new this.Event(this.newElements['publish']);
    this.title = new this.Event(this.elements['title']);



    // attach listeners to HTML elements
    //listen to click event on save-post button
    this.newElements['save-post'].pb.click(function (e) {
        e.preventDefault();
        _this.savePost.notify(e);
    });

    //listen to click event on post-preview button
    this.newElements['post-preview'].pb.click(function (e) {

        e.preventDefault();
        e.stopImmediatePropagation();

        _this.postPreview.notify(e);

    });

    //listen to click event on publish button
    this.newElements['publish'].pb.click(function (e) {
        e.preventDefault();
        _this.publish.notify(e);

    });

    //listen to change event on title input
    this.elements['title'].wp.change(function (e) {
       _this.title.notify(e);
    });

};


window.PbStoryCreatorView = PbStoryCreatorView;