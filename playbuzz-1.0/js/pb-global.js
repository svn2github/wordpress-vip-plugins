
/**
 *
 * @param pb
 * @param jQuery
 * @constructor
 */

function PbGlobal(pb, jQuery, settings) {

    this.$ = jQuery;
    this.global = this.$.extend({}, pb);
    this.toBool = 'comments,info,shares';
    this.settings = settings;

    //add settings from url to global object
    this.enrich(this.global);

    //normalize the global object
    this.normalize(this.global);

    return this.global;
}

PbGlobal.prototype.enrich = function(global){

    global.options = this.$.extend(this.settings, global.options);

    return global;
};

/**
 * normalize global object properties
 * @param global
 * @returns {*}
 */
PbGlobal.prototype.normalize = function (global) {

    var _this = this;

    global.options = parser(global.options);

    return global;


    function parser(obj) {
        for(var key in obj){

            if(_this.toBool.indexOf(key) > -1 ){
                obj[key] =  Boolean(obj[key]);
            }
        }

        return obj;

    }

};

window.PbGlobal = PbGlobal;