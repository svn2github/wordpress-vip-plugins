var playbuzz_search = function playbuzz_search() {

	var apiBaseUrl = document.location.protocol + "//rest-api-v2.playbuzz.com/v2/items";
	this.display = display_search_popup;
	this.playbuzz_popup = playbuzz_popup;

	// Display search popup
	function display_search_popup(on_select_item) {

		// Open Playbuzz Popup
		playbuzz_popup();

		// Create popup structure (search popup)
		playbuzz_search_popup_structure( on_select_item );

		// Show featured items (on load)
		playbuzz_featured_items( 1, on_select_item );
	}

	// Get attribute from pattern
	function get_attr(pattern, attr) {

		n = new RegExp( attr + '=\"([^\"]+)\"', 'g' ).exec( pattern );
		return n ? window.decodeURIComponent( n[1] ) : '';

	};

	// Return formatted date
	function item_date(published_at) {

		var months = [
				translation.jan,
				translation.feb,
				translation.mar,
				translation.apr,
				translation.may,
				translation.jun,
				translation.jul,
				translation.aug,
				translation.sep,
				translation.oct,
				translation.nov,
				translation.dec
			],
			publish_date = new Date( published_at ),
			published = months[publish_date.getMonth()] + ' ' + publish_date.getDate() + ', ' + publish_date.getFullYear();

		return published;

	}

	// Return item type
	function playbuzz_item_type(type) {

		switch (type && type.toLowerCase()) {
			case "personality-quiz"    :
			case "testyourself"    :
				name = translation.personality_quiz;
				break;
			case "story"            :
				name = translation.story;
				break;
			case "snap-article"     :
				name = translation.story;
				break;
			case "list"            :
				name = translation.list;
				break;
			case "trivia"            :
			case "multiplechoice"    :
				name = translation.trivia;
				break;
			case "poll"                :
			case "playbuzzpoll"    :
				name = translation.poll;
				break;
			case "ranked-list"      :
			case "ranklist"        :
				name = translation.ranked_list;
				break;
			case "gallery-quiz"        :
			case "gallery"        :
				name = translation.gallery_quiz;
				break;
			case "flip-cards"        :
			case "reveal"        :
				name = translation.flip_cards;
				break;
			case "swiper"            :
				name = translation.swiper;
				break;
			case "countdown"        :
				name = translation.countdown;
				break;
			case "video-snaps"        :
			case "videosnaps"        :
				name = translation.video_snaps;
				break;
			case "convo"            :
				name = translation.convo;
				break;
			default                :
				name = "";
				break;
		}
		return name;

	}

	// Clear search info
	function clear_search_info() {

		// Clear search form values
		(jQuery)( "#playbuzz_search" ).val( '' );
		(jQuery)( "#playbuzz_search_type" ).val( '' );
		(jQuery)( "#playbuzz_search_sort" ).val( '' );

		// Set proper placeholder text
		if ((jQuery)( "#playbuzz_popup_tab_myitems" ).hasClass( "playbuzz_active_tab" )) {
			(jQuery)( "#playbuzz_search" ).attr( "placeholder", translation.search_term );
		} else {
			(jQuery)( "#playbuzz_search" ).attr( "placeholder", translation.search_my_items );
		}

	}

	// Add shortcode to tinyMCE editor (embed new items from the search popup to the tinyMCE editor)
	function remove_search_modal(itemId, format, on_select_item) {
		// Close playbuzz search popup
		(jQuery)( ".playbuzz_popup_overlay_container" ).remove();
		return false;
	}

	// Playbuzz popup
	function playbuzz_popup() {

		// Create popup structure (using DOM construction for security reasons)
		(jQuery)( "<div></div>" ).addClass( "playbuzz_popup_overlay_container" ).appendTo( "body" );
		(jQuery)( "<div></div>" ).addClass( "playbuzz_popup_overlay_bg" ).appendTo( ".playbuzz_popup_overlay_container" );
		(jQuery)( "<div></div>" ).addClass( "playbuzz_popup_overlay_border" ).appendTo( ".playbuzz_popup_overlay_bg" );
		(jQuery)( "<div></div>" ).attr( "id", "playbuzz_popup" ).appendTo( ".playbuzz_popup_overlay_border" );

	}

	// Playbuzz search popup - create popup structure
	function playbuzz_search_popup_structure(on_select_item) {

		// Popup Components
		(jQuery)( "<div></div>" ).attr( "id", "playbuzz_search_form" ).attr( "name", "search" ).appendTo( "#playbuzz_popup" );
		(jQuery)( "<div></div>" ).attr( "id", "playbuzz_search_header" ).appendTo( "#playbuzz_search_form" );
		(jQuery)( "<div></div>" ).attr( "id", "playbuzz_search_input_form" ).appendTo( "#playbuzz_search_form" );
		(jQuery)( "<div></div>" ).attr( "id", "playbuzz_search_sub_header" ).appendTo( "#playbuzz_search_form" );
		(jQuery)( "<div></div>" ).attr( "id", "playbuzz_search_results" ).appendTo( "#playbuzz_search_form" );

		// Header
		(jQuery)( "<div></div>" ).attr( "id", "playbuzz_popup_close" ).appendTo( "#playbuzz_search_header" ).click(
			function () {
				(jQuery)( ".playbuzz_popup_overlay_container" ).remove();
			}
		);
		(jQuery)( "<div></div>" ).addClass( "playbuzz_search_logo" ).appendTo( "#playbuzz_search_header" ).click(
			function () {
				clear_search_info();
				playbuzz_featured_items( 1, on_select_item );
			}
		);
		(jQuery)( "<span></span>" ).appendTo( ".playbuzz_search_logo" ).text( translation.playbuzz );
		(jQuery)( "<nav></nav>" ).appendTo( "#playbuzz_search_header" );
		(jQuery)( "<div></div>" ).attr( "id", "playbuzz_popup_tab_content" ).click(
			function () {
				clear_search_info();
				playbuzz_featured_items( 1, on_select_item );
			}
		).addClass( "playbuzz_active_tab" ).appendTo( "#playbuzz_search_header nav" );
		(jQuery)( "<div></div>" ).attr( "id", "playbuzz_popup_tab_myitems" ).click(
			function () {
				clear_search_info();
				playbuzz_my_items( 1, on_select_item );
			}
		).appendTo( "#playbuzz_search_header nav" );
		(jQuery)( "<span></span>" ).appendTo( "#playbuzz_popup_tab_content" ).text( translation.playbuzz_content );
		(jQuery)( "<span></span>" ).appendTo( "#playbuzz_popup_tab_myitems" ).text( translation.my_items );

		// Input form
		(jQuery)( "<input>" ).attr( "type", "text" ).attr( "id", "playbuzz_search" ).attr( "class", "playbuzz_search" ).attr( "name", "playbuzz_search" ).attr( "size", "16" ).attr( "autocomplete", "off" ).attr( "placeholder", translation.search_term ).appendTo( "#playbuzz_search_input_form" ).keyup(
			function () {
				playbuzz_show_screen( on_select_item );
			}
		);
		(jQuery)( "<span></span>" ).addClass( "playbuzz_search_sep" ).appendTo( "#playbuzz_search_input_form" ).text( "|" );
		(jQuery)( "<a></a>" ).attr( "href", "https://www.playbuzz.com/create" ).attr( "target", "_blank" ).addClass( "playbuzz_create_button" ).appendTo( "#playbuzz_search_input_form" ).text( translation.create_your_own );

		// Sub Header
		(jQuery)( "<div></div>" ).addClass( "playbuzz_search_fields" ).appendTo( "#playbuzz_search_sub_header" );
		(jQuery)( "<label></label>" ).attr( "for", "playbuzz_search_type" ).addClass( "playbuzz_search_label" ).appendTo( ".playbuzz_search_fields" ).text( translation.show );
		(jQuery)( "<select></select>" ).attr( "name", "playbuzz_search_type" ).attr( "id", "playbuzz_search_type" ).addClass( "playbuzz_search_type" ).appendTo( ".playbuzz_search_fields" ).change(
			function () {
				playbuzz_show_screen( on_select_item );
			}
		);
		(jQuery)( "<option></option>" ).attr( "value", "" ).appendTo( ".playbuzz_search_type" ).text( translation.all_types );
		(jQuery)( "<option></option>" ).attr( "value", "story,snap-article" ).appendTo( ".playbuzz_search_type" ).text( translation.story );
		(jQuery)( "<option></option>" ).attr( "value", "list" ).appendTo( ".playbuzz_search_type" ).text( translation.list );
		(jQuery)( "<option></option>" ).attr( "value", "personality-quiz" ).appendTo( ".playbuzz_search_type" ).text( translation.personality_quiz );
		(jQuery)( "<option></option>" ).attr( "value", "poll" ).appendTo( ".playbuzz_search_type" ).text( translation.poll );
		(jQuery)( "<option></option>" ).attr( "value", "ranked-list" ).appendTo( ".playbuzz_search_type" ).text( translation.ranked_list );
		(jQuery)( "<option></option>" ).attr( "value", "trivia" ).appendTo( ".playbuzz_search_type" ).text( translation.trivia );
		(jQuery)( "<option></option>" ).attr( "value", "gallery-quiz" ).appendTo( ".playbuzz_search_type" ).text( translation.gallery_quiz );
		(jQuery)( "<option></option>" ).attr( "value", "flip-cards" ).appendTo( ".playbuzz_search_type" ).text( translation.flip_cards );
		(jQuery)( "<option></option>" ).attr( "value", "swiper" ).appendTo( ".playbuzz_search_type" ).text( translation.swiper );
		(jQuery)( "<option></option>" ).attr( "value", "countdown" ).appendTo( ".playbuzz_search_type" ).text( translation.countdown );
		(jQuery)( "<option></option>" ).attr( "value", "video-snaps" ).appendTo( ".playbuzz_search_type" ).text( translation.video_snaps );
		(jQuery)( "<option></option>" ).attr( "value", "convo" ).appendTo( ".playbuzz_search_type" ).text( translation.convo );
		(jQuery)( "<label></label>" ).attr( "for", "playbuzz_search_sort" ).addClass( "playbuzz_search_label" ).appendTo( ".playbuzz_search_fields" ).text( translation.sort_by );
		(jQuery)( "<select></select>" ).attr( "name", "playbuzz_search_sort" ).attr( "id", "playbuzz_search_sort" ).addClass( "playbuzz_search_sort" ).appendTo( ".playbuzz_search_fields" ).change(
			function () {
				playbuzz_show_screen( on_select_item );
			}
		);
		(jQuery)( "<option></option>" ).attr( "value", "" ).appendTo( ".playbuzz_search_sort" ).text( translation.relevance );
		(jQuery)( "<option></option>" ).attr( "value", "publishDate" ).appendTo( ".playbuzz_search_sort" ).text( translation.date );
		(jQuery)( "<div></div>" ).attr( "id", "playbuzz_search_for" ).appendTo( "#playbuzz_search_sub_header" );
		(jQuery)( "<p></p>" ).appendTo( "#playbuzz_search_for" ).text( translation.discover_playful_content );
		(jQuery)( "<div></div>" ).addClass( "playbuzz_search_sub_divider" ).appendTo( "#playbuzz_search_sub_header" );

	}

	// Playbuzz popup error message
	function playbuzz_popup_message(popup_title, message_title, message_content) {

		// Popup title
		(jQuery)( "#playbuzz_search_for" ).empty().append(
			(jQuery)( "<p></p>" ).append( popup_title )
		);

		// Popup content
		(jQuery)( "#playbuzz_search_results" ).empty().append(
			(jQuery)( "<div></div>" ).addClass( "playbuzz_error_message" ).append(
				(jQuery)( "<div></div>" ).addClass( "playbuzz_notice" ).append(
					(jQuery)( "<h3></h3>" ).append( message_title )
				).append(
					(jQuery)( "<p></p>" ).append( message_content )
				)
			)
		);

	}

	// Playbuzz no user screen
	function playbuzz_no_user() {

		// Update tabs
		(jQuery)( "#playbuzz_popup_tab_content" ).removeClass( "playbuzz_active_tab" );
		(jQuery)( "#playbuzz_popup_tab_myitems" ).addClass( "playbuzz_active_tab" );

		// Popup title
		(jQuery)( "#playbuzz_search_for" ).empty().append(
			(jQuery)( "<p></p>" ).append(
				(jQuery)( "<span></span>" ).addClass( "playbuzz_search_title_user_img" ).append(
					(jQuery)( "<a></a>" )
						.attr( "target", "_blank" )
						.attr( "href", "options-general.php?page=playbuzz&tab=embed" )
						.addClass( "playbuzz_set_username_link" )
						.text( translation.set_user )
				)
			)
		);

		// Popup content
		(jQuery)( "#playbuzz_search_results" ).empty().append(
			(jQuery)( "<div></div>" ).addClass( "playbuzz_error_message" ).append(
				(jQuery)( "<div></div>" ).addClass( "playbuzz_notice" ).append(
					(jQuery)( "<h3></h3>" ).append( translation.no_user )
				).append(
					(jQuery)( "<p></p>" ).append( translation.set_your_username )
				)
			)
		);

	}

	// Playbuzz search results
	function playbuzz_search_results(layout, data, popup_title, popup_title_paging, on_select_item) {
		// Popup title
		(jQuery)( "#playbuzz_search_for" )
			.empty()
			.append(
				(jQuery)( "<p></p>" )
					.append( popup_title )
					.append( popup_title_paging )
			);

		// Popup content
		(jQuery)( "#playbuzz_search_results" ).empty();
		(jQuery)( "#playbuzz_search_results" )[0].scrollTop = 0;

		(jQuery).each(
			data, function (key, val) {

				(jQuery)( "<div></div>" )
				.addClass( "playbuzz_" + layout + "_view" )
				.appendTo( "#playbuzz_search_results" )
				.append(
					// thumbnail
					(jQuery)( "<div></div>" )
						.addClass( "playbuzz_present_item_thumb" )
						.append(
							(jQuery)( "<img>", {src: val.imageMedium} )
						)
				)
				.append(
					// desc
					(jQuery)( "<div></div>" )
						.addClass( "playbuzz_present_item_desc" )
						.append(
							(jQuery)( "<div></div>" )
								.addClass( "playbuzz_present_item_title" )
								.text( val.title )
						)
						.append(
							(jQuery)( "<div></div>" )
								.addClass( "playbuzz_present_item_meta" )
								.text( translation.by + " " )
								.append(
									(jQuery)( "<span></span>" )
										.text( val.channelName )
								)
								.append( " " + translation.on + " " + item_date( val.publishDate ) )
						)
				)
				.append(
					// type
					(jQuery)( "<div></div>" )
						.addClass( "playbuzz_present_item_type" )
						.append(
							(jQuery)( "<span></span>" )
								.text( playbuzz_item_type( val.format ) )
						)
				)
				.append(
					// buttons
					(jQuery)( "<div></div>" )
						.addClass( "playbuzz_present_item_buttons" )
						.append(
							(jQuery)( "<a></a>" )
								.addClass( "button button-secondary" )
								.attr( "target", "_blank" )
								.attr( "href", val.playbuzzUrl )
								.text( translation.view )
						)
						.append(
							(jQuery)( "<input>" )
								.attr( "type", "button" )
								.attr( "class", "button button-primary" )
								.attr( "value", translation.embed )
								.click(
									function () {
										on_select_item( val.id, val.format );
										return remove_search_modal();
									}
								)
						)
				);
			}
		);

	}

	// Playbuzz popup pagination
	function playbuzz_popup_pagination(total_pages, current_page, type) {

		// Set current page
		current_page = (isNaN( current_page )) ? parseInt( current_page ) : current_page;
		current_page = (current_page < 1) ? 1 : current_page;

		// Set start page
		var start_page = current_page - 2;
		if (start_page <= 0) {
			start_page = 1;
		}

		// Set end_page
		var end_page = current_page + 2;
		if (end_page >= total_pages) {
			end_page = total_pages;
		}

		// Open pagination container
		(jQuery)( "<div></div>" )
			.addClass( "playbuzz_item_pagination" )
			.addClass( type )
			.attr( "data-function", type )
			.appendTo( "#playbuzz_search_results" );

		// Add prev page link
		if (current_page == 1) {
			(jQuery)( "<a></a>" )
				.addClass( "playbuzz_prev disabled_pagination" )
				.appendTo( ".playbuzz_item_pagination" );
		} else {
			(jQuery)( "<a></a>" )
				.attr( "onclick", type + "(" + (current_page - 1) + ")" )
				.addClass( "playbuzz_prev enabled_pagination" )
				.appendTo( ".playbuzz_item_pagination" );
		}

		// Add pages
		for (page = start_page; page <= end_page; ++page) {
			current_page_class = ((page == current_page) ? " playbuzz_current" : "");

			(jQuery)( "<a></a>" )
				.attr( "onclick", type + "(" + page + ")" )
				.addClass( "enabled_pagination" )
				.addClass( current_page_class )
				.appendTo( ".playbuzz_item_pagination" )
				.text( page );
		}

		// Add next page link
		if (current_page == total_pages) {
			(jQuery)( "<a></a>" )
				.addClass( "playbuzz_next disabled_pagination" )
				.appendTo( ".playbuzz_item_pagination" );
		} else {
			(jQuery)( "<a></a>" )
				.attr( "onclick", type + "(" + (current_page + 1) + ")" )
				.addClass( "playbuzz_next enabled_pagination" )
				.appendTo( ".playbuzz_item_pagination" );
		}

	}

	// Playbuzz show popup screen
	function playbuzz_show_screen(on_select_item) {

		var is_content_tab = (((jQuery)( "#playbuzz_popup_tab_content" ).hasClass( "playbuzz_active_tab" )) ? true : false),
			is_search = (((jQuery)( "#playbuzz_search" ).val().trim() != '') ? true : false);

		if (is_search) {
			if (is_content_tab) {
				playbuzz_general_search( 1, on_select_item );
			} else {
				playbuzz_user_search( 1, on_select_item );
			}
		} else {
			if (is_content_tab) {
				playbuzz_featured_items( 1, on_select_item );
			} else {
				playbuzz_my_items( 1, on_select_item );
			}
		}

	}

	// Playbuzz featured items screen
	function playbuzz_featured_items(current_page, on_select_item) {

		var apiBaseUrl = document.location.protocol + "//rest-api-v2.playbuzz.com/v2/items";

		// Set variables
		var results_layout = "grid",
			results_title = translation.featured_items,
			items_per_page = 30;

		// Update tabs
		(jQuery)( "#playbuzz_popup_tab_content" ).addClass( "playbuzz_active_tab" );
		(jQuery)( "#playbuzz_popup_tab_myitems" ).removeClass( "playbuzz_active_tab" );

		// Load items using the Playbuzz API
		(jQuery).ajax(
			{
				url: apiBaseUrl,
				type: "get",
				dataType: "json",
				data: {
					internalTags: "EditorsPick_Featured",
					format: (jQuery)( "#playbuzz_search_type" ).val(),
					sort: (jQuery)( "#playbuzz_search_sort" ).val(),
					size: items_per_page,
					from: (current_page * items_per_page) - items_per_page
				},
				error: function (data) {

					// Server Error
					playbuzz_popup_message( results_title, translation.server_error, translation.try_in_a_few_minutes );
					console.error( "Couldn't get data: ", data );

				},
				success: function (data) {

					// Set variables
					var total_items = data.payload.totalItems,
					total_pages = ((total_items >= items_per_page) ? Math.ceil( total_items / items_per_page ) : 1),
					results_pages = ((current_page > 1) ? " <span class='playbuzz_search_title_pagination'>(" + translation.page + " " + current_page + " / " + total_pages + ")" : "");

					// Data output
					if (total_items > 0) {
						// Show Results
						playbuzz_search_results( results_layout, data.payload.items, results_title, results_pages, on_select_item );
						// Pagination
						if (total_items > items_per_page) {
							playbuzz_popup_pagination( total_pages, current_page, 'playbuzz_featured_items' );
						}
					} else {
						// No Search Results
						playbuzz_popup_message( results_title, translation.no_results_found, translation.try_different_search );
					}

				}

			}
		);

	}

	window.playbuzz_featured_items = playbuzz_featured_items;

	function is_playbuzz_url(url) {
		var valid = ["http://www.playbuzz.com/", "https://www.playbuzz.com", "www.playbuzz.com"];
		for (var i = 0; i < valid.length; i++) {
			if (url.indexOf( valid[i] ) === 0) {
				return true;
			}
		}

		return false;
	}

	// Playbuzz general search screen
	function playbuzz_general_search(current_page, on_select_item) {
		var search_param = (jQuery)( "#playbuzz_search" ).val();

		// Set variables
		var results_layout = "list",

			string = (jQuery)( "#playbuzz_search" ).val();
		if (string.length > 25) {
			string = string.substring( 0, 16 ) + "...";
		}

		results_title = (translation.results_for + " '" + string + "'"),
			items_per_page = 30;

		// Update tabs
		(jQuery)( "#playbuzz_popup_tab_content" ).addClass( "playbuzz_active_tab" );
		(jQuery)( "#playbuzz_popup_tab_myitems" ).removeClass( "playbuzz_active_tab" );

		var dataObject;
		if (is_playbuzz_url( search_param )) {
			dataObject = {
				playbuzzUrl: search_param
			};
		} else {
			dataObject = {
				q: search_param,
				format: (jQuery)( "#playbuzz_search_type" ).val(),
				sort: (jQuery)( "#playbuzz_search_sort" ).val(),
				size: items_per_page,
				from: (current_page * items_per_page) - items_per_page
			};
		}

		// Load items using the Playbuzz API
		(jQuery).ajax(
			{
				url: apiBaseUrl,
				type: "get",
				dataType: "json",
				data: dataObject,
				error: function (data) {

					// Server Error
					playbuzz_popup_message( results_title, translation.server_error, translation.try_in_a_few_minutes );
					console.error( "Couldn't get data: ", data );

				},
				success: function (data) {

					// Set variables
					var total_items = data.payload.totalItems,
					total_pages = ((total_items >= items_per_page) ? Math.ceil( total_items / items_per_page ) : 1),
					results_pages = ((current_page > 1) ? " <span class='playbuzz_search_title_pagination'>(" + translation.page + " " + current_page + " / " + total_pages + ")" : "");

					// Data output
					if (total_items > 0) {
						// Show Results
						playbuzz_search_results( results_layout, data.payload.items, results_title, results_pages, on_select_item );
						// Pagination
						if (total_items > items_per_page) {
							playbuzz_popup_pagination( total_pages, current_page, 'playbuzz_general_search' );
						}
					} else {
						// No Search Results
						playbuzz_popup_message( results_title, translation.no_results_found, translation.try_different_search );
					}

				}

			}
		);

	}

	window.playbuzz_general_search = playbuzz_general_search;

	// Playbuzz my items screen
	function playbuzz_my_items(current_page, on_select_item) {

		// exit if username is not set
		if ( ! site_settings.pb_user || 0 === site_settings.pb_user) {
			playbuzz_no_user();
			return;
		}

		// Set variables
		var results_layout = "list",
			results_title = ("<span class='playbuzz_search_title_user_img'>" + site_settings.pb_user + "</span>"),
			items_per_page = 30;

		// Update tabs
		(jQuery)( "#playbuzz_popup_tab_content" ).removeClass( "playbuzz_active_tab" );
		(jQuery)( "#playbuzz_popup_tab_myitems" ).addClass( "playbuzz_active_tab" );

		// Load items using the Playbuzz API
		(jQuery).ajax(
			{
				url: apiBaseUrl,
				type: "get",
				dataType: "json",
				data: {
					format: (jQuery)( "#playbuzz_search_type" ).val(),
					sort: (jQuery)( "#playbuzz_search_sort" ).val(),
					size: items_per_page,
					from: (current_page * items_per_page) - items_per_page,
					channelAlias: site_settings.pb_user,
					moderation: "none"
				},
				error: function (data) {

					// Server Error
					playbuzz_popup_message( results_title, translation.server_error, translation.try_in_a_few_minutes );
					console.error( "Couldn't get data: ", data );

				},
				success: function (data) {

					// Set variables
					var total_items = data.payload.totalItems,
					total_pages = ((total_items >= items_per_page) ? Math.ceil( total_items / items_per_page ) : 1),
					results_pages = (" <span class='playbuzz_search_title_pagination'>(" + total_items + " " + translation.items + ")"),
					change_user = ("<a href='options-general.php?page=playbuzz&tab=embed' target='_blank' class='playbuzz_change_username_link'>" + translation.change_user + "</a>");
					create_button = ("<div class='playbuzz_create_button'><a href='https://www.playbuzz.com/create' target='_blank'>" + translation.create_your_own + "</a></div>")

					// Data output
					if (data.payload.currentItemCount > 0) {

						// Show Results
						playbuzz_search_results( results_layout, data.payload.items, results_title, results_pages + change_user, on_select_item );
						// Pagination
						if (total_items > items_per_page) {
							playbuzz_popup_pagination( total_pages, current_page, 'playbuzz_my_items' );
						}
					} else {
						// No Search Results
						playbuzz_popup_message( results_title + results_pages + change_user, translation.you_dont_have_any_items_yet, translation.go_to_playbuzz_to_create_your_own_playful_content + create_button );
					}

				}

			}
		);

	}

	window.playbuzz_my_items = playbuzz_my_items;

	// Playbuzz user search screen
	function playbuzz_user_search(current_page, on_select_item) {
		// exit if username is not set
		if ( ! site_settings.pb_user || 0 === site_settings.pb_user) {
			playbuzz_no_user();
			return;
		}

		var search_param = (jQuery)( "#playbuzz_search" ).val();

		if (search_param.length > 25) {
			search_param = search_param.substring( 0, 16 ) + "...";
		}

		// Set variables
		var results_layout = "list",
			results_title = (translation.results_for + " '" + search_param + "' "),
			items_per_page = 30;

		var dataObject;
		if (is_playbuzz_url( search_param )) {
			dataObject = {
				playbuzzUrl: search_param
			};
		} else {
			dataObject = {
				q: search_param,
				format: (jQuery)( "#playbuzz_search_type" ).val(),
				sort: (jQuery)( "#playbuzz_search_sort" ).val(),
				size: items_per_page,
				from: (current_page * items_per_page) - items_per_page,
				channelAlias: site_settings.pb_user,
				moderation: "none"
			};
		}

		// Update tabs
		(jQuery)( "#playbuzz_popup_tab_content" ).removeClass( "playbuzz_active_tab" );
		(jQuery)( "#playbuzz_popup_tab_myitems" ).addClass( "playbuzz_active_tab" );

		// Load items using the Playbuzz API
		(jQuery).ajax(
			{
				url: apiBaseUrl,
				type: "get",
				dataType: "json",
				data: dataObject,
				error: function (data) {

					// Server Error
					playbuzz_popup_message( results_title, translation.server_error, translation.try_in_a_few_minutes );
					console.error( "Couldn't get data: ", data );

				},
				success: function (data) {

					// Set variables
					var total_items = data.payload.totalItems,
					total_pages = ((total_items >= items_per_page) ? Math.ceil( total_items / items_per_page ) : 1),
					results_pages = ((current_page > 1) ? " <span class='playbuzz_search_title_pagination'>(" + translation.page + " " + current_page + " / " + total_pages + ")" : "");

					// Data output
					if (data.payload.items.length > 0) {
						// Show Results
						playbuzz_search_results( results_layout, data.payload.items, results_title, results_pages, on_select_item );
						// Pagination
						if (total_items > items_per_page) {
							playbuzz_popup_pagination( total_pages, current_page, 'playbuzz_user_search' );
						}
					} else {
						// No Search Results
						playbuzz_popup_message( results_title, translation.no_results_found, translation.try_different_search );
					}

				}

			}
		);

	}

	window.playbuzz_user_search = playbuzz_user_search;
};
