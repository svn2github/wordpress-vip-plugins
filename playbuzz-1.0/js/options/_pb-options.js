(function (window, $) {

	var pbOptionsModel = new window.PbOptionsModel( $,window.PbApi, window.pbLogger ),

		pbOptionsController = new window.PbOptionsController( $, pbOptionsModel );

	pbOptionsController.init();

})(window, window.jQuery);
