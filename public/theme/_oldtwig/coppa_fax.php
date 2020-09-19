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
	F()->response->name = q_singleval('SELECT name FROM fud30_users WHERE id='. __fud_real_user__);
	F()->response->coppa_address = @file_get_contents($FORUM_SETTINGS_PATH."coppa_maddress.msg");
