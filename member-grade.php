<?php
/**
 * Plugin Name: Member Register
 * Grade related functions
 */



function mr_grade_new()
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_GRADE_MANAGE))
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;
	global $mr_grade_values;
	global $mr_grade_types;

	// Check for possible insert
    $hidden_field_name = 'mr_submit_hidden_grade';
    if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' )
	{
        if (mr_insert_new_grade($_POST))
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Uusi/uudet vyöarvo(t) lisätty') . '</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
    }

    ?>
	<div class="wrap">

		<h2><?php echo __('Myönnä vyöarvoja'); ?></h2>
		<?php
		$sql = 'SELECT CONCAT(lastname, " ", firstname) AS name, id FROM ' . $wpdb->prefix . 'mr_member ORDER BY lastname ASC';
		$users = $wpdb->get_results($sql, ARRAY_A);
		mr_grade_form($users);
		?>
	</div>

	<?php
}


function mr_grade_list()
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_GRADE_MANAGE))
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;
	
	if (isset($_GET['removegrade']) && is_numeric($_GET['removegrade']))
	{
		// Mark the given grade visible=0, so it can be recovered just in case...
		$id = intval($_GET['removegrade']);
		
		// http://codex.wordpress.org/Class_Reference/wpdb#UPDATE_rows
		/*
		$wpdb->update( $table, $data, $where, $format = null, $where_format = null );
		$wpdb->update( 
			'mr_grade', // ? prefix included
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
		*/
		
		$sql = 'UPDATE ' . $wpdb->prefix . 'mr_grade SET visible = 0 WHERE id = ' . $id . ' LIMIT 1';
		if ($wpdb->query($sql))
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Vyöarvo poistettu') . ' (' . $id . ')</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
	}
	
	echo '<div class="wrap">';
	echo '<h2>' . __('Vyöarvot') . '</h2>';
	echo '<p>' . __('Jäsenet heidän viimeisimmän vyöarvon mukaan.') . '</p>';
	echo '<p>' . __('Kenties tässä pitäisi olla filtterit vyöarvojen, seurojen ym mukaan.') . '</p>';
	mr_show_grades();
	echo '</div>';
}

/**
 * Show grades for a single member or for everyone
 */
function mr_show_grades($memberid = null)
{
	global $wpdb;
	global $userdata;
	global $mr_grade_values;
	global $mr_grade_types;
	
	$allowremove = true;
	
	// If no rights, only own info
	if (!mr_has_permission(MR_ACCESS_PAYMENT_MANAGE))
	{
		$memberid = $userdata->mr_memberid;
		$allowremove = false;
	}

	$where = '';
	$order = 'B.lastname ASC, ';
	if ($memberid != null && is_numeric($memberid))
	{
		$where = 'WHERE B.id = \'' . $memberid . '\' ';
		$order = '';
	}

	$sql = 'SELECT A.*, B.firstname, B.lastname, B.id AS memberid FROM ' . $wpdb->prefix .
		'mr_grade A LEFT JOIN ' . $wpdb->prefix .
		'mr_member B ON A.member = B.id AND A.visible = 1 ' . $where . 'ORDER BY ' . $order . 'A.day DESC';

	//echo '<div class="error"><p>' . $sql . '</p></div>';
	
	$res = $wpdb->get_results($sql, ARRAY_A);

	// id member grade type location nominator day visible
	
	
	if (count($res) > 0)
	{
		?>
		<table class="wp-list-table widefat tablesorter">

		<thead>
		<tr>
			<?php
			if ($memberid == null)
			{
				?>
				<th class="headerSortDown"><?php echo __('Last name'); ?></th>
				<th><?php echo __('First name'); ?></th>
				<?php
			}
			?>
			<th><?php echo __('Vyöarvo'); ?></th>
			<th><?php echo __('Laji'); ?></th>
			<th
			<?php
			if ($memberid != null)
			{
				echo ' class="headerSortUp"';
			}
			?>
			><?php echo __('Myöntö PVM'); ?></th>
			<th><?php echo __('Myöntäjä'); ?></th>
			<th><?php echo __('Paikka'); ?></th>
			<?php
			if ($allowremove)
			{
				echo '<th class="w8em">' . __('Poista') . '</th>';
			}
			?>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach($res as $grade)
		{
			echo '<tr id="grade_' . $grade['id'] . '">';
			if ($memberid == null)
			{
				$url = '<a href="' . admin_url('admin.php?page=member-register-control') .
					'&memberid=' . $grade['memberid'] . '" title="' . $grade['firstname'] .
					' ' . $grade['lastname'] . '">';
				echo '<td>' . $url . $grade['lastname'] . '</a></td>';
				echo '<td>' . $url . $grade['firstname'] . '</a></td>';
			}
			echo '<td>';
			if (array_key_exists($grade['grade'], $mr_grade_values))
			{
				echo $mr_grade_values[$grade['grade']];
			}
			echo '</td>';
			echo '<td title="' . $mr_grade_types[$grade['type']] . '">' . $grade['type'] . '</td>';
			echo '<td>' . $grade['day'] . '</td>';
			echo '<td>' . $grade['nominator'] . '</td>';
			echo '<td>' . $grade['location'] . '</td>';
			// set visible to 0, do not remove for real...
			if ($allowremove)
			{
				echo '<td><a rel="remove" href="' . admin_url('admin.php?page=member-grade-list') .
					'&amp;removegrade=' . $grade['id'] . '" title="' . __('Poista henkilön ' . $grade['firstname'] . ' ' . 
					$grade['lastname'] . 'vyöarvo') . ': ' . $grade['grade'] . '"><img src="' . 
					plugins_url('/images/delete-1.png', __FILE__) . '" alt="Poista" /></a></td>';
			}
			echo '</tr>';
		}
		?>
		</tbody>
		</table>
		<?php
	}
	else
	{
		echo '<p>Ei löytynyt lainkaan vyöarvoja ';
		if ($memberid != null)
		{
			echo 'tälle henkilölle';
		}
		else
		{
			echo 'näillä ehdoilla';
		}
		echo '</p>';
	}
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

		//echo $sql;

		return $wpdb->query($sql);
	}
	else
	{
		return false;
	}
}



/**
 * Print out a form that is used to give grades.
 * @param $members Array of members, {id: , name: }
 */
function mr_grade_form($members)
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_GRADE_MANAGE))
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}
	
	global $mr_grade_values;
	?>
	<form name="form1" method="post" action="" enctype="multipart/form-data">
		<input type="hidden" name="mr_submit_hidden_grade" value="Y" />
		<table class="form-table" id="mrform">
			<tr class="form-field">
				<th><?php echo __('Jäsen'); ?> <span class="description">(<?php echo __('valitse useampi painamalla Ctrl-näppäintä'); ?>)</span></th>
				<td>
					<select name="members[]" multiple="multiple" size="8" data-placeholder="Valitse jäsenet">
					<option value=""></option>
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
					<select name="grade" data-placeholder="Valitse vyöarvo">
					<option value=""></option>
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
				<td><input type="text" name="location" class="required" value="Turku" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Myöntäjä'); ?> <span class="description">(<?php echo __('kuka myönsi'); ?>)</span></th>
				<td><input type="text" name="nominator" class="required" value="Ilpo Jalamo, 6 dan" /></td>
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


