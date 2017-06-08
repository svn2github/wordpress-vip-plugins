/**
 * Filter views used for searching against Getty Images API
 *
 * @package Getty Images
 * @author  bendoh, thinkoomph
 */
(function($) {
	var media = wp.media;
	var getty = gettyImages;

	/**
	 * Generic base class for top-level image filters
	 */
	var GettyImageFilter = media.View.extend({
		tagName: 'div',
		className: 'getty-filter',

		events: {
			change: 'change'
		},

		// Generate markup for the various types of filters
		initialize: function() {
			// Create a header with the text
			this.$el.append($('<em>').text(this.text));

			switch(this.type) {
				case 'bool': // Boolean value: Use a single checkbox
					var $checkbox = $('<input type="checkbox" />').attr('name', this.prop).val(1);
					this.$el.append($('<label>').prepend($checkbox));

					break;

				case 'enum': // Enumerate value: Use radio buttons
					// Create label > radio + text
					_.each(this.values, function(option) {
						if(!option) { // IE8 gives me an extra undefined value here?
							return;
						}
						var $radio = $('<input type="radio" />').attr('name', this.prop).val(option.value);
						this.$el.append($('<label>')
							.text(option.text)
							.prepend($radio));
					}, this);

					break;

				case 'set': // Set value: use checkboxes
					// Create label > checkbox + text elements
					_.each(this.values, function(option) {
						var $checkbox = $('<input type="checkbox" />').attr('name', this.prop + '[]').val(option.value);
						this.$el.append($('<label>')
							.text(option.text)
							.prepend($checkbox));
					}, this);

					break;
			}

			// Propagate changes to props over to form elements
			if(this.model) {
				this.model.on('change', this.select, this);

				if(typeof getty.user.settings.get(this.prop)==='undefined') {
					getty.user.settings.set(this.prop, this.value);
				}

				this.model.set(this.prop, getty.user.settings.get(this.prop));
				this.select();
			}
		},

		// Handle change of any input in filter, update model
		change: function(event) {
			switch(this.type) {
				case 'bool':
					if(event.target.checked)
						this.model.set(this.prop, true);
					else
						this.model.set(this.prop, false);

					break;

				case 'enum':
					this.model.set(this.prop, this.$el.find('input:checked').val());

					break;

				case 'set':
					this.model.set(this.prop, _.pluck(this.$el.find('input:checked'), 'value'));

					break;
			}

			// Save filter to persist with user
			getty.user.settings.set(this.prop, this.model.get(this.prop));

			//Trigger refinementChanged event if specified.
			this.model.trigger('refinementChanged', this.model.get(this.prop));
		},

		// Restore filter state from model
		select: function() {
			var value = this.model.get(this.prop);

			switch(this.type) {
				case 'bool':
					if(value)
						this.$el.find('input').attr('checked','checked');
					else
						this.$el.find('input').removeAttr('checked');

					break;

				case 'set':
					this.$el.find('input').removeAttr('checked');

					for(var i = 0; i < value.length; i++) {
						this.$el.find('input[value="' + value[i] + '"]').attr('checked', 'checked');
					}

					break;

				case 'enum':
					this.$el.find('input').removeAttr('checked');
					this.$el.find('input[value="' + value + '"]').attr('checked', 'checked');

					break;
			}
		}
	});

	var GettyImageFilterTag = media.View.extend({
		tagName: 'div',
		className: 'getty-filter-tag',

		events: {
			click: 'click'
		},

		initialize: function () {
			this.model.on('change', this.render, this);
		},

		click: function(ev) {
			this.updateModel($(ev.target));
			this.model.trigger('refinementRemoved', this.model.get(this.prop));
		},

		// handle click to remove the applied filter.
		updateModel: function ($element) {
			var values = this.model.get(this.prop);

			switch (this.type) {
				case 'bool':
					//TODO: not needed yet.
					break;

				case 'enum':
					//TODO: not needed yet.
					break;

				case 'set':
					this.model.set(this.prop, _.without(values, $element.attr('key')));
					break;
			}

			// Save filter to persist with user
			getty.user.settings.set(this.prop, this.model.get(this.prop));
		},

		render: function () {
			switch (this.type) {
				case 'bool': // Boolean value.
					//TODO: not needed yet.
					break;

				case 'enum': // Enumerate value: radio buttons.
					//TODO: not needed yet.
					break;

				case 'set': // Set value: checkboxes. Multiple values.
					this.$el.empty();
					_.each(this.model.get(this.prop), function (selectedValue) {
						var labelText = _.findWhere(this.values, {value: selectedValue}).text;
						this.$el.append($('<label>')
							.text(labelText).attr('key', selectedValue));
					}, this);

					break;
			}
		}
	});

	/***
	 ** Specialized default filters for top-level searches
	 ***/
	media.view.GettyImageTypeFilter = GettyImageFilter.extend({
		className: 'getty-filter getty-filter-image-type',

		text: getty.text.imageType,
		type: 'set',
		prop: 'GraphicStyles',
		values: [
			{
				text: getty.text.photography,
				value: "photography"
			},
			{
				text: getty.text.illustration,
				value: "illustration"
			}
		],
		value: [ 'photography', 'illustration' ]
	});

	media.view.GettyAssetTypeFilter = GettyImageFilter.extend({
		className: 'getty-filter getty-filter-asset-type',

		text: getty.text.assetType,
		type: 'enum',
		prop: 'ImageFamilies',
		values: [
			{
				text: getty.text.editorial,
				value: "editorial"
			},
			{
				text: getty.text.creative,
				value: "creative"
			},
		],
		value: 'editorial',
	});

	media.view.GettyNudityFilter = GettyImageFilter.extend({
		className: 'getty-filter getty-filter-nudity',

		text: getty.text.excludeNudity,
		type: 'bool',
		prop: 'ExcludeNudity',

		value: true
	});

	media.view.GettyOrientationFilter = GettyImageFilter.extend({
		className: 'getty-filter getty-filter-orientation',

		text: getty.text.orientation,
		type: 'set',
		prop: 'Orientations',
		values: [
			{
				text: getty.text.horizontal,
				value: 'Horizontal'
			},
			{
				text: getty.text.vertical,
				value: 'Vertical'
			}
		],
		value: [ 'Horizontal', 'Vertical' ],
	});

	media.view.GettyEditorialSortOrderFilter = GettyImageFilter.extend({
		className: 'getty-filter getty-filter-sort-order getty-filter-editorial-sort-order',

		text: getty.text.sortOrder,
		type: 'enum',
		prop: 'EditorialSortOrder',
		values: [
			{
				text: getty.text.bestMatch,
				value: 'best_match'
			},
			{
				text: getty.text.newest,
				value: 'newest'
			},
			{
				text: getty.text.oldest,
				value: 'oldest'
			},
			{
				text: getty.text.mostPopular,
				value: 'most_popular'
			}			
		],
		value: 'best_match'
	});

	media.view.GettyCreativeSortOrderFilter = GettyImageFilter.extend({
		className: 'getty-filter getty-filter-sort-order getty-filter-creative-sort-order',

		text: getty.text.sortOrder,
		type: 'enum',
		prop: 'CreativeSortOrder',
		values: [
			{
				text: getty.text.bestMatch,
				value: 'best_match'
			},
			{
				text: getty.text.newest,
				value: 'newest'
			},
			{
				text: getty.text.mostPopular,
				value: 'most_popular'
			}		
		],
		value: 'best_match'
	});

	/***
	 * Refinement panel filters.
	 ***/
	/* Number of people filter and tag */
	media.view.GettyNumberOfPeopleFilter = GettyImageFilter.extend({
		className: 'getty-filter getty-sidebar-filter getty-filter-number-of-people',

		text: getty.text.numberOfPeople,
		type: 'set',
		refinementFilter: true,
		prop: 'NumberOfPeople',
		values: [
			{
				text: getty.text.noPeople,
				value: 'none'
			},
			{
				text: getty.text.onePerson,
				value: 'one'
			},
			{
				text: getty.text.twoPerson,
				value: 'two'
			},
			{
				text: getty.text.groupOfPeople,
				value: 'group'
			}
		],
		value: [ ],
	});

	media.view.GettyNumberOfPeopleFilterTag = GettyImageFilterTag.extend({
		className: 'getty-filter-tag getty-filter-tag-number-of-people',

		text: getty.text.numberOfPeople,
		type: 'set',
		prop: 'NumberOfPeople',
		values: [
			{
				text: getty.text.noPeople,
				value: 'none'
			},
			{
				text: getty.text.onePerson,
				value: 'one'
			},
			{
				text: getty.text.twoPerson,
				value: 'two'
			},
			{
				text: getty.text.groupOfPeople,
				value: 'group'
			}
		]
	});

	/* Age of people filter and tag */
	media.view.GettyAgeOfPeopleFilter = GettyImageFilter.extend({
		className: 'getty-filter getty-sidebar-filter getty-filter-age-of-people',

		text: getty.text.age,
		type: 'set',
		refinementFilter: true,
		prop: 'AgeOfPeople',
		values: [
			{
				text: getty.text.newborn,
				value: 'newborn'
			},
			{
				text: getty.text.baby,
				value: 'baby'
			},			
			{
				text: getty.text.child,
				value: 'child'
			},
			{
				text: getty.text.teenager,
				value: 'teenager'
			},
			{
				text: getty.text.youngAdult,
				value: 'young_adult'
			},
			{
				text: getty.text.adult,
				value: 'adult'
			},
			{
				text: getty.text.adultsOnly,
				value: 'adults_only'
			},
			{
				text: getty.text.matureAdult,
				value: 'mature_adult'
			},
			{
				text: getty.text.seniorAdult,
				value: 'senior_adult'
			},
			{
				text: getty.text._0_1months,
				value: '0-1_months'
			},
			{
				text: getty.text._2_5months,
				value: '2-5_months'
			},
			{
				text: getty.text._6_11months,
				value: '6-11_months'
			},
			{
				text: getty.text._12_17months,
				value: '12-17_months'
			},
			{
				text: getty.text._18_23months,
				value: '18-23_months'
			},
			{
				text: getty.text._2_3years,
				value: '2-3_years'
			},
			{
				text: getty.text._4_5years,
				value: '4-5_years'
			},
			{
				text: getty.text._6_7years,
				value: '6-7_years'
			},
			{
				text: getty.text._8_9years,
				value: '8-9_years'
			},
			{
				text: getty.text._10_11years,
				value: '10-11_years'
			},
			{
				text: getty.text._12_13years,
				value: '12-13_years'
			},
			{
				text: getty.text._14_15years,
				value: '14-15_years'
			},
			{
				text: getty.text._16_17years,
				value: '16-17_years'
			},
			{
				text: getty.text._18_19years,
				value: '18-19_years'
			},
			{
				text: getty.text._20_24years,
				value: '20-24_years'
			},
			{
				text: getty.text._20_29years,
				value: '20-29_years'
			},
			{
				text: getty.text._25_29years,
				value: '25-29_years'
			},
			{
				text: getty.text._30_34years,
				value: '30-34_years'
			},
			{
				text: getty.text._30_39years,
				value: '30-39_years'
			},
			{
				text: getty.text._35_39years,
				value: '35-39_years'
			},
			{
				text: getty.text._40_44years,
				value: '40-44_years'
			},
			{
				text: getty.text._40_49years,
				value: '40-49_years'
			},
			{
				text: getty.text._45_49years,
				value: '45-49_years'
			},
			{
				text: getty.text._50_54years,
				value: '50-54_years'
			},
			{
				text: getty.text._50_59years,
				value: '50-59_years'
			},
			{
				text: getty.text._60_64years,
				value: '60-64_years'
			},
			{
				text: getty.text._60_69years,
				value: '60-69_years'
			},
			{
				text: getty.text._65_69years,
				value: '65-69_years'
			},
			{
				text: getty.text._70_79years,
				value: '70-79_years'
			},
			{
				text: getty.text._80_89years,
				value: '80-89_years"'
			},
			{
				text: getty.text._90_plusYears,
				value: '90plus_years'
			},
			{
				text: getty.text.over100,
				value: '100_over'
			},

		],
		value: [ ],
	});

	media.view.GettyAgeOfPeopleFilterTag = GettyImageFilterTag.extend({
		className: 'getty-filter-tag getty-filter-tag-age-of-people',

		text: getty.text.age,
		type: 'set',
		prop: 'AgeOfPeople',
		values: [
			{
				text: getty.text.newborn,
				value: 'newborn'
			},
			{
				text: getty.text.baby,
				value: 'baby'
			},			
			{
				text: getty.text.child,
				value: 'child'
			},
			{
				text: getty.text.teenager,
				value: 'teenager'
			},
			{
				text: getty.text.youngAdult,
				value: 'young_adult'
			},
			{
				text: getty.text.adult,
				value: 'adult'
			},
			{
				text: getty.text.adultsOnly,
				value: 'adults_only'
			},
			{
				text: getty.text.matureAdult,
				value: 'mature_adult'
			},
			{
				text: getty.text.seniorAdult,
				value: 'senior_adult'
			},
			{
				text: getty.text._0_1months,
				value: '0-1_months'
			},
			{
				text: getty.text._2_5months,
				value: '2-5_months'
			},
			{
				text: getty.text._6_11months,
				value: '6-11_months'
			},
			{
				text: getty.text._12_17months,
				value: '12-17_months'
			},
			{
				text: getty.text._18_23months,
				value: '18-23_months'
			},
			{
				text: getty.text._2_3years,
				value: '2-3_years'
			},
			{
				text: getty.text._4_5years,
				value: '4-5_years'
			},
			{
				text: getty.text._6_7years,
				value: '6-7_years'
			},
			{
				text: getty.text._8_9years,
				value: '8-9_years'
			},
			{
				text: getty.text._10_11years,
				value: '10-11_years'
			},
			{
				text: getty.text._12_13years,
				value: '12-13_years'
			},
			{
				text: getty.text._14_15years,
				value: '14-15_years'
			},
			{
				text: getty.text._16_17years,
				value: '16-17_years'
			},
			{
				text: getty.text._18_19years,
				value: '18-19_years'
			},
			{
				text: getty.text._20_24years,
				value: '20-24_years'
			},
			{
				text: getty.text._20_29years,
				value: '20-29_years'
			},
			{
				text: getty.text._25_29years,
				value: '25-29_years'
			},
			{
				text: getty.text._30_34years,
				value: '30-34_years'
			},
			{
				text: getty.text._30_39years,
				value: '30-39_years'
			},
			{
				text: getty.text._35_39years,
				value: '35-39_years'
			},
			{
				text: getty.text._40_44years,
				value: '40-44_years'
			},
			{
				text: getty.text._40_49years,
				value: '40-49_years'
			},
			{
				text: getty.text._45_49years,
				value: '45-49_years'
			},
			{
				text: getty.text._50_54years,
				value: '50-54_years'
			},
			{
				text: getty.text._50_59years,
				value: '50-59_years'
			},
			{
				text: getty.text._60_64years,
				value: '60-64_years'
			},
			{
				text: getty.text._60_69years,
				value: '60-69_years'
			},
			{
				text: getty.text._65_69years,
				value: '65-69_years'
			},
			{
				text: getty.text._70_79years,
				value: '70-79_years'
			},
			{
				text: getty.text._80_89years,
				value: '80-89_years"'
			},
			{
				text: getty.text._90_plusYears,
				value: '90plus_years'
			},
			{
				text: getty.text.over100,
				value: '100_over'
			},

		]
	});

 	/* Image style filter and tag */
	media.view.GettyImageStyleFilter = GettyImageFilter.extend({
		className: 'getty-filter getty-sidebar-filter getty-filter-compositions',

		text: getty.text.imageStyle,
		type: 'set',
		refinementFilter: true,
		prop: 'ImageStyle',
		values: [
			{
				text: getty.text.fullFrame,
				value: 'full_frame'
			},
			{
				text: getty.text.closeUp,
				value: 'close_up'
			},
			{
				text: getty.text.portrait,
				value: 'portrait'
			},
			{
				text: getty.text.sparse,
				value: 'sparse'
			},
			{
				text: getty.text.abstract,
				value: 'abstract'
			},
			{
				text: getty.text.macro,
				value: 'macro'
			},
			{
				text: getty.text.stillLife,
				value: 'still_life'
			},
			{
				text: getty.text.cutOut,
				value: 'cut_out'
			},
			{
				text: getty.text.copySpace,
				value: 'copy_space'
			}
		],
		value: [ ],
	});

	media.view.GettyImageStyleFilterTag = GettyImageFilterTag.extend({
		className: 'getty-filter-tag getty-filter-tag-compositions',

		text: getty.text.imageStyle,
		type: 'set',
		prop: 'ImageStyle',
		values: [
			{
				text: getty.text.fullFrame,
				value: 'full_frame'
			},
			{
				text: getty.text.closeUp,
				value: 'close_up'
			},
			{
				text: getty.text.portrait,
				value: 'portrait'
			},
			{
				text: getty.text.sparse,
				value: 'sparse'
			},
			{
				text: getty.text.abstract,
				value: 'abstract'
			},
			{
				text: getty.text.macro,
				value: 'macro'
			},
			{
				text: getty.text.stillLife,
				value: 'still_life'
			},
			{
				text: getty.text.cutOut,
				value: 'cut_out'
			},
			{
				text: getty.text.copySpace,
				value: 'copy_space'
			}
		]
	});

	/* Ethnicity filter and tag */
	media.view.GettyEthnicityFilterTag = GettyImageFilterTag.extend({
		className: 'getty-filter-tag getty-filter-tag-ethnicity',
		text: getty.text.ethnicity,
		type: 'set',
		prop: 'Ethnicity',
		values: [
			{
				text: getty.text.eastAsian,
				value: 'east_asian'
			},
			{
				text: getty.text.southeastAsian,
				value: 'southeast_asian'
			},
			{
				text: getty.text.southAsian,
				value: 'south_asian'
			},
			{
				text: getty.text.black,
				value: 'black'
			},
			{
				text: getty.text.hispanicLatino,
				value: 'hispanic_latino'
			},
			{
				text: getty.text.caucasian,
				value: 'caucasian'
			},
			{
				text: getty.text.middleEastern,
				value: 'middle_eastern'
			},
			{
				text: getty.text.nativeAmericanFirstNations,
				value: 'native_american_first_nations'
			},
			{
				text: getty.text.pacificIslander,
				value: 'pacific_islander'
			},
			{
				text: getty.text.mixedRacePerson,
				value: 'mixed_race_person'
			},
			{
				text: getty.text.multiEthnicGroup,
				value: 'multiethnic_group'
			}
		]
	});

	media.view.GettyEthnicityFilter = GettyImageFilter.extend({
		className: 'getty-filter getty-sidebar-filter getty-filter-ethnicity',

		text: getty.text.ethnicity,
		type: 'set',
		refinementFilter: true,	
		prop: 'Ethnicity',
		values: [
			{
				text: getty.text.eastAsian,
				value: 'east_asian'
			},
			{
				text: getty.text.southeastAsian,
				value: 'southeast_asian'
			},
			{
				text: getty.text.southAsian,
				value: 'south_asian'
			},
			{
				text: getty.text.black,
				value: 'black'
			},
			{
				text: getty.text.hispanicLatino,
				value: 'hispanic_latino'
			},
			{
				text: getty.text.caucasian,
				value: 'caucasian'
			},
			{
				text: getty.text.middleEastern,
				value: 'middle_eastern'
			},
			{
				text: getty.text.nativeAmericanFirstNations,
				value: 'native_american_first_nations'
			},
			{
				text: getty.text.pacificIslander,
				value: 'pacific_islander'
			},
			{
				text: getty.text.mixedRacePerson,
				value: 'mixed_race_person'
			},
			{
				text: getty.text.multiEthnicGroup,
				value: 'multiethnic_group'
			}
		],
		value: [ ],
	});

	/**
	 * Let the user refine their search by entering "search within" terms
	 * or by adding categories to filter
	 */
	media.view.GettyRefinements = media.View.extend({
		className: 'getty-refinement-stack',

		events: {
			'keyup .search-refine': 'pushSearchRefinement'
		},

		initialize: function(options) {
			this.categories = options.categories || new Backbone.Collection();
			this.refinements = options.refinements || new Backbone.Collection();
			this.attachmentsCollection = options.attachmentsCollection;

			this.categories.on('add remove reset', this.render, this);
			this.refinements.on('add remove reset', this.render, this);

			this.views.set([
				new GettyCategoryRefinementFilter({
					collection: this.categories,
					controller: this.controller,
					refinements: this.refinements
				}),

				new media.view.GettyNumberOfPeopleFilter({
					controller: this.controller,
					model: this.attachmentsCollection.propsQueue,
					priority: 55
				}),

				new media.view.GettyAgeOfPeopleFilter({
					controller: this.controller,
					model: this.attachmentsCollection.propsQueue,
					priority: 55
				}),

				new media.view.GettyImageStyleFilter({
					controller: this.controller,
					model: this.attachmentsCollection.propsQueue,
					priority: 55
				}),

				new media.view.GettyEthnicityFilter({
					controller: this.controller,
					model: this.attachmentsCollection.propsQueue,
					priority: 55
				})
			]);
		},

		// Refine an existing search with free-form text when user hits enter
		pushSearchRefinement: function(ev) {
			if(ev.keyCode == 13) {
				ev.preventDefault();
				ev.stopPropagation();

				var $input = $(ev.target);

				this.refinements.push(new Backbone.Model({
					text: $input.val()
				}));

				$input.val('');
			}
		},
	});

	// The full collection of possible refinement categories
	var GettyCategoryRefinementFilter = media.View.extend({
		tagName: 'ul',
		className: 'getty-filter-categories',

		prop: 'Refinements',
		text: getty.text.refineCategories,
		values: [],
		type: 'set',

		initialize: function(options) {
			GettyImageFilter.prototype.initialize.apply(this, arguments);

			this.refinements = options.refinements || new Backbone.Collection();

			this.collection.on('add', this.addCategory, this);
			this.collection.on('remove', this.removeCategory, this);
			this.collection.on('reset', this.clearCategories, this);

			// Sort the categories
			this.collection.on('sort', this.sortOptions, this);

			this._viewsByCid = {};

			// Initialize from existing categories
			this.collection.each(this.addCategory, this);
		},

		addCategory: function(model, collection) {
			var view = new GettyRefinementCategory({
				model: model,
				collection: this.refinements
			});
			this._viewsByCid[model.cid] = view;
			this.views.add(view);
		},

		removeCategory: function(model, collection) {
			if(this._viewsByCid[model.cid]) {
				var view = this._viewsByCid[model.cid];
				if(view) {
					view.remove();
					delete this._viewsByCid[model.cid];
				}
			}
		},

		clearCategories: function() {
			this.views.set([]);
			this._viewsByCid = {};
		},

		sortOptions: function() {
			_.each(this._viewsByCid, function(view, cid, list) {
				view.sort();
			});
		}
	});

	// A single refinement category, which has multiple options
	var GettyRefinementCategory = media.View.extend({
		template: media.template('getty-result-refinement-category'),
		className: 'getty-refinement-category',

		events: {
			'click .getty-refinement-category-name': 'expand'
		},

		initialize: function() {
			this._viewsById = {};

			this.model.get('options').on('add', this.addOption, this);
			this.model.get('options').on('remove', this.removeOption, this);
			this.model.get('options').on('reset', this.clearOptions, this);

			this.model.on('change:expanded', this.toggleExpansion, this);

			this.toggleExpansion();

			this.model.get('options').on('change:active', this.changeActive, this);
			this.model.get('options').each(this.addOption, this);
		},

		changeActive: function(model, collection) {
			if(model.get('active')) {
				this.collection.add(model);
			}
			else {
				this.collection.remove(model);
			}
		},

		addOption: function(model) {
			if(!this._viewsById[model.id]) {
				var view = new GettyRefinementCategoryOption({
					model: model,
					collection: this.collection
				});

				this._viewsById[model.id] = view;
				this.views.add('.getty-refinement-list', view);
			}
		},

		removeOption: function(model, collection, options) {
			if(this._viewsById[model.id]) {
				this._viewsById[model.id].remove();
				delete this._viewsById[model.id];
			}
		},

		clearOptions: function() {
			_.each(this._viewsById, function(view) {
				view.remove();
			});

			this._viewsById = {};
		},

		toggleExpansion: function() {
			this.$el.toggleClass('expanded', !!this.model.get('expanded'));
		},

		expand: function() {
			this.model.set('expanded', !this.model.get('expanded'));
		},

		prepare: function() {
			return this.model.attributes;
		},

		sort: function() {
			var $ul = this.$el.find('.getty-refinement-list');

			// Sort the list!
			_.each(_.sortBy(_.map(this.views.all(), function(view) {
				return {
					id: view.model.id,
					count: view.model.get('count')
				}
			}), 'count'), function(o) {
				$ul.prepend(this._viewsById[o.id].$el);
			}, this);
		}
	});

	// A single refinement category option
	var GettyRefinementCategoryOption = media.View.extend({
		tagName: 'li',
		template: media.template('getty-result-refinement-option'),
		className: 'getty-refinement-category-option',
		initialize: function() {
			this.model.on('change:active change:text change:count', this.render, this);
		},

		events: {
			'click': 'pushRefinement',
		},

		prepare: function() {
			return this.model.attributes;
		},

		pushRefinement: function() {
			this.model.set('active', true);
		},
	});

	// The set of active refinements, allow users to remove them
	var GettyActiveRefinements = media.View.extend({
		tagName: 'ul',
		className: 'getty-active-refinements',

		initialize: function() {
			this._viewsByCid = {};

			this.collection.on('add', function(model, collection, options) {
				var view = new GettyRefinement({
					model: model,
					collection: this.collection
				})

				this._viewsByCid[model.cid] = view;

				this.views.add(view);
			}, this);

			this.collection.on('remove', function(model, collection, options) {
				var view = this._viewsByCid[model.cid];

				delete this._viewsByCid[model.cid];

				if(view)
					view.remove();
			}, this);

			this.collection.on('reset', function() {
				this.render();

				this.views.set([]);
				this._viewsByCid = {};
			}, this);
		},

		render: function() {
			this.views.set([]);

			this.collection.each(function(refinement) {
				this.views.add(new GettyRefinement({
					model: refinement,
					collection: this.collection
				}));
			}, this);
		},

		prepare: function() {
			return this.model.attributes;
		}
	});

	// A single, active refinement
	var GettyRefinement = media.View.extend({
		template: wp.template('getty-result-refinement'),
		tagName: 'li',
		className: 'getty-refinement-item',

		events: {
			'click .getty-remove-refinement': 'popRefinement',
		},

		prepare: function() {
			return this.model.attributes;
		},

		popRefinement: function(ev) {
			if(!this.model.get('category')) {
				this.collection.remove(this.model);
			}
			else {
				this.model.set('active', false);
			}
		}
	});

	media.view.GettyImageFilterTagsContainer = media.View.extend({
		tagName: 'div',
		className: 'getty-filter-tag-container',
		initialize: function () {
			var gettyNumberOfPeopleFilterTag = new media.view.GettyNumberOfPeopleFilterTag({
				controller: this.controller,
				model: this.model,
				priority: 15
			});

			var gettyAgeOfPeopleFilterTag = new media.view.GettyAgeOfPeopleFilterTag({
				controller: this.controller,
				model: this.model,
				priority: 15
			});

			var imageStyleFilterTag = new media.view.GettyImageStyleFilterTag({
				controller: this.controller,
				model: this.model,
				priority: 15
			});

			var ethnicityTag = new media.view.GettyEthnicityFilterTag({
				controller: this.controller,
				model: this.model,
				priority: 15
			});

			this.views.add(gettyNumberOfPeopleFilterTag);
			this.views.add(gettyAgeOfPeopleFilterTag);
			this.views.add(imageStyleFilterTag);
			this.views.add(ethnicityTag);
		}
	});

})(jQuery);
