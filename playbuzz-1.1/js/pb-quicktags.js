(function () {

	function generate_id() {
		return Math.round( Math.random() * 1000000 );
	}

	function playbuzz_shortcode_generator() {

		var search = new playbuzz_search();
		search.display( playbuzz_generate_shortcode );

		function playbuzz_generate_shortcode(itemId) {
			var id = generate_id();
			var $playbuzz_item_shortcode = '[playbuzz-item item="' + itemId + '" wp-pb-id="' + id + '"]\n';
			QTags.insertContent( $playbuzz_item_shortcode );
		}
	}

	QTags.addButton( 'playbuzz-shortcode', 'Playbuzz', playbuzz_shortcode_generator );
})();
