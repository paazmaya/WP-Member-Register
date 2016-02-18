<?php
/**
 * Part of Member Register
 * License: MIT (http://opensource.org/licenses/MIT)
 *
 * Single member related functions
 */


/**
 *
 * @param array $filters
 * @return string
 */
function mr_filter_members( $filters = null ) {
    global $wpdb;

    // Should not use group
    /*
    $list = array_filter($filters, function ($key) {
        return $key !== 'group';
    }, ARRAY_FILTER_USE_KEY);
    // PHP 5.6+: ARRAY_FILTER_USE_KEY
    */
    $list = $filters;
    if (array_key_exists('group', $list)) {
        unset($list['group']);
    }
    $where = mr_filter_list($list, ' WHERE A.visible = 1');

    if ( is_array( $filters ) && isset( $filters['group'] ) && is_numeric( $filters['group'] ) ) {
        $where .= ' AND A.id IN (SELECT GM.member_id
                FROM ' . $wpdb->prefix . 'mr_group_member GM
                WHERE GM.group_id = ' . intval( $filters['group'] ) . ')';
    }

    return $where;
}


/**
 * Show a table of members based on the given filter if any.
 * @param array $filters
 */
function mr_show_members( $filters = null ) {
    global $wpdb;
    global $mr_access_type;
    global $mr_martial_arts;
    global $mr_date_format;

    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_MEMBERS_VIEW ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    // Possible filter options: club, active, group
    $where = mr_filter_members( $filters );

    // id access firstname lastname birthdate address zipcode postal phone email nationality
    // joindate passnro notes lastlogin active club visible

    $sql = 'SELECT A.*, B.name AS nationalityname, C.id AS wpuserid
	    FROM ' . $wpdb->prefix . 'mr_member A
		LEFT JOIN ' . $wpdb->prefix . 'mr_country B ON A.nationality = B.code
		LEFT JOIN ' . $wpdb->users . ' C ON A.user_login = C.user_login ' . $where . '
		ORDER BY A.lastname ASC';

    $members = $wpdb->get_results( $sql, ARRAY_A );

    ?>
    <table class="wp-list-table mr-table widefat sorter">
        <caption>
            <label><input type="text" id="tablesearch"/></label>
            <p></p>
        </caption>
        <thead>
        <tr>
            <th data-sort="int" class="hideable"><?php echo __( 'Member ID', 'member-register' ); ?></th>
            <th data-sort="string" class="sorting-asc"><?php echo __( 'Last name', 'member-register' ); ?></th>
            <th data-sort="string"><?php echo __( 'First name', 'member-register' ); ?></th>
            <th data-sort="int"><?php echo __( 'Birthday', 'member-register' ); ?></th>
            <th data-sort="string"><?php echo __( 'E-mail', 'member-register' ); ?></th>
            <th data-sort="int"><?php echo __( 'Phone number', 'member-register' ); ?></th>
            <th data-sort="string" class="hideable"><?php echo __( 'Main martial art', 'member-register' ); ?></th>
            <th data-sort="int" class="hideable"><?php echo __( 'Access rights', 'member-register' ); ?></th>
            <th data-sort="int" class="hideable"><?php echo __( 'Last login', 'member-register' ); ?></th>
            <th data-sort="string" class="hideable"><?php echo __( 'WP username', 'member-register' ); ?></th>
        </tr>
        </thead>
        <tbody>

        <?php
        foreach ( $members as $member ) {
            $url = '<a href="' . admin_url( 'admin.php?page=member-register-control' ) .
                   '&memberid=' . $member['id'] . '" title="' . $member['firstname'] .
                   ' ' . $member['lastname'] . '">';

            echo '<tr id="user_' . $member['id'] . '"' . ( intval( $member['active'] ) === 0 ? ' class="inactive"' : '' ) . '>';
            echo '<td>' . $member['id'] . '</td>';
            echo '<td data-sort-value="' . $member['lastname'] . '"';

            echo '>' . $url . $member['lastname'] . '</a></td>';
            echo '<td data-sort-value="' . $member['firstname'] . '">' . $url . $member['firstname'] . '</a></td>';
            echo '<td data-sort-value="' . str_replace( '-', '', $member['birthdate'] ) . '">';
            if ( $member['birthdate'] != '0000-00-00' ) {
                echo $member['birthdate'];
            }
            echo '</td>';
            echo '<td>' . $member['email'] . '</td>';
            echo '<td>' . $member['phone'] . '</td>';
            echo '<td title="' . ( isset( $mr_martial_arts[ $member['martial'] ] ) ? $mr_martial_arts[ $member['martial'] ] : '' ) . '">' . $member['martial'] . '</td>';
            echo '<td data-sort-value="' . $member['access'] . '" title="' . $member['access'] . '">';
            list_user_rights( $member['access'] );
            echo '</td>';
            echo '<td data-sort-value="' . $member['lastlogin'] . '">';
            if ( $member['lastlogin'] > 0 ) {
                echo date( $mr_date_format, $member['lastlogin'] );
            }
            echo '</td>';
            echo '<td data-sort-value="' . $member['user_login'] . '">';
            if ( $member['user_login'] != '' && $member['user_login'] != null && is_numeric( $member['wpuserid'] ) ) {
                echo '<a href="' . admin_url( 'user-edit.php?user_id=' ) . $member['wpuserid'] .
                     '" title="' . __( 'Modify WordPress user', 'member-register' ) . '">' . $member['user_login'] . '</a>';
            }
            echo '</td>';

            echo '</tr>';
        }
        ?>
        </tbody>
    </table>
<?php
}

/**
 * Show all possible information of the given user.
 * @param integer $id
 */
function mr_show_member_info( $id ) {
    if ( ! current_user_can( 'read' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    global $wpdb;
    global $userdata;
    global $mr_date_format;
    global $mr_access_type;
    global $mr_grade_values;
    global $mr_grade_types;
    global $mr_martial_arts;

    $id          = intval( $id );
    $usercanedit = false;

    if ( ! mr_has_permission( MR_ACCESS_MEMBERS_VIEW ) ) {
        // id must be of the current user
        $id = $userdata->mr_memberid;
    }

    if ( mr_has_permission( MR_ACCESS_MEMBERS_EDIT ) || $id == $userdata->mr_memberid ) {
        $usercanedit = true;
    }

    // Check for possible insert
    if ( isset( $_POST['mr_submit_hidden_member'] ) && $_POST['mr_submit_hidden_member'] == 'Y' && $usercanedit ) {
        if ( mr_update_member_info( $_POST ) ) {
            echo '<div class="updated"><p>';
            echo '<strong>' . __( 'Member information updated', 'member-register' ) . '</strong>';
            echo '</p></div>';
        } else {
            echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
        }
    }

    // ---------------

    $items  = [
        'id',
        'user_login',
        'access',
        'firstname',
        'lastname',
        'birthdate',
        'address',
        'zipcode',
        'postal',
        'phone',
        'email',
        'nationality',
        'joindate',
        'passnro',
        'martial',
        'notes',
        'lastlogin',
        'active',
        'club'
    ];
    $sql    = 'SELECT A.*, B.name AS nationalitycountry, C.title AS clubname, D.id AS wpuserid, ' .
              '(SELECT COUNT(*) FROM ' . $wpdb->prefix . 'mr_grade WHERE member = ' . $id . ' AND visible = 1) AS gradecount, ' .
              '(SELECT COUNT(*) FROM ' . $wpdb->prefix . 'mr_payment WHERE member = ' . $id . ' AND visible = 1) AS paymentcount FROM ' .
              $wpdb->prefix . 'mr_member A LEFT JOIN ' .
              $wpdb->prefix . 'mr_country B ON A.nationality = B.code LEFT JOIN ' .
              $wpdb->prefix . 'mr_club C ON A.club = C.id LEFT JOIN ' .
              $wpdb->users . ' D ON A.user_login = D.user_login WHERE A.id = ' . $id . ' AND A.visible = 1 LIMIT 1';
    $person = $wpdb->get_row( $sql, ARRAY_A );

    echo '<h1>' . $person['firstname'] . ' ' . $person['lastname'] . '</h1>';

    if ( mr_has_permission( MR_ACCESS_MEMBERS_EDIT ) ) {
        echo '<p>' . __( 'In case you wish to remove a user, first all grades and payments should be removed.', 'member-register' ) .
             ' (' . $person['gradecount'] . ', ' . $person['paymentcount'] . ')</p>';

        if ( intval( $person['gradecount'] ) == 0 && intval( $person['paymentcount'] ) == 0 ) {
            echo '<p><a rel="remove" class="button" href="' . admin_url( 'admin.php?page=member-register-control' ) . '&removeid=' . $id .
                 '" title="' . __( 'Remove this user', 'member-register' ) . ' ' . $person['firstname'] . ' ' . $person['lastname'] . '">' .
                 __( 'This user can be removed by clicking here', 'member-register' ) . '</a>';
        }
    }

    if ( isset( $_GET['edit'] ) && $usercanedit ) {
        mr_new_member_form( admin_url( 'admin.php?page=member-register-control' ) . '&memberid=' . $id, $person );
    } else {
        ?>
        <h3><?php echo __( 'Personal information', 'member-register' ); ?></h3>
        <table class="wp-list-table mr-table widefat fixed pages users">
            <tbody>
            <tr>
                <th><?php echo __( 'Last name', 'member-register' ); ?></th>
                <td><?php echo $person['lastname']; ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'First name', 'member-register' ); ?></th>
                <td><?php echo $person['firstname']; ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'Login Access', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'list of actions the member can make on this web site', 'member-register' ); ?>
                        )</span></th>
                <td><?php
                    list_user_rights( $person['access'] );
                    ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'Birthday', 'member-register' ); ?></th>
                <td><?php echo $person['birthdate']; ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'Address', 'member-register' ); ?></th>
                <td><?php echo $person['address']; ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'Postal Code', 'member-register' ); ?></th>
                <td><?php echo $person['zipcode']; ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'Post Office', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'and the country if not Finland', 'member-register' ); ?>
                        )</span></th>
                <td><?php echo $person['postal']; ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'Phone number', 'member-register' ); ?></th>
                <td><?php echo $person['phone']; ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'E-mail', 'member-register' ); ?></th>
                <td><a href="mailto:<?php echo $person['email']; ?>"
                       title="<?php echo __( 'send email', 'member-register' ); ?>"><?php echo $person['email']; ?></a>
                </td>
            </tr>
            <tr>
                <th><?php echo __( 'Nationality', 'member-register' ); ?></th>
                <td><?php echo $person['nationalitycountry']; ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'Date of joining', 'member-register' ); ?></th>
                <td><?php echo $person['joindate']; ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'Association passport number', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'blue cover passport', 'member-register' ); ?>)</span></th>
                <td><?php echo $person['passnro']; ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'Main martial art', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'registered this as the main martial art', 'member-register' ); ?>
                        )</span></th>
                <td><?php
                    if ( isset( $person['martial'] ) && $person['martial'] != '' && $mr_martial_arts[ $person['martial'] ] ) {
                        echo $mr_martial_arts[ $person['martial'] ] . ' (' . $person['martial'] . ')';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th><?php echo __( 'Additional information', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'freely written', 'member-register' ); ?>)</span></th>
                <td><?php echo $person['notes']; ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'Last visited pages', 'member-register' ); ?></th>
                <td><?php echo( $person['lastlogin'] != 0 ? date( $mr_date_format, $person['lastlogin'] ) : '' ); ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'Active', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'whether to be able to log on this the website', 'member-register' ); ?>
                        )</span></th>
                <td><?php echo $person['active']; ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'Club' ); ?> <span
                        class="description">(<?php echo __( 'main training place', 'member-register' ); ?>)</span></th>
                <td><?php
                    if ( $person['clubname'] != '' && mr_has_permission( MR_ACCESS_CLUB_MANAGE ) ) {
                        echo '<a href="' . admin_url( 'admin.php?page=member-club-list' ) . '&club=' .
                             $person['club'] . '" title="' . __( 'List of active members in the club called:', 'member-register' ) .
                             ' ' . $person['clubname'] . '">' . $person['clubname'] . '</a>';
                    } else {
                        echo $person['clubname'];
                    }
                    ?></td>
            </tr>
            <tr>
                <th><?php echo __( 'WordPress username', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'in case the member has linked to such', 'member-register' ); ?>
                        )</span></th>
                <td><?php
                    if ( $person['user_login'] != '' && $person['user_login'] != null && is_numeric( $person['wpuserid'] ) && $usercanedit ) {
                        echo '<a href="' . admin_url( 'user-edit.php?user_id=' ) . $person['wpuserid'] .
                             '" title="' . __( 'Modify this WordPress user', 'member-register' ) . '">' . $person['user_login'] . '</a>';
                    } else {
                        echo $person['user_login'];
                    }
                    ?></td>
            </tr>
            </tbody>
        </table>
        <?php
        if ( $usercanedit ) {
            echo '<p><a href="' . admin_url( 'admin.php?page=member-register-control' ) . '&memberid='
                 . $id . '&edit" title="' . __( 'Modify this member', 'member-register' ) . '" class="button-primary">' . __( 'Modify this member', 'member-register' ) . '</a></p>';
        }
    }

    // ---------------
    echo '<hr/>';
    echo '<h2>' . __( 'Grades', 'member-register' ) . '</h2>';
    mr_show_grades( $id );

    ?>

    <hr/>
    <h2><?php echo __( 'Payments', 'member-register' ); ?></h2>
    <?php

    // ---------------

    mr_show_payments_lists( $id );

}


/**
 * Remove the given member by setting the visible flag to 0
 * @param integer $id
 */
function mr_remove_member( $id ) {
    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_MEMBERS_EDIT ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    $id = intval( $id );

    global $wpdb;

    $info = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . 'mr_member WHERE id = ' . $id . ' LIMIT 1', ARRAY_A );

    $removal = $wpdb->update(
        $wpdb->prefix . 'mr_member',
        [
            'visible' => 0
        ],
        [
            'id' => $id
        ],
        [
            '%d'
        ],
        [
            '%d'
        ]
    );

    // TODO: if 'user_login' is not empty, that WP user should be disabled.

    if ( $removal ) {
        echo '<div class="updated"><p>';
        echo '<strong>' . __( 'Member removed', 'member-register' ) . ' ' . $info['firstname'] .
             ' ' . $info['lastname'] . '  (' . $id . ')</strong>';
        echo '</p></div>';
    } else {
        echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
    }
}

/**
 *
 */
function mr_member_new() {
    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_MEMBERS_EDIT ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    global $wpdb;

    // Check for possible insert
    $hidden_field_name = 'mr_submit_hidden_member';
    if ( isset( $_POST[ $hidden_field_name ] ) && $_POST[ $hidden_field_name ] == 'Y' ) {
        if ( mr_insert_new_member( $_POST ) ) {
            echo '<div class="updated"><p>';
            echo '<strong>' . __( 'New member added.', 'member-register' ) . ' ' . $_POST['firstname'] . ' ' . $_POST['lastname'] . '</strong>';
            echo '</p></div>';
        } else {
            echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
        }
    }

    ?>
    <div class="wrap">
        <h2><?php echo __( 'Add new member', 'member-register' ); ?></h2>
        <?php
        mr_new_member_form( admin_url( 'admin.php?page=member-register-new' ), [] );
        ?>
    </div>

<?php
}


function mr_insert_new_member( $postdata ) {
    global $wpdb;

    $values   = [];
    $required = [
        'user_login',
        'access',
        'firstname',
        'lastname',
        'birthdate',
        'address',
        'zipcode',
        'postal',
        'phone',
        'email',
        'nationality',
        'joindate',
        'passnro',
        'martial',
        'notes',
        'active',
        'club'
    ];

    foreach ( $postdata as $k => $v ) {
        if ( in_array( $k, $required ) ) {
            // sanitize
            $key = mr_urize( $k );
            if ( $key == 'access' ) {
                $rights = 0;
                if ( is_array( $v ) ) {
                    foreach ( $v as $level ) {
                        $rights += intval( $level );
                    }
                }
                $values[ $key ] = $rights;
            } else {
                $values[ $key ] = mr_htmlent( $v );
            }
        }
    }

    return $wpdb->insert(
        $wpdb->prefix . 'mr_member',
        $values
    );
}

function mr_update_member_info( $postdata ) {
    global $wpdb;
    global $userdata;

    $values   = [];
    $required = [
        'user_login',
        'access',
        'firstname',
        'lastname',
        'birthdate',
        'address',
        'zipcode',
        'postal',
        'phone',
        'email',
        'nationality',
        'joindate',
        'passnro',
        'martial',
        'notes',
        'active',
        'club'
    ];


    if ( ! mr_has_permission( MR_ACCESS_MEMBERS_EDIT ) ) {
        // id must be of the current user
        $postdata['id'] = $userdata->mr_memberid;
    }

    if ( isset( $postdata['id'] ) && is_numeric( $postdata['id'] ) ) {
        $memberId = intval( $postdata['id'] );

        foreach ( $postdata as $k => $v ) {
            if ( in_array( $k, $required ) ) {
                // sanitize
                $key = mr_urize( $k );

                if ( $k == 'access' ) {
                    $rights = 0;
                    if ( is_array( $v ) ) {
                        foreach ( $v as $level ) {
                            $rights += intval( $level );
                        }
                    }
                    $values[ $key ] = $rights;
                } else {
                    $values[ $key ] = mr_htmlent( $v );
                }
            }
        }

        return $wpdb->update(
            $wpdb->prefix . 'mr_member',
            $values,
            [
                'id' => $memberId
            ],
            [
                '%s'
            ],
            [
                '%d'
            ]
        );
    } else {
        return false;
    }
}


/**
 * Print out a form for adding new members.
 *
 * @param string $action Target page of the form
 * @param array $data Array of data
 */
function mr_new_member_form( $action, $data ) {
    if ( ! current_user_can( 'read' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    global $wpdb;
    global $userdata;
    global $mr_access_type;
    global $mr_martial_arts;


    if ( ! mr_has_permission( MR_ACCESS_MEMBERS_EDIT ) ) {
        // id must be of the current user
        $data['id'] = $userdata->mr_memberid;
    }

    // Default values for an empty form
    $values = [
        'id'          => 0,
        'user_login'  => '',
        'access'      => 1,
        'firstname'   => '',
        'lastname'    => '',
        'birthdate'   => '',
        'address'     => '',
        'zipcode'     => '',
        'postal'      => '',
        'phone'       => '',
        'email'       => '',
        'nationality' => 'FI',
        'joindate'    => date( 'Y-m-d' ),
        'passnro'     => '',
        'martial'     => '',
        'notes'       => '',
        'active'      => 1,
        'club'        => - 1
    ];
    $values = array_merge( $values, $data );

    ?>
    <form name="form1" method="post" action="<?php echo $action; ?>" autocomplete="on">
    <input type="hidden" name="mr_submit_hidden_member" value="Y"/>
    <input type="hidden" name="id" value="<?php echo $values['id']; ?>"/>
    <table class="form-table mr-table" id="mrform">
        <tr class="form-field">
            <th><?php echo __( 'WP username', 'member-register' ); ?> <span
                    class="description">(<?php echo __( 'If there is already a', 'member-register' ); ?>)</span></th>
            <td>
                <?php
                if ( mr_has_permission( MR_ACCESS_MEMBERS_EDIT ) ) {
                    ?>
                    <select name="user_login" class="chosen" data-placeholder="Valitse jo olemassa oleva WP käyttäjä">
                        <option value=""></option>
                        <?php
                        // If editing, select all free and the current. If new, select all free
                        $sql = 'SELECT A.user_login, A.display_name FROM ' . $wpdb->users . ' A ' .
                               'WHERE A.user_login = \'' . $values['user_login'] . '\' LIMIT 1' .
                               ' UNION ' .
                               'SELECT A.user_login, A.display_name FROM ' . $wpdb->users . ' A ' .
                               'WHERE A.user_login NOT IN (SELECT B.user_login FROM ' . $wpdb->prefix .
                               'mr_member B WHERE B.user_login IS NOT NULL AND B.visible = 1) ORDER BY 2 ASC';
                        echo '\n<!--\n' . $sql . '\n-->\n';

                        $users = $wpdb->get_results( $sql, ARRAY_A );
                        foreach ( $users as $user ) {
                            echo '<option value="' . $user['user_login'] . '"';
                            if ( $values['user_login'] == $user['user_login'] ) {
                                echo ' selected';
                            }
                            echo '>' . $user['display_name'] . ' (' . $user['user_login'] . ')</option>';
                        }
                        ?>
                    </select>
                <?php
                } else {
                    echo $userdata->user_login;
                }
                ?>
            </td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'Access level', 'member-register' ); ?></th>
            <td>
                <?php
                if ( mr_has_permission( MR_ACCESS_MEMBERS_EDIT ) ) {
                    ?>
                    <select class="chosen" name="access[]" multiple
                            data-placeholder="<?php echo __( 'Choose all actions', 'member-register' ); ?>">
                        <?php
                        foreach ( $mr_access_type as $k => $v ) {
                            echo '<option value="' . $k . '"';
                            if ( mr_has_permission( $k, $values['access'] ) ) {
                                echo ' selected';
                            }
                            echo '>' . $v . ' (' . $k . ')</option>';
                        }
                        ?>
                    </select>
                <?php
                } else {
                    list_user_rights( $values['access'] );
                }
                ?>
            </td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'First Name', 'member-register' ); ?></th>
            <td><input type="text" name="firstname" required value="<?php echo $values['firstname']; ?>"/></td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'Last name', 'member-register' ); ?></th>
            <td><input type="text" name="lastname" required value="<?php echo $values['lastname']; ?>"/></td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'Birthday', 'member-register' ); ?> <span class="description">(YYYY-MM-DD)</span></th>
            <td><input type="text" name="birthdate" class="pickday" value="<?php echo $values['birthdate']; ?>"/></td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'Postal Address', 'member-register' ); ?></th>
            <td><input type="text" name="address" value="<?php echo $values['address']; ?>"/></td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'Postal Code', 'member-register' ); ?></th>
            <td><input type="text" name="zipcode" value="<?php echo $values['zipcode']; ?>" list="zipcodes"/></td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'Post Office', 'member-register' ); ?></th>
            <td><input type="text" name="postal" value="<?php echo $values['postal']; ?>" list="postals"/></td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'The Telephone Number For The', 'member-register' ); ?></th>
            <td><input type="text" name="phone" value="<?php echo $values['phone']; ?>"/></td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'E-mail', 'member-register' ); ?></th>
            <td><input type="email" name="email" value="<?php echo $values['email']; ?>"/></td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'Nationality', 'member-register' ); ?></th>
            <td><select class="chosen" name="nationality"
                        data-placeholder="<?php echo __( 'Choose the nationality', 'member-register' ); ?>">
                    <option value=""></option>
                    <?php
                    $sql       = 'SELECT code, name FROM ' . $wpdb->prefix . 'mr_country ORDER BY name ASC';
                    $countries = $wpdb->get_results( $sql, ARRAY_A );
                    foreach ( $countries as $cnt ) {
                        echo '<option value="' . $cnt['code'] . '"';
                        if ( $cnt['code'] == $values['nationality'] ) {
                            echo ' selected';
                        }
                        echo '>' . $cnt['name'] . '</option>';
                    }
                    ?>
                </select></td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'Date of joining', 'member-register' ); ?> <span class="description">(YYYY-MM-DD)</span>
            </th>
            <td><input type="text" name="joindate" class="pickday" value="<?php echo $values['joindate']; ?>"/></td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'Association passport number', 'member-register' ); ?></th>
            <td><input type="text" name="passnro" value="<?php echo $values['passnro']; ?>"/></td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'Main martial art', 'member-register' ); ?></th>
            <td><select class="chosen" name="martial"
                        data-placeholder="<?php echo __( 'Choose the main martial art', 'member-register' ); ?>">
                    <option value=""></option>
                    <?php
                    foreach ( $mr_martial_arts as $k => $v ) {
                        echo '<option value="' . $k . '"';
                        if ( $values['martial'] == $k ) {
                            echo ' selected';
                        }
                        echo '>' . $v . ' (' . $k . ')</option>';
                    }
                    ?>
                </select></td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'Additional information', 'member-register' ); ?></th>
            <td><input type="text" name="notes" value="<?php echo $values['notes']; ?>"/></td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'Active', 'member-register' ); ?> <span
                    class="description">(<?php echo __( 'can login if enabled', 'member-register' ); ?>)</span></th>
            <td>
                <?php
                if ( mr_has_permission( MR_ACCESS_MEMBERS_EDIT ) ) {
                    ?>
                    <label><input type="radio" name="active" value="1" <?php if ( $values['active'] == 1 ) {
                            echo 'checked';
                        } ?>/> <?php echo __( 'yes', 'member-register' ); ?></label><br/>
                    <label><input type="radio" name="active" value="0" <?php if ( $values['active'] == 0 ) {
                            echo 'checked';
                        } ?>/> <?php echo __( 'no', 'member-register' ); ?></label>
                <?php
                } else {
                    echo ( $values['active'] == 1 ) ? __( 'yes', 'member-register' ) : __( 'no', 'member-register' );
                }
                ?>
            </td>
        </tr>
        <tr class="form-field">
            <th><?php echo __( 'Club', 'member-register' ); ?> <span
                    class="description">(<?php echo __( 'training place', 'member-register' ); ?>)</span></th>
            <td><select class="chosen" name="club"
                        data-placeholder="<?php echo __( 'Select a Club', 'member-register' ); ?>">
                    <option value=""></option>
                    <?php
                    $clubs = mr_get_list( 'club', 'visible = 1', '', 'title ASC' );
                    foreach ( $clubs as $club ) {
                        echo '<option value="' . $club['id'] . '"';
                        if ( $values['club'] == $club['id'] ) {
                            echo ' selected';
                        }
                        echo '>' . $club['title'] . '</option>';
                    }
                    ?>
                </select></td>
        </tr>
    </table>
    <datalist id="postals">
        <?php
        $sql     = 'SELECT DISTINCT postal FROM ' . $wpdb->prefix . 'mr_member WHERE visible = 1 AND active = 1 ORDER BY postal ASC';
        $results = $wpdb->get_results( $sql, ARRAY_A );
        foreach ( $results as $res ) {
            echo '<option value="' . $res['postal'] . '"/>';
        }
        ?>
    </datalist>
    <datalist id="zipcodes">
        <?php
        $sql     = 'SELECT DISTINCT zipcode FROM ' . $wpdb->prefix . 'mr_member WHERE visible = 1 AND active = 1 ORDER BY zipcode ASC';
        $results = $wpdb->get_results( $sql, ARRAY_A );
        foreach ( $results as $res ) {
            echo '<option value="' . $res['zipcode'] . '"/>';
        }
        ?>
    </datalist>

    <p class="submit">
        <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ) ?>"/>
    </p>

    </form>
<?php
}
