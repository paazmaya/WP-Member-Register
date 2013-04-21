<?php
/**
 Plugin Name: Member Register
 Plugin URI: http://paazmaya.com/member-register-wordpress-plugin
 Description: A register of member which can be linked to a WP users. Includes payment (and martial art belt grade) information.
 Version: 0.11.0
 License: Creative Commons Share-Alike-Attribute 3.0
 Author: Jukka Paasonen
 Author URI: http://paazmaya.com
*/

/**
 * add field to user profiles
 */


define ('MEMBER_REGISTER_VERSION', '0.11.0');

global $mr_file_base_directory;
$mr_file_base_directory = substr(__DIR__, 0, strpos(__DIR__, '/public_html')) . '/member_register_files';

global $mr_date_format;
$mr_date_format = 'Y-m-d H:i:s';

global $mr_db_version;
$mr_db_version = '11';

global $mr_grade_values;
$mr_grade_values = array(
	'5K' => '5 kyu',
	'5h' => '5 kyu + ' . __('raita'),
	'4K' => '4 kyu',
	'4h' => '4 kyu + ' . __('raita'),
	'3K' => '3 kyu',
	'3h' => '3 kyu + ' . __('raita'),
	'2K' => '2 kyu',
	'2h' => '2 kyu + ' . __('raita'),
	'1K' => '1 kyu',
	'1h' => '1 kyu + ' . __('raita'),
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

global $mr_martial_arts;
$mr_martial_arts = array(
	'karate' => 'Yuishinkai Karate',
	'kobujutsu' => 'Ryukyu Kobujutsu',
	'taiji' => 'Taiji',
	'judo' => 'Goshin Judo'
);

define('MR_ACCESS_OWN_INFO', 1 << 0); // 1
define('MR_ACCESS_FILES_VIEW', 1 << 1); // 2
define('MR_ACCESS_CONVERSATION', 1 << 2); // 4
define('MR_ACCESS_FORUM_CREATE', 1 << 3); // 8
define('MR_ACCESS_FORUM_DELETE', 1 << 4); // 16
define('MR_ACCESS_MEMBERS_VIEW', 1 << 5); // 32
define('MR_ACCESS_MEMBERS_EDIT', 1 << 6); // 64
define('MR_ACCESS_GRADE_MANAGE', 1 << 7); // 128
define('MR_ACCESS_PAYMENT_MANAGE', 1 << 8); // 256
define('MR_ACCESS_CLUB_MANAGE', 1 << 9); // 512
define('MR_ACCESS_FILES_MANAGE', 1 << 10); // 1024
define('MR_ACCESS_GROUP_MANAGE', 1 << 11); // 2048

global $mr_access_type;
$mr_access_type = array(
	1 => __('Omien tietojen katselu ja päivitys'),
	2 => __('Tiedostot jäsenille'),
	4 => __('Keskusteluun osallistuminen'),
	8 => __('Keskusteluaiheiden luominen'),
	16 => __('Keskustelujen ja keskusteluaiheiden poisto'),
	32 => __('Jäsenten listaus ja tietojen näkeminen'),
	64 => __('Jäsenten lisääminen, muokkaus ja poisto'),
	128 => __('Vyöarvojen hallinta'),
	256 => __('Jäsenmaksujen hallinta'),
	512 => __('Seurojen hallinta'),
	1024 => __('Tiedostojen hallinta'),
	2048 => __('Ryhmien hallinta')
);


require 'member-functions.php';
require 'member-member.php';
require 'member-grade.php';
require 'member-payment.php';
require 'member-forum.php';
require 'member-group.php';
require 'member-club.php';
require 'member-files.php';
require 'member-install.php';
require 'member-prf.php';

load_plugin_textdomain( 'member-register', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

register_activation_hook(__FILE__, 'mr_install');
//register_uninstall_hook( __FILE__, 'member_register_uninstall');

// Check Member Register related access data
add_action('init', 'member_register_wp_loaded');
add_action('init', 'member_register_file_download');
add_action('init', 'member_register_public_reg_form');

// http://codex.wordpress.org/Function_Reference/add_action
add_action('admin_init', 'member_register_admin_init');
add_action('admin_menu', 'member_register_admin_menu');
add_action('admin_menu', 'member_register_forum_menu');
add_action('admin_menu', 'member_register_files_menu');
add_action('admin_print_styles', 'member_register_admin_print_styles');
add_action('admin_print_scripts', 'member_register_admin_print_scripts');
add_action('admin_head', 'member_register_admin_head');

// http://codex.wordpress.org/Plugin_API/Action_Reference/profile_update
add_action('profile_update', 'member_register_profile_update');


// Login and logout
add_action('wp_login', 'member_register_login');
add_action('wp_logout', 'member_register_logout');

/**
 * Hooks for additional items in the public registration form.
 * http://codex.wordpress.org/Customizing_the_Registration_Form
 * Member Register plugin uses the 'mr_mr_prf_' prefix for these functions.
 */
function member_register_public_reg_form() 
{
	add_action('register_form', 'mr_prf_register_form');
	add_filter('registration_errors', 'mr_prf_registration_errors', 10, 3);
	add_action('user_register', 'mr_prf_user_register');
}

// http://tablesorter.com/docs/
// http://bassistance.de/jquery-plugins/jquery-plugin-validation/
function member_register_admin_init()
{
	wp_register_script('jquery-bassistance-validation', plugins_url('/js/jquery.validate.min.js', __FILE__), array('jquery')); // 1.9.0
	wp_register_script('jquery-bassistance-validation-messages-fi', plugins_url('/js/messages_fi.js', __FILE__), array('jquery'));
	wp_register_script('jquery-tablesorter', plugins_url('/js/jquery.tablesorter.min.js', __FILE__), array('jquery'));
	wp_register_script('jquery-ui-datepicker-fi', plugins_url('/js/jquery.ui.datepicker-fi.js', __FILE__), array('jquery'));
	wp_register_script('jquery-chosen', plugins_url('/js/chosen.jquery.min.js', __FILE__), array('jquery')); // 0.9.7
	wp_register_script('jquery-picnet-table-filter', plugins_url('/js/picnet.table.filter.min.js', __FILE__), array('jquery'));

	wp_register_style('jquery-ui-theme-blizter',  plugins_url('/css/jquery-ui.blizter.css', __FILE__));
	wp_register_style('jquery-ui-datepicker',  plugins_url('/css/jquery.ui.datepicker.css', __FILE__));
	wp_register_style('jquery-tablesorter',  plugins_url('/css/jquery.tablesorter.css', __FILE__));
	wp_register_style('jquery-chosen',  plugins_url('/css/chosen.css', __FILE__));
	wp_register_style('mr-styles',  plugins_url('/css/mr-styles.css', __FILE__));
}

function member_register_admin_print_scripts()
{
	// http://codex.wordpress.org/Function_Reference/wp_enqueue_script
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-datepicker');

	wp_enqueue_script('jquery-ui-datepicker-fi');

	wp_enqueue_script('jquery-bassistance-validation');
	wp_enqueue_script('jquery-bassistance-validation-messages-fi');
	wp_enqueue_script('jquery-tablesorter');
	wp_enqueue_script('jquery-chosen');
	wp_enqueue_script('jquery-picnet-table-filter');
}

function member_register_admin_print_styles()
{
	// http://codex.wordpress.org/Function_Reference/wp_enqueue_style
	wp_enqueue_style('jquery-ui-datepicker');
	wp_enqueue_style('jquery-ui-theme-blizter');
	wp_enqueue_style('jquery-tablesorter');
	wp_enqueue_style('jquery-chosen');
	wp_enqueue_style('mr-styles');
}

function member_register_file_download()
{
	// Before going any further, check if there is a request for a file download
	if (isset($_GET['download']) && $_GET['download'] != '')
	{
		// This might call exit()
		mr_file_download($_GET['download']);
	}
}

function member_register_admin_head()
{
	// jQuery is in noConflict state while in Wordpress...
	?>
	<script type="text/javascript">
		var hideLink = '<a href="#hide"><img src="<?php echo plugins_url('/images/hide_icon.png', __FILE__); ?>" alt="<?php echo __('Piilota'); ?>" /></a>';

		jQuery(document).ready(function(){
			jQuery.datepicker.setDefaults({
				showWeek: true,
				changeMonth: true,
				changeYear: true,
				yearRange: '1920:2060',
				numberOfMonths: 1,
				dateFormat: 'yy-mm-dd'
			});
			jQuery('input.pickday').datepicker();
			jQuery('table.tablesorter').tablesorter();			
			jQuery('select.chosen').chosen({
				allow_single_deselect: true
			});
			jQuery('form').validate();
			jQuery('table.tablesorter').tableFilter({
				enableCookies: false
			});

			// Removal button should ask the user: are you sure?
			jQuery('a[rel="remove"]').click(function() {
				var title = jQuery(this).attr('title');
				return confirm(title);
			});
			
			jQuery('th.hideable').prepend(hideLink);
			
			// Hide table columns
			jQuery('th.hideable a[href="#hide"]').click(function() {
				var inx = jQuery(this).parent().index() + 1;
				console.log("inx: " + inx);
				var table = jQuery(this).parentsUntil('table').parent();
				var text = jQuery(this).parent().text();
				console.log("text: " + text);
				
				var showLink = '<a href="#show" title="' + text + '">' + text + '</a>';
				jQuery('table caption').append(showLink);
				
				table.find('tr td:nth-child(' + inx + ')').hide();
				table.find('tr th:nth-child(' + inx + ')').hide();
				
				return false;
			});
			
			// Show the column again
			jQuery('table caption a[href="#show"]').live('click', function() {
				var text = jQuery(this).text();
				var inx = jQuery('thead th:contains(' + text + ')').index();
				console.log("show: " + text + ", inx: " + inx);
				
				var table = jQuery(this).parentsUntil('table').parent();
				
				table.find('tr td:nth-child(' + inx + ')').show();
				table.find('tr th:nth-child(' + inx + ')').show();
				
				jQuery(this).remove();
				
				return false;
			});
		});

	</script>
	<?php
	// http://www.picnet.com.au/picnet_table_filter.html
}

function member_register_admin_menu()
{
	// http://codex.wordpress.org/Adding_Administration_Menus
	add_menu_page(__('Jäsenrekisterin Hallinta'), __('Jäsenrekisteri'), 'read', 'member-register-control',
		'mr_member_list', plugins_url('/images/people.jpg', __FILE__)); // $position );
	
	if (mr_has_permission(MR_ACCESS_MEMBERS_EDIT))
	{
		add_submenu_page('member-register-control', __('Lisää uusi jäsen'),
			__('Uusi jäsen'), 'read', 'member-register-new', 'mr_member_new');
	}
	
	if (mr_has_permission(MR_ACCESS_PAYMENT_MANAGE))
	{
		add_submenu_page('member-register-control', __('Hallinnoi jäsenmaksuja'),
			__('Jäsenmaksut'), 'read', 'member-payment-list', 'mr_payment_list');
	}
	
	if (mr_has_permission(MR_ACCESS_PAYMENT_MANAGE))
	{
		add_submenu_page('member-register-control', __('Uusi maksu'),
			__('Uusi maksu'), 'read', 'member-payment-new', 'mr_payment_new');
	}
	
	if (mr_has_permission(MR_ACCESS_GRADE_MANAGE))
	{
		add_submenu_page('member-register-control', __('Vyöarvot'),
			__('Vyöarvot'), 'read', 'member-grade-list', 'mr_grade_list');
	}
	
	if (mr_has_permission(MR_ACCESS_GRADE_MANAGE))
	{
		add_submenu_page('member-register-control', __('Myönnä vyöarvoja'),
			__('Myönnä vyöarvoja'), 'read', 'member-grade-new', 'mr_grade_new');
	}
	
	if (mr_has_permission(MR_ACCESS_CLUB_MANAGE))
	{
		add_submenu_page('member-register-control', __('Seurat'),
			__('Jäsenseurat'), 'read', 'member-club-list', 'mr_club_list');
	}
	
	if (mr_has_permission(MR_ACCESS_GROUP_MANAGE))
	{
		add_submenu_page('member-register-control', __('Ryhmät'),
			__('Jäsen ryhmät'), 'read', 'member-group-list', 'mr_group_list');
	}

}


/**
 * Any member with login access can use the forum
 * http://codex.wordpress.org/Roles_and_Capabilities#Subscriber
 */
function member_register_forum_menu()
{
	if (current_user_can('read') && mr_has_permission(MR_ACCESS_CONVERSATION))
	{
		// http://codex.wordpress.org/Adding_Administration_Menus
		add_menu_page(__('Keskustelu'), __('Keskustelu'), 'read', 'member-forum',
			'mr_forum_list', plugins_url('/images/forum.png', __FILE__)); // $position );
	}
}

function member_register_files_menu()
{
	if (current_user_can('read') && mr_has_permission(MR_ACCESS_FILES_VIEW))
	{
		// http://codex.wordpress.org/Adding_Administration_Menus
		add_menu_page(__('Tiedostot'), __('Tiedostot'), 'read', 'member-files',
			'mr_files_list', plugins_url('/images/folder.gif', __FILE__)); // $position );

		if (mr_has_permission(MR_ACCESS_FILES_MANAGE))
		{
			add_submenu_page('member-files', __('Lisää uusi tiedosto'),
				__('Lisää uusi tiedosto'), 'read', 'member-files-new', 'mr_files_new');
		}
	}
}


// http://codex.wordpress.org/Plugin_API/Action_Reference/profile_update
function member_register_profile_update($user_id, $old_user_data = null)
{
	/*
	echo '<p>member_register_profile_update, used_id: ' . $user_id . '</p>';
	if (isset($old_user_data))
	{
		echo '<pre>';
		print_r($old_user_data);
		echo '</pre>';
	}
	*/
}


/**
 * Check which user logged in to WP and set Session Access variable.
 */
function member_register_login()
{
	global $wpdb;
	global $userdata;

	/*
	echo '<pre>';
	print_r($userdata);
	echo '</pre>';
	*/
}

function member_register_logout()
{
	global $userdata;
}


/**
 * As a hack, this function also updates the last login time.
 */
function member_register_wp_loaded()
{
	global $wpdb;
	global $userdata;

	// http://codex.wordpress.org/User:CharlesClarkson/Global_Variables
	if (isset($userdata->user_login) && $userdata->user_login != '' &&
		(!isset($userdata->mr_access) || !is_numeric($userdata->mr_access) ||
		!isset($userdata->mr_memberid) || !is_numeric($userdata->mr_memberid)))
	{
		$sql = 'SELECT id, access FROM ' . $wpdb->prefix . 'mr_member WHERE user_login = \'' .
			mr_htmlent($userdata->user_login) . '\' AND active = 1 LIMIT 1';
		$res = $wpdb->get_row($sql, ARRAY_A);
		if ($wpdb->num_rows == 1)
		{
			$userdata->mr_access = intval($res['access']);
			$userdata->mr_memberid = intval($res['id']);

			$wpdb->update(
				$wpdb->prefix . 'mr_member',
				array(
					'lastlogin' => time()
				),
				array(
					'user_login' => $userdata->user_login,
					'active' => 1
				),
				array(
					'%d'
				),
				array(
					'%s',
					'%d'
				)
			);
		}
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
	if (!current_user_can('read'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}

	global $userdata;

	echo '<div class="wrap">';

	// Check for requested member
	$memberid = isset($_GET['memberid']) && is_numeric($_GET['memberid']) ? intval($_GET['memberid']) : '';

	// But if the current user has no rights, show only their own info, if rights for that exist.
	if (!mr_has_permission(MR_ACCESS_MEMBERS_VIEW))
	{
		$memberid = $userdata->mr_memberid;
	}

	if ($memberid != '')
	{
		mr_show_member_info($memberid);
	}
	else
	{
		echo '<h2>' . __('Jäsenrekisteri') . '</h2>';
		echo '<p>' . __('Alla lista rekisteröidyistä jäsenistä') . '</p>';
		mr_show_members();
	}
	echo '</div>';
}




