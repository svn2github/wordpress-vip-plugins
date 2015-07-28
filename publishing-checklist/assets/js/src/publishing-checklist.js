(function($){

	$('#the-list').on('click', '.publishing-checklist-show-list', showChecklist );

	$('#the-list').on('click', '.publishing-checklist-hide-list', hideChecklist );

	$('.misc-pub-section').on('click', '.publishing-checklist-show-list', showChecklist );

	$('.misc-pub-section').on('click', '.publishing-checklist-hide-list', hideChecklist );

	function showChecklist(event) {
		$( '.publishing-checklist-items', $(this).parent() ).show();
		$(this).hide();
		$( '.publishing-checklist-hide-list', $(this).parent() ).show();
	}
	function hideChecklist(event) {
		$( '.publishing-checklist-items', $(this).parent() ).hide();
		$(this).hide();
		$( '.publishing-checklist-show-list', $(this).parent() ).show();
	}

}(jQuery));

