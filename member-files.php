<?php
/**
 * Plugin Name: Member Register
 * Files for members only
 * - Access can be for all, club, member level
 * All documents such as word, etc are converted to pdf which is transformed for each member separately
 * in order to have their name as a watermark.
 */


 /**
  * Called if $_GET['download'] is set
  */
function mr_file_download($get)
{
	global $mr_file_base_directory;
	global $wpdb;
	
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_FILES_VIEW))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	// $get should contain download: id / dir / basename
	// forward slash will be always available, but dir not
	
	$parts = explode('/', $get);
	if (count($parts) < 2)
	{
		wp_die( __('Not available.') );
	}
	
	//$basename = array_pop($parts);
	$id = intval(array_shift($parts));
	//$dir = implode('/', $parts);
	
	$sql = 'SELECT basename, directory FROM ' . $wpdb->prefix . 'mr_file WHERE visible = 1 AND id = \'' .
		$id . '\' LIMIT 1';
	$res = $wpdb->get_row($sql, ARRAY_A);
	
	if ($res)
	{
		$real = realpath($mr_file_base_directory . '/' . $res['directory'] . '/' . $id . '_' . $res['basename']);
		if (strpos($real, $mr_file_base_directory) !== false)
		{
			$fp = fopen($real, 'r');
			
			header("Content-Type: application/force-download");
			header("Content-Disposition: attachment; filename=" . $res['basename']);
			header("Content-length: " . filesize($real));
			header("Expires: " . gmdate("D, d M Y H:i:s", mktime(date("H")+2, date("i"), date("s"), date("m"), date("d"), date("Y")))." GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s")." GMT");
			header("Cache-Control: no-cache, must-revalidate");
			header("Pragma: no-cache");
			
			fpassthru($fp);
			fclose($fp);
		}
	}
	else
	{
		wp_die( __('Not found.') );
	}

	exit();
}


/**
 * Show a table of members based on the given filter if any.
 */
function mr_files_list()
{
	global $wpdb;
	global $userdata;
	global $mr_access_type;
	global $mr_grade_values;
	global $mr_martial_arts;
	global $mr_date_format;
	global $mr_file_base_directory;
	
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_FILES_VIEW))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	if (isset($_GET['remove-file']) && is_numeric($_GET['remove-file']) && mr_has_permission(MR_ACCESS_FILES_MANAGE))
	{
		$id = intval($_GET['remove-file']);
		
		$update = $wpdb->update(
			$wpdb->prefix . 'mr_file',
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
		
		// How about moving the file?
		$sql = 'SELECT basename, directory FROM ' . $wpdb->prefix . 'mr_file WHERE id = ' . $id . ' LIMIT 1';
		$info = $wpdb->get_row($sql, ARRAY_A);
		
		// Because of this _remove directory, the user created dirs cannot begin with _
		$removed = $mr_file_base_directory . '/_removed';
		if (!file_exists($removed))
		{
			umask(0000);
			mkdir($removed, 0775);
		}

		rename($mr_file_base_directory . '/' . $info['directory'] . '/' . $id . '_' . $info['basename'], 
			$removed . '/' . $id . '_' . $info['basename']);
		
		if ($update)
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Valittu tiedosto poistettu.') . '</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
	}
	
	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'mr_member WHERE id = ' . $userdata->mr_memberid . ' LIMIT 1';
	$userinfo = $wpdb->get_row($sql, ARRAY_A);
	
	if (mr_has_permission(MR_ACCESS_FILES_MANAGE))
	{
		$where = '';
	}
	else
	{
		$where = 'AND (A.clubonly = 0 OR A.clubonly = \'' . $userinfo['club'] . '\') ' .
			'AND (A.artonly = \'\' OR A.artonly = \'' . $userinfo['martial'] . '\') ' .
			'AND (A.mingrade = \'\' OR A.mingrade IN (SELECT grade FROM ' . 
			$wpdb->prefix . 'mr_grade WHERE member = \'' . $userdata->mr_memberid . '\')) ' .
			'AND (A.grouponly = 0 OR A.grouponly IN (SELECT group_id FROM ' . 
			$wpdb->prefix . 'mr_group_member WHERE member_id = \'' . $userdata->mr_memberid . '\') )';
	}
	$sql = 'SELECT A.*, B.firstname, B.lastname, C.title AS clubname, D.title AS groupname FROM ' . 
		$wpdb->prefix . 'mr_file A LEFT JOIN ' . $wpdb->prefix . 
		'mr_member B ON A.uploader = B.id LEFT JOIN ' . $wpdb->prefix . 
		'mr_club C ON A.clubonly = C.id LEFT JOIN ' . $wpdb->prefix . 
		'mr_group D ON A.grouponly = D.id WHERE A.visible = 1 ' . $where . ' ORDER BY A.basename ASC';

	//echo '<div class="error"><p>' . $sql . '</p></div>';
	
	echo '<div class="wrap">';
	
	$files = $wpdb->get_results($sql, ARRAY_A);
	?>
	<h2><?php echo __('Jäsenten tiedostot'); ?></h2>
	<table class="wp-list-table widefat tablesorter">
	<thead>
	<tr>
		<th class="headerSortDown"><?php echo __('Base name'); ?></th>
		<th><?php echo __('Directory'); ?></th>
		<th><?php echo __('Size'); ?> (KB)</th>
		<th><?php echo __('Uploaded'); ?></th>
		<th><?php echo __('Uploader'); ?></th>
		<?php
		if (mr_has_permission(MR_ACCESS_FILES_MANAGE))
		{
			echo '<th>' . __('Restrictions') . '</th>';
			echo '<th>' . __('Remove') . '</th>';
		}
		?>
	</tr>
	</thead>
	<tbody>

	<?php
	$out = '';
	foreach($files as $file)
	{
		$path = realpath($mr_file_base_directory . '/' . $file['directory'] . '/' . $file['id'] . '_' . $file['basename']);
		
		$out .= '<tr id="user_' . $file['id'] . '">';
		$out .= '<td';
		if (!file_exists($path))
		{
			$out .= ' class="redback" title="Tiedostoa ei löydy">' . $file['basename'];
		}
		else
		{
			$a = admin_url('admin.php?page=member-files') . '&amp;download=' . $file['id'];
			if ($file['directory'] != '')
			{
				$a .= urlencode('/' . $file['directory']);
			}
			$a .= urlencode('/' . $file['basename']);
			
			$out .= '><a href="' . $a . '" title="Lataa ' . 
				$file['basename'] . ' koneellesi">' . $file['basename'] . '</a>';
		}
		$out .= '</td>';
		$out .= '<td>' . $file['directory'] . '</td>';
		$out .= '<td>' . round($file['bytesize'] / 1024) . '</td>';
		$out .= '<td>' . date($mr_date_format, $file['uploaded']) . '</td>';
		$out .= '<td>';
		if (mr_has_permission(MR_ACCESS_MEMBERS_VIEW))
		{
			$out.= '<a href="' . admin_url('admin.php?page=member-register-control') .
				'&amp;memberid=' . $file['uploader'] . '" title="' . $file['firstname'] .
				' '	. $file['lastname'] . '">' . $file['firstname'] . ' ' . $file['lastname'] . '</a>';
		}
		else
		{
			$out.= $file['firstname'] . ' ' . $file['lastname'];
		}
		$out.= '</td>';
		if (mr_has_permission(MR_ACCESS_FILES_MANAGE))
		{
			$out .= '<td>';
			$restrictions = array();
			if ($file['clubonly'] != 0)
			{
				$restrictions[] = 'Vain seura: ' . $file['clubname'];
			}
			if ($file['mingrade'] != '')
			{
				$restrictions[] = 'Alin vyöarvo: ' . $mr_grade_values[$file['mingrade']];
			}
			if ($file['artonly'] != '')
			{
				$restrictions[] = 'Vain laji: ' . $mr_martial_arts[$file['artonly']];
			}
			if ($file['grouponly'] != 0)
			{
				$restrictions[] = 'Vain ryhmä: ' . $file['groupname'];
			}
			$out .= implode('<br />', $restrictions);
			$out .= '</td>';
			
			$out .= '<td>';
			$out .= '<a rel="remove" href="' . admin_url('admin.php?page=member-files') .
				'&amp;remove-file=' . $file['id'] . '" title="' . __('Poista tämä tiedosto') . ': ' .
				$file['basename'] . '"><img src="' . plugins_url('/images/delete-1.png', __FILE__) . '" alt="Poista" /></a>';
			$out .= '</td>';
		}
		$out .= '</tr>';
	}
	echo $out;
	?>
	</tbody>
	</table>
	<?php
	
	echo '</div>';
}


function mr_files_new()
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_FILES_MANAGE))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	global $wpdb;
	global $userdata;
	global $mr_grade_values;
	global $mr_martial_arts;
	global $mr_file_base_directory;
	
	// Check for possible insert
    $hidden_field_name = 'mr_submit_hidden_file';
    if( isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' && isset($_FILES['hoplaa']) )
	{
		/*
		echo '<pre>';
		print_r($_FILES);
		echo '</pre>';
		*/
		$dir = isset($_POST['directory']) ? mr_urize($_POST['directory']) : '';
		$mingrade = (isset($_POST['grade']) && $_POST['grade'] != '' && array_key_exists($_POST['grade'], $mr_grade_values)) ? $_POST['grade'] : '';
		$clubonly = (isset($_POST['club']) && is_numeric($_POST['club'])) ? $_POST['club'] : 0;
		$artonly = (isset($_POST['art']) && $_POST['art'] != '' && array_key_exists($_POST['art'], $mr_martial_arts)) ? $_POST['art'] : '';
		$grouponly = isset($_POST['group']) && is_numeric($_POST['group']) ? intval($_POST['group']) : '';
	
        if (mr_insert_new_file($_FILES['hoplaa'], $dir, $mingrade, $clubonly, $artonly, $grouponly))
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Uusi tiedosto lisätty, nimellä:') . ' ' . $_FILES['hoplaa']['name'] . ', kansioon: ' . $dir . '.</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
    }

    ?>
	<div class="wrap">
		<h2><?php echo __('Lisää uusi tiedosto'); ?></h2>
		<form name="form1" method="post" action="<?php echo admin_url('admin.php?page=member-files-new'); ?>" enctype="multipart/form-data" autocomplete="on">
			<datalist id="directories">
				<?php
				$dirs = glob($mr_file_base_directory . '/*', GLOB_ONLYDIR);
				foreach ($dirs as $dir)
				{
					$base = basename($dir);
					if ($base != '_removed')
					{
						echo '<option value="' . $base . '" />';
					}
				}
				?>
			</datalist> 
			<input type="hidden" name="mr_submit_hidden_file" value="Y" />
			<table class="form-table" id="mrform">
				<tr class="form-field">
					<th><?php echo __('Valitse tiedosto'); ?><span class="description">(max 10 MB)</span></th>
					<td><input type="file" name="hoplaa" value="" /></td>
				</tr>
				<tr class="form-field">
					<th><?php echo __('Kansio'); ?><span class="description">(parempaa järjestyksenpitoa varten, yksi sana)</span></th>
					<td><input type="text" name="directory" value="" list="directories" /></td>
				</tr>
				<tr class="form-field">
					<th><?php echo __('Seura'); ?><span class="description">(rajoita vain tiettyyn seuraan kuuluville)</span></th>
					<td><select name="club" data-placeholder="Valitse seura">
						<option value=""></option>
						<?php
						$clubs = mr_get_list('club', 'visible = 1', '', 'title ASC');
						foreach($clubs as $club)
						{
							echo '<option value="' . $club['id'] . '">' . $club['title'] . '</option>';
						}
						?>
					</select></td>
				</tr>
				<tr class="form-field">
					<th><?php echo __('Vyöarvo'); ?><span class="description">(rajoita vain tietyn vyön suorittaneille, joka on merkitty rekisteriin)</span></th>
					<td><select name="grade" data-placeholder="Valitse alin vyöarvo">
						<option value=""></option>
						<?php
						foreach ($mr_grade_values as $key => $val)
						{
							echo '<option value="' . $key . '">' . $val . '</option>';
						}
						?>
					</select></td>
				</tr>
				<tr class="form-field">
					<th><?php echo __('Päälaji'); ?><span class="description">(rajoita vain tämän lajin päälajikseen valinneille)</span></th>
					<td><select name="art" data-placeholder="Valitse laji">
						<option value=""></option>
						<?php
						foreach ($mr_martial_arts as $key => $val)
						{
							echo '<option value="' . $key . '">' . $val . '</option>';
						}
						?>
					</select></td>
				</tr>
				<tr class="form-field">
					<th><?php echo __('Ryhmä'); ?><span class="description">(rajoita vain tiettyyn ryhmään kuuluville)</span></th>
					<td><select name="group" data-placeholder="Valitse ryhmä">
						<option value=""></option>
						<?php
						$groups = mr_get_list('group', 'visible = 1', '', 'title ASC');
						foreach($groups as $group)
						{
							echo '<option value="' . $group['id'] . '">' . $group['title'] . '</option>';
						}
						?>
					</select></td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
			</p>

		</form>
	</div>

	<?php
}
 

function mr_insert_new_file($filesdata, $dir = '', $mingrade = '', $clubonly = 0, $artonly = '', $grouponly = '')
{
	global $wpdb;
	global $userdata;
	global $mr_file_base_directory;

	// Should not bewgin with _ nor .
	$dir = mr_urize($dir);
	$first = substr($dir, 0, 1);
	if ($first == '_' || $first == '.')
	{
		$dir = substr($dir, 1);
	}
	
	$values = array(
		'basename' => mr_urize(basename($filesdata['name'])),
		'bytesize' => $filesdata['size'],
		'directory' => $dir,
		'uploader' => $userdata->mr_memberid,
		'uploaded' => time(),
		'mingrade' => $mingrade, // if not empty, checked
		'clubonly' => $clubonly, // if not zero, checked
		'artonly' => $artonly, // if not empty, checked
		'grouponly' => $grouponly, // if not empty, checked
		'visible' => 1
	);
	
	umask(0000);
	
	if (!file_exists($mr_file_base_directory))
	{
		mkdir($mr_file_base_directory, 0775);
	}
	
	$target = $mr_file_base_directory . '/' . $dir;
	
	if (!file_exists($target))
	{
		mkdir($target, 0775);
	}

	$insert = $wpdb->insert(
		$wpdb->prefix . 'mr_file',
		$values,
		array(
			'%s', // basename
			'%d', // bytesize
			'%s', // directory
			'%d', // uploader id
			'%d', // uploaded time
			'%s', // mingrade
			'%d', // clubonly
			'%s', // artonly
			'%d', // grouponly
			'%d' // visible
		)
	);
	
	if ($insert !== false)
	{
		$target = $target . '/' . $wpdb->insert_id . '_' . $values['basename'];
		
		if (move_uploaded_file($filesdata['tmp_name'], $target))
		{
			chmod($target, 0775);
		} 
		else 
		{
			return false;
		}
	}
	return $insert;
}

 
 