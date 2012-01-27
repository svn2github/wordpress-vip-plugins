jQuery(function($){
	$('.row-actions .sf_publish a').click(function(e){
		var $the_a = $(this);
		var the_title = prompt( sf_l10n['prompt'], $the_a.parents('td').find('.row-title').html() );
		if ( the_title != null && the_title != "" ) {
			$the_a.attr( 'href', $the_a.attr('href') + '&sf_text=' + the_title );
			return true;
		} else {
			return false;
		}
	});
});