/*
 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 
 */

// CONTROLLER OVERIDE:

media.controller.MEXP = media.controller.State.extend({

	initialize: function(options)
	{
		this.props = new Backbone.Collection();
		for (var tab in options.tabs)
		{
			this.props.add(new Backbone.Model({
				id: tab,
				params: {},
				page: null,
				min_id: null,
				max_id: null,
				fetchOnRender: options.tabs[ tab ].fetchOnRender
			}));
		}

		this.props.add(new Backbone.Model({
			id: '_all',
			selection: new Backbone.Collection()
		}));

		this.props.on('change:selection', this.refresh, this);
	},
	refresh: function()
	{
		this.frame.toolbar.get().refresh();
                //this.attachEvents();
	},
	mexpInsert: function()
	{
		media.editor.insert(this.attachItems());

		this.frame.close();
	},
	attachItems : function ( )
	{
		var selection = this.frame.content.get().getSelection(),
		urls = [], video_ids = [];
		
		var multi = (selection.length > 1) ? true : false;
		var meta;
		selection.each(function(model)
		{
			meta = model.get('meta');
			if( meta['type'] !== 'playlist' )
			{
				if(multi)
				{
					video_ids.push(model.get('id'));
				}else if(jQuery('#dfp_flag_'+model.get('id')).attr('checked'))
				{
					urls = ['[anvplayer video="' + model.get('id') + '"  no_pr="true" station="'+meta['station']+'" ]'];
				} else
				{
					urls.push(model.get('url'));
				}
			}else
			{
				urls.push(model.get('url'));
			}
		});
		if(video_ids.length)
		{
			urls = ['[anvplayer video="' + video_ids.join(',') + '" station="'+meta['station']+'"]'];
		}
		selection.reset();

		if (typeof(tinymce) === 'undefined' || tinymce.activeEditor === null || tinymce.activeEditor.isHidden())
		{
			return _.toArray(urls).join("\n\n");
		}

		return "<p>" + _.toArray(urls).join("</p><p>") + "</p>";
		
	}        
});

/*
 * media.view.MEXP prototype extension: adding method toggleDFPPreroll
 * and triggered in toggleSelectionHandler.
 */
media.view.MEXP.prototype.toggleSelectionHandler = function ( event )
{
	if ( event.target.href )
		return;

	var target = jQuery( '#' + event.currentTarget.id );
	var id     = target.attr( 'data-id' );

	if ( this.getSelection().get( id ) )
	{
		this.removeFromSelection( target, id );
	}
	else
	{
		this.addToSelection( target, id );
	}
	
	this.toggleDFPPreroll( event, id );
}

media.view.MEXP.prototype.toggleDFPPreroll = function( event, vid_id )
{
	
	var selection = this.getSelection();
	
	if(this.getSelection().get( vid_id ) == null)
	{
		jQuery( '#dfp_flag_'+vid_id ).attr('checked',false);
	}
	
	if(selection.length > 1)
	{
		jQuery( '.mexp-item-meta-dfp-flags' ).find('input').attr('disabled', 'disabled');
		jQuery( '.mexp-item-meta-dfp-flags' ).addClass('anv-disabled');
	} else
	{
		jQuery( '.mexp-item-meta-dfp-flags' ).find('input').removeAttr('disabled');
		jQuery( '.mexp-item-meta-dfp-flags' ).removeClass('anv-disabled');
	}
}

media.view.MEXP.prototype.fetchedEmpty = function( response )
{

	if ( !this.model.get( 'page' ) )
	{
		this.$el.find( '.mexp-empty' ).text( this.service.labels.noresults ).show();
	}
	this.$el.find( '.mexp-pagination' ).hide();

	this.trigger( 'loaded loaded:noresults', response );
	
	jQuery( '#' + this.service.id + '-loadmore' ).attr( 'disabled', true ).hide();
}

/*
 * This method is overwritten to enable the mexp-button after being disabled in updateInput 
 */
media.view.MEXP.prototype.loaded = function( response )
{
	// hide spinner
	this.$el.find( '.spinner' ).hide();
	jQuery( '#mexp-button' ).removeAttr( 'disabled' );
}

/*
 * This method is overridden because there is an error in mexp view that calls moreEmpty() nonexisting method
 */
media.view.MEXP.prototype.fetchedSuccess = function( response )
{

	if ( !this.model.get( 'page' ) )
	{

		if ( !response.items )
		{
			this.fetchedEmpty( response );
			return;
		}

		this.model.set( 'min_id', response.meta.min_id );
		this.model.set( 'items',  response.items );

		this.collection.reset( response.items );

	} else
	{

		if ( !response.items )
		{
			this.fetchedEmpty( response );//Anvato change...
			return;
		}

		this.model.set( 'items', this.model.get( 'items' ).concat( response.items ) );

		var collection = new Backbone.Collection( response.items );
		var container  = document.createDocumentFragment();

		this.collection.add( collection.models );

		collection.each( function( model ) {
			container.appendChild( this.renderItem( model ) );
		}, this );

		this.$el.find( '.mexp-items' ).append( container );

	}

	jQuery( '#' + this.service.id + '-loadmore' ).attr( 'disabled', false ).show();
	this.model.set( 'max_id', response.meta.max_id );

	this.trigger( 'loaded loaded:success', response );

}

function anv_preview(mcp_id, video_id, type, accesskey)
{
	var ptype = type === 'video' || type === 'live' ? 'video' : 'playlist';
	var player_js_url = "http://qa.up.anv.bz/dev/scripts/anvload.js";
	var script = jQuery("<script src='"+player_js_url+"'></script>");
	if(accesskey)
	{
		script.attr("data-anvp", '{\"mcp\":\"' + mcp_id + '\", \"accessKey\":\"'+ accesskey +'\", \"pInstance\": \"anv_preview_cont\", \"'+ptype+'\":\"' + video_id + '\", \"autoplay\": \"true\", \"token\":\"default\"}');
	}
	else
	{
		script.attr("data-anvp", '{\"mcp\":\"' + mcp_id + '\", \"pInstance\": \"anv_preview_cont\", \"'+ptype+'\":\"' + video_id + '\", \"autoplay\": \"true\"}');
	}

	var div = jQuery("<div class='anv_preview'><div id=\"anv_preview_cont\"></div><a class=\"anv_preview_close\" href='Javascript://' onclick=\"anv_preview_close()\"></a></div>");
	div.append(script).insertBefore('.mexp-content-wp_anvato > .mexp-items');
	jQuery('.mexp-content-wp_anvato > .mexp-items').css('opacity', 0.25);
}

function anv_preview_close()
{
	jQuery('.anv_preview').remove();
	jQuery('.mexp-content-wp_anvato > .mexp-items').css('opacity', 1);
}

function anv_type_select(el)
{
	if ( el.value === 'vod')
	{
		window.anv_playlist_enabled = true;
	}
	else
	{
		window.anv_playlist_enabled = false;
	}
}

window.anv_playlist_enabled = true;