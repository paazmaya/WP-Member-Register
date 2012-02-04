<?php
/**
 * Plugin Name: Member Register
 */

/**
 * Check for permission for doing certain things.
 * @param $access int Access right that is required
 * @param $rights int Access rights that the user has, if any
 */
function mr_has_permission($access, $rights = 0)
{
	global $userdata;
	
	if ($rights == 0)
	{
		if (!isset($userdata->mr_access))
		{
			return false;
		}
		$rights = $userdata->mr_access;
	}
	
	if ($access & $rights)
	{
		return true;
	}
	else
	{
		return false;
	}
}

/**
 * Show all rights that the given access has
 */
function list_user_rights($rights)
{
	global $mr_access_type;
	
	echo '<ul>';	
	foreach ($mr_access_type as $key => $val)
	{
		if (mr_has_permission($key, $rights))
		{
			echo '<li>' . $val . '</li>';
		}
	}
	echo '</ul>';
}

function mr_show_access_values()
{
	global $mr_access_type;
	echo '<p>' . __('Alla lyhyesti selostettuna kunkin käyttäjätason (access) oikeudet') . '. ' .
		__('Pyynnöstä näitä voidaan lisätä tai vähentää.') . '</p>';
	echo '<ul>';
	foreach ($mr_access_type as $k => $v)
	{
		echo '<li title="' . $v . ' (' . $k . ')">[' . $k . '] ' . $v . '</li>';
	}
	echo '</ul>';
}


/**
 * Get a set of items from the given table, where should be like something.
 */
function mr_get_list($table, $where = '', $shouldbe = '', $order = '1 ASC')
{
	global $wpdb;
	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'mr_' . $table;
	if (isset($where) && $where != '')
	{
		$sql .= ' WHERE ' . $where . ' LIKE \'%' . $shouldbe . '%\'';
	}
	$sql .= ' ORDER BY ' . $order;

	return $wpdb->get_results($sql, ARRAY_A);
}


/**
 * Testing purposes....
 */
function print_access()
{
	global $userdata;
	global $mr_access_type;
	
	echo '<p>';
	
	foreach ($mr_access_type as $key => $val)
	{
		echo 'Key: ' . $key . ', in binary: ' . decbin($key) . ', val: ' . $val . '<br />';
	}
	
	echo 'MR_ACCESS_OWN_INFO: ' . MR_ACCESS_OWN_INFO . '<br />';
	echo 'MR_ACCESS_FILES_VIEW: ' . MR_ACCESS_FILES_VIEW . '<br />';
	echo 'MR_ACCESS_CONVERSATION: ' . MR_ACCESS_CONVERSATION . '<br />';
	echo 'MR_ACCESS_FORUM_CREATE: ' . MR_ACCESS_FORUM_CREATE . '<br />';
	echo 'MR_ACCESS_FORUM_DELETE: ' . MR_ACCESS_FORUM_DELETE . '<br />';
	echo 'MR_ACCESS_MEMBERS_VIEW: ' . MR_ACCESS_MEMBERS_VIEW . '<br />';
	echo 'MR_ACCESS_MEMBERS_EDIT: ' . MR_ACCESS_MEMBERS_EDIT . '<br />';
	echo 'MR_ACCESS_GRADE_MANAGE: ' . MR_ACCESS_GRADE_MANAGE . '<br />';
	echo 'MR_ACCESS_PAYMENT_MANAGE: ' . MR_ACCESS_PAYMENT_MANAGE . '<br />';
	echo 'MR_ACCESS_CLUB_MANAGE: ' . MR_ACCESS_CLUB_MANAGE . '<br />';
	echo 'MR_ACCESS_FILES_MANAGE: ' . MR_ACCESS_FILES_MANAGE . '<br />';
	
	echo '<br />You have: ' . decbin($userdata->mr_access) . ' / ' . $userdata->mr_access;
	echo '<br />Full rights would be: ' . bindec(11111111111);
	
	echo '</p>';
}






function mr_htmlent($str)
{
	return htmlentities(trim($str), ENT_QUOTES, 'UTF-8');
}

function mr_htmldec($str)
{
	return html_entity_decode(trim($str), ENT_QUOTES, 'UTF-8');
}

function mr_urize($str)
{
	$str = mb_strtolower($str, 'UTF-8');
	$str = mr_htmldec($str);
	$str = str_replace(array(' ', ',', '@', '$', '/', '&', '!', '=', '%'), '-', $str);
	$str = str_replace(array('--', '---'), '-', $str);
	return $str;
}
