( function( $ ){
	Shopify.getShopifySettings = function(){
		var address = $( "#shopify_url" ).val().replace( "https://", "" ).replace( "http://", "" ).split( "." )[0];
		$.ajax({
			url: "//" + address + ".myshopify.com/meta.json",
			dataType: "jsonp",
			success: function( json ){
				if( $( "#shopify_setup" ).val() === "false") {
					window.open( "https://wordpress-shortcode-generator.shopifyapps.com/login?shop=" + json.myshopify_domain + "&wordpress_admin_url=" + $( "#signin" ).data( "wordpressdomain" ) );
				}
				$( "#shopify_myshopify_domain" ).val( json.myshopify_domain );
				$( "#shopify_primary_shopify_domain" ).val( json.domain );
				$( "#shopify_money_format" ).val( json.money_format );
				$( "#shopify_setup" ).val( "true" );
				$( "#shopify_settings_form" ).submit();
			}})
		.fail( function(){
			$( "#shopify_error" ).append( "We're sorry, but something went wrong. Please try again or contact <a href='http://docs.shopify.com/support'>Shopify Support</a>" );
		});
	};

	Shopify.hideTip = function(){
		var d = new Date();
		d.setTime( d.getTime() + ( 5*365*24*60*60*1000 ) ); //5 year cookie
		var expires = "expires=" + d.toGMTString();
		$( "#shopify_getting_started" ).fadeOut();
		document.cookie = "shopifyHideTip=true; " + expires;
	};

	Shopify.updateWidgetPreview = function( element ){
		var widget_id = $( "#widget_preview form" ).attr( "id" );
		var widget = Shopify.allWidgets[widget_id];
		switch( element.id ) {
			case "shopify_text_color":
				if ( widget.widget_container.hasClass( "centered" )) {
					widget.widget_container.css( "color", element.value );
				} else {
					widget.widget_container.find( ".widget_price" ).css( "color", element.value );
				}
				break;
			case "shopify_button_text_color":
				widget.widget_container.find( ".widget_buttons input[type='submit']" ).css( "color", element.value );
				break;
			case "shopify_button_background":
				widget.widget_container.find( ".widget_buttons input[type='submit']" ).css( "background", element.value );
				break;
			case "shopify_button_text":
				widget.widget_container.find( ".widget_buttons input[type='submit']" ).val( element.value );
				break;
			case "shopify_background_color":
				widget.widget_container.css( "background", element.value );
				break;
			case "shopify_border_color":
				widget.widget_container.css( "border", element.value + " 1px solid" );
				break;
			case "shopify_border_padding":
				widget.widget_container.css( "padding", element.value );
				break;
			case "shopify_style":
				widget.widget_container.removeClass( "simple centered" ).addClass( element.value );
				break;
			case "shopify_image_size":
				widget.widget_container.removeClass( "small medium large grande" ).addClass( element.value );
				widget.size = element.value;
				widget.updateImage();
				break;
			case "shopify_money_format":
				widget.money_format = element.value;
				widget.updateWidget();
				break;
			case "shopify_destination":
				var button = "";
				var button_text = $( "#shopify_button_text" ).attr( "value" );
				if( element.value === "cart" ){
					button = "<input type='hidden' class='selected_variant' name='id' value=''/> <input type='submit' class='widget_buy_button' value='" + button_text + "' target='#'/>";
				} else {
					button = "<input type='hidden' name='return_to' value='/checkout'/><input type='submit' class='widget_buy_button' value='" + button_text + "' target='#' onclick='Shopify.allWidgets." + widget_id + ".buyNow();return false;'/>";
				}
				widget.widget_container.find( ".destination" ).attr( "value", element.value );
				widget.widget_container.find( ".widget_buttons" ).empty().append( button );
				widget.updateVisablePrice(); //set id posted by form properly
				break;
			default:
				break;
		}
	};

	$( function(){
		if( location.search.indexOf( "shopify_menu" ) !== -1) {
			if( $( "#shopify_setup" ).val() === "true" ){
				if( document.cookie.indexOf( "shopifyHideTip" ) === -1 ){
					$( "#store_link" ).attr("href", "http://" + $( "#shopify_primary_shopify_domain").val() + "/collections/all" );
					$( "#shopify_getting_started" ).show();
				}

				$( "#shopify_settings_form" ).keyup( function( e ) {
					var code = e.keyCode || e.which;
					if ( code == 13 ) {
						e.preventDefault();
						Shopify.getShopifySettings();
						return false;
					}});

				if( location.search.indexOf( "settings-updated=true" ) !== -1 ){
					$( "#flash" ).show().append( "Settings updated" );
					setTimeout( function(){$( "#flash" ).fadeOut();}, 5000 );
				}

				$( "#shopify_settings_form" ).bind( "change keyup", function( e ){
					Shopify.updateWidgetPreview( e.target );
				});

				$( ".color-picker" ).iris({
					hide: true,
					palettes: true,
					size: 140,
					change: function( event, ui ) {
						Shopify.updateWidgetPreview( event.target );
					}
				});
				$( ".color-picker" ).click( function( event ){
					$( ".color-picker" ).iris( "hide" );
					$( event.target ).iris( "show" );
				});
			} else {
				$( "#shopify_url" ).keyup( function( e ) {
					var code = e.keyCode || e.which;
					if ( code == 13 ) {
						e.preventDefault();
						Shopify.getShopifySettings();
						return false;
					}});
				$( "#shopify_nag" ).hide();
			}
		}
	});
})( jQuery );

