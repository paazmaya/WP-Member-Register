<?php
/**
 * Part of Member Register
 * License: MIT (http://opensource.org/licenses/MIT)
 *
 * Group related functions
 */


function mr_group_list() {
    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_GROUP_MANAGE ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    global $wpdb;

    echo '<div class="wrap">';

    if ( isset( $_GET['remove-group'] ) && is_numeric( $_GET['remove-group'] ) ) {
        // Mark the given group visible=0, so it can be recovered just in case...
        $id     = intval( $_GET['remove-group'] );
        $update = $wpdb->update(
            $wpdb->prefix . 'mr_group',
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

        if ( $update !== false ) {
            echo '<div class="updated"><p>';
            echo '<strong>' . __( 'Group removed', 'member-register' ) . ' (' . $id . ')</strong>';
            echo '</p></div>';
        } else {
            echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
        }
    }


    if ( isset( $_GET['group-member'] ) && is_numeric( $_GET['group-member'] ) ) {
        $id  = intval( $_GET['group-member'] );
        $sql = 'SELECT * FROM ' . $wpdb->prefix . 'mr_group WHERE id = ' . $id . ' AND visible = 1 LIMIT 1';
        $res = $wpdb->get_row( $sql, ARRAY_A );

        if ( isset( $_GET['edit'] ) ) {
            echo '<h1>' . __( 'Edit this group', 'member-register' ) . ' ' . $res['title'] . '</h1>';
            $sql     = 'SELECT member_id FROM ' . $wpdb->prefix . 'mr_group_member WHERE group_id = ' . $id . '';
            $results = $wpdb->get_results( $sql, ARRAY_A );
            $members = [];
            foreach ( $results as $r ) {
                $members[] = $r['member_id'];
            }
            mr_new_group_form( $members, $res['title'], $id );
        } else {

            // Check for possible update
            $hidden_field_name = 'mr_submit_hidden_group';
            if ( isset( $_POST[ $hidden_field_name ] ) && $_POST[ $hidden_field_name ] == 'Y' ) {
                if ( mr_group_update( $id, $_POST ) ) {
                    echo '<div class="updated"><p>';
                    echo '<strong>' . __( 'Group updated' ) . '</strong>';
                    echo '</p></div>';
                } else {
                    echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
                }
            }

            echo '<h1>' . $res['title'] . '</h1>';


            echo '<p><a href="' . admin_url( 'admin.php?page=member-group-list' ) . '&amp;group-member=' .
                 $id . '&amp;edit" title="' . __( 'Modify this group', 'member-register' ) . '" class="button-primary">' .
                 __( 'Modify this group', 'member-register' ) . '</a></p>';

            echo '<h2>' . __( 'Members in this group.', 'member-register' ) . '</h2>';
            mr_show_members( [
                'group'  => $id,
                'active' => true
            ] );
        }
    } else {
        echo '<h2>' . __( 'Groups', 'member-register' ) . '</h2>';

        // Check for possible insert
        $hidden_field_name = 'mr_submit_hidden_group';
        if ( isset( $_POST[ $hidden_field_name ] ) && $_POST[ $hidden_field_name ] == 'Y' ) {
            if ( mr_insert_new_group( $_POST ) ) {
                echo '<div class="updated"><p>';
                echo '<strong>' . __( 'New group added', 'member-register' ) . '</strong>';
                echo '</p></div>';
            } else {
                echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
            }
        }

        if ( isset( $_GET['create-group'] ) ) {
            mr_group_new();
        } else {
            echo '<p><a href="' . admin_url( 'admin.php?page=member-group-list' ) . '&amp;create-group"' .
                 ' title="' . __( 'Create new group', 'member-register' ) . '" class="button-primary">' .
                 __( 'Create new group', 'member-register' ) . '</a></p>';

            mr_show_groups( null ); // no specific member
        }
    }

    echo '</div>';
}


/**
 * Show list of groups for a specific member (int), or for all (null).
 * @param null $memberid
 */
function mr_show_groups( $memberid = null ) {
    global $wpdb;
    global $userdata;
    global $mr_date_format;

    $allowremove = true; // visible = 0

    // If no rights, only own info
    if ( ! mr_has_permission( MR_ACCESS_GROUP_MANAGE ) ) {
        $memberid    = $userdata->mr_memberid;
        $allowremove = false;
    }

    $where = '';
    if ( $memberid != null && is_numeric( $memberid ) ) {
        $where .= 'AND A.id IN (SELECT D.group FROM ' . $wpdb->prefix .
                  'mr_group_member D WHERE D.member_id = \'' . $memberid . '\') ';
    }

    $sql = 'SELECT A.*, B.firstname, B.lastname, (SELECT COUNT(C.member_id) FROM ' . $wpdb->prefix .
           'mr_group_member C WHERE C.group_id = A.id AND C.active = 1) AS total FROM ' . $wpdb->prefix .
           'mr_group A LEFT JOIN ' . $wpdb->prefix .
           'mr_member B ON A.creator = B.id WHERE A.visible = 1 ' .
           $where . 'ORDER BY A.title DESC';
    $res = $wpdb->get_results( $sql, ARRAY_A );


    if ( count( $res ) > 0 ) {
        // id member reference type amount deadline paidday validuntil visible
        ?>
        <table class="wp-list-table mr-table widefat sorter">
            <caption>
                <label><input type="text" id="tablesearch"/></label>
                <p></p>
            </caption>
            <thead>
            <tr>
                <th data-sort="string" class="sorting-asc"><?php echo __( 'Title', 'member-register' ); ?></th>
                <th data-sort="string"><?php echo __( 'Created by', 'member-register' ); ?></th>
                <th data-sort="int"><?php echo __( 'Last modification', 'member-register' ); ?></th>
                <th data-sort="int"><?php echo __( 'Member count', 'member-register' ); ?></th>

                <?php
                if ( $allowremove ) {
                    echo '<th class="w8em">' . __( 'Delete', 'member-register' ) . '</th>';
                }
                ?>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ( $res as $group ) {
                echo '<tr id="group_' . $group['id'] . '">';
                echo '<td data-sort-value="' . $group['title'] . '"><a href="' . admin_url( 'admin.php?page=member-group-list' ) . '&amp;group-member=' .
                     $group['id'] . '" title="' . __( 'Show members', 'member-register' ) . '">' . $group['title'] . '</a></td>';
                echo '<td data-sort-value="' . $group['firstname'] . ' ' . $group['lastname'] . '">';
                if ( mr_has_permission( MR_ACCESS_GROUP_MANAGE ) ) {
                    $url = '<a href="' . admin_url( 'admin.php?page=member-register-control' ) .
                           '&memberid=' . $group['creator'] . '" title="' . $group['firstname'] .
                           ' ' . $group['lastname'] . '">';
                    echo $url . $group['firstname'] . ' ' . $group['lastname'] . '</a>';
                } else {
                    echo $group['firstname'] . ' ' . $group['lastname'];
                }
                echo '</td>';
                echo '<td data-sort-value="' . $group['modified'] . '">' . date( $mr_date_format, $group['modified'] ) . '</td>';
                echo '<td>' . $group['total'] . '</td>';

                // set visible to 0, do not remove for real...
                if ( $allowremove ) {
                    echo '<td><a class="dashicons dashicons-dismiss" rel="remove" href="' . admin_url( 'admin.php?page=member-group-list' ) .
                         '&amp;remove-group=' . $group['id'] . '" title="' .
                         __( 'Remove this group', 'member-register' ) . ', ' . $group['title'] . '">&nbsp;</a></td>';
                }
                echo '</tr>';
            }
            ?>
            </tbody>
        </table>
    <?php
    } else {
        echo '<p>' . __( 'Could not find any group with this request', 'member-register' ) . '.</p>';
    }
}


function mr_group_new() {
    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_GROUP_MANAGE ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    global $wpdb;



    ?>
    <div class="wrap">
        <h3><?php echo __( 'Create new group that must have at least one member', 'member-register' ); ?></h3>
        <?php
        mr_new_group_form();
        ?>
    </div>

<?php

}

function mr_insert_new_group( $postdata ) {
    global $wpdb;
    global $userdata;

    if ( isset( $postdata['members'] ) && is_array( $postdata['members'] ) &&
         count( $postdata['members'] ) > 0 && isset( $postdata['title'] ) && $postdata['title'] != ''
    ) {
        $values = [
            'title'    => mr_htmlent( $postdata['title'] ),
            'creator'  => $userdata->mr_memberid,
            'modified' => time()
        ];
        $insert = $wpdb->insert(
            $wpdb->prefix . 'mr_group',
            $values,
            [
                '%s',
                '%d',
                '%d'
            ]
        );
        if ( $insert !== false ) {
            $id     = $wpdb->insert_id;
            $setval = [];

            foreach ( $postdata['members'] as $member ) {
                $setval[] = '(' . $id . ', ' . intval( $member ) . ')';
            }
            $sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_group_member (group_id, member_id) VALUES ' . implode( ', ', $setval );

            return $wpdb->query( $sql );
        }
    }

    return false;
}

function mr_group_update( $id, $postdata ) {
    global $wpdb;
    global $userdata;


    if ( isset( $postdata['members'] ) && is_array( $postdata['members'] ) &&
         count( $postdata['members'] ) > 0 && isset( $postdata['title'] ) && $postdata['title'] != ''
    ) {
        // This will fail if title was not changed...
        $update = $wpdb->update(
            $wpdb->prefix . 'mr_group',
            [
                'title' => $postdata['title']
            ],
            [
                'id' => $id
            ],
            [
                '%s'
            ],
            [
                '%d'
            ]
        );

        // Remove those that exists
        $deletion = $wpdb->delete(
            $wpdb->prefix . 'mr_group_member',
            [
                'group_id' => $id
            ],
            [
                '%d'
            ]
        );

        // Insert current
        if ( $deletion !== false ) {
            $setval = [];

            foreach ( $postdata['members'] as $member ) {
                $setval[] = '(' . $id . ', ' . intval( $member ) . ')';
            }

            $sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_group_member (group_id, member_id) VALUES ' . implode( ', ', $setval );

            return $wpdb->query( $sql );
        }
    }

    return false;
}


/**
 * Print out a form for creating new groups
 * $members array of pre selected members by id
 * @param null $members
 * @param string $title
 * @param null $id
 */
function mr_new_group_form( $members = null, $title = '', $id = null ) {
    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_GROUP_MANAGE ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    global $wpdb;

    $action = admin_url( 'admin.php?page=member-group-list' );
    if ( $id != null ) {
        $action .= '&amp;group-member=' . $id;
    }
    ?>
    <form name="form1" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="mr_submit_hidden_group" value="Y"/>
        <table class="form-table mr-table" id="mrform">
            <tr class="form-field">
                <th><?php echo __( 'Title', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'name for easy recognition', 'member-register' ); ?>)</span>
                </th>
                <td><input type="text" name="title" required value="<?php echo $title; ?>"/></td>
            </tr>
            <tr class="form-field">
                <th><?php echo __( 'Members', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'multiple choice', 'member-register' ); ?>)</span></th>
                <td><select class="chosen" name="members[]" required multiple size="7"
                            data-placeholder="<?php echo __( 'Choose members', 'member-register' ); ?>">
                        <option value=""></option>
                        <?php
                        $sql   = 'SELECT CONCAT(lastname, ", ", firstname) AS name, id
				    FROM ' . $wpdb->prefix . 'mr_member
				    WHERE active = 1 AND A.visible = 1 ORDER BY lastname ASC';
                        $users = $wpdb->get_results( $sql, ARRAY_A );
                        foreach ( $users as $user ) {
                            echo '<option value="' . $user['id'] . '"';
                            if ( $members != null && in_array( $user['id'], $members ) ) {
                                echo ' selected';
                            }
                            echo '>' . $user['name'] . ' (' . $user['id'] . ')</option>';
                        }
                        ?>
                    </select></td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="Submit" class="button-primary"
                   value="<?php echo __( 'Create group', 'member-register' ); ?>"/>
        </p>

    </form>
<?php
}
