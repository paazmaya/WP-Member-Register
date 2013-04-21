<?php
/**
 * Part of Member Register
 * Public registration form (prf) related functions.
 */


/**
 * Public registration. Additional form items.
 */
function mr_prf_register_form ()
{
	$martial_art = ( isset( $_POST['martial_art'] ) ) ? $_POST['martial_art']: '';
	?>
	<p>
		<label for="martial_art"><?php __('Martial Art', 'member-register') ?><br />
			<input type="text" name="martial_art" id="martial_art" class="input" value="<?php echo esc_attr(stripslashes($martial_art)); ?>" size="25" /></label>
	</p>
	<?php
}

/**
 * Public registration. Additional form items validation.
 */
function mr_prf_registration_errors ($errors, $sanitized_user_login, $user_email) {

	if ( empty( $_POST['martial_art'] ) )
		$errors->add( 'martial_art_error', __('You must include a martial art.', 'member-register') );

	return $errors;
}

/**
 * Public registration. Additional form items saving.
 */
function mr_prf_user_register($user_id)
{
	if ( isset( $_POST['martial_art'] ) )
	{
		update_user_meta($user_id, 'martial_art', $_POST['martial_art']);
	}
}