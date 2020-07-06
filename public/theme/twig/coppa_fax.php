<?php
/**
* copyright            : (C) 2001-2017 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: coppa_fax.php.t 6078 2017-09-25 14:57:31Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}

	// This form is for printing, therefore it lacks any advanced layout.
	if (!__fud_real_user__) {
		if ($FUD_OPT_2 & 32768) {	// USE_PATH_INFO
			header('Location: /index.php/i/'. _rsidl);
		} else {
			header('Location: /index.php?t=index&'. _rsidl);
		}
		exit;
	}
	
	// User's name to print on form.
	$name = q_singleval('SELECT name FROM fud30_users WHERE id='. __fud_real_user__);


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
<link rel="stylesheet" href="/theme/twig/forum.css" />
</head>
<body>
<div class="content">
<strong>Instructions for a Parent or Guardian</strong><br /><br />
Please print this page, sign it, and mail or fax it back to us.
<pre>
<?php echo @file_get_contents($FORUM_SETTINGS_PATH."coppa_maddress.msg"); ?>
</pre>
<table border="1" cellspacing="1" cellpadding="3">
<tr>
	<td colspan="2">Registration Form</td>
</tr>
<tr>
	<td>Login</td>
	<td><?php echo $usr->login; ?></td>
</tr>
<tr>
	<td>Password</td>
	<td>&lt;HIDDEN&gt;</td>
</tr>
<tr>
	<td>E-mail</td>
	<td><?php echo $usr->email; ?></td>
</tr>
<tr>
	<td>Name</td>
	<td><?php echo $name; ?></td>
</tr>
<tr>
	<td colspan="2">
		Please sign the form below and send it to us<br />
		I have reviewed the information my child has supplied and I have read the Privacy Policy for the web site. I understand that the profile information may be changed using a password. I understand that I may ask that this registration profile be removed entirely from the forum.
	</td>
</tr>
<tr>
	<td>Sign here if you give permission</td>
	<td><u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u></td>
</tr>
<tr>
	<td>Sign here if you would like the account to be deleted</td>
	<td><u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u></td>
</tr>
<tr>
	<td>Parent/Guardian Full Name:</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td>Relation to Child:</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td>Telephone:</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td>E-mail Address:</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td>Date:</td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td colspan="2">Please contact <a href="mailto:<?php echo $GLOBALS['ADMIN_EMAIL']; ?>"><?php echo $GLOBALS['ADMIN_EMAIL']; ?></a> with any questions</td>
</tr>
</table>
</div>
</body></html>
