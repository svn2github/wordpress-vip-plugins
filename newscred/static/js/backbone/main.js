/**
 *  @package NewsCred WordPress Plugin
 *  @author  Md Imranur Rahman <imran.aspire@gmail.com>
 *
 *  main  run  file
 **/

var ncApp = this.ncApp || {};

ncApp.domain = NC_globals.domain;
ncApp.isLogin = NC_globals.is_login;
ncApp.imageUrl = NC_globals.imageurl;

ncApp.defaultWidth = NC_globals.default_width;
ncApp.defaultHeight = NC_globals.default_height;

var templateUrl = NC_globals.jsurl + "/backbone/templates/";

// get templates
ncApp.template = function ( url ) {
    var data = "<h1> failed to load url : " + url + "</h1>";
    $.ajax( {
        async:false,
        url:templateUrl + url + ".html",
        success:function ( response ) {
            data = response;
        }
    } );
    return data;
}

