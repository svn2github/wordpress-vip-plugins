jQuery( function($) {
	$('#daylife-search').keypress( function( event ) {
		if ( 13 == event.which ) {
			event.preventDefault();
			$('#daylife-search-button').click();
		}
	});
	$('#daylife-search-button').click( function() {
		var data = {
			action: 'daylife-image-search',
			nonce: $('#daylife-search-nonce-field').val(),
			keyword: $('#daylife-search').val()
		};
		$.post(ajaxurl, data, function(response) {
			daylifeUpdateImages(response);
		});

		return false;
	});
	$('#daylife-suggest-button').click( function() {
		 $('#daylife-search').val('');
		var data = {
			action: 'daylife-image-search',
			nonce: $('#daylife-search-nonce-field').val(),
			content: $('#content').val()
		};
		$.post(ajaxurl, data, function(response) {
			daylifeUpdateImages(response);
		});

		return false;
	});
	function daylifeUpdateImages(response) {
		$('.daylife-response').show();
		$('.daylife-response').html( response );
		$('.daylife-response button').click( function() {
			var img = $(this).siblings( 'img' );
			var data = {
				action: 'daylife-image-load',
				nonce: $('#daylife-add-nonce-field').val(),
				daylife_url: img.attr( 'daylife_url' ),
				caption: img.attr( 'caption' ),
				credit: img.attr( 'credit' ),
				image_title: img.attr( 'image_title' ),
				thumb_url: img.attr( 'thumb_url' ),
				url: img.attr( 'url' ),
				post_id: $('#post_ID').val()
			}
			$.post(ajaxurl, data, function(response) {
				send_to_editor(response);
			});
			return false;
		});		
		$('#daylife-paging').bind( 'click', function() {
			var data = {
				action: 'daylife-image-search',
				nonce: $('#daylife-search-nonce-field').val(),
				daylife_page: $(this).attr('href').substring(1)
			};
			if ( 0 == $('#daylife-search').val().length )
				data.content = $('#content').val();
			else
				data.keyword = $('#daylife-search').val();
			$.post(ajaxurl, data, function(response) {
				daylifeUpdateImages(response);
			});
		});
	}
});
