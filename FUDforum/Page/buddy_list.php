<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: buddy_list.php.t 4994 2010-09-02 17:33:29Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function buddy_add($user_id, $bud_id)
{
	q('INSERT INTO fud30_buddy (bud_id, user_id) VALUES ('. $bud_id .', '. $user_id .')');
	return buddy_rebuild_cache($user_id);
}

function buddy_delete($user_id, $bud_id)
{
	q('DELETE FROM fud30_buddy WHERE user_id='. $user_id .' AND bud_id='. $bud_id);
	return buddy_rebuild_cache($user_id);
}

function buddy_rebuild_cache($uid)
{
	$arr = array();
	$q = uq('SELECT bud_id FROM fud30_buddy WHERE user_id='. $uid);
	while ($ent = db_rowarr($q)) {
		$arr[$ent[0]] = 1;
	}
	unset($q);

	if ($arr) {
		q('UPDATE fud30_users SET buddy_list='. _esc(serialize($arr)) .' WHERE id='. $uid);
		return $arr;
	}
	q('UPDATE fud30_users SET buddy_list=NULL WHERE id='. $uid);
}function check_return($returnto)
{
	if ($GLOBALS['FUD_OPT_2'] & 32768 && !empty($_SERVER['PATH_INFO'])) {
		if (!$returnto || !strncmp($returnto, '/er/', 4)) {
			header('Location: /index.php/i/'. _rsidl);
		} else if ($returnto[0] == '/') { /* Unusual situation, path_info & normal themes are active. */
			header('Location: /index.php'. $returnto);
		} else {
			header('Location: /index.php?'. $returnto);
		}
	} else if (!$returnto || !strncmp($returnto, 't=error', 7)) {
		header('Location: /index.php?t=index&'. _rsidl);
	} else if (strpos($returnto, 'S=') === false && $GLOBALS['FUD_OPT_1'] & 128) {
		header('Location: /index.php?'. $returnto .'&S='. s);
	} else {
		header('Location: /index.php?'. $returnto);
	}
	exit;
}function alt_var($key)
{
	if (!isset($GLOBALS['_ALTERNATOR_'][$key])) {
		$args = func_get_args(); unset($args[0]);
		$GLOBALS['_ALTERNATOR_'][$key] = array('p' => 2, 't' => func_num_args(), 'v' => $args);
		return $args[1];
	}
	$k =& $GLOBALS['_ALTERNATOR_'][$key];
	if ($k['p'] == $k['t']) {
		$k['p'] = 1;
	}
	return $k['v'][$k['p']++];
}

	if (!_uid) {
		std_error('login');
	}

	if (isset($_POST['add_login']) && is_string($_POST['add_login'])) {
		if (!($buddy_id = q_singleval('SELECT id FROM fud30_users WHERE alias='. _esc(char_fix(htmlspecialchars($_POST['add_login'])))))) {
			error_dialog('Unable to add user', 'The user you tried to add to your buddy list was not found.');
		}
		if ($buddy_id == _uid) {
			error_dialog('Info', 'You cannot add yourself to your buddy list');
		}
		if (q_singleval('SELECT id FROM fud30_user_ignore WHERE user_id='. $buddy_id .' AND ignore_id='. _uid)) {
			error_dialog('Info', 'Cannot add users who ignore you to your buddy list.');
		}

		if (!empty($usr->buddy_list)) {
			$usr->buddy_list = unserialize($usr->buddy_list);
		}

		if (!isset($usr->buddy_list[$buddy_id]) && !q_singleval('SELECT id FROM fud30_user_ignore WHERE user_id='. $buddy_id .' AND ignore_id='. _uid)) {
			$usr->buddy_list = buddy_add(_uid, $buddy_id);
		} else {
			error_dialog('Info', 'You already have this user on your buddy list');
		}
	}

	/* incomming from message display page (add buddy link) */
	if (isset($_GET['add']) && ($_GET['add'] = (int)$_GET['add'])) {
		if (!sq_check(0, $usr->sq)) {
			check_return($usr->returnto);
		}

		if (!empty($usr->buddy_list)) {
			$usr->buddy_list = unserialize($usr->buddy_list);
		}

		if (($buddy_id = q_singleval('SELECT id FROM fud30_users WHERE id='. $_GET['add'])) && !isset($usr->buddy_list[$buddy_id]) && _uid != $buddy_id && !q_singleval('SELECT id FROM fud30_user_ignore WHERE user_id='. $buddy_id .' AND ignore_id='. _uid)) {
			buddy_add(_uid, $buddy_id);
		}
		check_return($usr->returnto);
	}

	if (isset($_GET['del']) && ($_GET['del'] = (int)$_GET['del'])) {
		if (!sq_check(0, $usr->sq)) {
			check_return($usr->returnto);
		}

		buddy_delete(_uid, $_GET['del']);
		/* needed for external links to this form */
		if (isset($_GET['redr'])) {
			check_return($usr->returnto);
		}
	}

	ses_update_status($usr->sid, 'Browsing own buddy list');

/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> You have <span class="GenTextRed">('.$c.')</span> unread '.convertPlural($c, array('private message','private messages')).'</a></li>' : '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> Private Messaging</a></li>';
	} else {
		$ucp_private_msg = '';
	}$tabs = '';
if (_uid) {
	$tablist = array(
'Notifications'=>'uc',
'Account Settings'=>'register',
'Subscriptions'=>'subscribed',
'Bookmarks'=>'bookmarked',
'Referrals'=>'referals',
'Buddy List'=>'buddy_list',
'Ignore List'=>'ignore_list',
'Show Own Posts'=>'showposts'
);

	if (!($FUD_OPT_2 & 8192)) {
		unset($tablist['Referrals']);
	}

	if (isset($_POST['mod_id'])) {
		$mod_id_chk = $_POST['mod_id'];
	} else if (isset($_GET['mod_id'])) {
		$mod_id_chk = $_GET['mod_id'];
	} else {
		$mod_id_chk = null;
	}

	if (!$mod_id_chk) {
		if ($FUD_OPT_1 & 1024) {
			$tablist['Private Messaging'] = 'pmsg';
		}
		$pg = ($_GET['t'] == 'pmsg_view' || $_GET['t'] == 'ppost') ? 'pmsg' : $_GET['t'];

		foreach($tablist as $tab_name => $tab) {
			$tab_url = '/index.php?t='. $tab . (s ? '&amp;S='. s : '');
			if ($tab == 'referals') {
				if (!($FUD_OPT_2 & 8192)) {
					continue;
				}
				$tab_url .= '&amp;id='. _uid;
			} else if ($tab == 'showposts') {
				$tab_url .= '&amp;id='. _uid;
			}
			$tabs .= $pg == $tab ? '<td class="tabON"><div class="tabT"><a class="tabON" href="'.$tab_url.'">'.$tab_name.'</a></div></td>' : '<td class="tabI"><div class="tabT"><a href="'.$tab_url.'">'.$tab_name.'</a></div></td>';
		}

		$tabs = '<table cellspacing="1" cellpadding="0" class="tab">
<tr>
	'.$tabs.'
</tr>
</table>';
	}
}

	$c = uq('SELECT b.bud_id, u.id, u.alias, u.join_date, u.birthday, '. q_bitand('u.users_opt', 32768) .', u.posted_msg_count, u.home_page, u.last_visit AS time_sec
		FROM fud30_buddy b INNER JOIN fud30_users u ON b.bud_id=u.id WHERE b.user_id='. _uid);

	$buddies = '';
	/* Result index
	 * 0 - bud_id	1 - user_id	2 - login	3 - join_date	4 - birthday	5 - users_opt	6 - msg_count
	 * 7 - home_page	8 - last_visit
	 */

	if (($r = db_rowarr($c))) {
		$dt = getdate(__request_timestamp__);
		$md = sprintf('%02d%02d', $dt['mon'], $dt['mday']);

		do {
			if ((!($r[5] & 32768) && $FUD_OPT_2 & 32) || $is_a) {
				$online_status = (($r[8] + $LOGEDIN_TIMEOUT * 60) > __request_timestamp__) ? '<img src="/theme/twig/images/online.png" title="'.$r[2].' is currently online" alt="'.$r[2].' is currently online" />' : '<img src="/theme/twig/images/offline.png" title="'.$r[2].' is currently offline" alt="'.$r[2].' is currently offline" />';
			} else {
				$online_status = '';
			}

			if ($r[4] && substr($r[4], 0, 4) == $md) {
				$age = $dt['year'] - (int)substr($r[4], 4);
				$bday_indicator = '<img src="/blank.gif" alt="" width="10" height="1" /><img src="/theme/twig/images/bday.gif" alt="" />Today '.$r[2].' turns '.$age;
			} else {
				$bday_indicator = '';
			}

			$buddies .= '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="ac">'.$online_status.'</td>
	<td class="GenText wa">
		'.($FUD_OPT_1 & 1024 ? '<a href="/index.php?t=ppost&amp;'._rsid.'&amp;toi='.urlencode($r[0]).'">'.$r[2].'</a>' : '<a href="/index.php?t=email&amp;toi='.$r[1].'&amp;'._rsid.'" rel="nofollow">'.$r[2].'</a>' ) .'&nbsp;
		<span class="SmallText">(<a href="/index.php?t=buddy_list&amp;'._rsid.'&amp;del='.$r[0].'&amp;SQ='.$GLOBALS['sq'].'">remove</a>)</span>&nbsp;
		'.$bday_indicator.'
	</td>
	<td class="ac">'.$r[6].'</td>
	<td class="ac nw">'.strftime('%a, %d %B %Y %H:%M', $r[3]).'</td>
	<td class="GenText nw">
		<a href="/index.php?t=usrinfo&amp;id='.$r[1].'&amp;'._rsid.'"><img src="/theme/twig/images/msg_about.gif" alt="" /></a>&nbsp;
		<a href="/index.php?t=showposts&amp;'._rsid.'&amp;id='.$r[1].'"><img src="/theme/twig/images/show_posts.gif" alt="" /></a>
		'.($r[7] ? '<a href="'.$r[7].'"><img src="/theme/twig/images/homepage.gif" alt="" /></a>' : '' ) .'
	</td>
</tr>';
		} while (($r = db_rowarr($c)));
		$buddies = '<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th>Status</th>
	<th>My Buddies</th>
	<th class="nw ac">Message Count</th>
	<th class="ac nw">Registered on</th>
	<th class="ac nw">Action</th>
</tr>
'.$buddies.'
</table>';
	}
	unset($c);

if ($FUD_OPT_2 & 2 || $is_a) {	// PUBLIC_STATS is enabled or Admin user.
	$page_gen_time = number_format(microtime(true) - __request_timestamp_exact__, 5);
	$page_stats = $FUD_OPT_2 & 2 ? '<br /><div class="SmallText al">Total time taken to generate the page: '.convertPlural($page_gen_time, array(''.$page_gen_time.' seconds')).'</div>' : '<br /><div class="SmallText al">Total time taken to generate the page: '.convertPlural($page_gen_time, array(''.$page_gen_time.' seconds')).'</div>';
} else {
	$page_stats = '';
}

F()->response->tabs = $tabs;
F()->response->buddies = $buddies;
F()->response->member_search_enabled = $FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304);
