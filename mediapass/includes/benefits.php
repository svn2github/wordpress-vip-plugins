<div class="wrap">
	<h2 class="header"><img src="<?php echo plugins_url('/images/logo-icon.png', dirname(__FILE__)) ?>" class="mp-icon" /><span>Customize Logo and Benefits</span></h2>
	
	<h3>Logo</h3>
	
	<p>Upload a logo to customize the look and feel of your Page Overlay and Video Overlay subscription options. Your logo must be a jpg. See examples below.</p>
	
	<form name="benefits" action="" method="post" accept-charset="utf-8" id="benefits-form">
		<?php wp_nonce_field('mp-nonce'); ?>
		<br/><br/>
		 <table width="100%" border="0" cellpadding="0" cellspacing="0" id="logo_benefits_options">
          	<colgroup>
               	<col class="first_column" />
               </colgroup>
               <tr valign="top">
          	     <th valign="top" scope="row">Upload Image</th>
          	     <td valign="top"><label for="upload_image">
          	          <input id="upload_image" type="text" size="36" name="upload_image" value="" />
          	          <input id="upload_image_button" type="button" value="Upload Image" />
          	          <p>Enter URL or upload an image for the logo.</p>
                         </label></td>
     	     </tr>
          	<tr>
          	     <th valign="top">Current Logo</th>
          	     <td valign="top">
          	     	<?php if (!empty($data['Msg']['logo']) && $data['Msg']['logo'] != 'logo is not saved.'): ?>
						<img src="<?php echo esc_url( $data['Msg']['logo'] ); ?>" />
					<?php else: ?>
						None
					<?php endif ?>
          	     </td>
     	     </tr>
     	     	<tr>
          	     <td valign="top">
                    	<div id="logo_arrow">
                              <img src="<?php echo plugins_url('/images/arrow.png', dirname(__FILE__))?>" alt="Arrow" name="logo_arrow" style="float:right;" />
                              <strong>
                                   Your logo on
                                   <br/>
                                   Page Overlay option
                              </strong>
                         </div>
                     </td>
          	     <td valign="top"><img src="<?php echo plugins_url('/images/overlay.jpg', dirname(__FILE__))?>" alt="Example logo on Page Overlay option" width="751" height="454" class="shadow"></td>
     	     </tr>
          	<tr>
          	     <td valign="top">&nbsp;</td>
          	     <td valign="top">&nbsp;</td>
     	     </tr>
          	<tr>
          	     <td colspan="2" valign="top"><h3>Benefits</h3><p>Customize your messaging by marketing the benefits of becoming a premium subscriber. Enter the text to be displayed to your users in the Member Benefits section of the subscription option. See examples below.</p></td>
     	     </tr>
          	<tr>
          	     <td valign="top"><p class="tip">TIP: Benefits are displayed as bullet points, so separate each benefit on a new line in the text box.</p></td>
          	     <td valign="top">
                    	<div style="float:left;">
                         	<textarea name="benefits" rows="8" cols="100" style="margin-bottom:5px;"><?php if(!empty($data['Msg']['benefit'])) { echo esc_textarea( $data['Msg']['benefit'] ); }?></textarea>
                              <br>
          	          	<p style="float:right;margin-top:0;">
                              	<b>1000</b> characters remaining in your input limit. <!-- add character limit/count back here -->
                               </p>
          	          	<input type="submit" value="Update">                         
                         </div>
          	     </td>
          	</tr>
          	<tr>
          	     <td valign="top">&nbsp;</td>
          	     <td valign="top">&nbsp;</td>
     	     </tr>
          	<tr>
                    <td valign="top">
                         <div id="benefits_arrow"> 
                              <img src="<?php echo plugins_url('/images/arrow.png', dirname(__FILE__))?>" alt="" name="benefits_arrow" style="float:right;" />
                              <strong>Benefits Text <br>
						on In-Page option</strong>
                         </div>
                    </td>
          	     <td valign="top"><img src="<?php echo plugins_url('/images/inpage.jpg', dirname(__FILE__))?>" alt="Example of benefits on In-Page option" width="609" height="471" class="shadow"></td>
     	     </tr>
          </table>
<hr/>
	</form>
		</div>

<script type="text/javascript" charset="utf-8">
	fieldlimiter.setup({
	    thefield: document.benefits.benefits, //reference to form field
	    maxlength: 1000,
	    statusids: ["fieldlimiter_status"], //id(s) of divs to output characters limit in the form [id1, id2, etc]. If non, set to empty array [].
	    onkeypress: function(maxlength, curlength) { //onkeypress event handler
	        //define custom event actions here if desired
	    }
	})
</script>