<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: cat_focus.php.t 4994 2010-09-02 17:33:29Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}


	if (!_uid || empty($_POST['c'])) { // nothing to do for unregistered users or missing category id
		return;
	}

	if (!($c = q_singleval('SELECT id FROM fud30_cat WHERE id='. (int)$_POST['c']))) { // invalid category id
		return;
	}

	if (($cur_status = q_singleval('SELECT cat_collapse_status FROM fud30_users WHERE id='. _uid))) {
		$cur_status = unserialize($cur_status);
	} else {
		$cur_status = array();
	}

	$cur_status[$c] = (int) !empty($_POST['on']);

	q('UPDATE fud30_users SET cat_collapse_status='. ($cur_status ? _esc(serialize($cur_status)) : 'NULL') .' WHERE id='. _uid);
