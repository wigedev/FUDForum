<?php
/**
* copyright            : (C) 2001-2019 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: emailconf.php.t 6245 2019-02-11 18:20:33Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

/*{PRE_HTML_PHP}*/
/*{POST_HTML_PHP}*/

	if (empty($_GET['conf_key'])) {
		error_dialog('{TEMPLATE: emailconf_err_invkey_title}', '{TEMPLATE: emailconf_err_invkey_msg}');
	}

	/* It is possible that a user may access the email confirmation URL twice, for such a 'rare' case,
	 * we have this check to prevent a confusing error message being thrown at the helpless user.
	 */
	if (_uid && $usr->users_opt & 131072) {
		check_return($usr->returnto);
	}

	$uid = q_singleval('SELECT id FROM {SQL_TABLE_PREFIX}users WHERE conf_key='. _esc($_GET['conf_key']));
	if (!$uid || (__fud_real_user__ && __fud_real_user__ != $uid)) {
		error_dialog('{TEMPLATE: emailconf_err_invkey_title}', '{TEMPLATE: emailconf_err_invkey_msg}');
	}
	q('UPDATE {SQL_TABLE_PREFIX}users SET users_opt='. q_bitor(users_opt, 131072) .', conf_key=NULL WHERE id='. $uid);
	logaction($uid, 'EMAILCONFIRMED', 0, 'Key='. $_GET['conf_key']);

	if (defined('plugins')) {
		plugin_call_hook('EMAILCONFIRMED', $uid);
	}

	if (!__fud_real_user__) {
		$usr->ses_id = user_login($uid, $usr->ses_id, true);
		$usr->users_opt = (int) q_singleval('SELECT users_opt FROM {SQL_TABLE_PREFIX}users WHERE id='. $uid);
	}
	if ($usr->users_opt & 2097152) {
		header('Location: {ROOT}'. ($FUD_OPT_2 & 32768 ? '/rc/' : '?t=reg_conf&') . _rsidl);
		return;
	}
	check_return($usr->returnto);
?>
