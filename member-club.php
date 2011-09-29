<?php
/**
 * Plugin Name: Member Register
 * Club related functions.
 */


function mr_club_list()
{
	if (!current_user_can('create_users') && $userdata->mr_access >= 8)
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;
	
	echo '<div class="wrap">';
	
	if (isset($_GET['removeclub']) && is_numeric($_GET['removeclub']))
	{
		// Mark the given club visible=0, so it can be recovered just in case...
		$id = intval($_GET['removeclub']);
		$sql = 'UPDATE ' . $wpdb->prefix . 'mr_club SET visible = 0 WHERE id = ' . $id;
		if ($wpdb->query($sql))
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Seura poistettu') . ' (' . $id . ')</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
	}
	
	if (isset($_GET['club']) && is_numeric($_GET['club']))
	{
		$id = intval($_GET['club']);
		
		// Was there an update of this club?
		$hidden_field_name = 'mr_submit_hidden_club';
		if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' && $userdata->mr_access >= 3)
		{
			$_POST['id'] = $id;
			if (mr_update_club($_POST))
			{
				echo '<div class="updated"><p>';
				echo '<strong>' . __('Seuran tiedot päivitetty.') . '</strong>';
				echo '</p></div>';
			}
			else
			{
				echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
			}
		}
			
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'mr_club WHERE id = ' . $id . ' AND visible = 1 LIMIT 1';
		$res = $wpdb->get_row($sql, ARRAY_A);
		
		if (isset($_GET['edit']))
		{
			echo '<h1>' . __('Modify') . ' ' . $res['title'] . '</h1>';
			mr_club_form($res);
		}
		else 
		{
			echo '<h1>' . $res['title'] . '</h1>';
			
		
			echo '<p><a href="' . admin_url('admin.php?page=member-club-list') . '&club=' .
				$id . '&edit" title="' . __('Muokkaa tätä seuraa') . '" class="button-primary">' . __('Muokkaa tätä seuraa') . '</a></p>';
			echo '<h2>' . __('Aktiiviset jäsenet tässä seurassa.') . '</h2>';
			mr_show_members(array(
				'club' => intval($_GET['club']),
				'active' => true
			));
		}		
	}
	else 
	{
		echo '<h1>' . __('Jäsenseurat') . '</h1>';
		echo '<p>' . __('Suomen Yuishinkai-liiton Jäsenseurat.') . '</p>';
		echo '<p>' . __('Paikat joissa harjoitellaan Yuishinkai karatea ja/tai Ryukyu kobujutsua.') . '</p>';
		
		// Was there an insert of a new club?
		$hidden_field_name = 'mr_submit_hidden_club';
		if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' && $userdata->mr_access >= 3)
		{
			if (mr_insert_new_club($_POST))
			{
				echo '<div class="updated"><p>';
				echo '<strong>' . __('Uusi seura lisätty.') . '</strong>';
				echo '</p></div>';
			}
			else
			{
				echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
			}
		}
		mr_show_clubs();
	}
	
	echo '</div>';
}

function mr_club_form($data = null)
{
	$values = array(
		'title' => '',
		'address' => ''
	);
	$action = admin_url('admin.php?page=member-club-list');
	
	if (is_array($data))
	{
		// Assume this to be an edit for existing
		$values = array_merge($values, $data);
		$action .= '&club=' . $values['id'];
	}

	?>
	<form name="form1" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data">
		<input type="hidden" name="mr_submit_hidden_club" value="Y" />
		<table class="form-table" id="createuser">
			<tr class="form-field">
				<th><?php echo __('Name'); ?> <span class="description">(<?php echo __('otsikko'); ?>)</span></th>
				<td><input type="text" name="title" value="<?php echo $values['title']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Address'); ?> <span class="description">(<?php echo __('otsikko'); ?>)</span></th>
				<td><input type="text" name="address" value="<?php echo $values['address']; ?>" /></td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Publish') ?>" />
		</p>

	</form>
	<?php
}


function mr_show_clubs()
{
	global $wpdb;
	
	// id, title, address, visible
	
	$sql = 'SELECT A.*, COUNT(B.id) AS members FROM ' . $wpdb->prefix . 
		'mr_club A LEFT JOIN ' . $wpdb->prefix . 
		'mr_member B ON B.club = A.id WHERE A.visible = 1 GROUP BY B.club ORDER BY A.title ASC';

	echo '<div class="error"><p>' . $sql . '</p></div>';
	
	$clubs = $wpdb->get_results($sql, ARRAY_A);
	
	$allowremove = true;
	
	?>
	<table class="wp-list-table widefat tablesorter">
	<thead>
	<tr>
		<th class="headerSortDown"><?php echo __('Nimi'); ?></th>
		<th><?php echo __('Osoite'); ?></th>
		<th><?php echo __('Aktiivisia jäseniä'); ?></th>
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
	foreach($clubs as $club)
	{
		$url = '<a href="' . admin_url('admin.php?page=member-club-list') . '&club=' . $club['id'] .
			'" title="' . __('List of active members in the club called:') . ' ' .$club['title'] . '">';
		echo '<tr id="user_' . $club['id'] . '">';
		echo '<td>' . $url . $club['title'] . '</a></td>';
		echo '<td>' . $url . $club['address'] . '</a></td>';
		echo '<td>' . $url . $club['members'] . '</a></td>';
		// set visible to 0, do not remove for real...
		if ($allowremove)
		{
			echo '<td><a href="' . admin_url('admin.php?page=member-grade-list') .
				'&removegrade=' . $club['id'] . '" title="' . __('Poista seura') . ' ' .
				$club['title'] . '">X</a></td>';
		}
		echo '</tr>';
	}
	?>
	</tbody>
	</table>
	<?php
}


function mr_insert_new_club($postdata)
{
	global $wpdb;

	$values = array(
		"'" . mr_htmlent($postdata['title']) . "'",
		"'" . mr_htmlent($postdata['address']) . "'"
	);

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_club (title, address) VALUES('
		. implode(', ', $values) . ')';

	//echo '<div class="error"><p>' . $sql . '</p></div>';

	return $wpdb->query($sql);
}

function mr_update_club($postdata)
{
	global $wpdb;

	$sql = 'UPDATE ' . $wpdb->prefix . 'mr_club SET title = \'' . mr_htmlent($postdata['title']) .
		'\', address = \'' . mr_htmlent($postdata['address']) . '\'';

	//echo '<div class="error"><p>' . $sql . '</p></div>';

	return $wpdb->query($sql);
}




