<?php

/**
 * Part of Member Register
 * License: MIT (http://opensource.org/licenses/MIT)
 *
 * Club related functions.
 */
// TODO: has many calls to access level checking but kept until decided if they are needed...
// might be that there will be more levels thus checks needed

function mr_club_list() {
    global $wpdb;
    global $userdata;

    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_CLUB_MANAGE ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }


    echo '<div class="wrap">';

    if ( isset( $_GET['removeclub'] ) && is_numeric( $_GET['removeclub'] ) && mr_has_permission( MR_ACCESS_CLUB_MANAGE ) ) {
        $clubId = intval( $_GET['removeclub'] );
        // Mark the given club visible=0, so it can be recovered just in case...
        $update = $wpdb->update(
            $wpdb->prefix . 'mr_club', [
                'visible' => 0
            ], [
                'id' => $clubId
            ], [
                '%d'
            ], [
                '%d'
            ]
        );

        if ( $update !== false ) {
            echo '<div class="updated"><p>';
            echo '<strong>' . __( 'The Club removed', 'member-register' ) . ' (' . $clubId . ')</strong>';
            echo '</p></div>';
        } else {
            echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
        }
    }

    if ( isset( $_GET['club'] ) && is_numeric( $_GET['club'] ) ) {
        $id = intval( $_GET['club'] );

        // Was there an update of this club?
        $hidden_field_name = 'mr_submit_hidden_club';
        if ( isset( $_POST[ $hidden_field_name ] ) && $_POST[ $hidden_field_name ] == 'Y' && mr_has_permission( MR_ACCESS_CLUB_MANAGE ) ) {
            $_POST['id'] = $id;
            if ( mr_update_club( $_POST ) ) {
                echo '<div class="updated"><p>';
                echo '<strong>' . __( 'The clubs information has been updated.', 'member-register' ) . '</strong>';
                echo '</p></div>';
            } else {
                echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
            }
        }

        $sql = 'SELECT * FROM ' . $wpdb->prefix . 'mr_club WHERE id = ' . $id . ' AND visible = 1 LIMIT 1';
        $res = $wpdb->get_row( $sql, ARRAY_A );

        if ( isset( $_GET['edit'] ) ) {
            echo '<h1>' . __( 'Modify', 'member-register' ) . ' ' . $res['title'] . '</h1>';
            mr_club_form( $res );
        } else {
            echo '<h1>' . $res['title'] . '</h1>';
            echo '<p>' . $res['address'] . '</p>';


            echo '<p><a href="' . admin_url( 'admin.php?page=member-club-list' ) . '&club=' .
                 $id . '&edit" title="' . __( 'Modify this club', 'member-register' ) . '" class="button-primary">' .
                 __( 'Modify this club', 'member-register' ) . '</a></p>';
            echo '<h2>' . __( 'Active members in this club', 'member-register' ) . '</h2>';
            mr_show_members( [
                'club'   => $id,
                'active' => true
            ] );
        }
    } else {
        echo '<h1>' . __( 'Clubs', 'member-register' ) . '</h1>';
        echo '<p>' . __( 'Clubs in which the members of the Finnish Yuishinkai Association are practising.',
                'member-register' ) . '</p>';
        echo '<p>' . __( 'Places where the martial arts of the Association are practised.', 'member-register' ) . '</p>';

        // Was there an insert of a new club?
        $hidden_field_name = 'mr_submit_hidden_club';
        if ( isset( $_POST[ $hidden_field_name ] ) && $_POST[ $hidden_field_name ] == 'Y' && mr_has_permission( MR_ACCESS_CLUB_MANAGE ) ) {
            if ( mr_insert_new_club( $_POST ) ) {
                echo '<div class="updated"><p>';
                echo '<strong>' . __( 'New club added.', 'member-register' ) . '</strong>';
                echo '</p></div>';
            } else {
                echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
            }
        }

        if ( isset( $_GET['createclub'] ) ) {
            mr_club_form();
        } else {
            echo '<p><a href="' . admin_url( 'admin.php?page=member-club-list' ) . '&createclub"' .
                 ' title="' . __( 'Create a new Club', 'member-register' ) . '" class="button-primary">' .
                 __( 'Create a new Club', 'member-register' ) . '</a></p>';

            mr_show_clubs();
        }
    }

    echo '</div>';
}

/**
 * @param array|null $data
 */
function mr_club_form( $data = null ) {
    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_CLUB_MANAGE ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    $values = [
        'title'   => '',
        'address' => ''
    ];
    $action = admin_url( 'admin.php?page=member-club-list' );

    if ( is_array( $data ) ) {
        // Assume this to be an edit for existing
        $values = array_merge( $values, $data );
        $action .= '&club=' . $values['id'];
    }
    ?>
    <form name="form1" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" autocomplete="on">
        <input type="hidden" name="mr_submit_hidden_club" value="Y"/>
        <table class="form-table" id="mrform">
            <tr class="form-field">
                <th><?php echo __( 'Name', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'official title', 'member-register' ); ?>)</span></th>
                <td><input type="text" name="title" required
                           value="<?php echo $values['title']; ?>"/></td>
            </tr>
            <tr class="form-field">
                <th><?php echo __( 'Address', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'main training place', 'member-register' ); ?>)</span></th>
                <td><input type="text" name="address" required
                           value="<?php echo $values['address']; ?>"/></td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Publish' ) ?>"/>
        </p>

    </form>
<?php
}

/**
 * List clubs and numbr of active members in them
 */
function mr_show_clubs() {
    global $wpdb;
    global $userdata;

    // id, title, address, visible

    $sql = 'SELECT A.*, COUNT(B.id) AS members FROM ' . $wpdb->prefix .
           'mr_club A LEFT JOIN ' . $wpdb->prefix .
           'mr_member B ON B.club = A.id
        WHERE A.visible = 1 AND (B.active = 1 OR B.active IS NULL) AND (B.visible = 1 OR B.visible IS NULL)
        GROUP BY A.id ORDER BY A.title ASC';

    //echo '<div class="error"><p>' . $sql . '</p></div>';

    $clubs = $wpdb->get_results( $sql, ARRAY_A );

    $allowremove = false;
    if ( mr_has_permission( MR_ACCESS_CLUB_MANAGE ) ) {
        $allowremove = true;
    }
    ?>
    <table class="wp-list-table widefat sorter">
        <caption>
            <label><input type="text" id="tablesearch"/></label>
            <p></p>
        </caption>
        <thead>
        <tr>
            <th data-sort="string" class="sorting-asc"><?php echo __( 'Name', 'member-register' ); ?></th>
            <th data-sort="string"><?php echo __( 'Address', 'member-register' ); ?></th>
            <th data-sort="int"><?php echo __( 'Active members', 'member-register' ); ?></th>
            <?php
            if ( $allowremove ) {
                echo '<th class="w8em">' . __( 'Delete', 'member-register' ) . '</th>';
            }
            ?>
        </tr>
        </thead>
        <tbody>

        <?php
        foreach ( $clubs as $club ) {
            $url = '<a href="' . admin_url( 'admin.php?page=member-club-list' ) . '&club=' . $club['id'] .
                   '" title="' . __( 'List of active members in the club called:',
                    'member-register' ) . ' ' . $club['title'] . '">';
            echo '<tr id="user_' . $club['id'] . '">';
            echo '<td data-sort-value="' . $club['title'] . '">' . $url . $club['title'] . '</a></td>';
            echo '<td data-sort-value="' . $club['address'] . '">' . $url . $club['address'] . '</a></td>';
            echo '<td data-sort-value="' . $club['members'] . '">' . $url . $club['members'] . '</a></td>';
            // set visible to 0, do not remove for real...
            if ( $allowremove ) {
                echo '<td><a class="dashicons dashicons-dismiss" rel="remove" href="' . admin_url( 'admin.php?page=member-club-list' ) .
                     '&amp;removeclub=' . $club['id'] . '" title="' . __( 'Delete club', 'member-register' ) . ': ' .
                     $club['title'] . '">&nbsp;</a></td>';
            }
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>
<?php
}

function mr_insert_new_club( $postdata ) {
    global $wpdb;

    if ( isset( $postdata['title'] ) && $postdata['title'] != '' &&
         isset( $postdata['address'] ) && $postdata['address'] != ''
    ) {
        return $wpdb->insert(
            $wpdb->prefix . 'mr_club', [
                'title'   => $postdata['title'],
                'address' => $postdata['address']
            ], [
                '%s',
                '%s'
            ]
        );
    }

    return false;
}

function mr_update_club( $postdata ) {
    global $wpdb;

    if ( isset( $postdata['title'] ) && $postdata['title'] != '' &&
         isset( $postdata['address'] ) && $postdata['address'] != '' &&
         isset( $postdata['id'] ) && is_numeric( $postdata['id'] )
    ) {
        return $wpdb->update(
            $wpdb->prefix . 'mr_club', [
                'title'   => $postdata['title'],
                'address' => $postdata['address']
            ], [
                'id' => $postdata['id']
            ], [
                '%s',
                '%s'
            ], [
                '%d'
            ]
        );
    }

    return false;
}

