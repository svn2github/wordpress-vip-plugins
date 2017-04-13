
function PbAlert(jQuery){

    this.$ = jQuery;
    this.error = 'notice-error';
    this.warning = 'notice-warning';
    this.success = 'notice-success';
    this.msgPlaceholder = '{MSG}';
    this.classPlaceholder = '{CLASS}';
    this.template =
        '<div class="' + this.classPlaceholder + ' notice">' +
            '<p>' + this.msgPlaceholder +'</p>' +
        '</div>';

}

PbAlert.prototype.notify = function (msg, options) {

    var type = !options.type || !this[options.type] ? '' :  this[options.type];
    var template = this.template.replace(this.msgPlaceholder, msg).replace(this.classPlaceholder, type);

    //add alert after placeholder.
    options.placeholder.before(template);

};


window.PbAlert = new PbAlert(window.jQuery);