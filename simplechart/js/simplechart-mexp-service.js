var mexpController = wp.media.controller.MEXP;

wp.media.controller.MEXP = mexpController.extend({
	mexpInsert : function(){
		// leave Media Explorer panels other than Simplechart unaffected
		if ( this.get('id').indexOf('simplechart_mexp_service') === -1 ) {
			mexpController.prototype.mexpInsert.apply( this );
			return;
		}

		/*
		 * replicate existing function except with shortcodes intead of URLs
		 */
		var selection = this.frame.content.get().getSelection(),
		shortcodes = [];

		selection.each( function( model ) {
			shortcodes.push( '[simplechart id="' + model.get( 'id' ) + '"]' );
		}, this );

		if ( typeof(tinymce) === 'undefined' || tinymce.activeEditor === null || tinymce.activeEditor.isHidden() ) {
			wp.media.editor.insert( _.toArray( shortcodes ).join( "\n\n" ) );
		} else {
			wp.media.editor.insert( "<p>" + _.toArray( shortcodes ).join( "</p><p>" ) + "</p>" );
		}

		selection.reset();
		this.frame.close();

	}
});