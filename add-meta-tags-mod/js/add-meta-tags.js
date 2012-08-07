(function($) {

counter = {
	init : function() {
		var t = this,
		    $title = $('#mt_seo_title'),
		    $desc = $('#mt_seo_description');

		t.buildCounter( $title, 70, 'title' )
		t.buildCounter( $desc, 140, '<code>meta</code> description' );

		$title.keyup( function() {
			t.updateTitle();
		});
		$desc.keyup( function() {
			t.updateDesc();
		});

		$title.live('change', function() {
			t.updateTitle();
		});
		$desc.live('change', function() {
			t.updateDesc();
		});
	},

	buildCounter : function( el, count, desc ) {
		var t = this,
		    $counter = $( "<div class='mt_counter' data-limit="+count+" />" );

		el.after( $counter );
		$counter.html( "The " + desc + " is limited to " + count + " characters in search engines. <span class='count'>"+count+"</span> characters remaining." );
		t.updateTitle();
		t.updateDesc();
	},

	updateTitle : function() {
		var t = this,
		    $title = $('#mt_seo_title'),
		    count = $title.val().replace('%title%', originalTitle).length,
		    limit = $title.attr('data-limit') || 70,
		    originalTitle = $('#title').val();

		$title.siblings( '.mt_counter' ).find( '.count' ).replaceWith( t.updateCounter( count, limit ) );
		$("#mt_snippet .title").html( $title.val().replace('%title%', originalTitle).substring(0, limit) );
	},

	updateDesc : function() {
		var t = this,
		    $desc = $('#mt_seo_description'),
		    count = $desc.val().length,
		    limit = $desc.attr('data-limit') || 140;

		$desc.siblings( '.mt_counter' ).find( '.count' ).replaceWith( t.updateCounter( count, limit ) );
		$('#mt_snippet .content').html( $desc.val().substring(0, limit) );
	},

	updateCounter : function( count, limit ) {
		var $counter = $( '<span class="count" />' ),
		    left = limit - count;

		$counter.text( left );

		if( left > 0 )
			$counter.removeClass( 'negative' ).addClass( 'positive' );
		else
			$counter.removeClass( 'postive' ).addClass( 'negative' );

		return $('<b>').append( $counter ).html();
	}
};

$(document).ready(function(){counter.init();});
})(jQuery);