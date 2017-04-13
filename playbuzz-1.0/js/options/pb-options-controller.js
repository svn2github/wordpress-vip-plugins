function PbOptionsController(jQuery, model) {
	this.$ = jQuery;
	this.model = model;
	this.$channelAlias = this.$( "#pb-channel-alias" );
	this.$channelId = this.$( "#pb_channel_id" );
	this.$submit = this.$("#submit");
	this.isSaving = false;
	this.submitClicked = false;
}

PbOptionsController.prototype.init = function () {

	var _this = this;
	var channelAlias;

	//listen to change on the channel alias
	this.$channelAlias.change(function () {
		channelAlias = _this.$( this ).val();
		_this.pbChannelAliasChanged( channelAlias );
	});

	//backward compatibility for users who already have a  channelAlias
	if (this.$channelAlias.val() !== "" && this.$channelId.val() === "") {
		channelAlias = this.$channelAlias.val();
		this.pbChannelAliasChanged( channelAlias );
	}

	this.$submit.click(function (e) {

		if(_this.isSaving){
			e.preventDefault();
            _this.submitClicked = true;
		}

    });
};

PbOptionsController.prototype.pbChannelAliasChanged = function (channelAlias) {

	var _this = this;
	var deferred = this.$.Deferred();
	
    this.isSaving = true;

	//request the user id by the channel alias
	this.model.getUserId( channelAlias )

		.then(function (res) {
			_this.changeUserId( res );
            _this.isSaving = false;

			if(_this.submitClicked){
            	_this.$submit.click();
			}

			deferred.resolve( res );
		})

		.fail(function (res) {
			_this.isSaving = false;

			if(_this.submitClicked){
                _this.$submit.click();
            }
			_this.errorHandler( res );
			deferred.reject( res );
		});

	return deferred.promise();
};

PbOptionsController.prototype.errorHandler = function (err) {

};

PbOptionsController.prototype.changeUserId = function (id) {
	this.$channelId.val( id );
};

window.PbOptionsController = PbOptionsController;
