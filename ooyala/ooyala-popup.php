<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<title>Ooyala Video</title>
<?php wp_print_scripts( array( 'jquery', 'ooyala', 'set-post-thumbnail' ) ); ?>
<?php wp_print_styles( array( 'global', 'media', 'wp-admin', 'colors' ) ); ?>
<script type="text/javascript">
	var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
	var postId = <?php echo absint( $_GET['post_id'] ); ?>;
	var ajax_nonce_ooyala = '<?php echo wp_create_nonce( 'ooyala' ); ?>';
</script>

<script>
	jQuery(document).ready(function () {
		OV.Popup.init();
		OV.Popup.resizePop();
		
		//Make initial reqest to load last few videos
		OV.Popup.ooyalaRequest( 'last_few' );
	});
	jQuery(window).resize(function() {
	  	setTimeout(function ()	{ OV.Popup.resizePop(); }, 50);
	});
</script>
<style>	
	body { min-width:300px !important; }
	.tablenav-pages a { font-weight: normal;}
	.ooyala-item {float:left; height:146px; width:146px; padding:4px; border:1px solid #DFDFDF; margin:4px; box-shadow: 2px 2px 2px #DFDFDF;}
	.ooyala-item .item-title {height: 32px;}
	.ooyala-item .photo { margin:4px; }
	.ooyala-item .photo img { width: 128px; height:72px}
	.ooyala-item .item-title {text-align:center;}
	#latest-link {font-size: 0.6em; padding-left:10px;}
	#ov-content-upload label {display:block}	
</style>
</head>
<body id="media-upload">
	<div id="media-upload-header">
		<ul id="sidemenu" class="ov-tabs">
			<li id="ov-tab-ooyala"><a class="current" href=""><?php _e('Ooyala video','ooyalavideo'); ?></a></li>
			<li id="ov-tab-upload"><a href=""><?php _e('Upload to Ooyala','ooyalavideo'); ?></a></li>
		</ul>
	</div>
	<div class="ov-contents">
		<div id="ov-content-ooyala" class="ov-content">	
		 	<form name="ooyala-requests-form" action="#">
				<p id="media-search" class="search-box">
					<img src="<?php echo $this->plugin_url; ?>img/ooyala_72dpi_dark_sm.png" style="display:block; float:left"/>
					<label class="screen-reader-text" for="media-search-input"><?php _e('Search Keyword', 'ooyala_video');?></label>
					<input type="text" id="ov-search-term" name="ooyalasearch" value="">
					<input type="submit" name=""  id="ov-search-button" class="button" value="Search">
				</p>
				<div id="response-div">
					<h3 class="media-title"><?php _e('Loading...', 'ooyala_video');?></h3>
		      	</div>
		        <table border="0" cellpadding="4" cellspacing="0">

		           <tr>
		            <td nowrap="nowrap" style="text-align:right;"><?php echo _e('Insert video ID:','ooyalavideo'); ?></td>
		            <td>
		              <table border="0" cellspacing="0" cellpadding="0">
		                <tr>
		                  <td><input name="vid" type="text" id="ooyala_vid" value="" style="width: 200px" /></td>
		                </tr>
		              </table></td>
		          </tr>
		          <tr>
		            <td>
			    <input type="submit" id="ooyala-insert" name="insert" value="<?php echo _e('Insert','ooyalavideo'); ?>" />
		            </td>
		            <td align="right"><a href="#close" id="ooyala-close"><?php _e('Cancel', 'ooyala_video');?></td>
		          </tr>
		        </table>
		      <input type="hidden" name="tab" value="portal" />
			</form>
		</div>
		<div id="ov-content-upload" class="ov-content"  style="display:none;margin:1em">
			<h3 class="media-title"><?php _e('Upload to Ooyala', 'ooyalavideo' ); ?></h3>
			<?php
			// Define any default labels to assign and the dynamic label prefix  
			// for any user-selected dynamic labels 

			$param_string = OoyalaBacklotAPI::signed_params(array( 
			  'status' => 'pending',  
			  ));
		?>
	 	<fieldset>
			<script src="http://www.ooyala.com/partner/uploadButton?width=100&amp;height=20&amp;label=<?php echo ( urlencode( esc_attr__('Select File', 'ooyalavideo') ) );?>"></script>
			<script>
			var ooyalaParams = '<?php echo $param_string ?>';
			 function onOoyalaUploaderReady( )  { 
		        try 
		        { 
		          ooyalaUploader.setParameters(ooyalaParams); 
		        } 
		        catch(e) 
		        { 
		          alert(e); 
		        } 

		        ooyalaUploader.addEventListener('fileSelected', 'ooyalaOnFileSelected');  
		        ooyalaUploader.addEventListener('progress', 'ooyalaOnProgress');  
		        ooyalaUploader.addEventListener('complete', 'ooyalaOnUploadComplete');  
		        ooyalaUploader.addEventListener('error', 'ooyalaOnUploadError');  
		
		        document.getElementById('uploadButton').disabled = false;  
		      }
			</script>
		 	<p>
				<label><?php _e('Filename', 'ooyala_video');?></label>
				<input id="ooyala_file_name" size="40" /> 
			</p>
			<p>
				<label for><?php _e('Description', 'ooyala_video');?></label>
	        	<textarea id="ooyala_description" rows="5" cols="40"></textarea>
			</p>		
			<p>
				<button id="uploadButton" onClick="return ooyalaStartUpload();"><?php _e('Upload!', 'ooyala_video');?></button>  <a id="ooyala-status"></a>
			</p>
		</fieldset>
		</div>
	</div>
</div>
</body>
</html>