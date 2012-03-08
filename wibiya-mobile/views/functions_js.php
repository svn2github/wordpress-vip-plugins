<script type="text/javascript" >
function changePreview(obj)
{
	if(obj.className=='apple')
	{
		obj.className='android';
		livepreview.location.href='<?php echo __LIVEPREVIEW_URI__."&device=android";?>';
		objPrev = document.getElementsByClassName('sitePreview iphone');
		objPrev[0].className='sitePreview android';
	}
	else{
		obj.className='apple';
		livepreview.location.href='<?php echo __LIVEPREVIEW_URI__."&device=iphone";?>';
		objPrev = document.getElementsByClassName('sitePreview android');
		objPrev[0].className='sitePreview iphone';
		
	}
}
function changeSecondaryEmail()
{
	if(document.getElementById('secondary_email').value!=''&&document.getElementById('secondary_email').value!=null&&document.getElementById('secondary_email').value!=undefined)
	{	
		url = document.location.href;
		pos = url.indexOf('&secondary_email=');
		if(pos==-1){ document.location.href=document.location.href+'&secondary_email='+document.getElementById('secondary_email').value;  }
		else{
			document.location.href=url.substr(0, pos)+'&secondary_email='+document.getElementById('secondary_email').value;
		}
	}
}
</script>