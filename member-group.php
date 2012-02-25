<?php
/**
 * Plugin Name: Member Register
 * Group related functions
 */


function mr_group_list()
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_GROUP_MANAGE))
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;

	echo '<div class="wrap">';
	
	if (isset($_GET['remove-group']) && is_numeric($_GET['remove-group']))
	{
		// Mark the given group visible=0, so it can be recovered just in case...
		$id = intval($_GET['remove-group']);
		$update = $wpdb->update(
			$wpdb->prefix . 'mr_group',
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
		
		if ($update !== false)
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Ryhmä poistettu') . ' (' . $id . ')</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
	}
		
	
	if (isset($_GET['group-member']) && is_numeric($_GET['group-member']))
	{
		$id = intval($_GET['group-member']);
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'mr_group WHERE id = ' . $id . ' AND visible = 1 LIMIT 1';
		$res = $wpdb->get_row($sql, ARRAY_A);

		if (isset($_GET['edit']))
		{
			echo '<h1>' . __('Muokkaa ryhmää') . ' ' . $res['title'] . '</h1>';
			$sql = 'SELECT member_id FROM ' . $wpdb->prefix . 'mr_group_member WHERE group_id = ' . $id . '';
			$results = $wpdb->get_results($sql, ARRAY_A);
			$members = array();
			foreach ($results as $r)
			{
				$members[] = $r['member_id'];
			}
			mr_new_group_form($members, $res['title'], $id);
		}
		else
		{
			
			// Check for possible update
			$hidden_field_name = 'mr_submit_hidden_group';
			if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y')
			{
				if (mr_group_update($id, $_POST))
				{
					echo '<div class="updated"><p>';
					echo '<strong>' . __('Ryhmän tiedot päivitetty') . '</strong>';
					echo '</p></div>';
				}
				else
				{
					echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
				}
			}
		
			echo '<h1>' . $res['title'] . '</h1>';


			echo '<p><a href="' . admin_url('admin.php?page=member-group-list') . '&amp;group-member=' .
				$id . '&amp;edit" title="' . __('Muokkaa tätä ryhmää') . '" class="button-primary">' . __('Muokkaa tätä ryhmää') . '</a></p>';
				
			echo '<h2>' . __('Aktiiviset jäsenet tässä ryhmässä.') . '</h2>';
			mr_show_members(array(
				'group' => $id,
				'active' => true
			));
		}
	}
	else
	{
		echo '<h2>' . __('Ryhmät') . '</h2>';
		
		
		// Check for possible insert
		$hidden_field_name = 'mr_submit_hidden_group';
		if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y')
		{
			if (mr_insert_new_group($_POST))
			{
				echo '<div class="updated"><p>';
				echo '<strong>' . __('Uusi ryhmä lisätty') . '</strong>';
				echo '</p></div>';
			}
			else
			{
				echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
			}
		}

		if (isset($_GET['create-group']))
		{
			mr_group_new();
		}
		else
		{
			echo '<p><a href="' . admin_url('admin.php?page=member-group-list') . '&amp;create-group"' .
					' title="' . __('Luo uusi ryhmä') . '" class="button-primary">' .
					__('Luo uusi ryhmä') . '</a></p>';

			mr_show_groups(null); // no specific member
		}
	}
	
	echo '</div>';
}




/**
 * Show list of groups for a specific member (int), or for all (null).
 */
function mr_show_groups($memberid = null)
{
	global $wpdb;
	global $userdata;
	global $mr_date_format;

	$allowremove = true; // visible = 0
	
	// If no rights, only own info
	if (!mr_has_permission(MR_ACCESS_GROUP_MANAGE))
	{
		$memberid = $userdata->mr_memberid;
		$allowremove = false;
	}
	
	$where = '';
	if ($memberid != null && is_numeric($memberid))
	{
		$where .= 'AND A.id IN (SELECT D.group FROM ' . $wpdb->prefix .
			'mr_group_member D WHERE D.member_id = \'' . $memberid . '\') ';
	}
	
	$sql = 'SELECT A.*, B.firstname, B.lastname, (SELECT COUNT(C.member_id) FROM ' . $wpdb->prefix .
		'mr_group_member C WHERE C.group_id = A.id) AS total FROM ' . $wpdb->prefix .
		'mr_group A LEFT JOIN ' . $wpdb->prefix .
		'mr_member B ON A.creator = B.id WHERE A.visible = 1 ' .
		$where . 'ORDER BY A.title DESC';
	$res = $wpdb->get_results($sql, ARRAY_A);


	if (count($res) > 0)
	{
		// id member reference type amount deadline paidday validuntil visible
		?>
		<table class="wp-list-table widefat tablesorter">
			<thead>
				<tr>
					<th class="headerSortUp"><?php echo __('Title'); ?></th>
					<th><?php echo __('Created by'); ?></th>
					<th><?php echo __('Last modification'); ?></th>
					<th><?php echo __('Jäseniä'); ?></th>
					
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
		foreach($res as $group)
		{
			echo '<tr id="group_' . $group['id'] . '">';
			echo '<td><a href="' . admin_url('admin.php?page=member-group-list') . '&amp;group-member=' . 
				$group['id'] . '" title="Näytä ryhmän jäsenet">' . $group['title'] . '</a></td>';
			echo '<td>';
			if (mr_has_permission(MR_ACCESS_GROUP_MANAGE))
			{
				$url = '<a href="' . admin_url('admin.php?page=member-register-control') .
					'&memberid=' . $group['creator'] . '" title="' . $group['firstname'] .
					' ' . $group['lastname'] . '">';
				echo $url . $group['firstname'] . ' ' . $group['lastname'] . '</a>';
			}
			else
			{
				echo $group['firstname'] . ' ' . $group['lastname'];
			}
			echo '</td>';
			echo '<td>' . date($mr_date_format, $group['modified']) . '</td>';
			echo '<td>' . $group['total'] . '</td>';

			// set visible to 0, do not remove for real...
			if ($allowremove)
			{
				echo '<td><a rel="remove" href="' . admin_url('admin.php?page=member-group-list') .
					'&amp;remove-group=' . $group['id'] . '" title="' . __('Poista ryhmä nimellä ' . $group['title'] .
					', jonka loi ') . $group['firstname'] . ' ' . $group['lastname'] . '"><img src="' . 
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
		echo '<p>Ei löytynyt lainkaan ryhmiä näillä ehdoilla</p>';
	}
}



function mr_group_new()
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_GROUP_MANAGE))
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;



    ?>
	<div class="wrap">
		<h2><?php echo __('Luo uusi ryhmä, jossa on vähintään yksi jäsen'); ?></h2>
		<?php
		mr_new_group_form();
		?>
	</div>

	<?php

}

function mr_insert_new_group($postdata)
{
	global $wpdb;
	global $userdata;

	if (isset($postdata['members']) && is_array($postdata['members']) && 
		count($postdata['members']) > 0 && isset($postdata['title']) && $postdata['title'] != '')
	{
		$values = array(
			"'" . mr_htmlent($postdata['title']) . "'",
			$userdata->mr_memberid, 
			time()
		);
		$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_group (title, creator, modified) VALUES (' . implode(', ', $values) . ')';
		if ($wpdb->query($sql))
		{
			$id = $wpdb->insert_id;
			$setval = array();

			foreach($postdata['members'] as $member)
			{
				$setval[] = '(' . $id . ', ' . intval($member) . ')';
			}
			$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_group_member (group_id, member_id) VALUES ' . implode(', ', $setval);
			return $wpdb->query($sql);
		}
	}

	return false;
}

function mr_group_update($id, $postdata)
{
	global $wpdb;
	global $userdata;


	if (isset($postdata['members']) && is_array($postdata['members']) && 
		count($postdata['members']) > 0 && isset($postdata['title']) && $postdata['title'] != '')
	{
		// This will fail if title was not changed...
		$update = $wpdb->update(
			$wpdb->prefix . 'mr_group',
			array(
				'title' => $postdata['title']
			),
			array(
				'id' => $id
			),
			array(
				'%s'
			),
			array(
				'%d'
			)
		);

		// Remove those that exists
		$sql = 'DELETE FROM ' . $wpdb->prefix . 'mr_group_member WHERE group_id = ' . $id;
		
		// Insert current
		if ($wpdb->query($sql))
		{
			$setval = array();

			foreach($postdata['members'] as $member)
			{
				$setval[] = '(' . $id . ', ' . intval($member) . ')';
			}
			$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_group_member (group_id, member_id) VALUES ' . implode(', ', $setval);
			return $wpdb->query($sql);
		}
	}

	return false;
}


/**
 * Print out a form for creating new groups
 * $members array of pre selcted members by id
 */
function mr_new_group_form($members = null, $title = '', $id = null)
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_GROUP_MANAGE))
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}
	
	global $wpdb;
	
	$action = admin_url('admin.php?page=member-group-list');
	if ($id != null)
	{
		$action .= '&amp;group-member=' . $id;
	}
	?>
	<form name="form1" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" autocomplete="off">
		<input type="hidden" name="mr_submit_hidden_group" value="Y" />
		<table class="form-table" id="mrform">
			<tr class="form-field">
				<th><?php echo __('Title'); ?> <span class="description">(<?php echo __('nimi jolla ryhmä on helppo tunnistaa'); ?>)</span></th>
				<td><input type="text" name="title" value="<?php echo $title; ?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Members'); ?> <span class="description">(<?php echo __('monivalinta'); ?>)</span></th>
				<td><select name="members[]" multiple="multiple" size="7" style="height: 8em;" data-placeholder="Valitse jäsenet">
				<option value=""></option>
				<?php
				$sql = 'SELECT CONCAT(lastname, ", ", firstname) AS name, id FROM ' . $wpdb->prefix . 'mr_member WHERE active = 1 ORDER BY lastname ASC';
				$users = $wpdb->get_results($sql, ARRAY_A);
				foreach($users as $user)
				{
					echo '<option value="' . $user['id']. '"';
					if ($members != null && in_array($user['id'], $members))
					{
						echo ' selected="selected"';
					}
					echo '>' . $user['name'] . ' (' . $user['id']. ')</option>';
				}
				?>
				</select></td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="Lisää ryhmä" />
		</p>

	</form>
	<?php
}
