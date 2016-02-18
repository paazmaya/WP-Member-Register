<?php
/**
 * Part of Member Register
 * License: MIT (http://opensource.org/licenses/MIT)
 *
 * Files for members only
 * - Access can be for all, club, member level
 * All documents such as word, etc are converted to pdf which is transformed for each member separately
 * in order to have their name as a watermark.
 */

/**
 * Called if $_GET['download'] is set
 * @param $get
 */
function mr_file_download( $get ) {
    global $mr_file_base_directory;
    global $wpdb;

    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_FILES_VIEW ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    // $get should contain download: id / dir / basename
    // forward slash will be always available, but dir not

    $parts = explode( '/', $get );
    if ( count( $parts ) < 2 ) {
        wp_die( __( 'Not available.', 'member-register' ) );
    }

    //$basename = array_pop($parts);
    $id = intval( array_shift( $parts ) );
    //$dir = implode('/', $parts);

    $sql = 'SELECT basename, directory FROM ' . $wpdb->prefix . 'mr_file
	    WHERE visible = 1 AND id = \'' . $id . '\' LIMIT 1';
    $res = $wpdb->get_row( $sql, ARRAY_A );

    if ( $res ) {
        $real = realpath( $mr_file_base_directory . '/' . $res['directory'] . '/' . $id . '_' . $res['basename'] );
        if ( strpos( $real, $mr_file_base_directory ) !== false ) {
            $fp = fopen( $real, 'r' );

            header( "Content-Type: application/force-download" );
            header( "Content-Disposition: attachment; filename=" . $res['basename'] );
            header( "Content-length: " . filesize( $real ) );
            header( "Expires: " . gmdate( "D, d M Y H:i:s", mktime( date( "H" ) + 2, date( "i" ), date( "s" ), date( "m" ), date( "d" ), date( "Y" ) ) ) . " GMT" );
            header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" );
            header( "Cache-Control: no-cache, must-revalidate" );
            header( "Pragma: no-cache" );

            fpassthru( $fp );
            fclose( $fp );
        }
    } else {
        wp_die( __( 'Not found.', 'member-register' ) );
    }

    exit();
}

/**
 * Show a table of members based on the given filter if any.
 */
function mr_files_list() {
    global $wpdb;
    global $userdata;
    global $mr_access_type;
    global $mr_grade_values;
    global $mr_martial_arts;
    global $mr_date_format;
    global $mr_file_base_directory;

    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_FILES_VIEW ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    if ( isset( $_GET['remove-file'] ) &&
         is_numeric( $_GET['remove-file'] ) &&
         mr_has_permission( MR_ACCESS_FILES_MANAGE )
    ) {
        $id = intval( $_GET['remove-file'] );

        $update = $wpdb->update(
            $wpdb->prefix . 'mr_file', [
                'visible' => 0
            ], [
                'id' => $id
            ], [
                '%d'
            ], [
                '%d'
            ]
        );

        // How about moving the file?
        $sql  = 'SELECT basename, directory FROM ' . $wpdb->prefix . 'mr_file WHERE id = ' . $id . ' LIMIT 1';
        $info = $wpdb->get_row( $sql, ARRAY_A );

        // Because of this _remove directory, the user created dirs cannot begin with _
        $removed = $mr_file_base_directory . '/_removed';
        if ( ! file_exists( $removed ) ) {
            umask( 0000 );
            mkdir( $removed, 0775 );
        }

        rename( $mr_file_base_directory . '/' . $info['directory'] . '/' . $id . '_' . $info['basename'], $removed . '/' . $id . '_' . $info['basename'] );

        if ( $update !== false ) {
            echo '<div class="updated"><p>';
            echo '<strong>' . __( 'The file has been removed.', 'member-register' ) . ' (' . $info['directory'] . '/' . $info['basename'] . ')</strong>';
            echo '</p></div>';
        } else {
            echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
        }
    }

    $sql      = 'SELECT * FROM ' . $wpdb->prefix . 'mr_member
	    WHERE id = ' . $userdata->mr_memberid . ' AND visible = 1 LIMIT 1';
    $userinfo = $wpdb->get_row( $sql, ARRAY_A );

    if ( mr_has_permission( MR_ACCESS_FILES_MANAGE ) ) {
        $where = '';
    } else {
        $where = 'AND (A.clubonly = 0 OR A.clubonly = \'' . $userinfo['club'] . '\') ' .
                 'AND (A.artonly = \'\' OR A.artonly = \'' . $userinfo['martial'] . '\') ' .
                 'AND (A.mingrade = \'\' OR A.mingrade IN (SELECT grade FROM ' .
                 $wpdb->prefix . 'mr_grade WHERE member = \'' . $userdata->mr_memberid . '\')) ' .
                 'AND (A.grouponly = 0 OR A.grouponly IN (SELECT group_id FROM ' .
                 $wpdb->prefix . 'mr_group_member WHERE member_id = \'' . $userdata->mr_memberid . '\') )';
    }
    $sql = 'SELECT A.*, B.firstname, B.lastname, C.title AS clubname, D.title AS groupname FROM ' .
           $wpdb->prefix . 'mr_file A LEFT JOIN ' . $wpdb->prefix .
           'mr_member B ON A.uploader = B.id LEFT JOIN ' . $wpdb->prefix .
           'mr_club C ON A.clubonly = C.id LEFT JOIN ' . $wpdb->prefix .
           'mr_group D ON A.grouponly = D.id WHERE A.visible = 1 ' . $where . ' ORDER BY A.basename ASC';

    // TODO: Should it be visualised that when A.visible is not 1 ?

    //echo '<div class="error"><p>' . $sql . '</p></div>';

    echo '<div class="wrap">';

    $files = $wpdb->get_results( $sql, ARRAY_A );
    ?>
    <h2><?php echo __( 'Files for members', 'member-register' ); ?></h2>
    <table class="wp-list-table mr-table widefat sorter">
        <caption>
            <label><input type="text" id="tablesearch"/></label>
            <p></p>
        </caption>
        <thead>
        <tr>
            <th data-sort="string"><?php echo __( 'Base name', 'member-register' ); ?></th>
            <th data-sort="string"><?php echo __( 'Directory', 'member-register' ); ?></th>
            <th data-sort="float"><?php echo __( 'Size', 'member-register' ); ?> (KB)</th>
            <th data-sort="int" class="sorting-desc"><?php echo __( 'Uploaded', 'member-register' ); ?></th>
            <th data-sort="string"><?php echo __( 'Uploader', 'member-register' ); ?></th>
            <?php
            if ( mr_has_permission( MR_ACCESS_FILES_MANAGE ) ) {
                echo '<th data-sort="string">' . __( 'Restrictions', 'member-register' ) . '</th>';
                echo '<th>' . __( 'Remove', 'member-register' ) . '</th>';
            }
            ?>
        </tr>
        </thead>
        <tbody>

        <?php
        $out = '';
        foreach ( $files as $file ) {
            $path   = realpath( $mr_file_base_directory . '/' . $file['directory'] . '/' . $file['id'] . '_' . $file['basename'] );
            $exists = file_exists( $path );

            $out .= '<tr id="user_' . $file['id'] . '"' . ( $exists ? '' : ' class="inactive"' ) . '>';
            $out .= '<td';
            if ( ! $exists ) {
                $out .= ' title="' . __( 'File not found', 'member-register' ) . '">' . $file['basename'];
            } else {
                $a = admin_url( 'admin.php?page=member-files' ) . '&amp;download=' . $file['id'];
                if ( $file['directory'] != '' ) {
                    $a .= urlencode( '/' . $file['directory'] );
                }
                $a .= urlencode( '/' . $file['basename'] );

                $out .= '><a href="' . $a . '" title="' . __( 'Download', 'member-register' ) . ' ' .
                        $file['basename'] . '">' . $file['basename'] . '</a>';
            }
            $out .= '</td>';
            $out .= '<td>' . $file['directory'] . '</td>';
            $out .= '<td data-sort-value="' . $file['bytesize'] . '">' . round( $file['bytesize'] / 1024 ) . '</td>';
            $out .= '<td data-sort-value="' . $file['uploaded'] . '">' . date( $mr_date_format, $file['uploaded'] ) . '</td>';
            $out .= '<td data-sort-value="' . $file['firstname'] . ' ' . $file['lastname'] . '">';
            if ( mr_has_permission( MR_ACCESS_MEMBERS_VIEW ) ) {
                $out .= '<a href="' . admin_url( 'admin.php?page=member-register-control' ) .
                        '&amp;memberid=' . $file['uploader'] . '" title="' . $file['firstname'] .
                        ' ' . $file['lastname'] . '">' . $file['firstname'] . ' ' . $file['lastname'] . '</a>';
            } else {
                $out .= $file['firstname'] . ' ' . $file['lastname'];
            }
            $out .= '</td>';
            if ( mr_has_permission( MR_ACCESS_FILES_MANAGE ) ) {
                $out .= '<td>';
                $restrictions = [];
                if ( $file['clubonly'] != 0 ) {
                    $restrictions[] = __( 'Only club', 'member-register' ) . ': ' . $file['clubname'];
                }
                if ( $file['mingrade'] != '' ) {
                    $restrictions[] = __( 'Lowest grade', 'member-register' ) . ': ' . $mr_grade_values[ $file['mingrade'] ];
                }
                if ( $file['artonly'] != '' ) {
                    $restrictions[] = __( 'Only martial art', 'member-register' ) . ': ' . $mr_martial_arts[ $file['artonly'] ];
                }
                if ( $file['grouponly'] != 0 ) {
                    $restrictions[] = __( 'Only group', 'member-register' ) . ': ' . $file['groupname'];
                }
                $out .= implode( '<br/>', $restrictions );
                $out .= '</td>';

                $out .= '<td>';
                $out .= '<a class="dashicons dashicons-dismiss" rel="remove" href="' . admin_url( 'admin.php?page=member-files' ) .
                        '&amp;remove-file=' . $file['id'] . '" title="' . __( 'Remove file', 'member-register' ) . ': ' .
                        $file['directory'] . '/' . $file['basename'] . '">&nbsp;</a>';
                $out .= '</td>';
            }
            $out .= '</tr>';
        }
        echo $out;
        ?>
        </tbody>
    </table>
    <?php
    echo '</div>';
}

function mr_files_new() {
    if ( ! current_user_can( 'read' ) || ! mr_has_permission( MR_ACCESS_FILES_MANAGE ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'member-register' ) );
    }

    global $wpdb;
    global $userdata;
    global $mr_grade_values;
    global $mr_martial_arts;
    global $mr_file_base_directory;

    // Check for possible insert
    $hidden_field_name = 'mr_submit_hidden_file';
    if ( isset( $_POST[ $hidden_field_name ] ) && $_POST[ $hidden_field_name ] == 'Y' && isset( $_FILES['uploadfile'] ) ) {
        if ( isset( $_FILES['uploadfile']['error'] ) && $_FILES['uploadfile']['error'] != UPLOAD_ERR_OK ) {
            echo '<div class="error"><p>' . file_upload_error_message( $_FILES['uploadfile']['error'] ) . '</p></div>';
        } else {
            $dir       = isset( $_POST['directory'] ) ? mr_urize( $_POST['directory'] ) : '';
            $mingrade  = ( isset( $_POST['grade'] ) && $_POST['grade'] != '' && array_key_exists( $_POST['grade'], $mr_grade_values ) ) ? $_POST['grade'] : '';
            $clubonly  = ( isset( $_POST['club'] ) && is_numeric( $_POST['club'] ) ) ? $_POST['club'] : 0;
            $artonly   = ( isset( $_POST['art'] ) && $_POST['art'] != '' && array_key_exists( $_POST['art'], $mr_martial_arts ) ) ? $_POST['art'] : '';
            $grouponly = isset( $_POST['group'] ) && is_numeric( $_POST['group'] ) ? intval( $_POST['group'] ) : '';

            if ( mr_insert_new_file( $_FILES['uploadfile'], $dir, $mingrade, $clubonly, $artonly, $grouponly ) ) {
                echo '<div class="updated"><p>';
                echo '<strong>' . __( 'New file added.', 'member-register' ) . ' <em>' . $dir . '/' . $_FILES['uploadfile']['name'] . '</em></strong>';
                echo '</p></div>';
            } else {
                echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
            }
        }
    }
    ?>
    <div class="wrap">
        <h2><?php echo __( 'Add new file', 'member-register' ); ?></h2>

        <form name="form1" method="post" action="<?php echo admin_url( 'admin.php?page=member-files-new' ); ?>"
              enctype="multipart/form-data" autocomplete="on">
            <datalist id="directories">
                <?php
                $dirs = glob( $mr_file_base_directory . '/*', GLOB_ONLYDIR );
                foreach ( $dirs as $dir ) {
                    $base = basename( $dir );
                    if ( $base != '_removed' ) {
                        echo '<option value="' . $base . '"/>';
                    }
                }
                ?>
            </datalist>
            <input type="hidden" name="mr_submit_hidden_file" value="Y"/>
            <table class="form-table mr-table" id="mrform">
                <tr class="form-field">
                    <th><?php echo __( 'Choose file', 'member-register' ); ?><span
                            class="description">(<?php echo __( 'max 10 MB', 'member-register' ); ?>)</span></th>
                    <td><input type="file" name="uploadfile" required value=""/></td>
                </tr>
                <tr class="form-field">
                    <th><?php echo __( 'Folder', 'member-register' ); ?><span
                            class="description">(<?php echo __( 'single word, no spaces', 'member-register' ); ?>
                            )</span></th>
                    <td><input type="text" name="directory" value="" list="directories"/></td>
                </tr>
                <tr class="form-field">
                    <th><?php echo __( 'Club', 'member-register' ); ?><span
                            class="description">(<?php echo __( 'limited to the members of the given club', 'member-register' ); ?>
                            )</span></th>
                    <td>
                        <select name="club" data-placeholder="<?php echo __( 'Select a Club', 'member-register' ); ?>">
                            <option value=""></option>
                            <?php
                            $clubs = mr_get_list( 'club', 'visible = 1', '', 'title ASC' );
                            foreach ( $clubs as $club ) {
                                echo '<option value="' . $club['id'] . '">' . $club['title'] . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr class="form-field">
                    <th><?php echo __( 'Grade', 'member-register' ); ?><span
                            class="description">(<?php echo __( 'limited to members having at least the grade', 'member-register' ); ?>
                            )</span></th>
                    <td><select name="grade" data-placeholder="<?php echo __( 'Lowest grade', 'member-register' ); ?>">
                            <option value=""></option>
                            <?php
                            foreach ( $mr_grade_values as $key => $val ) {
                                echo '<option value="' . $key . '">' . $val . '</option>';
                            }
                            ?>
                        </select></td>
                </tr>
                <tr class="form-field">
                    <th><?php echo __( 'Martial art', 'member-register' ); ?><span
                            class="description">(<?php echo __( 'limit only to member who are registered to the given martial art', 'member-register' ); ?>
                            )</span></th>
                    <td><select name="art" data-placeholder="<?php echo __( 'Martial art', 'member-register' ); ?>">
                            <option value=""></option>
                            <?php
                            foreach ( $mr_martial_arts as $key => $val ) {
                                echo '<option value="' . $key . '">' . $val . '</option>';
                            }
                            ?>
                        </select></td>
                </tr>
                <tr class="form-field">
                    <th><?php echo __( 'Group', 'member-register' ); ?><span
                            class="description">(<?php echo __( 'limit to members belonging to a group', 'member-register' ); ?>
                            )</span></th>
                    <td><select name="group" data-placeholder="<?php echo __( 'Choose group', 'member-register' ); ?>">
                            <option value=""></option>
                            <?php
                            $groups = mr_get_list( 'group', 'visible = 1', '', 'title ASC' );
                            foreach ( $groups as $group ) {
                                echo '<option value="' . $group['id'] . '">' . $group['title'] . '</option>';
                            }
                            ?>
                        </select></td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="Submit" class="button-primary"
                       value="<?php esc_attr_e( 'Save Changes' ) ?>"/>
            </p>

        </form>
    </div>

<?php
}

function mr_insert_new_file(
    $filesdata, $dir = '', $mingrade = '',
    $clubonly = 0, $artonly = '', $grouponly = ''
) {
    global $wpdb;
    global $userdata;
    global $mr_file_base_directory;

    // Should not bewgin with _ nor .
    $dir   = mr_urize( $dir );
    $first = substr( $dir, 0, 1 );
    if ( $first == '_' || $first == '.' ) {
        $dir = substr( $dir, 1 );
    }

    $values = [
        'basename'  => mr_urize( basename( $filesdata['name'] ) ),
        'bytesize'  => $filesdata['size'],
        'directory' => $dir,
        'uploader'  => $userdata->mr_memberid,
        'uploaded'  => time(),
        'mingrade'  => $mingrade, // if not empty, checked
        'clubonly'  => $clubonly, // if not zero, checked
        'artonly'   => $artonly, // if not empty, checked
        'grouponly' => $grouponly, // if not empty, checked
        'visible'   => 1
    ];

    umask( 0000 );

    if ( ! file_exists( $mr_file_base_directory ) ) {
        mkdir( $mr_file_base_directory, 0775 );
    }

    $target = $mr_file_base_directory . '/' . $dir;

    if ( ! file_exists( $target ) ) {
        mkdir( $target, 0775 );
    }

    $insert = $wpdb->insert(
        $wpdb->prefix . 'mr_file',
        $values,
        [
            '%s', // basename
            '%d', // bytesize
            '%s', // directory
            '%d', // uploader id
            '%d', // uploaded time
            '%s', // mingrade
            '%d', // clubonly
            '%s', // artonly
            '%d', // grouponly
            '%d' // visible
        ]
    );

    if ( $insert !== false ) {
        $target = $target . '/' . $wpdb->insert_id . '_' . $values['basename'];

        if ( move_uploaded_file( $filesdata['tmp_name'], $target ) ) {
            chmod( $target, 0775 );
        } else {
            return false;
        }
    }

    return $insert;
}

/**
 * @param int $code
 *
 * @see http://www.php.net/manual/en/features.file-upload.errors.php
 * @return string|void
 */
function file_upload_error_message( $code ) {
    $message = __( 'Unknown upload error', 'member-register' );
    switch ( $code ) {
        case UPLOAD_ERR_INI_SIZE:
            $message = __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini', 'member-register' );
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $message = __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', 'member-register' );
            break;
        case UPLOAD_ERR_PARTIAL:
            $message = __( 'The uploaded file was only partially uploaded', 'member-register' );
            break;
        case UPLOAD_ERR_NO_FILE:
            $message = __( 'No file was uploaded', 'member-register' );
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $message = __( 'Missing a temporary folder', 'member-register' );
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $message = __( 'Failed to write file to disk', 'member-register' );
            break;
        case UPLOAD_ERR_EXTENSION:
            $message = __( 'File upload stopped by extension', 'member-register' );
            break;
    }

    return $message;
}
