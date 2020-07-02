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

/*{PRE_HTML_PHP}*/

	// This form is for printing, therefore it lacks any advanced layout.
	if (!__fud_real_user__) {
		if ($FUD_OPT_2 & 32768) {	// USE_PATH_INFO
			header('Location: {ROOT}/i/'. _rsidl);
		} else {
			header('Location: {ROOT}?t=index&'. _rsidl);
		}
		exit;
	}
	
	// User's name to print on form.
	$name = q_singleval('SELECT name FROM {SQL_TABLE_PREFIX}users WHERE id='. __fud_real_user__);

/*{POST_PAGE_PHP_CODE}*/
?>
{TEMPLATE: COPPAFAX_PAGE}
