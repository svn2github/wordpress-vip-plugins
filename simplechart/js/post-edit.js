function WPSimplechartApp(){
	var app = {
		appOrigin : null,
		modalElements : {
			container : '<div id="simplechart-modal"><a id="simplechart-close" href="#">{{closeModal}}</a><iframe id="simplechart-frame" src="{{iframeSrc}}"></iframe></div>',
			backdrop : '<div id="simplechart-backdrop"></div>'
		},
		modalInitialized : false,
		chartData : null,
		childWindow : null,

		init : function(){
			window.addEventListener('message', this.receiveMessages );
			this.inputEl = document.getElementById( WPSimplechartBootstrap.postmetaKey );
			this.inputTemplateEl = document.getElementById( 'simplechart-template' );
			this.imgInputEl = document.getElementById( 'simplechart-png-string' );
			this.inputChartUrlEl = document.getElementById( 'simplechart-chart-url' );
			this.inputChartIdEl = document.getElementById( 'simplechart-chart-id' );

			$( '#simplechart-clear' ).click( this.clearInputEl );
			$( '#simplechart-launch' ).click( this.openModal );
			// just to make dev go a little faster...
			$( '#simplechart-launch' ).click();
		},

		setAppOrigin : function(){
			var tempEl = document.createElement( 'a' );
			tempEl.href = WPSimplechartBootstrap.appUrl;
			return tempEl.origin;
		},

		clearInputEl : function(e){
			e.preventDefault();
			app.inputEl.setAttribute('value', '');
			app.inputTemplateEl.setAttribute('value', '');
			app.inputChartUrlEl.setAttribute('value', '');
			app.inputChartIdEl.setAttribute('value', '');
		},

		openModal : function(){
			if ( ! app.modalInitialized ) {
				app.modalElements.container = app.modalElements.container.replace('{{iframeSrc}}', WPSimplechartBootstrap.appUrl);
				app.modalElements.container = app.modalElements.container.replace('{{closeModal}}', WPSimplechartBootstrap.closeModal);
				$( 'body' ).append( app.modalElements.container + app.modalElements.backdrop );
				$( '#simplechart-close' ).click( function(){
					$( '#simplechart-backdrop, #simplechart-modal' ).hide();
				} );

				app.modalInitialized = true;
			} else {
				$( '#simplechart-backdrop, #simplechart-modal' ).show();
			}
		},

		/*
		 * postMessage send/receive functions
		 */
		receiveMessages: function(e) {
			if ( _.isUndefined( e.data.src ) || 'simplechart' !== e.data.src ) {
				return;
			}

			app.appOrigin = app.appOrigin || app.setAppOrigin();
			if ( e.origin !== app.appOrigin ){
				throw( 'Illegal Simplechart postMessage from ' + e.appOrigin );
				return;
			}

			if ( app.isFrameReadyMessage( e.data ) ){
				console.log( 'window received ready message from Simplechart iframe');
				app.childWindow = app.childWindow || document.getElementById('simplechart-frame').contentWindow;
				app.sendSimplechartOptions();
				app.sendSavedData();
				return;
			}

			// parse data for iframe
			app.chartData = JSON.parse( e.data.data.chartData );

			// store template string
			app.inputTemplateEl.value = app.chartData.template;

			// store rest of JSON for chart
			delete app.chartData.template;
			app.inputEl.value = JSON.stringify( app.chartData );

			// store base64 image string
			app.imgInputEl.value = e.data.data.chartImg;

			// store published chart URL
			if ( ! _.isUndefined( app.chartData.chartUrl ) ){
				app.inputChartUrlEl.value = app.chartData.chartUrl;
			}

			// store published chart ID
			if ( ! _.isUndefined( app.chartData.id ) ){
				app.inputChartIdEl.value = app.chartData.id;
			}

			// set post title to chart name if empty
			$title =  $( '#title' );
			if ( ! $title.val() ){
				$title.val( decodeURIComponent( app.chartData.meta.title ) ).focus();
			}

			console.log( 'parent window received data from app iframe' );
			console.log( app.inputTemplateEl.value, app.chartData );


			// close modal
			$( '#simplechart-close' ).click();
		},

		sendSimplechartOptions: function() {
			var options = window.simplechartSiteOptions || false;

			var msgObj = {
				src : 'simplechart',
				channel : 'downstream',
				msg : 'options',
				data : options
			};
			app.childWindow.postMessage( msgObj, app.appOrigin );
		},

		sendSavedData : function(){
			var mergedFields = WPSimplechartBootstrap.data || null;
			if ( mergedFields ){
				mergedFields.template = WPSimplechartBootstrap.template;
			}

			var msgObj = {
				src : 'simplechart',
				channel : 'downstream',
				msg : 'savedData',
				data : mergedFields
			};
			app.childWindow.postMessage( msgObj, app.appOrigin );
		},

		isFrameReadyMessage : function(msgObj){
			return !_.isUndefined( msgObj.channel ) &&
				msgObj.channel === 'upstream' &&
				!_.isUndefined( msgObj.msg ) &&
				msgObj.msg === 'ready';
		}
	};

	// initialize when document is ready;
	if ( typeof $ === 'undefined' ){
		var $ = jQuery;
	}
	$(document).ready(function(){
		app.init();
	});

	return app;
};

if ( typeof pagenow !== 'undefined' && pagenow === 'simplechart' ){
	var WPSimplechart = WPSimplechartApp();
}
