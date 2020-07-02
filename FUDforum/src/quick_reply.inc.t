<?php
/**
* copyright            : (C) 2001-2019 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: quick_reply.inc.t 6282 2019-05-25 15:34:41Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

        /* Variables for quick_reply template */
	// FUD_OPT_3 8388608=expanded, 16777216=collapsed; thread_opt 1=locked
	$quick_reply_enabled = _uid && ($GLOBALS['FUD_OPT_3'] & (8388608|16777216)) && ((!($frm->thread_opt & 1) || $perms & 4096));
	$quick_reply_collapsed = $GLOBALS['FUD_OPT_3'] & 16777216;
	$quick_reply_subject = strncmp('{TEMPLATE: quick_reply_prefix}', $obj2->subject, strlen('{TEMPLATE: quick_reply_prefix}')) ? '{TEMPLATE: quick_reply_prefix}'.' '. $obj2->subject : $obj2->subject;
?>
