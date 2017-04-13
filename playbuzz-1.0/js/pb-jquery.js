
/**
 *  extensions for jQuery.
 */

window.jQuery.extend({


    /**
     * deserialize query string to json object.
     * @docs - https://css-tricks.com/snippets/jquery/get-query-params-object/
     * @param str - optional - query string to deserialize.
     * @param param - optional - return specific param.
     * @returns {*}
     */
    queryParameters : function(param, str) {
        var queryObj = (str || document.location.search).replace(/(^\?)/,'').split("&").map(function(n){return n = n.split("="),this[n[0]] = n[1],this}.bind({}))[0];

        if(param){
            return queryObj[param];
        }

        return queryObj;
    }

});