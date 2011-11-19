<?php
/**
 * Plugin Name: Member Register
 * Files for members only
 * - Access can be for all, club, member level
 * All documents such as word, etc are converted to pdf which is transformed for each member separately
 * in order to have their name as a watermark.
 */

 
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
	
	if ($userdata->mr_access > 3)
	{
		mr_files_new();
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
		<th><?php echo __('Uploader'); ?></th>
		<th><?php echo __('Uploaded'); ?></th>
		<th><?php echo __('Size'); ?> (KB)</th>
		<!--<th><?php echo __('Remove'); ?></th>-->
	</tr>
	</thead>
	<tbody>

	<?php
	foreach($files as $file)
	{
		$url = '<a href="' . admin_url('admin.php?page=member-register-control') .
			'&memberid=' . $file['uploader'] . '" title="' . $file['firstname'] .
			' '	. $file['lastname'] . '">';

		echo '<tr id="user_' . $file['id'] . '">';
		echo '<td>' . $file['basename'] . '</td>';
		echo '<td>' . $file['directory'] . '</td>';
		echo '<td>' . date($mr_date_format, $file['uploaded']) . '</td>';
		echo '<td>' . $url . $file['firstname'] . ' ' . $file['lastname'] . '</a></td>';
		echo '<td>' . round($file['bytesize'] / 1024) . '</td>';
		//echo '<td>';
		//echo '</td>';
		echo '</tr>';
	}
	?>
	</tbody>
	</table>
	<?php
}


function mr_files_new()
{
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
		<form name="form1" method="post" action="<?php echo admin_url('admin.php?page=member-files'); ?>" enctype="multipart/form-data">
			<input type="hidden" name="mr_submit_hidden_file" value="Y" />
			<table class="form-table" id="createfile">
				<tr class="form-field">
					<th><?php echo __('File'); ?></th>
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

	$basename = basename($filesdata['name']);
	$values = array(
		'basename' => $basename,
		'bytesize' => 0,
		'directory' => '',
		'uploader' => $userdata->mr_memberid,
		'uploaded' => time(),
		'access' => 0,
		'visible' => 1
	);
	
	//if (dir_exists($mr_file_base_directory . '/' . $dir

	if (move_uploaded_file($filesdata['tmp_name'], $mr_file_base_directory . $basename))
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
			'%s',
			'%d',
			'%s',
			'%d',
			'%d',
			'%d',
			'%d'			
		)
	);
}

 
 