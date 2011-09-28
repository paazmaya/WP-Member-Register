<?php
/**
 Plugin Name: Member Register
 Plugin URI: http://paazio.nanbudo.fi/member-register-wordpress-plugin
 Description: A register of member which can be linked to a WP users. Includes payment (and martial art belt grade) information.
 Version: 0.6.0
 License: Creative Commons Share-Alike-Attribute 3.0
 Author: Jukka Paasonen
 Author URI: http://paazmaya.com
*/

/**
 * add field to user profiles
 */


define ('MEMBER_REGISTER_VERSION', '0.6.0');

global $mr_date_format;
$mr_date_format = 'Y-m-d H:i:s';

global $mr_db_version;
$mr_db_version = '8';

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
	8 => 'Jäsenmaksujen ja seurojen hallinta',
	9 => 'Vyöarvojen hallinta',
	10 => 'Kaikki mahdollinen mitä täällä ikinä voi tehdä'
);
require 'member-functions.php';
require 'member-forum.php';
require 'member-install.php';

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
	add_menu_page(__('Jäsenrekisterin Hallinta'), __('Jäsenrekisteri'), 'create_users', 'member-register-control',
		'mr_member_list', plugins_url('/images/people.jpg', __FILE__)); // $position );
	add_submenu_page('member-register-control', __('Lisää uusi jäsen'),
		__('Uusi jäsen'), 'create_users', 'member-register-new', 'mr_member_new');
	add_submenu_page('member-register-control', __('Hallinnoi jäsenmaksuja'),
		__('Jäsenmaksut'), 'create_users', 'member-payment-list', 'mr_payment_list');
	add_submenu_page('member-register-control', __('Uusi maksu'),
		__('Uusi maksu'), 'create_users', 'member-payment-new', 'mr_payment_new');
	add_submenu_page('member-register-control', __('Vyöarvot'),
		__('Vyöarvot'), 'create_users', 'member-grade-list', 'mr_grade_list');
	add_submenu_page('member-register-control', __('Myönnä vyöarvoja'),
		__('Myönnä vyöarvoja'), 'create_users', 'member-grade-new', 'mr_grade_new');
	add_submenu_page('member-register-control', __('Seurat'),
		__('Jäsenseurat'), 'create_users', 'member-club-list', 'mr_club_list');

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
		add_menu_page(__('Keskustelu'), __('Keskustelu'), 'read', 'member-forum',
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
	/*
	global $userdata;
	echo '<pre>';
	print_r($userdata);
	echo '</pre>';
	*/

	echo '<div class="wrap">';

	if (isset($_GET['memberid']) && is_numeric($_GET['memberid']))
	{
		mr_show_member_info(intval($_GET['memberid']));
	}
	else
	{
		echo '<h2>' . __('Jäsenrekisteri') . '</h2>';
		echo '<p>' . __('Alla lista rekisteröidyistä jäsenistä') . '</p>';
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
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Maksu merkitty maksetuksi tänään') . ', ' . $today . '</strong>';
			echo '</p></div>';
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
		$sql = 'UPDATE ' . $wpdb->prefix . 'mr_payment SET visible = 0 WHERE id = ' . $id;
		if ($wpdb->query($sql))
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Maksu poistettu') . ' (' . $id . ')</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
	}

	echo '<div class="wrap">';
	echo '<h2>' . __('Jäsenmaksut') . '</h2>';

	mr_show_payments_lists(null); // no specific member
	echo '</div>';
}

function mr_grade_list()
{
	if (!current_user_can('create_users') && $userdata->mr_access >= 9)
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}

	global $wpdb;
	
	if (isset($_GET['removegrade']) && is_numeric($_GET['removegrade']))
	{
		// Mark the given grade visible=0, so it can be recovered just in case...
		$id = intval($_GET['removegrade']);
		$sql = 'UPDATE ' . $wpdb->prefix . 'mr_grade SET visible = 0 WHERE id = ' . $id;
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
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'mr_club WHERE id = ' . $id . ' LIMIT 1';
		$res = $wpdb->get_row($sql, ARRAY_A);
		
		echo '<h1>' . $res['name'] . '</h1>';
		echo '<p>' . __('Aktiiviset jäsenet tässä seurassa.') . '</p>';
		echo '<p><a href="' . admin_url('admin.php?page=member-club-list') . '&edit-club=' .
			$id . '" title="' . __('Muokkaa tätä seuraa') . '" class="button-primary">' . __('Muokkaa tätä seuraa') . '</a></p>';
		mr_show_members(array(
			'club' => intval($_GET['club']),
			'active' => true
		));
		
	}
	else if (isset($_GET['edit-club']) && is_numeric($_GET['edit-club']))
	{
		$id = intval($_GET['edit-club']);
	}
	else 
	{
		echo '<h1>' . __('Jäsenseurat') . '</h1>';
		echo '<p>' . __('Suomen Yuishinkai-liiton Jäsenseurat.') . '</p>';
		echo '<p>' . __('Paikat joissa harjoitellaan Yuishinkai karatea ja/tai Ryukyu kobujutsua.') . '</p>';
		mr_show_clubs();
	}
	
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
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Uusi jäsen lisätty, nimellä:') . ' ' . $_POST['firstname'] . ' ' . $_POST['lastname'] . '</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
    }

    ?>
	<div class="wrap">
		<h2><?php echo __('Lisää uusi jäsen'); ?></h2>
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
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Uusi/uudet maksu(t) lisätty') . '</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
    }

    ?>
	<div class="wrap">
		<h2><?php echo __('Lisää uusi maksu, useammalle henkilölle jos tarve vaatii'); ?></h2>
		<p><?php echo __('Pääasia että rahaa tulee, sitä kun menee.'); ?></p>
		<p><?php echo __('Viitenumero on automaattisesti laskettu ja näkyy listauksessa kun maksu on luotu.'); ?></p>
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

/**
 * List all payments for all members.
 */
function mr_show_payments_lists($memberid)
{
	?>
	<h3><?php echo __('Maksamattomat maksut'); ?></h3>
	<p><?php echo __('Merkitse maksu maksetuksi vasemmalla olevalla "OK" painikkeella.'); ?></p>

	<?php
	mr_show_payments($memberid, true);
	?>
	<hr />
	<h3><?php echo __('Maksetut maksut'); ?></h3>
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

	$allowremove = true;

	// id member reference type amount deadline paidday validuntil club visible
	?>
	<table class="wp-list-table widefat tablesorter">
		<thead>
			<tr>
				<?php
				if ($isUnpaidView)
				{
					echo '<th filter="false">' . __('Maksettu?') . '</th>';
				}
				if ($memberid == null)
				{
					?>
					<th><?php echo __('Sukunimi'); ?></th>
					<th><?php echo __('Etunimi'); ?></th>
					<?php
				}
				?>
				<th><?php echo __('Tyyppi'); ?></th>
				<th class="w8em"><?php echo __('Summa (EUR)'); ?></th>
				<th class="w8em"><?php echo __('Viite'); ?></th>
				<th class="headerSortUp"><?php echo __('Eräpäivä'); ?></th>
				<?php
				if (!$isUnpaidView)
				{
					echo '<th>' . __('Maksu PVM') . '</th>';
				}
				?>
				<th><?php echo __('Voimassaolo'); ?></th>
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
				'&removepayment=' . $payment['id'] . '" title="' . __('Poista maksu viitteellä') . ' ' .
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
		'mr_member B ON A.member = B.id AND A.visible = 1 ' . $where . 'ORDER BY ' . $order . 'A.day DESC';

	//echo '<div class="error"><p>' . $sql . '</p></div>';
	
	$res = $wpdb->get_results($sql, ARRAY_A);

	// id member grade type location nominator day visible
	
	$allowremove = true;
	?>
	<table class="wp-list-table widefat tablesorter">

	<thead>
	<tr>
		<?php
		if ($memberid == null)
		{
			?>
			<th class="headerSortDown"><?php echo __('Sukunimi'); ?></th>
			<th><?php echo __('Etunimi'); ?></th>
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
		echo '<td>' . $grade['location'] . '</td>';
		// set visible to 0, do not remove for real...
		if ($allowremove)
		{
			echo '<td><a href="' . admin_url('admin.php?page=member-grade-list') .
				'&removegrade=' . $grade['id'] . '" title="' . __('Poista vyöarvo') . ' ' .
				$grade['grade'] . '">X</a></td>';
		}
		echo '</tr>';
	}
	?>
	</tbody>
	</table>
	<?php
}

function mr_show_clubs()
{
	global $wpdb;
	
	// id, name, address, visible
	
	$sql = 'SELECT A.*, COUNT(B.id) AS members FROM ' . $wpdb->prefix . 
		'mr_club A LEFT JOIN ' . $wpdb->prefix . 
		'mr_member B ON B.club = A.id WHERE A.visible = 1 GROUP BY B.club ORDER BY A.name ASC';

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
		echo '<tr id="user_' . $club['id'] . '">';
		echo '<td>' . $club['name'] . '</td>';
		echo '<td>' . $club['address'] . '</td>';
		echo '<td><a href="' . admin_url('admin.php?page=member-club-list') . '&club=' . $club['id'] .
			'" title="">' . $club['members'] . '</a></td>';
		// set visible to 0, do not remove for real...
		if ($allowremove)
		{
			echo '<td><a href="' . admin_url('admin.php?page=member-grade-list') .
				'&removegrade=' . $club['id'] . '" title="' . __('Poista seura') . ' ' .
				$club['name'] . '">X</a></td>';
		}
		echo '</tr>';
	}
	?>
	</tbody>
	</table>
	<?php
}

/**
 * Show a table of members based on the given filter if any.
 */
function mr_show_members($filters = null)
{
	global $wpdb;
	global $mr_access_type;
	
	// Possible filter options: club, active
	
	$wheres = array();
	$where = '';
	if (is_array($filters))
	{
		if (isset($filters['club']) && is_numeric($filters['club']))
		{
			$wheres[] = ' A.club = ' . intval($filters['club']);
		}
		if (isset($filters['active']) && is_bool($filters['active']))
		{
			$wheres[] = ' A.active = ' . ($filters['active'] ? 1 : 0);
		}
		if (count($wheres) > 0)
		{
			$where = ' WHERE ' . implode(' AND', $wheres);
		}
	}

	// id access firstname lastname birthdate address zipcode postal phone email nationality
	// joindate passnro notes lastlogin active club

	$sql = 'SELECT A.*, B.name AS nationalityname, C.id AS wpuserid FROM ' . $wpdb->prefix .
		'mr_member A LEFT JOIN ' . $wpdb->prefix . 'mr_country B ON A.nationality = B.code LEFT JOIN '
		. $wpdb->prefix . 'users C ON A.user_login = C.user_login' . $where . ' ORDER BY A.lastname ASC';

	echo '<div class="error"><p>' . $sql . '</p></div>';
	
	$members = $wpdb->get_results($sql, ARRAY_A);

	?>
	<table class="wp-list-table widefat tablesorter">
	<thead>
	<tr>
		<th class="headerSortDown"><?php echo __('Sukunimi'); ?></th>
		<th><?php echo __('Etunimi'); ?></th>
		<th><?php echo __('Syntymäpäivä'); ?></th>
		<th><?php echo __('Sähköposti'); ?></th>
		<th><?php echo __('Puhelin'); ?></th>
		<th><?php echo __('Passi #'); ?></th>
		<th><?php echo __('Oikeudet'); ?></th>
		<th><?php echo __('Viimeksi vieraillut'); ?></th>
		<th><?php echo __('WP käyttäjä'); ?></th>
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
		echo '<td>';
		if ($member['birthdate'] != '0000-00-00') 
		{
			echo $member['birthdate'];
		}
		echo '</td>';
		echo '<td>' . $member['email'] . '</td>';
		echo '<td>' . $member['phone'] . '</td>';
		echo '<td>' . $member['passnro'] . '</td>';
		echo '<td title="' . $mr_access_type[$member['access']] . '">' . $member['access'] . '</td>';
		echo '<td>';
		if ($member['lastlogin'] > 0)
		{
			echo date($mr_date_format, $member['lastlogin']);
		}
		echo '</td>';
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
	global $mr_date_format;
	global $mr_access_type;
	global $mr_grade_values;
	global $mr_grade_types;

	$id = intval($id);

	// Check for possible insert
    if (isset($_POST['mr_submit_hidden_member']) && $_POST['mr_submit_hidden_member'] == 'Y' )
	{
        if (mr_update_member_info($_POST))
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Jäsenen tiedot päivitetty') . '</strong>';
			echo '</p></div>';
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
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Uusi vyöarvo lisätty') . '</strong>';
			echo '</p></div>';
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
	$sql = 'SELECT A.*, B.name AS nationalitycountry FROM ' . $wpdb->prefix . 'mr_member A LEFT JOIN ' .
		$wpdb->prefix . 'mr_country B ON A.nationality = B.code WHERE A.id = ' . $id . ' LIMIT 1';
	$person = $wpdb->get_row($sql, ARRAY_A);

	echo '<h1>' . $person['firstname'] . ' ' . $person['lastname'] . '</h1>';
	if (isset($_GET['edit']))
	{
		mr_new_member_form(admin_url('admin.php?page=member-register-control') . '&memberid=' . $id, $person);
	}
	else
	{
		?>
		<h3><?php echo __('Henkilötiedot'); ?></h3>
		<table class="wp-list-table widefat users">
		<tbody>
			<tr>
				<th><?php echo __('Sukunimi'); ?></th>
				<td><?php echo $person['lastname']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Etunimi'); ?></th>
				<td><?php echo $person['firstname']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Kirjautumistaso'); ?> <span class="description">()</span></th>
				<td><?php echo $mr_access_type[$person['access']]; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Syntymäpäivä'); ?></th>
				<td><?php echo $person['birthdate']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Osoite'); ?></th>
				<td><?php echo $person['address']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Postinumero'); ?></th>
				<td><?php echo $person['zipcode']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Postitoimipaikka'); ?> <span class="description">(<?php echo __('ja maa jos ei Suomi'); ?>)</span></th>
				<td><?php echo $person['postal']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Puhelin'); ?></th>
				<td><?php echo $person['phone']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Sähköposti'); ?></th>
				<td><a href="mailto:<?php echo $person['email']; ?>" title="Lähetä sähköpostia"><?php echo $person['email']; ?></a></td>
			</tr>
			<tr>
				<th><?php echo __('Kansallisuus'); ?></th>
				<td><?php echo $person['nationalitycountry']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Liittymispäivä'); ?></th>
				<td><?php echo $person['joindate']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Yuishinkai passinumero'); ?> <span class="description">()</span></th>
				<td><?php echo $person['passnro']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Lisätietoja'); ?> <span class="description">(<?php echo __('vapaasti kirjoiteltu'); ?>)</span></th>
				<td><?php echo $person['notes']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('Viimeksi vieraillut sivuilla'); ?></th>
				<td><?php echo date($mr_date_format, $person['lastlogin']); ?></td>
			</tr>
			<tr>
				<th><?php echo __('Aktiivinen'); ?> <span class="description">(s<?php echo __('aako kirjautua sivuille'); ?>)</span></th>
				<td><?php echo $person['active']; ?></td>
			</tr>
			<tr>
				<th><?php echo __('WP käyttäjä'); ?> <span class="description">(<?php echo __('mikäli sellainen on'); ?>)</span></th>
				<td><?php echo $person['user_login']; ?></td>
			</tr>
		</tbody>
		</table>
		<?php
		echo '<p><a href="' . admin_url('admin.php?page=member-register-control') . '&memberid='
			. $id . '&edit" title="' . 'Muokkaa tätä käyttää" class="button-primary">Muokkaa tätä käyttää</a></p>';
	}

	// ---------------
	echo '<hr />';
	echo '<h2>' . 'Vyöarvot' . '</h2>';
	mr_show_grades($id);

	// Quick add a grade
	mr_grade_quick_form(array(
		'id' => $id,
		'name' => $person['firstname'] . ' ' . $person['lastname']
	));
	?>

	<hr />
	<h2><?php echo __('Jäsenmaksut'); ?></h2>
	<?php

	// ---------------

	mr_show_payments_lists($id);

}


