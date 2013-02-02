<script type="text/javascript">
// set the flow name for this screen here
janrain.settings.capture.flowName = 'plugins';
janrain.settings.capture.screenToRender = 'publicProfile';

<?php include_once('settings.php'); ?>

function janrainCaptureWidgetOnLoad() {
		function handleCaptureLogin(result) {

				if (janrain.settings.capture.responseType == 'token') {
						janrain.capture.ui.modal.close();
				}
		}
		janrain.events.onCaptureSessionFound.addHandler(function(result){
			console.log ("user is logged in");
		});

		janrain.events.onCaptureSessionNotFound.addHandler(function(result){
			console.log ("user is logged out");
		});

		janrain.events.onCaptureLoginSuccess.addHandler(handleCaptureLogin);
		janrain.events.onCaptureRegistrationSuccess.addHandler(handleCaptureLogin);
		
		janrain.capture.ui.start();
}
</script>

<div style="display: none;" id="publicProfile">
		<div class="capture_header">
				<h1>My profile</h1>
		</div>
		<div class="contentBoxWhiteShadow capture_profile_section">
				<div class="capture_profile_pic capture_default inline">
						<div id="profile_pic" class="capture_default">
								{* profilePhotoCustom *}
						</div>
				</div>
				<div class="inline">
						<h1>{* displayName *}</h1>
						{* profileBlurb *}
				</div>
		</div>
		<div class="capture_personal_info capture_profile_section contentBoxWhiteShadow">
				<h2>Personal Info</h2>
						{* name *}
						{* emailAddress *}
						{* phoneNumber *}
						{* address *}
						{* location *}
						{* gender *}
						{* birthdate *}
		</div>
</div>
