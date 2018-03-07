
function check_auto_setup_key ( )
{
	var key = jQuery('input[name="anvato_plugin_setup[mcp_config_automatic_key]"]').val();
	
	if( typeof key === 'undefined' || key.trim() === '' )
	{
		jQuery('#anv_msg_board > p').html('<strong>Please provide a key for auto setup</strong>');
		jQuery('#anv_msg_board').show();
		
		return false;
	}
	
	return true;
}

/*
 * There is server side sanitization as well, this method is primarily used to display errors.
 */
function validate_hw_fields ( )
{
	var width = jQuery('input[name="anvato_player[width]"]').val();
	var width_type = jQuery('select[name="anvato_player[width_type]"]').val();
	var height = jQuery('input[name="anvato_player[height]"]').val();
	var height_type = jQuery('select[name="anvato_player[height_type]"]').val();
	
	if(jQuery('#setting-error-settings_updated').length)
	{
		jQuery('#setting-error-settings_updated').hide();
	}
	
	if( height == null || isNaN(parseInt(height)) || height.indexOf(".") != -1 ||
			(height_type == '%' && (parseInt(height) > 100 || parseInt(height) < 0)) ||
			(height_type == 'px' && (parseInt(height) > 1000 || parseInt(height) < 100)) )
	{
		jQuery('#anv_msg_board > p').html('<strong>Please provide a valid value for height, valid range:'+
				((height_type == '%') ? '1-100' : '100-1000') + '</strong>' );
		jQuery('#anv_msg_board').show();
		
		return false;
	}
	
	if( width == null || isNaN(parseInt(width)) || width.indexOf(".") != -1 ||
			(width_type == '%' && (parseInt(width) > 100 || parseInt(width) < 0)) ||
			(width_type == 'px' && (parseInt(width) > 1000 || parseInt(width) < 100)) )
	{
		jQuery('#anv_msg_board > p').html('<strong>Please provide a valid value for width, valid range:'+
				((width_type == '%') ? '1-100' : '100-1000') + '</strong>' );
		jQuery('#anv_msg_board').show();
		return false;
	}
	
	return true;
}