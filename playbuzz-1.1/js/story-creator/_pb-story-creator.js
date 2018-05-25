(function (Globals, $) {
	Globals.pbInitStoryCreator = function (Globals, $) {

		var pbGlobal = new Globals.PbGlobal( Globals.pb, $, Globals.pbSettings ),

			pbStoryCreatorModel = new Globals.PbStoryCreatorModel( $, Globals.PbCreator, Globals.PbApi, pbGlobal ),

			pbStoryCreatorView = new Globals.PbStoryCreatorView( $, Globals.PbEvent ),

			pbStoryCreatorController = new Globals.PbStoryCreatorController( pbStoryCreatorModel, pbStoryCreatorView, $, Globals.PbAlert, Globals.wp, Globals.pbLogger );

		pbStoryCreatorController.init();
	};

	Globals.pbInitStoryCreator( Globals, $ );

})( window, window.jQuery );
