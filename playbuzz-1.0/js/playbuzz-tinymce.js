(function() {

	tinymce.PluginManager.add( 'playbuzz', function( editor, url ) {

		/**
		 *
		 *  HELPER FUNCTIONS
		 *
		 */

		var protocol = document.location.protocol === "https:" ? "https:" : "http:";
		var apiBaseUrl = protocol + "//rest-api-v2.playbuzz.com/v2/items";

		// Get attribute from pattern
		function get_attr( pattern, attr ) {

			n = new RegExp( attr + '=\"([^\"]+)\"', 'g' ).exec( pattern );
			return n ? window.decodeURIComponent( n[1] ) : '';

		};

		// Return formatted date
		function item_date( published_at ) {

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
			    published = months[ publish_date.getMonth() ] + ' ' + publish_date.getDate() + ', ' + publish_date.getFullYear();

			return published;

		}

		// Return item type
		function playbuzz_item_type( type ) {

			switch ( type && type.toLowerCase() ) {
				case "personality-quiz"	:
				case "testyourself"   	: name = translation.personality_quiz; 	break;
				case "story"   			: name = translation.story; 			break;
				case "snap-article"     : name = translation.story; 			break;
				case "list"           	: name = translation.list;             	break;
				case "trivia"		  	:
				case "multiplechoice" 	: name = translation.trivia;           	break;
				case "poll"			  	:
				case "playbuzzpoll"   	: name = translation.poll;    		   	break;
				case "ranked-list"      :
				case "ranklist"       	: name = translation.ranked_list;      	break;
				case "gallery-quiz"		:
				case "gallery"   	    : name = translation.gallery_quiz;     	break;
				case "flip-cards"		:
				case "reveal"       	: name = translation.flip_cards;       	break;
				case "swiper"         	: name = translation.swiper;    	 	break;
				case "countdown"      	: name = translation.countdown;   	 	break;
				case "video-snaps"		:
				case "videosnaps"     	: name = translation.video_snaps;   	break;
				case "convo"			: name = translation.convo;				break;
				default               	: name = "";                           	break;
			}
			return name;

		}

		// Clear search info
		function clear_search_info() {

			// Clear search form values
			(jQuery)("#playbuzz_search").val( '' );
			(jQuery)("#playbuzz_search_type").val( '' );
			(jQuery)("#playbuzz_search_sort").val( '' );

			// Set proper placeholder text
			if ( (jQuery)("#playbuzz_popup_tab_myitems").hasClass( "playbuzz_active_tab" ) ) {
				(jQuery)("#playbuzz_search").attr( "placeholder", translation.search_term );
			} else {
				(jQuery)("#playbuzz_search").attr( "placeholder", translation.search_my_items );
			}

		}

		// Add shortcode to tinyMCE editor (embed new items from the search popup to the tinyMCE editor)
		function playbuzz_shortcode_embed( itemId, format ) {

			// Add shortcode to tinyMCE editor
			if ( tinyMCE && tinyMCE.activeEditor ) {
				tinyMCE.activeEditor.selection.setContent( '[playbuzz-item item="' + itemId + '" format="' + format + '"]<br>' );
			}

			// Close playbuzz search popup
			(jQuery)(".playbuzz_popup_overlay_container").remove();

			return false;

		}

		// Playbuzz popup
		function playbuzz_popup() {

			// Create popup structure (using DOM construction for security reasons)
			(jQuery)("<div></div>").addClass( "playbuzz_popup_overlay_container" ).appendTo( "body" );
			(jQuery)("<div></div>").addClass( "playbuzz_popup_overlay_bg" ).appendTo( ".playbuzz_popup_overlay_container" );
			(jQuery)("<div></div>").addClass( "playbuzz_popup_overlay_border" ).appendTo( ".playbuzz_popup_overlay_bg" );
			(jQuery)("<div></div>").attr( "id", "playbuzz_popup" ).appendTo( ".playbuzz_popup_overlay_border" );

		}

		// Playbuzz search popup - create popup structure
		function playbuzz_search_popup_structure() {

			// Popup Components
			(jQuery)("<div></div>").attr( "id", "playbuzz_search_form" ).attr( "name", "search" ).appendTo( "#playbuzz_popup" );
			(jQuery)("<div></div>"  ).attr( "id", "playbuzz_search_header" ).appendTo( "#playbuzz_search_form" );
			(jQuery)("<div></div>"  ).attr( "id", "playbuzz_search_input_form" ).appendTo( "#playbuzz_search_form" );
			(jQuery)("<div></div>"  ).attr( "id", "playbuzz_search_sub_header" ).appendTo( "#playbuzz_search_form" );
			(jQuery)("<div></div>"  ).attr( "id", "playbuzz_search_results" ).appendTo( "#playbuzz_search_form" );

			// Header
			(jQuery)("<div></div>").attr( "id", "playbuzz_popup_close" ).appendTo( "#playbuzz_search_header" ).click( function(){ (jQuery)(".playbuzz_popup_overlay_container").remove(); } );
			(jQuery)("<div></div>").addClass( "playbuzz_search_logo" ).appendTo( "#playbuzz_search_header" ).click( function(){ clear_search_info(); playbuzz_featured_items( 1 ); } );
				(jQuery)("<span></span>").appendTo( ".playbuzz_search_logo" ).text( translation.playbuzz );
			(jQuery)("<nav></nav>").appendTo( "#playbuzz_search_header" );
				(jQuery)("<div></div>").attr( "id", "playbuzz_popup_tab_content" ).click( function(){ clear_search_info(); playbuzz_featured_items( 1 ); } ).addClass( "playbuzz_active_tab" ).appendTo( "#playbuzz_search_header nav" );
				(jQuery)("<div></div>").attr( "id", "playbuzz_popup_tab_myitems" ).click( function(){ clear_search_info(); playbuzz_my_items( 1 );  } ).appendTo( "#playbuzz_search_header nav" );
				(jQuery)("<span></span>").appendTo( "#playbuzz_popup_tab_content" ).text( translation.playbuzz_content );
				(jQuery)("<span></span>").appendTo( "#playbuzz_popup_tab_myitems" ).text( translation.my_items );

			// Input form
			(jQuery)("<input>").attr( "type", "text" ).attr( "id", "playbuzz_search" ).attr( "class", "playbuzz_search" ).attr( "name", "playbuzz_search" ).attr( "size", "16" ).attr( "autocomplete", "off" ).attr( "placeholder", translation.search_term ).appendTo( "#playbuzz_search_input_form" ).keyup( function(){ playbuzz_show_screen(); } );
			(jQuery)("<span></span>").addClass( "playbuzz_search_sep" ).appendTo( "#playbuzz_search_input_form" ).text( "|" );
			(jQuery)("<a></a>").attr( "href", "https://www.playbuzz.com/create" ).attr( "target", "_blank" ).addClass( "playbuzz_create_button" ).appendTo( "#playbuzz_search_input_form" ).text( translation.create_your_own );

			// Sub Header
			(jQuery)("<div></div>").addClass( "playbuzz_search_fields" ).appendTo( "#playbuzz_search_sub_header" );
				(jQuery)("<label></label>"  ).attr( "for",   "playbuzz_search_type" ).addClass( "playbuzz_search_label" ).appendTo( ".playbuzz_search_fields" ).text( translation.show );
				(jQuery)("<select></select>").attr( "name",  "playbuzz_search_type" ).attr( "id", "playbuzz_search_type" ).addClass( "playbuzz_search_type" ).appendTo( ".playbuzz_search_fields" ).change( function(){ playbuzz_show_screen(); } );
				(jQuery)("<option></option>").attr( "value", "" ).appendTo( ".playbuzz_search_type" ).text( translation.all_types );
				(jQuery)("<option></option>").attr( "value", "story,snap-article" ).appendTo( ".playbuzz_search_type" ).text( translation.story );
				(jQuery)("<option></option>").attr( "value", "list" ).appendTo( ".playbuzz_search_type" ).text( translation.list );
				(jQuery)("<option></option>").attr( "value", "personality-quiz" ).appendTo( ".playbuzz_search_type" ).text( translation.personality_quiz );
				(jQuery)("<option></option>").attr( "value", "poll" ).appendTo( ".playbuzz_search_type" ).text( translation.poll );
				(jQuery)("<option></option>").attr( "value", "ranked-list" ).appendTo( ".playbuzz_search_type" ).text( translation.ranked_list );
				(jQuery)("<option></option>").attr( "value", "trivia" ).appendTo( ".playbuzz_search_type" ).text( translation.trivia );
				(jQuery)("<option></option>").attr( "value", "gallery-quiz" ).appendTo( ".playbuzz_search_type" ).text( translation.gallery_quiz );
				(jQuery)("<option></option>").attr( "value", "flip-cards" ).appendTo( ".playbuzz_search_type" ).text( translation.flip_cards );
				(jQuery)("<option></option>").attr( "value", "swiper" ).appendTo( ".playbuzz_search_type" ).text( translation.swiper );
				(jQuery)("<option></option>").attr( "value", "countdown" ).appendTo( ".playbuzz_search_type" ).text( translation.countdown );
				(jQuery)("<option></option>").attr( "value", "video-snaps" ).appendTo( ".playbuzz_search_type" ).text( translation.video_snaps );
				(jQuery)("<option></option>").attr( "value", "convo" ).appendTo( ".playbuzz_search_type" ).text( translation.convo );
				(jQuery)("<label></label>"  ).attr( "for",   "playbuzz_search_sort" ).addClass( "playbuzz_search_label" ).appendTo( ".playbuzz_search_fields" ).text( translation.sort_by );
				(jQuery)("<select></select>").attr( "name",  "playbuzz_search_sort" ).attr( "id", "playbuzz_search_sort" ).addClass( "playbuzz_search_sort" ).appendTo( ".playbuzz_search_fields" ).change( function(){ playbuzz_show_screen(); } );
				(jQuery)("<option></option>").attr( "value", "" ).appendTo( ".playbuzz_search_sort" ).text( translation.relevance );
				(jQuery)("<option></option>").attr( "value", "publishDate" ).appendTo( ".playbuzz_search_sort" ).text( translation.date );
			(jQuery)("<div></div>").attr( "id", "playbuzz_search_for" ).appendTo( "#playbuzz_search_sub_header" );
				(jQuery)("<p></p>").appendTo( "#playbuzz_search_for" ).text( translation.discover_playful_content );
			(jQuery)("<div></div>").addClass( "playbuzz_search_sub_divider" ).appendTo( "#playbuzz_search_sub_header" );

		}

		// Playbuzz item popup - create popup structure
		function playbuzz_item_popup_structure( settings_to_use, item_url, info, shares, comments, recommend, margin_top, width, height, links, tags, itemId, format ) {

			// Popup Components
			(jQuery)("<form></form>").attr( "id", "playbuzz_item_form" ).attr( "name", "item" ).appendTo( "#playbuzz_popup" );
			(jQuery)("<div></div>"  ).attr( "id", "playbuzz_item_header" ).appendTo( "#playbuzz_item_form" );
			(jQuery)("<div></div>"  ).attr( "id", "playbuzz_item_body" ).appendTo( "#playbuzz_item_form" );
			(jQuery)("<div></div>"  ).attr( "id", "playbuzz_item_update" ).appendTo( "#playbuzz_item_form" );

			// Header
			(jQuery)("<div></div>").attr( "id", "playbuzz_popup_close" ).appendTo( "#playbuzz_item_header" ).click( function(){ (jQuery)(".playbuzz_popup_overlay_container").remove(); } );;
			(jQuery)("<p></p>").addClass( "playbuzz_item_header_text" ).appendTo( "#playbuzz_item_header" ).text( translation.playbuzz_item_settings );

			// Footer
			(jQuery)("<input>").attr( "id", "playbuzz_item_settings_format" ).attr( "type", "hidden" ).attr( "value", format ).appendTo( "#playbuzz_item_update" );
			(jQuery)("<input>").attr( "id", "playbuzz_item_settings_url" ).attr( "type", "hidden" ).attr( "value", item_url ).appendTo( "#playbuzz_item_update" );
			(jQuery)("<input>").attr( "id", "playbuzz_item_settings_id" ).attr( "type", "hidden" ).attr( "value", itemId ).appendTo( "#playbuzz_item_update" );
			(jQuery)("<input>").attr( "id", "playbuzz_item_settings_width" ).attr( "type", "hidden" ).attr( "value", width ).appendTo( "#playbuzz_item_update" );
			(jQuery)("<input>").attr( "id", "playbuzz_item_settings_height" ).attr( "type", "hidden" ).attr( "value", height ).appendTo( "#playbuzz_item_update" );
			(jQuery)("<input>").attr( "id", "playbuzz_item_settings_links" ).attr( "type", "hidden" ).attr( "value", links ).appendTo( "#playbuzz_item_update" );
			(jQuery)("<input>").attr( "id", "playbuzz_item_settings_tags" ).attr( "type", "hidden" ).attr( "value", tags ).appendTo( "#playbuzz_item_update" );
			(jQuery)("<div></div>").addClass( "playbuzz_item_cancel_button" ).appendTo( "#playbuzz_item_update" ).text( translation.cancel ).click( function() { (jQuery)( '.playbuzz_popup_overlay_container' ).remove(); } );
			(jQuery)("<div></div>").addClass( "playbuzz_item_update_button" ).appendTo( "#playbuzz_item_update" ).text( translation.update_item );

			// Content
			(jQuery)("<div></div>").attr( "id", "playbuzz_item_preview" ).appendTo( "#playbuzz_item_body" );
			(jQuery)("<div></div>").attr( "id", "playbuzz_item_settings" ).appendTo( "#playbuzz_item_body" );
				(jQuery)("<p></p>"    ).addClass( "playbuzz_item_settings_title" ).appendTo( "#playbuzz_item_settings" ).text( translation.item_settings ).append( (jQuery)("<span></span>" ).text( translation.embedded_item_appearance ) );
				(jQuery)("<div></div>").addClass( "playbuzz_item_settings_select" ).appendTo( "#playbuzz_item_settings" );
					(jQuery)("<input>").attr( "id", "playbuzz_item_settings_default" ).attr( "name", "playbuzz_item_settings" ).attr( "type", "radio" ).attr( "value", "default" ).appendTo( ".playbuzz_item_settings_select" );
					(jQuery)("<label></label>").attr( "for", "playbuzz_item_settings_default" ).appendTo( ".playbuzz_item_settings_select" ).text( translation.use_site_default_settings ).append( (jQuery)("<a></a>").attr( "target", "_blank" ).attr( "href", "options-general.php?page=playbuzz&tab=embed" ).text( translation.configure_default_settings ) );
					(jQuery)("<br>").appendTo( ".playbuzz_item_settings_select" );
					(jQuery)("<input>").attr( "id", "playbuzz_item_settings_custom" ).attr( "name", "playbuzz_item_settings" ).attr( "type", "radio" ).attr( "value", "custom" ).appendTo( ".playbuzz_item_settings_select" );
					(jQuery)("<label></label>").attr( "for", "playbuzz_item_settings_custom" ).appendTo( ".playbuzz_item_settings_select" ).text( translation.custom );
					(jQuery)("<br>").appendTo( ".playbuzz_item_settings_select" );

					(jQuery)("<div></div>").addClass( "settings_half settings_half1" ).appendTo( ".playbuzz_item_settings_select" );
					(jQuery)("<input>").attr( "id", "playbuzz_item_settings_info" ).attr( "type", "checkbox" ).appendTo( ".settings_half1" );
					(jQuery)("<label></label>").attr( "for", "playbuzz_item_settings_info" ).appendTo( ".settings_half1" ).text( translation.display_item_information );
					(jQuery)("<div></div>").addClass( "description" ).appendTo( ".settings_half1" ).text( translation.show_item_thumbnail_name_description_creator );
					(jQuery)("<input>").attr( "id", "playbuzz_item_settings_shares" ).attr( "type", "checkbox" ).appendTo( ".settings_half1" );
					(jQuery)("<label></label>").attr( "for", "playbuzz_item_settings_shares" ).appendTo( ".settings_half1" ).text( translation.display_share_buttons );
					(jQuery)("<div></div>").addClass( "description" ).appendTo( ".settings_half1" ).text( translation.show_share_buttons_with_links_to_your_site );

					(jQuery)("<div></div>").addClass( "settings_half settings_half2" ).appendTo( ".playbuzz_item_settings_select" );
					(jQuery)("<input>").attr( "id", "playbuzz_item_settings_comments" ).attr( "type", "checkbox" ).appendTo( ".settings_half2" );
					(jQuery)("<label></label>").attr( "for", "playbuzz_item_settings_comments" ).appendTo( ".settings_half2" ).text( translation.display_facebook_comments );
					(jQuery)("<div></div>").addClass( "description" ).appendTo( ".settings_half2" ).text( translation.show_facebook_comments_in_your_items );
					(jQuery)("<input>").attr( "id", "playbuzz_item_settings_margin" ).attr( "type", "checkbox" ).appendTo( ".settings_half2" );
					(jQuery)("<label></label>").attr( "for", "playbuzz_item_settings_margin" ).appendTo( ".settings_half2" ).text( translation.site_has_fixed_sticky_top_header );
					(jQuery)("<div></div>").addClass( "playbuzz_item_settings_margin_top_text" ).appendTo( ".settings_half2" ).text( translation.height + " " );
					(jQuery)("<input>").attr( "id", "playbuzz_item_settings_margin_top" ).attr( "type", "input" ).attr( "value", margin_top ).appendTo( ".playbuzz_item_settings_margin_top_text" ).text( translation.px );
					(jQuery)("<div></div>").addClass( "description" ).appendTo( ".settings_half2" ).text( translation.use_this_if_your_website_has_top_header_thats_always_visible_even_while_scrolling_down );

			// Select Settings
			if ( settings_to_use == "default" ) {
				(jQuery)("#playbuzz_item_settings_default").prop( 'checked', true );
			}

			if ( settings_to_use == "custom"  ) {
				(jQuery)("#playbuzz_item_settings_custom").prop( 'checked', true );
			}

			if ( ( typeof info != 'undefined' ) && ( info.length ) && ( ( info == true ) || ( info > 0 ) || ( info.toLowerCase() == "true" ) || ( info.toLowerCase() == "on" ) || ( info == "1" ) ) ) {
				(jQuery)("#playbuzz_item_settings_info").prop( 'checked', true );
			}

			if ( ( typeof shares != 'undefined' ) && ( shares.length ) && ( ( shares == true ) || ( shares > 0 ) || ( shares.toLowerCase() == "true" ) || ( shares.toLowerCase() == "on" ) || ( shares == "1" ) ) ) {
				(jQuery)("#playbuzz_item_settings_shares").prop( 'checked', true );
			}

			if ( ( typeof recommend != 'undefined' ) && ( recommend.length ) && ( ( recommend == true ) || ( recommend > 0 ) || ( recommend.toLowerCase() == "true" ) || ( recommend.toLowerCase() == "on" ) || ( recommend == "1" ) ) ) {
				(jQuery)("#playbuzz_item_settings_recommend").prop( 'checked', true );
			}

			if ( ( typeof comments != 'undefined' ) && ( comments.length ) && ( ( comments == true ) || ( comments > 0 ) || ( comments.toLowerCase() == "true" ) || ( comments.toLowerCase() == "on" ) || ( comments == "1" ) ) ) {
				(jQuery)("#playbuzz_item_settings_comments").prop( 'checked', true );
			}

			if ( ( typeof margin_top != 'undefined' ) && ( margin_top.length ) && ( ( margin_top == true ) || ( margin_top > 0 ) || ( margin_top.toLowerCase() == "true" ) || ( margin_top.toLowerCase() == "on" ) || ( margin_top == "1" ) ) ) {
				(jQuery)("#playbuzz_item_settings_margin_top").prop( 'checked', true );
			}

		}

		// Playbuzz popup error message
		function playbuzz_popup_message( popup_title, message_title, message_content ) {

			// Popup title
			(jQuery)("#playbuzz_search_for").empty().append(
				(jQuery)("<p></p>").append( popup_title )
			);

			// Popup content
			(jQuery)("#playbuzz_search_results").empty().append(
				(jQuery)("<div></div>").addClass( "playbuzz_error_message" ).append(
					(jQuery)("<div></div>").addClass( "playbuzz_notice" ).append(
						(jQuery)("<h3></h3>").append( message_title )
					).append(
						(jQuery)("<p></p>").append( message_content )
					)
				)
			);

		}

		// Playbuzz no user screen
		function playbuzz_no_user() {

			// Update tabs
			(jQuery)("#playbuzz_popup_tab_content").removeClass( "playbuzz_active_tab" );
			(jQuery)("#playbuzz_popup_tab_myitems").addClass( "playbuzz_active_tab" );

			// Popup title
			(jQuery)("#playbuzz_search_for").empty().append(
				(jQuery)("<p></p>").append(
					(jQuery)("<span></span>").addClass( "playbuzz_search_title_user_img" ).append(
						(jQuery)("<a></a>")
						.attr( "target", "_blank" )
						.attr( "href", "options-general.php?page=playbuzz&tab=embed" )
						.addClass( "playbuzz_set_username_link" )
						.text( translation.set_user )
					)
				)
			);

			// Popup content
			(jQuery)("#playbuzz_search_results").empty().append(
				(jQuery)("<div></div>").addClass( "playbuzz_error_message" ).append(
					(jQuery)("<div></div>").addClass( "playbuzz_notice" ).append(
						(jQuery)("<h3></h3>").append( translation.no_user )
					).append(
						(jQuery)("<p></p>").append( translation.set_your_username )
					)
				)
			);

		}

		// Playbuzz search results
		function playbuzz_search_results( layout, data, popup_title, popup_title_paging  ) {

			// Popup title
			(jQuery)("#playbuzz_search_for")
			.empty()
			.append(
				(jQuery)("<p></p>")
				.append( popup_title )
				.append( popup_title_paging )
			);

			// Popup content
			(jQuery)("#playbuzz_search_results").empty();
			(jQuery)("#playbuzz_search_results")[0].scrollTop = 0;

			(jQuery).each(data, function(key, val) {

				(jQuery)("<div></div>")
				.addClass( "playbuzz_" + layout + "_view" )
				.appendTo( "#playbuzz_search_results" )
				.append(
					// thumbnail
					(jQuery)("<div></div>")
					.addClass( "playbuzz_present_item_thumb" )
					.append(
						(jQuery)("<img>",{ src:val.imageMedium } )
					)
				)
				.append(
					// desc
					(jQuery)("<div></div>")
					.addClass( "playbuzz_present_item_desc" )
					.append(
						(jQuery)("<div></div>")
						.addClass( "playbuzz_present_item_title" )
						.text( val.title )
					)
					.append(
						(jQuery)("<div></div>")
						.addClass( "playbuzz_present_item_meta" )
						.text( translation.by + " " )
						.append(
							(jQuery)("<span></span>" )
							.text( val.channelName )
						)
						.append( " " + translation.on + " " + item_date( val.publishDate ) )
					)
				)
				.append(
					// type
					(jQuery)("<div></div>")
					.addClass( "playbuzz_present_item_type" )
					.append(
						(jQuery)("<span></span>")
						.text( playbuzz_item_type( val.format ) )
					)
				)
				.append(
					// buttons
					(jQuery)("<div></div>")
					.addClass( "playbuzz_present_item_buttons" )
					.append(
						(jQuery)("<a></a>")
						.addClass( "button button-secondary" )
						.attr( "target", "_blank" )
						.attr( "href", val.playbuzzUrl )
						.text( translation.view )
					)
					.append(
						(jQuery)("<input>")
						.attr( "type", "button" )
						.attr( "class", "button button-primary" )
						.attr( "value", translation.embed )
						.click( function() {
							return playbuzz_shortcode_embed( val.id, val.format )
						})
					)
				);
			});

		}

		// Playbuzz popup pagination
		function playbuzz_popup_pagination( total_pages, current_page, type ) {

			// Set current page
			current_page = ( isNaN( current_page ) ) ? parseInt( current_page ) : current_page ;
			current_page = ( current_page < 1 ) ? 1 : current_page ;

			// Set start page
			var start_page = current_page -2;
			if ( start_page <= 0 ) { start_page = 1;
			}

			// Set end_page
			var end_page = current_page + 2;
			if ( end_page >= total_pages ) { end_page = total_pages;
			}

			// Open pagination container
			(jQuery)("<div></div>")
			.addClass( "playbuzz_item_pagination" )
			.addClass( type )
			.attr( "data-function", type )
			.appendTo( "#playbuzz_search_results" );

			// Add prev page link
			if ( current_page == 1 ) {
				(jQuery)("<a></a>")
				.addClass( "playbuzz_prev disabled_pagination" )
				.appendTo( ".playbuzz_item_pagination" );
			} else {
				(jQuery)("<a></a>")
				.attr( "onclick", type + "(" + (current_page -1) + ")" )
				.addClass( "playbuzz_prev enabled_pagination" )
				.appendTo( ".playbuzz_item_pagination" );
			}

			// Add pages
			for (page = start_page; page <= end_page; ++page) {
				current_page_class = ( (page == current_page) ? " playbuzz_current" : "" );

				(jQuery)("<a></a>")
				.attr( "onclick", type + "(" + page + ")" )
				.addClass( "enabled_pagination" )
				.addClass( current_page_class )
				.appendTo( ".playbuzz_item_pagination" )
				.text( page );
			}

			// Add next page link
			if ( current_page == total_pages ) {
				(jQuery)("<a></a>")
				.addClass( "playbuzz_next disabled_pagination" )
				.appendTo( ".playbuzz_item_pagination" );
			} else {
				(jQuery)("<a></a>")
				.attr( "onclick", type + "(" + (current_page + 1) + ")" )
				.addClass( "playbuzz_next enabled_pagination" )
				.appendTo( ".playbuzz_item_pagination" );
			}

		}

		// Playbuzz show popup screen
		function playbuzz_show_screen() {

			var is_content_tab = ( ( (jQuery)("#playbuzz_popup_tab_content").hasClass( "playbuzz_active_tab" ) ) ? true : false ),
				is_search      = ( ( (jQuery)("#playbuzz_search").val().trim() != '' ) ? true : false );

			if ( is_search ) {
				if ( is_content_tab ) {
					playbuzz_general_search( 1 );
				} else {
					playbuzz_user_search( 1 );
				}
			} else {
				if ( is_content_tab ) {
					playbuzz_featured_items( 1 );
				} else {
					playbuzz_my_items( 1 );
				}
			}

		}

		// Playbuzz featured items screen
		function playbuzz_featured_items( current_page ) {

			// Set variables
			var results_layout = "grid",
				results_title  = translation.featured_items,
				items_per_page = 30;

			// Update tabs
			(jQuery)("#playbuzz_popup_tab_content").addClass( "playbuzz_active_tab" );
			(jQuery)("#playbuzz_popup_tab_myitems").removeClass( "playbuzz_active_tab" );

			// Load items using the Playbuzz API
			(jQuery).ajax({
				url      : apiBaseUrl,
				type     : "get",
				dataType : "json",
				data     : {
					internalTags : "EditorsPick_Featured",
					format	    : (jQuery)("#playbuzz_search_type").val(),
					sort        : (jQuery)("#playbuzz_search_sort").val(),
					size        : items_per_page,
					from        : (current_page * items_per_page) -items_per_page
				},
				error    : function( data ) {

					// Server Error
					playbuzz_popup_message( results_title, translation.server_error, translation.try_in_a_few_minutes );
					console.error( "Couldn't get data: ", data );

				},
				success  : function( data ) {

					// Set variables
					var total_items   = data.payload.totalItems,
						total_pages   = ( ( total_items >= items_per_page ) ? Math.ceil( total_items / items_per_page ) : 1 ),
						results_pages = ( ( current_page > 1 ) ? " <span class='playbuzz_search_title_pagination'>(" + translation.page + " " + current_page + " / " + total_pages + ")" : "" );

					// Data output
					if ( total_items > 0 ) {
						// Show Results
						playbuzz_search_results( results_layout, data.payload.items, results_title, results_pages );
						// Pagination
						if ( total_items > items_per_page ) {
							playbuzz_popup_pagination( total_pages, current_page, 'playbuzz_featured_items' );
						}
					} else {
						// No Search Results
						playbuzz_popup_message( results_title, translation.no_results_found, translation.try_different_search );
					}

				}

			});

		}
		window.playbuzz_featured_items = playbuzz_featured_items;

		function is_playbuzz_url( url ) {
			var valid = ["http://www.playbuzz.com/", "https://www.playbuzz.com", "www.playbuzz.com"];
			for (var i = 0; i < valid.length; i++) {
				if (url.indexOf( valid[i] ) === 0) {
					return true;
				}
			}

			return false;
		}

		// Playbuzz general search screen
		function playbuzz_general_search( current_page ) {
			var search_param = (jQuery)("#playbuzz_search").val();

			// Set variables
			var results_layout = "list",

			string = (jQuery)("#playbuzz_search").val();
			if (string.length > 25) {
				string = string.substring( 0,16 ) + "...";
			}

			results_title  = ( translation.results_for + " '" + string + "'" ),
			items_per_page = 30;

			// Update tabs
			(jQuery)("#playbuzz_popup_tab_content").addClass( "playbuzz_active_tab" );
			(jQuery)("#playbuzz_popup_tab_myitems").removeClass( "playbuzz_active_tab" );

			var dataObject;
			if (is_playbuzz_url( search_param )) {
				dataObject = {
					playbuzzUrl : search_param
				};
			} else {
				dataObject = {
					q         : search_param,
					format 	  : (jQuery)("#playbuzz_search_type").val(),
					sort      : (jQuery)("#playbuzz_search_sort").val(),
					size      : items_per_page,
					from      : (current_page * items_per_page) -items_per_page
				};
			}

			// Load items using the Playbuzz API
			(jQuery).ajax({
				url      : apiBaseUrl,
				type     : "get",
				dataType : "json",
				data     : dataObject,
				error    : function( data ) {

					// Server Error
					playbuzz_popup_message( results_title, translation.server_error, translation.try_in_a_few_minutes );
					console.error( "Couldn't get data: ", data );

				},
				success  : function( data ) {

					// Set variables
					var total_items   = data.payload.totalItems,
						total_pages   = ( ( total_items >= items_per_page ) ? Math.ceil( total_items / items_per_page ) : 1 ),
						results_pages = ( ( current_page > 1 ) ? " <span class='playbuzz_search_title_pagination'>(" + translation.page + " " + current_page + " / " + total_pages + ")" : "" );

					// Data output
					if ( total_items > 0 ) {
						// Show Results
						playbuzz_search_results( results_layout, data.payload.items, results_title, results_pages );
						// Pagination
						if ( total_items > items_per_page ) {
							playbuzz_popup_pagination( total_pages, current_page, 'playbuzz_general_search' );
						}
					} else {
						// No Search Results
						playbuzz_popup_message( results_title, translation.no_results_found, translation.try_different_search );
					}

				}

			});

		}
		window.playbuzz_general_search = playbuzz_general_search;

		// Playbuzz my items screen
		function playbuzz_my_items( current_page ) {

			// exit if username is not set
			if ( ! site_settings.pb_user || 0 === site_settings.pb_user ) {
				playbuzz_no_user();
				return;
			}

			// Set variables
			var results_layout = "list",
				results_title  = ( "<span class='playbuzz_search_title_user_img'>" + site_settings.pb_user + "</span>" ),
				items_per_page = 30;

			// Update tabs
			(jQuery)("#playbuzz_popup_tab_content").removeClass( "playbuzz_active_tab" );
			(jQuery)("#playbuzz_popup_tab_myitems").addClass( "playbuzz_active_tab" );

			// Load items using the Playbuzz API
			(jQuery).ajax({
				url      : apiBaseUrl,
				type     : "get",
				dataType : "json",
				data     : {
					format	         : (jQuery)("#playbuzz_search_type").val(),
					sort             : (jQuery)("#playbuzz_search_sort").val(),
					size             : items_per_page,
					from             : (current_page * items_per_page) -items_per_page,
					channelAlias	 : site_settings.pb_user,
					moderation       : false
				},
				error    : function( data ) {

					// Server Error
					playbuzz_popup_message( results_title, translation.server_error, translation.try_in_a_few_minutes );
					console.error( "Couldn't get data: ", data );

				},
				success  : function( data ) {

					// Set variables
					var total_items   = data.payload.totalItems,
						total_pages   = ( ( total_items >= items_per_page ) ? Math.ceil( total_items / items_per_page ) : 1 ),
						results_pages = ( " <span class='playbuzz_search_title_pagination'>(" + total_items + " " + translation.items + ")" ),
						change_user   = ( "<a href='options-general.php?page=playbuzz&tab=embed' target='_blank' class='playbuzz_change_username_link'>" + translation.change_user + "</a>" );
						create_button = ( "<div class='playbuzz_create_button'><a href='https://www.playbuzz.com/create' target='_blank'>" + translation.create_your_own + "</a></div>")

					// Data output
					if ( data.payload.currentItemCount > 0 ) {

						// Show Results
						playbuzz_search_results( results_layout, data.payload.items, results_title, results_pages + change_user );
						// Pagination
						if ( total_items > items_per_page ) {
							playbuzz_popup_pagination( total_pages, current_page, 'playbuzz_my_items' );
						}
					} else {
						// No Search Results
						playbuzz_popup_message( results_title + results_pages + change_user, translation.you_dont_have_any_items_yet, translation.go_to_playbuzz_to_create_your_own_playful_content + create_button );
					}

				}

			});

		}
		window.playbuzz_my_items = playbuzz_my_items;

		// Playbuzz user search screen
		function playbuzz_user_search( current_page ) {

			// exit if username is not set
			if ( ! site_settings.pb_user || 0 === site_settings.pb_user ) {
				playbuzz_no_user();
				return;
			}

			var search_param = (jQuery)("#playbuzz_search").val();

			if (search_param.length > 25) {
				search_param = search_param.substring( 0,16 ) + "...";
			}

			// Set variables
			var results_layout = "list",
				results_title  = (  translation.results_for + " '" + search_param + "' " ),
				items_per_page = 30;

			var dataObject;
			if (is_playbuzz_url( search_param )) {
				dataObject = {
					playbuzzUrl: search_param
				};
			} else {
				dataObject = {
					q                : search_param,
					format           : (jQuery)("#playbuzz_search_type").val(),
					sort             : (jQuery)("#playbuzz_search_sort").val(),
					size             : items_per_page,
					from             : (current_page * items_per_page) -items_per_page,
					channelAlias	 : site_settings.pb_user,
					moderation       : false
				};
			}

			// Update tabs
			(jQuery)("#playbuzz_popup_tab_content").removeClass( "playbuzz_active_tab" );
			(jQuery)("#playbuzz_popup_tab_myitems").addClass( "playbuzz_active_tab" );

			// Load items using the Playbuzz API
			(jQuery).ajax({
				url      : apiBaseUrl,
				type     : "get",
				dataType : "json",
				data     : dataObject,
				error    : function( data ) {

					// Server Error
					playbuzz_popup_message( results_title, translation.server_error, translation.try_in_a_few_minutes );
					console.error( "Couldn't get data: ", data );

				},
				success  : function( data ) {

					// Set variables
					var total_items  = data.payload.totalItems,
						total_pages  = ( ( total_items >= items_per_page ) ? Math.ceil( total_items / items_per_page ) : 1 ),
						results_pages = ( ( current_page > 1 ) ? " <span class='playbuzz_search_title_pagination'>(" + translation.page + " " + current_page + " / " + total_pages + ")" : "" );

					// Data output
					if ( data.payload.items.length > 0 ) {
						// Show Results
						playbuzz_search_results( results_layout, data.payload.items, results_title, results_pages );
						// Pagination
						if ( total_items > items_per_page ) {
							playbuzz_popup_pagination( total_pages, current_page, 'playbuzz_user_search' );
						}
					} else {
						// No Search Results
						playbuzz_popup_message( results_title, translation.no_results_found, translation.try_different_search );
					}

				}

			});

		}
		window.playbuzz_user_search = playbuzz_user_search;

		/**
		 *
		 *  TINYMCE PLUGIN
		 *
		 */

		// Add playbuzz search popup
		editor.addCommand( 'search_playbuzz_items', function( ui, v ) {

			// Open Playbuzz Popup
			playbuzz_popup();

			// Create popup structure (search popup)
			playbuzz_search_popup_structure();

			// Show featured items (on load)
			playbuzz_featured_items( 1 );

		});

		// Add playbuzz button to tinyMCE visual editor
		editor.addButton( 'playbuzz', {
			icon    : 'playbuzz',
			tooltip : 'Playbuzz',
			onclick : function() {
				// Open search popup
				editor.execCommand( 'search_playbuzz_items' );
			}
		});

		// Replace the shortcode with an item info box
		editor.on( 'BeforeSetContent', function( event ) {

			event.content = event.content.replace( /\[playbuzz-item([^\]]*)\]/g, function( all, attr, con ) {

				// Encode all the shortcode attributes, to be stored in <div data-playbuzz-attr="...">
				var encodedShortcodeAttributes = window.encodeURIComponent( attr );

				// Split shortcode attributes
				var splitedAttr = attr.split( " " );

				// Extract itemPath from itemUrl -  "http://playbuzz.com/{creatorName}/{gameName}
				var itemId 		  = get_attr( decodeURIComponent( encodedShortcodeAttributes ), 'item' ),
					itemUrl       = get_attr( decodeURIComponent( encodedShortcodeAttributes ), 'url' ),
				    itemPath      = itemUrl.split( "playbuzz.com/" ).pop(),
				    itemPathArray = itemPath.split( "/" ),
				    creatorName   = itemPathArray[0],
				    gameName      = itemPathArray[1];

				var data = {
					size				: 1,
					moderation       	: false
				};

				if (itemUrl) {
					data.alias = creatorName + "/" + gameName;
				} else {
					data.id = itemId;
				}

				// Set random image id
				var id = Math.round( Math.random() * 100000 );

				// Get Item info
				(jQuery).ajax({
					url      : apiBaseUrl,
					type     : "get",
					dataType : "json",
					data     : data,
					success  : function( data ) {

						// Data output
						if ( data.payload.totalItems > 0 ) {

							var item = data.payload.items[0];

							// Set item image
							(jQuery)(tinyMCE.activeEditor.dom.doc.body)
							.find( "#playbuzz_placeholder_" + id )
							.attr( "src", item.imageLarge );

							// Set item info
							(jQuery)(tinyMCE.activeEditor.dom.doc.body)
							.find( "#playbuzz_info_" + id )
							.empty()
							.append(
								// Title
								(jQuery)("<p></p>")
								.addClass( "wp_playbuzz_title" )
								.text( item.title )
							)
							.append(
								// Meta
								(jQuery)("<p></p>")
								.addClass( "wp_playbuzz_meta" )
								.text( translation.created_by + " " ).append(
									(jQuery)("<span></span>" )
									.addClass( "wp_playbuzz_author" )
									.text( item.channelName )
								)
								.append( " " + translation.on + " " + item_date( item.publishDate ) )
							);

						} else {

							// Set playbuzz logo
							(jQuery)(tinyMCE.activeEditor.dom.doc.body)
							.find( "#playbuzz_placeholder_" + id )
							.attr( "src", url + '/../img/playbuzz-placeholder.png' );

							// Set "item not found" text
							(jQuery)(tinyMCE.activeEditor.dom.doc.body)
							.find( "#playbuzz_info_" + id )
							.empty()
							.append(
								// Title
								(jQuery)("<p></p>")
								.addClass( "wp_playbuzz_title" )
								.text( translation.item_doesnt_exist )
							)
							.append(
								// Meta
								(jQuery)("<p></p>")
								.addClass( "wp_playbuzz_meta" )
								.text( translation.check_shortcode_url )
							);

						}

					}

				});

				// Shortcode replacement

				var container = (jQuery)('<div></div>');
				var playbuzz_info = (jQuery)('<div class="wp_playbuzz_info"></div>').attr( 'id', "playbuzz_info_" + id );
				var playbuzz_image = (jQuery)('<div class="wp_playbuzz_image"></div>').attr( 'id', "playbuzz_image_" + id );
				var playbuzz_placeholder = (jQuery)('<img class="mceItem wp_playbuzz_placeholder" data-mce-resize="false" data-mce-placeholder="1" />')
						.attr( 'id', "playbuzz_placeholder_" + id )
						.attr( 'src', url + "/../img/playbuzz-placeholder.png" );
				playbuzz_image.append( playbuzz_placeholder );

				var playbuzz_embed = (jQuery)('<div class="wp_playbuzz_embed"></div>')
					.attr( 'id', "playbuzz_embed_" + id )
					.text( translation.your_item_will_be_embedded_here );

				var playbuzz_buttons = (jQuery)('<div class="wp_playbuzz_buttons"></div>')
					.attr( 'id', "playbuzz_overlay_" + id )
					.attr( 'data-playbuzz-attr', encodedShortcodeAttributes );

				var playbuzz_delete = (jQuery)('<div class="wp_playbuzz_delete"></div>').attr( 'id', "playbuzz_overlay_close_" + id );
				var playbuzz_edit = (jQuery)('<div class="wp_playbuzz_edit"></div>')
					.attr( 'id', "playbuzz_overlay_edit_" + id )
					.attr( 'data-playbuzz-attr', encodedShortcodeAttributes );

				container
					.append( playbuzz_info )
					.append( playbuzz_image )
					.append( playbuzz_embed )
					.append( playbuzz_buttons )
					.append( playbuzz_delete )
					.append( playbuzz_edit );

				return '<div class="wp_playbuzz_container" contenteditable="false">'
							+ container.html() +
					   '</div>';
			});

		});

		// Replace the item info box with the shortcode
		editor.on( 'GetContent', function( event ) {

			event.content = event.content.replace( /((<div class="wp_playbuzz_container"[^<>]*>)(.*?)(?:<\/div><\/div>))*/g, function( match, tag ) {

				// Extract shortcode attributes from <div data-playbuzz-attr="...">
				var data = get_attr( tag, 'data-playbuzz-attr' );

				// Create the shortcode
				if ( data ) {
					return  '<p>[playbuzz-item' + data + ']</p>';
				}

				return match;

			});

		});

		// Item edit popup
		editor.on( 'click', function(e) {

			// Delete item
			if ( ( e.target.nodeName == 'DIV' ) && ( e.target.className.indexOf( 'wp_playbuzz_delete' ) > -1 ) ) {
				//var id = tinyMCE.activeEditor.selection.getNode().id;
				var id = e.target.id;
				if (id !== "") {
					(jQuery)(tinyMCE.activeEditor.dom.doc.body).find( "#" + id ).parent().remove();
					(jQuery)(tinyMCE.activeEditor.dom.doc.body).append( '<p><br data-mce-bogus="1"></p>' );
					//Force cursor activation
					(jQuery)('#content-html').trigger( 'click' );
					setTimeout( "(jQuery)('#content-tmce').trigger('click')", 200 )
				} else {
					(jQuery)(tinyMCE.activeEditor.selection.getNode()).remove();
				}
				(jQuery)( '.playbuzz_popup_overlay_container' ).remove();
			}

			// Edit item
			if ( ( e.target.nodeName == 'DIV' ) && ( ( e.target.className.indexOf( 'wp_playbuzz_buttons' ) > -1 ) || ( e.target.className.indexOf( 'wp_playbuzz_edit' ) > -1 ) ) ) {

				// Extract shortcode attributes stored in <div data-playbuzz-attr="...">
				var attr = e.target.attributes['data-playbuzz-attr'].value;
				attr = window.decodeURIComponent( attr );

				// Set values
				var item_url      = get_attr( attr, 'url' ),
					itemId            = get_attr( attr, 'item' ),
					info          = get_attr( attr, 'info' ),
					shares        = get_attr( attr, 'shares' ),
					comments      = get_attr( attr, 'comments' ),
					recommend     = get_attr( attr, 'recommend' ),
					margin_top    = get_attr( attr, 'margin-top' ),
					width         = get_attr( attr, 'width' ),
					height        = get_attr( attr, 'height' ),
					links         = get_attr( attr, 'links' ),
					tags          = get_attr( attr, 'tags' ),
					format          = get_attr( attr, 'format' ),
					itemPath      = item_url.split( 'playbuzz.com/' ).pop(),
					itemPathArray = itemPath.split( "/" ),
					creatorName   = itemPathArray[0],
					gameName      = itemPathArray[1];

				var data = {
					size				: 1,
					moderation       	: false
				};

				if (item_url) {
					data.alias = creatorName + "/" + gameName;
				} else {
					data.id = itemId;
				}

				// Which settings to use ? site default or custom item settings
				var settings_to_use = ( ( info.length > 0 ) || ( shares.length > 0 ) || ( comments.length > 0 ) || ( recommend.length > 0 ) || ( margin_top.length > 0 ) || ( ! isNaN( margin_top ) && margin_top.trim() != '' ) ) ? 'custom' : 'default';

				// Open Playbuzz Popup
				playbuzz_popup();

				// Create item popup structure
				playbuzz_item_popup_structure( settings_to_use, item_url, info, shares, comments, recommend, margin_top, width, height, links, tags, itemId, format );

				// Item Preview
				(jQuery).ajax({
					url      : apiBaseUrl,
					type     : "get",
					dataType : "json",
					data     : data,
					error    : function( data ) {

						// Clear preview
						(jQuery)("#playbuzz_item_preview").empty();
						console.error( "Couldn't get data: ", data );

					},
					success  : function( data ) {

						if ( data.payload.items.length > 0 ) {

							var item = data.payload.items[0];

							// Create preview
							(jQuery)("#playbuzz_item_preview").empty().append(
								(jQuery)("<table></table>").append(
									(jQuery)("<tbody></tbody>").append(
										(jQuery)("<tr></tr>").attr( "valign", "top" ).append(
											(jQuery)("<td></td>").addClass( "playbuzz_item_thumb" )
										).append(
											(jQuery)("<td></td>").addClass( "playbuzz_item_info" )
										)
									)
								)
							);

							// Add thumb
							(jQuery)("<p></p>").addClass( "playbuzz_item_thumb" ).appendTo( "td.playbuzz_item_thumb" );
							(jQuery)("<img>").attr( "src", item.imageLarge ).appendTo( "p.playbuzz_item_thumb" );

							// Add info
							(jQuery)("<p></p>").addClass( "playbuzz_item_title" ).appendTo( "td.playbuzz_item_info" ).text( item.title );
							(jQuery)("<p></p>").addClass( "playbuzz_item_meta" ).appendTo( "td.playbuzz_item_info" ).text( translation.created_by + " " ).append(
								(jQuery)("<span></span>" ).html( "<a target='_blank' href='http://www.playbuzz.com/" + item.channelAlias + "'>" + item.channelName + "</a> " )
							).append( translation.on + " " + item_date( item.publishDate ) );
							(jQuery)("<p></p>").addClass( "playbuzz_item_desc" ).appendTo( "td.playbuzz_item_info" ).text( item.description );
							(jQuery)("<p></p>").addClass( "playbuzz_item_view_type_link" ).appendTo( "td.playbuzz_item_info" );
							(jQuery)("<span></span>").addClass( "playbuzz_item_type" ).appendTo( "p.playbuzz_item_view_type_link" ).text( playbuzz_item_type( item.format ) );
							(jQuery)("<span></span>").addClass( "playbuzz_item_link" ).appendTo( "p.playbuzz_item_view_type_link" );
							(jQuery)("<a></a>").attr( "target", "_blank" ).attr( "href", item.playbuzzUrl ).appendTo( ".playbuzz_item_link" ).text( translation.preview_item );

						}

					}
				});

				// Set/Change fields visibility
				function settings_visibility() {
					if ( (jQuery)("input[type='radio'][name='playbuzz_item_settings']:checked").val() == 'default' ) {
						(jQuery)(".settings_half").addClass( "settings_disabled" );
						(jQuery)("#playbuzz_item_settings_info").prop( "disabled", true );
						(jQuery)("#playbuzz_item_settings_shares").prop( "disabled", true );
						(jQuery)("#playbuzz_item_settings_recommend").prop( "disabled", true );
						(jQuery)("#playbuzz_item_settings_comments").prop( "disabled", true );
						(jQuery)("#playbuzz_item_settings_margin").prop( "disabled", true );
						(jQuery)("#playbuzz_item_settings_margin_top").prop( "disabled", true );
					} else {
						(jQuery)(".settings_half").removeClass( "settings_disabled" );
						(jQuery)("#playbuzz_item_settings_info").prop( "disabled", false );
						(jQuery)("#playbuzz_item_settings_shares").prop( "disabled", false );
						(jQuery)("#playbuzz_item_settings_recommend").prop( "disabled", false );
						(jQuery)("#playbuzz_item_settings_comments").prop( "disabled", false );
						(jQuery)("#playbuzz_item_settings_margin").prop( "disabled", false );
						if ( (jQuery)("#playbuzz_item_settings_margin").prop( "checked" ) ) {
							(jQuery)("#playbuzz_item_settings_margin_top").prop( "disabled", false );
						} else {
							(jQuery)("#playbuzz_item_settings_margin_top").prop( "disabled", true );
						}
					}
				}
				settings_visibility();
				(jQuery)("input[type='radio'][name='playbuzz_item_settings']:radio").change(function(){
					settings_visibility();
				});

				// Margin-top
				if ( ! isNaN( margin_top ) && margin_top.trim() != '' ) {
					(jQuery)("#playbuzz_item_settings_margin").prop( 'checked', true );
					(jQuery)("#playbuzz_item_settings_margin_top").prop( "disabled", false );
				} else {
					(jQuery)("#playbuzz_item_settings_margin_top").prop( "disabled", true );
				}

				// Change margin top
				(jQuery)("#playbuzz_item_settings_margin").change(function(){
					if ( (jQuery)(this).is( ':checked' ) ) {
						(jQuery)("#playbuzz_item_settings_margin_top").prop( "disabled", false );
					} else {
						(jQuery)("#playbuzz_item_settings_margin_top").prop( "disabled", true );
					}
				});

				// Click Update button
				(jQuery)(".playbuzz_item_update_button").click(function( e ) {

					// start shortcode tag
					var shortcode_str = '[playbuzz-item';

					// use site default settings or custom settings
					default_or_custom = (jQuery)("input[type='radio'][name='playbuzz_item_settings']:checked").val();

					var new_item_id = (jQuery)("#playbuzz_item_settings_id");

					if ( typeof new_item_id != 'undefined' && new_item_id.length && new_item_id.val() != '') {
						shortcode_str += ' item="' + new_item_id.val() + '"';
					} else {
						// add "url"
						new_item_url = (jQuery)("#playbuzz_item_settings_url");
						if ( typeof new_item_url != 'undefined' && new_item_url.length && new_item_url.val() != '' ) {
							shortcode_str += ' url="' + new_item_url.val() + '"';
						}
					}

					// add "info"
					new_info = (jQuery)("#playbuzz_item_settings_info").prop( "checked" );
					if ( default_or_custom == 'custom' ) {
						shortcode_str += ' info="' + new_info + '"';
					}

					// add "shares"
					new_shares = (jQuery)("#playbuzz_item_settings_shares").prop( "checked" );
					if ( default_or_custom == 'custom' ) {
						shortcode_str += ' shares="' + new_shares + '"';
					}

					// add "comments"
					new_comments = (jQuery)("#playbuzz_item_settings_comments").prop( "checked" );
					if ( default_or_custom == 'custom' ) {
						shortcode_str += ' comments="' + new_comments + '"';
					}

					// add "recommend"
					new_recommend = (jQuery)("#playbuzz_item_settings_recommend").prop( "checked" );
					if ( default_or_custom == 'custom' ) {
						shortcode_str += ' recommend="' + new_recommend + '"';
					}

					// add "links"
					new_links = (jQuery)("#playbuzz_item_settings_links");
					if ( typeof new_links != 'undefined' && new_links.length && new_links.val() != '' ) {
						shortcode_str += ' links="' + new_links.val() + '"';
					}

					// add "tags"
					new_tags = (jQuery)("#playbuzz_item_settings_tags");
					if ( typeof new_tags != 'undefined' && new_tags.length && new_tags.val() != '' ) {
						shortcode_str += ' tags="' + new_tags.val() + '"';
					}

					// add "width"
					new_width = (jQuery)("#playbuzz_item_settings_width");
					if ( typeof new_width != 'undefined' && new_width.length && new_width.val() != '' && new_width.val() != 'auto' ) {
						shortcode_str += ' width="' + new_width.val() + '"';
					}

					// add "height"
					new_height = (jQuery)("#playbuzz_item_settings_height");
					if ( typeof new_height != 'undefined' && new_height.length && new_height.val() != '' && new_height.val() != 'auto' ) {
						shortcode_str += ' height="' + new_height.val() + '"';
					}

					format = (jQuery)("#playbuzz_item_settings_format");
					if ( typeof format != 'undefined' && format.length && format.val() != '' ) {
						shortcode_str += ' format="' + format.val() + '"';
					}

					// add "margin-top"
					new_margin_top = (jQuery)("#playbuzz_item_settings_margin_top");
					if ( default_or_custom == 'custom' && typeof new_margin_top != 'undefined' && new_margin_top.length && new_margin_top.val() != '' && new_margin_top.val() != '0' && new_margin_top.val() != '0px' && (jQuery)("#playbuzz_item_settings_margin").is( ':checked' ) ) {
						shortcode_str += ' margin-top="' + new_margin_top.val() + '"';
					}

					// End shortcode tag
					shortcode_str += ']';

					// Insert shortcode to the editor
					var id = tinyMCE.activeEditor.selection.getNode().id;
					id !== "" && (jQuery)(tinyMCE.activeEditor.dom.doc.body).find( "#" + id ).parent().remove();
					tinyMCE.activeEditor.selection.setContent( shortcode_str );
					(jQuery)( '.playbuzz_popup_overlay_container' ).remove();

				});

			}

		});

	});

})();
