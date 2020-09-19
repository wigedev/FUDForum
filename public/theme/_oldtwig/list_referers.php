<?php
/**
 * copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: list_referers.php.t 4994 2010-09-02 17:33:29Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
}
function pager_replace(&$str, $s, $c)
{
    $str = str_replace(['%s', '%c'], [$s, $c], $str);
}

function tmpl_create_pager($start, $count, $total, $arg, $suf = '', $append = 1, $js_pager = 0, $no_append = 0)
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
        } else {
            if (strpos($suf, '//') !== false) {
                $suf = preg_replace('!/+!', '/', $suf);
            }
        }
    } else {
        if (!$no_append) {
            $upfx = '&amp;start=';
        }
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

        $page_pager_data .= !$js_pager ? '&nbsp;<a href="' .
            $page_first_url .
            '" class="PagerLink">&laquo;</a>&nbsp;&nbsp;<a href="' .
            $page_prev_url .
            '" accesskey="p" class="PagerLink">&lsaquo;</a>&nbsp;&nbsp;' : '&nbsp;<a href="javascript://" onclick="' .
            $page_first_url .
            '" class="PagerLink">&laquo;</a>&nbsp;&nbsp;<a href="javascript://" onclick="' .
            $page_prev_url .
            '" class="PagerLink">&lsaquo;</a>&nbsp;&nbsp;';
    }

    $mid = ceil($GLOBALS['GENERAL_PAGER_COUNT'] / 2);

    if ($ttl_pg > $GLOBALS['GENERAL_PAGER_COUNT']) {
        if (($mid + $cur_pg) >= $ttl_pg) {
            $end = $ttl_pg;
            $mid += $mid + $cur_pg - $ttl_pg;
            $st = $cur_pg - $mid;
        } else {
            if (($cur_pg - $mid) <= 0) {
                $st = 0;
                $mid += $mid - $cur_pg;
                $end = $mid + $cur_pg;
            } else {
                $st = $cur_pg - $mid;
                $end = $mid + $cur_pg;
            }
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
            $page_pager_data .= !$js_pager ? '<a href="' .
                $page_page_url .
                '" class="PagerLink">' .
                $st .
                '</a>&nbsp;&nbsp;' : '<a href="javascript://" onclick="' .
                $page_page_url .
                '" class="PagerLink">' .
                $st .
                '</a>&nbsp;&nbsp;';
        } else {
            $st++;
            $page_pager_data .= !$js_pager ? $st . '&nbsp;&nbsp;' : $st . '&nbsp;&nbsp;';
        }
    }

    $page_pager_data = substr($page_pager_data, 0, strlen((!$js_pager ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;')) * -1);

    if (($page_start = $start + $count) < $total) {
        $page_start_2 = ($st - 1) * $count;
        if ($append) {
            $page_next_url = $arg . $upfx . $page_start . $suf;
            // $page_last_url = $arg . $upfx . $page_start_2 . $suf;
            $page_last_url = $arg . $upfx . floor($total - 1 / $count) * $count . $suf;
        } else {
            $page_next_url = $page_last_url = $arg;
            pager_replace($page_next_url, $upfx . $page_start, $count);
            pager_replace($page_last_url, $upfx . $page_start_2, $count);
        }
        $page_pager_data .= !$js_pager ? '&nbsp;&nbsp;<a href="' .
            $page_next_url .
            '" accesskey="n" class="PagerLink">&rsaquo;</a>&nbsp;&nbsp;<a href="' .
            $page_last_url .
            '" class="PagerLink">&raquo;</a>' : '&nbsp;&nbsp;<a href="javascript://" onclick="' .
            $page_next_url .
            '" class="PagerLink">&rsaquo;</a>&nbsp;&nbsp;<a href="javascript://" onclick="' .
            $page_last_url .
            '" class="PagerLink">&raquo;</a>';
    }

    return !$js_pager ? '<span class="SmallText fb">Pages (' .
        $ttl_pg .
        '): [' .
        $page_pager_data .
        ']</span>' : '<span class="SmallText fb">Pages (' . $ttl_pg . '): [' . $page_pager_data . ']</span>';
}

function alt_var($key)
{
    if (!isset($GLOBALS['_ALTERNATOR_'][$key])) {
        $args = func_get_args();
        unset($args[0]);
        $GLOBALS['_ALTERNATOR_'][$key] = ['p' => 2, 't' => func_num_args(), 'v' => $args];
        return $args[1];
    }
    $k =& $GLOBALS['_ALTERNATOR_'][$key];
    if ($k['p'] == $k['t']) {
        $k['p'] = 1;
    }
    return $k['v'][$k['p']++];
}

ses_update_status($usr->sid, 'Browsing referrals');

/* Print number of unread private messages in User Control Panel. */
if (__fud_real_user__ && $FUD_OPT_1 & 1024) {    // PM_ENABLED
    $c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id=' . _uid . ' AND fldr=1 AND read_stamp=0');
    $ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;' .
        _rsid .
        '" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> You have <span class="GenTextRed">(' .
        $c .
        ')</span> unread ' .
        convertPlural($c, ['private message', 'private messages']) .
        '</a></li>' : '<li><a href="/index.php?t=pmsg&amp;' .
        _rsid .
        '" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> Private Messaging</a></li>';
} else {
    $ucp_private_msg = '';
}
if (_uid) {
    $admin_cp = $accounts_pending_approval = $group_mgr = $reported_msgs = $custom_avatar_queue = $mod_que = $thr_exch = '';

    if ($usr->users_opt & 524288 || $is_a) {    // is_mod or admin.
        if ($is_a) {
            // Approval of custom Avatars.
            if ($FUD_OPT_1 & 32 &&
                ($avatar_count = q_singleval(
                    'SELECT count(*) FROM fud30_users WHERE users_opt>=16777216 AND ' .
                    q_bitand('users_opt', 16777216) .
                    ' > 0'
                ))) {
                $custom_avatar_queue = '| <a href="/adm/admavatarapr.php?S=' .
                    s .
                    '&amp;SQ=' .
                    $GLOBALS['sq'] .
                    '">Custom Avatar Queue</a> <span class="GenTextRed">(' .
                    $avatar_count .
                    ')</span>';
            }

            // All reported messages.
            if ($report_count = q_singleval('SELECT count(*) FROM fud30_msg_report')) {
                $reported_msgs = '| <a href="/index.php?t=reported&amp;' .
                    _rsid .
                    '" rel="nofollow">Reported Messages</a> <span class="GenTextRed">(' .
                    $report_count .
                    ')</span>';
            }

            // All thread exchange requests.
            if ($thr_exchc = q_singleval('SELECT count(*) FROM fud30_thr_exchange')) {
                $thr_exch = '| <a href="/index.php?t=thr_exch&amp;' .
                    _rsid .
                    '">Topic Exchange</a> <span class="GenTextRed">(' .
                    $thr_exchc .
                    ')</span>';
            }

            // All account approvals.
            if ($FUD_OPT_2 & 1024 &&
                ($accounts_pending_approval = q_singleval(
                    'SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND ' .
                    q_bitand('users_opt', 2097152) .
                    ' > 0 AND id > 0'
                ))) {
                $accounts_pending_approval = '| <a href="/adm/admuserapr.php?S=' .
                    s .
                    '&amp;SQ=' .
                    $GLOBALS['sq'] .
                    '">Accounts Pending Approval</a> <span class="GenTextRed">(' .
                    $accounts_pending_approval .
                    ')</span>';
            } else {
                $accounts_pending_approval = '';
            }

            $q_limit = '';
        } else {
            // Messages reported in moderated forums.
            if ($report_count = q_singleval(
                'SELECT count(*) FROM fud30_msg_report mr INNER JOIN fud30_msg m ON mr.msg_id=m.id INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_mod mm ON t.forum_id=mm.forum_id AND mm.user_id=' .
                _uid
            )) {
                $reported_msgs = '| <a href="/index.php?t=reported&amp;' .
                    _rsid .
                    '" rel="nofollow">Reported Messages</a> <span class="GenTextRed">(' .
                    $report_count .
                    ')</span>';
            }

            // Thread move requests in moderated forums.
            if ($thr_exchc = q_singleval(
                'SELECT count(*) FROM fud30_thr_exchange te INNER JOIN fud30_mod m ON m.user_id=' .
                _uid .
                ' AND te.frm=m.forum_id'
            )) {
                $thr_exch = '| <a href="/index.php?t=thr_exch&amp;' .
                    _rsid .
                    '">Topic Exchange</a> <span class="GenTextRed">(' .
                    $thr_exchc .
                    ')</span>';
            }

            $q_limit = ' INNER JOIN fud30_mod mm ON f.id=mm.forum_id AND mm.user_id=' . _uid;
        }

        // Messages requiring approval.
        if ($approve_count = q_singleval(
            'SELECT count(*) FROM fud30_msg m INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_forum f ON t.forum_id=f.id ' .
            $q_limit .
            ' WHERE m.apr=0 AND f.forum_opt>=2'
        )) {
            $mod_que = '<a href="/index.php?t=modque&amp;' .
                _rsid .
                '">Moderation Queue</a> <span class="GenTextRed">(' .
                $approve_count .
                ')</span>';
        }
    } else {
        if ($usr->users_opt & 268435456 &&
            $FUD_OPT_2 & 1024 &&
            ($accounts_pending_approval = q_singleval(
                'SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND ' .
                q_bitand('users_opt', 2097152) .
                ' > 0 AND id > 0'
            ))) {
            $accounts_pending_approval = '| <a href="/adm/admuserapr.php?S=' .
                s .
                '&amp;SQ=' .
                $GLOBALS['sq'] .
                '">Accounts Pending Approval</a> <span class="GenTextRed">(' .
                $accounts_pending_approval .
                ')</span>';
        } else {
            $accounts_pending_approval = '';
        }
    }
    if ($is_a || $usr->group_leader_list) {
        $group_mgr = '| <a href="/index.php?t=groupmgr&amp;' . _rsid . '">Group Manager</a>';
    }

    if ($thr_exch || $accounts_pending_approval || $group_mgr || $reported_msgs || $custom_avatar_queue || $mod_que) {
        $admin_cp = '<br /><span class="GenText fb">Admin:</span> ' .
            $mod_que .
            ' ' .
            $reported_msgs .
            ' ' .
            $thr_exch .
            ' ' .
            $custom_avatar_queue .
            ' ' .
            $group_mgr .
            ' ' .
            $accounts_pending_approval .
            '<br />';
    }
} else {
    $admin_cp = '';
}

if (!isset($_GET['start']) || !($start = (int)$_GET['start'])) {
    $start = 0;
}

$page_pager = $referer_entry_data = '';
if (($ttl = q_singleval('SELECT count(DISTINCT(referer_id)) FROM fud30_users WHERE referer_id>0'))) {
    if ($start > $ttl) {
        $start = 0;
    }

    $c = q(
        q_limit(
            'SELECT u2.alias, u2.id, count(*) AS cnt FROM fud30_users u LEFT JOIN fud30_users u2 ON u2.id=u.referer_id WHERE u.referer_id > 0 AND u2.id IS NOT NULL GROUP BY u2.id ORDER BY cnt, u.alias DESC',
            $MEMBERS_PER_PAGE,
            $start
        )
    );
    while ($r = db_rowarr($c)) {
        $refered_entry_data = '';
        $c2 = uq('SELECT alias, id FROM fud30_users WHERE referer_id=' . $r[1]);
        while ($r2 = db_rowarr($c2)) {
            $refered_entry_data .= '<a href="/index.php?t=usrinfo&amp;id=' .
                $r2[1] .
                '&amp;' .
                _rsid .
                '">' .
                $r2[0] .
                '</a> &nbsp;';
        }
        unset($c2);
        $referer_entry_data .= '<tr class="' . alt_var('list_referers_alt', 'RowStyleA', 'RowStyleB') . '">
	<td class="nw GenText vt"><a href="/index.php?t=usrinfo&amp;id=' . $r[1] . '&amp;' . _rsid . '">' . $r[0] . '</a></td>
	<td class="ac GenText">' . $r[2] . '</td>
	<td class="GenText">' . $refered_entry_data . '</td>
</tr>';
    }
    unset($c);

    if ($ttl > $MEMBERS_PER_PAGE) {
        if ($FUD_OPT_2 & 32768) {
            $page_pager = tmpl_create_pager($start, $MEMBERS_PER_PAGE, $ttl, '/index.php/lt/', '/' . _rsid);
        } else {
            $page_pager = tmpl_create_pager($start, $MEMBERS_PER_PAGE, $ttl, '/index.php?t=list_referers&amp;' . _rsid);
        }
    }
}

F()->response->referer_entry_data = $referer_entry_data;
F()->response->page_pager = $page_pager;
