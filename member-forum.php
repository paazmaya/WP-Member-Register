<?php
/**
 * Part of Member Register
 * License: MIT (http://opensource.org/licenses/MIT)
 *
 * Forum related functions
 */


/**
 * This is the only function hooked to display a page
 */
function mr_forum_list()
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_CONVERSATION))
	{
		wp_die( __('You do not have sufficient permissions to access this page.', 'member-register') );
	}

	global $wpdb;
	global $userdata;

	echo '<div class="wrap">';

	if (isset($_GET['topic']) && is_numeric($_GET['topic']))
	{
		echo '<h2>' . __('Keskustelua aiheesta...', 'member-register') . '</h2>';

		// Check for possible insert
		$hidden_field_name = 'mr_submit_hidden_post';
		if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' && mr_has_permission(MR_ACCESS_CONVERSATION))
		{

			if (mr_insert_new_post($_POST))
			{
				echo '<div class="updated"><p>';
				echo '<strong>' . __('Uusi viesti keskusteluun lisätty', 'member-register') . '</strong>';
				echo '</p></div>';
			}
			else
			{
				echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
			}
		}
		else if (isset($_GET['remove-post']) && is_numeric($_GET['remove-post']) && mr_has_permission(MR_ACCESS_FORUM_DELETE))
		{
			// In reality just archive the post
            $update = mr_forum_remove_post($_GET['remove-post']);

			if ($update !== false)
			{
				echo '<div class="updated"><p>';
				echo '<strong>' . __('Valittu viesti poistettu.', 'member-register') . '</strong>';
				echo '</p></div>';
			}
			else
			{
				echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
			}
		}

		mr_show_info_topic($_GET['topic']);

		// New post form to the given topic
		if (mr_has_permission(MR_ACCESS_CONVERSATION))
		{
			echo '<h3>' . __('Lisää viesti', 'member-register') . '</h3>';
			mr_show_form_post($_GET['topic']);
			echo '<hr />';
		}

		mr_show_posts_for_topic($_GET['topic']);
	}
	else
	{
		echo '<h2>' . __('Discussion Of The', 'member-register') . '</h2>';
		echo '<p>' . __('Below is a list of active topics of discussion', 'member-register') . '</p>';

		// Check for possible insert
		$hidden_field_name = 'mr_submit_hidden_topic';
		if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' && mr_has_permission(MR_ACCESS_FORUM_CREATE))
		{
			if (mr_insert_new_topic($_POST))
			{
				echo '<div class="updated"><p>';
				echo '<strong>' . __('Uusi aihe lisätty. Nyt voit aloittaa sen piirissä keskustelun.', 'member-register') . '</strong>';
				echo '</p></div>';
			}
			else
			{
				echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
			}
		}
		else if (isset($_GET['remove-topic']) && is_numeric($_GET['remove-topic']) && mr_has_permission(MR_ACCESS_FORUM_DELETE))
		{
			// In reality just archive the topic
			$update = $wpdb->update(
				$wpdb->prefix . 'mr_forum_topic',
				array(
					'visible' => 0
				),
				array(
					'id' => $_GET['remove-topic']
				),
				array(
					'%d'
				),
				array(
					'%d'
				)
			);

			if ($update)
			{
				echo '<div class="updated"><p>';
				echo '<strong>' . __('Valittu aihe poistettu.', 'member-register') . '</strong>';
				echo '</p></div>';
			}
			else
			{
				echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
			}
		}

		// New topic form
		if (mr_has_permission(MR_ACCESS_FORUM_CREATE))
		{
			echo '<h3>' . __('Create a new discussion topic', 'member-register') . '</h3>';
			mr_show_form_topic();
			echo '<hr />';
		}
		echo '<h3>' . __('Käynnissä olevat keskustelun aiheet', 'member-register') . '</h3>';

		mr_show_list_topics($userdata->mr_access);
	}
	echo '</div>';
}


function mr_show_info_topic($topic)
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_CONVERSATION))
	{
		wp_die( __('You do not have sufficient permissions to access this page.', 'member-register') );
	}

	global $wpdb;
	global $mr_date_format;

	$items = array('id', 'title', 'member', 'created');
	$sql = 'SELECT A.*, COUNT(B.id) AS total, MAX(B.created) AS lastpost, C.firstname, C.lastname, C.id AS memberid FROM ' .
		$wpdb->prefix . 'mr_forum_topic A LEFT JOIN ' .
		$wpdb->prefix . 'mr_forum_post B ON A.id = B.topic AND B.visible = 1 LEFT JOIN ' .
		$wpdb->prefix . 'mr_member C ON C.id = A.member WHERE' .
		' A.id = ' . intval($topic) . ' AND A.visible = 1' .
		' GROUP BY A.id ORDER BY lastpost DESC LIMIT 1';

	//echo '<div class="error"><p>' . $sql . '</p></div>';

	$res = $wpdb->get_row($sql, ARRAY_A);

	echo '<h3>' . $res['title'] . '</h3>';
	echo '<p>' . __('Tämän aiheen loi', 'member-register') . ' ' .  $res['firstname'] . ' ' . $res['lastname'] .
		', ' . __('päivämäärällä', 'member-register') . ' ' . date('Y-m-d', $res['created']) . '.<br />';
	echo __('Viestejä yhteensä', 'member-register') . ' ' . $res['total'];
	if ($res['total'] > 0)
	{
		echo ', ' . __('the most recent of', 'member-register') . ' ' . date($mr_date_format, $res['lastpost']);
	}
	echo '.</p>';
}


function mr_show_list_topics()
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_CONVERSATION))
	{
		wp_die( __('You do not have sufficient permissions to access this page.', 'member-register') );
	}

	global $wpdb;
	global $userdata;
	global $mr_date_format;

	// Remember that the "created" is a unix timestamp
	// id, title, member, created
	$items = array('id', 'title', 'member', 'created');
	$sql = 'SELECT A.*, COUNT(B.id) AS total, MAX(B.created) AS lastpost, D.firstname, D.lastname, D.id AS memberid FROM ' .
		$wpdb->prefix . 'mr_forum_topic A LEFT JOIN ' .
		$wpdb->prefix . 'mr_forum_post B ON A.id = B.topic AND B.visible = 1 LEFT JOIN ' .
		$wpdb->prefix . 'mr_member D ON D.id = ' .
		'(SELECT C.member FROM wp_mr_forum_post C WHERE A.id = C.topic ORDER BY C.created DESC LIMIT 1)' .
		' WHERE A.visible = 1 GROUP BY A.id ORDER BY lastpost DESC';

	//echo '<div class="error"><p>' . $sql . '</p></div>';
	$res = $wpdb->get_results($sql, ARRAY_A);

	?>
	<table class="wp-list-table widefat sorter">
	<thead>
	<tr>
		<th data-sort="string"><?php echo __('The Topic', 'member-register'); ?></th>
		<th data-sort="int" class="sorting-desc"><?php echo __('The latest post', 'member-register'); ?></th>
		<th data-sort="string"><?php echo __('Viimeisimmän viestin kirjoitti', 'member-register'); ?></th>
		<th data-sort="int"><?php echo __('Viestejä', 'member-register'); ?></th>
		<?php
		if (mr_has_permission(MR_ACCESS_FORUM_DELETE))
		{
			echo '<th class="w4em" filter="false">' . __('Delete', 'member-register') . '</th>';
		}
		?>
	</tr>
	</thead>
	<tbody>
	<?php
	if (count($res) > 0)
	{
		foreach($res as $topic)
		{
			echo '<tr id="topic_' . $topic['id'] . '">';
			echo '<td data-sort-value="' . $topic['title'] .'"><a href="' . admin_url('admin.php?page=member-forum') .
				'&topic=' . $topic['id'] . '" title="' . $topic['title'] .
				'">' . $topic['title'] . '</a>';
			echo '</td>';
			echo '<td data-sort-value="' . $topic['lastpost'] .'">';
			if ($topic['lastpost'] != 0 && $topic['lastpost'] != null)
			{
				echo date($mr_date_format, $topic['lastpost']);
			}
			echo '</td>';
			echo '<td>' . $topic['firstname'] . ' ' . $topic['lastname'] . '</td>';
			echo '<td>' . $topic['total'] . '</td>';
			if (mr_has_permission(MR_ACCESS_FORUM_DELETE))
			{
				echo '<td><a class="dashicons dashicons-dismiss" rel="remove" href="' . admin_url('admin.php?page=member-forum') .
				'&amp;remove-topic=' . $topic['id'] . '" title="' . __('Poista tämä aihe, otsikolla', 'member-register') . ': ' .
				$topic['title'] . '">_</a></td>';
			}
			echo '</tr>';
		}
	}
	?>
	</tbody>
	</table>
	<?php
}


function mr_show_posts_for_topic($topic)
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_CONVERSATION))
	{
		wp_die( __('You do not have sufficient permissions to access this page.', 'member-register') );
	}

	global $wpdb;
	global $userdata;
	global $mr_date_format;

	$topic = intval($topic);
	// id, topic, content, member, created

	$items = array('id', 'topic', 'content', 'member', 'created');

	$sql = 'SELECT A.*, B.firstname, B.lastname, B.id AS memberid FROM ' .
		$wpdb->prefix . 'mr_forum_post A LEFT JOIN ' .
		$wpdb->prefix . 'mr_member B ON A.member = B.id WHERE A.topic = ' .
		$topic . ' AND A.visible = 1 ORDER BY A.created DESC';

	//echo '<div class="error"><p>' . $sql . '</p></div>';
	$res = $wpdb->get_results($sql, ARRAY_A);

	?>
	<table class="wp-list-table widefat sorter">
	<thead>
	<tr>
		<th data-sort="int" class="sorting-desc"><?php echo __('Time', 'member-register'); ?></th>
		<th data-sort="string"><?php echo __('Jäsen', 'member-register'); ?></th>
		<th data-sort="string"><?php echo __('A Message', 'member-register'); ?></th>
		<?php
		if (mr_has_permission(MR_ACCESS_FORUM_DELETE))
		{
			echo '<th class="w4em" filter="false">' . __('Delete', 'member-register') . '</th>';
		}
		?>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach($res as $post)
	{
		echo '<tr id="post_' . $post['id'] . '">';
		echo '<td data-sort-value="' . $post['created'] . '">' . date($mr_date_format, $post['created']) . '</td>';
		echo '<td>' . $post['firstname'] . ' ' . $post['lastname'] . '</td>';
		echo '<td>' . mr_htmldec($post['content']) . '</td>';
		if (mr_has_permission(MR_ACCESS_FORUM_DELETE))
		{
			echo '<td><a class="dashicons dashicons-dismiss" rel="remove" href="' . admin_url('admin.php?page=member-forum') . '&amp;topic=' . $topic .
				'&amp;remove-post=' . $post['id'] . '" title="' . __('Poista tämä viesti joka on kirjoitettu', 'member-register') . ' ' .
				date($mr_date_format, $post['created']) . '">_</a></td>';
		}
		echo '</tr>';
	}
	?>
	</tbody>
	</table>
	<?php

}














function mr_forum_remove_post($postId)
{
    global $wpdb;

    return $wpdb->update(
        $wpdb->prefix . 'mr_forum_post',
        array(
            'visible' => 0
        ),
        array(
            'id' => $postId
        ),
        array(
            '%d'
        ),
        array(
            '%d'
        )
    );
}


function mr_insert_new_topic($postdata)
{
	global $wpdb;
	global $userdata;

	return $wpdb->insert(
		$wpdb->prefix . 'mr_forum_topic',
		array(
			'title' => $postdata['title'],
			'member' => $userdata->mr_memberid,
			'created' => time()
		),
		array(
			'%s',
			'%d',
			'%d',
		)
	);
}

function mr_insert_new_post($postdata)
{
	global $wpdb;
	global $userdata;

	return $wpdb->insert(
		$wpdb->prefix . 'mr_forum_post',
		array(
			'content' => nl2br(strip_tags($postdata['content']), true),
			'topic' => $postdata['topic'],
			'member' => $userdata->mr_memberid,
			'created' => time()
		),
		array(
			'%s',
			'%d',
			'%d',
			'%d',
		)
	);
}


function mr_show_form_topic()
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_FORUM_CREATE))
	{
		wp_die( __('You do not have sufficient permissions to access this page.', 'member-register') );
	}

	global $mr_access_type;
	global $userdata;

	$action = admin_url('admin.php?page=member-forum');
	?>
	<form name="form1" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" autocomplete="on">
		<input type="hidden" name="mr_submit_hidden_topic" value="Y" />
		<table class="form-table" id="mrform">
			<tr class="form-field">
				<th><?php echo __('The Topic', 'member-register'); ?> <span class="description">(<?php echo __('the title of', 'member-register'); ?>)</span></th>
				<td><input type="text" name="title" class="required" required="required" value="" /></td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Publish') ?>" />
		</p>

	</form>
	<?php
}


function mr_show_form_post($topic)
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_CONVERSATION))
	{
		wp_die( __('You do not have sufficient permissions to access this page.', 'member-register') );
	}

	$action = admin_url('admin.php?page=member-forum') . '&topic=' . $topic;
	?>
	<form name="form1" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data" autocomplete="on">
		<input type="hidden" name="mr_submit_hidden_post" value="Y" />
		<input type="hidden" name="topic" value="<?php echo intval($topic); ?>" />
		<table class="form-table" id="mrform">
			<tr class="form-field">
				<th><?php echo __('A Message', 'member-register'); ?> <span class="description">(<?php echo __('feel free to', 'member-register'); ?>)</span></th>
				<td><textarea name="content" class="required" required="required"></textarea></td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Publish') ?>" />
		</p>

	</form>
	<?php
}



