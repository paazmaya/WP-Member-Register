<?php
/**
 Plugin Name: Member Register
 * Forum related functions
*/



function mr_forum_list()
{
	if (!current_user_can('read'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	echo '<div class="wrap">';
	
	// http://codex.wordpress.org/User:CharlesClarkson/Global_Variables
	echo '<pre>';
	print_r($userdata);
	echo '</pre>';
	
	if (isset($_GET['topic']) && is_numeric($_GET['topic']))
	{
		echo '<h2>Keskustelua aiheesta...</h2>';
	
		mr_show_info_topic($_GET['topic']);
		
		// New post form to the given topic
		echo '<h2>Lisää viesti</h2>';
		
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
				echo '<p>' . $wpdb->print_error() . '</p>';
			}
		}
		else 
		{
			mr_show_form_post($_GET['new-post']);
		}
		
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
				echo '<p>' . $wpdb->print_error() . '</p>';
			}
		}
		else 
		{
			mr_show_form_topic();
		}
		
		echo '<h3>Käynnissä olevat keskustelun aiheet</h3>';
		
		mr_show_list_topics('0');
	}
	echo '</div>';
}


function mr_show_info_topic($topic)
{
	global $wpdb;
	
	$items = array('id', 'title', 'member', 'access', 'created');
	
	$sql = 'SELECT A.*, MAX(B.created) AS lastpost, C.firstname, C.lastname, C.id AS memberid FROM ' .
		$wpdb->prefix . 'mr_forum_topic A LEFT JOIN ' .
		$wpdb->prefix . 'mr_forum_post B ON A.id = B.topic LEFT JOIN ' .
		$wpdb->prefix . 'mr_member C ON B.member = C.id WHERE A.access >= ' .
		intval($access) . ' GROUP BY B.topic WHERE A.id = ' . intval($topic) .
		' ORDER BY lastpost DESC LIMIT 1';
	echo '<p>' . $sql . '</p>';
	
	$res = $wpdb->get_row($sql, ARRAY_A);
	
	echo '<h3>' . $res['title'] . '</h3>';
	echo '<p>Tämän aiheen loi ' .  $res['firstname'] . ' ' . $res['lastname'] .
		', päivämäärällä ' . date('Y-m-d', $res['created']) . '.</p>';
}


function mr_show_list_topics($access)
{
	global $wpdb;
	
	// Remember that the "created" is a unix timestamp
	// id, title, member, access, created
	$items = array('id', 'title', 'member', 'access', 'created');
	$sql = 'SELECT A.*, MAX(B.created) AS lastpost, C.firstname, C.lastname, C.id AS memberid FROM ' .
		$wpdb->prefix . 'mr_forum_topic A LEFT JOIN ' .
		$wpdb->prefix . 'mr_forum_post B ON A.id = B.topic LEFT JOIN ' .
		$wpdb->prefix . 'mr_member C ON B.member = C.id WHERE A.access >= ' .
		intval($access) . ' GROUP BY B.topic ORDER BY lastpost DESC';
	echo '<p>' . $sql . '</p>';
	$res = $wpdb->get_results($sql, ARRAY_A);

	?>
	<table class="wp-list-table widefat fixed users">
	<thead>
	<tr>
		<th>Aihe</th>
		<th>Viimeisin viesti</th>
		<th>Viimeisimmän viestin kirjoitti</th>
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
				'">' . $topic['title'] . '</a></td>';
			echo '<td>' . date('Y-m-d h:i:s', $topic['lastpost']) . '</td>';
			echo '<td>' . $topic['firstname'] . ' ' . $topic['lastname'] . '</td>';
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
	
	// id, topic, content, member, created

	$items = array('id', 'topic', 'content', 'member', 'created');
	
	$sql = 'SELECT A.*, B.firstname, B.lastname, B.id AS memberid FROM ' . 
		$wpdb->prefix . 'mr_forum_post A LEFT JOIN ' . 
		$wpdb->prefix . 'mr_member B ON A.member = B.id WHERE A.topic = ' .
		intval($topic) . ' ORDER BY A.created DESC';
	echo '<p>' . $sql . '</p>';
	$res = $wpdb->get_results($sql, ARRAY_A);
	
	?>
	<table class="wp-list-table widefat fixed users">
	<thead>
	<tr>
		<th>Aika</th>
		<th>Jäsen</th>
		<th>Viesti</th>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach($res as $topic)
	{
		echo '<tr id="post_' . $topic['id'] . '">';
		echo '<td>' . date('Y-m-d h:i:s', $topic['created']) . '</td>';
		echo '<td>' . $topic['firstname'] . ' ' . $topic['lastname'] . '</td>';
		echo '<td>' . $topic['content'] . '</td>';
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
	$values[] = 0;
	$values[] = "'" . time() . "'";
	

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_forum_topic (title, access, member, created) VALUES(' 
		. implode(', ', $values) . ')';

	//echo $sql;

	return $wpdb->query($sql);
}

function mr_insert_new_post($postdata)
{
	global $wpdb;

	$values = array(
		"'" . mr_htmlent($postdata['content']) . "'",
		"'" . intval($postdata['topic']) . "'",
		$values[] = 0,
		$values[] = "'" . time() . "'"
	);	

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_forum_post (content, topic, member, created) VALUES(' 
		. implode(', ', $values) . ')';

	//echo $sql;

	return $wpdb->query($sql);
}


function mr_show_form_topic()
{
	global $mr_access_type;
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
					foreach ($mr_access_type as $k => $v)
					{
						echo '<option value="' . $k . '">' . $v . ' (' . $k . ')</option>';
					}
					?>
					</select>
				</td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
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
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
		</p>

	</form>
	<?php
}







