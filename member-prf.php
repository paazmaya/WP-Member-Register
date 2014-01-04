<?php
/**
 * Part of Member Register
 * License: MIT (http://opensource.org/licenses/MIT)
 *
 * Public registration form (prf) related functions.
 */


/**
 * Public registration. Additional form items.
 */
function mr_prf_register_form ()
{
	global $wpdb;
	global $mr_martial_arts;

	$values = array(
		'firstname' => isset($_POST['firstname']) ? mr_htmlent($_POST['firstname']) : '',
		'lastname' => isset($_POST['lastname']) ? mr_htmlent($_POST['lastname']) : '',
		'birthdate' => isset($_POST['birthdate']) ? mr_htmlent($_POST['birthdate']) : '',
		'address' => isset($_POST['address']) ? mr_htmlent($_POST['address']) : '',
		'zipcode' => isset($_POST['zipcode']) ? mr_htmlent($_POST['zipcode']) : '',
		'postal' => isset($_POST['postal']) ? mr_htmlent($_POST['postal']) : '',
		'phone' => isset($_POST['phone']) ? mr_htmlent($_POST['phone']) : '',
		'nationality' => isset($_POST['nationality']) ? mr_htmlent($_POST['nationality']) : '',
		'martial' => isset($_POST['martial']) ? mr_htmlent($_POST['martial']) : '',
		'club' => isset($_POST['club']) ? intval($_POST['club']) : -1
	);

	?>
	<p>
		<label><?php echo __('Etunimi', 'member-register'); ?><br />
			<input type="text" name="firstname" class="required" required="required" value="<?php echo $values['firstname']; ?>" />
		</label>
	</p>
	<p>
		<label><?php echo __('Last name', 'member-register'); ?><br />
			<input type="text" name="lastname" class="required" required="required" value="<?php echo $values['lastname']; ?>" />
		</label>
	</p>
	<p>
		<label><?php echo __('Birthday', 'member-register'); ?> <span class="description">(<?php echo __('muotoa YYYY-MM-DD, esim 1950-12-31', 'member-register'); ?>)</span><br />
			<input type="text" name="birthdate" class="pickday required" required="required" value="<?php echo $values['birthdate']; ?>" />
			<!--  min="1900-01-01" max="<?php echo date('Y-m-d', time() - 60*60*24*365); ?>"  -->
		</label>
	</p>
	<p>
		<label><?php echo __('Postiosoite', 'member-register'); ?><br />
			<input type="text" name="address" class="required" required="required" value="<?php echo $values['address']; ?>" />
		</label>
	</p>
	<p>
		<label><?php echo __('Postinumero', 'member-register'); ?><br />
			<input type="text" name="zipcode" class="required" required="required" value="<?php echo $values['zipcode']; ?>" list="zipcodes" />
		</label>
	</p>
	<p>
		<label><?php echo __('Postitoimipaikka', 'member-register'); ?><br />
			<input type="text" name="postal" class="required" required="required" value="<?php echo $values['postal']; ?>" list="postals" />
		</label>
	</p>
	<p>
		<label><?php echo __('Puhelinnumero', 'member-register'); ?><br />
			<input type="text" name="phone" class="required" required="required" value="<?php echo $values['phone']; ?>" />
		</label>
	</p>
	<p>
		<label><?php echo __('Nationality', 'member-register'); ?><br />
			<select name="nationality" class="required" required="required" data-placeholder="Valitse kansallisuus">
		<option value=""></option>
		<?php
		$sql = 'SELECT code, name FROM ' . $wpdb->prefix . 'mr_country ORDER BY name ASC';
		$countries = $wpdb->get_results($sql, ARRAY_A);
		foreach($countries as $cnt)
		{
			echo '<option value="' . $cnt['code']. '"';
			if ($cnt['code'] == $values['nationality'])
			{
				echo ' selected="selected"';
			}
			echo '>' . $cnt['name'] . '</option>';
		}
		?>
		</select>
		</label>
	</p>
	<p>
		<label><?php echo __('Main martial art', 'member-register'); ?><br />
			<select name="martial" class="required" required="required" data-placeholder="Valitse päälaji">
				<option value=""></option>
				<?php
				foreach ($mr_martial_arts as $k => $v)
				{
					echo '<option value="' . $k . '"';
					if ($values['martial'] == $k)
					{
						echo ' selected="selected"';
					}
					echo '>' . $v . ' (' . $k . ')</option>';
				}
				?>
			</select>
		</label>
	</p>
	<p>
		<label><?php echo __('Seura', 'member-register'); ?> <span class="description">(<?php echo __('ei pakollinen', 'member-register'); ?>)</span><br />
			<select name="club" data-placeholder="Valitse seura">
				<option value=""></option>
				<?php
				$clubs = mr_get_list('club', 'visible = 1', '', 'title ASC');
				foreach($clubs as $club)
				{
					echo '<option value="' . $club['id'] . '"';
					if ($values['club'] == $club['id'])
					{
						echo ' selected="selected"';
					}
					echo '>' . $club['title'] . '</option>';
				}
				?>
			</select>
		</label>
	</p>

	<datalist id="postals">
		<?php
		$sql = 'SELECT DISTINCT postal FROM ' . $wpdb->prefix . 'mr_member WHERE visible = 1 ORDER BY postal ASC';
		$results = $wpdb->get_results($sql, ARRAY_A);
		foreach ($results as $res)
		{
			echo '<option value="' . $res['postal'] . '" />';
		}
		?>
	</datalist>
	<datalist id="zipcodes">
		<?php
		$sql = 'SELECT DISTINCT zipcode FROM ' . $wpdb->prefix . 'mr_member WHERE visible = 1 ORDER BY zipcode ASC';
		$results = $wpdb->get_results($sql, ARRAY_A);
		foreach ($results as $res)
		{
			echo '<option value="' . $res['zipcode'] . '" />';
		}
		?>
	</datalist>
	<?php
}

/**
 * Public registration. Additional form items validation.
 */
function mr_prf_registration_errors ($errors, $sanitized_user_login, $user_email)
{
	global $mr_martial_arts;

	$values = array(
		'firstname' => __('First name should not be empty', 'member-register'),
		'lastname' => __('Last name should not be empty', 'member-register'),
		'birthdate' => __('Birthdate should not be empty', 'member-register'),
		'address' => __('Address should not be empty', 'member-register'),
		'zipcode' => __('Zip code should not be empty', 'member-register'),
		'postal' => __('Post region should not be empty', 'member-register'),
		'phone' => __('Phone number should not be empty', 'member-register'),
		'nationality' => __('Nationality should be selected', 'member-register'),
		'martial' => __('Main martial art should be selected', 'member-register')
	);

	foreach ($values as $key => $message)
	{
		if (empty($_POST[$key]))
		{
			$errors->add($key . '_error', $message);
		}
	}

	// Birth date must exist
	$test_date  = explode('-', $_POST['birthdate']);
	if (count($test_date) == 3)
	{
		//  checkdate ( int $month , int $day , int $year )
		if (!checkdate($test_date[1], $test_date[2], $test_date[0]))
		{
			$errors->add('birthdate_list_error', __('Birthdate should be a date that exists', 'member-register'));
		}
	}
	else
	{
		$errors->add('birthdate_list_error', __('Birthdate should be in format shown below', 'member-register'));
	}

	// Martial art must be one of those available
	if (!array_key_exists($_POST['martial'], $mr_martial_arts))
	{
		$errors->add('martial_list_error', __('The main martial art should be one of those available in the list', 'member-register'));
	}

	return $errors;
}

/**
 * Public registration. Additional form items saving.
 */
function mr_prf_user_register($user_id)
{
	global $wpdb;

	// Email address can be fetched now from wp_users.
	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'users WHERE ID = '. $user_id;
	$data = $wpdb->get_row($sql, ARRAY_A);

	$values = array(
		'user_login' => $data['user_login'],
		'access' => 1,
		'email' => $data['user_email'],
		'joindate' => date('Y-m-d'),
		'passnro' => '',
		'notes' => '',
		'active' => 0,
		'firstname' => isset($_POST['firstname']) ? mr_htmlent($_POST['firstname']) : '',
		'lastname' => isset($_POST['lastname']) ? mr_htmlent($_POST['lastname']) : '',
		'birthdate' => isset($_POST['birthdate']) ? mr_htmlent($_POST['birthdate']) : '',
		'address' => isset($_POST['address']) ? mr_htmlent($_POST['address']) : '',
		'zipcode' => isset($_POST['zipcode']) ? mr_htmlent($_POST['zipcode']) : '',
		'postal' => isset($_POST['postal']) ? mr_htmlent($_POST['postal']) : '',
		'phone' => isset($_POST['phone']) ? mr_htmlent($_POST['phone']) : '',
		'nationality' => isset($_POST['nationality']) ? mr_htmlent($_POST['nationality']) : '',
		'martial' => isset($_POST['martial']) ? mr_htmlent($_POST['martial']) : '',
		'club' => isset($_POST['club']) ? intval($_POST['club']) : -1
	);

	$keys = implode(', ', array_keys($values));
	$vals = '\'' . implode('\', \'', array_values($values)) . '\'';

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_member (' . $keys . ') VALUES(' . $vals . ')';
	$wpdb->query($sql);

	// Finally update few items in the WP_users (display_name)
	$wpdb->query('UPDATE ' . $wpdb->prefix . 'users SET display_name = \'' .
		$values['firstname'] . ' ' . $values['lastname'] . '\' WHERE ID = '. $user_id);

	// Also add the meta data for name
	update_user_meta($user_id, 'first_name', $values['firstname']);
	update_user_meta($user_id, 'last_name', $values['lastname']);
}
