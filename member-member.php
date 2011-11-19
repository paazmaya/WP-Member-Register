<?php
/**
 * Plugin Name: Member Register
 * Single member related functions
 */

 
/**
 * Show a table of members based on the given filter if any.
 */
function mr_show_members($filters = null)
{
	global $wpdb;
	global $mr_access_type;
	global $mr_martial_arts;
	global $mr_date_format;
	
	// Possible filter options: club, active
	
	$wheres = array();
	$where = '';
	if (is_array($filters))
	{
		if (isset($filters['club']) && is_numeric($filters['club']))
		{
			$wheres[] = ' A.club = ' . intval($filters['club']);
		}
		if (isset($filters['active']) && is_bool($filters['active']))
		{
			$wheres[] = ' A.active = ' . ($filters['active'] ? 1 : 0);
		}
		if (count($wheres) > 0)
		{
			$where = ' WHERE ' . implode(' AND', $wheres);
		}
	}

	// id access firstname lastname birthdate address zipcode postal phone email nationality
	// joindate passnro notes lastlogin active club

	$sql = 'SELECT A.*, B.name AS nationalityname, C.id AS wpuserid FROM ' . $wpdb->prefix .
		'mr_member A LEFT JOIN ' . $wpdb->prefix . 'mr_country B ON A.nationality = B.code LEFT JOIN '
		. $wpdb->prefix . 'users C ON A.user_login = C.user_login' . $where . ' ORDER BY A.lastname ASC';

	//echo '<div class="error"><p>' . $sql . '</p></div>';
	
	$members = $wpdb->get_results($sql, ARRAY_A);

	?>
	<table class="wp-list-table widefat tablesorter">
	<thead>
	<tr>
		<th class="headerSortDown"><?php echo __('Last name'); ?></th>
		<th><?php echo __('First name'); ?></th>
		<th><?php echo __('Birthday'); ?></th>
		<th><?php echo __('E-mail'); ?></th>
		<th><?php echo __('Phone number'); ?></th>
		<th><?php echo __('Main martial art'); ?></th>
		<th><?php echo __('Access rights'); ?></th>
		<th><?php echo __('Last login'); ?></th>
		<th><?php echo __('WP username'); ?></th>
	</tr>
	</thead>
	<tbody>

	<?php
	foreach($members as $member)
	{
		$url = '<a href="' . admin_url('admin.php?page=member-register-control') .
			'&memberid=' . $member['id'] . '" title="' . $member['firstname'] .
			' '	. $member['lastname'] . '|' . __('Address') . ': ' . $member['address'] . ', ' .
			$member['zipcode'] . ' ' . $member['postal'] . '|' . __('Nationality') . ': ' .
			$member['nationalityname'] . '|' . __('Date of joining') . ': ' .
			$member['joindate'] . '" class="tip">';

		echo '<tr id="user_' . $member['id'] . '">';
		echo '<td';
		if (intval($member['active']) == 0)
		{
			echo ' class="redback"';
		}
		echo '>' . $url . $member['lastname'] . '</a></td>';
		echo '<td>' . $url . $member['firstname'] . '</a></td>';
		echo '<td>';
		if ($member['birthdate'] != '0000-00-00') 
		{
			echo $member['birthdate'];
		}
		echo '</td>';
		echo '<td>' . $member['email'] . '</td>';
		echo '<td>' . $member['phone'] . '</td>';
		echo '<td title="' . (isset($mr_martial_arts[$member['martial']]) ? $mr_martial_arts[$member['martial']] : '') . '">' . $member['martial'] . '</td>';
		echo '<td title="' . (isset($mr_access_type[$member['access']]) ? $mr_access_type[$member['access']] : '') . '">' . $member['access'] . '</td>';
		echo '<td>';
		if ($member['lastlogin'] > 0)
		{
			echo date($mr_date_format, $member['lastlogin']);
		}
		echo '</td>';
		echo '<td>';
		if ($member['user_login'] != '' && $member['user_login'] != null && is_numeric($member['wpuserid']))
		{
			echo '<a href="' . admin_url('user-edit.php?user_id=') . $member['wpuserid'] .
				'" title="' . __('Muokkaa WP käyttäjää') . '">' . $member['user_login'] . '</a>';
		}
		echo  '</td>';

		echo '</tr>';
	}
	?>
	</tbody>
	</table>
	<?php
}

/**
 * Show all possible information of the given user.
 */
function mr_show_member_info($id)
{
	global $wpdb;
	global $userdata;
	global $mr_date_format;
	global $mr_access_type;
	global $mr_grade_values;
	global $mr_grade_types;
	global $mr_martial_arts;

	$id = intval($id);

	// Check for possible insert
    if (isset($_POST['mr_submit_hidden_member']) && $_POST['mr_submit_hidden_member'] == 'Y' )
	{
        if (mr_update_member_info($_POST))
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Jäsenen tiedot päivitetty') . '</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
    }
    else if (isset($_POST['mr_submit_hidden_grade']) && $_POST['mr_submit_hidden_grade'] == 'Y' )
	{
        if (mr_insert_new_grade($_POST))
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Uusi vyöarvo lisätty') . '</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
    }


	// ---------------

	$items = array('id', 'user_login', 'access', 'firstname', 'lastname',
		'birthdate', 'address', 'zipcode', 'postal', 'phone', 'email',
		'nationality', 'joindate', 'passnro', 'martial', 'notes', 'lastlogin', 'active',
		'club');
	$sql = 'SELECT A.*, B.name AS nationalitycountry, C.title AS clubname, D.id AS wpuserid FROM ' . 
		$wpdb->prefix . 'mr_member A LEFT JOIN ' .
		$wpdb->prefix . 'mr_country B ON A.nationality = B.code LEFT JOIN ' . 
		$wpdb->prefix . 'mr_club C ON A.club = C.id LEFT JOIN ' .
		$wpdb->prefix . 'users D ON A.user_login = D.user_login WHERE A.id = ' . $id . ' LIMIT 1';
	$person = $wpdb->get_row($sql, ARRAY_A);

	echo '<h1>' . $person['firstname'] . ' ' . $person['lastname'] . '</h1>';
	echo '<p>' . __('In case you wish to remove a user, first all grades and payments should be removed.') . '</p>';
	
	if (isset($_GET['edit']))
	{
		mr_new_member_form(admin_url('admin.php?page=member-register-control') . '&memberid=' . $id, $person);
	}
	else
	{
		?>
		<h3><?php echo __('Henkilötiedot'); ?></h3>
		<table class="wp-list-table widefat users">
		<tbody>
			<tr>
				<th><?php echo __('Last name'); ?></th>
				<td><?php echo $person['lastname']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('First name'); ?></th>
				<td><?php echo $person['firstname']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Kirjautumistaso'); ?> <span class="description">()</span></th>
				<td><?php echo $mr_access_type[$person['access']] . ' ('. $person['access'] . ')'; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Birthday'); ?></th>
				<td><?php echo $person['birthdate']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Address'); ?></th>
				<td><?php echo $person['address']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Postinumero'); ?></th>
				<td><?php echo $person['zipcode']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Postitoimipaikka'); ?> <span class="description">(<?php echo __('ja maa jos ei Suomi'); ?>)</span></th>
				<td><?php echo $person['postal']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Phone number'); ?></th>
				<td><?php echo $person['phone']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('E-mail'); ?></th>
				<td><a href="mailto:<?php echo $person['email']; ?>" title="<?php echo __('Lähetä sähköpostia'); ?>"><?php echo $person['email']; ?></a></td>
			</tr>
			<tr>
				<th><?php echo __('Nationality'); ?></th>
				<td><?php echo $person['nationalitycountry']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Date of joining'); ?></th>
				<td><?php echo $person['joindate']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Yuishinkai passinumero'); ?> <span class="description">()</span></th>
				<td><?php echo $person['passnro']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Main martial art'); ?> <span class="description">(<?php echo __('rekisteröity tähän lajiin'); ?>)</span></th>
				<td><?php echo (isset($mr_martial_arts[$person['martial']]) ? $mr_martial_arts[$person['martial']] . ' (' . $person['martial'] . ')' : '-'); ?></td>
			</tr>
			<tr>
				<th><?php echo __('Lisätietoja'); ?> <span class="description">(<?php echo __('vapaasti kirjoiteltu'); ?>)</span></th>
				<td><?php echo $person['notes']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Viimeksi vieraillut sivuilla'); ?></th>
				<td><?php echo ($person['lastlogin'] != 0 ? date($mr_date_format, $person['lastlogin']) : ''); ?></td>
			</tr>
			<tr>
				<th><?php echo __('Aktiivinen'); ?> <span class="description">(<?php echo __('saako kirjautua sivuille'); ?>)</span></th>
				<td><?php echo $person['active']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Seura'); ?> <span class="description">(<?php echo __('missä harjoittelee'); ?>)</span></th>
				<td><?php 
				if ($person['clubname'] != '' && $userdata->mr_access >= 8)
				{
					echo '<a href="' . admin_url('admin.php?page=member-club-list') . '&club=' . 
						$person['club'] . '" title="' . __('List of active members in the club called:') .
						' ' . $person['clubname'] . '">' . $person['clubname'] . '</a>';
				}
				else
				{
					echo $person['clubname']; 
				}
				?></td>
			</tr>
			<tr>
				<th><?php echo __('WP username'); ?> <span class="description">(<?php echo __('mikäli sellainen on'); ?>)</span></th>
				<td><?php 
					if ($person['user_login'] != '' && $person['user_login'] != null && is_numeric($person['wpuserid']))
					{
						echo '<a href="' . admin_url('user-edit.php?user_id=') . $person['wpuserid'] .
							'" title="' . __('Muokkaa WP käyttäjää') . '">' . $person['user_login'] . '</a>';
					}
					else 
					{
						echo $person['user_login']; 
					}
				?></td>
			</tr>
		</tbody>
		</table>
		<?php
		echo '<p><a href="' . admin_url('admin.php?page=member-register-control') . '&memberid='
			. $id . '&edit" title="' . __('Muokkaa tätä käyttää') . '" class="button-primary">' . __('Muokkaa tätä käyttää') . '</a></p>';
	}

	// ---------------
	echo '<hr />';
	echo '<h2>' . __('Vyöarvot') . '</h2>';
	mr_show_grades($id);

	// Quick add a grade
	mr_grade_quick_form(array(
		'id' => $id,
		'name' => $person['firstname'] . ' ' . $person['lastname']
	));
	?>

	<hr />
	<h2><?php echo __('Jäsenmaksut'); ?></h2>
	<?php

	// ---------------

	mr_show_payments_lists($id);

}


function mr_member_new()
{
	if (!current_user_can('create_users'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;

	// Check for possible insert
    $hidden_field_name = 'mr_submit_hidden_member';
    if( isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' )
	{
        if (mr_insert_new_member($_POST))
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Uusi jäsen lisätty, nimellä:') . ' ' . $_POST['firstname'] . ' ' . $_POST['lastname'] . '</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
    }

    ?>
	<div class="wrap">
		<h2><?php echo __('Lisää uusi jäsen'); ?></h2>
		<?php
		mr_new_member_form(admin_url('admin.php?page=member-register-new'), array());
		?>
	</div>

	<?php
}
 

function mr_insert_new_member($postdata)
{
	global $wpdb;

	$values = array();
	$required = array('user_login', 'access', 'firstname', 'lastname', 'birthdate',
		'address', 'zipcode', 'postal', 'phone', 'email', 'nationality', 'joindate',
		'passnro', 'martial', 'notes', 'active', 'club');

	foreach($postdata as $k => $v)
	{
		if (in_array($k, $required))
		{
			// sanitize
			$values[mr_urize($k)] = mr_htmlent($v);
		}
	}
	
	return $wpdb->insert(
		$wpdb->prefix . 'mr_member',
		$values
	);
}

function mr_update_member_info($postdata)
{
	global $wpdb;

	$set = array();
	$required = array('user_login', 'access', 'firstname', 'lastname', 'birthdate',
		'address', 'zipcode', 'postal', 'phone', 'email', 'nationality', 'joindate',
		'passnro', 'martial', 'notes', 'active', 'club');

	if (isset($postdata['id']) && is_numeric($postdata['id']))
	{
		foreach($postdata as $k => $v)
		{
			if (in_array($k, $required))
			{
				// sanitize
				$set[] = mr_urize($k) . " = '" . mr_htmlent($v) . "'";
			}
		}

		$id = intval($postdata['id']);

		$sql = 'UPDATE ' . $wpdb->prefix . 'mr_member SET ' . implode(', ', $set) . 'WHERE id = ' . $id;

		//echo '<div class="error"><p>' . $sql . '</p></div>';

		return $wpdb->query($sql);
	}
	else
	{
		return false;
	}
}

 
 
/**
 * Print out a form for adding new members.
 * @param $action Target page of the form
 * @param $data Array
 */
function mr_new_member_form($action, $data)
{
	global $wpdb;
	global $mr_access_type;
	global $mr_martial_arts;

	// Default values for an empty form
	$values = array(
		'id' => 0,
		'user_login' => '',
		'access' => 1,
		'firstname' => '',
		'lastname' => '',
		'birthdate' => '',
		'address' => '',
		'zipcode' => '',
		'postal' => '',
		'phone' => '',
		'email' => '',
		'nationality' => 'FI',
		'joindate' => '',
		'passnro' => '',
		'martial' => '',
		'notes' => '',
		'active' => 1,
		'club' => -1
	);
	$values = array_merge($values, $data);

	/*
	echo '<pre>';
	print_r($values);
	echo '</pre>';
	*/

	?>
	<form name="form1" method="post" action="<?php echo $action; ?>">
		<input type="hidden" name="mr_submit_hidden_member" value="Y" />
		<input type="hidden" name="id" value="<?php echo $values['id']; ?>" />
		<table class="form-table" id="createuser">
			<tr class="form-field">
				<th><?php echo __('WP username'); ?> <span class="description">(<?php echo __('jos on jo olemassa'); ?>)</span></th>
				<td><select name="user_login">
				<option value="">-</option>
				<?php
				if (isset($_GET['edit']))
				{
					$sql = 'SELECT user_login, display_name FROM ' . $wpdb->prefix . 'users ORDER BY 2 ASC';
				}
				else
				{
					$sql = 'SELECT A.user_login, A.display_name FROM ' . $wpdb->prefix . 'users A LEFT JOIN '
						. $wpdb->prefix . 'mr_member B ON A.user_login = B.user_login WHERE B.user_login IS NULL ORDER BY 2 ASC';
				}

				$users = $wpdb->get_results($sql, ARRAY_A);
				foreach($users as $user)
				{
					echo '<option value="' . $user['user_login']. '"';
					if ($values['user_login'] == $user['user_login'])
					{
						echo ' selected="selected"';
					}
					echo '>' . $user['display_name'] . ' (' . $user['user_login'] . ')</option>';
				}
				?>
				</select></td>
			</tr>
			<tr class="form-field form-required">
				<th><?php echo __('Kirjautumistaso'); ?></th>
				<td><select name="access">
					<?php
					foreach ($mr_access_type as $k => $v)
					{
						echo '<option value="' . $k . '"';
						if ($values['access'] == $k)
						{
							echo ' selected="selected"';
						}
						echo '>' . $v . ' (' . $k . ')</option>';
					}
					?>
					</select>
				</td>
			</tr>
			<tr class="form-field form-required">
				<th><?php echo __('Etunimi'); ?></th>
				<td><input type="text" name="firstname" value="<?php echo $values['firstname']; ?>" /></td>
			</tr>
			<tr class="form-field form-required">
				<th><?php echo __('Last name'); ?></th>
				<td><input type="text" name="lastname" value="<?php echo $values['lastname']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Birthday'); ?> <span class="description">(YYYY-MM-DD)</span></th>
				<td><input type="text" name="birthdate" class="pickday" value="<?php echo $values['birthdate']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Postiosoite'); ?></th>
				<td><input type="text" name="address" value="<?php echo $values['address']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Postinumero'); ?></th>
				<td><input type="text" name="zipcode" value="<?php echo $values['zipcode']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Postitoimipaikka'); ?></th>
				<td><input type="text" name="postal" value="<?php echo $values['postal']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Puhelinnumero'); ?></th>
				<td><input type="text" name="phone" value="<?php echo $values['phone']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('E-mail'); ?></th>
				<td><input type="text" name="email" value="<?php echo $values['email']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Nationality'); ?></th>
				<td><select name="nationality">
				<option value="">-</option>
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
				</select></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Date of joining'); ?> <span class="description">(YYYY-MM-DD)</span></th>
				<td><input type="text" name="joindate" class="pickday" value="<?php echo $values['joindate']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Yuishinkai passinumero'); ?></th>
				<td><input type="text" name="passnro" value="<?php echo $values['passnro']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Main martial art'); ?></th>
				<td><select name="martial">
					<option value="">-</option>
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
					</select></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Lisätietoja'); ?></th>
				<td><input type="text" name="notes" value="<?php echo $values['notes']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Aktiivinen'); ?> <span class="description">(<?php echo __('voiko käyttää sivustoa'); ?>)</span></th>
				<td>
					<label><input type="radio" name="active" value="1" <?php if ($values['active'] == 1) echo 'checked="checked"'; ?> /> kyllä</label><br />
					<label><input type="radio" name="active" value="0" <?php if ($values['active'] == 0) echo 'checked="checked"'; ?> /> ei</label>
				</td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Seura'); ?> <span class="description">(<?php echo __('missä seurassa pääsääntöisesti harjoittelee'); ?>)</span></th>
				<td><select name="club">
				<option value="-1">-</option>
				<?php
				$clubs = mr_get_list('club', '', '', 'title ASC');
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
				</select></td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
		</p>

	</form>
	<?php
}
 
 
 
 
 
 
 