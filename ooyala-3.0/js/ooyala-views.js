/**
 * Views for the Ooyala plugin
 *
 * @package Ooyala
 * @author  bendoh, thinkoomph
 */
(function($) {
	var media = wp.media;

	/**
	 * Extend both top-level media frames with an additional mode for searching
	 * and embedding videos from Ooyala
	 */
	var OoyalaFrame = function(parent) {
		return {
			createStates: function() {
				parent.prototype.createStates.apply(this, arguments);

				this.states.add([
					new ooyala.State({
						id: 'ooyala',
						title: ooyala.text.title,
						titleMode: 'ooyala-title-bar',
						multiple: true,
						content: 'ooyala-browse',
						router: false,
						menu: 'default',
						toolbar: 'ooyala-toolbar',
						selection: new ooyala.model.Selection(null, {multiple: true}),
						edge: 120,
						gutter: 8
					}),
				]);
			},
		}
	};

	media.view.MediaFrame.Post = media.view.MediaFrame.Post.extend(OoyalaFrame(media.view.MediaFrame.Post));

	/**
	 * The Ooyala Browser view
	 */
	ooyala.view.Browser = media.view.AttachmentsBrowser.extend({
		template: media.template('ooyala-attachments-browser'),
		className: 'attachments-browser ooyala-attachments-browser',

		events: {
			'keyup .search': 'searchOnEnter',
			'click .ooyala-more': 'more',
			'click .ooyala-label': 'refineLabel',
			'click .ooyala-clear-label': 'clearLabel',
			'click .ooyala-set-featured': 'setFeatured',
		},

		initialize: function() {
			this.render();

			// Set various flags whenever the results description model  changes
			this.collection.results.on('change:searched change:refinements', this.updateFlags, this);
			this.collection.results.on('change:searching', this.updateSearching, this);

			// Set .have-more flag when there're more results to load
			this.collection.on('add remove reset', this.updateHaveMore, this);
			// Update the running totals label after each search
			this.collection.results.on('change:searching', this.updateTotal, this);

			this.controller.state().on('activate', this.ready, this);

			// update visual identifier of label refinement on change
			this.collection.propsQueue.on('change:label', this.labelState, this);

			// visually de-select the attachment
			this.options.selection.on('selection:unsingle', function() {
				this.$('.attachment.details').removeClass('details');
			}, this);

			media.view.AttachmentsBrowser.prototype.initialize.apply(this, arguments);
		},

		/**
		 * Set featured image to the live preview image
		 */
		setFeatured: function() {
			var single = this.options.selection.single()
			  , $thumbnail = this.$el.find('.thumbnail')
			  , attachment = single.id ? ooyala.model.Attachments.all.get(single.id) : false
				, view = this
			;

			if(attachment) {
				$thumbnail.addClass('featured-loading');
				$thumbnail.find('button').prop('disabled', true);

				attachment.setFeatured().done(function() {
					view.sidebar.get('details').render();
				});
			}

		},

		/**
		 * When activated, restore any injected content, focus on search field
		 */
		ready: function() {
			this.updateFlags(this.collection.results);
			this.updateTotal();
			// load a blank search to show the newest #(page) results
			this.search(); //TODO: maybe not do this on *every* ready event, but only the first ever
			// focus on the search field
			this.$el.find('.search-primary').focus();
			// load up the players associated with the account
			ooyala.model.DisplayOptions.players();
			// load the labels associated with the account
			ooyala.model.DisplayOptions.labels();
			$('.ooyala-browse-link').addClass('ooyala-browsing');
			// update label state
			this.labelState();
		},

		// Create attachments view
		updateContent: function() {
			if( !this.attachments )
				this.createAttachments();
		},

		// Start a new search with a label and current search term
		refineLabel: function(ev) {
			ev.preventDefault();
			this.collection.propsQueue.set('label', _.unescape($(ev.target).text()));
			this.search();
		},

		// Clear the currently selected label
		clearLabel: function(ev) {
			ev.preventDefault();
			this.collection.propsQueue.unset('label');
			this.search();
		},

		// update the label state
		labelState: function() {
			var label = this.collection.propsQueue.get('label');
			this.$('.ooyala-toolbar').toggleClass('has-label',!!label);
			this.$('.ooyala-selected-label').text(label);
		},

		// Create sidebar and register hooks on selection
		createSidebar: function() {
			var options = this.options,
				selection = options.selection,
				sidebar = this.sidebar = new media.view.Sidebar({
					controller: this.controller
				});

			this.views.set('.ooyala-sidebar', sidebar);

			selection.on( 'selection:single', this.createSingle, this );
			selection.on( 'selection:unsingle', this.disposeSingle, this );

			if( selection.single() )
				this.createSingle();
		},

		// Create single attachment detail view for sidebar
		createSingle: function() {
			var sidebar = this.sidebar,
				single = this.options.selection.single();

			var attachment = single.id ? ooyala.model.Attachments.all.get(single.id) : false;

			if(attachment) {
				var details = new ooyala.view.Details({
					controller: this.controller,
					model: attachment,
					priority:   80
				});

				sidebar.set('details', details);

				var displayOptions = {
					model: this.model.display(attachment),
					priority: 200,
				};

				sidebar.set('display', new ooyala.view.AttachmentDisplaySettings(displayOptions));
				sidebar.set('playlist', new ooyala.view.PlaylistEmbedSettings(displayOptions));

				this.$('.ooyala-set-featured').blur();
			}
		},

		disposeSingle: function() {
			var sidebar = this.sidebar;
			sidebar.unset('details');
			sidebar.unset('display');
			sidebar.unset('playlist');
		},

		createUploader: function() {}, // we aren't using the WP uploader for ooyala

		// A 'running' total of search results
		updateTotal: function() {
			var total = this.collection.length || 0;

			// add a plus sign if there are more results that may be incoming
			var separated = new Number(total).commaString() + ( this.haveMore() ? '+' : '' );

			if(total > 0) {
				if(total == 1) {
					this.total.$el.text(ooyala.text.oneResult.replace('%d', separated));
				}
				else {
					this.total.$el.text(ooyala.text.results.replace('%d', separated));
				}

				this.refresh.$el.show();
			}
			else {
				this.total.$el.text(ooyala.text.noResults);
			}
		},

		// Handle return key in primary search or click on "Search" button
		// This starts a new top-level search
		searchOnEnter: function(ev) {
			if(ev.keyCode == 13) {
				this.search();
			}
		},

		// Start a new search
		search: function() {
			this.collection.search();
		},

		// Create the Search Toolbar, containing filters
		createToolbar: function() {
			var self = this;

			// Create the toolbar
			this.toolbar = new media.view.Toolbar({
				className: 'ooyala-toolbar',
				controller: this.controller,
				model: this.collection
			});

			// Make primary come first.
			this.toolbar.views.set([ this.toolbar.primary, this.toolbar.secondary ]);

			this.total = new media.View({
				tagName: 'span',
				className: 'search-results-total',
				priority: -20
			});

			this.refresh = new ooyala.view.Refresh({
				collection: this.collection,
				priority: -25
			});

			this.label = new ooyala.view.LabelRefinement({
				priority: -30
			});

			// Wrap search input in container because IE10 ignores left/right absolute
			// positioning on input[type="text"]... because.
			this.searchContainer = new media.View({ className: 'ooyala-search-input-container' });

			this.searchContainer.views.add(new ooyala.view.Search({
				className: 'search search-primary',
				model:      this.collection.propsQueue,
				controller: this,
				priority:   0,
				attributes: {
					type: 'search',
					placeholder: ooyala.text.searchPlaceholder,
					tabindex: 10
				}
			}));

			// Views with negative priority get put in secondary toolbar area
			this.toolbar.set({
				search: this.searchContainer,

				// Plain objects get turned into buttons
				searchButton: {
					text: ooyala.text.search,
					click: function() { self.search() },
					priority: 10,
				},

				total: this.total,

				refresh: this.refresh,

				label: this.label,
			});

			this.views.set('.ooyala-search-toolbar', this.toolbar);
		},

		createAttachments: function() {
			// No-op the scroll handler, making paging manual.
			// works better with a slow API.
			// >> TODO: doesn't this no op the scroll handler universally for all attachment views??
			media.view.Attachments.prototype.scroll = function(ev) {
				// Don't bubble upwards and out of the modal
				if(ev) {
					ev.stopPropagation();
				}
			};

			this.attachments = new ooyala.view.Attachments({
				controller: this.controller,
				collection: this.collection,
				selection:  this.options.selection,
				model:      this.model,
				sortable:   this.options.sortable,
				refreshThreshold: 2,

				// The single `Attachment` view to be used in the `Attachments` view.
				AttachmentView: this.options.AttachmentView
			});

			this.views.set('.ooyala-results', this.attachments);

			// Create "More" button, because automatic paging is just too slow with
			// this API.
			this.moreButton = new ooyala.view.More();
			this.moreButton.render(); // Load in the template, just once.

			this.collection.on('add reset', this.updateMore, this);
			this.collection.results.on('change', this.updateMore, this);

			this.updateMore();
		},

		// keep "More..." link at the end of the collection
		updateMore: function(model, collection, options) {
			// Always keep add more link at the end of the list
			if(this.attachments) {
				this.attachments.$el.append(this.moreButton.$el);
			}

			this.updateHaveMore();
		},

		// Load more results
		more: function() {
			this.collection.more();
		},

		/**
		 * Show loading state
		 */
		updateSearching: function(model) {
			this.$el.toggleClass('search-loading', model.get('searching'));

			var button = this.controller.toolbar.view.$el.find('.media-button-searchButton');
			if(model.get('searching')) {
				button.attr('disabled', 'disabled');

				if(model.spinner) {
					model.spinner.stop();
				}

				var opts = { className: 'ooyala-spinner', color: '#888' };
				var $el;

				if(this.haveMore()) {
					opts.color = '#ddd';
					$el = this.moreButton.$el.find('.ooyala-more-spinner');
				}
				else {
					opts.color = '#888';
					opts.lines = 11;
					opts.length = 21;
					opts.width = 9;
					opts.radius = 38;
					$el = this.$el.find('.ooyala-search-spinner')
				}

				model.spinner = new Spinner(opts);
				model.spinner.spin($el[0]);
			}
			else if(model.spinner) {
				button.removeAttr('disabled');
				model.spinner.stop();
			}
		},

		/**
		 * Add flags to container for various states
		 */
		updateFlags: function(model) {
			this.$el.toggleClass('have-searched', !!model.get('searched'));
			this.$el.toggleClass('have-results', this.collection.length > 0);
		},

		/**
		 * Are there more images to be loaded?
		 */
		haveMore: function() {
			return this.collection.length > 0 && this.collection.hasMore();
		},

		/**
		 * Add .have-more flag to container when there are more results
		 * to be loaded.
		 */
		updateHaveMore: function(model, collection) {
			this.$el.toggleClass('have-more', this.haveMore());
			this.$el.toggleClass('no-more', !this.haveMore());
		}
	});

	/**
	 * Playlists browser
	 */
	ooyala.view.Playlists = media.View.extend({

		tagName: 'div',
		className: 'ooyala-playlists',
		events: {
			'click .ooyala-playlists-list li': 'selectPlaylist',
		},

		initialize: function(options) {
			this.playlistOptions = options.playlistOptions;
			this.controller.on('change:account', function() {
				this.playlistOptions.set('playlist', null);
			}, this);
			this.createBrowser();
			this.createSidebar();
			this.playlistOptions.on('change:playlist', this.updateSidebar, this);
			media.View.prototype.initialize.apply(this, arguments);
		},

		createBrowser: function() {
			var browser = this.browser = new ooyala.view.PlaylistsBrowser({
				controller: this.controller,
				model: this.playlistOptions,
			});

			this.views.add(browser);
		},

		createSidebar: function() {
			var sidebar = this.sidebar = new media.view.Sidebar({
				controller: this.controller,
			});
			this.views.add(sidebar);
		},

		updateSidebar: function() {
			var sidebar = this.sidebar;
			if (this.playlistOptions.get('playlist')) {
				var displayOptions = {
					model: this.playlistOptions,
					priority: 200,
				};

				sidebar.set('details', new ooyala.view.PlaylistDetail(displayOptions));
				sidebar.set('display', new ooyala.view.AttachmentDisplaySettings(displayOptions));
				sidebar.set('playlist', new ooyala.view.PlaylistEmbedSettings(displayOptions));
			} else {
				sidebar.unset('details');
				sidebar.unset('display');
				sidebar.unset('playlist');
			}
		},

		selectPlaylist: function(ev) {
			this.playlistOptions.set('playlist', $(ev.target).data('id'));
		},

	});

	/**
	 * Playlist details
	 */
	ooyala.view.PlaylistDetail = media.View.extend({

		tagName: 'div',
		className: 'ooyala-playlist-detail',
		template: media.template('ooyala-playlist-detail'),

		initialize: function() {
			this.model.on('change:playlist', this.render, this);
			media.View.prototype.initialize.apply(this, arguments);
		},

		prepare: function() {
			var selected = ooyala.model.DisplayOptions.playlists().findWhere({id: this.model.get('playlist')});
			return selected ? selected.attributes : {};
		},

	});


	/**
	 * Display a list of available plugins
	 */
	ooyala.view.PlaylistsBrowser = media.View.extend({

		template: media.template('ooyala-playlists-browser'),
		className: 'ooyala-playlists-browser-wrapper',

		initialize: function() {
			this.options.playlists = ooyala.model.DisplayOptions.playlists();
			this.options.playlists.on('add remove reset sync error fetching', this.render, this);
			this.model.on('change:playlist', this.render, this);
			media.View.prototype.initialize.apply(this, arguments);
		},

	});

	/**
	 * Attachments
	 */
	ooyala.view.Attachments = media.view.Attachments.extend({
		prepare: function() {
			// Create all of the Attachment views, and replace
			// the list in a single DOM operation.
			if (this.collection.length) {
				this.views.set(this.collection.map(this.createAttachmentView, this));
			} else {
				this.views.unset();
			}
		},
	});

	/**
	 * View used to display the "More" button
	 */
	ooyala.view.More = media.View.extend({
		template: media.template('ooyala-more'),
		tagName: 'li',
		className: 'ooyala-more attachment'
	});

	/**
	 * View used to show current label refinement
	 */
	ooyala.view.LabelRefinement = media.View.extend({
		template: media.template('ooyala-label-search'),
		className: 'ooyala-label-search',
	});

	/**
	 *
	 */
	ooyala.view.Settings = media.view.Settings.extend({

		// need to add some functionality after basic rendering
		render: function() {
			media.view.Settings.prototype.render.apply(this, arguments);
			// change field to a number type dynamically
			// (the update handler in media.view.Settings does not support HTML5 inputs)
			try {
				this.$('.ooyala-numeric-input input').each(function() {
					this.type = 'number';
				});
			} catch(e) {}

			return this;
		},

		// add radio button support to updater
		update: function(key) {
			var $setting = this.$('[data-setting="' + key + '"]');

			media.view.Settings.prototype.update.apply(this, arguments);

			if ($setting.is('input[type="radio"]')) {
				$setting.filter('[value="' + this.model.get(key) + '"]').prop('checked', 'checked');
			}
		},

	});

	/**
	 * Asset Display Options
	 */
	ooyala.view.AttachmentDisplaySettings = ooyala.view.Settings.extend({
		template: media.template('ooyala-display-settings'),

		className: 'ooyala-settings-wrapper',

		initialize: function() {
			// re-renders settings section after preview image has been checked or status has been changed
			this.model.attachment.on('change:checkingImage', this.render, this);
			this.model.attachment.on('change:status', this.changeStatus, this);
			// hide or show playlist-specific fields
			this.model.on('change:playlist', this.render, this);
			// validate additional params when they change
			this.model.on('change:additional_params_raw', this.validateParams, this);
			// validate custom CSS, but only loosely
			this.model.on('change:custom_css', this.validateCSS, this);
			// user has confirmed that they wish to embed a potentially non-embeddable asset
			this.model.on('change:forceEmbed', this.render, this);
			this.options.players = ooyala.model.DisplayOptions.players();
			this.options.players.on('add remove reset sync error fetching', this.render, this);
			// add events to the default ones from media.view.Settings (so they are not overridden)
			_.extend( this.events, {
				'click .message a': 'dismissWarning',
			});
			ooyala.view.Settings.prototype.initialize.apply(this, arguments);
		},

		// need to add some functionality after basic rendering
		render: function() {
			ooyala.view.Settings.prototype.render.apply(this, arguments);
			// validate additional params
			this.validateParams();

			return this;
		},

		// update stuff if the attachment status changes
		changeStatus: function() {
			// add'l details may be available now that status has changed
			this.model.attachment.fetch();
			this.render();
		},

		// validate the additional parameters field for valid JSON or object literal notation
		validateParams: function() {
			// try parsing the input as an object literal, then convert to JSON
			// this is attempting to normalize the input as JSON
			var params = (this.model.get('additional_params_raw') || '').trim();

			if(params) {
				try {
					// encapsulating braces are optional
					if(!/^\s*\{.+\}\s*$/.test(params)) {
						params = '{' + params + '}';
					}

					// eval(), with justification:
					// Yes, this could execute some arbitrary code, but this only happening in the context of
					// wp-admin, as a means to see if this is a 'plain' JS object or string of JSON.
					// This will prevent the user from inadvertantly passing arbitrary code to the shortcode,
					// which in turn would put it right into a script tag ending up on the front end.
					params = eval('(' + params + ')');

					// empty objects, arrays, or primitives need not apply
					if ( typeof params == 'object' && !Array.isArray(params) && Object.keys(params).length ) {
						params = JSON.stringify( params );
					} else {
						params = false;
					}
				} catch(e) {
					//some error along the way...not valid JSON or JS object
					params = false;
				}
			}

			this.model.set('additional_params',params||'');
			this.$('.ooyala-additional-parameters').toggleClass('ooyala-error',params===false);
		},

		// loosely validate CSS just as a sanity check
		validateCSS: function() {
			var css = this.model.get('custom_css') || '';

			this.$('.ooyala-custom-css').toggleClass('ooyala-error', css && !css.match(new RegExp(ooyala.cssPattern)));
		},

		// dismiss warning about the asset potentially not being embeddable
		// this allows the shortcode to still be inserted with confirmation that it may not work
		dismissWarning: function(e) {
			e.preventDefault();
			this.model.set('forceEmbed',true);
			// pretend to select the asset again so that the insert button updates
			this.controller.state().get('selection').trigger('selection:single');
		},

	});

	/**
	 * Playlist Embed Options
	 */
	ooyala.view.PlaylistEmbedSettings = ooyala.view.Settings.extend({
		template: media.template('ooyala-playlist-settings'),

		className: 'ooyala-settings-wrapper ooyala-playlist-settings',

		initialize: function() {
			this.options.playlists = ooyala.model.DisplayOptions.playlists();
			this.options.playlists.on('add remove reset sync error fetching', this.render, this);
			// user has confirmed that they wish to embed a potentially non-embeddable asset
			this.model.on('change:forceEmbed', this.render, this);
			this.model.on('change:playlist', this.showOptions, this);
			// add events to the default ones from media.view.Settings (so they are not overridden)
			_.extend(this.events, {
				'change .js--caption': 'updateCaption',
			});
			ooyala.view.Settings.prototype.initialize.apply(this, arguments);
		},

		// need to add some functionality after basic rendering
		render: function() {
			// do not display anything if we cannot embed or if the plugin is not enabled
			if (!this.model.canEmbed() || !ooyala.model.DisplayOptions.playlistsIsActive()) {
				return this;
			}

			ooyala.view.Settings.prototype.render.apply(this, arguments);

			return this;
		},

		// when showing options, focus on the first one
		showOptions: function() {
			this.render();

			// only focus if we have a playlist and an attachment
			if (this.model.attachment.get('id') && this.model.get('playlist')) {
				this.$('input, select:not([data-setting="playlist"])').first().focus();
			}
		},

		// update the caption checkboxes/select
		updateCaption: function(ev) {
			var $el = $(ev.target)
			  , model = this.model
			;

			if ($el.is('select')) {
				if ($el.val() !== 'custom') {
					this.$('input.js--caption').each(function() {
						model.set($(this).data('setting'), false);
					});
				}
			} else {
				if (this.$('input.js--caption').filter(':checked').length > 0) {
					this.model.set('playlist_captionType', 'custom');
				} else {
					this.model.set('playlist_captionType', 'none');
				}
			}
		},

	});

	/**
	 * The title bar, containing the account selection, logo, "privacy" and "About" links
	 */
	ooyala.view.TitleBar = media.View.extend({
		template: media.template('ooyala-title-bar'),
		className: 'ooyala-title-bar',

		initialize: function() {
			if(this.controller.get('unsupported')) {
				return;
			}
		},

		prepare: function() {
			var data = this.controller.attributes;
			data.playlistsIsActive = ooyala.model.DisplayOptions.playlistsIsActive();
			data.account = this.controller.get('account');
			return data;
		},

		events: {
			'click .ooyala-about-link': 'showAbout',
			'click .ooyala-upload-toggle': 'toggleUploadPanel',
			'click .ooyala-browse-link': 'showBrowser',
			'change #ooyala-accounts': 'changeAccount',
			'click .ooyala-playlists-link': 'showPlaylists',
		},

		changeAccount: function(ev) {
			this.controller.set('account', ev.target.value);
		},

		// toggle upload panel (clicking the Upload link will display OR hide this panel)
		toggleUploadPanel: function() {
			this.$('.ooyala-browse-link').removeClass('ooyala-browsing');
			if ( this.controller.frame.content.mode() === 'ooyala-upload-panel' ) {
				this.controller.frame.content.mode('ooyala-browse');
			} else {
				this.controller.frame.content.mode('ooyala-upload-panel');
			}
		},

		showAbout: function() {
			this.$('.ooyala-browse-link').removeClass('ooyala-browsing');
			this.controller.frame.content.mode('ooyala-about-text');
		},

		showBrowser: function() {
			this.controller.frame.content.mode('ooyala-browse');
		},

		showPlaylists: function() {
			this.$('.ooyala-browse-link').removeClass('ooyala-browsing');
			this.controller.frame.content.mode('ooyala-playlists');
		},

		render: function() {
			media.View.prototype.render.apply(this, arguments);
			this.$('#ooyala-accounts').val(this.controller.get('account'));
			return this;
		},
	});

	/**
	 * The primary search field. Refreshes when model changes
	 */
	ooyala.view.Search = media.view.Search.extend({
		initialize: function() {
			this.model.on('change:search', this.render, this);
		},

		ready: function() {
			this.render();
		},

		render: function() {
			media.view.Search.prototype.render.apply(this, arguments);

			this.controller.collection.results.on('change:searching', this.updateButtonState, this);
		},

		updateButtonState: function(model) {
			if(model.get('searching')) {
				this.$el.parents('.media-toolbar-primary').find('.media-button-searchButton').attr('disabled', 'disabled');
			}
			else {
				this.$el.parents('.media-toolbar-primary').find('.media-button-searchButton').removeAttr('disabled');
			}
		}
	});

	/**
	 * The "About" screen, containing text and a close button
	 */
	ooyala.view.About = media.View.extend({
		template: media.template('ooyala-about-text'),
		className: 'ooyala-about-text',

		events: {
			'click .ooyala-close': 'close',
		},

		close: function(ev) {
			ev.preventDefault();
			this.controller.frame.content.mode('ooyala-browse');
		},

		// TODO: this is not functioning
		closeAboutOnEscape: function(ev) {
			if(ev.keyCode == 27) {
				this.close();
				ev.stopPropagation();
			}
		}
	});

	/**
	 * Edit labels UI
	 */
	ooyala.view.EditLabels = media.View.extend({
		template: media.template('ooyala-edit-labels'),
		className: 'ooyala-edit-labels',

		events: {
			'keyup .ooyala-label-input': 'maybeAddLabels',
			'blur .ooyala-label-input': 'maybeAddLabels',
			'click .ooyala-label-remove': 'removeLabel'
		},

		ready: function() {
			this.model.on('change:labels', this.render, this);
		},

		render: function() {
			media.View.prototype.render.apply(this, arguments);

			var _this = this, $input = _this.$('input');

			$input.autocomplete({
				minLength: 0,
				delay: 0,
				source: function(req, res) {
					res(ooyala.model.DisplayOptions.labels().filter(function(label) {
						return label.get('name').toLowerCase().indexOf(req.term) > -1;
					}).map(function(label) { return label.get('name'); }));
				},
				select: function(ev, ui) {
					$input.val(ui.item.value);

					_this.addLabels();

					$input.val();
				}
			});

			$input.on('focus', function() {
				$input.trigger('keydown');
			});

			if(this.refocus) {
				$input.focus();
				delete this.refocus;
			}
		},

		maybeAddLabels: function(ev) {
			// When they type a comma or return, add tags immediately
			if(ev.keyCode !== 13 && ev.keyCode !== 188) {
				return;
			}

			ev.preventDefault();
			ev.stopPropagation();

			this.addLabels();
		},

		addLabels: function() {
			var model = this.model
			  , input = this.$('input').val()
			  , current = model.get('labels')
			  , originalLength = current.length
			  , labels
			;

			// Grab all non-empty labels separated by , that don't already exist
			labels = input
				.split(/\s*,+\s*/)
				.map(function(val) { return val.trim(); });

			for(var i in labels) {
				if(labels[i] && !current.find(function(label) {
					return label.get('name').toLowerCase() === labels[i].toLowerCase();
				})) {
					current.push({ name: labels[i] }, { silent: true });
				}
			}

			// And update the model if there's a change
			if(current.length != originalLength) {
				this.refocus = true;
				model.trigger('change:labels')

				// If this is an existing asset, immediately add the labels
				if(model.get('id')) {
					ooyala.model.Labels.create(current).done(function(labels) {
						model.save({ labels: labels });
					});
				}
			}
		},

		removeLabel: function(ev) {
			var name = $(ev.target).parents('.ooyala-label').text()
			  , label = this.model.get('labels').findWhere({ name: name })
			;

			this.model.get('labels').remove(label);
			this.model.save();
			this.render();

			ev.preventDefault();
			ev.stopPropagation();
		}

	});

	/**
	 * The Upload panel
	 */
	ooyala.view.Upload = media.View.extend({
		template: media.template('ooyala-upload-panel'),
		className: 'ooyala-upload-panel',

		events: {
			'click .ooyala-close': 'close',
			'click .ooyala-start-upload': 'startUpload',
			'click .ooyala-stop-upload': 'stopUpload',
			'change input, textarea, select': 'syncSettings',
		},

		initialize: function() {

			// make an uploader, but only once
			if(!this.controller.uploader) {
				// browse button needs to exist in the dom before creating uploader
				this.controller.$browser = $('<a href="#" class="button" />').hide().appendTo('body');

				this.controller.uploader = new ooyala.plupload.Uploader({
					browse_button: this.controller.$browser[0],
					url: '/', //this is required for init but will be changed out before upload
					chunk_size: ooyala.chunk_size,
					max_retries: 2,
					multi_selection: false,
					// file upload events that act upon the files only
					// these cannot reference the view directly, because the view object changes with each initialization
					init: {
						UploadProgress: function(uploader,file) {
							file.model.asset.set('percent',file.percent);
						},
						UploadComplete: function(uploader,files) {
							files[0].model.finalize()
								.always(function(){ //always remove the upload from queue, success or not
									uploader.splice();
									uploader.view.render();
								});
						},
					},
				});

				// initialize the uploader
				this.controller.uploader.init();
			}
			// save view inside of uploader so it can be accessed from the uploader callbacks
			this.controller.uploader.view = this;
			this.controller.uploader.bind('FilesAdded', this.selectFile, this);
		},

		render: function() {
			media.View.prototype.render.apply(this,arguments);

			this.bindProgress();

			// replace the placeholder button with the actual one that has been bound to plupload events
			var $placeholder = this.$('.ooyala-upload-browser');
			if ( $placeholder.length ) {
				var $browser = this.controller.$browser;
				$browser.detach().text( $placeholder.text() );
				$browser[0].className = $placeholder[0].className;
				$placeholder.replaceWith( $browser.show().attr('style','') );
			}
		},

		// if there is an ongoing upload, update the progress while on the upload panel
		// TODO: change this to a progress bar
		bindProgress: function() {
			if ( this.controller.uploader.state === ooyala.plupload.STARTED ) {
				this.controller.uploader.files[0].model.asset.on('change:percent', this.progress, this);
			}
		},

		// update the uploading progress when on the panel
		progress: function(model) {
			this.$('.progress span').text( model.get('percent') );
		},

		// return to browse
		close: function() {
			this.controller.frame.content.mode('ooyala-browse');
		},

		// select file with the browse button
		// this assumes there is not a file upload in progress,
		// because the browse button should not be available in that case
		selectFile: function(uploader,files) {
			// force only one file in the queue at a time
			uploader.splice(0,Math.max(0,uploader.files.length-1));
			var file = files[0];

			// model for creating the asset and retrieving upload urls
			file.model = new ooyala.model.Upload({
				file_name: file.name,
				file_size: file.size,
				name: file.name,
			});
			// get a new url for each chunk
			file.chunkURL = function(file,chunk) {
				return file.model.get('uploading_urls')[chunk];
			};

			// add label editing view
			this.views.set('.ooyala-labels-container', new ooyala.view.EditLabels({
				model: file.model.asset
			}));

			uploader.view.render();
		},

		//fetch the upload urls and begin the upload
		startUpload: function(e) {
			e.preventDefault();

			var file = this.controller.uploader.files[0];
			if ( !file ) return;

			// once we have the uploading urls, the upload can actually begin
			file.model.on('change:uploading_urls',
				function(model) {
					// start the upload with plupload
					this.controller.uploader.start();
					// update controls
					this.$('.ooyala-upload-controls').addClass('uploading');
					// update progress of upload while on upload panel
					this.bindProgress();
					// add the upload to the 'all' query
					ooyala.model.Query.get({}).unshift(model.asset);
					// add the upload to the selection (recently viewed)
					this.controller.get('selection').unshift(model.asset);
					this.controller.get('selection').single(model.asset);
				},
				this);
			// this will fetch the uploading urls
			file.model.save();
			// update name in case it had to be changed pre-upload
			// (e.g. if the user deleted it entirely and it was replaced with the filename)
			this.$('[data-setting="name"]').val(file.model.get('name'));
			// disable the 'Start Upload' button until upload actually starts,
			// at which point it turns into a cancel button
			$(e.target).addClass('disabled');
			// hide the "Change File" button immediately
			this.$('.ooyala-upload-browser').hide();
		},

		stopUpload: function(e) {
			e.preventDefault();
			// stop upload
			this.controller.uploader.stop();
			// clear file queue and destory upload,
			// which removes the unfinished asset through the API
			// and the corresponding attachment
			// all in one!
			this.controller.uploader.splice()[0].model.destroy();
			this.render();
		},

		// sync settings for currently selected file with its attachment model
		syncSettings: function(e) {
			var $field = $(e.target), key;
			if ( key = $field.data('setting') ) {
				this.controller.uploader.files[0].model.set(key,$field.val());
			}
		},

	});

	/**
	 * Your browser is unsupported. Wah wah.
	 */
	ooyala.view.UnsupportedBrowser = media.View.extend({
		className: 'ooyala-unsupported-browser-message',
		template: media.template('ooyala-unsupported-browser')
	});

	/**
	 * View used to display a single Ooyala result
	 * in the results grid
	 */
	ooyala.view.Attachment = media.view.Attachment.extend({
		template: media.template('ooyala-attachment'),

		initialize: function() {
			media.view.Attachment.prototype.initialize.apply(this, arguments);
			this.$el.addClass('ooyala-attachment-item');

			this.model.on('change:status', this.changeStatus, this);
			this.changeStatus();
		},

		// disable rerendering on every change while in the process of uploading
		changeStatus: function() {
			this.render();
			this.$el.addClass('status-' + this.model.get('status'));

			if ( this.model.get('status') == 'uploading' && this.model.get('percent') !== undefined ) {
				this.model.off('change', this.render);
				// this.progress() needs this element
				this.$bar = this.$('.media-progress-bar div');
				this.model.on('change:percent',this.progress,this);
			} else {
				this.model.on('change',this.render,this);
			}
		},

		// Add to selection (Recently Viewed)
		toggleSelection: function( options ) {
			var collection = this.collection,
				selection = this.options.selection,
				model = this.model;

			if(!selection)
				return;

			selection.unshift(model);
			selection.single(model);
		},
	});

	/**
	 * Settings / Info for a particular selected asset
	 * shown in the sidebar
	 */
	ooyala.view.Details = media.view.Attachment.Details.extend({

		template: media.template('ooyala-details'),
		className: 'ooyala-details',

		events: {
			'click a.show-more': 'toggleMore',
		},

		initialize: function() {

			this.options = _.extend( this.options, {
				descriptionMaxLen: 400, //maximum character length for descriptions
				maxLenThreshold: 15, //character threshold (it would be silly to chop of that or anything less)
			});
			// update as we are uploading or when the status changes
			this.model.on('change:percent', this.progress, this);
			this.model.on('change:status', this.render, this);

			// or if we get an attachment ID
			this.model.on('change:attachment_id', this.render, this);

			media.view.Attachment.Details.prototype.initialize.apply(this, arguments);

			// Load up additional details
			this.model.fetch();

			this.views.set('.ooyala-labels-container', new ooyala.view.EditLabels({
				linkable: true,
				model: this.model
			}));
		},

		// update the percentage progress
		progress: function(model) {
			this.$('.progress span').text(model.get('percent'));
		},

		// show or hide extra description text that exceeds the limit
		toggleMore: function(e) {
			e.preventDefault();
			$(e.target).html( this.$('span.more').toggleClass('show').hasClass('show') ? '(show&nbsp;less)' : '(show&nbsp;more)' );
		},

		// disable auto-focus of first input, which is now the labels field
		initialFocus: function() {
		}

	});

	/**
	 * Media frame toolbar
	 */
	ooyala.view.Toolbar = media.view.Toolbar.extend({
		className: 'ooyala-toolbar media-toolbar',

		events: {
		},

		initialize: function(options) {
			var self = this;

			media.view.Toolbar.prototype.initialize.apply(this, arguments);

			// The past selection of assets
			this.selection = new ooyala.view.Selection({
				controller: this.controller,
				collection: options.collection
			});

			// This is the primary action button
			this.button = new media.view.Button({
				text: ooyala.text.insertAsset,
				style: 'primary',
				disabled: true,
				requires: { selection: true },
				priority: 10,
				click: function() {
					self.controller.state().insert();
				},
			});

			// when an asset is selected or deselected, update the button
			this.collection.on('selection:single change:attachment selection:unsingle', this.updateButton, this);
			// whenever the view changes, update the button
			this.controller.content.view.on('content:activate', this.updateButton, this);
			// when the playlist tab is activated, start listening for playlist changes
			this.controller.state().on('change:playlistOptions', function() {
				this.controller.state().get('playlistOptions').on('change:playlist', this.updateButton, this);
			}, this);

			// visually de-select the attachment
			this.collection.on('selection:unsingle', function() {
				this.$('.attachment.details').removeClass('details');
			}, this);

			this.primary.set('button', this.button);
			this.secondary.set('selection', this.selection);
			this.updateButton();
		},

		// update the 'Insert' button based on state of selected asset
		updateButton: function() {
			var mode = this.controller.content.mode();
			// only show the button if we are on the asset or playlist embed view
			if (['ooyala-playlists', 'ooyala-browse'].indexOf(mode) > -1) {
				// selected asset
				var asset = this.controller.state().get('selection').single()
				  , playlistOptions = this.controller.state().get('playlistOptions')
				;
				// if there is a selected asset and it is embeddable or there is a selected playlist
				if ((asset && this.controller.state().display(asset).canEmbed())
				  || (playlistOptions && playlistOptions.get('playlist'))
				) {
					this.button.model.set('disabled', false);
				} else {
					this.button.model.set('disabled', true);
				}
				// update button text
				this.button.model.set('text', mode === 'ooyala-playlists' ? ooyala.text.insertPlaylist : ooyala.text.insertAsset);
				this.button.$el.css('visibility', 'visible');
			} else {
				this.button.$el.css('visibility', 'hidden');
			}
		},
	});

	/**
	 * Selection thumbnail
	 */
	ooyala.view.Attachment.Selection = ooyala.view.Attachment.extend({
		className: 'attachment selection',

		// On click, just select the model, instead of removing the model from
		// the selection.
		toggleSelection: function() {
			this.options.selection.single(this.model);
		}
	});

	/**
	 * Refresh
	 */
	ooyala.view.Refresh = media.View.extend({
		tagName: 'a',
		className: 'search-results-refresh dashicons dashicons-image-rotate',

		events: {
			'click': 'refresh'
		},

		attributes: {
			title: ooyala.text.refresh
		},

		refresh: function() {
			this.collection.refresh();
		}
	});

	/**
	 * Selection
	 */
	ooyala.view.Selection = media.view.Selection.extend({

		initialize: function() {
			_.defaults(this.options, {
				editable: false,
				clearable: false,
			});

			this.attachments = new media.view.Attachments.Selection({
				controller: this.controller,
				collection: this.collection,
				selection:  this.collection,
				AttachmentView: ooyala.view.Attachment.Selection,
				model: new Backbone.Model({
					edge: 40,
					gutter: 5
				})
			});

			this.views.set('.selection-view', this.attachments);
			this.collection.on('add remove reset', this.refresh, this);
			this.controller.state().on('content:activate', this.refresh, this);
		},

		refresh: function() {
			media.view.Selection.prototype.refresh.apply(this, arguments);
			this.$('.count').text(ooyala.text.recentlyViewed);
		},

	});

})(jQuery);
