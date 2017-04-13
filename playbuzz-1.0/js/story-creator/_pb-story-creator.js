
(function (window, $) {

    var pbGlobal = new window.PbGlobal(window.pb, $, window.pbSettings),

        pbStoryCreatorModel = new window.PbStoryCreatorModel($, window.PbCreator, window.PbApi, pbGlobal),

        pbStoryCreatorView = new window.PbStoryCreatorView($, window.PbEvent),

        pbStoryCreatorController = new window.PbStoryCreatorController(pbStoryCreatorModel, pbStoryCreatorView, $, window.PbAlert, window.wp, window.pbLogger );


    pbStoryCreatorController.init();

})(window, window.jQuery);




