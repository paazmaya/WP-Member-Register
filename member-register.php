<?php
/**
 Plugin Name: Member Register
 Plugin URI: http://paazio.nanbudo.fi/member-register-wordpress-plugin
 Description: A register of member which can be linked to a WP users. Includes payment (and martial art belt grade) information.
 Version: 0.5.6
 License: Creative Commons Share-Alike-Attribute 3.0
 Author: Jukka Paasonen
 Author URI: http://paazmaya.com
*/

/**
 * add field to user profiles
 */


define ('MEMBER_REGISTER_VERSION', '0.5.6');

global $mr_db_version;
$mr_db_version = '6';

global $mr_grade_values;
$mr_grade_values = array(
	'5K' => '5 kyu',
	'5h' => '5 kyu + raita',
	'4K' => '4 kyu',
	'4h' => '4 kyu + raita',
	'3K' => '3 kyu',
	'3h' => '3 kyu + raita',
	'2K' => '2 kyu',
	'2h' => '2 kyu + raita',
	'1K' => '1 kyu',
	'1h' => '1 kyu + raita',
	'1D' => '1 dan',
	'2D' => '2 dan',
	'3D' => '3 dan',
	'4D' => '4 dan',
	'5D' => '5 dan',
	'6D' => '6 dan',
	'7D' => '7 dan'
);

global $mr_grade_types;
$mr_grade_types = array(
	'Yuishinkai' => 'Yuishinkai Karate',
	'Kobujutsu' => 'Ryukyu Kobujutsu'
);

/*
// Security permissions:
$writePost = 1;
$readPost = 2;
$deletePost = 4;
$addUser = 8;
$deleteUser = 16;

$mr_access_values = (
	'1' => 'Kirjoittaa viestejä',
	'2' => 'Lukea viestejä',
);

// User groups:
$mr_access_type = array(
	0 => 0,
	1 => $readPost,
	2 => $writePost | $readPost,
	3 => ,
	4 => ,
	5 => ,
	6 => $readPost | $deletePost | $deleteUser,
	7 => ,
	8 => ,
	9 => ,
	10 => $writePost | $readPosts | $deletePosts | $addUser | $deleteUser
);
// Now we apply all of this!
if(mr_check_permission($administrator, $deleteUser)) {
	deleteUser("Some User"); # This is executed because $administrator can $deleteUser
}

*/

global $mr_access_type;
$mr_access_type = array(
	0 => 'Ei mitään, ei aktiivinen jäsen',
	1 => 'Omien tietojen katselu ja päivitys',
	2 => 'Keskusteluun osallistuminen',
	3 => 'Keskusteluaiheiden luominen',
	4 => 'Keskustelujen poisto',
	5 => 'Keskusteluaiheiden poisto', // voi myös päättää keskusteluaiheen näkyvyys tason
	6 => 'Jäsenten lisääminen ja muokkaus',
	7 => 'Jäsenten poistaminen',
	8 => 'Jäsenmaksujen hallinta',
	9 => 'Vyöarvojen hallinta',
	10 => 'Kaikki mahdollinen mitä täällä ikinä voi tehdä'
);
require 'member-functions.php';
require 'member-forum.php';

register_activation_hook(__FILE__, 'mr_install');
//register_uninstall_hook( __FILE__, 'member_register_uninstall');



// http://codex.wordpress.org/Function_Reference/add_action
add_action('admin_init', 'member_register_admin_init');
add_action('admin_menu', 'member_register_admin_menu');
add_action('admin_menu', 'member_register_forum_menu');
add_action('admin_print_styles', 'member_register_admin_print_styles');
add_action('admin_print_scripts', 'member_register_admin_print_scripts');
add_action('admin_head', 'member_register_admin_head');

// http://codex.wordpress.org/Plugin_API/Action_Reference/profile_update
add_action('profile_update', 'member_register_profile_update');

// Login and logout
add_action('wp_login', 'member_register_login');
add_action('wp_logout', 'member_register_logout');

// Check Member Register related access data
add_action('wp_loaded', 'member_register_wp_loaded');


// http://tablesorter.com/docs/
// http://bassistance.de/jquery-plugins/jquery-plugin-validation/
function member_register_admin_init()
{
	wp_register_script('jquery-bassistance-validation', plugins_url('/js/jquery.validate.min.js', __FILE__), array('jquery'));
	wp_register_script('jquery-bassistance-validation-messages-fi', plugins_url('/js/messages_fi.js', __FILE__), array('jquery'));
	wp_register_script('jquery-tablesorter', plugins_url('/js/jquery.tablesorter.min.js', __FILE__), array('jquery'));
	wp_register_script('jquery-ui-datepicker', plugins_url('/js/jquery.ui.datepicker.min.js', __FILE__), array('jquery', 'jquery-ui-core')); // 1.8.9
	wp_register_script('jquery-ui-datepicker-fi', plugins_url('/js/jquery.ui.datepicker-fi.js', __FILE__), array('jquery'));
	wp_register_script('jquery-cluetip', plugins_url('/js/jquery.cluetip.min.js', __FILE__), array('jquery'));
	wp_register_script('jquery-picnet-table-filter', plugins_url('/js/picnet.table.filter.min.js', __FILE__), array('jquery'));

	wp_register_style('jquery-ui-theme-blizter',  plugins_url('/css/jquery-ui.blizter.css', __FILE__));
	wp_register_style('jquery-ui-core',  plugins_url('/css/jquery.ui.core.css', __FILE__));
	wp_register_style('jquery-ui-datepicker',  plugins_url('/css/jquery.ui.datepicker.css', __FILE__));
	wp_register_style('jquery-tablesorter',  plugins_url('/css/jquery.tablesorter.css', __FILE__));
	wp_register_style('jquery-cluetip',  plugins_url('/css/jquery.cluetip.css', __FILE__));
	wp_register_style('mr-styles',  plugins_url('/css/mr-styles.css', __FILE__));
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
	wp_enqueue_script('jquery-cluetip');
	wp_enqueue_script('jquery-picnet-table-filter');
}

function member_register_admin_print_styles()
{
	// http://codex.wordpress.org/Function_Reference/wp_enqueue_style
	//wp_enqueue_style('jquery-ui-core');
	wp_enqueue_style('jquery-ui-datepicker');
	wp_enqueue_style('jquery-ui-theme-blizter');
	wp_enqueue_style('jquery-tablesorter');
	wp_enqueue_style('jquery-cluetip');
	wp_enqueue_style('mr-styles');
}

function member_register_admin_head()
{
	// jQuery is in noConflict state while in Wordpress...
	?>
	<script type="text/javascript">

		jQuery(document).ready(function(){
			jQuery.datepicker.setDefaults({
				showWeek: true,
				changeMonth: true,
				changeYear: true,
				yearRange: '1900:2110',
				numberOfMonths: 1,
				dateFormat: 'yy-mm-dd'
			});
			jQuery('input.pickday').datepicker();
			jQuery('table.tablesorter').tablesorter();
			jQuery('table a.tip').cluetip({
				splitTitle: '|',
				sticky: true,
				closeText: 'sulje',
				closePosition: 'title'
			});
			jQuery('table.tablesorter').tableFilter();
		});

	</script>
	<?php
	// http://www.picnet.com.au/picnet_table_filter.html
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

}


/**
 * Any member with login access can use the forum
 * http://codex.wordpress.org/Roles_and_Capabilities#Subscriber
 */
function member_register_forum_menu()
{
	global $userdata;

	if (isset($userdata->user_login) && isset($userdata->mr_access) && $userdata->mr_access >= 2)
	{
		// http://codex.wordpress.org/Adding_Administration_Menus
		add_menu_page('Keskustelu', 'Keskustelu', 'read', 'member-forum',
			'mr_forum_list', plugins_url('/images/forum-icon-01.gif', __FILE__)); // $position );
	}
}


// http://codex.wordpress.org/Plugin_API/Action_Reference/profile_update
function member_register_profile_update($user_id, $old_user_data)
{
	echo '<p>' . $user_id . '</p>';
	echo '<pre>';
	print_r($old_user_data);
	echo '</pre>';
}

/**
 * Check which user logged in to WP and set Session Access variable.
 */
function member_register_login()
{
	global $userdata;
}

function member_register_logout()
{
	global $userdata;
}



function member_register_wp_loaded()
{
	global $wpdb;
	global $userdata;

	// http://codex.wordpress.org/User:CharlesClarkson/Global_Variables
	if (isset($userdata->user_login) && (!isset($userdata->mr_access) ||
		!is_numeric($userdata->mr_access) ||
		!isset($userdata->mr_memberid) || !is_numeric($userdata->mr_memberid)))
	{
		$sql = 'SELECT id, access FROM ' . $wpdb->prefix . 'mr_member WHERE user_login = \'' .
			mr_htmlent($userdata->user_login) . '\' AND active = 1 LIMIT 1';
		$res = $wpdb->get_row($sql, ARRAY_A);
		$userdata->mr_access = intval($res['access']);
		$userdata->mr_memberid = intval($res['id']);
	}

	date_default_timezone_set('Europe/Helsinki');
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
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}
	echo '<div class="wrap">';

	if (isset($_GET['memberid']) && is_numeric($_GET['memberid']))
	{
		mr_show_member_info(intval($_GET['memberid']));
	}
	else
	{
		echo '<h2>Jäsenrekisteri</h2>';
		echo '<p>Alla lista rekisteröidyistä jäsenistä</p>';
		mr_show_access_values();
		mr_show_members();
	}
	echo '</div>';
}










function mr_payment_list()
{
	if (!current_user_can('create_users'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;

	if (isset($_POST['haspaid']) && is_numeric($_POST['haspaid']))
	{
		$id = intval($_POST['haspaid']);
		$today = date('Y-m-d');
		$sql = 'UPDATE ' . $wpdb->prefix . 'mr_payment SET paidday = \'' . $today . '\' WHERE id = ' . $id;
		if ($wpdb->query($sql))
		{
			echo '<div class="updated"><p><strong>Maksu merkitty maksetuksi tänään, ' . $today . '</strong></p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
	}
	else if (isset($_GET['removepayment']) && is_numeric($_GET['removepayment']))
	{
		// Mark the given payment visible=0, so it can be recovered just in case...
		$id = intval($_GET['removepayment']);
		$today = date('Y-m-d');
		$sql = 'UPDATE ' . $wpdb->prefix . 'mr_payment SET visible = 0 WHERE id = ' . $id;
		if ($wpdb->query($sql))
		{
			echo '<div class="updated"><p><strong>Maksu (' . $id . ') poistettu.</strong></p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
	}

	echo '<div class="wrap">';
	echo '<h2>Jäsenmaksut</h2>';

	mr_show_payments_lists(null); // no specific member
	echo '</div>';
}

function mr_grade_list()
{
	if (!current_user_can('create_users'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}

	echo '<div class="wrap">';
	echo '<h2>Vyöarvot</h2>';
	echo '<p>Jäsenet heidän viimeisimmän vyöarvon mukaan.</p>';
	echo '<p>Kenties tässä pitäisi olla filtterit vyöarvojen, seurojen ym mukaan.</p>';
	mr_show_grades();
	echo '</div>';
}

function mr_member_new()
{
	if (!current_user_can('create_users'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;

	// Check for possible insert
    $hidden_field_name = 'mr_submit_hidden_member';
    if( isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' )
	{
        if (mr_insert_new_member($_POST))
		{
			echo '<div class="updated"><p><strong>Uusi jäsen lisätty, nimellä: '
				. $_POST['firstname'] . ' ' . $_POST['lastname'] . '</strong></p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
    }

    ?>
	<div class="wrap">
		<h2>Lisää uusi jäsen</h2>
		<?php
		mr_new_member_form(admin_url('admin.php?page=member-register-new'), array());
		?>
	</div>

	<?php
}

function mr_payment_new()
{
	if (!current_user_can('create_users'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;


	// Check for possible insert
    $hidden_field_name = 'mr_submit_hidden_payment';
    if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' )
	{
        if (mr_insert_new_payment($_POST))
		{
			?>
			<div class="updated"><p><strong>Uusi/uudet maksu(t) lisätty</strong></p></div>
			<?php
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
    }

    ?>
	<div class="wrap">
		<h2>Lisää uusi maksu, useammalle henkilölle jos tarve vaatii</h2>
		<p>Pääasia että rahaa tulee, sitä kun menee.</p>
		<p>Viitenumero on automaattisesti laskettu ja näkyy listauksessa kun maksu on luotu.</p>
		<?php
		$sql = 'SELECT CONCAT(lastname, " ", firstname) AS name, id FROM ' . $wpdb->prefix . 'mr_member ORDER BY lastname ASC';
		$users = $wpdb->get_results($sql, ARRAY_A);
		mr_new_payment_form($users);
		?>
	</div>

	<?php

}


function mr_grade_new()
{
	if (!current_user_can('create_users'))
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
			?>
			<div class="updated"><p><strong>Uusi/uudet vyöarvo(t) lisätty</strong></p></div>
			<?php
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
    }

    ?>
	<div class="wrap">

		<h2>Myönnä vyöarvoja</h2>
		<?php
		$sql = 'SELECT CONCAT(lastname, " ", firstname) AS name, id FROM ' . $wpdb->prefix . 'mr_member ORDER BY lastname ASC';
		$users = $wpdb->get_results($sql, ARRAY_A);
		mr_grade_form($users);
		?>
	</div>

	<?php
}

/**
 * List all payments for all members.
 */
function mr_show_payments_lists($memberid)
{
	?>
	<h3>Maksamattomat maksut</h3>
	<p>Merkitse maksu maksetuksi vasemmalla olevalla "OK" painikkeella.</p>

	<?php
	mr_show_payments($memberid, true);
	?>
	<hr />
	<h3>Maksetut maksut</h3>
	<?php
	mr_show_payments($memberid, false);
}



/**
 * Show list of payments for a member,
 * for all, unpaid, paid ones.
 */
function mr_show_payments($memberid = null, $isUnpaidView = false)
{
	global $wpdb;

	$where = '';
	if ($memberid != null && is_numeric($memberid))
	{
		$where .= 'AND A.member = \'' . $memberid . '\' ';
	}
	if ($isUnpaidView)
	{
		$where .= 'AND A.paidday = \'0000-00-00\' ';
	}
	else
	{
		$where .= 'AND A.paidday != \'0000-00-00\' ';
	}
	$sql = 'SELECT A.*, B.firstname, B.lastname, B.id AS memberid FROM ' . $wpdb->prefix .
		'mr_payment A LEFT JOIN ' . $wpdb->prefix .
		'mr_member B ON A.member = B.id WHERE A.visible = 1 ' .
		$where . 'ORDER BY A.deadline DESC';
	$res = $wpdb->get_results($sql, ARRAY_A);

	$allowremove = false;

	// id member reference type amount deadline paidday validuntil club visible
	?>
	<table class="wp-list-table widefat tablesorter">
		<thead>
			<tr>
				<?php
				if ($isUnpaidView)
				{
					echo '<th filter="false">Maksettu?</th>';
				}
				if ($memberid == null)
				{
					?>
					<th>Sukunimi</th>
					<th>Etunimi</th>
					<?php
				}
				?>
				<th>Tyyppi</th>
				<th class="w8em">Summa (EUR)</th>
				<th class="w8em">Viite</th>
				<th class="headerSortUp">Eräpäivä</th>
				<?php
				if (!$isUnpaidView)
				{
					echo '<th>Maksu PVM</th>';
				}
				?>
				<th>Voimassaolo</th>
				<?php
				if ($allowremove)
				{
					echo '<th>Poista</th>';
				}
				?>
			</tr>
		</thead>
	<tbody>
	<?php
	foreach($res as $payment)
	{
		echo '<tr id="payment_' . $payment['id'] . '">';
		if ($isUnpaidView)
		{
			echo '<td>';
			if ($payment['paidday'] == '0000-00-00')
			{
				echo '<form action="admin.php?page=member-payment-list" method="post">';
				echo '<input type="hidden" name="haspaid" value="' . $payment['id'] . '" />';
				echo '<input type="submit" value="OK" /></form>';
			}
			echo '</td>';
		}
		if ($memberid == null)
		{
			$url = '<a href="' . admin_url('admin.php?page=member-register-control') .
				'&memberid=' . $payment['memberid'] . '" title="' . $payment['firstname'] .
				' ' . $payment['lastname'] . '">';
			echo '<td>' . $url . $payment['lastname'] . '</a></td>';
			echo '<td>' . $url . $payment['firstname'] . '</a></td>';
		}
		echo '<td>' . $payment['type'] . '</td>';
		echo '<td>' . $payment['amount'] . '</td>';
		echo '<td>' . $payment['reference'] . '</td>';
		echo '<td>' . $payment['deadline'] . '</td>';
		if (!$isUnpaidView)
		{
			echo '<td>' . $payment['paidday'] . '</td>';
		}
		echo '<td>' . $payment['validuntil'] . '</td>';

		// set visible to 0, do not remove for real...
		if ($allowremove)
		{
			echo '<td><a href="' . admin_url('admin.php?page=member-payment-list') .
				'&removepayment=' . $payment['id'] . '" title="Poista maksu viitteellä ' .
				$payment['reference'] . '">X</a></td>';
		}
		echo '</tr>';
	}
	?>
	</tbody>
	</table>
	<?php
}

/**
 * Show grades for a single member or for everyone
 */
function mr_show_grades($memberid = null)
{
	global $wpdb;
	global $mr_grade_values;
	global $mr_grade_types;

	$where = '';
	$order = 'B.lastname ASC, ';
	if ($memberid != null && is_numeric($memberid))
	{
		$where = 'WHERE B.id = \'' . $memberid . '\' ';
		$order = '';
	}

	$sql = 'SELECT A.*, B.firstname, B.lastname, B.id AS memberid FROM ' . $wpdb->prefix .
		'mr_grade A LEFT JOIN ' . $wpdb->prefix .
		'mr_member B ON A.member = B.id ' . $where . 'ORDER BY ' . $order . 'A.day DESC';

	$res = $wpdb->get_results($sql, ARRAY_A);

	// id member grade type location nominator day
	$items = array('firstname', 'lastname', 'memberid',
		'id', 'member', 'grade', 'type', 'location', 'nominator', 'day');
	?>
	<table class="wp-list-table widefat tablesorter">

	<thead>
	<tr>
		<?php
		if ($memberid == null)
		{
			?>
			<th class="headerSortDown">Sukunimi</th>
			<th>Etunimi</th>
			<?php
		}
		?>
		<th>Vyöarvo</th>
		<th>Laji</th>
		<th
		<?php
		if ($memberid != null)
		{
			echo ' class="headerSortUp"';
		}
		?>
		>Myöntö PVM</th>
		<th>Paikka</th>
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
		echo '<td>' . $grade['location'] . '</td>';
		echo '</tr>';
	}
	?>
	</tbody>
	</table>
	<?php
}

function mr_show_members()
{
	global $wpdb;
	global $mr_access_type;

	$items = array('user_login', 'firstname', 'lastname', 'birthdate', 'email', 'joindate');

	// id access firstname lastname birthdate address zipcode postal phone email nationality
	// joindate passnro notes lastlogin active club

	$sql = 'SELECT A.*, B.name AS nationalityname, C.id AS wpuserid FROM ' . $wpdb->prefix .
		'mr_member A LEFT JOIN ' . $wpdb->prefix . 'mr_country B ON A.nationality = B.code LEFT JOIN '
		. $wpdb->prefix . 'users C ON A.user_login = C.user_login ORDER BY A.lastname ASC';

	$members = $wpdb->get_results($sql, ARRAY_A);

	?>
	<table class="wp-list-table widefat tablesorter">
	<thead>
	<tr>
		<th class="headerSortDown">Sukunimi</th>
		<th>Etunimi</th>
		<th>Syntymäpäivä</th>
		<th>Sähköposti</th>
		<th>Puhelin</th>
		<th>Passi #</th>
		<th>Oikeudet</th>
		<th>WP käyttäjä</th>
	</tr>
	</thead>
	<tbody>

	<?php
	foreach($members as $member)
	{
		$url = '<a href="' . admin_url('admin.php?page=member-register-control') .
			'&memberid=' . $member['id'] . '" title="' . $member['firstname'] .
			' '	. $member['lastname'] . '|Osoite: ' . $member['address'] . ', ' .
			$member['zipcode'] . ' ' . $member['postal'] . '|Kansallisuus: ' . $member['nationalityname'] .
			'|Liittymispäivä: ' . $member['joindate'] . '" class="tip">';

		echo '<tr id="user_' . $member['id'] . '">';
		echo '<td';
		if (intval($member['active']) == 0)
		{
			echo ' class="redback"';
		}
		echo '>' . $url . $member['lastname'] . '</a></td>';
		echo '<td>' . $url . $member['firstname'] . '</a></td>';
		echo '<td>' . $member['birthdate'] . '</td>';
		echo '<td>' . $member['email'] . '</td>';
		echo '<td>' . $member['phone'] . '</td>';
		echo '<td>' . $member['passnro'] . '</td>';
		echo '<td title="' . $mr_access_type[$member['access']] . '">' . $member['access'] . '</td>';
		echo '<td>';
		if ($member['user_login'] != '' && $member['user_login'] != null && is_numeric($member['wpuserid']))
		{
			echo '<a href="' . admin_url('user-edit.php?user_id=') . $member['wpuserid'] .
				'" title="Muokkaa WP käyttäjää">' . $member['user_login'] . '</a>';
		}
		echo  '</td>';

		echo '</tr>';
	}
	?>
	</tbody>
	</table>
	<?php
}

/**
 * Show all possible information of the given user.
 */
function mr_show_member_info($id)
{
	global $wpdb;
	global $mr_grade_values;
	global $mr_grade_types;

	$id = intval($id);

	// Check for possible insert
    if (isset($_POST['mr_submit_hidden_member']) && $_POST['mr_submit_hidden_member'] == 'Y' )
	{
        if (mr_update_member_info($_POST))
		{
			?>
			<div class="updated"><p><strong>Jäsenen tiedot päivitetty</strong></p></div>
			<?php
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
    }
    else if (isset($_POST['mr_submit_hidden_grade']) && $_POST['mr_submit_hidden_grade'] == 'Y' )
	{
        if (mr_insert_new_grade($_POST))
		{
			?>
			<div class="updated"><p><strong>Uusi vyöarvo lisätty</strong></p></div>
			<?php
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
    }


	// ---------------

	$items = array('id', 'user_login', 'access', 'firstname', 'lastname',
		'birthdate', 'address', 'zipcode', 'postal', 'phone', 'email',
		'nationality', 'joindate', 'passnro', 'notes', 'lastlogin', 'active',
		'club');
	$sql = 'SELECT ' . implode(', ', $items) . ' FROM ' . $wpdb->prefix . 'mr_member WHERE id = ' . $id . ' LIMIT 1';
	$person = $wpdb->get_row($sql, ARRAY_A);

	echo '<h1>' . $person['firstname'] . ' ' . $person['lastname'] . '</h1>';
	if (isset($_GET['edit']))
	{
		mr_new_member_form(admin_url('admin.php?page=member-register-control') . '&memberid=' . $id, $person);
	}
	else
	{
		?>
		<h3>Henkilötiedot</h3>
		<table class="wp-list-table widefat users">
		<tbody>
		<?php
		foreach($items as $item)
		{
			echo '<tr>';
			echo '<th>' . $item . '</th>';
			echo '<td>' . $person[$item] . '</td>';
			echo '</tr>';
		}
		?>
		</tbody>
		</table>
		<?php
		echo '<p><a href="' . admin_url('admin.php?page=member-register-control') . '&memberid='
			. $id . '&edit" title="Muokkaa tätä käyttää" class="button-primary">Muokkaa tätä käyttää</a></p>';
	}

	// ---------------
	echo '<hr />';
	echo '<h2>Vyöarvot</h2>';
	mr_show_grades($id);

	// Quick add a grade
	mr_grade_quick_form(array(
		'id' => $id,
		'name' => $person['firstname'] . ' ' . $person['lastname']
	));
	?>

	<hr />
	<h2>Jäsenmaksut</h2>
	<?php

	// ---------------

	mr_show_payments_lists($id);

}


// missing country list
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
		  type enum('Yuishinkai','Kobujutsu') COLLATE utf8_swedish_ci NOT NULL DEFAULT 'Yuishinkai',
		  location varchar(255) COLLATE utf8_swedish_ci NOT NULL,
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
		  member mediumint(5) unsigned NOT NULL COMMENT 'User ID in mr_member',
		  reference mediumint(6) unsigned NOT NULL DEFAULT '0',
		  type varchar(24) COLLATE utf8_swedish_ci NOT NULL,
		  amount float(8,2) NOT NULL DEFAULT '0.00',
		  deadline date DEFAULT '0000-00-00' NOT NULL,
		  paidday date DEFAULT '0000-00-00' NOT NULL,
		  validuntil date DEFAULT '0000-00-00' NOT NULL,
		  club mediumint(6) unsigned NOT NULL COMMENT 'Club ID in mr_club',
		  PRIMARY KEY (id),
		  KEY member (member)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;";

		dbDelta($sql);
	}

	$table_name = $wpdb->prefix . 'mr_forum_post';
	if ($wpdb->get_var("show tables like '" . $table_name. "'") != $table_name)
	{
		$sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(6) NOT NULL AUTO_INCREMENT,
		  topic mediumint(6) NOT NULL,
		  content text COLLATE utf8_swedish_ci NOT NULL,
		  member mediumint(6) NOT NULL COMMENT 'User ID in mr_member',
		  created int(10) NOT NULL COMMENT 'Unix timestamp',
		  visible tinyint(1) NOT NULL DEFAULT '1',
		  PRIMARY KEY (id)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;";

		dbDelta($sql);
	}

	$table_name = $wpdb->prefix . 'mr_forum_topic';
	if ($wpdb->get_var("show tables like '" . $table_name. "'") != $table_name)
	{
		$sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(6) NOT NULL AUTO_INCREMENT,
		  title varchar(250) COLLATE utf8_swedish_ci NOT NULL,
		  member mediumint(6) NOT NULL COMMENT 'User ID in mr_member',
		  access tinyint(2) NOT NULL DEFAULT '0' COMMENT 'Minimum access level needed to see',
		  created int(10) unsigned NOT NULL COMMENT 'Unix timestamp',
		  visible tinyint(1) NOT NULL DEFAULT '1',
		  UNIQUE KEY id (id)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;";

		dbDelta($sql);
	}


	add_option('mr_db_version', $mr_db_version);
}

