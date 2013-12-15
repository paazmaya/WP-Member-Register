<?php
/**
 * Part of Member Register
 * License: MIT (http://opensource.org/licenses/MIT)
 *
 * Payment related functions
 */




function mr_payment_new()
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_PAYMENT_MANAGE))
	{
		wp_die( __('You do not have sufficient permissions to access this page.', 'member-register'));
	}

	global $wpdb;


	// Check for possible insert
    $hidden_field_name = 'mr_submit_hidden_payment';
    if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y' )
	{
        if (mr_insert_new_payment($_POST))
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Uusi/uudet maksu(t) lisätty', 'member-register') . '</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
    }

    ?>
	<div class="wrap">
		<h2><?php echo __('Lisää uusi maksu, useammalle henkilölle jos tarve vaatii', 'member-register'); ?></h2>
		<p><?php echo __('Pääasia että rahaa tulee, sitä kun menee.', 'member-register'); ?></p>
		<p><?php echo __('Viitenumero on automaattisesti laskettu ja näkyy listauksessa kun maksu on luotu.', 'member-register'); ?></p>
		<?php
		$sql = 'SELECT CONCAT(lastname, ", ", firstname) AS name, id FROM ' . $wpdb->prefix . 'mr_member WHERE visible = 1 ORDER BY lastname ASC';
		$users = $wpdb->get_results($sql, ARRAY_A);
		mr_new_payment_form($users);
		?>
	</div>

	<?php

}



function mr_payment_list()
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_PAYMENT_MANAGE))
	{
		wp_die( __('You do not have sufficient permissions to access this page.', 'member-register'));
	}

	global $wpdb;

	if (isset($_POST['haspaid']) && is_numeric($_POST['haspaid']))
	{
		$today = date('Y-m-d');

		$update = $wpdb->update(
			$wpdb->prefix . 'mr_payment',
			array(
				'paidday' => $today
			),
			array(
				'id' => $_POST['haspaid']
			),
			array(
				'%s'
			),
			array(
				'%d'
			)
		);

		if ($update)
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Maksu merkitty maksetuksi tänään', 'member-register') . ', ' . $today . '</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
	}
	else if (isset($_GET['removepayment']) && is_numeric($_GET['removepayment']))
	{
		// Mark the given payment visible=0, so it can be recovered just in case...
		$id = intval($_GET['removepayment']);
		$update = $wpdb->update(
			$wpdb->prefix . 'mr_payment',
			array(
				'visible' => 0
			),
			array(
				'id' => $_GET['removepayment']
			),
			array(
				'%d'
			),
			array(
				'%d'
			)
		);

		if ($update !== false)
		{
			echo '<div class="updated"><p>';
			echo '<strong>' . __('Maksu poistettu', 'member-register') . ' (' . $id . ')</strong>';
			echo '</p></div>';
		}
		else
		{
			echo '<div class="error"><p>' . $wpdb->print_error() . '</p></div>';
		}
	}

	echo '<div class="wrap">';
	echo '<h2>' . __('Jäsenmaksut', 'member-register') . '</h2>';

	mr_show_payments_lists(null); // no specific member
	echo '</div>';
}




/**
 * List all payments for all members.
 */
function mr_show_payments_lists($memberid)
{
	?>
	<h3><?php echo __('Maksamattomat maksut', 'member-register'); ?></h3>
	<?php
	if (mr_has_permission(MR_ACCESS_PAYMENT_MANAGE))
	{
		echo' <p>' . __('Merkitse maksu maksetuksi vasemmalla olevalla "OK" painikkeella.', 'member-register') . '</p>';
	}

	mr_show_payments($memberid, true);
	?>
	<hr />
	<h3><?php echo __('Maksetut maksut', 'member-register'); ?></h3>
	<?php
	mr_show_payments($memberid, false);
}



/**
 * Show list of payments for a member,
 * for all, unpaid, paid ones.
 */
function mr_show_payments($memberid = null, $isUnpaidView = false)
{
	global $wpdb;
	global $userdata;

	$allowremove = true; // visible = 0
	$allowreview = true; // paid = 1
	// If no rights, only own info
	if (!mr_has_permission(MR_ACCESS_PAYMENT_MANAGE))
	{
		$memberid = $userdata->mr_memberid;
		$allowremove = false;
		$allowreview = false;
	}

	$where = '';
	if ($memberid != null && is_numeric($memberid))
	{
		$where .= 'AND A.member = \'' . $memberid . '\' ';
	}
	if ($isUnpaidView)
	{
		$where .= 'AND A.paidday = \'0000-00-00\' ';
	}
	else
	{
		$where .= 'AND A.paidday != \'0000-00-00\' ';
	}
	$sql = 'SELECT A.*, B.firstname, B.lastname, B.id AS memberid FROM ' . $wpdb->prefix .
		'mr_payment A LEFT JOIN ' . $wpdb->prefix .
		'mr_member B ON A.member = B.id WHERE A.visible = 1 ' .
		$where . 'ORDER BY A.deadline DESC';
	$res = $wpdb->get_results($sql, ARRAY_A);


	if (count($res) > 0)
	{
		// id member reference type amount deadline paidday validuntil visible
		?>
		<table class="wp-list-table widefat fixed pages tablesorter">
			<thead>
				<tr>
					<?php
					if ($isUnpaidView && $allowreview)
					{
						echo '<th filter="false">' . __('Maksettu?', 'member-register') . '</th>';
					}
					if ($memberid == null)
					{
						?>
						<th><?php echo __('Last name', 'member-register'); ?></th>
						<th><?php echo __('First name', 'member-register'); ?></th>
						<?php
					}
					?>
					<th><?php echo __('Tyyppi', 'member-register'); ?></th>
					<th class="w8em"><?php echo __('Summa (EUR)', 'member-register'); ?></th>
					<th class="w8em"><?php echo __('Viite', 'member-register'); ?></th>
					<th class="headerSortUp"><?php echo __('Eräpäivä', 'member-register'); ?></th>
					<?php
					if (!$isUnpaidView)
					{
						echo '<th>' . __('Maksu PVM', 'member-register') . '</th>';
					}
					?>
					<th><?php echo __('Voimassaolo', 'member-register'); ?></th>
					<?php
					if ($allowremove)
					{
						echo '<th class="w8em">' . __('Poista', 'member-register') . '</th>';
					}
					?>
				</tr>
			</thead>
		<tbody>
		<?php
		foreach($res as $payment)
		{
			echo '<tr id="payment_' . $payment['id'] . '">';
			if ($isUnpaidView && $allowreview)
			{
				echo '<td>';
				if ($payment['paidday'] == '0000-00-00')
				{
					echo '<form action="admin.php?page=member-payment-list" method="post" autocomplete="on">';
					echo '<input type="hidden" name="haspaid" value="' . $payment['id'] . '" />';
					echo '<input type="submit" value="OK" /></form>';
				}
				echo '</td>';
			}
			if ($memberid == null)
			{
				$url = '<a href="' . admin_url('admin.php?page=member-register-control') .
					'&memberid=' . $payment['memberid'] . '" title="' . $payment['firstname'] .
					' ' . $payment['lastname'] . '">';
				echo '<td>' . $url . $payment['lastname'] . '</a></td>';
				echo '<td>' . $url . $payment['firstname'] . '</a></td>';
			}
			echo '<td>' . $payment['type'] . '</td>';
			echo '<td>' . $payment['amount'] . '</td>';
			echo '<td>' . $payment['reference'] . '</td>';
			echo '<td>' . $payment['deadline'] . '</td>';
			if (!$isUnpaidView)
			{
				echo '<td>' . $payment['paidday'] . '</td>';
			}
			echo '<td>' . $payment['validuntil'] . '</td>';

			// set visible to 0, do not remove for real...
			if ($allowremove)
			{
				echo '<td><a rel="remove" href="' . admin_url('admin.php?page=member-payment-list') .
					'&amp;removepayment=' . $payment['id'] . '" title="' . __('Poista maksu viitteellä', 'member-register') .
					': ' . $payment['reference'] . '"><img src="' .
					plugins_url('/images/delete-1.png', __FILE__) . '" alt="Poista" /></a></td>';
			}
			echo '</tr>';
		}
		?>
		</tbody>
		</table>
		<?php
	}
	else
	{
		echo '<p>Ei löytynyt lainkaan ';
		if ($isUnpaidView)
		{
			echo 'maksamattomia';
		}
		else
		{
			echo 'maksettuja';
		}
		echo ' maksuja näillä ehdoilla</p>';
	}
}



function mr_insert_new_payment($postdata)
{
	global $wpdb;

	$keys = array();
	$values = array();
	$setval = array();

	$required = array('type', 'amount', 'deadline', 'validuntil');


	if (isset($postdata['members']) && is_array($postdata['members']) && count($postdata['members']) > 0)
	{
		foreach($postdata as $k => $v)
		{
			if (in_array($k, $required))
			{
				// sanitize
				$keys[] = mr_urize($k);
				$values[] = "'" . mr_htmlent($v) . "'";
			}
		}

		$keys[] = 'member';
		$keys[] = 'reference';
		$keys[] = 'paidday';

		$paidday = '0000-00-00';
		if (isset($postdata['alreadypaid']) && ($postdata['alreadypaid'] == 'on' || $postdata['alreadypaid'] == '1'))
		{
			$paidday = date('Y-m-d');
		}

		$id = intval('2' . $wpdb->get_var('SELECT MAX(id) FROM ' . $wpdb->prefix . 'mr_payment'));

		foreach($postdata['members'] as $member)
		{
			$id++;
			// calculate reference number
			$ref = "'" . mr_reference_count($id) . "'";

			$setval[] = '(' . implode(', ', array_merge($values, array('"' . $member . '"', $ref, '"' . $paidday . '"'))) . ')';

		}
	}

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'mr_payment (' . implode(', ', $keys) . ') VALUES ' . implode(', ', $setval);

	//echo '<div class="error"><p>' . $sql . '</p></div>';

	return $wpdb->query($sql);
}




/**
 * Print out a form for creating new payments
 */
function mr_new_payment_form($members)
{
	if (!current_user_can('read') || !mr_has_permission(MR_ACCESS_PAYMENT_MANAGE))
	{
		wp_die( __('You do not have sufficient permissions to access this page.', 'member-register'));
	}

	global $wpdb;
	?>
	<form name="form1" method="post" action="" enctype="multipart/form-data" autocomplete="on">
		<input type="hidden" name="mr_submit_hidden_payment" value="Y" />
		<table class="form-table" id="mrform">
			<tr class="form-field">
				<th><?php echo __('Member', 'member-register'); ?> <span class="description">(<?php echo __('monivalinta', 'member-register'); ?>)</span></th>
				<td><select class="chosen" name="members[]" multiple="multiple" size="7" data-placeholder="Valitse jäsenet">
				<option value=""></option>
				<?php
				foreach($members as $user)
				{
					echo '<option value="' . $user['id']. '">' . $user['name'] . ' (' . $user['id']. ')</option>';
				}
				?>
				</select></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Type', 'member-register'); ?> <span class="description">(<?php echo __('vuosimaksu, ainaisjäsenmaksu, jne...', 'member-register'); ?>)</span></th>
				<td><input type="text" name="type" value="" class="required" required="required" list="types" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Amount', 'member-register'); ?> <span class="description">(<?php echo __('EUR', 'member-register'); ?>)</span></th>
				<td><input type="number" name="amount" value="" class="required" required="required" list="amounts" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Deadline', 'member-register'); ?> <span class="description">(<?php echo __('3 viikkoa tulevaisuudessa', 'member-register'); ?>)</span></th>
				<td><input type="text" name="deadline" class="pickday required" required="required" value="<?php
				echo date('Y-m-d', time() + 60*60*24*21);
				?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Valid until', 'member-register'); ?> <span class="description">(<?php echo __('kuluvan vuoden loppuun', 'member-register'); ?>)</span></th>
				<td><input type="text" name="validuntil" class="pickday" required="required" value="<?php
				echo date('Y') . '-12-31';
				?>" /></td>
			</tr>
			<tr class="form-field">
				<th><?php echo __('Already paid', 'member-register'); ?> <span class="description">(<?php echo __('maksettu tänään', 'member-register'); ?>)</span></th>
				<td><input type="checkbox" name="alreadypaid" class="w4em" /></td>
			</tr>
		</table>

		<datalist id="types">
			<?php
			$sql = 'SELECT DISTINCT type FROM ' . $wpdb->prefix . 'mr_payment WHERE visible = 1 ORDER BY type ASC';
			$results = $wpdb->get_results($sql, ARRAY_A);
			foreach ($results as $res)
			{
				echo '<option value="' . $res['type'] . '" />';
			}
			?>
		</datalist>
		<datalist id="amounts">
			<?php
			$sql = 'SELECT DISTINCT amount FROM ' . $wpdb->prefix . 'mr_payment WHERE visible = 1 ORDER BY amount ASC';
			$results = $wpdb->get_results($sql, ARRAY_A);
			foreach ($results as $res)
			{
				echo '<option value="' . $res['amount'] . '" />';
			}
			?>
		</datalist>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="Lisää lasku" />
		</p>

	</form>
	<?php
}





/**
 * Counts and adds the check number used in the Finnish invoices.
 */
function mr_reference_count($given)
{
	$div = array (7, 3, 1);
	$len = strlen($given);
	$arr = str_split($given);
	$summed = 0;
	for ($i = $len - 1; $i >= 0; --$i)
	{
		$summed += $arr[$i] * $div[($len - 1 - $i) % 3];
	}
	$check = (10 - ($summed % 10)) %10;
	return $given.$check;
}
