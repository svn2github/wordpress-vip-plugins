<?php
/**
 * Settings page template
 *
 * @package Civil_Comments
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

if ( isset( $_GET['settings-updated'] ) ) { // Input var okay.
	add_settings_error( 'civil_comments_messages', 'civil_comments_message', __( 'Settings Saved', 'civil-comments' ), 'updated' );
}

settings_errors( 'civil_comments_messages' );
?>
<div class="wrap">
	<a href="https://app.civilcomments.com/" style="float:right; margin-right: 0.6em; margin-top:0.2em;">
		<img src="<?php echo esc_url( CIVIL_PLUGIN_URL . '/assets/img/logo.png' ); ?>" alt="<?php esc_attr_e( 'Civil Comments Logo', 'civil-comments' ); ?>" height="40" width="75">
	</a>
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form action="options.php" method="post">
		<?php
		settings_fields( 'civil_comments' );
		$settings = Civil_Comments\get_settings( 'civil_comments' );
		$enable = isset( $settings['enable'] ) ? (bool) $settings['enable'] : false;
		$hide = isset( $settings['hide'] ) ? (bool) $settings['hide'] : false;
		$publication_slug = isset( $settings['publication_slug'] ) ? $settings['publication_slug'] : '';
		$lang = isset( $settings['lang'] ) ? $settings['lang'] : get_locale();
		$start_date = isset( $settings['start_date'] ) ? $settings['start_date'] : '';
		$enable_sso = isset( $settings['enable_sso'] ) ? (bool) $settings['enable_sso'] : false;
		$sso_secret = isset( $settings['sso_secret'] ) ? $settings['sso_secret'] : '';
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<h2><?php esc_html_e( 'General', 'civil-comments' ); ?></h2>
				</th>
			</tr>
			<tr>
				<th scope="row">
					<label for="cc-enable"><?php esc_html_e( 'Enable Civil Comments', 'civil-comments' ); ?></label>
				</th>
				<td>
					<input type="checkbox" name="civil_comments[enable]" id="cc-enable" value="1" <?php checked( true, $enable ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="cc-publication-slug">
						<?php esc_html_e( 'Publication Slug', 'civil-comments' ); ?>
					</label>
				</th>
				<td>
					<input type="text" id="cc-publication-slug" name="civil_comments[publication_slug]" class="regular-text" value="<?php echo esc_attr( $publication_slug ); ?>">
					<p class="description"><?php esc_html_e( 'The unique ID for your site from Civil Comments.', 'civil-comments' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="cc-lang">
						<?php esc_html_e( 'Language', 'civil-comments' ); ?>
					</label>
				</th>
				<td>
					<?php
					$languages = get_available_languages();
					if ( ! is_multisite() && defined( 'WPLANG' ) && '' !== WPLANG && 'en_US' !== WPLANG && ! in_array( WPLANG, $languages, true ) ) {
						$languages[] = WPLANG;
					}

					if ( ! empty( $languages ) ) {
						$locale = $lang;
						if ( ! in_array( $locale, $languages, true ) ) {
							$locale = '';
						}

						wp_dropdown_languages( array(
							'name'                        => 'civil_comments[lang]',
							'id'                          => 'cc-lang',
							'selected'                    => $locale,
							'languages'                   => $languages,
							'show_available_translations' => false,
						) );
					} else { ?>
						<select name="civil_comments[lang]" id="cc-lang">
							<option value="en_US" selected>English (United States)</option>
						</select>
					<?php } ?>
						<p class="description"><?php esc_html_e( 'Choose from your installed languages. Defaults to your currently selected language.', 'civil-comments' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="js-start-date"><?php esc_html_e( 'Start Date', 'civil-comments' ); ?></label>
					</th>
					<td>
						<input type="text" name="civil_comments[start_date]" id="js-start-date" class="regular-text" value="<?php echo esc_attr( $start_date ); ?>">
						<p class="description"><?php esc_html_e( 'If left blank, civil comments will apply to all existing and future posts. If a date and time are provided, Civil will only be applied to posts created after that date.', 'civil-comments' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cc-hide"><?php esc_html_e( 'Hide Comments Until Clicked', 'civil-comments' ); ?></label>
					</th>
					<td>
						<input type="checkbox" name="civil_comments[hide]" id="cc-hide" value="1" <?php checked( true, $hide ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<h2><?php esc_html_e( 'Single Sign-On', 'civil-comments' ); ?></h2>
					</th>
				</tr>
				<tr>
					<th scope="row">
						<label for="cc-enable-sso"><?php esc_html_e( 'Enable Single Sign-On', 'civil-comments' ); ?></label>
					</th>
					<td>
						<input type="checkbox" name="civil_comments[enable_sso]" id="cc-enable-sso" value="1" <?php checked( true, $enable_sso ); ?>>
						<p class="description"><?php esc_html_e( 'Optional. Site owners can replace Civil\'s built-in login options with their site\'s existing account management system. Requires SSO to be enabled in the Civil Comments admin.', 'civil-comments' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="cc-sso-secret">
							<?php esc_html_e( 'SSO Secret', 'civil-comments' ); ?>
						</label>
					</th>
					<td>
						<input type="text" id="cc-sso-secret" name="civil_comments[sso_secret]" class="regular-text" value="<?php echo esc_attr( $sso_secret ); ?>">
						<p class="description"><?php esc_html_e( 'Secret key from Civil Comments admin.', 'civil-comments' ); ?></p>
					</td>
				</tr>
		</table>

		<?php submit_button( __( 'Save Settings', 'civil-comments' ) ); ?>
	</form>
</div>
