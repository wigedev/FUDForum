<?php
/**
 * copyright            : (C) 2001-2019 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: online_today.php.t 6334 2019-11-14 19:23:19Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
}
function alt_var($key)
{
    if (!isset($GLOBALS['_ALTERNATOR_'][$key])) {
        $args = func_get_args();
        unset($args[0]);
        $GLOBALS['_ALTERNATOR_'][$key] = array('p' => 2, 't' => func_num_args(), 'v' => $args);
        return $args[1];
    }
    $k =& $GLOBALS['_ALTERNATOR_'][$key];
    if ($k['p'] == $k['t']) {
        $k['p'] = 1;
    }
    return $k['v'][$k['p']++];
}

function draw_user_link($login, $type, $custom_color = '')
{
    if ($custom_color) {
        return '<span style="color: ' . $custom_color . '">' . $login . '</span>';
    }

    switch ($type & 1572864) {
        case 0:
        default:
            return $login;
        case 1048576:
            return '<span class="adminColor">' . $login . '</span>';
        case 524288:
            return '<span class="modsColor">' . $login . '</span>';
    }
}

if (!_uid && $FUD_OPT_3 & 262144) {
    std_error('disabled');
}

ses_update_status($usr->sid, 'Viewing the list of users who were on the forum today.');

if (_uid) {
    $admin_cp = $accounts_pending_approval = $group_mgr = $reported_msgs = $custom_avatar_queue = $mod_que = $thr_exch = '';

    if ($usr->users_opt & 524288 || $is_a) {    // is_mod or admin.
        if ($is_a) {
            // Approval of custom Avatars.
            if ($FUD_OPT_1 & 32 && ($avatar_count = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=16777216 AND ' . q_bitand('users_opt', 16777216) . ' > 0'))) {
                $custom_avatar_queue = '| <a href="/adm/admavatarapr.php?S=' . s . '&amp;SQ=' . $GLOBALS['sq'] . '">Custom Avatar Queue</a> <span class="GenTextRed">(' . $avatar_count . ')</span>';
            }

            // All reported messages.
            if ($report_count = q_singleval('SELECT count(*) FROM fud30_msg_report')) {
                $reported_msgs = '| <a href="/index.php?t=reported&amp;' . _rsid . '" rel="nofollow">Reported Messages</a> <span class="GenTextRed">(' . $report_count . ')</span>';
            }

            // All thread exchange requests.
            if ($thr_exchc = q_singleval('SELECT count(*) FROM fud30_thr_exchange')) {
                $thr_exch = '| <a href="/index.php?t=thr_exch&amp;' . _rsid . '">Topic Exchange</a> <span class="GenTextRed">(' . $thr_exchc . ')</span>';
            }

            // All account approvals.
            if ($FUD_OPT_2 & 1024 && ($accounts_pending_approval = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND ' . q_bitand('users_opt', 2097152) . ' > 0 AND id > 0'))) {
                $accounts_pending_approval = '| <a href="/adm/admuserapr.php?S=' . s . '&amp;SQ=' . $GLOBALS['sq'] . '">Accounts Pending Approval</a> <span class="GenTextRed">(' . $accounts_pending_approval . ')</span>';
            } else {
                $accounts_pending_approval = '';
            }

            $q_limit = '';
        } else {
            // Messages reported in moderated forums.
            if ($report_count = q_singleval('SELECT count(*) FROM fud30_msg_report mr INNER JOIN fud30_msg m ON mr.msg_id=m.id INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_mod mm ON t.forum_id=mm.forum_id AND mm.user_id=' . _uid)) {
                $reported_msgs = '| <a href="/index.php?t=reported&amp;' . _rsid . '" rel="nofollow">Reported Messages</a> <span class="GenTextRed">(' . $report_count . ')</span>';
            }

            // Thread move requests in moderated forums.
            if ($thr_exchc = q_singleval('SELECT count(*) FROM fud30_thr_exchange te INNER JOIN fud30_mod m ON m.user_id=' . _uid . ' AND te.frm=m.forum_id')) {
                $thr_exch = '| <a href="/index.php?t=thr_exch&amp;' . _rsid . '">Topic Exchange</a> <span class="GenTextRed">(' . $thr_exchc . ')</span>';
            }

            $q_limit = ' INNER JOIN fud30_mod mm ON f.id=mm.forum_id AND mm.user_id=' . _uid;
        }

        // Messages requiring approval.
        if ($approve_count = q_singleval('SELECT count(*) FROM fud30_msg m INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_forum f ON t.forum_id=f.id ' . $q_limit . ' WHERE m.apr=0 AND f.forum_opt>=2')) {
            $mod_que = '<a href="/index.php?t=modque&amp;' . _rsid . '">Moderation Queue</a> <span class="GenTextRed">(' . $approve_count . ')</span>';
        }
    } else if ($usr->users_opt & 268435456 && $FUD_OPT_2 & 1024 && ($accounts_pending_approval = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND ' . q_bitand('users_opt', 2097152) . ' > 0 AND id > 0'))) {
        $accounts_pending_approval = '| <a href="/adm/admuserapr.php?S=' . s . '&amp;SQ=' . $GLOBALS['sq'] . '">Accounts Pending Approval</a> <span class="GenTextRed">(' . $accounts_pending_approval . ')</span>';
    } else {
        $accounts_pending_approval = '';
    }
    if ($is_a || $usr->group_leader_list) {
        $group_mgr = '| <a href="/index.php?t=groupmgr&amp;' . _rsid . '">Group Manager</a>';
    }

    if ($thr_exch || $accounts_pending_approval || $group_mgr || $reported_msgs || $custom_avatar_queue || $mod_que) {
        $admin_cp = '<br /><span class="GenText fb">Admin:</span> ' . $mod_que . ' ' . $reported_msgs . ' ' . $thr_exch . ' ' . $custom_avatar_queue . ' ' . $group_mgr . ' ' . $accounts_pending_approval . '<br />';
    }
} else {
    $admin_cp = '';
}/* Print number of unread private messages in User Control Panel. */
if (__fud_real_user__ && $FUD_OPT_1 & 1024) {    // PM_ENABLED
    $c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id=' . _uid . ' AND fldr=1 AND read_stamp=0');
    $ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;' . _rsid . '" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> You have <span class="GenTextRed">(' . $c . ')</span> unread ' . convertPlural($c, array('private message', 'private messages')) . '</a></li>' : '<li><a href="/index.php?t=pmsg&amp;' . _rsid . '" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> Private Messaging</a></li>';
} else {
    $ucp_private_msg = '';
}

if (isset($_GET['o'])) {
    switch ($_GET['o']) {
        case 'alias':
            $o = 'u.alias';
            break;
        case 'last_visit':
        default:
            $o = 'u.last_visit';
    }
} else {
    $o = 'u.last_visit';
}

if (isset($_GET['s']) && $_GET['s'] == 'a') {
    $s = 'ASC';
} else {
    $s = 'DESC';
}

$c = uq('SELECT
			u.alias AS login, u.users_opt, u.id, u.last_visit, u.custom_color,
			m.id AS mid, m.subject, m.post_stamp,
			t.forum_id,
			mm.id,
			COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS gco
		FROM fud30_users u
		LEFT JOIN fud30_msg m ON u.u_last_post_id=m.id
		LEFT JOIN fud30_thread t ON m.thread_id=t.id
		LEFT JOIN fud30_mod mm ON mm.forum_id=t.forum_id AND mm.user_id=' . _uid . '
		LEFT JOIN fud30_group_cache g1 ON g1.user_id=' . (_uid ? '2147483647' : '0') . ' AND g1.resource_id=t.forum_id
		LEFT JOIN fud30_group_cache g2 ON g2.user_id=' . _uid . ' AND g2.resource_id=t.forum_id
		WHERE u.last_visit>' . mktime(0, 0, 0) . ' AND ' . (!$is_a ? q_bitand('u.users_opt', 32768) . '=0 AND' : '') . ' u.id!=' . _uid . '
		ORDER BY ' . $o . ' ' . $s);
/*
    array(9) {
           [0]=> string(4) "root" [1]=> string(1) "A" [2]=> string(4) "9944" [3]=> string(10) "1049362510"
               [4]=> string(5) "green" [5]=> string(6) "456557" [6]=> string(33) "Re: Deactivating TCP checksumming"
               [7]=> string(10) "1049299437" [8]=> string(1) "6"
             }
*/

$user_entries = '';
while ($r = db_rowarr($c)) {
    if (!$r[7]) {
        $last_post = 'n/a';
    } else if ($r[10] & 2 || $r[9] || $is_a) {
        $last_post = strftime('%a, %d %B %Y %H:%M', $r[7]) . '<br />
<a href="/index.php?t=' . d_thread_view . '&amp;goto=' . $r[5] . '&amp;' . _rsid . '#msg_' . $r[5] . '">' . $r[6] . '</a>';
    } else {
        $last_post = 'You do not have appropriate permissions needed to see this topic.';
    }

    $user_entries .= '<tr class="' . alt_var('search_alt', 'RowStyleA', 'RowStyleB') . '">
	<td class="GenText"><a href="/index.php?t=usrinfo&amp;id=' . $r[2] . '&amp;' . _rsid . '">' . draw_user_link($r[0], $r[1], $r[4]) . '</a></td>
	<td class="DateText">' . strftime('%H:%M:%S', $r[3]) . '</td>
	<td class="SmallText">' . $last_post . '</td>
</tr>';
}
unset($c);

F()->response->random = get_random_value();
F()->response->aliasSortOrder = $o=='u.alias' && $s=='ASC' ? 'd' : 'a';
F()->response->lastVisitSortOrder = $o=='u.last_visit' && $s=='ASC' ? 'd' : 'a';
F()->response->userEntries = $user_entries;
