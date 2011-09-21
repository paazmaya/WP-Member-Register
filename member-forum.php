<?php








function mr_forum_list()
{
	if (!current_user_can('read'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	echo '<div class="wrap">';

	echo '<h2>Keskustelu</h2>';
	echo '<p>Alla lista aktiivista keskusteluista</p>';
	echo '</div>';
}


