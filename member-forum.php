<?php
/**
 Plugin Name: Member Register
 * Forum related functions
*/


/**
 * This is the only function hooked to display a page
 */
function mr_forum_list()
{
	if (!current_user_can('read'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	global $wpdb;
	global $userdata;
	
	echo '<div class="wrap">';
	
	if (isset($_GET['topic']) && is_numeric($_GET['topic']))
	{
		echo '<h2>Keskustelua aiheesta...</h2>';
		
		// Check for possible insert	
		$hidden_field_name = 'mr_submit_hidden_post';
		if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' )
		{
			
			if (mr_insert_new_post($_POST))
			{
				?>
				<div class="updated"><p>
					<strong>Uusi viesti keskusteluun lisätty</strong>
				</p></div>
				<?php
			}
			else
			{
				echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
			}
		}
	
		mr_show_info_topic($_GET['topic'], $userdata->mr_access);
		
		// New post form to the given topic
		echo '<h3>Lisää viesti</h3>';
		mr_show_form_post($_GET['topic']);
		echo '<hr />';
		
		mr_show_posts_for_topic($_GET['topic']);
	}
	else
	{
		echo '<h2>Keskustelu</h2>';
		echo '<p>Alempana lista aktiivista keskustelun aiheista</p>';
		
		// New topic form
		echo '<h3>Luo uusi keskustelun aihe</h3>';
		
		// Check for possible insert	
		$hidden_field_name = 'mr_submit_hidden_topic';
		if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' )
		{
			if (mr_insert_new_topic($_POST))
			{
				?>
				<div class="updated"><p>
					<strong>Uusi aihe lisätty. Nyt voit aloittaa sen piirissä keskustelun.</strong>
				</p></div>
				<?php
			}
			else
			{
				echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
			}
		}
		else if (isset($_GET['remove-topic']) && is_numeric($_GET['remove-topic']) && $userdata->mr_access >= 5)
		{
			// In reality just archive the topic
			$sql = 'UPDATE ' . $wpdb->prefix . 'mr_forum_topic SET visible = 1 WHERE id = \'' . intval($_GET['remove-topic']) . '\'';
			if ($wpdb->query($sql))
			{
				?>
				<div class="updated"><p>
					<strong>Valittu aihe poistettu.</strong>
				</p></div>
				<?php
			}
			else
			{
				echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
			}
		}
		
		mr_show_form_topic();
		echo '<hr />';
		echo '<h3>Käynnissä olevat keskustelun aiheet</h3>';
		
		mr_show_list_topics($userdata->mr_access);
	}
	echo '</div>';
}


function mr_show_info_topic($topic, $access)
{
	global $wpdb;
	
	$items = array('id', 'title', 'member', 'access', 'created');
	$sql = 'SELECT A.*, COUNT(B.id) AS total, MAX(B.created) AS lastpost, D.firstname, D.lastname, D.id AS memberid FROM ' .
		$wpdb->prefix . 'mr_forum_topic A LEFT JOIN ' .
		$wpdb->prefix . 'mr_forum_post B ON A.id = B.topic LEFT JOIN ' .
		$wpdb->prefix . 'mr_member D ON D.id = ' .
		'(SELECT C.member FROM wp_mr_forum_post C WHERE A.id = C.topic ORDER BY C.created DESC LIMIT 1)' .
		' WHERE A.access <= ' . intval($access) . ' AND A.id = ' . intval($topic) . ' AND A.visible = 1' .
		' GROUP BY A.id ORDER BY lastpost DESC LIMIT 1';
	
	//echo '<div class="error"><p>' . $sql . '</p></div>';
	
	$res = $wpdb->get_row($sql, ARRAY_A);
	
	echo '<h3>' . $res['title'] . '</h3>';
	echo '<p>Tämän aiheen loi ' .  $res['firstname'] . ' ' . $res['lastname'] .
		', päivämäärällä ' . date('Y-m-d', $res['created']) . '.<br />';
	echo 'Viestejä yhteensä ' . $res['total'] . ', joista viimeisin ' .
		date('Y-m-d H:i:s', $res['lastpost']) . '</p>';
}


function mr_show_list_topics($access)
{
	global $wpdb;
	global $userdata;
	
	// Remember that the "created" is a unix timestamp
	// id, title, member, access, created
	$items = array('id', 'title', 'member', 'access', 'created');
	$sql = 'SELECT A.*, COUNT(B.id) AS total, MAX(B.created) AS lastpost, D.firstname, D.lastname, D.id AS memberid FROM ' .
		$wpdb->prefix . 'mr_forum_topic A LEFT JOIN ' .
		$wpdb->prefix . 'mr_forum_post B ON A.id = B.topic LEFT JOIN ' .
		$wpdb->prefix . 'mr_member D ON D.id = ' .
		'(SELECT C.member FROM wp_mr_forum_post C WHERE A.id = C.topic ORDER BY C.created DESC LIMIT 1)' .
		' WHERE A.access <= ' . intval($access) . ' AND A.visible = 1 GROUP BY A.id ORDER BY lastpost DESC';
		
	//echo '<div class="error"><p>' . $sql . '</p></div>';
	$res = $wpdb->get_results($sql, ARRAY_A);

	?>
	<table class="wp-list-table widefat tablesorter">
	<thead>
	<tr>
		<th>Aihe
		<?php
		if ($userdata->mr_access >= 5)
		{
			echo ' (poista)';
		}
		?>
		</th>
		<th class="w20em headerSortUp">Viimeisin viesti</th>
		<th class="w20em">Viimeisimmän viestin kirjoitti</th>
		<th>Viestejä</th>
	</tr>
	</thead>
	<tbody>
	<?php
	if (count($res) > 0)
	{
		foreach($res as $topic)
		{
			echo '<tr id="topic_' . $topic['id'] . '">';
			echo '<td><a href="' . admin_url('admin.php?page=member-forum') .
				'&topic=' . $topic['id'] . '" title="' . $topic['title'] .
				'">' . $topic['title'] . '</a>';
			if ($userdata->mr_access >= 5)
			{
				echo ' <a href="' . admin_url('admin.php?page=member-forum') .
				'&remove-topic=' . $topic['id'] . '" title="' . $topic['title'] .
				'">(X)</a>';
			}
			echo '</td>';
			echo '<td>';
			if ($topic['lastpost'] != 0 && $topic['lastpost'] != null)
			{
				echo date('Y-m-d H:i:s', $topic['lastpost']);
			}
			echo '</td>';
			echo '<td>' . $topic['firstname'] . ' ' . $topic['lastname'] . '</td>';
			echo '<td>' . $topic['total'] . '</td>';
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
	global $wpdb;
	global $userdata;
	
	// id, topic, content, member, created

	$items = array('id', 'topic', 'content', 'member', 'created');
	
	$sql = 'SELECT A.*, B.firstname, B.lastname, B.id AS memberid FROM ' . 
		$wpdb->prefix . 'mr_forum_post A LEFT JOIN ' . 
		$wpdb->prefix . 'mr_member B ON A.member = B.id WHERE A.topic = ' .
		intval($topic) . ' AND A.visible = 1 ORDER BY A.created DESC';
	echo '<div class="error"><p>' . $sql . '</p></div>';
	$res = $wpdb->get_results($sql, ARRAY_A);
	
	?>
	<table class="wp-list-table widefat tablesorter">
	<thead>
	<tr>
		<th class="w20em headerSortUp">Aika</th>
		<th class="w20em">Jäsen</th>
		<th>Viesti</th>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach($res as $topic)
	{
		echo '<tr id="post_' . $topic['id'] . '">';
		echo '<td>' . date('Y-m-d H:i:s', $topic['created']) . '</td>';
		echo '<td>' . $topic['firstname'] . ' ' . $topic['lastname'] . '</td>';
		echo '<td>' . mr_htmldec($topic['content']) . '</td>';
		echo '</tr>';
	}
	?>
	</tbody>
	</table>
	<?php

}

function mr_insert_new_topic($postdata)
{
	global $wpdb;
	global $userdata;

	$values = array();
	$required = array('title', 'access');

	foreach($postdata as $k => $v)
	{
		if (in_array($k, $required))
		{
			// sanitize
			$values[] = "'" . mr_htmlent($v) . "'";
		}
	}
	$values[] = "'" . $userdata->mr_memberid . "'";
	$values[] = "'" . time() . "'";
	

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_forum_topic (title, access, member, created) VALUES(' 
		. implode(', ', $values) . ')';

	echo '<div class="error"><p>' . $sql . '</p></div>';

	return $wpdb->query($sql);
}

function mr_insert_new_post($postdata)
{
	global $wpdb;
	global $userdata;

	$values = array(
		"'" . mr_htmlent(nl2br($postdata['content'], true)) . "'",
		"'" . intval($postdata['topic']) . "'",
		$values[] = "'" . $userdata->mr_memberid . "'",
		$values[] = "'" . time() . "'"
	);	

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_forum_post (content, topic, member, created) VALUES(' 
		. implode(', ', $values) . ')';

	echo '<div class="error"><p>' . $sql . '</p></div>';

	return $wpdb->query($sql);
}


function mr_show_form_topic()
{
	global $mr_access_type;
	global $userdata;
	
	$action = admin_url('admin.php?page=member-forum');
	?>
	<form name="form1" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data">
		<input type="hidden" name="mr_submit_hidden_topic" value="Y" />
		<table class="form-table" id="createuser">
			<tr class="form-field">
				<th>Aihe <span class="description">(otsikko)</span></th>
				<td><input type="text" name="title" value="" /></td>
			</tr>
			<tr class="form-field">
				<th>Lukuoikeus <span class="description">(mistä tasosta alkaen lukuoikeus myönnetään)</span></th>
				<td>
					<select name="access">
					<?php
					for ($i = 1; $i <= $userdata->mr_access; $i++)
					{
						echo '<option value="' . $i . '">' . $mr_access_type[$i] . ' (' . $i . ')</option>';
					}
					?>
					</select>
				</td>
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
	$action = admin_url('admin.php?page=member-forum') . '&topic=' . $topic;
	?>
	<form name="form1" method="post" action="<?php echo $action; ?>" enctype="multipart/form-data">
		<input type="hidden" name="mr_submit_hidden_post" value="Y" />
		<input type="hidden" name="topic" value="<?php echo intval($topic); ?>" />
		<table class="form-table" id="createuser">
			<tr class="form-field">
				<th>Viesti <span class="description">(vapaasti)</span></th>
				<td><textarea name="content"></textarea></td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Publish') ?>" />
		</p>

	</form>
	<?php
}







