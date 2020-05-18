<?php
/**
 * Part of Member Register
 * License: MIT (http://opensource.org/licenses/MIT)
 *
 * Payment related functions
 */


function mr_payment_new() {
    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_PAYMENT_MANAGE ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    global $wpdb;


    // Check for possible insert
    $hidden_field_name = 'mr_submit_hidden_payment';
    if ( isset( $_POST[ $hidden_field_name ] ) && $_POST[ $hidden_field_name ] == 'Y' ) {
        if ( mr_insert_new_payment( $_POST ) ) {
            echo '<div class="updated"><p>';
            echo '<strong>' . __( 'New payment added', 'member-register' ) . '</strong>';
            echo '</p></div>';
        } else {
            echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
        }
    }

    ?>
    <div class="wrap">
        <h2><?php echo __( 'Create new payments for multiple members at a time', 'member-register' ); ?></h2>

        <p><?php echo __( 'Reference number will be calculated and shown once the payment is created and shown in the list.', 'member-register' ); ?></p>
        <?php
        $sql   = 'SELECT CONCAT(lastname, ", ", firstname) AS name, id
		    FROM ' . $wpdb->prefix . 'mr_member
		    WHERE visible = 1 ORDER BY lastname ASC';
        $users = $wpdb->get_results( $sql, ARRAY_A );
        mr_new_payment_form( $users );
        ?>
    </div>

<?php

}


function mr_payment_list() {
    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_PAYMENT_MANAGE ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    global $wpdb;

    if ( isset( $_POST['haspaid'] ) && is_numeric( $_POST['haspaid'] ) ) {
        $today = date( 'Y-m-d' );

        $update = $wpdb->update(
            $wpdb->prefix . 'mr_payment',
            [
                'paidday' => $today
            ],
            [
                'id' => $_POST['haspaid']
            ],
            [
                '%s'
            ],
            [
                '%d'
            ]
        );

        if ( $update !== false ) {
            echo '<div class="updated"><p>';
            echo '<strong>' . __( 'Mark the payment as paid today', 'member-register' ) . ', ' . $today . '</strong>';
            echo '</p></div>';
        } else {
            echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
        }
    } else if ( isset( $_GET['removepayment'] ) && is_numeric( $_GET['removepayment'] ) ) {
        // Mark the given payment visible=0, so it can be recovered just in case...
        $id     = intval( $_GET['removepayment'] );
        $update = $wpdb->update(
            $wpdb->prefix . 'mr_payment',
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
            echo '<strong>' . __( 'The payment cleared', 'member-register' ) . ' (' . $id . ')</strong>';
            echo '</p></div>';
        } else {
            echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
        }
    }

    echo '<div class="wrap">';
    echo '<h2>' . __( 'Payments', 'member-register' ) . '</h2>';

    mr_show_payments_lists( null ); // no specific member
    echo '</div>';
}


/**
 * List all payments for all members.
 * @param $memberid
 */
function mr_show_payments_lists( $memberid ) {
    ?>
    <h3><?php echo __( 'Unpaid fees', 'member-register' ); ?></h3>
    <?php
    if ( mr_has_permission( MR_ACCESS_PAYMENT_MANAGE ) ) {
        echo ' <p>' . __( 'Mark as paid with the OK button.', 'member-register' ) . '</p>';
    }

    mr_show_payments( $memberid, true );
    ?>
    <hr/>
    <h3><?php echo __( 'Fees paid to the', 'member-register' ); ?></h3>
    <?php
    mr_show_payments( $memberid, false );
}


/**
 * Show list of payments for a member,
 * for all, unpaid, paid ones.
 * @param null $memberid
 * @param bool $isUnpaidView
 */
function mr_show_payments( $memberid = null, $isUnpaidView = false ) {
    global $wpdb;
    global $userdata;

    $allowremove = true; // visible = 0
    $allowreview = true; // paid = 1
    // If no rights, only own info
    if ( ! mr_has_permission( MR_ACCESS_PAYMENT_MANAGE ) ) {
        $memberid    = $userdata->mr_memberid;
        $allowremove = false;
        $allowreview = false;
    }

    $where = '';
    if ( $memberid != null && is_numeric( $memberid ) ) {
        $where .= 'AND A.member = \'' . $memberid . '\' ';
    }
    if ( $isUnpaidView ) {
        $where .= 'AND A.paidday = \'0000-00-00\' ';
    } else {
        $where .= 'AND A.paidday != \'0000-00-00\' ';
    }
    $sql = 'SELECT A.*, B.firstname, B.lastname, B.id AS memberid FROM ' . $wpdb->prefix .
           'mr_payment A JOIN ' . $wpdb->prefix .
           'mr_member B ON A.member = B.id WHERE A.visible = 1 AND B.visible = 1 ' .
           $where . 'ORDER BY A.deadline DESC';
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
                <?php
                if ( $isUnpaidView && $allowreview ) {
                    echo '<th data-sort="int" filter="false">' . __( 'Paid?', 'member-register' ) . '</th>';
                }
                if ( $memberid == null ) {
                    ?>
                    <th data-sort="string"><?php echo __( 'Last name', 'member-register' ); ?></th>
                    <th data-sort="string"><?php echo __( 'First name', 'member-register' ); ?></th>
                <?php
                }
                ?>
                <th data-sort="string-ins"><?php echo __( 'Type', 'member-register' ); ?></th>
                <th data-sort="float"><?php echo __( 'Summa (EUR)', 'member-register' ); ?></th>
                <th data-sort="int"><?php echo __( 'Reference', 'member-register' ); ?></th>
                <th data-sort="int" class="sorting-desc"><?php echo __( 'Due date', 'member-register' ); ?></th>
                <?php
                if ( ! $isUnpaidView ) {
                    echo '<th data-sort="int">' . __( 'The Payment date', 'member-register' ) . '</th>';
                }
                ?>
                <th data-sort="int"><?php echo __( 'Payment valid until', 'member-register' ); ?></th>
                <?php
                if ( $allowremove ) {
                    echo '<th>' . __( 'Delete', 'member-register' ) . '</th>';
                }
                ?>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ( $res as $payment ) {
                echo '<tr id="payment_' . $payment['id'] . '">';
                if ( $isUnpaidView && $allowreview ) {
                    echo '<td>';
                    if ( $payment['paidday'] == '0000-00-00' ) {
                        echo '<form action="admin.php?page=member-payment-list" method="post" autocomplete="on">';
                        echo '<input type="hidden" name="haspaid" value="' . $payment['id'] . '"/>';
                        echo '<input type="submit" class="button" value="OK"/></form>';
                    }
                    echo '</td>';
                }
                if ( $memberid == null ) {
                    $url = '<a href="' . admin_url( 'admin.php?page=member-register-control' ) .
                           '&memberid=' . $payment['memberid'] . '" title="' . $payment['firstname'] .
                           ' ' . $payment['lastname'] . '">';
                    echo '<td data-sort-value="' . $payment['lastname'] . '">' . $url . $payment['lastname'] . '</a></td>';
                    echo '<td data-sort-value="' . $payment['firstname'] . '">' . $url . $payment['firstname'] . '</a></td>';
                }
                echo '<td>' . $payment['type'] . '</td>';
                echo '<td>' . $payment['amount'] . '</td>';
                echo '<td>' . $payment['reference'] . '</td>';
                echo '<td data-sort-value="' . str_replace( '-', '', $payment['deadline'] ) . '">' . $payment['deadline'] . '</td>';
                if ( ! $isUnpaidView ) {
                    echo '<td data-sort-value="' . str_replace( '-', '', $payment['paidday'] ) . '">' . $payment['paidday'] . '</td>';
                }
                echo '<td data-sort-value="' . str_replace( '-', '', $payment['validuntil'] ) . '">' . $payment['validuntil'] . '</td>';

                // set visible to 0, do not remove for real...
                if ( $allowremove ) {
                    echo '<td><a class="dashicons dashicons-dismiss" rel="remove" href="' . admin_url( 'admin.php?page=member-payment-list' ) .
                         '&amp;removepayment=' . $payment['id'] . '" title="' . __( 'Remove this payment', 'member-register' ) . ', ' .
                         $payment['firstname'] . ' ' . $payment['lastname'] . ' (' . $payment['amount'] . ' EUR)">&nbsp;</a></td>';
                }
                echo '</tr>';
            }
            ?>
            </tbody>
        </table>
    <?php
    } else {
        echo '<p>' . __( 'Could not find any payments', 'member-register' ) . '</p>';
    }
}


function mr_insert_new_payment( $postdata ) {
    global $wpdb;

    $keys   = [];
    $values = [];
    $setval = [];

    $required = [ 'type', 'amount', 'deadline', 'validuntil' ];


    if ( isset( $postdata['members'] ) && is_array( $postdata['members'] ) && count( $postdata['members'] ) > 0 ) {
        foreach ( $postdata as $k => $v ) {
            if ( in_array( $k, $required ) ) {
                // sanitize
                $keys[]   = mr_urize( $k );
                $values[] = "'" . mr_htmlent( $v ) . "'";
            }
        }

        $keys[] = 'member';
        $keys[] = 'reference';
        $keys[] = 'paidday';

        $paidday = '0000-00-00';
        if ( isset( $postdata['alreadypaid'] ) && ( $postdata['alreadypaid'] == 'on' || $postdata['alreadypaid'] == '1' ) ) {
            $paidday = date( 'Y-m-d' );
        }

        $id = intval( '2' . $wpdb->get_var( 'SELECT MAX(id) FROM ' . $wpdb->prefix . 'mr_payment' ) );

        foreach ( $postdata['members'] as $member ) {
            $id ++;
            // calculate reference number
            $ref = "'" . mr_reference_count( $id ) . "'";

            $setval[] = '(' . implode( ', ', array_merge( $values, [
                            '"' . $member . '"',
                            $ref,
                            '"' . $paidday . '"'
                        ] ) ) . ')';

        }
    }
    $sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_payment (' . implode( ', ', $keys ) . ') VALUES ' . implode( ', ', $setval );

    //echo '<div class="error"><p>' . $sql . '</p></div>';

    return $wpdb->query( $sql );
}


/**
 * Print out a form for creating new payments
 * @param $members
 */
function mr_new_payment_form( $members ) {
    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_PAYMENT_MANAGE ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    global $wpdb;
    ?>
    <form name="form1" method="post" action="" enctype="multipart/form-data" autocomplete="on">
        <input type="hidden" name="mr_submit_hidden_payment" value="Y"/>
        <table class="form-table mr-table" id="mrform">
            <tr class="form-field">
                <th><?php echo __( 'Member', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'multiple choice', 'member-register' ); ?>)</span></th>
                <td><select class="chosen" name="members[]" multiple size="7"
                            data-placeholder="<?php echo __( 'Choose members', 'member-register' ); ?>">
                        <option value=""></option>
                        <?php
                        foreach ( $members as $user ) {
                            echo '<option value="' . $user['id'] . '">' . $user['name'] . ' (' . $user['id'] . ')</option>';
                        }
                        ?>
                    </select></td>
            </tr>
            <tr class="form-field">
                <th><?php echo __( 'Type', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'annual, lifetime, etc...', 'member-register' ); ?>)</span>
                </th>
                <td><input type="text" name="type" value="" required list="types"/></td>
            </tr>
            <tr class="form-field">
                <th><?php echo __( 'Amount', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'EUR', 'member-register' ); ?>)</span></th>
                <td><input type="number" name="amount" value="" required list="amounts"/></td>
            </tr>
            <tr class="form-field">
                <th><?php echo __( 'Deadline', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'Three weeks in the future by default', 'member-register' ); ?>
                        )</span></th>
                <td><input type="text" name="deadline" class="pickday required" required value="<?php
                    echo date( 'Y-m-d', time() + 60 * 60 * 24 * 21 );
                    ?>"/></td>
            </tr>
            <tr class="form-field">
                <th><?php echo __( 'Valid until', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'end of the current year', 'member-register' ); ?>)</span>
                </th>
                <td><input type="text" name="validuntil" class="pickday" required value="<?php
                    echo date( 'Y' ) . '-12-31';
                    ?>"/></td>
            </tr>
            <tr class="form-field">
                <th><?php echo __( 'Already paid', 'member-register' ); ?> <span
                        class="description">(<?php echo __( 'paid today', 'member-register' ); ?>)</span></th>
                <td><input type="checkbox" name="alreadypaid" class="w4em"/></td>
            </tr>
        </table>

        <datalist id="types">
            <?php
            $sql     = 'SELECT DISTINCT type FROM ' . $wpdb->prefix . 'mr_payment WHERE visible = 1 ORDER BY type ASC';
            $results = $wpdb->get_results( $sql, ARRAY_A );
            foreach ( $results as $res ) {
                echo '<option value="' . $res['type'] . '"/>';
            }
            ?>
        </datalist>
        <datalist id="amounts">
            <?php
            $sql     = 'SELECT DISTINCT amount FROM ' . $wpdb->prefix . 'mr_payment WHERE visible = 1 ORDER BY amount ASC';
            $results = $wpdb->get_results( $sql, ARRAY_A );
            foreach ( $results as $res ) {
                echo '<option value="' . $res['amount'] . '"/>';
            }
            ?>
        </datalist>

        <p class="submit">
            <input type="submit" name="Submit" class="button-primary"
                   value="<?php echo __( 'Create payment', 'member-register' ); ?>"/>
        </p>

    </form>
<?php
}


/**
 * Counts and adds the check number used in the Finnish invoices.
 * @param $given
 * @return string
 */
function mr_reference_count( $given ) {
    $div    = [ 7, 3, 1 ];
    $len    = strlen( $given );
    $arr    = str_split( $given );
    $summed = 0;
    for ( $i = $len - 1; $i >= 0; -- $i ) {
        $summed += $arr[ $i ] * $div[ ( $len - 1 - $i ) % 3 ];
    }
    $check = ( 10 - ( $summed % 10 ) ) % 10;

    return $given . $check;
}
