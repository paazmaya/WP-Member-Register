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
	
	// $get should contain download: dir / basename
	// forward slash will be always available
	
	$parts = explode('/', str_replace(array('..', '%', ' '), '-', $get));
	$basename = array_pop($parts);
	$dir = implode('/', $parts);
	
	$sql = 'SELECT access FROM ' . $wpdb->prefix . 'mr_file WHERE visible = 1 AND directory = \'' .
		$dir . '\' AND basename = \'' . $basename . '\' LIMIT 1';
	$res = $wpdb->get_results($sql, ARRAY_A);
	
	if (count($res) == 1) // && $res['0']['access'] == '1')
	{
		$real = realpath($mr_file_base_directory . '/' . $dir . '/' . $basename);
		if (strpos($real, $mr_file_base_directory) !== false)
		{
			$fp = fopen($real, 'r');
			header("Content-Type: application/force-download");
			header("Content-Disposition: attachment; filename=" . $basename);
			header("Content-length: " . filesize($real));
			header("Expires: ".gmdate("D, d M Y H:i:s", mktime(date("H")+2, date("i"), date("s"), date("m"), date("d"), date("Y")))." GMT");
			header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
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
	global $mr_date_format;
	global $mr_file_base_directory;
	
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_FILES_VIEW))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	$sql = 'SELECT A.*, B.firstname, B.lastname FROM ' . $wpdb->prefix .
		'mr_file A LEFT JOIN ' . $wpdb->prefix . 
		'mr_member B ON A.uploader = B.id WHERE A.visible = 1 ORDER BY A.basename ASC';

	//echo '<div class="error"><p>' . $sql . '</p></div>';
	
	$files = $wpdb->get_results($sql, ARRAY_A);
	?>
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
		$path = realpath($mr_file_base_directory . '/' . $file['directory'] . '/' . $file['basename']);
		
		$out .= '<tr id="user_' . $file['id'] . '">';
		$out .= '<td';
		if (!file_exists($path))
		{
			$out .= ' class="redback" title="Tiedostoa ei löydy">' . $file['basename'];
		}
		else
		{
			$out .= '><a href="' . admin_url('admin.php?page=member-files') . '&amp;download=' . 
				urlencode($file['directory'] . '/' . $file['basename']) . '" title="Lataa ' . 
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
			$out .= '</td>';
		}
		$out .= '</tr>';
	}
	echo $out;
	?>
	</tbody>
	</table>
	<?php
}


function mr_files_new()
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_FILES_MANAGE))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	global $wpdb;
	global $userdata;
	
	// Check for possible insert
    $hidden_field_name = 'mr_submit_hidden_file';
    if( isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' && isset($_FILES['hoplaa']) )
	{
		/*
		echo '<pre>';
		print_r($_FILES);
		echo '</pre>';
		*/
	
        if (mr_insert_new_file($_FILES['hoplaa']))
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Uusi tiedosto lisätty, nimellä:') . ' ' . $_FILES['hoplaa']['name'] . '</strong>';
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
		<form name="form1" method="post" action="<?php echo admin_url('admin.php?page=member-files-new'); ?>" enctype="multipart/form-data">
			<input type="hidden" name="mr_submit_hidden_file" value="Y" />
			<table class="form-table" id="createfile">
				<tr class="form-field">
					<th><?php echo __('Valitse tiedosto'); ?></th>
					<td><input type="file" name="hoplaa" value="" /></td>
				</tr>
			</table>

			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
			</p>

		</form>
	</div>

	<?php
}
 

function mr_insert_new_file($filesdata, $dir = '')
{
	global $wpdb;
	global $userdata;
	global $mr_file_base_directory;

	$dir = strtolower(str_replace(array('..', ' '), '-', str_replace(array('..', '/', '\\', '...', '%'), '', $dir)));
	
	$values = array(
		'basename' => strtolower(str_replace(array('..', '%', ' '), '-', basename($filesdata['name']))),
		'bytesize' => 0,
		'directory' => $dir,
		'uploader' => $userdata->mr_memberid,
		'uploaded' => time(),
		'access' => 1,
		'visible' => 1
	);
	
	//if (dir_exists($mr_file_base_directory . '/' . $dir
	
	if (!file_exists($mr_file_base_directory))
	{
		mkdir($mr_file_base_directory);
	}
	
	$target = realpath($mr_file_base_directory . '/' . $dir);
	
	if (!file_exists($target))
	{
		mkdir($target);
	}

	if (move_uploaded_file($filesdata['tmp_name'], $target . '/' . $values['basename']))
	{
		$values['bytesize'] = $filesdata['size'];
	} 
	else 
	{
		return false;
	}
	
	return $wpdb->insert(
		$wpdb->prefix . 'mr_file',
		$values,
		array(
			'%s', // basename
			'%d', // bytesize
			'%s', // directory
			'%d', // uploader id
			'%d', // uploaded time
			'%d', // access
			'%d' // visible
		)
	);
}

 
 