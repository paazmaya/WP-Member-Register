<?php
/**
 Plugin Name: Member Register
 Plugin URI: http://paazio.nanbudo.fi/member-register-wordpress-plugin
 Description: A register of member which can be linked to a WP users. Includes payment (and martial art belt grade) information.
 Version: 0.3.1
 License: Creative Commons Share-Alike-Attribute 3.0
 Author: Jukka Paasonen
 Author URI: http://paazmaya.com
*/

/**
 * add field to user profiles
 */

 
define ('MEMBER_REGISTER_VERSION', '0.3.1');


wp_enqueue_script('jquery');




global $mr_db_version;
$mr_db_version = '0.1';

register_activation_hook(__FILE__,'mr_install');

add_action('admin_menu', 'mr_plugin_menu');


/*
register_uninstall_hook( __FILE__, 'member_register_uninstall' );

function member_register_uninstall()
{
}
*/

function mr_plugin_menu()
{
	// http://codex.wordpress.org/Adding_Administration_Menus
	add_menu_page('Jäsenrekisterin Hallinta', 'Jäsenrekisteri', 'create_users', 'member-register-control', 
		'mr_options'); //$icon_url, $position );
	add_submenu_page('member-register-control', 'Lisää uusi jäsen',
		'Uusi jäsen', 'create_users', 'member-register-new', 'mr_new_user');
}

function mr_options()
{
	if (!current_user_can('create_users'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	echo '<div class="wrap">';
	echo '<h2>Jäsenrekisteri</h2>';
	echo '<p><a class="button add-new-h2" href="member-new.php">Lisää uusi jäsen</a></p>';
	echo mr_show_members();
	echo '</div>';
}
function mr_new_user()
{
	if (!current_user_can('create_users'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
    $hidden_field_name = 'mr_submit_hidden';

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' )
	{
        if (mr_insert_new_user($_POST))
		{

			// Put an settings updated message on the screen
			?>
			<div class="updated"><p><strong>Uusi jäsen lisätty</strong></p></div>
			<?php
		}
		else 
		{
			echo '<p>' . mysql_error() . '</p>';
		}

    }

    ?>
	<div class="wrap">
		<h2>Lisää uusi jäsen</h2>
		<form name="form1" method="post" action="">
			<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
			<table>
				<tr>
					<th>user_login (jos siis on jo WP käyttäjä)</th>
					<td><input type="text" name="user_login" /></td>
				</tr>
				<tr>
					<th>access</th>
					<td><input type="text" name="access" value="1" /></td>
				</tr>
				<tr>
					<th>firstname</th>
					<td><input type="text" name="firstname" /></td>
				</tr>
				<tr>
					<th>lastname</th>
					<td><input type="text" name="lastname" /></td>
				</tr>
				<tr>
					<th>birthdate (YYYY-MM-DD)</th>
					<td><input type="text" name="birthdate" /></td>
				</tr>
				<tr>
					<th>address</th>
					<td><input type="text" name="address" /></td>
				</tr>
				<tr>
					<th>zipcode</th>
					<td><input type="text" name="zipcode" /></td>
				</tr>
				<tr>
					<th>postal</th>
					<td><input type="text" name="postal" /></td>
				</tr>
				<tr>
					<th>phone</th>
					<td><input type="text" name="phone" /></td>
				</tr>
				<tr>
					<th>email</th>
					<td><input type="text" name="email" /></td>
				</tr>
				<tr>
					<th>nationality</th>
					<td><input type="text" name="nationality" /></td>
				</tr>
				<tr>
					<th>joindate (YYYY-MM-DD)</th>
					<td><input type="text" name="joindate" /></td>
				</tr>
				<tr>
					<th>passnro</th>
					<td><input type="text" name="passnro" /></td>
				</tr>
				<tr>
					<th>notes</th>
					<td><input type="text" name="notes" /></td>
				</tr>
				<tr>
					<th>active</th>
					<td><input type="text" name="active" value="1" /></td>
				</tr>
				<tr>
					<th>club</th>
					<td><select name="club">
					<option value="0">-</option>
					<?php
					$clubs = mr_get_list('club', '', '', 'name ASC');
					foreach($clubs as $club)
					{
						echo '<option value="' . $club['id']. '">' . $club['name'] . '</option>';
					}
					?>
					</select></td>
				</tr>
			</table>
			<hr />
		
		  
			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
			</p>

		</form>
	</div>

	<?php
		
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

function mr_insert_new_user($postdata)
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
	
	//echo $sql;
	
	return $wpdb->query($sql);
}

function admin_init()
{

}


function mr_install () 
{
	global $wpdb;
	global $mr_db_version;
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	
	$table_name = $wpdb->prefix . 'mr_club';
	if ($wpdb->get_var("show tables like '" . $table_name. "'") != $table_name)
	{
	
		$sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(6) unsigned NOT NULL AUTO_INCREMENT,
		  name varchar(100) COLLATE utf8_swedish_ci NOT NULL,
		  address tinytext COLLATE utf8_swedish_ci NOT NULL,
		  PRIMARY KEY (id)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;";

		dbDelta($sql);
	}

	$table_name = $wpdb->prefix . 'mr_grade';
	if ($wpdb->get_var("show tables like '" . $table_name. "'") != $table_name)
	{
	
		$sql = "CREATE TABLE " . $table_name . " (
		  member mediumint(5) unsigned NOT NULL DEFAULT '0',
		  grade enum('8K','7K','6K','5h','5K','4h','4K','3h','3K','2h','2K','1h','1K','1s','1D','2s','2D','3D','4D','5D','6D','7D','8D') COLLATE utf8_swedish_ci NOT NULL DEFAULT '8K',
		  location varchar(100) COLLATE utf8_swedish_ci NOT NULL,
		  nominator tinyint(4) NOT NULL DEFAULT '0',
		  day date DEFAULT '0000-00-00' NOT NULL,
		  KEY member (member)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;";

		dbDelta($sql);
	}

	$table_name = $wpdb->prefix . 'mr_member';
	if ($wpdb->get_var("show tables like '" . $table_name. "'") != $table_name)
	{
	
		$sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(5) unsigned NOT NULL AUTO_INCREMENT,
		  user_login varchar(50) COLLATE utf8_swedish_ci NOT NULL DEFAULT '' COMMENT 'wp_users reference',
		  access tinyint(1) NOT NULL DEFAULT '0',
		  firstname varchar(40) COLLATE utf8_swedish_ci NOT NULL,
		  lastname varchar(40) COLLATE utf8_swedish_ci NOT NULL,
		  birthdate date DEFAULT '0000-00-00' NOT NULL,
		  address varchar(160) COLLATE utf8_swedish_ci NOT NULL,
		  zipcode varchar(6) COLLATE utf8_swedish_ci NOT NULL DEFAULT '20100',
		  postal varchar(80) COLLATE utf8_swedish_ci NOT NULL DEFAULT 'Turku',
		  phone varchar(20) COLLATE utf8_swedish_ci NOT NULL,
		  email varchar(200) COLLATE utf8_swedish_ci NOT NULL,
		  nationality varchar(2) COLLATE utf8_swedish_ci NOT NULL DEFAULT 'FI',
		  joindate date DEFAULT '0000-00-00' NOT NULL,
		  passnro mediumint(6) unsigned NOT NULL DEFAULT '0',
		  notes tinytext COLLATE utf8_swedish_ci NOT NULL,
		  lastlogin datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  active tinyint(1) NOT NULL DEFAULT '0',
		  club mediumint(6) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (id)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;";

		dbDelta($sql);
	}

	$table_name = $wpdb->prefix . 'mr_payment';
	if ($wpdb->get_var("show tables like '" . $table_name. "'") != $table_name)
	{
	
		$sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(5) unsigned NOT NULL AUTO_INCREMENT,
		  member mediumint(5) unsigned NOT NULL DEFAULT '0',
		  reference mediumint(6) unsigned NOT NULL DEFAULT '0',
		  type varchar(24) COLLATE utf8_swedish_ci NOT NULL,
		  amount float(8,2) NOT NULL DEFAULT '0.00',
		  deadline date DEFAULT '0000-00-00' NOT NULL,
		  paidday date DEFAULT '0000-00-00' NOT NULL,
		  validuntil date DEFAULT '0000-00-00' NOT NULL,
		  club mediumint(6) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (id),
		  KEY member (member)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;";
	
	
		dbDelta($sql);
	}
	
	
	
	add_option('mr_db_version', $mr_db_version);
		
		
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




function mr_show_members()
{
	$items = array('user_login', 'firstname', 'lastname', 'birthdate', 'email');
	$members = mr_get_list('member');
	$out = '<table>';
	$out .= '<thead>';
	$out .= '<tr>';
	
	foreach($items as $item)
	{
		$out .= '<th>' . $item . '</th>';
	}
	
	$out .= '</tr>';
	$out .= '</thead>';
	$out .= '<tbody>';
		
	foreach($members as $member)
	{
		$out .= '<tr>';
		foreach($items as $item)
		{
			$out .= '<td>' . $member[$item] . '</td>';
		}
		$out .= '</tr>';
	}
	$out .= '</tbody>';
	$out .= '</table>';

	return $out;
}
