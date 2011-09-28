<?php
/**
 Plugin Name: Member Register
*/

/**
 * Check for permission for doing certain things.
 * @param $access int Access level of the current user
 * @param $permission bytes to check against which are for the action that is to be checked
 */
function mr_check_permission($access, $permission)
{
	global $mr_access_type;

	if ($mr_access_type[$access] & $permission)
	{
		return true;
	}
	else
	{
		return false;
	}
}


function mr_show_access_values()
{
	global $mr_access_type;
	echo '<p>' . __('Alla lyhyesti selostettuna kunkin käyttäjätason (access) oikeudet') . '. ' .
		__('Pyynnöstä näitä voidaan lisätä tai vähentää.') . '</p>';
	echo '<ul>';
	foreach ($mr_access_type as $k => $v)
	{
		echo '<li title="' . $v . ' (' . $k . ')">[' . $k . '] ' . $v . '</li>';
	}
	echo '</ul>';
}

/**
 * Insert the given grade
 * @param $postdata Array
 */
function mr_insert_new_grade($postdata)
{
	global $wpdb;

	$keys = array();
	$values = array();

	// Note that member/members are also required.
	$required = array('grade', 'type', 'location', 'nominator', 'day');

	foreach($postdata as $k => $v)
	{
		if (in_array($k, $required))
		{
			// sanitize
			$keys[] = mr_urize($k);
			$values[] = "'" . mr_htmlent($v) . "'";
		}
	}
	$keys[] = 'member';

	if (isset($postdata['member']))
	{
		$postdata['members'] = array($postdata['member']);
	}

	if (isset($postdata['members']) && is_array($postdata['members']))
	{
		foreach($postdata['members'] as $member)
		{
			$setval[] = '(' . implode(', ', array_merge($values, array('"' . intval($member) . '"'))) . ')';
		}

		$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_grade (' . implode(', ', $keys) . ') VALUES ' . implode(', ', $setval);

		echo $sql;

		return $wpdb->query($sql);
	}
	else
	{
		return false;
	}
}



function mr_insert_new_payment($postdata)
{
	global $wpdb;

	$keys = array();
	$values = array();
	$setval = array();

	$required = array('type', 'amount', 'deadline', 'validuntil');


	if (isset($postdata['members']) && is_array($postdata['members']) && count($postdata['members']) > 0)
	{
		foreach($postdata as $k => $v)
		{
			if (in_array($k, $required))
			{
				// sanitize
				$keys[] = mr_urize($k);
				$values[] = "'" . mr_htmlent($v) . "'";
			}
		}

		$keys[] = 'member';
		$keys[] = 'reference';


		$id = intval('2' . $wpdb->get_var('SELECT MAX(id) FROM ' . $wpdb->prefix . 'mr_payment'));

		foreach($postdata['members'] as $member)
		{
			$id++;
			// calculate reference number
			$ref = "'" . mr_reference_count($id) . "'";

			$setval[] = '(' . implode(', ', array_merge($values, array('"' . $member . '"', $ref))) . ')';

		}
	}

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_payment (' . implode(', ', $keys) . ') VALUES ' . implode(', ', $setval);

	//echo '<div class="error"><p>' . $sql . '</p></div>';

	return $wpdb->query($sql);
}



function mr_insert_new_member($postdata)
{
	global $wpdb;

	$keys = array();
	$values = array();
	$required = array('user_login', 'access', 'firstname', 'lastname', 'birthdate',
		'address', 'zipcode', 'postal', 'phone', 'email', 'nationality', 'joindate',
		'passnro', 'notes', 'active', 'club');

	foreach($postdata as $k => $v)
	{
		if (in_array($k, $required))
		{
			// sanitize
			$keys[] = mr_urize($k);
			$values[] = "'" . mr_htmlent($v) . "'";
		}
	}

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_member (' . implode(', ', $keys) . ') VALUES(' . implode(', ', $values) . ')';

	//echo '<div class="error"><p>' . $sql . '</p></div>';

	return $wpdb->query($sql);
}

function mr_update_member_info($postdata)
{
	global $wpdb;

	$set = array();
	$required = array('user_login', 'access', 'firstname', 'lastname', 'birthdate',
		'address', 'zipcode', 'postal', 'phone', 'email', 'nationality', 'joindate',
		'passnro', 'notes', 'active', 'club');

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
 * Get a set of items from the given table, where should be like something.
 */
function mr_get_list($table, $where = '', $shouldbe = '', $order = '1 ASC')
{
	global $wpdb;
	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'mr_' . $table;
	if (isset($where) && $where != '')
	{
		$sql .= ' WHERE ' . $where . ' LIKE \'%' . $shouldbe . '%\'';
	}
	$sql .= ' ORDER BY ' . $order;

	return $wpdb->get_results($sql, ARRAY_A);
}


/**
 * Print out a form for creating new payments
 */
function mr_new_payment_form($members)
{
	global $wpdb;
	?>
	<form name="form1" method="post" action="" enctype="multipart/form-data">
		<input type="hidden" name="mr_submit_hidden_payment" value="Y" />
		<table class="form-table" id="createuser">
			<tr class="form-field">
				<th><?php echo __('Member'); ?> <span class="description">(<?php echo __('monivalinta'); ?>)</span></th>
				<td><select name="members[]" multiple="multiple" size="7" style="height: 8em;">
				<option value="">-</option>
				<?php
				foreach($members as $user)
				{
					echo '<option value="' . $user['id']. '">' . $user['name'] . ' (' . $user['id']. ')</option>';
				}
				?>
				</select></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Type'); ?> <span class="description">(<?php echo __('lienee aina vuosimaksu, Ainaisjäsenmaksu'); ?>)</span></th>
				<td><input type="text" name="type" value="vuosimaksu" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Amount'); ?> <span class="description">(<?php echo __('EUR'); ?>)</span></th>
				<td><input type="text" name="amount" value="10" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Deadline'); ?> <span class="description">(<?php echo __('3 viikkoa tulevaisuudessa'); ?>)</span></th>
				<td><input type="text" name="deadline" class="pickday" value="<?php
				echo date('Y-m-d', time() + 60*60*24*21);
				?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Valid until'); ?> <span class="description">(<?php echo __('kuluvan vuoden loppuun'); ?>)</span></th>
				<td><input type="text" name="validuntil" class="pickday" value="<?php
				echo date('Y') . '-12-31';
				?>" /></td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php __('Add bills') ?>" />
		</p>

	</form>
	<?php
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

	// Default values for an empty form
	$values = array(
		'id' => 0,
		'user_login' => 0,
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
		'notes' => '',
		'active' => 1,
		'club' => -1
	);
	$values = array_merge($values, $data);

	echo '<pre>';
	print_r($values);
	echo '</pre>';

	?>
	<form name="form1" method="post" action="<?php echo $action; ?>">
		<input type="hidden" name="mr_submit_hidden_member" value="Y" />
		<input type="hidden" name="id" value="<?php echo $values['id']; ?>" />
		<table class="form-table" id="createuser">
			<tr class="form-field">
				<th><?php echo __('WP käyttäjä'); ?> <span class="description">(<?php echo __('jos on jo olemassa'); ?>)</span></th>
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
				<th<?php echo __('>Etunimi'); ?></th>
				<td><input type="text" name="firstname" value="<?php echo $values['firstname']; ?>" /></td>
			</tr>
			<tr class="form-field form-required">
				<th><?php echo __('Sukunimi'); ?></th>
				<td><input type="text" name="lastname" value="<?php echo $values['lastname']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Syntymäpäivä'); ?> <span class="description">(YYYY-MM-DD)</span></th>
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
				<th><?php echo __('Sähköposti'); ?></th>
				<td><input type="text" name="email" value="<?php echo $values['email']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Kansallisuus'); ?></th>
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
				<th><?php echo __('Liittymispäivä'); ?> <span class="description">(YYYY-MM-DD)</span></th>
				<td><input type="text" name="joindate" class="pickday" value="<?php echo $values['joindate']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Yuishinkai passinumero'); ?></th>
				<td><input type="text" name="passnro" value="<?php echo $values['passnro']; ?>" /></td>
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
				$clubs = mr_get_list('club', '', '', 'name ASC');
				foreach($clubs as $club)
				{
					echo '<option value="' . $club['id'] . '"';
					if ($values['club'] == $club['id'])
					{
						echo ' selected="selected"';
					}
					echo '>' . $club['name'] . '</option>';
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

/**
 * Print out a form that is used to give grades.
 * @param $members Array of members, {id: , name: }
 */
function mr_grade_form($members)
{
	global $mr_grade_values;
	?>
	<form name="form1" method="post" action="" enctype="multipart/form-data">
		<input type="hidden" name="mr_submit_hidden_grade" value="Y" />
		<table class="form-table" id="createuser">
			<tr class="form-field">
				<th><?php echo __('Jäsen'); ?> <span class="description">(<?php echo __('valitse useampi painamalla Ctrl-näppäintä'); ?>)</span></th>
				<td>
					<select name="members[]" multiple="multiple" size="8">
					<option value="">-</option>
					<?php
					foreach($members as $user)
					{
						echo '<option value="' . $user['id']. '">' . $user['name'] . ' (' . $user['id']. ')</option>';
					}
					?>
					</select>
				</td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Vyöarvo'); ?> <span class="description">(<?php echo __('suluissa tietokantamerkintä'); ?>)</span></th>
				<td>
					<select name="grade">
					<option value="">-</option>
					<?php
					foreach($mr_grade_values as $k => $v)
					{
						echo '<option value="' . $k . '">' . $v . ' (' . $k . ')</option>';
					}
					?>
					</select>
				</td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Tyyppi'); ?> <span class="description">(<?php echo __('kummassa lajissa'); ?>)</span></th>
				<td>
					<label><input type="radio" name="type" value="Yuishinkai" checked="checked" /> Yuishinkai</label><br />
					<label><input type="radio" name="type" value="Kobujutsu" /> Kobujutsu</label>
				</td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Paikka'); ?> <span class="description">(<?php echo __('millä paikkakunnalla ja maassa jos ei Suomi'); ?>)</span></th>
				<td><input type="text" name="location" value="Turku" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Myöntäjä'); ?> <span class="description">(<?php echo __('kuka myönsi'); ?>)</span></th>
				<td><input type="text" name="nominator" value="Ilpo Jalamo, 6 dan" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Päivämäärä'); ?> <span class="description">(YYYY-MM-DD)</span></th>
				<td><input type="text" name="day" class="pickday" value="<?php
				echo date('Y-m-d', time() - 60*60*24*1);
				?>" /></td>
			</tr>

		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
		</p>

	</form>
	<?php
}


/**
 * Print out a form that is used to give a grade to a given member.
 * @param $member Array of member, {id: , name: }
 */
function mr_grade_quick_form($member)
{
	global $mr_grade_values;
	?>
	<form name="form1" method="post" action="" enctype="multipart/form-data">
		<input type="hidden" name="mr_submit_hidden_grade" value="Y" />
		<table class="form-table" id="creategrade">
			<tr>
				<th><?php echo __('Jäsen'); ?> <span class="description">(<?php echo __('valmiiksi valittu'); ?>)</span></th>
				<td><input name="member" type="hidden" value="<?php echo $member['id']; ?>" />
				<?php
				echo $member['name'];
				?>
				</td>
				<th><?php echo __('Vyöarvo'); ?> <span class="description">(<?php echo __('suluissa tietokantamerkintä'); ?>)</span></th>
				<td>
					<select name="grade">
					<option value="">-</option>
					<?php
					foreach($mr_grade_values as $k => $v)
					{
						echo '<option value="' . $k . '">' . $v . ' (' . $k . ')</option>';
					}
					?>
					</select>
				</td>
				<th><?php echo __('Tyyppi'); ?> <span class="description">(<?php echo __('kummassa lajissa'); ?>)</span></th>
				<td>
					<label><input type="radio" name="type" value="Yuishinkai" checked="checked" /> Yuishinkai</label><br />
					<label><input type="radio" name="type" value="Kobujutsu" /> Kobujutsu</label>
				</td>
			</tr>
			<tr>
				<th><?php echo __('Paikka'); ?> <span class="description">(<?php echo __('millä paikkakunnalla'); ?>)</span></th>
				<td><input type="text" name="location" value="Turku" /></td>
				<th><?php echo __('Myöntäjä'); ?> <span class="description">(<?php echo __('kuka myönsi'); ?>)</span></th>
				<td><input type="text" name="nominator" value="Ilpo Jalamo, 6 dan" /></td>
				<th><?php echo __('Päivämäärä'); ?> <span class="description">(YYYY-MM-DD)</span></th>
				<td><input type="text" name="day" class="pickday" value="<?php
				echo date('Y-m-d', time() - 60*60*24*1);
				?>" /></td>
			</tr>

		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
		</p>

	</form>
	<?php
}











/**
 * Counts and adds the check number used in the Finnish invoices.
 */
function mr_reference_count($given)
{
	$div = array (7, 3, 1);
	$len = strlen($given);
	$arr = str_split($given);
	$summed = 0;
	for ($i = $len - 1; $i >= 0; --$i)
	{
		$summed += $arr[$i] * $div[($len - 1 - $i) % 3];
	}
	$check = (10 - ($summed % 10)) %10;
	return $given.$check;
}

function mr_htmlent($str)
{
	return htmlentities(trim($str), ENT_QUOTES, 'UTF-8');
}

function mr_htmldec($str)
{
	return html_entity_decode(trim($str), ENT_QUOTES, 'UTF-8');
}

function mr_urize($str)
{
	$str = mb_strtolower($str, 'UTF-8');
	$str = mr_htmldec($str);
	$str = str_replace(array(' ', ',', '@', '$', '/', '&', '!', '=', '%'), '-', $str);
	$str = str_replace(array('--', '---'), '-', $str);
	return $str;
}
