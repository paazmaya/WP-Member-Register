<?php
/**
 * Part of Member Register
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
	
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_MEMBERS_VIEW))
	{
		wp_die( __('You do not have sufficient permissions to access this page.', 'member-register') );
	}
	
	// Possible filter options: club, active, group
	
	$wheres = array();
	$where = ' WHERE A.visible = 1';
	if (is_array($filters))
	{
		if (isset($filters['club']) && is_numeric($filters['club']))
		{
			$wheres[] = 'A.club = ' . intval($filters['club']);
		}
		if (isset($filters['active']) && is_bool($filters['active']))
		{
			$wheres[] = 'A.active = ' . ($filters['active'] ? 1 : 0);
		}
		if (isset($filters['group']) && is_numeric($filters['group']))
		{
			$wheres[] = 'A.id IN (SELECT GM.member_id FROM ' . $wpdb->prefix . 'mr_group_member GM WHERE GM.group_id = ' . intval($filters['group']) . ')';
		}
		if (count($wheres) > 0)
		{
			$where = $where . ' AND ' . implode(' AND ', $wheres);
		}
	}

	// id access firstname lastname birthdate address zipcode postal phone email nationality
	// joindate passnro notes lastlogin active club visible

	$sql = 'SELECT A.*, B.name AS nationalityname, C.id AS wpuserid FROM ' . $wpdb->prefix .
		'mr_member A LEFT JOIN ' . $wpdb->prefix . 'mr_country B ON A.nationality = B.code LEFT JOIN '
		. $wpdb->prefix . 'users C ON A.user_login = C.user_login' . $where . ' ORDER BY A.lastname ASC';

	//echo '<div class="error"><p>' . $sql . '</p></div>';
	
	$members = $wpdb->get_results($sql, ARRAY_A);

	?>
	<table class="wp-list-table widefat tablesorter">
	<caption></caption>
	<thead>
	<tr>
		<th class="hideable"><?php echo __('Member ID', 'member-register'); ?></th>
		<th class="headerSortDown"><?php echo __('Last name', 'member-register'); ?></th>
		<th><?php echo __('First name', 'member-register'); ?></th>
		<th><?php echo __('Birthday', 'member-register'); ?></th>
		<th><?php echo __('E-mail', 'member-register'); ?></th>
		<th><?php echo __('Phone number', 'member-register'); ?></th>
		<th class="hideable"><?php echo __('Main martial art', 'member-register'); ?></th>
		<th class="hideable"><?php echo __('Access rights', 'member-register'); ?></th>
		<th class="hideable"><?php echo __('Last login', 'member-register'); ?></th>
		<th class="hideable"><?php echo __('WP username', 'member-register'); ?></th>
	</tr>
	</thead>
	<tbody>

	<?php
	foreach($members as $member)
	{
		$url = '<a href="' . admin_url('admin.php?page=member-register-control') .
			'&memberid=' . $member['id'] . '" title="' . $member['firstname'] .
			' '	. $member['lastname'] . '">';

		echo '<tr id="user_' . $member['id'] . '">';
		echo '<td>' . $member['id'] . '</td>';
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
		echo '<td title="' . $member['access'] . '">';
		list_user_rights($member['access']);
		echo '</td>';
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
				'" title="' . __('Muokkaa WP käyttäjää', 'member-register') . '">' . $member['user_login'] . '</a>';
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
	if (!current_user_can('read'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.', 'member-register') );
	}
	
	global $wpdb;
	global $userdata;
	global $mr_date_format;
	global $mr_access_type;
	global $mr_grade_values;
	global $mr_grade_types;
	global $mr_martial_arts;

	$id = intval($id);
	$usercanedit = false;
	
	if (!mr_has_permission(MR_ACCESS_MEMBERS_VIEW))
	{
		// id must be of the current user
		$id = $userdata->mr_memberid;
	}
	
	if (mr_has_permission(MR_ACCESS_MEMBERS_EDIT) || $id == $userdata->mr_memberid)
	{
		$usercanedit = true;
	}

	// Check for possible insert
    if (isset($_POST['mr_submit_hidden_member']) && $_POST['mr_submit_hidden_member'] == 'Y' && $usercanedit)
	{
        if (mr_update_member_info($_POST))
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Jäsenen tiedot päivitetty', 'member-register') . '</strong>';
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
	$sql = 'SELECT A.*, B.name AS nationalitycountry, C.title AS clubname, D.id AS wpuserid, ' .
		'(SELECT COUNT(*) FROM ' . $wpdb->prefix . 'mr_grade WHERE member = ' . $id . ' AND visible = 1) AS gradecount, '.
		'(SELECT COUNT(*) FROM ' . $wpdb->prefix . 'mr_payment WHERE member = ' . $id . ' AND visible = 1) AS paymentcount FROM ' . 
		$wpdb->prefix . 'mr_member A LEFT JOIN ' .
		$wpdb->prefix . 'mr_country B ON A.nationality = B.code LEFT JOIN ' . 
		$wpdb->prefix . 'mr_club C ON A.club = C.id LEFT JOIN ' .
		$wpdb->prefix . 'users D ON A.user_login = D.user_login WHERE A.id = ' . $id . ' LIMIT 1';
	$person = $wpdb->get_row($sql, ARRAY_A);

	echo '<h1>' . $person['firstname'] . ' ' . $person['lastname'] . '</h1>';
	
	if (mr_has_permission(MR_ACCESS_MEMBERS_EDIT))
	{
		echo '<p>' . __('In case you wish to remove a user, first all grades and payments should be removed.', 'member-register') . 
			' (' . $person['gradecount'] . ', ' . $person['paymentcount'] . ')</p>';
		
		if (intval($person['gradecount']) == 0 && intval($person['paymentcount']) == 0)
		{
			echo '<p><a href="' . admin_url('admin.php?page=member-register-control') . '&removeid=' . $id .
				'" title="' . __('This user can be removed by clicking here', 'member-register') . '">[' .
				__('This user can be removed by clicking here', 'member-register') . ']</a>';
		}
	}
	
	if (isset($_GET['edit']) && $usercanedit)
	{
		mr_new_member_form(admin_url('admin.php?page=member-register-control') . '&memberid=' . $id, $person);
	}
	else
	{
		?>
		<h3><?php echo __('Henkilötiedot', 'member-register'); ?></h3>
		<table class="wp-list-table widefat users">
		<tbody>
			<tr>
				<th><?php echo __('Last name', 'member-register'); ?></th>
				<td><?php echo $person['lastname']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('First name', 'member-register'); ?></th>
				<td><?php echo $person['firstname']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Kirjautumisoikeudet', 'member-register'); ?> <span class="description">(lista asioista joita käyttäjä voi tehdä)</span></th>
				<td><?php
					list_user_rights($person['access']);
				?></td>
			</tr>
			<tr>
				<th><?php echo __('Birthday', 'member-register'); ?></th>
				<td><?php echo $person['birthdate']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Address', 'member-register'); ?></th>
				<td><?php echo $person['address']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Postinumero', 'member-register'); ?></th>
				<td><?php echo $person['zipcode']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Postitoimipaikka', 'member-register'); ?> <span class="description">(<?php echo __('ja maa jos ei Suomi', 'member-register'); ?>)</span></th>
				<td><?php echo $person['postal']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Phone number', 'member-register'); ?></th>
				<td><?php echo $person['phone']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('E-mail', 'member-register'); ?></th>
				<td><a href="mailto:<?php echo $person['email']; ?>" title="<?php echo __('Lähetä sähköpostia', 'member-register'); ?>"><?php echo $person['email']; ?></a></td>
			</tr>
			<tr>
				<th><?php echo __('Nationality', 'member-register'); ?></th>
				<td><?php echo $person['nationalitycountry']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Date of joining', 'member-register'); ?></th>
				<td><?php echo $person['joindate']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Yuishinkai passinumero', 'member-register'); ?> <span class="description">(sinikantinen passi)</span></th>
				<td><?php echo $person['passnro']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Main martial art', 'member-register'); ?> <span class="description">(<?php echo __('rekisteröity tähän lajiin', 'member-register'); ?>)</span></th>
				<td><?php
					if (isset($person['martial']) && $person['martial'] != '' && $mr_martial_arts[$person['martial']])
					{
						echo $mr_martial_arts[$person['martial']] . ' (' . $person['martial'] . ')'; 
					}
					?>
				</td>
			</tr>
			<tr>
				<th><?php echo __('Lisätietoja', 'member-register'); ?> <span class="description">(<?php echo __('vapaasti kirjoiteltu', 'member-register'); ?>)</span></th>
				<td><?php echo $person['notes']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Viimeksi vieraillut sivuilla', 'member-register'); ?></th>
				<td><?php echo ($person['lastlogin'] != 0 ? date($mr_date_format, $person['lastlogin']) : ''); ?></td>
			</tr>
			<tr>
				<th><?php echo __('Aktiivinen', 'member-register'); ?> <span class="description">(<?php echo __('saako kirjautua sivuille', 'member-register'); ?>)</span></th>
				<td><?php echo $person['active']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Seura'); ?> <span class="description">(<?php echo __('missä harjoittelee', 'member-register'); ?>)</span></th>
				<td><?php 
				if ($person['clubname'] != '' && mr_has_permission(MR_ACCESS_CLUB_MANAGE))
				{
					echo '<a href="' . admin_url('admin.php?page=member-club-list') . '&club=' . 
						$person['club'] . '" title="' . __('List of active members in the club called:', 'member-register') .
						' ' . $person['clubname'] . '">' . $person['clubname'] . '</a>';
				}
				else
				{
					echo $person['clubname']; 
				}
				?></td>
			</tr>
			<tr>
				<th><?php echo __('WP username', 'member-register'); ?> <span class="description">(<?php echo __('mikäli sellainen on', 'member-register'); ?>)</span></th>
				<td><?php 
					if ($person['user_login'] != '' && $person['user_login'] != null && is_numeric($person['wpuserid']) && $usercanedit)
					{
						echo '<a href="' . admin_url('user-edit.php?user_id=') . $person['wpuserid'] .
							'" title="' . __('Muokkaa WP käyttäjää', 'member-register') . '">' . $person['user_login'] . '</a>';
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
		if ($usercanedit)
		{
			echo '<p><a href="' . admin_url('admin.php?page=member-register-control') . '&memberid='
				. $id . '&edit" title="' . __('Muokkaa tätä käyttää', 'member-register') . '" class="button-primary">' . __('Muokkaa tätä käyttää', 'member-register') . '</a></p>';
		}
	}

	// ---------------
	echo '<hr />';
	echo '<h2>' . __('Vyöarvot', 'member-register') . '</h2>';
	mr_show_grades($id);
	
	?>

	<hr />
	<h2><?php echo __('Jäsenmaksut', 'member-register'); ?></h2>
	<?php

	// ---------------

	mr_show_payments_lists($id);

}


/**
 * Remove the given member by setting the visible flag to 0
 */
function mr_remove_member($id)
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_MEMBERS_EDIT))
	{
		wp_die( __('You do not have sufficient permissions to access this page.', 'member-register'));
	}

	global $wpdb;
	
	$removal = $wpdb->update( 
		$wpdb->prefix . 'mr_member',
		array( 
			'visible' => 0
		), 
		array( 
			'id' => $id 
		), 
		array( 
			'%d'
		), 
		array( 
			'%d' 
		) 
	);
	
	// TODO: if 'user_login' is not empty, that WP user should be disabled.
	
	if ($removal)
	{
		echo '<div class="updated"><p>';
		echo '<strong>' . __('Jäsen poistettu', 'member-register') . ' (' . $id . ')</strong>';
		echo '</p></div>';
	}
	else
	{
		echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
	}
}

function mr_member_new()
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_MEMBERS_EDIT))
	{
		wp_die( __('You do not have sufficient permissions to access this page.', 'member-register'));
	}

	global $wpdb;

	// Check for possible insert
    $hidden_field_name = 'mr_submit_hidden_member';
    if( isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' )
	{
        if (mr_insert_new_member($_POST))
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Uusi jäsen lisätty, nimellä:', 'member-register') . ' ' . $_POST['firstname'] . ' ' . $_POST['lastname'] . '</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
    }

    ?>
	<div class="wrap">
		<h2><?php echo __('Lisää uusi jäsen', 'member-register'); ?></h2>
		<?php
		mr_new_member_form(admin_url('admin.php?page=member-register-new'), array());
		?>
	</div>

	<?php
}
 

function mr_insert_new_member($postdata)
{
	global $wpdb;

	$keys = array();
	$values = array();
	$required = array('user_login', 'access', 'firstname', 'lastname', 'birthdate',
		'address', 'zipcode', 'postal', 'phone', 'email', 'nationality', 'joindate',
		'passnro', 'martial', 'notes', 'active', 'club');

	foreach($postdata as $k => $v)
	{
		if (in_array($k, $required))
		{
			// sanitize
			$keys[] = mr_urize($k);
			if ($k == 'access')
			{
				$rights = 0;
				if (is_array($v))
				{
					foreach($v as $level)
					{
						$rights += intval($level);
					}
				}
				$values[] = "'" . $rights . "'";
			}
			else
			{
				$values[] = "'" . mr_htmlent($v) . "'";
			}
		}
	}

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_member (' . implode(', ', $keys) . ') VALUES(' . implode(', ', $values) . ')';

	//echo '<div class="error"><p>' . $sql . '</p></div>';

	return $wpdb->query($sql);
}

function mr_update_member_info($postdata)
{
	global $wpdb;
	global $userdata;

	$values = array();
	$required = array('user_login', 'access', 'firstname', 'lastname', 'birthdate',
		'address', 'zipcode', 'postal', 'phone', 'email', 'nationality', 'joindate',
		'passnro', 'martial', 'notes', 'active', 'club');

	
	if (!mr_has_permission(MR_ACCESS_MEMBERS_EDIT))
	{
		// id must be of the current user
		$postdata['id'] = $userdata->mr_memberid;
	}
	
	if (isset($postdata['id']) && is_numeric($postdata['id']))
	{
		foreach($postdata as $k => $v)
		{
			if (in_array($k, $required))
			{
				// sanitize
				
				if ($k == 'access')
				{
					$rights = 0;
					if (is_array($v))
					{
						foreach($v as $level)
						{
							$rights += intval($level);
						}
					}
					$values[] = mr_urize($k) . " = '" . $rights . "'";
				}
				else
				{
					$values[] = mr_urize($k) . " = '" . mr_htmlent($v) . "'";
				}
			}
		}

		$id = intval($postdata['id']);

		$sql = 'UPDATE ' . $wpdb->prefix . 'mr_member SET ' . implode(', ', $values) . ' WHERE id = ' . $id . ' LIMIT 1';

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
	if (!current_user_can('read'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.', 'member-register') );
	}
	
	global $wpdb;
	global $userdata;
	global $mr_access_type;
	global $mr_martial_arts;
	
	
	if (!mr_has_permission(MR_ACCESS_MEMBERS_EDIT))
	{
		// id must be of the current user
		$data['id'] = $userdata->mr_memberid;
	}

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
	<form name="form1" method="post" action="<?php echo $action; ?>" autocomplete="on">
		<input type="hidden" name="mr_submit_hidden_member" value="Y" />
		<input type="hidden" name="id" value="<?php echo $values['id']; ?>" />
		<table class="form-table" id="mrform">
			<tr class="form-field">
				<th><?php echo __('WP username', 'member-register'); ?> <span class="description">(<?php echo __('jos on jo olemassa', 'member-register'); ?>)</span></th>
				<td>
				<?php
				if (mr_has_permission(MR_ACCESS_MEMBERS_EDIT))
				{
					?>
					<select name="user_login" data-placeholder="Valitse jo olemassa oleva WP käyttäjä">
					<option value=""></option>
					<?php
					// If editing, select all free and the current. If new, select all free				
					$sql = 'SELECT A.user_login, A.display_name FROM ' . $wpdb->prefix . 'users A ' .
						'WHERE A.user_login = \'' . $values['user_login'] . '\' LIMIT 1' .
						' UNION ' .
						'SELECT A.user_login, A.display_name FROM ' . $wpdb->prefix . 'users A ' .
						'WHERE A.user_login NOT IN (SELECT B.user_login FROM ' . $wpdb->prefix .
						'mr_member B WHERE B.user_login IS NOT NULL) ORDER BY 2 ASC';
						
						
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
					</select>
					<?php
				}
				else
				{
					echo $userdata->user_login;
				}
				?>
				</td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Kirjautumistaso', 'member-register'); ?></th>
				<td>
				<?php
				if (mr_has_permission(MR_ACCESS_MEMBERS_EDIT))
				{
					?>
					<select class="chosen" name="access[]" multiple="multiple" data-placeholder="Valitse käyttäjän oikeudet">
					<?php
					foreach ($mr_access_type as $k => $v)
					{
						echo '<option value="' . $k . '"';
						if (mr_has_permission($k, $values['access']))
						{
							echo ' selected="selected"';
						}
						echo '>' . $v . ' (' . $k . ')</option>';
					}
					?>
					</select>
					<?php
				}
				else
				{
					list_user_rights($values['access']);
				}
				?>
				</td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Etunimi', 'member-register'); ?></th>
				<td><input type="text" name="firstname" class="required" required="required" value="<?php echo $values['firstname']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Last name', 'member-register'); ?></th>
				<td><input type="text" name="lastname" class="required" required="required" value="<?php echo $values['lastname']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Birthday', 'member-register'); ?> <span class="description">(YYYY-MM-DD)</span></th>
				<td><input type="text" name="birthdate" class="pickday" value="<?php echo $values['birthdate']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Postiosoite', 'member-register'); ?></th>
				<td><input type="text" name="address" value="<?php echo $values['address']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Postinumero', 'member-register'); ?></th>
				<td><input type="text" name="zipcode" value="<?php echo $values['zipcode']; ?>" list="zipcodes" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Postitoimipaikka', 'member-register'); ?></th>
				<td><input type="text" name="postal" value="<?php echo $values['postal']; ?>" list="postals" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Puhelinnumero', 'member-register'); ?></th>
				<td><input type="text" name="phone" value="<?php echo $values['phone']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('E-mail', 'member-register'); ?></th>
				<td><input type="text" name="email" value="<?php echo $values['email']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Nationality', 'member-register'); ?></th>
				<td><select class="chosen" name="nationality" data-placeholder="Valitse käyttäjän kansallisuus">
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
				</select></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Date of joining', 'member-register'); ?> <span class="description">(YYYY-MM-DD)</span></th>
				<td><input type="text" name="joindate" class="pickday" value="<?php echo $values['joindate']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Yuishinkai passinumero', 'member-register'); ?></th>
				<td><input type="text" name="passnro" value="<?php echo $values['passnro']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Main martial art', 'member-register'); ?></th>
				<td><select name="martial" data-placeholder="Valitse päälaji">
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
					</select></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Lisätietoja', 'member-register'); ?></th>
				<td><input type="text" name="notes" value="<?php echo $values['notes']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Aktiivinen', 'member-register'); ?> <span class="description">(<?php echo __('voiko käyttää sivustoa', 'member-register'); ?>)</span></th>
				<td>
				<?php
				if (mr_has_permission(MR_ACCESS_MEMBERS_EDIT))
				{
					?>
					<label><input type="radio" name="active" value="1" <?php if ($values['active'] == 1) echo 'checked="checked"'; ?> /> kyllä</label><br />
					<label><input type="radio" name="active" value="0" <?php if ($values['active'] == 0) echo 'checked="checked"'; ?> /> ei</label>
					<?php
				}
				else 
				{
					echo ($values['active'] == 1) ? 'kyllä' : 'ei';
				}
				?>
				</td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Seura', 'member-register'); ?> <span class="description">(<?php echo __('missä seurassa pääsääntöisesti harjoittelee', 'member-register'); ?>)</span></th>
				<td><select name="club" data-placeholder="Valitse seura">
				<option value=""></option>
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

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
		</p>

	</form>
	<?php
}
 
 
 
 
 
 
 