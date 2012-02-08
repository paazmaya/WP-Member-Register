<?php
/**
 * Plugin Name: Member Register
 * Club related functions.
 */


 // TODO: has many calls to access level checking but kept until decided if they are needed...
 // might be that there will be more levels thus checks needed

function mr_club_list()
{
	global $wpdb;
	global $userdata;

	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_CLUB_MANAGE))
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}


	echo '<div class="wrap">';

	if (isset($_GET['removeclub']) && is_numeric($_GET['removeclub']) && mr_has_permission(MR_ACCESS_CLUB_MANAGE))
	{
		// Mark the given club visible=0, so it can be recovered just in case...
		$update = $wpdb->update(
			$wpdb->prefix . 'mr_club',
			array(
				'visible' => 0
			),
			array(
				'id' => $_GET['removeclub']
			),
			array(
				'%d'
			),
			array(
				'%d'
			)
		);

		if ($update)
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Seura poistettu') . ' (' . $_GET['removeclub'] . ')</strong>';
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
		if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' && mr_has_permission(MR_ACCESS_CLUB_MANAGE))
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
			echo '<p>' . $res['address'] . '</p>';


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
		if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' && mr_has_permission(MR_ACCESS_CLUB_MANAGE))
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

		if (isset($_GET['createclub']))
		{
			mr_club_form();
		}
		else
		{
			echo '<p><a href="' . admin_url('admin.php?page=member-club-list') . '&createclub"' .
					' title="' . __('Luo uusi seura') . '" class="button-primary">' .
					__('Luo uusi seura') . '</a></p>';

			mr_show_clubs();
		}
	}

	echo '</div>';
}

function mr_club_form($data = null)
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_CLUB_MANAGE))
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}

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
	<form name="form1" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data"  autocomplete="on">
		<input type="hidden" name="mr_submit_hidden_club" value="Y" />
		<table class="form-table" id="mrform">
			<tr class="form-field">
				<th><?php echo __('Name'); ?> <span class="description">(<?php echo __('otsikko'); ?>)</span></th>
				<td><input type="text" name="title" class="required" value="<?php echo $values['title']; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Address'); ?> <span class="description">(<?php echo __('otsikko'); ?>)</span></th>
				<td><input type="text" name="address" class="required" value="<?php echo $values['address']; ?>" /></td>
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
	global $userdata;

	// id, title, address, visible

	$sql = 'SELECT A.*, COUNT(B.id) AS members FROM ' . $wpdb->prefix .
		'mr_club A LEFT JOIN ' . $wpdb->prefix .
		'mr_member B ON B.club = A.id WHERE A.visible = 1 GROUP BY A.id ORDER BY A.title ASC';

	//echo '<div class="error"><p>' . $sql . '</p></div>';

	$clubs = $wpdb->get_results($sql, ARRAY_A);

	$allowremove = false;
	if (mr_has_permission(MR_ACCESS_CLUB_MANAGE))
	{
		$allowremove = true;
	}

	?>
	<table class="wp-list-table widefat tablesorter">
	<thead>
	<tr>
		<th class="headerSortDown"><?php echo __('Nimi'); ?></th>
		<th><?php echo __('Address'); ?></th>
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
			echo '<td><a rel="remove" href="' . admin_url('admin.php?page=member-club-list') .
				'&amp;removeclub=' . $club['id'] . '" title="' . __('Poista seura') . ': ' .
				$club['title'] . '"><img src="' . plugins_url('/images/delete-1.png', __FILE__) . '" alt="Poista" /></a></td>';
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

	if (isset($postdata['title']) && $postdata['title'] != '' && isset($postdata['address']) && $postdata['address'] != '')
	{
		return $wpdb->insert(
			$wpdb->prefix . 'mr_club',
			array(
				'title' => $postdata['title'],
				'address' => $postdata['address']
			),
			array(
				'%s',
				'%s'
			)
		);
	}
	return false;
}

function mr_update_club($postdata)
{
	global $wpdb;

	if (isset($postdata['title']) && $postdata['title'] != '' && isset($postdata['address']) && $postdata['address'] != '')
	{
		return $wpdb->update(
			$wpdb->prefix . 'mr_club',
			array(
				'title' => $postdata['title'],
				'address' => $postdata['address']
			),
			array(
				'id' => $postdata['id']
			),
			array(
				'%s',
				'%s'
			),
			array(
				'%d'
			)
		);
	}
	return false;
}




