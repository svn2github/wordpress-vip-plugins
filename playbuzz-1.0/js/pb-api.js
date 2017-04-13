function PbApi(jQuery){

	this.$ = jQuery;
}

PbApi.prototype.post = function (data, options) {

	var deferred = this.$.Deferred();
	data.action = options.action;

	this.$.ajax({
		url: options.url,
		method: 'POST',
		data: data,
		success: function (res) {
			deferred.resolve( res );
		},
		error: function (err) {
			deferred.reject( err );
		}
	});

	return deferred.promise();

};

PbApi.prototype.get = function (options) {

	var deferred = this.$.Deferred();

	this.$.ajax({
		url: options.url,
		method: 'GET',
		success: function (res) {
			deferred.resolve( res );
		},
		error: function (err) {
			deferred.reject( err );
		}
	});

	return deferred.promise();

};



window.PbApi = new PbApi( window.jQuery );
