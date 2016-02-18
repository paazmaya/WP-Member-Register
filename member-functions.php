<?php
/**
 * Part of Member Register
 * License: MIT (http://opensource.org/licenses/MIT)
 *
 * General functions and helpers.
 */

/**
 * Check for permission for doing certain things.
 *
 * @param $access int Access right that is required
 * @param $rights int Access rights that the user has, if any
 *
 * @return bool
 */
function mr_has_permission( $access, $rights = 0 ) {
    global $userdata;

    if ( $rights == 0 ) {
        if ( ! isset( $userdata->mr_access ) ) {
            return false;
        }
        $rights = $userdata->mr_access;
    }

    if ( $access & $rights ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Show all rights that the given access has
 * @param $rights
 */
function list_user_rights( $rights ) {
    global $mr_access_type;

    $list = [];
    foreach ( $mr_access_type as $key => $val ) {
        if ( mr_has_permission( $key, $rights ) ) {
            $list[] = $val;
        }
    }
    echo implode( '<br/>', $list );
}

function mr_show_access_values() {
    global $mr_access_type;
    echo '<p>' . __( 'Below is a short explanation for each access level', 'member-register' ) . '. ' .
         __( 'They can be added or removed if needed.', 'member-register' ) . '</p>';
    echo '<ul>';
    foreach ( $mr_access_type as $k => $v ) {
        echo '<li title="' . $v . ' (' . $k . ')">[' . decbin( $k ) . '] ' . $v . '</li>';
    }
    echo '</ul>';
}


/**
 * Get a set of items from the given table, where should be like something.
 * @param string $table
 * @param string $where
 * @param string $shouldbe
 * @param string $order
 * @return array|null|object
 */
function mr_get_list( $table, $where = '', $shouldbe = '', $order = '1 ASC' ) {
    global $wpdb;
    $sql = 'SELECT * FROM ' . $wpdb->prefix . 'mr_' . $table;
    if ( isset( $where ) && $where != '' ) {
        $sql .= ' WHERE ' . $where . ' LIKE \'%' . $shouldbe . '%\'';
    }
    $sql .= ' ORDER BY ' . $order;

    return $wpdb->get_results( $sql, ARRAY_A );
}


/**
 * Testing purposes....
 */
function print_access() {
    global $userdata;
    global $mr_access_type;

    echo '<p>';

    foreach ( $mr_access_type as $key => $val ) {
        echo 'Key: ' . $key . ', in binary: ' . decbin( $key ) . ', val: ' . $val . '<br/>';
    }

    echo 'MR_ACCESS_OWN_INFO: ' . MR_ACCESS_OWN_INFO . '<br/>';
    echo 'MR_ACCESS_FILES_VIEW: ' . MR_ACCESS_FILES_VIEW . '<br/>';
    echo 'MR_ACCESS_CONVERSATION: ' . MR_ACCESS_CONVERSATION . '<br/>';
    echo 'MR_ACCESS_FORUM_CREATE: ' . MR_ACCESS_FORUM_CREATE . '<br/>';
    echo 'MR_ACCESS_FORUM_DELETE: ' . MR_ACCESS_FORUM_DELETE . '<br/>';
    echo 'MR_ACCESS_MEMBERS_VIEW: ' . MR_ACCESS_MEMBERS_VIEW . '<br/>';
    echo 'MR_ACCESS_MEMBERS_EDIT: ' . MR_ACCESS_MEMBERS_EDIT . '<br/>';
    echo 'MR_ACCESS_GRADE_MANAGE: ' . MR_ACCESS_GRADE_MANAGE . '<br/>';
    echo 'MR_ACCESS_PAYMENT_MANAGE: ' . MR_ACCESS_PAYMENT_MANAGE . '<br/>';
    echo 'MR_ACCESS_CLUB_MANAGE: ' . MR_ACCESS_CLUB_MANAGE . '<br/>';
    echo 'MR_ACCESS_FILES_MANAGE: ' . MR_ACCESS_FILES_MANAGE . '<br/>';

    echo '<br/>You have: ' . decbin( $userdata->mr_access ) . ' / ' . $userdata->mr_access;
    echo '<br/>Full rights would be: ' . bindec( 11111111111 );

    echo '</p>';
}


function mr_htmlent( $str ) {
    return htmlentities( trim( $str ), ENT_QUOTES, 'UTF-8' );
}

function mr_htmldec( $str ) {
    return html_entity_decode( trim( $str ), ENT_QUOTES, 'UTF-8' );
}

/**
 * Converts a block of text to be suitable for the use in URI.
 *
 * @param    string $str
 *
 * @return string
 */
function mr_urize( $str ) {
    $str = mb_strtolower( $str, 'UTF-8' );
    $str = mr_htmldec( $str );
    $str = str_replace( [ ' ', ',', '@', '$', '/', '\\', '&', '!', '=', '%', '´', '`', '^', '¨' ], '-', $str );
    $str = str_replace( [ '--', '---' ], '-', $str );
    $str = str_replace( [ '...', '..' ], '.', $str );
    // a...z = ASCII table values 97...122
    $str = str_replace( [ '?', '"', '\'', ':', '(', ')', '*', '[', ']', '{', '}' ], '', $str );
    $str = str_replace( [ 'ä', 'æ', 'å' ], 'a', $str );
    $str = str_replace( [ 'ō', 'ö', 'ø' ], 'o', $str );
    $str = str_replace( [ 'š', 'ß' ], 's', $str );
    $str = str_replace( [ 'ć', 'č' ], 'c', $str );
    $str = str_replace( [ 'ž' ], 'z', $str );
    $str = str_replace( [ '--', '---', '----' ], '-', $str );
    $str = trim( $str, ' -' );

    return $str;
}


/**
 * @param array $filters
 * @param string $prepend If not empty, should contain WHERE and the first rule
 * @return string SQL query phrase
 */
function mr_filter_list( $filters = null, $prepend = '' ) {
    $wheres = [];
    $where  = $prepend;
    if ( is_array( $filters ) ) {
        foreach ($filters as $key => $value) {
            if ( is_numeric( $value ) ) {
                $wheres[] = 'A.' . $key . ' = ' . intval( $value );
            }
            else if ( is_bool( $value ) ) {
                $wheres[] = 'A.' . $key . ' = ' . ( $value ? 1 : 0 );
            }
        }

        if ( count( $wheres ) > 0 ) {
            $where = $where . ' AND ' . implode( ' AND ', $wheres );
        }
    }
    return $where;
}
