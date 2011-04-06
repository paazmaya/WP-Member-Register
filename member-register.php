<?php
/**
 Plugin Name: Member Register
 Plugin URI: http://paazio.nanbudo.fi/member-register-wordpress-plugin
 Description: A register of member which can be linked to a WP users. Includes payment (and martial art belt grade) information.
 Version: 0.3.3
 License: Creative Commons Share-Alike-Attribute 3.0
 Author: Jukka Paasonen
 Author URI: http://paazmaya.com
*/

/**
 * add field to user profiles
 */


define ('MEMBER_REGISTER_VERSION', '0.3.3');
global $mr_db_version;
$mr_db_version = '0.2';


register_activation_hook(__FILE__, 'mr_install');
//register_uninstall_hook( __FILE__, 'member_register_uninstall' );


/*
$(document).ready(function(){

});
*/

add_action( 'admin_init', 'member_register_admin_init' );
add_action( 'admin_menu', 'member_register_admin_menu' );
add_action( 'admin_print_styles', 'member_register_admin_print_styles' );
add_action( 'admin_print_scripts', 'member_register_admin_print_scripts' );
add_action( 'admin_head', 'member_register_admin_head' );


// http://tablesorter.com/docs/
// http://bassistance.de/jquery-plugins/jquery-plugin-validation/
function member_register_admin_init()
{
	wp_register_script( 'jquery-bassistance-validation', plugins_url('/js/jquery.validate.min.js', __FILE__), array('jquery') );
	wp_register_script( 'jquery-bassistance-validation-messages-fi', plugins_url('/js/messages_fi.js', __FILE__), array('jquery') );
	wp_register_script( 'jquery-tablesorter', plugins_url('/js/jquery.tablesorter.min.js', __FILE__), array('jquery') );
	wp_register_script( 'jquery-ui-datepicker', plugins_url('/js/jquery.ui.datepicker.min.js', __FILE__), array('jquery', 'jquery-ui-core') ); // 1.8.9
	wp_register_script( 'jquery-ui-datepicker-fi', plugins_url('/js/jquery.ui.datepicker-fi.js', __FILE__), array('jquery') );
	
	
	wp_register_style( 'jquery-ui-core',  plugins_url('/css/jquery.ui.core.css', __FILE__));
	wp_register_style( 'jquery-ui-datepicker',  plugins_url('/css/jquery.ui.datepicker.css', __FILE__));
}

function member_register_admin_print_scripts()
{
	// http://codex.wordpress.org/Function_Reference/wp_enqueue_script
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-bassistance-validation');
	wp_enqueue_script('jquery-bassistance-validation-messages-fi');
	wp_enqueue_script('jquery-tablesorter');
	wp_enqueue_script('jquery-ui-datepicker');
	wp_enqueue_script('jquery-ui-datepicker-fi');
}

function member_register_admin_print_styles()
{
	// http://codex.wordpress.org/Function_Reference/wp_enqueue_style
	//wp_enqueue_style( 'jquery-ui-core' );
	wp_enqueue_style( 'jquery-ui-datepicker' );
}

function member_register_admin_head()
{
	// jQuery is in noConflict state while in Wordpress...
	?>
	<script type="text/javascript">

		jQuery(document).ready(function(){
			jQuery.datepicker.setDefaults({
				showWeek: true,
				numberOfMonths: 2,
				dateFormat: 'yy-mm-dd'
			});
			jQuery('input.pickday').datepicker();
		});
		
	</script>
	<?php

}

function member_register_admin_menu()
{
	// http://codex.wordpress.org/Adding_Administration_Menus
	add_menu_page('Jäsenrekisterin Hallinta', 'Jäsenrekisteri', 'create_users', 'member-register-control',
		'mr_member_list', plugins_url('/images/people.jpg', __FILE__)); // $position );
	add_submenu_page('member-register-control', 'Lisää uusi jäsen',
		'Uusi jäsen', 'create_users', 'member-register-new', 'mr_member_new');
	add_submenu_page('member-register-control', 'Hallinnoi jäsenmaksuja',
		'Jäsenmaksut', 'create_users', 'member-payment-list', 'mr_payment_list');
	add_submenu_page('member-register-control', 'Uusi maksu',
		'Uusi maksu', 'create_users', 'member-payment-new', 'mr_payment_new');
	add_submenu_page('member-register-control', 'Vyöarvot',
		'Vyöarvot', 'create_users', 'member-grade-list', 'mr_grade_list');
	add_submenu_page('member-register-control', 'Myönnä vyöarvoja',
		'Myönnä vyöarvoja', 'create_users', 'member-grade-new', 'mr_grade_new');

	// http://codex.wordpress.org/Function_Reference/wp_enqueue_script
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-bassistance-validation');
	wp_enqueue_script('jquery-bassistance-validation-messages-fi');
	wp_enqueue_script('jquery-tablesorter');
	
	// http://codex.wordpress.org/Function_Reference/wp_enqueue_style
	//wp_enqueue_style( 'myPluginStylesheet' );
}








/*

function member_register_uninstall()
{
}
*/


function mr_member_list()
{
	if (!current_user_can('create_users'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	echo '<div class="wrap">';
	echo '<h2>Jäsenrekisteri</h2>';
	echo '<p>Alla lista rekisteröidyistä jäsenistä</p>';
	echo mr_show_members();
	echo '</div>';
}
function mr_payment_list()
{
	if (!current_user_can('create_users'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	global $wpdb;

	if (isset($_POST['haspaid']) && is_numeric($_POST['haspaid']))
	{
		$id = intval($_POST['haspaid']);
		$today = date('Y-m-d');
		$sql = 'UPDATE ' . $wpdb->prefix . 'mr_payment SET paidday = \'' . $today . '\' WHERE id = ' . $id;
		if ($wpdb->query($sql))
		{
			?>
			<div class="updated"><p><strong>Maksu merkitty maksetuksi tänään</strong></p></div>
			<?php
		}
		else
		{
			echo '<p>' . $wpdb->print_error() . '</p>';
		}
	}

	echo '<div class="wrap">';
	echo '<h2>Jäsenmaksut</h2>';
	echo '<p>Merkitse maksu maksetuksi vasemmalla olevalla "OK" painikkeella.</p>';
	echo mr_show_payments();
	echo '</div>';
}

function mr_grade_list()
{
	if (!current_user_can('create_users'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	global $wpdb;

	echo '<div class="wrap">';
	echo '<h2>Vyöarvot</h2>';
	echo '<p>Jäsenet heidän viimeisimmän vyöarvon mukaan.</p>';
	echo '<p>Kenties tässä pitäisi olla filtterit vyöarvojen, seurojen ym mukaan.</p>';
	echo mr_show_grades();
	echo '</div>';
}


function mr_member_new()
{
	if (!current_user_can('create_users'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	global $wpdb;

    $hidden_field_name = 'mr_submit_hidden';

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' )
	{
        if (mr_insert_new_member($_POST))
		{
			// Put an settings updated message on the screen
			?>
			<div class="updated"><p><strong>Uusi jäsen lisätty</strong></p></div>
			<?php
		}
		else
		{
			echo '<p>' . $wpdb->print_error() . '</p>';
		}

    }

    ?>
	<div class="wrap">
		<h2>Lisää uusi jäsen</h2>
		<form name="form1" method="post" action="">
			<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
			<table class="form-table" id="createuser">
				<tr class="form-field">
					<th>user_login <span class="description">(jos siis on jo WP käyttäjä)</span></th>
					<td><select name="user_login">
					<option value="">-</option>
					<?php
					$sql = 'SELECT A.user_login, A.display_name FROM ' . $wpdb->prefix . 'users A LEFT JOIN ' . $wpdb->prefix . 'mr_member B ON A.user_login = B.user_login WHERE B.user_login IS NULL ORDER BY 2 ASC';

					$users = $wpdb->get_results($sql, ARRAY_A);
					foreach($users as $user)
					{
						echo '<option value="' . $user['user_login']. '">' . $user['display_name'] . ' (' . $user['user_login'] . ')</option>';
					}
					?>
					</select></td>
				</tr>
				<tr class="form-field form-required">
					<th>access</th>
					<td><input type="text" name="access" value="1" /></td>
				</tr>
				<tr class="form-field form-required">
					<th>firstname</th>
					<td><input type="text" name="firstname" /></td>
				</tr>
				<tr class="form-field form-required">
					<th>lastname</th>
					<td><input type="text" name="lastname" /></td>
				</tr>
				<tr class="form-field">
					<th>birthdate <span class="description">(YYYY-MM-DD)</span></th>
					<td><input type="text" name="birthdate" class="pickday" /></td>
				</tr>
				<tr class="form-field">
					<th>address</th>
					<td><input type="text" name="address" /></td>
				</tr>
				<tr class="form-field">
					<th>zipcode</th>
					<td><input type="text" name="zipcode" /></td>
				</tr>
				<tr class="form-field">
					<th>postal</th>
					<td><input type="text" name="postal" /></td>
				</tr>
				<tr class="form-field">
					<th>phone</th>
					<td><input type="text" name="phone" /></td>
				</tr>
				<tr class="form-field">
					<th>email</th>
					<td><input type="text" name="email" /></td>
				</tr>
				<tr class="form-field">
					<th>nationality</th>
					<td><input type="text" name="nationality" /></td>
				</tr>
				<tr class="form-field">
					<th>joindate <span class="description">(YYYY-MM-DD)</span></th>
					<td><input type="text" name="joindate" class="pickday" /></td>
				</tr>
				<tr class="form-field">
					<th>passnro</th>
					<td><input type="text" name="passnro" /></td>
				</tr>
				<tr class="form-field">
					<th>notes</th>
					<td><input type="text" name="notes" /></td>
				</tr>
				<tr class="form-field">
					<th>active</th>
					<td><input type="text" name="active" value="1" /></td>
				</tr>
				<tr class="form-field">
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

			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
			</p>

		</form>
	</div>

	<?php

}

function mr_payment_new()
{
	if (!current_user_can('create_users'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	global $wpdb;

    $hidden_field_name = 'mr_submit_hidden';

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' )
	{
        if (mr_insert_new_payment($_POST))
		{
			?>
			<div class="updated"><p><strong>Uusi/uudet maksu(t) lisätty</strong></p></div>
			<?php
		}
		else
		{
			echo '<p>' . $wpdb->print_error() . '</p>';
		}

    }

    ?>
	<div class="wrap">
		<h2>Lisää uusi maksu, useammalle henkilölle jos tarve vaatii</h2>
		<p>Pääasia että rahaa tulee, sitä kun menee</p>
		<form name="form1" method="post" action="" enctype="multipart/form-data">
			<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
			<table class="form-table" id="createuser">
				<tr class="form-field">
					<th>member <span class="description">(monivalinta)</span></th>
					<td><select name="members[]" multiple="multiple" size="7" style="height: 8em;">
					<option value="">-</option>
					<?php
					$sql = 'SELECT firstname, lastname, id FROM ' . $wpdb->prefix . 'mr_member ORDER BY lastname ASC';

					$users = $wpdb->get_results($sql, ARRAY_A);
					foreach($users as $user)
					{
						echo '<option value="' . $user['id']. '">' . $user['lastname'] . ', ' . $user['firstname'] . '</option>';
					}
					?>
					</select></td>
				</tr>
				<tr class="form-field">
					<th>type <span class="description">(lienee aina vuosimaksu)</span></th>
					<td><input type="text" name="type" value="vuosimaksu" /></td>
				</tr>
				<tr class="form-field">
					<th>amount <span class="description">(EUR)</span></th>
					<td><input type="text" name="amount" value="10" /></td>
				</tr>
				<tr class="form-field">
					<th>deadline <span class="description">(3 viikkoa tulevaisuudessa)</span></th>
					<td><input type="text" name="deadline" class="pickday" value="<?php
					echo date('Y-m-d', time() + 60*60*24*21);
					?>" /></td>
				</tr>
				<tr class="form-field">
					<th>validuntil <span class="description">(kuluvan vuoden loppuun)</span></th>
					<td><input type="text" name="validuntil" class="pickday" value="<?php
					echo date('Y') . '-12-31';
					?>" /></td>
				</tr>
				<tr class="form-field">
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

			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
			</p>

		</form>
	</div>

	<?php

}


function mr_grade_new()
{
	if (!current_user_can('create_users'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	global $wpdb;

    $hidden_field_name = 'mr_submit_hidden';

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' )
	{
        if (mr_insert_new_grade($_POST))
		{
			?>
			<div class="updated"><p><strong>Uusi/uudet vyöarvo(t) lisätty</strong></p></div>
			<?php
		}
		else
		{
			echo '<p>' . $wpdb->print_error() . '</p>';
		}

    }

    ?>
	<div class="wrap">

		<h2>Myönnä vyöarvoja</h2>
		<form name="form1" method="post" action="" enctype="multipart/form-data">
			<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
			<table class="form-table" id="createuser">
				<tr class="form-field">
					<th>member <span class="description">(monivalinta)</span></th>
					<td><select name="members[]" multiple="multiple" size="7" style="height: 8em;">
					<option value="">-</option>
					<?php
					$sql = 'SELECT firstname, lastname, id FROM ' . $wpdb->prefix . 'mr_member ORDER BY lastname ASC';

					$users = $wpdb->get_results($sql, ARRAY_A);
					foreach($users as $user)
					{
						echo '<option value="' . $user['id']. '">' . $user['lastname'] . ', ' . $user['firstname'] . '</option>';
					}
					?>
					</select></td>
				</tr>
				<tr class="form-field">
					<th>grade <span class="description">()</span></th>
					<td><input type="text" name="grade" value="2K" /></td>
				</tr>
				<tr class="form-field">
					<th>location <span class="description">(missä)</span></th>
					<td><input type="text" name="location" value="Turku" /></td>
				</tr>
				<tr class="form-field">
					<th>nominator <span class="description">(kuka myönsi)</span></th>
					<td><input type="text" name="nominator" value="Ilpo Jalamo, 6 dan" /></td>
				</tr>
				<tr class="form-field">
					<th>day <span class="description">(YYYY-MM-DD)</span></th>
					<td><input type="text" name="day" class="pickday" value="<?php
					echo date('Y-m-d', time() - 60*60*24*1);
					?>" /></td>
				</tr>

			</table>

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

function mr_insert_new_payment($postdata)
{
	global $wpdb;

	$keys = array();
	$values = array();
	$setval = array();

	$required = array('type', 'amount', 'deadline', 'validuntil', 'club');


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


		$id = '10' . $wpdb->get_var('SELECT MAX(id) FROM ' . $wpdb->prefix . 'mr_payment');

		foreach($postdata['members'] as $member)
		{
			$id++;
			// calculate reference number
			$ref = "'" . mr_reference_count($id) . "'";

			$setval[] = '(' . implode(', ', array_merge($values, array('"' . $member . '"', $ref))) . ')';

		}
	}

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_payment (' . implode(', ', $keys) . ') VALUES ' . implode(', ', $setval);

	//echo $sql;

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

	//echo $sql;

	return $wpdb->query($sql);

}
function mr_insert_new_grade($postdata)
{
	global $wpdb;

	$keys = array();
	$values = array();

	$required = array('grade', 'location', 'nominator', 'day');

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
		
	foreach($postdata['members'] as $member)
	{
		$setval[] = '(' . implode(', ', array_merge($values, array('"' . $member . '"'))) . ')';
	}

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_grade (' . implode(', ', $keys) . ') VALUES ' . implode(', ', $setval);

	echo $sql;

	return $wpdb->query($sql);

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

function mr_show_payments()
{
	global $wpdb;
	$sql = 'SELECT A.*, B.firstname, B.lastname, C.name AS clubname FROM ' . $wpdb->prefix .
		'mr_payment A LEFT JOIN ' . $wpdb->prefix .
		'mr_member B ON A.member = B.id LEFT JOIN ' . $wpdb->prefix .
		'mr_club C ON A.club = C.id ORDER BY A.deadline DESC';
	$res = $wpdb->get_results($sql, ARRAY_A);

	$items = array('firstname', 'lastname', 'id',
		'member', 'reference', 'type', 'amount', 'deadline',
		'paidday', 'validuntil', 'club', 'clubname');

	$out = '<table class="wp-list-table widefat fixed users">';
	$out .= '<thead>';
	$out .= '<tr>';
	$out .='<th></th>';

	foreach($items as $item)
	{
		$out .= '<th>' . $item . '</th>';
	}

	$out .= '</tr>';
	$out .= '</thead>';
	$out .= '<tbody>';

	foreach($res as $payment)
	{
		$out .= '<tr id="payment_' . $payment['id'] . '">';
		$out .= '<td>';
		if ($payment['paidday'] == '0000-00-00')
		{
			$out .= '<form action="admin.php?page=member-payment-list" method="post">';
			$out .= '<input type="hidden" name="haspaid" value="' . $payment['id'] . '" />';
			$out .= '<input type="submit" value="OK" /></form>';
		}
		$out .= '</td>';
		foreach($items as $item)
		{
			$out .= '<td>' . $payment[$item] . '</td>';
		}
		$out .= '</tr>';
	}
	$out .= '</tbody>';
	$out .= '</table>';

	return $out;
}

function mr_show_grades()
{

	global $wpdb;
	$sql = 'SELECT A.firstname, A.lastname, B.* FROM ' . $wpdb->prefix .
		'mr_member A LEFT JOIN ' . $wpdb->prefix .
		'mr_grade B ON A.id = B.member WHERE B.id IS NOT NULL ORDER BY A.lastname DESC';

	$res = $wpdb->get_results($sql, ARRAY_A);

	$items = array('firstname', 'lastname',
		'id', 'member', 'grade', 'location', 'nominator', 'day');

	$out = '<table class="wp-list-table widefat fixed users">';
	$out .= '<thead>';
	$out .= '<tr>';

	foreach($items as $item)
	{
		$out .= '<th>' . $item . '</th>';
	}

	$out .= '</tr>';
	$out .= '</thead>';
	$out .= '<tbody>';

	foreach($res as $grade)
	{
		$out .= '<tr id="grade_' . $grade['id'] . '">';
		foreach($items as $item)
		{
			$out .= '<td>' . $grade[$item] . '</td>';
		}
		$out .= '</tr>';
	}
	$out .= '</tbody>';
	$out .= '</table>';

	return $out;
}

function mr_show_members()
{
	$items = array('user_login', 'firstname', 'lastname', 'birthdate', 'email', 'joindate');
	$members = mr_get_list('member');
	$out = '<table class="wp-list-table widefat fixed users">';
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
		$out .= '<tr id="user_' . $member['id'] . '">';
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
		  id mediumint(5) unsigned NOT NULL AUTO_INCREMENT,
		  member mediumint(5) unsigned NOT NULL DEFAULT '0',
		  grade enum('8K','7K','6K','5h','5K','4h','4K','3h','3K','2h','2K','1h','1K','1s','1D','2s','2D','3D','4D','5D','6D','7D','8D') COLLATE utf8_swedish_ci NOT NULL DEFAULT '8K',
		  location varchar(100) COLLATE utf8_swedish_ci NOT NULL,
		  nominator varchar(255) NOT NULL DEFAULT '',
		  day date DEFAULT '0000-00-00' NOT NULL,
		  PRIMARY KEY (id),
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

