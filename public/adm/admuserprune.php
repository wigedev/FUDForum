<?php
/**
* copyright            : (C) 2001-2013 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: admuserprune.php 6247 2019-02-17 19:31:28Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

	@set_time_limit(6000);

	require('./GLOBALS.php');

	// Run from command line.
	if (php_sapi_name() == 'cli') {
		if (empty($_SERVER['argv'][1])) {
			echo "Usage: php admuserprune.php days\n";
			echo " - 'days' is the number of days the users haven't logged in for.\n";
			die();
		}

		fud_use('adm_cli.inc', 1);
		$_POST['btn_prune'] = $_POST['btn_conf'] = 1;
		$_POST['user_age'] = $_SERVER['argv'][1];
		$_POST['units'] = 86400; // a day.
	}

	fud_use('adm.inc', true);
	fud_use('widgets.inc', true);
	fud_use('users_reg.inc');
	fud_use('users_adm.inc', true);

	require($WWW_ROOT_DISK .'adm/header.php');

	if (isset($_POST['btn_prune']) && !empty($_POST['user_age']) && !isset($_POST['btn_cancel'])) {
		/* Figure out our limit if any. */
		$back = __request_timestamp__ - $_POST['units'] * $_POST['user_age'];

		if (!isset($_POST['btn_conf']) && $back > 0) {
			/* Count the number of users that will be affected. */
			$user_count = q_singleval('SELECT count(*) FROM '. $DBHOST_TBL_PREFIX .'users WHERE id > 1 AND posted_msg_count = 0 AND last_visit < '. $back .' AND join_date < '. $back);
?>
<div align="center">You are about to delete <font color="red"><?php echo $user_count; ?></font> users,
which haven't logged on since <font color="red"><?php echo fdate($back, 'd M Y H:i'); ?></font><br /><br />
			Are you sure you want to do this?<br />
			<form method="post" action="">
			<input type="hidden" name="btn_prune" value="1" />
			<?php echo _hs; ?>
			<input type="hidden" name="units" value="<?php echo $_POST['units']; ?>" />
			<input type="hidden" name="user_age" value="<?php echo $_POST['user_age']; ?>" />
			<input type="submit" name="btn_conf" value="Yes" />
			<input type="submit" name="btn_cancel" value="No" />
			</form>
</div>
<?php
			require($WWW_ROOT_DISK .'adm/footer.php');
			exit;
		} else if ( isset($_POST['btn_conf']) && $back > 0) {
			$c = q('SELECT id FROM '. $DBHOST_TBL_PREFIX .'users WHERE id > 1 AND posted_msg_count = 0 AND last_visit < '. $back .' AND join_date < '. $back);
			while ($r = db_rowarr($c)) {
				// echo 'DELETE USER '. $r[0] .'<br />';
				usr_delete($r[0]);
			}
			pf(successify('Done. It is highly recommended that you run a <a href="consist.php?'. __adm_rsid .'">consistency check</a> after pruning.'));
		} else if ($back < 1) {
			pf(errorify('You\'ve selected a date too far in the past!'));
		}

		if (defined('shell_script')) {
			return;
		}
	}
?>
<h2>User Pruning</h2>

<p>This utility remove forum users that have <u>zero posts</u> and haven't logged on for the time specified.
For example, if you enter a value of 2 and select "years" this form will offer to delete users with 0 posts that haven't logged on within the last 2 years.</p>

<form id="adp" method="post" action="admuserprune.php">
<table class="datatable">
<tr class="field">
	<td nowrap="nowrap">Users with last login prior to:</td>
	<td ><input tabindex="1" type="number" name="user_age" /></td>
	<td nowrap="nowrap"><?php draw_select('units', "Day(s)\nWeek(s)\nMonth(s)\nYear(s)", "86400\n604800\n2635200\n31622400", '31622400'); ?>&nbsp;&nbsp;ago</td>
</tr>

</td></tr>

<tr class="field">
	<td align="right" colspan="3"><input tabindex="2" type="submit" name="btn_prune" value="Prune" /></td>
</tr>
</table>
<?php echo _hs; ?>
</form>

<p><a href="admuser.php?<?php echo __adm_rsid; ?>">&laquo; Back to User Administration System</a></p>

<?php require($WWW_ROOT_DISK .'adm/footer.php'); ?>
