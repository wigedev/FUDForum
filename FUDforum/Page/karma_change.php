<?php
/**
* copyright            : (C) 2001-2018 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: karma_change.php.t 6339 2019-11-15 17:54:35Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}

	if (!_uid) {			// User must be logged in.
		std_error('login');
	}
	if (!($FUD_OPT_4 & 4)) {	// KARMA must be enabled.
		std_error('access');
	}



	if (isset($_GET['karma_msg_id'], $_GET['sel_number'])) {
		switch ($_GET['sel_number']) {
		    case 'up' : $rt = 1;
				break;
		    case 'down': $rt = -1;
				break;
		    default: $rt = 0;
		}

		$msg = (int) $_GET['karma_msg_id'];

		/* Security check whether the user has permission to rate topic/karma in the forum */
		if (!q_singleval(q_limit('SELECT m1.id
				FROM fud30_msg m1 JOIN fud30_thread t ON m1.thread_id=t.id
				LEFT JOIN fud30_mod m ON t.forum_id=m.forum_id AND m.user_id='. _uid .'
				INNER JOIN fud30_group_cache g1 ON g1.user_id='. (_uid ? 2147483647 : 0) .' AND g1.resource_id=t.forum_id
				'.(_uid ? ' LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=t.forum_id ' : '').'
				WHERE m1.id='. $msg . ($is_a ? '' : ' AND (m.id IS NOT NULL OR '. q_bitand(_uid ? 'COALESCE(g2.group_cache_opt, g1.group_cache_opt)' : 'g1.group_cache_opt', 1024) .' > 0)'), 1))) {
			std_error('access');
		}

		$poster_id = db_saq('SELECT poster_id FROM fud30_msg WHERE id='. $msg);
		$karma = db_saq('SELECT karma FROM fud30_users WHERE id='. $poster_id[0]);
               
		/* Check if user already voted for this specific message */
		if (!q_singleval('SELECT user_id FROM fud30_karma_rate_track WHERE msg_id = '. $msg .' AND user_id = '. _uid)) {
               
		   if (db_li('INSERT INTO fud30_karma_rate_track (msg_id, user_id, poster_id, stamp, rating) VALUES('. $msg .', '. _uid .', '. $poster_id[0] .', '. __request_timestamp__ .', '. $rt .')', $ef)) {

			$new_karma = (int)$karma[0] + $rt;
			q('UPDATE fud30_users SET karma='. $new_karma .' WHERE id='. $poster_id[0]);

                   }
		} else { /* user already voted, don't change karma */
		   $new_karma = (int)$karma[0];
		}
		if ($is_a) {
		  $MOD = 1;
		} else {
		  $MOD = 0;
		}

		$obj = new StdClass;
		$obj->id = $msg;
		$obj->karma = $new_karma;
		exit(''.($GLOBALS['FUD_OPT_4'] & 4 && $obj->poster_id > 0 ? '<div class="karma_usr_'.$obj->poster_id.' SmallText">
'.($MOD ? '<a href="javascript://" onclick="window_open(\'/index.php?t=karma_track&amp;'._rsid.'&amp;msgid='.$obj->id.'\', \'karma_rating_track\', 300, 400);" class="karma">' : '' )  .'
	<b>Karma:</b> '.$obj->karma.'
'.($MOD ? '</a>' : '' )  .'
' : '' )  .'');
	}

