<?php
/**
* copyright            : (C) 2001-2011 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: actions.php.t 5260 2011-05-11 16:00:55Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
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
}function draw_user_link($login, $type, $custom_color='')
{
	if ($custom_color) {
		return '<span style="color: '.$custom_color.'">'.$login.'</span>';
	}

	switch ($type & 1572864) {
		case 0:
		default:
			return $login;
		case 1048576:
			return '<span class="adminColor">'.$login.'</span>';
		case 524288:
			return '<span class="modsColor">'.$login.'</span>';
	}
}function &get_all_read_perms($uid, $mod)
{
	$limit = array(0);

	$r = uq('SELECT resource_id, group_cache_opt FROM fud30_group_cache WHERE user_id='. _uid);
	while ($ent = db_rowarr($r)) {
		$limit[$ent[0]] = $ent[1] & 2;
	}
	unset($r);

	if (_uid) {
		if ($mod) {
			$r = uq('SELECT forum_id FROM fud30_mod WHERE user_id='. _uid);
			while ($ent = db_rowarr($r)) {
				$limit[$ent[0]] = 2;
			}
			unset($r);
		}

		$r = uq('SELECT resource_id FROM fud30_group_cache WHERE resource_id NOT IN ('. implode(',', array_keys($limit)) .') AND user_id=2147483647 AND '. q_bitand('group_cache_opt', 2) .' > 0');
		while ($ent = db_rowarr($r)) {
			if (!isset($limit[$ent[0]])) {
				$limit[$ent[0]] = 2;
			}
		}
		unset($r);
	}

	return $limit;
}

function perms_from_obj($obj, $adm)
{
	$perms = 1|2|4|8|16|32|64|128|256|512|1024|2048|4096|8192|16384|32768|262144;

	if ($adm || $obj->md) {
		return $perms;
	}

	return ($perms & $obj->group_cache_opt);
}

function make_perms_query(&$fields, &$join, $fid='')
{
	if (!$fid) {
		$fid = 'f.id';
	}

	if (_uid) {
		$join = ' INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id='. $fid .' LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id='. $fid .' ';
		$fields = ' COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS group_cache_opt ';
	} else {
		$join = ' INNER JOIN fud30_group_cache g1 ON g1.user_id=0 AND g1.resource_id='. $fid .' ';
		$fields = ' g1.group_cache_opt ';
	}
}

	if (!($FUD_OPT_1 & 536870912) || (!_uid && $FUD_OPT_3 & 131072)) {
		// Test ACTION_LIST_ENABLED and NO_ANON_ACTION_LIST.
		std_error('disabled');
	}

	ses_update_status($usr->sid, 'Snooping on other people, just like you');

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
	}

	if (isset($_GET['o'])) {
		switch ($_GET['o']) {
			case 'alias':	$o = 'u.alias'; break;
			case 'ip':	$o = 's.ip_addr'; break;
			case 'time':
			default:	$o = 's.time_sec';
		}
	} else {
		$o = 'u.alias';
	}

	if (isset($_GET['s']) && $_GET['s'] == 'a') {
		$s = 'ASC';
	} else {
		$s = 'DESC';
	}

	$limit = &get_all_read_perms(_uid, ($usr->users_opt & 524288));

	$c = uq('SELECT
			s.action, s.user_id, s.forum_id,
			u.alias, u.custom_color, s.time_sec, s.ip_addr, u.users_opt,
			m.id, m.subject, m.post_stamp,
			t.forum_id,
			mm1.id, mm2.id
		FROM fud30_ses s
		LEFT JOIN fud30_users u ON s.user_id=u.id
		LEFT JOIN fud30_msg m ON u.u_last_post_id=m.id
		LEFT JOIN fud30_thread t ON m.thread_id=t.id
		LEFT JOIN fud30_mod mm1 ON mm1.forum_id=t.forum_id AND mm1.user_id='. _uid .'
		LEFT JOIN fud30_mod mm2 ON mm2.forum_id=s.forum_id AND mm2.user_id='. _uid .'
		WHERE s.time_sec>'. (__request_timestamp__ - ($LOGEDIN_TIMEOUT * 60)) .' AND s.user_id!='. _uid .'
		ORDER BY '. $o .' '. $s);

	$action_data = ''; $uc = 0;
	while ($r = db_rowarr($c)) {
		++$uc; // Update loggedin user count.

		if ($r[7] & 32768 && !$is_a) {	// Hide invisible_mode users.
			continue;
		}

		if ($r[3]) {
			$user_login = '<a href="/index.php?t=usrinfo&id='.$r[1].'&'._rsid.'">'.draw_user_link($r[3], $r[6], $r[4]).'</a>';

			if (!$r[10]) {
				$last_post = 'n/a';
			} else {
				$last_post = (!$is_a && !$r[12] && empty($limit[$r[11]])) ? 'You do not have appropriate permissions needed to see this topic.' : strftime('%a, %d %B %Y %H:%M', $r[10]).'
<br />
<a href="/index.php?t='.d_thread_view.'&goto='.$r[8].'&'._rsid.'#msg_'.$r[8].'">'.$r[9].'</a>';
			}
		} else {
			$user_login = $GLOBALS['ANON_NICK'];
			$last_post = 'n/a';
		}

		if (!$r[2] || ($is_a || !empty($limit[$r[2]]) || $r[13])) {
			if ($FUD_OPT_2 & 32768) {	// USE_PATH_INFO
				if (($p = strpos($r[0], 'href="')) !== false) {
					$p += 6;
					$p = substr($r[0], $p, (strpos($r[0], '"', $p) - $p));

					if ($p{strlen($p) - 1} == '/') {
						$tmp = explode('/', substr(str_replace('/index.php', '', $p), 1, -1));
						if ($FUD_OPT_1 & 128) {	// SESSION_USE_URL
							array_pop($tmp);
						}
						if ($FUD_OPT_2 & 8192) {	// TRACK_REFERRALS
							array_pop($tmp);
						}
						$tmp[] = _rsid;
						$sn = '/index.php/'. implode('/', $tmp);
					} else {
						$sn = $p .'/'. _rsid;
					}
					$action = str_replace($p, $sn, $r[0]);
				} else {
					$action = $r[0];
				}
			} else {
				if (($p = strpos($r[0], '?')) !== false) {
					$action = substr_replace($r[0], '?'. _rsid .'&', $p, 1);
				} else if (($p = strpos($r[0], '.php')) !== false) {
					$action = substr_replace($r[0], '.php?'. _rsid .'&', $p, 4);
				} else {
					$action = $r[0];
				}
			}
		} else {
			$action = 'You do not have appropriate permissions needed to see this topic.';
		}

		$ip_addr = $r[6];
		$action_data .= '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="GenText">'.$user_login.'</td>
	<td class="GenText">'.$action.'</td>
	'.($is_a ? '<td class="SmallText"><a href="/index.php?t=ip&amp;ip='.$ip_addr.'&amp;'._rsid.'">'.$ip_addr.'</a></td>' : '' ) .'
	<td class="DateText">'.strftime('%H:%M:%S', $r[5]).'</td>
	<td class="SmallText">'.$last_post.'</td>
</tr>';
	}
	unset($c);

if ($FUD_OPT_2 & 2 || $is_a) {	// PUBLIC_STATS is enabled or Admin user.
	$page_gen_time = number_format(microtime(true) - __request_timestamp_exact__, 5);
	$page_stats = $FUD_OPT_2 & 2 ? '<br /><div class="SmallText al">Total time taken to generate the page: '.convertPlural($page_gen_time, array(''.$page_gen_time.' seconds')).'</div>' : '<br /><div class="SmallText al">Total time taken to generate the page: '.convertPlural($page_gen_time, array(''.$page_gen_time.' seconds')).'</div>';
} else {
	$page_stats = '';
}

F()->response->s = ($o=='s.time_sec' && $s=='ASC') ? 'd' : 'a' ; // TODO: Give 'o' and 's' meaningful names
F()->response->action_data = $action_data;
