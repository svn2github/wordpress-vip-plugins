/**
 * Models / Controllers for the Getty Images plugin
 *
 * @package Getty Images
 * @author  bendoh, thinkoomph
 */
(function($) {
	var media = wp.media;
	var getty = gettyImages;
	var GettyAttachments;

	// Simple Getty API module. Relies on CORS support
	var api = {
		key: 'ytkqdr79qavqfkrnn5z4rsjr',
		baseUrl: 'https://connect.gettyimages.com',

		// Make an API request, return a promise for the request
		request: function(endpoint, params, method, payload) {
			method = method || 'GET';
			var processData = true;
			console.log('[API] endpoint:', endpoint, 'params:', params, 'method:', method, 'payload: ', payload);
			
			if (endpoint[0] != '/') console.error('Invalid API call: ', endpoint);

			var url = this.baseUrl + endpoint;

			var data = params;
			if(method === 'POST') {
				url = url + '?'+ $.param(params);
				var data = JSON.stringify(payload);
				processData = false;
			}
			// Have we a session?
			var session = getty.user.get('session');

			// Is it valid?
			if(session && session.expires < new Date().getTime() / 1000) {
				getty.user.logout();
				session = false;
			}
			
			// Defer the request until we get authentication
			var result = $.Deferred();
			var defer = $.Deferred();
			var apiKey = this.key;

			defer
				.done(function() {
					var headers = { 'Api-Key': apiKey };
					if (session) headers['Authorization'] = 'Bearer ' + session.token;

					$.ajax(url, {
						data: data,
						type: method,
						accepts: 'application/json',
						contentType: 'application/json',
						headers: headers,
						processData: processData
					})
						.fail(result.reject)
						.done(function(data) {
							result.resolve(data);
						});
				})
				.fail(function() {
					// API must be unavailable. Messaging!
					result.reject();
				});

			defer.resolve();

			return result.promise();
		}
	};

	/**
	 * Attach plugin nonce to any WP AJAX calls
	 */
	getty.post = function(action, data) {
		data = data || {};

		data.nonce = getty.nonce;

		return media.post(action, data);
	}

	/**
	 * A single Getty Image result
	 */
	media.model.GettyAttachment = media.model.Attachment.extend({
		parse: function( resp, xhr ) {
			if ( ! resp )
				return resp;

			this.set('sizes', gettyImages.sizes);

			if(resp.display_sizes) {
				var compSize = _.find(resp.display_sizes, function(size) { return size.name == "comp"; })
				if(compSize) {
					resp.url_comp = compSize.uri;
				}

				var thumbSize = _.find(resp.display_sizes, function(size) { return size.name == "thumb"; });
				if(thumbSize) {
					resp.url_thumb = thumbSize.uri;
				}
			}
			
			// Convert date strings into Date objects.
			_.each([ 'date_created' ], function(field) {
				resp[field] = new Date(resp[field]);
			});

			return resp;
		},

		sync: function(method, model, options) {
			if(method == 'read') {
				this.getWpDetails();

				// Clone the args so manipulation is non-destructive.
				var args = _.clone( this.args );

				this.set('downloadingDetails', true);

				var self = this;

				var fieldsQuery = '?fields=allowed_use,artist,artist_title,asset_family,caption,collection_id,collection_name,color_type,copyright,credit_line,date_created,date_submitted,download_sizes,editorial_segments,editorial_source,event_ids,graphical_style,id,keywords,license_model,people,quality_rank,referral_destinations,thumb,title,product_types';

				if(getty.user.get('loggedIn')) {
					fieldsQuery = fieldsQuery + ',downloads,largest_downloads';
				}
				var path = '/v3/images/' + model.get('id') + fieldsQuery

				return api.request(path)
					.done(function(data) {
						self.set('haveDetails', true);
						var newModel = self.parse(data.images[0], this);
						self.set(newModel);

						if(getty.user.get('loggedIn')) {
							self.getDownloadAuthorizations();
						}
					})
					.fail(function(data) {
						// TODO: Error condition
					})
					.always(function() {
						self.unset('downloadingDetails');
					});
			}
		},

		getDownloadAuthorizations: function() {
			var sizes = this.get('download_sizes');

			// No downloadable image sizes anyway. Do nothing.
			if(!sizes || sizes.length === 0) {
				this.unset('sizesByAgreement');
				return;
			}

			//Group sizes by product type.
			var flattenSizes = [];
			for (var i = 0; i < sizes.length; i++) {
				var size = sizes[i];
				
				size.downloads.forEach(function(download) {
					var flatSize = _.extend(_.omit(size, 'downloads'), _.pick(download, 'product_id', 'product_type', 'uri'));

					if (flatSize.name != null) {
						var userProducts = getty.user.get('products');
						if (userProducts) {
							var sizeProduct = _.find(userProducts, 
							function (product) {
								 return product.id == flatSize.product_id; 
								});

							if (sizeProduct && sizeProduct.downloads_remaining) {
								flatSize.downloads_remaining = sizeProduct.downloads_remaining;
							}
						}

						flattenSizes.push(flatSize);
					}
				});
			}

			sizesByAgreement = _.groupBy(flattenSizes, 'product_type');

			//If there are multiple imagepacks available, offer the "youngest" one only so we download always against the same.
			if(sizesByAgreement.imagepack) {
				var youngestImagepackId = _.min(sizesByAgreement.imagepack, 'product_id').product_id;
				
				sizesByAgreement.imagepack = _.where(sizesByAgreement.imagepack, {product_id: youngestImagepackId });
			}

			var selectedProduct = Object.keys(sizesByAgreement)[0];
			
			if(selectedProduct) {
				var selectedDownloadSize = sizesByAgreement[selectedProduct][0];
			}
			this.set({ 'sizesByAgreement': sizesByAgreement, 'ProductOffering': selectedProduct, 'SelectedDownloadSize': selectedDownloadSize } );
		},

		download: function() {
			this.downloadWithAgreement();
		},

		downloadWithAgreement: function() {
			var self = this;

			this.set('downloading', true);

			var queryParams = {
				height: this.get('SelectedDownloadSize').height,
				product_id: this.get('SelectedDownloadSize').product_id,
				product_type: this.get('SelectedDownloadSize').product_type,
				auto_download: false
			};

			var payload = {download_details: { download_notes: "", project_code: "" }};

			var url = '/v3/downloads/images/' + this.get('id');
			api.request(url, queryParams, 'POST', payload)
				.done(function(response) {
					var meta = self.toJSON();

					delete meta.attachment;
					delete meta[''];

					// Get the URL, forward to WP for sideloading
					getty.post( 'getty_images_download', {
						url: response.uri,
						post_id: $('#post_ID').val(),
						meta: meta
					})
						.done(function(wpresponse) {
							self.downloaded(wpresponse);
						})
						.fail(function(wpresponse) {
							console.log(wpresponse);
						})
						.always(function() {
							self.unset('downloading');

							var refreshState = function(state) {
								var lib;

								if(!state) {
									return;
								}

								lib = state.get('library');

								if(!lib) {
									return;
								}

								var r = parseInt(lib.props.get('getty-refresh'));

								if(!r || isNaN(r)) {
									r = 1;
								}

								lib.props.set('getty-refresh', r + 1);
							}

							// Force all other attachment queries to refresh
							for(var frame in media.frames) {
								_.each([ 'insert', 'select', 'gallery', 'featured-image' ], function(state) {
									refreshState(media.frames[frame].state(state));

									var modal = media.editor.get();
									if(modal) {
										refreshState(modal.state(state));
									}
								});
							}
						});
				});
		},
		
		// Image downloaded! Yay!
		downloaded: function(response) {
			var s = window.getty_s;
			this.set('attachment', new media.model.Attachment(response));

			// Refresh Download counts for products
			getty.user.refreshProducts();

			// Clear out any cached queries in media library
			if(s) {
				s.events = 'event6';
				s.prop1 = s.eVar1 = s.prop2 = s.eVar2 = '';
				s.prop3 = s.eVar3 = this.get('id');
				getty.tl();
			}
		},

		// Get WordPress image details
		getWpDetails: function() {
			// Get the URL, forward to WP for sideloading
			var self = this;
			getty.post( 'getty_image_details', {
				ImageId: this.get('id')
			})
				.done(function(response) {
					self.set('attachment', new media.model.Attachment(response));
				});
		}
	}, {
		create: function( attrs ) {
			return GettyAttachments.all.push( new media.model.GettyAttachment(attrs) );
		},

		get: _.memoize( function( id, attachment ) {
			return GettyAttachments.all.push( attachment || new media.model.GettyAttachment({ id: id }) );
		})
	});

	/**
	 * The collection of Getty Images search resulggts.
	 */
	GettyAttachments = media.model.GettyAttachments = media.model.Attachments.extend({
		model: media.model.GettyAttachment,

		initialize: function(models, options) {
			options = options || {};

			// Keep a controller reference
			this.controller = options.controller;

			// Queried search properties
			this.props = new Backbone.Model();

			// Queue up query changes in this model, but don't refresh search
			// until executed
			this.propsQueue = new Backbone.Model();

			// Result data: total, refinements
			this.results = new Backbone.Model();

			// Propagate changes to props to propsQueue:
			this.props.on('change', this.changeQueuedProps, this);
		},

		// Forward changes to search props to the queue
		changeQueuedProps: function() {
			this.propsQueue.set(this.props.attributes);
		},

		// Observe changes to result properties in mirrored collection
		observe: function(attachments) {
			attachments.results.on('change', this.syncResults, this);

			return media.model.Attachments.prototype.observe.apply(this, arguments);
		},

		// Stop observing result properties in mirrored collection
		unobserve: function(attachments) {
			attachments.results.off('change', this.syncResults, this);

			return media.model.Attachments.prototype.unobserve.apply(this, arguments);
		},

		// Sync up results with mirrored query
		syncResults: function(model) {
			this.results.set(model.changed);
		},

		// Perform a search with queued search properties
		search: function() {
			var searchTerm = this.propsQueue.get('search'),
				s = window.getty_s;

			if(typeof searchTerm != 'string' || !searchTerm.match(/[^\s]/)) {
				return;
			}

			if(this.propsQueue.get('Refinements') && this.propsQueue.get('Refinements').length == 0) {
				this.propsQueue.unset('Refinements');
			}

			if(getty.user.settings.get('mode') == 'embed') {
				this.propsQueue.set('EmbedContentOnly', "true");
			}
			else {
				this.propsQueue.unset('EmbedContentOnly');
			}

			var query = media.model.GettyQuery.get(this.propsQueue.toJSON());

			if(query !== this.mirroring) {
				this.reset();
				this.props.clear();
				this.props.set(this.propsQueue.attributes);

				this.results.unset('total');
				this.results.set('searching', true);
				this.results.set('searched', false);

				this.mirror(query);

				getty.tracking.search.search_term = this.props.get('search');

				if(s) {
					s.events = 'event2';
					s.prop1 = s.eVar1 = s.prop3 = s.eVar3 = '';
					s.prop2 = s.eVar2 = this.props.get('search');
					getty.tl();
				}
			}

			// Force reset of attributes for cached queries
			if(query.cached) {
				this.results.clear();
				this.results.set(query.results.attributes);
			}
		},
	}, {
		filters: {
			search: function(attachment) {
				if (!this.props.get('search'))
          return true;

        return _.any(['Title', 'Caption', 'ImageId', 'UrlComp', 'ImageFamily'], function(key) {
          var value = attachment.get(key);
          return value && -1 !== value.search(this.props.get('search'));
        }, this);
			}
		}
	}	);

	// Cache all attachments here. TODO: Memory clean up?
	GettyAttachments.all = new GettyAttachments();

	var GettyEventRefinements = new Backbone.Collection();

	/**
	 * The Getty Images query and parsing model
	 */
	media.model.GettyQuery = media.model.Query.extend({
		initialize: function( models, options ) {
			media.model.Query.prototype.initialize.apply(this, arguments);

			options = options || {};

			// Track refinement options and total
			this.results = new Backbone.Model();
			this.results.unset('total');
			this.results.set('searching', false);
			this.results.set('searched', false);

			// Track number of results returned from API separately from
			// the size of the collection, as the API MAY give back duplicates
			// which will cause the paging to screw up since the collection
			// will have fewer results than were actually returned.
			this.numberResults = 0;
		},

		// Override more() to return a more-deferred deferred object
		// and not bother trying to use Backbone sync() or fetch() methods
		// to get the data, since this is a very custom workflow
		more: function( options ) {
			if ( this._more && 'pending' === this._more.state() )
				return this._more;

			if(!this.hasMore())
				return $.Deferred().resolveWith(this).promise();

			if(_.isEmpty(this.props.get('search')))
				return $.Deferred().resolveWith(this).promise();

			// Flag the search as executing
			this.results.set('searching', true);

			// Build searchPhrase from any main query + refinements
			var searchPhrase = this.props.get('search');

			if(this.props.get('SearchWithin')) {
				searchPhrase += ' ' + this.props.get('SearchWithin');
			}

			var args = _.clone(this.args);
			this.page = Math.floor(this.numberResults / args.posts_per_page);
		
			
			var params = {
				phrase: searchPhrase,
				fields: 'id, title, caption, date_created, asset_family, artist, collection_name, comp, thumb',
				page_size: args.posts_per_page,
				page: this.page + 1,
				sort_order: this.props.get('ImageFamilies') === 'editorial' ? this.props.get('EditorialSortOrder') : this.props.get('CreativeSortOrder'),
				exclude_nudity: this.props.get('ExcludeNudity'),
				// XXX Refinements: this.props.get('Refinements'),
				embed_content_only: this.props.get('EmbedContentOnly')
			};

			if(this.props.get('GraphicStyles').length > 0) {
				params.graphical_styles = this.props.get('GraphicStyles').join(',');
			}

			if(this.props.get('Orientations').length > 0) {
				params.orientations = this.props.get('Orientations').join(',');
			}

			if(this.props.get('NumberOfPeople').length > 0) {
				params.number_of_people = this.props.get('NumberOfPeople').join(',');
			}

			if(this.props.get('AgeOfPeople').length > 0) {
				params.age_of_people = this.props.get('AgeOfPeople').join(',');
			}

			if(this.props.get('ImageStyle').length > 0) {
				params.compositions = this.props.get('ImageStyle').join(',');
			}

			if(this.props.get('Ethnicity').length > 0) {
				params.ethnicity = this.props.get('Ethnicity').join(',');
			}

			// Add in any applicable product offerings so the user only gets
			// results for product offerings he owns
			var userProducts = getty.user.get('products');

			if(userProducts && userProducts.length > 0) {
				params.product_types = _.pluck(userProducts, 'type').join(',');
			}
			
			// Get refinement options for the first page of results only
			// XXX 
			// if(this.page == 0) {
			// 	request.ResultOptions.RefinementOptionsSet = 'RefinementSet2';
			// }

			// Proxy the deferment from the API query so we can retry if
			// necessary
			if('deferred' in this) {
				this.deferred.retry++;
			}
			else {
				this.deferred = $.Deferred();
				this.deferred.retry = 0;
			}
			
			var asset_family = this.props.get('ImageFamilies');
			var path = '/v3/search/images/' + asset_family;

			var self = this;
			this._more = api.request(path, params)
				.done(function(response) {
					self.set(self.parse(response), { remove: false });

					self.numberResults += response.images.length;

					if(response.images.length == 0 || response.images.length < args.posts_per_page) {
						self._hasMore = false;
					}

					self.deferred.resolve(response);
					delete self.deferred;
				})
				.fail(function(response) {
					if(self.deferred.retry < 3) {
						self.more();
					}
					else {
						self.deferred.reject(response);
						delete self.deferred;
					}
				})
				.always(function() {
					self.results.set('searching', false);
				});

			return (this.deferred ? this.deferred : $.Deferred().reject()).promise();
		},

		parse: function(response, xhr) {
			this.results.set('total', response.result_count);	

			this.results.set('searched', true);

			var attachments = _.map(response.images, function(item) {
				var id, attachment, newAttributes;

				id = item.id;

				attachment = wp.media.model.GettyAttachment.get( id );
				newAttributes = attachment.parse( item, xhr );

				if ( ! _.isEqual( attachment.attributes, newAttributes ) )
					attachment.set( newAttributes );

				return attachment;
			});

			return attachments;
		}
	}, {
		defaultArgs: {
			posts_per_page: 50
		}
	});

	// Ensure our query object gets used instead, there's no other way
	// to inject a custom query object into media.model.GettyQuery.get
	// so we must override. This function caches distinct queries
	// so that re-queries come back instantly. Though there's no memory cleanup...
	media.model.GettyQuery.get = (function(){
		var queries = [];
		var Query = media.model.GettyQuery;

		return function( props, options ) {
			var args     = {},
					defaults = Query.defaultProps,
					query;

			// Remove the `query` property. This isn't linked to a query,
			// this *is* the query.
			delete props.query;

			// Fill default args.
			_.defaults( props, defaults );

			// Generate the query `args` object.
			// Correct any differing property names.
			_.each( props, function( value, prop ) {
				if ( _.isNull( value ) )
					return;

				args[ Query.propmap[ prop ] || prop ] = value;
			});

			// Fill any other default query args.
			_.defaults( args, Query.defaultArgs );

			// Search the query cache for matches.
			query = _.find( queries, function( query ) {
				return _.isEqual( query.args, args );
			});

			// Otherwise, create a new query and add it to the cache.
			if ( ! query ) {
				query = new Query( [], _.extend( options || {}, {
					props: props,
					args:  args
				} ) );

				// Only push successful queries into the cache.
				query.more().done(function() {
					queries.push(query);
				});
			}
			else {
				// Flag that this was a cached query
				query.cached = true;

				// Reverse the models for cached queries
				// because there's a bug in the media library
				// that causes the images to come back
				// in the reverse order when it's cached
				query.models.reverse();
			}

			return query;
		};
	}());

	/**
	 * Getty user and session management
	 */
	media.model.GettyUser = Backbone.Model.extend({
		defaults: {
			id: 'getty-images-login',
			username: '',
			loginToken: '',
			seriesToken: '',
			loginTime: 0,
			loggedIn: false
		},

		initialize: function() {
			var settings = {};

			try {
				settings = JSON.parse($.cookie('wpGIc')) || {};
			} catch(ex) {
			}

			this.settings = new Backbone.Model(settings);
			this.settings.on('change', this.updateUserSettings, this);
		},

		updateUserSettings: function(model, values) {
			$.cookie('wpGIc', JSON.stringify(model));
		},

		// Restore login session from cookie.  Sets loggedIn if the session
		// expiration has not passed yet.
		restore: function() {
			var loginCookie = jQuery.cookie('wpGIs');

			this.unset('error');

			var loggedIn = false;

			if(loginCookie && loginCookie.indexOf(':') > -1) {
				var un_token_series = loginCookie.split(':');

				if(un_token_series.length == 4) {
					this.set('username', un_token_series[0]);

					this.set('session', {
						token: un_token_series[1],
						secure: un_token_series[2],
						expires: un_token_series[3]
					});

					// Consider ourselves logged in if expiration hasn't passed.
					// If less than 5 minutes away, try to refresh from server.
					var diff = un_token_series[3] - new Date().getTime();

					if(diff > 0) {
						loggedIn = true;

						// Pull in available product offerings for the user
						this.refreshProducts();
					}
				}
			}

			this.set('loggedIn', loggedIn);
		},

		tokenLogin: function(token, isTrackingEnabled) {
			var s = window.getty_s;

			this.settings.set("omniture-opt-in", isTrackingEnabled);

			try {
				var tokenDetail = this.getTokenDetail(token);
			} catch (e) {
				this.setTokenError();
				return;
			}

			this.set('loggingIn', true);
			this.unset('error');
			this.set('session', { token: token, expires: tokenDetail.expires });

			var self = this;
			return api.request('/v3/customers/current')
				.done(function(result) {
					self.set('username', result.user_name);
					
					// Stick the session data in a persistent cookie
					self.refreshSession(token, tokenDetail.expires);
					
					getty.tracking.user.username = self.get('username');
					
					if(s) {
						s.events = 'event1';
						s.prop2 = s.eVar2 = s.prop3 = s.eVar3 = '';
						s.prop1 = s.eVar1 = self.get('username');
						getty.tl();
					}
				})
				.fail(function(result) {
					self.set('loggedIn', false);
					self.trigger('change:loggedIn');
					self.set('promptLogin', true);
					self.setTokenError();
					getty.tracking.user.username = '';
				})
				.always(function() {
					self.set('loggingIn', false);
				});
		},
		
		setTokenError: function() {
			this.set('error', "Please verify that you've entered everything correctly.");
		},
		
		getTokenDetail: function(token) {
			var parseTokenDateTime = function(str) {
				var giEpoch = new Date('2011-01-01T00:00:00.000').getTime();
				var bs = window.atob(str);
			
				var f = function(str, pos) {
					return str.charCodeAt(pos) * Math.pow(256,pos);
				}
				var seconds = f(bs, 0) + f(bs, 1) + f(bs, 2) + f(bs, 3);

				return giEpoch + (seconds * 1000);
			};
			
			token = token.split('|')[1];
			var tokenData = window.atob(token).split("\n");
			var tokenDetails = {};

			tokenDetails.systemId = tokenData[1];
			tokenDetails.userId = parseInt(tokenData[2]);
			tokenDetails.created = parseTokenDateTime(tokenData[3]);
			tokenDetails.expires = parseTokenDateTime(tokenData[4]);
			tokenDetails.secureOnly = tokenData[5];
			tokenDetails.clientId = tokenData[6];
			tokenDetails.clientIp = tokenData[7];
			tokenDetails.rememberedUser = tokenData[8];
			tokenDetails.authId = tokenData[9];
			tokenDetails.renewalEnds = parseTokenDateTime(tokenData[10]);
			tokenDetails.actAsSystemId = tokenData[11];
			tokenDetails.allowInternalAccess = tokenData[12];
			tokenDetails.visitorId = tokenData[13];
			tokenDetails.adminId = tokenData[14];
			return tokenDetails;
		},
		
		refreshProducts: function() {
			var self = this;
			api.request('/v3/products')
				.done(function (result) {
					var productsWithName = result.products.map(function (product) { product.name = getty.getProductName(product.type); return product; });
					self.set('products', productsWithName);
				});
		},

		refreshSession: function(token, expiration) {
			// Pluck the values we need to save the session.
			var session = [
				this.get('username'),
				token,
				token,
				expiration
			];

			// Save it in a cookie: nom.
			$.cookie('wpGIs', session.join(':'));

			// Restore from the cookie
			this.restore();
		},

		logout: function() {
			// Log out, which will clear (most) of the cookie values out
			// so the username can be retained but the login and session
			// tokens get erased
			var self = this;

			// Throw away expired sessions.
			$.cookie('wpGIs', '');

			// Throw away invalidated attributes
			self.unset('session');
			self.unset('products');

			getty.user.settings.unset('mode')
			self.set('loggedIn', false);

			// Throw away all download auths and image statuses
			media.model.GettyAttachments.all.each(function(image) {
				image.unset('sizesByAgreement');
				image.unset('Authorizations');
				image.unset('Status');
			});

			// Trash cookie
			$.cookie('wpGIs', [ '', '', '', '' ].join(':'));
		}
	});

	/**
	 * Display options based on an existing attachment
	 */
	media.model.GettyDisplayOptions = Backbone.Model.extend({
		initialize: function() {
			this.attachment = this.get('attachment');

			if(!this.attachment) {
				return;
			}

			this.attachment.on('change:attachment', function() {
				this.wpAttachment = this.attachment.get('attachment');
				this.fetch();
			}, this);
			this.wpAttachment = this.attachment.get('attachment');
			this.set('sizes', _.clone(getty.sizes));
			//set defaults for embeds
			if ( !gettyImages.isWPcom && !gettyImages.user.get('loggedIn') ) {
				this.set({
					align: 'none',
					size: 'full',
				});
			}
			this.fetch();
		},

		sync: function(method, model, options) {
			if(method == 'read') {
				// If there's an attachment, pull the largest size in the database,
				// calculate potential sizes based on that
				this.image = new Image();
				var url;

				if(!this.wpAttachment) {
					url = this.attachment.get('url_comp');

					this.set('caption', this.attachment.get('caption'));
					this.set('alt', this.attachment.get('title'));
				}
				else {
					url = this.wpAttachment.get('url');
					this.set('caption', this.wpAttachment.get('caption'));
					this.set('alt', this.wpAttachment.get('alt'));
				}

				$(this.image).on('load', this.loadImage());
				this.set('downloadingSizes',true);
				this.image.src = url;
			}
		},

		// Closure-in-closure because we can't control the binding of
		// 'this' with jQuery-registered event handlers
		loadImage: function() {
			var self = this;

			return function(ev) {
				var sizes = {};
				var ar = this.width / this.height;

				// Constrain image to image sizes
				if ( gettyImages.isWPcom || gettyImages.user.get('loggedIn') ) {
					_.each(gettyImages.sizes, function(size, slug) {
						var cr = size.width / size.height;
						var s = { label: size.label };

						s.url = this.src;

						if(ar > cr) {
							// Constrain to width
							s.width = parseInt(size.width);
							s.height = parseInt(size.width / ar);
						}
						else {
							// Constrain to height (or square!)
							s.width = parseInt(ar * size.height);
							s.height = parseInt(size.height);
						}

						sizes[slug] = s;
					}, this);
				} else {
					_.each(gettyImages.embedSizes, function(size, slug) {
						sizes[slug] = {
							label: size.label,
							width: parseInt(size.scale * this.width),
							height: parseInt(size.scale * this.height),
						};
					}, this);
				}

				sizes.full = {
					label: getty.text.fullSize,
					width: this.width,
					height: this.height,
					url: this.src
				}

				self.unset('downloadingSizes');
				self.set('sizes', sizes);
			}
		}
	});

})(jQuery);
