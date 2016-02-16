<?php
/**
 * Plugin Name: Member Register
 * Plugin URI: http://paazmaya.com/member-register-a-wordpress-plugin
 * Description: A register of member which can be linked to a WP users. Includes payment (and martial art belt grade) information.
 * Version: 0.14.0
 * License: MIT
 * License URI: http://opensource.org/licenses/MIT
 * Author: Jukka Paasonen
 * Author URI: http://paazmaya.com
 */

/**
 * add field to user profiles
 */


define ( 'MEMBER_REGISTER_VERSION', '0.14.0' );

global $mr_file_base_directory;
$mr_file_base_directory = realpath( ABSPATH . '../member_register_files');

global $mr_date_format;
$mr_date_format = 'Y-m-d H:i:s';

global $mr_db_version;
$mr_db_version = '12';

global $mr_grade_values;
$mr_grade_values = [
    '5K' => '5 kyu',
    '5h' => '5 kyu + ' . __( 'stripe', 'member-register' ),
    '4K' => '4 kyu',
    '4h' => '4 kyu + ' . __( 'stripe', 'member-register' ),
    '3K' => '3 kyu',
    '3h' => '3 kyu + ' . __( 'stripe', 'member-register' ),
    '2K' => '2 kyu',
    '2h' => '2 kyu + ' . __( 'stripe', 'member-register' ),
    '1K' => '1 kyu',
    '1h' => '1 kyu + ' . __( 'stripe', 'member-register' ),
    '1D' => '1 dan',
    '2D' => '2 dan',
    '3D' => '3 dan',
    '4D' => '4 dan',
    '5D' => '5 dan',
    '6D' => '6 dan',
    '7D' => '7 dan'
];

global $mr_grade_types;
$mr_grade_types = [
    'Yuishinkai' => 'Yuishinkai Karate',
    'Kobujutsu'  => 'Ryukyu Kobujutsu'
];

global $mr_martial_arts;
// Should match the enum of martial in mr_member table.
$mr_martial_arts = [
    'karate'    => 'Yuishinkai Karate',
    'kobujutsu' => 'Ryukyu Kobujutsu',
    'taiji'     => 'Taiji',
    'judo'      => 'Goshin Judo',
    'mma'       => 'Mixed Martial Arts'
];

define( 'MR_ACCESS_OWN_INFO', 1 << 0 ); // 1
define( 'MR_ACCESS_FILES_VIEW', 1 << 1 ); // 2
define( 'MR_ACCESS_CONVERSATION', 1 << 2 ); // 4
define( 'MR_ACCESS_FORUM_CREATE', 1 << 3 ); // 8
define( 'MR_ACCESS_FORUM_DELETE', 1 << 4 ); // 16
define( 'MR_ACCESS_MEMBERS_VIEW', 1 << 5 ); // 32
define( 'MR_ACCESS_MEMBERS_EDIT', 1 << 6 ); // 64
define( 'MR_ACCESS_GRADE_MANAGE', 1 << 7 ); // 128
define( 'MR_ACCESS_PAYMENT_MANAGE', 1 << 8 ); // 256
define( 'MR_ACCESS_CLUB_MANAGE', 1 << 9 ); // 512
define( 'MR_ACCESS_FILES_MANAGE', 1 << 10 ); // 1024
define( 'MR_ACCESS_GROUP_MANAGE', 1 << 11 ); // 2048

global $mr_access_type;
$mr_access_type = [
    1    => __( 'Own information view and update', 'member-register' ),
    2    => __( 'Files for members', 'member-register' ),
    4    => __( 'Participate in a discussion', 'member-register' ),
    8    => __( 'Create a discussion topic', 'member-register' ),
    16   => __( 'The debates and discussion topics in the removal', 'member-register' ),
    32   => __( 'Members listing and viewing their information', 'member-register' ),
    64   => __( 'Adding, editing and removal of members', 'member-register' ),
    128  => __( 'Grade management', 'member-register' ),
    256  => __( 'Payment management', 'member-register' ),
    512  => __( 'The clubs management', 'member-register' ),
    1024 => __( 'File management', 'member-register' ),
    2048 => __( 'Manage groups', 'member-register' )
];


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

// http://codex.wordpress.org/Function_Reference/load_plugin_textdomain
load_plugin_textdomain( 'member-register', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

register_activation_hook( __FILE__, 'mr_install' );
//register_uninstall_hook( __FILE__, 'member_register_uninstall');

// Check Member Register related access data
add_action( 'init', 'member_register_wp_loaded' );
add_action( 'init', 'member_register_file_download' );
add_action( 'init', 'member_register_public_reg_form' );

// http://codex.wordpress.org/Function_Reference/add_action
add_action( 'admin_init', 'member_register_admin_init' );
add_action( 'admin_menu', 'member_register_admin_menu' );
add_action( 'admin_menu', 'member_register_forum_menu' );
add_action( 'admin_menu', 'member_register_files_menu' );
add_action( 'admin_print_styles', 'member_register_admin_print_styles' );
add_action( 'admin_print_scripts', 'member_register_admin_print_scripts' );
add_action( 'admin_head', 'member_register_admin_head' );

// http://codex.wordpress.org/Plugin_API/Action_Reference/profile_update
add_action( 'profile_update', 'member_register_profile_update' );


// Login and logout
add_action( 'wp_login', 'member_register_login' );
add_action( 'wp_logout', 'member_register_logout' );

/**
 * Hooks for additional items in the public registration form.
 * http://codex.wordpress.org/Customizing_the_Registration_Form
 * Member Register plugin uses the 'mr_mr_prf_' prefix for these functions.
 */
function member_register_public_reg_form() {
    add_action( 'register_form', 'mr_prf_register_form' );
    add_filter( 'registration_errors', 'mr_prf_registration_errors', 10, 3 );
    add_action( 'user_register', 'mr_prf_user_register' );
}

// http://codex.wordpress.org/Function_Reference/wp_enqueue_script#Default_Scripts_Included_and_Registered_by_WordPress
// http://bassistance.de/jquery-plugins/jquery-plugin-validation/
function member_register_admin_init() {
    wp_register_script( 'jquery-bassistance-validation', plugins_url( '/js/jquery.validate.min.js', __FILE__ ),
        [ 'jquery' ] ); // 1.9.0
    wp_register_script( 'jquery-bassistance-validation-messages-fi', plugins_url( '/js/messages_fi.js', __FILE__ ),
        [ 'jquery' ] );
    wp_register_script( 'jquery-stupidtable', plugins_url( '/js/stupidtable.min.js', __FILE__ ), [ 'jquery' ] );
    wp_register_script( 'jquery-ui-datepicker-fi', plugins_url( '/js/jquery.ui.datepicker-fi.js', __FILE__ ),
        [ 'jquery' ] );
    wp_register_script( 'jquery-select2', plugins_url( '/js/select2.min.js', __FILE__ ), [ 'jquery' ] ); //
    wp_register_script( 'jquery-select2-locale-fi', plugins_url( '/js/select2_locale_fi.js', __FILE__ ),
        [ 'jquery-select2' ] ); //

    wp_register_style( 'jquery-ui-datepicker', plugins_url( '/css/jquery.ui.datepicker.css', __FILE__ ) );
    wp_register_style( 'jquery-select2', plugins_url( '/css/select2.css', __FILE__ ) );
    wp_register_style( 'jquery-select2-bootstrap', plugins_url( '/css/select2-bootstrap.css', __FILE__ ) );
    wp_register_style( 'mr-styles', plugins_url( '/css/mr-styles.css', __FILE__ ) );
}

function member_register_admin_print_scripts() {
    // http://codex.wordpress.org/Function_Reference/wp_enqueue_script
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'jquery-ui-core' );
    wp_enqueue_script( 'jquery-ui-datepicker' );

    wp_enqueue_script( 'jquery-ui-datepicker-fi' );

    wp_enqueue_script( 'jquery-bassistance-validation' );
    wp_enqueue_script( 'jquery-bassistance-validation-messages-fi' );
    wp_enqueue_script( 'jquery-stupidtable' );
    wp_enqueue_script( 'jquery-select2' );
    wp_enqueue_script( 'jquery-select2-locale-fi' );
}

function member_register_admin_print_styles() {
    // http://codex.wordpress.org/Function_Reference/wp_enqueue_style
    wp_enqueue_style( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-stupidtable' );
    wp_enqueue_style( 'jquery-select2' );
    wp_enqueue_style( 'jquery-select2-bootstrap' );
    wp_enqueue_style( 'mr-styles' );
}

function member_register_file_download() {
    // Before going any further, check if there is a request for a file download
    if ( isset( $_GET['download'] ) && $_GET['download'] != '' ) {
        // This might call exit()
        mr_file_download( $_GET['download'] );
    }
}

function member_register_admin_head() {
    // jQuery is in noConflict state while in Wordpress...
    ?>
    <script type="text/javascript">
        var MEMBER_REGISTER = {
            storageKey: 'member-register-hidden-columns',
            hiddenColumns: [1, 7, 8],
            saveHiddenColumns: function () {
                if (typeof window.localStorage === 'object') {
                    window.localStorage.setItem(this.storageKey, JSON.stringify(this.hiddenColumns));
                }
            },
            readHiddenColumns: function () {
                if (typeof window.localStorage === 'object') {
                    var possibleHidden = window.localStorage.getItem(this.storageKey);
                    if (possibleHidden !== null) {
                        this.hiddenColumns = JSON.parse(possibleHidden);
                    }
                }
            },
            hideColumns: function () {
                var $table = jQuery('table').has('th.hideable');
                var $caption = $table.find('caption > p');

                for (var i = 0; i < MEMBER_REGISTER.hiddenColumns.length; ++i) {
                    var index = MEMBER_REGISTER.hiddenColumns[i];
                    var ths = $table.find('tr th:nth-child(' + index + ')');
                    var text = ths.text();
                    var tds = $table.find('tr td:nth-child(' + index + ')');

                    var showLink = '<a href="#show" title="' + text + '" data-index="' + index +
                        '"><i class="dashicons dashicons-download"></i>' + text + '</a>';
                    $caption.append(showLink);
                    ths.hide();
                    tds.hide();
                }
            }
        };

        jQuery(document).ready(function () {
            MEMBER_REGISTER.readHiddenColumns();
            MEMBER_REGISTER.hideColumns();

            jQuery('table.sorter').stupidtable();

            // Search field
            jQuery('#tablesearch').bind('change keyup', function (event) {
                var $self = jQuery(this);
                var search = $self.val();
                var $trs = $self.parentsUntil('table').parent().find('tbody > tr');
                $trs.each(function (index, item) {
                    var $tr = jQuery(item);
                    if ($tr.text().indexOf(search) !== -1) {
                        if ($tr.is(':hidden')) {
                            $tr.show();
                        }
                    }
                    else {
                        $tr.hide();
                    }
                });
            });

            jQuery.datepicker.setDefaults({
                showWeek: true,
                changeMonth: true,
                changeYear: true,
                yearRange: '1920:2060',
                numberOfMonths: 1,
                dateFormat: 'yy-mm-dd'
            });
            jQuery('input.pickday').datepicker();
            jQuery('select.chosen').select2({
                allowClear: true
            });
            jQuery('form').validate();

            // Removal button should ask the user: are you sure?
            jQuery('a[rel="remove"]').click(function () {
                var title = jQuery(this).attr('title');
                return confirm(title);
            });

            var hideLink = '<a href="#hide" class="dashicons dashicons-upload" title="<?php echo __('Hide', 'member-register'); ?>">&nbsp;</a>';
            jQuery('th.hideable').append(hideLink);

            // Hide table columns
            jQuery('th.hideable a[href="#hide"]').on('click', function () {
                var $self = jQuery(this);
                var inx = $self.parent().index() + 1;

                var table = $self.parentsUntil('table').parent();
                var text = $self.parent().text();
                //console.log('hide(). inx: ' + inx + ', text: ' + text);

                if (MEMBER_REGISTER.hiddenColumns.indexOf(inx) === -1) {
                    MEMBER_REGISTER.hiddenColumns.push(inx);
                }
                var showLink = '<a href="#show" title="' + text + '" data-index="' + inx +
                    '"><i class="dashicons dashicons-download"></i>' + text + '</a>';
                table.find('caption > p').append(showLink);

                var ths = table.find('tr th:nth-child(' + inx + ')');
                var tds = table.find('tr td:nth-child(' + inx + ')');

                ths.hide();
                tds.hide();
                MEMBER_REGISTER.saveHiddenColumns();

                return false;
            });

            // Show the column again
            jQuery(document).on('click', 'table caption a[href="#show"]', function () {
                var $self = jQuery(this);
                var text = $self.text();
                var inx = $self.data('index');
                //console.log('show(). inx: ' + inx + ', text: ' + text);

                var table = $self.parentsUntil('table').parent();
                var ths = table.find('tr th:nth-child(' + inx + ')');
                var tds = table.find('tr td:nth-child(' + inx + ')');
                ths.show();
                tds.show();

                var columnIndex = MEMBER_REGISTER.hiddenColumns.indexOf(inx);
                if (columnIndex !== -1) {
                    MEMBER_REGISTER.hiddenColumns.splice(columnIndex, 1);
                }

                $self.remove();
                MEMBER_REGISTER.saveHiddenColumns();

                return false;
            });
        });

    </script>
<?php
}

function member_register_admin_menu() {
    // http://codex.wordpress.org/Adding_Administration_Menus
    // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position )
    add_menu_page( __( 'Member Register', 'member-register' ), __( 'Member Register', 'member-register' ), 'read',
        'member-register-control', 'mr_member_list_active', 'dashicons-groups' ); // $position );

    if ( mr_has_permission( MR_ACCESS_MEMBERS_EDIT ) ) {
        // add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function )
        add_submenu_page( 'member-register-control', __( 'Add new member', 'member-register' ),
            __( 'Add new member', 'member-register' ), 'read', 'member-register-new', 'mr_member_new' );
        add_submenu_page( 'member-register-control', __( 'List inactive members', 'member-register' ),
            __( 'List non active members', 'member-register' ), 'read', 'member-register-inactive', 'mr_member_list_inactive' );
    }

    if ( mr_has_permission( MR_ACCESS_PAYMENT_MANAGE ) ) {
        add_submenu_page( 'member-register-control', __( 'Hallinnoi jäsenmaksuja', 'member-register' ),
            __( 'Jäsenmaksut', 'member-register' ), 'read', 'member-payment-list', 'mr_payment_list' );
    }

    if ( mr_has_permission( MR_ACCESS_PAYMENT_MANAGE ) ) {
        add_submenu_page( 'member-register-control', __( 'New payment', 'member-register' ),
            __( 'New payment', 'member-register' ), 'read', 'member-payment-new', 'mr_payment_new' );
    }

    if ( mr_has_permission( MR_ACCESS_GRADE_MANAGE ) ) {
        add_submenu_page( 'member-register-control', __( 'Grades', 'member-register' ),
            __( 'Grades', 'member-register' ), 'read', 'member-grade-list', 'mr_grade_list' );
    }

    if ( mr_has_permission( MR_ACCESS_GRADE_MANAGE ) ) {
        add_submenu_page( 'member-register-control', __( 'Nominate grades', 'member-register' ),
            __( 'Nominate grades', 'member-register' ), 'read', 'member-grade-new', 'mr_grade_new' );
    }

    if ( mr_has_permission( MR_ACCESS_CLUB_MANAGE ) ) {
        add_submenu_page( 'member-register-control', __( 'Clubs', 'member-register' ),
            __( 'Clubs', 'member-register' ), 'read', 'member-club-list', 'mr_club_list' );
    }

    if ( mr_has_permission( MR_ACCESS_GROUP_MANAGE ) ) {
        add_submenu_page( 'member-register-control', __( 'Groups', 'member-register' ),
            __( 'Groups', 'member-register' ), 'read', 'member-group-list', 'mr_group_list' );
    }

}


/**
 * Any member with login access can use the forum
 * http://codex.wordpress.org/Roles_and_Capabilities#Subscriber
 */
function member_register_forum_menu() {
    if ( current_user_can( 'read' ) && mr_has_permission( MR_ACCESS_CONVERSATION ) ) {
        // http://codex.wordpress.org/Adding_Administration_Menus
        add_menu_page( __( 'Discussions', 'member-register' ), __( 'Discussions', 'member-register' ), 'read', 'member-forum',
            'mr_forum_list', 'dashicons-format-chat' ); // $position );
    }
}

function member_register_files_menu() {
    if ( current_user_can( 'read' ) && mr_has_permission( MR_ACCESS_FILES_VIEW ) ) {
        // http://codex.wordpress.org/Adding_Administration_Menus
        add_menu_page( __( 'Files', 'member-register' ), __( 'Files', 'member-register' ), 'read', 'member-files',
            'mr_files_list', 'dashicons-portfolio' ); // $position );

        if ( mr_has_permission( MR_ACCESS_FILES_MANAGE ) ) {
            add_submenu_page( 'member-files', __( 'Add new file', 'member-register' ),
                __( 'Add new file', 'member-register' ), 'read', 'member-files-new', 'mr_files_new' );
        }
    }
}


// http://codex.wordpress.org/Plugin_API/Action_Reference/profile_update
function member_register_profile_update( $user_id, $old_user_data = null ) {
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
function member_register_login() {
    global $wpdb;
    global $userdata;

    /*
    echo '<pre>';
    print_r($userdata);
    echo '</pre>';
    */
}

function member_register_logout() {
    global $userdata;
}


/**
 * As a hack, this function also updates the last login time.
 */
function member_register_wp_loaded() {
    global $wpdb;
    global $userdata;

    // http://codex.wordpress.org/User:CharlesClarkson/Global_Variables
    if ( isset( $userdata->user_login ) && $userdata->user_login != '' &&
         ( ! isset( $userdata->mr_access ) || ! is_numeric( $userdata->mr_access ) ||
           ! isset( $userdata->mr_memberid ) || ! is_numeric( $userdata->mr_memberid ) )
    ) {
        $sql = 'SELECT id, access FROM ' . $wpdb->prefix . 'mr_member
            WHERE visible = 1 AND user_login = \'' .
               mr_htmlent( $userdata->user_login ) . '\' AND active = 1 LIMIT 1';
        $res = $wpdb->get_row( $sql, ARRAY_A );
        if ( $wpdb->num_rows == 1 ) {
            $userdata->mr_access   = intval( $res['access'] );
            $userdata->mr_memberid = intval( $res['id'] );

            $wpdb->update(
                $wpdb->prefix . 'mr_member',
                [
                    'lastlogin' => time()
                ],
                [
                    'user_login' => $userdata->user_login,
                    'active'     => 1
                ],
                [
                    '%d'
                ],
                [
                    '%s',
                    '%d'
                ]
            );
        }
    }
}

/*

function member_register_uninstall()
{
}
*/


function mr_member_list_page( $showActiveMembers ) {
    if ( ! current_user_can( 'read' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    global $userdata;

    echo '<div class="wrap">';

    // Check for requested member
    $memberid = isset( $_GET['memberid'] ) && is_numeric( $_GET['memberid'] ) ? intval( $_GET['memberid'] ) : '';

    // But if the current user has no rights, show only their own info, if rights for that exist.
    if ( ! mr_has_permission( MR_ACCESS_MEMBERS_VIEW ) ) {
        $memberid = $userdata->mr_memberid;
    }

    if ( $memberid != '' ) {
        mr_show_member_info( $memberid );
    } else {
        echo '<h2>' . __( 'Member Register', 'member-register' ) . '</h2>';
        if ( isset( $_GET['removeid'] ) && is_numeric( $_GET['removeid'] ) ) {
            // Remove this member (hide with visible = 0)
            mr_remove_member( intval( $_GET['removeid'] ) );
        } else {
            echo '<p>' . __( 'A list of registered active members', 'member-register' ) . '</p>';
            mr_show_members( [ 'active' => $showActiveMembers ] );
        }
    }
    echo '</div>';
}

function mr_member_list_active() {
    mr_member_list_page( true );
}

function mr_member_list_inactive() {
    mr_member_list_page( false );
}

