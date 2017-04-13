function PbOptionsModel(jQuery, PbApi, pbLogger) {
	this.$ = jQuery;
	this.api = PbApi;
	this.url = "//www.playbuzz.com/GetUserId?channelAlias=";
	this.logger = new pbLogger({
        environment: 'production',
		serviceName: 'wp-plugin'
	});
}

PbOptionsModel.prototype.getUserId = function (channelAlias) {

	var _this = this;
	var deferred = this.$.Deferred();
	var url = this.url + channelAlias;

	_this.api.get( {url: url} )

		.then(function (res) {

			deferred.resolve( res.payload.id );

		})

		.fail(function (err) {

			var error = err.statusText;

			_this.errorHandler( error );
			deferred.reject( error );
		});

	return deferred.promise();

};

PbOptionsModel.prototype.errorHandler = function (err) {
	this.logger.error( err );
};


window.PbOptionsModel = PbOptionsModel;
