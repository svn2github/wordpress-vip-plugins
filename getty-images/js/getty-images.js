/**
 * Main front-end admin code for Getty Images
 *
 * @package Getty Images
 * @author  bendoh, thinkoomph
 */

(function($) {
	var media = wp.media;
	var getty = gettyImages;

	// The Getty User session, which handles authentication needs and maintains
	// application tokens for anonymous searches
	getty.user = new media.model.GettyUser(); getty.user.restore();

	// Extract user defaults from a cookie
	getty.userSettings = function(defaults) {
		defaults = defaults || {};

		var settings = {};

		try {
			settings = JSON.parse($.cookie('wpGIc')) || {};
		} catch(ex) {
		}

		return _.defaults(settings, defaults);
	}

	// Save user defaults to a cookie
	getty.updateUserSetting = function(setting, value) {
		var settings = getty.userSettings();

		settings[setting] = value;

		$.cookie('wpGIc', JSON.stringify(settings));
	}

	/**
	 * Main controller for the Getty Images panel, which
	 * is a state within the MediaFrame.
	 */
	media.controller.GettyImages = media.controller.State.extend({
		// Keep track of create / render handlers to ensure that they
		// are all registered / dereigstered whenever this state is
		// activated or deactivated
		handlers: {
			'content:create:getty-images-browse': 'createBrowser',
			'title:create:getty-title-bar': 'createTitleBar',
			'content:create:getty-about-text': 'createAboutText',
			'content:activate:getty-images-browse': 'activateBrowser',
			'toolbar:create:getty-images-toolbar': 'createToolbar',
		},

		initialize: function() {
			// Are we stupid?
			if($.browser.msie && $.browser.version < 10) {
				this.set('unsupported', true);
			}

			this._displays = [];

			if(!this.get('library')) {
				this.set('library', new media.model.GettyAttachments(null, {
					controller: this,
					props: { query: true },
				}));
			}

			if(!this.get('refinements')) {
				this.set('refinements', new Backbone.Collection());
			}

			if(!this.get('categories')) {
				this.set('categories', new Backbone.Collection());
			}
		},

		turnBindings: function(method) {
			var frame = this.frame;

			_.each(this.handlers, function(handler, event) {
				this.frame[method](event, this[handler], this);	
			}, this);
		},

		activate: function() {
			this.turnBindings('on');
			
			if(this.get('unsupported')) {
				this.frame.$el.addClass('getty-unsupported-browser');
			}
		},

		deactivate: function() {
			this.turnBindings('off');
		},

		createTitleBar: function(title) {
			title.view = new media.view.GettyTitleBar({
				controller: this,
			});
		},

		createAboutText: function(content) {
			content.view = new media.view.GettyAbout({
				controller: this,
			});
		},

		createBrowser: function(content) {
			if(this.get('unsupported')) {
				content.view = new media.view.GettyUnsupportedBrowser();
				return;
			}

			content.view = new media.view.GettyBrowser({
				controller: this.frame,
				model:      this,
				collection: this.get('library'),
				selection:  this.get('selection'),
				refinements: this.get('refinements'),
				categories: this.get('categories'),
				sortable:   false,
				search:     true,
				filters:    true,
				display:    false,
				dragInfo:   false,
				AttachmentView: media.view.GettyAttachment
			});
		},

		activateBrowser: function(content) {
			this.frame.$el.removeClass('hide-toolbar');
		},

		// This toolbar is for the FRAME, not for the image browser,
		// which also has a toolbar region
		createToolbar: function(toolbar) {
			if(this.get('unsupported')) {
				return;
			}

			toolbar.view = new media.view.GettyToolbar({
				controller: this.frame,
				collection: this.get('selection')
			});
		},

		insert: function() {
			var image = this.get('selection').single();

			if(!image) {
				return;
			}

			// Get display options from user
			var display = this.display(image);
			
			var align = display.get('align') || 'none';
			var alt = display.get('alt');
			var caption = display.get('caption');
			
			var sizeSlug = display.get('size');
			var sizes = display.get('sizes');
			var size = sizes[sizeSlug];

			if(!size) {
				return;
			}

			// Build image tag with those options
			var $img = $('<img>');

			$img.attr('src', size.url);

			if(alt) {
				$img.attr('alt', alt);
			}

			if(align != 'none') {
				$img.addClass('align' + align);
			}

			$img.addClass('size-' + sizeSlug);
			$img.attr('width', size.width);
			$img.attr('height', size.height);

			var $container = $('<div>').append($img);

			if(caption) {
				$container.html( '[caption align="align' + align + '" width="' + size.width + '"]' + $container.html() + caption + '[/caption]' );
			}

			wp.media.editor.insert($container.html());
		},

		reset: function() {
			this.get('selection').reset();
			this._displays = [];
			this.refreshContent();
		},

		display: function( attachment ) {
			var displays = this._displays;
			var defaultProps = media.view.settings.defaultProps;

			if(!displays[attachment.cid]) {
				displays[attachment.cid] = new media.model.GettyDisplayOptions(_.extend({
					type: 'image',
					align: defaultProps.align || getUserSetting( 'getty_align', 'none' ),
					size:  defaultProps.size  || getUserSetting( 'getty_imgsize', 'medium' ),
				}, { attachment: attachment }));
			}

			return displays[attachment.cid];
		}
	});

	// Register handler for getty-images button which brings the
	// user directly to the Getty Images view
	$(document).ready(function() {
		var getty = window.gettyImages;
		var media = wp.media;

		$(document.body).on('click', '.getty-images', function(e) {
			e.preventDefault();

			if(!media.frames.getty) {
				media.frames.getty = wp.media.editor.open(wpActiveEditor, {
					state: 'getty-images',
					frame: 'post'
				});
			}
			media.frames.getty.open();

			media.frames.getty.$el.find('.search-primary').focus();
		});
	});
})(jQuery);
