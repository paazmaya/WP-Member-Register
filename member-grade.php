<?php
/**
 * Part of Member Register
 * License: MIT (http://opensource.org/licenses/MIT)
 *
 * Grade related functions
 */


function mr_grade_new() {
    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_GRADE_MANAGE ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    global $wpdb;

    // Check for possible insert
    $hidden_field_name = 'mr_submit_hidden_grade';
    if ( isset( $_POST[ $hidden_field_name ] ) && $_POST[ $hidden_field_name ] == 'Y' ) {
        if ( mr_insert_new_grade( $_POST ) ) {
            echo '<div class="updated"><p>';
            echo '<strong>' . __( 'Grade added', 'member-register' ) . '</strong>';
            echo '</p></div>';
        } else {
            echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
        }
    }

    ?>
    <div class="wrap">

        <h2><?php echo __( 'Nominate grades', 'member-register' ); ?></h2>
        <?php
        $sql   = 'SELECT CONCAT(lastname, ", ", firstname) AS name, id
		    FROM ' . $wpdb->prefix . 'mr_member
		    WHERE visible = 1 ORDER BY lastname ASC';
        $users = $wpdb->get_results( $sql, ARRAY_A );
        mr_grade_form( $users );
        ?>
    </div>

<?php
}


function mr_grade_list() {
    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_GRADE_MANAGE ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    global $wpdb;

    if ( isset( $_GET['removegrade'] ) && is_numeric( $_GET['removegrade'] ) ) {
        // Mark the given grade visible=0, so it can be recovered just in case...
        $id = intval( $_GET['removegrade'] );

        // http://codex.wordpress.org/Class_Reference/wpdb#UPDATE_rows
        $update = $wpdb->update(
            $wpdb->prefix . 'mr_grade',
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
            echo '<strong>' . __( 'Grade removed', 'member-register' ) . ' (' . $id . ')</strong>';
            echo '</p></div>';
        } else {
            echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
        }
    }

    echo '<div class="wrap">';
    echo '<h2>' . __( 'Grades', 'member-register' ) . '</h2>';
    echo '<p>' . __( 'Members listed with their latest grade.', 'member-register' ) . '</p>';
    mr_show_grades();
    echo '</div>';
}

/**
 * Show grades for a single member or for everyone
 * @param null $memberid
 */
function mr_show_grades( $memberid = null ) {
    global $wpdb;
    global $userdata;
    global $mr_grade_values;
    global $mr_grade_types;

    $allowremove = true;

    // If no rights, only own info
    if ( ! mr_has_permission( MR_ACCESS_PAYMENT_MANAGE ) ) {
        $memberid    = $userdata->mr_memberid;
        $allowremove = false;
    }

    $where = '';
    $order = ', B.lastname ASC';
    if ( $memberid != null && is_numeric( $memberid ) ) {
        $where = 'AND B.id = \'' . $memberid . '\' ';
        $order = '';
    }

    $sql = 'SELECT A.*, B.firstname, B.lastname, B.id AS memberid FROM ' . $wpdb->prefix .
           'mr_grade A LEFT JOIN ' . $wpdb->prefix .
           'mr_member B ON A.member = B.id
		WHERE A.visible = 1 AND (B.visible = 1 OR B.visible IS NULL) ' . $where . 'ORDER BY A.day DESC' . $order . '';

    //echo '<div class="error"><p>' . $sql . '</p></div>';

    $res = $wpdb->get_results( $sql, ARRAY_A );

    // id member grade type location nominator day visible


    if ( count( $res ) > 0 ) {
        ?>
        <table class="wp-list-table mr-table widefat sorter">
            <caption>
                <label><input type="text" id="tablesearch"/></label>
                <p></p>
            </caption>
            <thead>
            <tr>
                <?php
                if ( $memberid == null ) {
                    ?>
                    <th data-sort="string"><?php echo __( 'Last name', 'member-register' ); ?></th>
                    <th data-sort="string"><?php echo __( 'First name', 'member-register' ); ?></th>
                <?php
                }
                ?>
                <th data-sort="int"><?php echo __( 'Grade', 'member-register' ); ?></th>
                <th data-sort="string"><?php echo __( 'Type', 'member-register' ); ?></th>
                <th data-sort="int" class="sorting-desc"><?php echo __( 'Nomination date', 'member-register' ); ?></th>
                <th data-sort="string"><?php echo __( 'Nominator', 'member-register' ); ?></th>
                <th data-sort="string"><?php echo __( 'Location', 'member-register' ); ?></th>
                <?php
                if ( $allowremove ) {
                    echo '<th class="w8em">' . __( 'Delete', 'member-register' ) . '</th>';
                }
                ?>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ( $res as $grade ) {
                $gradeName = array_key_exists( $grade['grade'], $mr_grade_values ) ? $mr_grade_values[ $grade['grade'] ] : $grade['grade'];
                echo '<tr id="grade_' . $grade['id'] . '">';
                if ( $memberid == null ) {
                    $url = '<a href="' . admin_url( 'admin.php?page=member-register-control' ) .
                           '&memberid=' . $grade['memberid'] . '" title="' . $grade['firstname'] .
                           ' ' . $grade['lastname'] . '">';
                    echo '<td data-sort-value="' . $grade['lastname'] . '">' . $url . $grade['lastname'] . '</a></td>';
                    echo '<td data-sort-value="' . $grade['firstname'] . '">' . $url . $grade['firstname'] . '</a></td>';
                }
                echo '<td data-sort-value="' . $grade['grade'] . '">' . $gradeName . '</td>';
                echo '<td title="' . $mr_grade_types[ $grade['type'] ] . '">' . $grade['type'] . '</td>';
                echo '<td data-sort-value="' . str_replace( '-', '', $grade['day'] ) . '">' . $grade['day'] . '</td>';
                echo '<td>' . $grade['nominator'] . '</td>';
                echo '<td>' . $grade['location'] . '</td>';
                // set visible to 0, do not remove for real...
                if ( $allowremove ) {
                    echo '<td><a class="dashicons dashicons-dismiss" rel="remove" href="' . admin_url( 'admin.php?page=member-grade-list' ) .
                         '&amp;removegrade=' . $grade['id'] . '" title="' . __( 'Delete grade', 'member-register' ) . ' ' .
                         $grade['type'] . ' ' . $gradeName . '">&nbsp;</a></td>';
                }
                echo '</tr>';
            }
            ?>
            </tbody>
        </table>
    <?php
    } else {
        echo '<p>' . __( 'There were no grades available with the given request', 'member-register' ) . '.</p>';
    }
}


/**
 * Insert the given grade
 *
 * @param $postdata array
 *
 * @return bool
 */
function mr_insert_new_grade( $postdata ) {
    global $wpdb;

    $keys   = [];
    $values = [];
    $setval = [];

    // Note that member/members are also required.
    $required = [ 'grade', 'type', 'location', 'nominator', 'day' ];

    foreach ( $postdata as $k => $v ) {
        if ( in_array( $k, $required ) ) {
            // sanitize
            $keys[] = mr_urize( $k );
            if ( $k == 'day' ) {
                $v = mr_htmlent( $v );
                if ( strlen( $v ) == 4 ) {
                    $v = $v . '-01-01';
                }
            }

            $values[] = "'" . mr_htmlent( $v ) . "'";
        }
    }
    $keys[] = 'member';

    if ( isset( $postdata['member'] ) ) {
        $postdata['members'] = [ $postdata['member'] ];
    }

    if ( isset( $postdata['members'] ) && is_array( $postdata['members'] ) && count( $postdata['members'] ) > 0 ) {
        foreach ( $postdata['members'] as $member ) {
            $setval[] = '(' . implode( ', ', array_merge( $values, [ '"' . intval( $member ) . '"' ] ) ) . ')';
        }

        $sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_grade (' . implode( ', ', $keys ) . ') VALUES ' . implode( ', ', $setval );

        //echo $sql;

        return $wpdb->query( $sql );
    } else {
        return false;
    }
}


/**
 * Print out a form that is used to give grades.
 *
 * @param $members array of members, {id: , name: }
 */
function mr_grade_form( $members ) {
    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_GRADE_MANAGE ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    global $wpdb;
    global $mr_grade_values;
    ?>
    <form name="form1" method="post" action="" enctype="multipart/form-data" autocomplete="on">
        <input type="hidden" name="mr_submit_hidden_grade" value="Y"/>
        <table class="form-table mr-table" id="mrform">
            <tr class="form-field">
                <th><?php echo __( 'Member', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'select multiple members if needed', 'member-register' ); ?>
                        )</span></th>
                <td>
                    <select class="chosen required" required name="members[]" multiple size="8"
                            data-placeholder="<?php echo __( 'Choose members', 'member-register' ); ?>">
                        <option value=""></option>
                        <?php
                        foreach ( $members as $user ) {
                            echo '<option value="' . $user['id'] . '">' . $user['name'] . ' (' . $user['id'] . ')</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr class="form-field">
                <th><?php echo __( 'Grade', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'key in parenthesis', 'member-register' ); ?>)</span></th>
                <td>
                    <select name="grade" class="required chosen" required
                            data-placeholder="<?php echo __( 'Choose a grade', 'member-register' ); ?>">
                        <option value=""></option>
                        <?php
                        foreach ( $mr_grade_values as $k => $v ) {
                            echo '<option value="' . $k . '">' . $v . ' (' . $k . ')</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr class="form-field">
                <th><?php echo __( 'Martial art', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'choose the martial art', 'member-register' ); ?>)</span>
                </th>
                <td>
                    <label><input type="radio" name="type" value="Yuishinkai" checked/> Yuishinkai</label><br/>
                    <label><input type="radio" name="type" value="Kobujutsu"/> Kobujutsu</label>
                </td>
            </tr>
            <tr class="form-field">
                <th><?php echo __( 'Location', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'city and country if not native', 'member-register' ); ?>
                        )</span></th>
                <td><input type="text" name="location" required value="" list="locations"/></td>
            </tr>
            <tr class="form-field">
                <th><?php echo __( 'Nominator', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'who nominated', 'member-register' ); ?>)</span></th>
                <td><input type="text" name="nominator" required value="" list="nominators"/></td>
            </tr>
            <tr class="form-field">
                <th><?php echo __( 'Date', 'member-register' ); ?> <span class="description">(YYYY-MM-DD)</span></th>
                <td><input type="text" name="day" class="pickday required" required value="<?php
                    echo date( 'Y-m-d', time() - 60 * 60 * 24 * 1 );
                    ?>" list="dates"/></td>
            </tr>

        </table>

        <datalist id="locations">
            <?php
            $sql     = 'SELECT DISTINCT location FROM ' . $wpdb->prefix . 'mr_grade WHERE visible = 1 ORDER BY location ASC';
            $results = $wpdb->get_results( $sql, ARRAY_A );
            foreach ( $results as $res ) {
                echo '<option value="' . $res['location'] . '"/>';
            }
            ?>
        </datalist>
        <datalist id="nominators">
            <?php
            $sql     = 'SELECT DISTINCT nominator FROM ' . $wpdb->prefix . 'mr_grade WHERE visible = 1 ORDER BY nominator ASC';
            $results = $wpdb->get_results( $sql, ARRAY_A );
            foreach ( $results as $res ) {
                echo '<option value="' . $res['nominator'] . '"/>';
            }
            ?>
        </datalist>
        <datalist id="dates">
            <?php
            $sql     = 'SELECT DISTINCT day FROM ' . $wpdb->prefix . 'mr_grade WHERE visible = 1 ORDER BY day ASC';
            $results = $wpdb->get_results( $sql, ARRAY_A );
            foreach ( $results as $res ) {
                echo '<option value="' . $res['day'] . '"/>';
            }
            ?>
        </datalist>

        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ) ?>"/>
        </p>

    </form>
<?php
}


