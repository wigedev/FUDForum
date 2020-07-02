<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: rpasswd.php.t 5030 2010-10-08 18:27:42Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

	define('plain_form', 1);

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}/** Log action to the forum's Action Log Viewer ACP. */
function logaction($user_id, $res, $res_id=0, $action=null)
{
	q('INSERT INTO fud30_action_log (logtime, logaction, user_id, a_res, a_res_id)
		VALUES('. __request_timestamp__ .', '. ssn($action) .', '. $user_id .', '. ssn($res) .', '. (int)$res_id .')');
}

	if (!__fud_real_user__) {
		std_error('login');
	}
	if (!($FUD_OPT_4 & 2)) {	// Not ALLOW_PASSWORD_RESET.
                std_error('disabled');
        }

	/* Change current password (cpasswd) to passwd1 (use passwd2 for verification). */
	if (isset($_POST['btn_submit'], $_POST['passwd1'], $_POST['cpasswd']) && is_string($_POST['passwd1'])) {
		if (!($r = db_sab('SELECT id, passwd, salt FROM fud30_users WHERE login='. _esc($usr->login)))) {
			exit('Go away!');
		}
		
		if (__fud_real_user__ != $r->id || !((empty($r->salt) && $r->passwd == md5((string)$_POST['cpasswd'])) || $r->passwd == sha1($r->salt . sha1((string)$_POST['cpasswd'])))) {
			$rpasswd_error_msg = 'Invalid Password';
		} else if ($_POST['passwd1'] !== $_POST['passwd2']) {
			$rpasswd_error_msg = 'Passwords do not match';
		} else if (strlen($_POST['passwd1']) < 6 ) {
			$rpasswd_error_msg = 'Password must be at least 6 characters long';
		} else {
			$salt = substr(md5(uniqid(mt_rand(), true)), 0, 9);
			$secure_pass = sha1($salt . sha1($_POST['passwd1']));
			q('UPDATE fud30_users SET passwd='. _esc($secure_pass) .', salt='. _esc($salt) .' WHERE id='. __fud_real_user__);
			logaction(__fud_real_user__, 'CHANGE_PASSWD', 0, get_ip());
			exit('<html><script>window.close();</script></html>');
		}

		$rpasswd_error = '<tr>
	<td class="MsgR3 ErrorText ac" colspan="2">'.$rpasswd_error_msg.'</td>
</tr>';
	} else {
		$rpasswd_error = '';
	}

	$TITLE_EXTRA = ': Change Password Form';



?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name=viewport content="width=device-width, initial-scale=1">
<title><?php echo $GLOBALS['FORUM_TITLE'].$TITLE_EXTRA; ?></title>
<script src="/js/lib.js"></script>
<script async src="/js/jquery.js"></script>
<script async src="/js/ui/jquery-ui.js"></script>
<link rel="stylesheet" href="/theme/responsive/forum.css" />
</head>
<body>
<div class="content">
<form method="post" action="/index.php?t=rpasswd">
<div class="ac">
<table cellspacing="1" cellpadding="2" class="MiniTable" width="100%">
<?php echo $rpasswd_error; ?>
<tr>
	<th colspan="2">Change Password</th>
</tr>
<tr class="RowStyleB">
	<td>Login</td>
	<td><?php echo htmlspecialchars($usr->login, null, null, false); ?></td>
</tr>
<tr class="RowStyleB">
	<td>Current Password:</td>
	<td><input type="password" name="cpasswd" value="" /></td>
</tr>
<tr class="RowStyleB">
	<td>New Password:</td>
	<td><input type="password" name="passwd1" id="passwd1" value="" /></td>
</tr>
<tr class="RowStyleB">
	<td>Confirm Password:</td>
	<td><input type="password" name="passwd2" id="passwd2" value="" onkeyup="passwords_match('passwd1', this); return false;" /></td>
</tr>
<tr class="RowStyleB">
	<td align="right" colspan="2"><input type="submit" class="button" value="Go" name="btn_submit" /></td>
</tr>
</table>
</div>
<?php echo _hs; ?>
</form>
</div>
</body></html>
