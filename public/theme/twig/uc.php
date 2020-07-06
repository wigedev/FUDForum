<?php
/**
* copyright            : (C) 2001-2011 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: uc.php.t 5221 2011-04-22 11:30:48Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function tmpl_draw_select_opt($values, $names, $selected)
{
	$vls = explode("\n", $values);
	$nms = explode("\n", $names);

	if (count($vls) != count($nms)) {
		exit("FATAL ERROR: inconsistent number of values inside a select<br />\n");
	}

	$options = '';
	foreach ($vls as $k => $v) {
		$options .= '<option value="'.$v.'"'.($v == $selected ? ' selected="selected"' : '' )  .'>'.$nms[$k].'</option>';
	}

	return $options;
}function pager_replace(&$str, $s, $c)
{
	$str = str_replace(array('%s', '%c'), array($s, $c), $str);
}

function tmpl_create_pager($start, $count, $total, $arg, $suf='', $append=1, $js_pager=0, $no_append=0)
{
	if (!$count) {
		$count =& $GLOBALS['POSTS_PER_PAGE'];
	}
	if ($total <= $count) {
		return;
	}

	$upfx = '';
	if ($GLOBALS['FUD_OPT_2'] & 32768 && (!empty($_SERVER['PATH_INFO']) || strpos($arg, '?') === false)) {
		if (!$suf) {
			$suf = '/';
		} else if (strpos($suf, '//') !== false) {
			$suf = preg_replace('!/+!', '/', $suf);
		}
	} else if (!$no_append) {
		$upfx = '&amp;start=';
	}

	$cur_pg = ceil($start / $count);
	$ttl_pg = ceil($total / $count);

	$page_pager_data = '';

	if (($page_start = $start - $count) > -1) {
		if ($append) {
			$page_first_url = $arg . $upfx . $suf;
			$page_prev_url = $arg . $upfx . $page_start . $suf;
		} else {
			$page_first_url = $page_prev_url = $arg;
			pager_replace($page_first_url, 0, $count);
			pager_replace($page_prev_url, $page_start, $count);
		}

		$page_pager_data .= !$js_pager ? '&nbsp;<a href="'.$page_first_url.'" class="PagerLink">&laquo;</a>&nbsp;&nbsp;<a href="'.$page_prev_url.'" accesskey="p" class="PagerLink">&lsaquo;</a>&nbsp;&nbsp;' : '&nbsp;<a href="javascript://" onclick="'.$page_first_url.'" class="PagerLink">&laquo;</a>&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_prev_url.'" class="PagerLink">&lsaquo;</a>&nbsp;&nbsp;';
	}

	$mid = ceil($GLOBALS['GENERAL_PAGER_COUNT'] / 2);

	if ($ttl_pg > $GLOBALS['GENERAL_PAGER_COUNT']) {
		if (($mid + $cur_pg) >= $ttl_pg) {
			$end = $ttl_pg;
			$mid += $mid + $cur_pg - $ttl_pg;
			$st = $cur_pg - $mid;
		} else if (($cur_pg - $mid) <= 0) {
			$st = 0;
			$mid += $mid - $cur_pg;
			$end = $mid + $cur_pg;
		} else {
			$st = $cur_pg - $mid;
			$end = $mid + $cur_pg;
		}

		if ($st < 0) {
			$start = 0;
		}
		if ($end > $ttl_pg) {
			$end = $ttl_pg;
		}
		if ($end - $start > $GLOBALS['GENERAL_PAGER_COUNT']) {
			$end = $start + $GLOBALS['GENERAL_PAGER_COUNT'];
		}
	} else {
		$end = $ttl_pg;
		$st = 0;
	}

	while ($st < $end) {
		if ($st != $cur_pg) {
			$page_start = $st * $count;
			if ($append) {
				$page_page_url = $arg . $upfx . $page_start . $suf;
			} else {
				$page_page_url = $arg;
				pager_replace($page_page_url, $page_start, $count);
			}
			$st++;
			$page_pager_data .= !$js_pager ? '<a href="'.$page_page_url.'" class="PagerLink">'.$st.'</a>&nbsp;&nbsp;' : '<a href="javascript://" onclick="'.$page_page_url.'" class="PagerLink">'.$st.'</a>&nbsp;&nbsp;';
		} else {
			$st++;
			$page_pager_data .= !$js_pager ? $st.'&nbsp;&nbsp;' : $st.'&nbsp;&nbsp;';
		}
	}

	$page_pager_data = substr($page_pager_data, 0 , strlen((!$js_pager ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;')) * -1);

	if (($page_start = $start + $count) < $total) {
		$page_start_2 = ($st - 1) * $count;
		if ($append) {
			$page_next_url = $arg . $upfx . $page_start . $suf;
			// $page_last_url = $arg . $upfx . $page_start_2 . $suf;
			$page_last_url = $arg . $upfx . floor($total-1/$count)*$count . $suf;
		} else {
			$page_next_url = $page_last_url = $arg;
			pager_replace($page_next_url, $upfx . $page_start, $count);
			pager_replace($page_last_url, $upfx . $page_start_2, $count);
		}
		$page_pager_data .= !$js_pager ? '&nbsp;&nbsp;<a href="'.$page_next_url.'" accesskey="n" class="PagerLink">&rsaquo;</a>&nbsp;&nbsp;<a href="'.$page_last_url.'" class="PagerLink">&raquo;</a>' : '&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_next_url.'" class="PagerLink">&rsaquo;</a>&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_last_url.'" class="PagerLink">&raquo;</a>';
	}

	return !$js_pager ? '<span class="SmallText fb">Pages ('.$ttl_pg.'): ['.$page_pager_data.']</span>' : '<span class="SmallText fb">Pages ('.$ttl_pg.'): ['.$page_pager_data.']</span>';
}include $GLOBALS['FORUM_SETTINGS_PATH'] .'ip_filter_cache';
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'login_filter_cache';
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'email_filter_cache';

function is_ip_blocked($ip)
{
	if (empty($GLOBALS['__FUD_IP_FILTER__'])) {
		return;
	}
	$block =& $GLOBALS['__FUD_IP_FILTER__'];
	list($a,$b,$c,$d) = explode('.', $ip);

	if (!isset($block[$a])) {
		return;
	}
	if (isset($block[$a][$b][$c][$d])) {
		return 1;
	}

	if (isset($block[$a][256])) {
		$t = $block[$a][256];
	} else if (isset($block[$a][$b])) {
		$t = $block[$a][$b];
	} else {
		return;
	}

	if (isset($t[$c])) {
		$t = $t[$c];
	} else if (isset($t[256])) {
		$t = $t[256];
	} else {
		return;
	}

	if (isset($t[$d]) || isset($t[256])) {
		return 1;
	}
}

function is_login_blocked($l)
{
	foreach ($GLOBALS['__FUD_LGN_FILTER__'] as $v) {
		if (preg_match($v, $l)) {
			return 1;
		}
	}
	return;
}

function is_email_blocked($addr)
{
	if (empty($GLOBALS['__FUD_EMAIL_FILTER__'])) {
		return;
	}
	$addr = strtolower($addr);
	foreach ($GLOBALS['__FUD_EMAIL_FILTER__'] as $k => $v) {
		if (($v && (strpos($addr, $k) !== false)) || (!$v && preg_match($k, $addr))) {
			return 1;
		}
	}
	return;
}

function is_allowed_user(&$usr, $simple=0)
{
	/* Check if the ban expired. */
	if (($banned = $usr->users_opt & 65536) && $usr->ban_expiry && $usr->ban_expiry < __request_timestamp__) {
		q('UPDATE fud30_users SET users_opt = '. q_bitand('users_opt', ~65536) .' WHERE id='. $usr->id);
		$usr->users_opt ^= 65536;
		$banned = 0;
	} 

	if ($banned || is_email_blocked($usr->email) || is_login_blocked($usr->login) || is_ip_blocked(get_ip())) {
		$ban_expiry = (int) $usr->ban_expiry;
		$ban_reason = $usr->ban_reason;
		if (!$simple) { // On login page we already have anon session.
			ses_delete($usr->sid);
			$usr = ses_anon_make();
		}
		setcookie($GLOBALS['COOKIE_NAME'].'1', 'd34db33fd34db33fd34db33fd34db33f', ($ban_expiry ? $ban_expiry : (__request_timestamp__ + 63072000)), $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
		if ($banned) {
			error_dialog('ERROR: You have been banned.', 'Your account was '.($ban_expiry ? 'temporarily banned until '.strftime('%a, %d %B %Y %H:%M', $ban_expiry) : 'permanently banned' )  .' from accessing the site, due to a violation of the forum&#39;s rules.
<br />
<br />
<span class="GenTextRed">'.$ban_reason.'</span>');
		} else {
			error_dialog('ERROR: Your account has been filtered out.', 'Your account has been blocked from accessing the forum due to one of the installed user filters.');
		}
	}

	if ($simple) {
		return;
	}

	if ($GLOBALS['FUD_OPT_1'] & 1048576 && $usr->users_opt & 262144) {
		error_dialog('ERROR: Your account is not yet confirmed', 'We have not received a confirmation from your parent and/or legal guardian, which would allow you to post messages. If you lost your COPPA form, <a href="/index.php?t=coppa_fax&amp;'._rsid.'">view it again</a>.');
	}

	if ($GLOBALS['FUD_OPT_2'] & 1 && !($usr->users_opt & 131072)) {
		std_error('emailconf');
	}

	if ($GLOBALS['FUD_OPT_2'] & 1024 && $usr->users_opt & 2097152) {
		error_dialog('Unverified Account', 'The administrator had chosen to review all accounts manually prior to activation. Until your account has been validated by the administrator you will not be able to utilize the full capabilities of your account.');
	}
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
}function is_notified($user_id, $thread_id)
{
	return q_singleval('SELECT * FROM fud30_thread_notify WHERE thread_id='. (int)$thread_id .' AND user_id='. $user_id);
}

function thread_notify_add($user_id, $thread_id)
{
	db_li('INSERT INTO fud30_thread_notify (user_id, thread_id) VALUES ('. $user_id .', '. (int)$thread_id .')', $ret);
}

function thread_notify_del($user_id, $thread_id)
{
	q('DELETE FROM fud30_thread_notify WHERE thread_id='. (int)$thread_id .' AND user_id='. $user_id);
}

function thread_bookmark_add($user_id, $thread_id)
{
	db_li('INSERT INTO fud30_bookmarks (user_id, thread_id) VALUES ('. $user_id .', '. (int)$thread_id .')', $ret);
}

function thread_bookmark_del($user_id, $thread_id)
{
	q('DELETE FROM fud30_bookmarks WHERE thread_id='. (int)$thread_id .' AND user_id='. $user_id);
}function is_forum_notified($user_id, $forum_id)
{
	return q_singleval('SELECT 1 FROM fud30_forum_notify WHERE user_id='. $user_id .' AND forum_id='. $forum_id);
}

function forum_notify_add($user_id, $forum_id)
{
	db_li('INSERT INTO fud30_forum_notify (user_id, forum_id) VALUES ('. $user_id .', '. $forum_id .')', $ret);
}

function forum_notify_del($user_id, $forum_id)
{
	q('DELETE FROM fud30_forum_notify WHERE user_id='. $user_id .' AND forum_id='. $forum_id);
}

	if (__fud_real_user__) {
		is_allowed_user($usr);
	} else {
		std_error('login');
	}

	ses_update_status($usr->sid, 'Viewing personal control panel.');

if (_uid) {
	$admin_cp = $accounts_pending_approval = $group_mgr = $reported_msgs = $custom_avatar_queue = $mod_que = $thr_exch = '';

	if ($usr->users_opt & 524288 || $is_a) {	// is_mod or admin.
		if ($is_a) {
			// Approval of custom Avatars.
			if ($FUD_OPT_1 & 32 && ($avatar_count = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=16777216 AND '. q_bitand('users_opt', 16777216) .' > 0'))) {
				$custom_avatar_queue = '| <a href="/adm/admavatarapr.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Custom Avatar Queue</a> <span class="GenTextRed">('.$avatar_count.')</span>';
			}

			// All reported messages.
			if ($report_count = q_singleval('SELECT count(*) FROM fud30_msg_report')) {
				$reported_msgs = '| <a href="/index.php?t=reported&amp;'._rsid.'" rel="nofollow">Reported Messages</a> <span class="GenTextRed">('.$report_count.')</span>';
			}

			// All thread exchange requests.
			if ($thr_exchc = q_singleval('SELECT count(*) FROM fud30_thr_exchange')) {
				$thr_exch = '| <a href="/index.php?t=thr_exch&amp;'._rsid.'">Topic Exchange</a> <span class="GenTextRed">('.$thr_exchc.')</span>';
			}

			// All account approvals.
			if ($FUD_OPT_2 & 1024 && ($accounts_pending_approval = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND '. q_bitand('users_opt', 2097152) .' > 0 AND id > 0'))) {
				$accounts_pending_approval = '| <a href="/adm/admuserapr.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Accounts Pending Approval</a> <span class="GenTextRed">('.$accounts_pending_approval.')</span>';
			} else {
				$accounts_pending_approval = '';
			}

			$q_limit = '';
		} else {
			// Messages reported in moderated forums.
			if ($report_count = q_singleval('SELECT count(*) FROM fud30_msg_report mr INNER JOIN fud30_msg m ON mr.msg_id=m.id INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_mod mm ON t.forum_id=mm.forum_id AND mm.user_id='. _uid)) {
				$reported_msgs = '| <a href="/index.php?t=reported&amp;'._rsid.'" rel="nofollow">Reported Messages</a> <span class="GenTextRed">('.$report_count.')</span>';
			}

			// Thread move requests in moderated forums.
			if ($thr_exchc = q_singleval('SELECT count(*) FROM fud30_thr_exchange te INNER JOIN fud30_mod m ON m.user_id='. _uid .' AND te.frm=m.forum_id')) {
				$thr_exch = '| <a href="/index.php?t=thr_exch&amp;'._rsid.'">Topic Exchange</a> <span class="GenTextRed">('.$thr_exchc.')</span>';
			}

			$q_limit = ' INNER JOIN fud30_mod mm ON f.id=mm.forum_id AND mm.user_id='. _uid;
		}

		// Messages requiring approval.
		if ($approve_count = q_singleval('SELECT count(*) FROM fud30_msg m INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_forum f ON t.forum_id=f.id '. $q_limit .' WHERE m.apr=0 AND f.forum_opt>=2')) {
			$mod_que = '<a href="/index.php?t=modque&amp;'._rsid.'">Moderation Queue</a> <span class="GenTextRed">('.$approve_count.')</span>';
		}
	} else if ($usr->users_opt & 268435456 && $FUD_OPT_2 & 1024 && ($accounts_pending_approval = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND '. q_bitand('users_opt', 2097152) .' > 0 AND id > 0'))) {
		$accounts_pending_approval = '| <a href="/adm/admuserapr.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Accounts Pending Approval</a> <span class="GenTextRed">('.$accounts_pending_approval.')</span>';
	} else {
		$accounts_pending_approval = '';
	}
	if ($is_a || $usr->group_leader_list) {
		$group_mgr = '| <a href="/index.php?t=groupmgr&amp;'._rsid.'">Group Manager</a>';
	}

	if ($thr_exch || $accounts_pending_approval || $group_mgr || $reported_msgs || $custom_avatar_queue || $mod_que) {
		$admin_cp = '<br /><span class="GenText fb">Admin:</span> '.$mod_que.' '.$reported_msgs.' '.$thr_exch.' '.$custom_avatar_queue.' '.$group_mgr.' '.$accounts_pending_approval.'<br />';
	}
} else {
	$admin_cp = '';
}/* Print number of unread private messages in User Control Panel. */
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

	if (!empty($_GET['ufid']) && sq_check(0, $usr->sq)) {
		forum_notify_del(_uid, (int)$_GET['ufid']);
	}
	if (!empty($_GET['utid']) && sq_check(0, $usr->sq)) {
		thread_notify_del(_uid, (int)$_GET['utid']);
	}
	if (!empty($_GET['ubid']) && sq_check(0, $usr->sq)) {
		buddy_delete(_uid, (int)$_GET['ubid']);
	}

	$uc_buddy_ents = '';
	$c = uq('SELECT u.id, u.alias, u.last_visit, '. q_bitand('users_opt', 32768) .' FROM fud30_buddy b INNER JOIN fud30_users u ON b.bud_id=u.id WHERE b.user_id='. _uid .' ORDER BY u.last_visit DESC');
	while ($r = db_rowarr($c)) {
		$uc_pm = ($FUD_OPT_1 & 1024) ? '<a href="/index.php?t=ppost&toi='.$r[0].'&amp;'._rsid.'">PM</a>&nbsp;||&nbsp;' : '';
		$obj = new stdClass();
		$obj->login = $r[1];
		$uc_online = (!$r[3] && ($r[2] + $LOGEDIN_TIMEOUT * 60) > __request_timestamp__) ? '<img src="/theme/twig/images/online.png" alt="'.$obj->login.' is currently online" title="'.$obj->login.' is currently online" />' : '<img src="/theme/twig/images/offline.png" alt="'.$obj->login.' is currently offline" title="'.$obj->login.' is currently offline" />';
		$uc_buddy_ents .= '<tr class="RowStyleA">
	<td class="vm">'.$uc_online.'</td>
	<td class="nw vm wa"><a href="/index.php?t=usrinfo&amp;id='.$r[0].'&amp;'._rsid.'">'.$r[1].'</a></td>
	<td class="nw vm RowStyleB SmallText">'.$uc_pm.'<a href="/index.php?t=uc&amp;ubid='.$r[0].'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">X</a></td>
</tr>';
	}
	unset($c);

	$uc_new_pms = '';
	$c = uq(q_limit('SELECT m.ouser_id, u.alias, m.post_stamp, m.subject, m.id FROM fud30_pmsg m INNER JOIN fud30_users u ON u.id=m.ouser_id WHERE m.duser_id='. _uid .' AND fldr=1 AND read_stamp=0 ORDER BY post_stamp DESC', ($usr->posts_ppg ? $usr->posts_ppg : $POSTS_PER_PAGE)));
	while ($r = db_rowarr($c)) {
		$uc_new_pms .= '<tr class="RowStyleB">
	<td><a href="/index.php?t=pmsg_view&amp;id='.$r[4].'&amp;'._rsid.'">'.$r[3].'</a></td>
	<td class="nw"><a href="/index.php?t=usrinfo&amp;id='.$r[0].'&amp;'._rsid.'">'.$r[1].'</a></td>
	<td class="DateText nw">'.strftime('%b %d %Y %H:%M', $r[2]).'</td>
</tr>';
	}
	unset($c);
	if ($uc_new_pms) {
		$uc_new_pms = '<tr>
	<th class="wa">Subject</th>
	<th class="nw">Author</th>
	<th class="nw">Time</th>
</tr>
'.$uc_new_pms;
	}

	$uc_sub_forum = '';
	$c = uq('SELECT
		f.name, f.id, f.descr, f.thread_count, f.post_count,
		u.alias,
		m.subject, m.id AS mid, m.post_stamp, m.poster_id,
		c.name AS cat_name
		FROM fud30_forum_notify fn
		INNER JOIN fud30_forum f ON f.id=fn.forum_id
		INNER JOIN fud30_cat c ON c.id=f.cat_id
		INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id=f.id
		LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=f.id
		LEFT JOIN fud30_msg m ON f.last_post_id=m.id
		LEFT JOIN fud30_users u ON u.id=m.poster_id
		LEFT JOIN fud30_forum_read fr ON fr.forum_id=f.id AND fr.user_id='. _uid .'
		LEFT JOIN fud30_mod mo ON mo.user_id='. _uid .' AND mo.forum_id=f.id
		WHERE fn.user_id='. _uid .'
		AND '. $usr->last_read .' < m.post_stamp AND (fr.last_view IS NULL OR m.post_stamp > fr.last_view)
		'. ($is_a ? '' : ' AND (mo.id IS NOT NULL OR '. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 1) .'> 0)') .'
		ORDER BY m.post_stamp DESC');
	while ($r = db_rowobj($c)) {
		$uc_sub_forum .= '<tr>
	<td class="RowStyleA SmallText wa"><a href="/index.php?t='.t_thread_view.'&amp;frm_id='.$r->id.'&amp;'._rsid.'" class="big">'.htmlspecialchars($r->cat_name, null, null, false).' &raquo; '.$r->name.'</a>'.($r->descr ? '<br />
'.$r->descr.'' : '' ) .'<br /><a href="/index.php?t=post&amp;frm_id='.$r->id.'&amp;'._rsid.'">New Topic</a> || <a href="/index.php?t=uc&amp;ufid='.$r->id.'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">Unsubscribe</a></td>
	<td class="RowStyleB ac">'.$r->post_count.'</td>
	<td class="RowStyleB ac">'.$r->thread_count.'</td>
	<td class="RowStyleA SmallText ar nw">'.($r->mid ? '<a href="/index.php?t='.d_thread_view.'&amp;goto='.$r->mid.'&amp;'._rsid.'#msg_'.$r->mid.'" title="'.$r->subject.'">'.substr($r->subject, 0, min(25, strlen($r->subject))).'</a>
<br />
<span class="DateText">'.strftime('%a, %d %B %Y', $r->post_stamp).'</span>
<br />
By: '.($r->alias ? '<a href="/index.php?t=usrinfo&amp;id='.$r->poster_id.'&amp;'._rsid.'">'.htmlspecialchars($r->alias, null, null, false).'</a>' : $GLOBALS['ANON_NICK'].'' ) : '' ) .'</td>
</tr>';
	}
	if ($uc_sub_forum) {
		$uc_sub_forum = '<tr>
        <th class="wa">Category &raquo; Forum</th>
	<th class="nw">Messages</th>
        <th class="nw">Topics</th>
        <th class="nw">Last message</th>
</tr>
'.$uc_sub_forum;
	}
	unset($c);

	$uc_sub_topic = '';
	$ppg = $usr->posts_ppg ? $usr->posts_ppg : $POSTS_PER_PAGE;
	$c = uq(q_limit('SELECT
			m2.subject, m.post_stamp, m.poster_id,
			u.alias,
			t.replies, t.views, t.thread_opt, t.id, t.last_post_id
		FROM fud30_thread_notify tn
		INNER JOIN fud30_thread t ON tn.thread_id=t.id
		INNER JOIN fud30_msg m ON t.last_post_id=m.id
		INNER JOIN fud30_msg m2 ON t.root_msg_id=m2.id
		INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id=t.forum_id
		LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=t.forum_id
		LEFT JOIN fud30_users u ON u.id=m.poster_id
		LEFT JOIN fud30_read r ON t.id=r.thread_id AND r.user_id='. _uid .'
		LEFT JOIN fud30_mod mo ON mo.user_id='. _uid .' AND mo.forum_id=t.forum_id
		WHERE tn.user_id='. _uid .' AND m.post_stamp > '. $usr->last_read .' AND m.post_stamp > r.last_view '.
		($is_a ? '' : ' AND (mo.id IS NOT NULL OR '. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 1) .'> 0)').
		'ORDER BY m.post_stamp DESC', ($usr->posts_ppg ? $usr->posts_ppg : $POSTS_PER_PAGE)));
	while ($r = db_rowobj($c)) {
		$msg_count = $r->replies + 1;
		if ($msg_count > $ppg && $usr->users_opt & 256) {
			if ($THREAD_MSG_PAGER < ($pgcount = ceil($msg_count / $ppg))) {
				$i = $pgcount - $THREAD_MSG_PAGER;
				$mini_pager_data = '&nbsp;...';
			} else {
				$mini_pager_data = '';
				$i = 0;
			}
			while ($i < $pgcount) {
				$mini_pager_data .= '&nbsp;<a href="/index.php?t='.d_thread_view.'&amp;th='.$r->id.'&amp;start='.($i * $ppg).'&amp;'._rsid.'">'.++$i.'</a>';
			}
			$mini_thread_pager = $mini_pager_data ? '<span class="SmallText">(<img src="/theme/twig/images/pager.gif" alt="" />'.$mini_pager_data.')</span>' : '';
		} else {
			$mini_thread_pager = '';
		}

		$uc_sub_topic .= '<tr>
	<td class="RowStyleA"><a href="/index.php?t='.d_thread_view.'&amp;th='.$r->id.'&amp;unread=1&amp;'._rsid.'"><img src="/theme/twig/images/newposts.gif" title="Go to the first unread message in this topic" alt="" /></a>&nbsp;<a class="big" href="/index.php?t='.d_thread_view.'&amp;th='.$r->id.'&amp;'._rsid.'">'.$r->subject.'</a> '.$mini_thread_pager.'<br /><div class="ar"><a href="/index.php?t=post&amp;th_id='.$r->id.'&amp;'._rsid.'">Reply</a> || <a href="/index.php?t=uc&amp;utid='.$r->id.'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">Unsubscribe</a></div></td>
	<td class="RowStyleB ac">'.$r->replies.'</td>
	<td class="RowStyleB ac">'.$r->views.'</td>
	<td class="RowStyleC ar nw"><span class="DateText">'.strftime('%a, %d %B %Y', $r->post_stamp).'</span><br />By: '.($r->alias ? '<a href="/index.php?t=usrinfo&amp;id='.$r->poster_id.'&amp;'._rsid.'">'.htmlspecialchars($r->alias, null, null, false).'</a>' : $GLOBALS['ANON_NICK'].'' ) .' <a href="/index.php?t='.d_thread_view.'&amp;goto='.$r->last_post_id.'&amp;'._rsid.'#msg_'.$r->last_post_id.'"><img src="/theme/twig/images/goto.gif" title="Go to the last message in this topic" alt="" /></a></td>
</tr>';
	}
	if ($uc_sub_topic) {
		$uc_sub_topic = '<tr>
        <th class="wa">Topic</th>
	<th class="nw">Replies</th>
        <th class="nw">Views</th>
        <th class="nw">Last message</th>
</tr>
'.$uc_sub_topic;
	}
	unset($c);

if ($FUD_OPT_2 & 2 || $is_a) {	// PUBLIC_STATS is enabled or Admin user.
	$page_gen_time = number_format(microtime(true) - __request_timestamp_exact__, 5);
	$page_stats = $FUD_OPT_2 & 2 ? '<br /><div class="SmallText al">Total time taken to generate the page: '.convertPlural($page_gen_time, array(''.$page_gen_time.' seconds')).'</div>' : '<br /><div class="SmallText al">Total time taken to generate the page: '.convertPlural($page_gen_time, array(''.$page_gen_time.' seconds')).'</div>';
} else {
	$page_stats = '';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
	<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="<?php echo (!empty($META_DESCR) ? $META_DESCR.'' : $GLOBALS['FORUM_DESCR'].''); ?>" />
	<title><?php echo $GLOBALS['FORUM_TITLE'].$TITLE_EXTRA; ?></title>
	<link rel="search" type="application/opensearchdescription+xml" title="<?php echo $GLOBALS['FORUM_TITLE']; ?> Search" href="/open_search.php" />
	<?php echo $RSS; ?>
	<link rel="stylesheet" href="/theme/twig/forum.css" media="screen" title="Default Forum Theme" />
	<link rel="stylesheet" href="/js/ui/jquery-ui.css" media="screen" />
	<script src="/js/jquery.js"></script>
	<script async src="/js/ui/jquery-ui.js"></script>
	<script src="/js/lib.js"></script>
</head>
<body>
<!--  -->
<div class="header">
  <?php echo ($GLOBALS['FUD_OPT_1'] & 1 && $GLOBALS['FUD_OPT_1'] & 16777216 ? '
  <div class="headsearch">
    <form id="headsearch" method="get" action="/index.php">'._hs.'
      <input type="hidden" name="t" value="search" />
      <br /><label accesskey="f" title="Forum Search">Forum Search:<br />
      <input type="search" name="srch" value="" size="20" placeholder="Forum Search" /></label>
      <input type="image" src="/theme/twig/images/search.png" title="Search" name="btn_submit">&nbsp;
    </form>
  </div>
  ' : ''); ?>
  <a href="/" title="Home">
    <img class="headimg" src="/theme/twig/images/header.gif" alt="" align="left" height="80" />
    <span class="headtitle"><?php echo $GLOBALS['FORUM_TITLE']; ?></span>
  </a><br />
  <span class="headdescr"><?php echo $GLOBALS['FORUM_DESCR']; ?><br /><br /></span>
</div>
<div class="content">

<!-- Table for sidebars. -->
<table width="100%"><tr><td>
<div id="UserControlPanel">
<ul>
	<?php echo $ucp_private_msg; ?>
	<?php echo ($FUD_OPT_4 & 16 ? '<li><a href="/index.php?t=blog&amp;'._rsid.'" title="Blog"><img src="/theme/twig/images/blog.png" alt="" /> Blog</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_4 & 8 ? '<li><a href="/index.php?t=page&amp;'._rsid.'" title="Pages"><img src="/theme/twig/images/pages.png" alt="" /> Pages</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_3 & 134217728 ? '<li><a href="/index.php?t=cal&amp;'._rsid.'" title="Calendar"><img src="/theme/twig/images/calendar.png" alt="" /> Calendar</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_1 & 16777216 ? ' <li><a href="/index.php?t=search'.(isset($frm->forum_id) ? '&amp;forum_limiter='.(int)$frm->forum_id.'' : '' )  .'&amp;'._rsid.'" title="Search"><img src="/theme/twig/images/top_search.png" alt="" /> Search</a></li>' : ''); ?>
	<li><a accesskey="h" href="/index.php?t=help_index&amp;<?php echo _rsid; ?>" title="Help"><img src="/theme/twig/images/top_help.png" alt="" /> Help</a></li>
	<?php echo (($FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304) || $usr->users_opt & 1048576) ? '<li><a href="/index.php?t=finduser&amp;btn_submit=Find&amp;'._rsid.'" title="Members"><img src="/theme/twig/images/top_members.png" alt="" /> Members</a></li>' : ''); ?>
	<?php echo (__fud_real_user__ ? '<li><a href="/index.php?t=uc&amp;'._rsid.'" title="Access the user control panel"><img src="/theme/twig/images/top_profile.png" alt="" /> Control Panel</a></li>' : ($FUD_OPT_1 & 2 ? '<li><a href="/index.php?t=register&amp;'._rsid.'" title="Register"><img src="/theme/twig/images/top_register.png" alt="" /> Register</a></li>' : '')).'
	'.(__fud_real_user__ ? '<li><a href="/index.php?t=login&amp;'._rsid.'&amp;logout=1&amp;SQ='.$GLOBALS['sq'].'" title="Logout"><img src="/theme/twig/images/top_logout.png" alt="" /> Logout [ '.htmlspecialchars($usr->alias, null, null, false).' ]</a></li>' : '<li><a href="/index.php?t=login&amp;'._rsid.'" title="Login"><img src="/theme/twig/images/top_login.png" alt="" /> Login</a></li>'); ?>
	<li><a href="/index.php?t=index&amp;<?php echo _rsid; ?>" title="Home"><img src="/theme/twig/images/top_home.png" alt="" /> Home</a></li>
	<?php echo ($is_a || ($usr->users_opt & 268435456) ? '<li><a href="/adm/index.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'" title="Administration"><img src="/theme/twig/images/top_admin.png" alt="" /> Administration</a></li>' : ''); ?>
</ul>
</div>
<?php echo $tabs; ?>

<table cellspacing="3" cellpadding="3" border="0" class="wa">
<tr>
	<td class="vt"><table border="0" cellspacing="1" cellpadding="2" class="ucPW">
<tr>
	<th colspan="3">Buddy List</th>
</tr>
<?php echo $uc_buddy_ents; ?>
</table></td>

	<td class="wa vt">
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th colspan="3">New Private Messages</th>
</tr>
<?php echo $uc_new_pms; ?>
</table>
<br /><br />
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th colspan="4">Subscribed Forums With New Messages</th>
</tr>
<?php echo $uc_sub_forum; ?>
</table>
<br /><br />
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th colspan="4">Subscribed Topics With New Messages</th>
</tr>
<?php echo $uc_sub_topic; ?>
</table></td>
</tr>
</table>

<br /><div class="ac"><span class="curtime"><b>Current Time:</b> <?php echo strftime('%a %b %d %H:%M:%S %Z %Y', __request_timestamp__); ?></span></div>
<?php echo $page_stats; ?>
<?php echo (!empty($RIGHT_SIDEBAR) ? '
</td><td width="200px" align-"right" valign="top" class="sidebar-right">
	'.$RIGHT_SIDEBAR.'
' : ''); ?>
</td></tr></table>

</div>
<div class="footer ac">
	<b>.::</b>
	<a href="mailto:<?php echo $GLOBALS['ADMIN_EMAIL']; ?>">Contact</a>
	<b>::</b>
	<a href="/index.php?t=index&amp;<?php echo _rsid; ?>">Home</a>
	<b>::.</b>
	<p class="SmallText">Powered by: FUDforum <?php echo $GLOBALS['FORUM_VERSION']; ?>.<br />Copyright &copy;2001-2020 <a href="http://fudforum.org/">FUDforum Bulletin Board Software</a></p>
</div>

</body></html>
