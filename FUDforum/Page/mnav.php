<?php
/**
 * copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: mnav.php.t 5072 2010-11-11 17:12:40Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
}
$GLOBALS['__revfs'] = ['&quot;', '&lt;', '&gt;', '&amp;'];
$GLOBALS['__revfd'] = ['"', '<', '>', '&'];

function reverse_fmt($data)
{
    $s = $d = [];
    foreach ($GLOBALS['__revfs'] as $k => $v) {
        if (strpos($data, $v) !== false) {
            $s[] = $v;
            $d[] = $GLOBALS['__revfd'][$k];
        }
    }

    return $s ? str_replace($s, $d, $data) : $data;
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

function tmpl_draw_select_opt($values, $names, $selected)
{
    $vls = explode("\n", $values);
    $nms = explode("\n", $names);

    if (count($vls) != count($nms)) {
        exit("FATAL ERROR: inconsistent number of values inside a select<br />\n");
    }

    $options = '';
    foreach ($vls as $k => $v) {
        $options .= '<option value="' .
            $v .
            '"' .
            ($v == $selected ? ' selected="selected"' : '') .
            '>' .
            $nms[$k] .
            '</option>';
    }

    return $options;
}

function read_msg_body($off, $len, $id)
{
    if ($off == -1) {    // Fetch from DB and return.
        return q_singleval('SELECT data FROM fud30_msg_store WHERE id=' . $id);
    }

    if (!$len) {    // Empty message.
        return;
    }

    // Open file if it's not already open.
    if (!isset($GLOBALS['__MSG_FP__'][$id])) {
        $GLOBALS['__MSG_FP__'][$id] = fopen($GLOBALS['MSG_STORE_DIR'] . 'msg_' . $id, 'rb');
    }

    // Read from file.
    fseek($GLOBALS['__MSG_FP__'][$id], $off);
    return fread($GLOBALS['__MSG_FP__'][$id], $len);
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

if (!isset($_GET['start']) || !($start = (int)$_GET['start'])) {
    $start = 0;
}
$forum_limiter = isset($_GET['forum_limiter']) ? (string)$_GET['forum_limiter'] : '';
$rng = isset($_GET['rng']) ? (float)$_GET['rng'] : 1;
$rng2 = isset($_GET['rng2']) ? (float)$_GET['rng2'] : 0;
$unit = isset($_GET['u']) ? (int)$_GET['u'] : 86400;
$ppg = $usr->posts_ppg ? $usr->posts_ppg : $POSTS_PER_PAGE;
$subl = !empty($_GET['sub']);

require $FORUM_SETTINGS_PATH . 'cat_cache.inc';

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
}/* Print number of unread private messages in User Control Panel. */
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
if (!isset($th)) {
    $th = 0;
}
if (!isset($frm->id)) {
    $frm = new stdClass();    // Initialize to prevent 'strict standards' notice.
    $frm->id = 0;
}/* Draw search engine selection boxes. */
if ($is_a) {
    $c = uq(
        'SELECT f.id, f.name, c.id
			FROM fud30_fc_view v
			INNER JOIN fud30_forum f ON f.id=v.f
			INNER JOIN fud30_cat c ON f.cat_id=c.id
			WHERE f.url_redirect IS NULL ORDER BY v.id'
    );
} else {
    $c = uq(
        'SELECT f.id, f.name, c.id
			FROM fud30_fc_view v
			INNER JOIN fud30_forum f ON f.id=v.f
			INNER JOIN fud30_cat c ON f.cat_id=c.id
			INNER JOIN fud30_group_cache g1 ON g1.user_id=' .
        (_uid ? '2147483647' : '0') .
        ' AND g1.resource_id=f.id
			LEFT JOIN fud30_mod mm ON mm.forum_id=f.id AND mm.user_id=' .
        _uid .
        '
			LEFT JOIN fud30_group_cache g2 ON g2.user_id=' .
        _uid .
        ' AND g2.resource_id=f.id
			WHERE f.url_redirect IS NULL AND mm.id IS NOT NULL OR ' .
        q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', (1 | 262144)) .
        ' >= ' .
        (1 | 262144) .
        '
			ORDER BY v.id'
    );
}
$oldc = $forum_limit_data = '';
$g = $f = [];
if ($forum_limiter) {
    if ($forum_limiter{0} != 'c') {
        $f[$forum_limiter] = 1;
    } else {
        $g[(int)ltrim($forum_limiter, 'c')] = 1;
    }
}

while ($r = db_rowarr($c)) {
    if ($oldc != $r[2]) {
        foreach ($cat_cache as $k => $i) {
            if ($k == $r[2]) {    // Control break on Catagory ID
                break;
            }
        }
        $forum_limit_data .= '<option value="c' .
            $k .
            '"' .
            (isset($g[$k]) ? ' selected="selected"' : '') .
            '>- ' .
            ($tabw = ($i[0] ? str_repeat('&nbsp;&nbsp;&nbsp;', $i[0]) : '')) .
            $i[1] .
            '</option>';
        $oldc = $r[2];
    }
    $forum_limit_data .= '<option value="' .
        $r[0] .
        '"' .
        (isset($f[$r[0]]) ? ' selected="selected"' : '') .
        '>' .
        $tabw .
        '&nbsp;&nbsp;&nbsp;' .
        $r[1] .
        '</option>';
}
unset($c);

/* User has no permissions to any forum, so as far as they are concerned the search is disabled. */
if (!$forum_limit_data) {
    std_error('disabled');
}

function trim_body($body)
{
    /* Remove stuff in old bad quote tags - remove in future release. */
    while (($p = strpos(
            $body,
            '<table border="0" align="center" width="90%" cellpadding="3" cellspacing="1"><tr><td class="SmallText"><b>'
        )) !== false) {
        if (($pos = strpos($body, '<br></td></tr></table>', $p)) === false) {
            $pos = strpos($body, '<br /></td></tr></table>', $p);
            if ($pos === false) {
                break;
            }
            $e = $pos + strlen('<br /></td></tr></table>');
        } else {
            $e = $pos + strlen('<br></td></tr></table>');
        }
        $body = substr($body, 0, $p) . substr($body, $e);
    }

    /* Remove stuff in quotes */
    while (preg_match('!<cite>(.*?)</cite><blockquote>(.*?)</blockquote>!is', $body)) {
        $body = preg_replace('!<cite>(.*?)</cite><blockquote>(.*?)</blockquote>!is', '', $body);
    }

    $body = strip_tags($body);
    $body_len = strlen($body);

    if ($body_len > $GLOBALS['MNAV_MAX_LEN']) {
        $startpos = 0;
        $srch = isset($_GET['srch']) ? trim((string)$_GET['srch']) : '';
        if (!empty($srch)) {
            // Focus on first search term.
            if (function_exists('mb_substr')) {
                $startpos = mb_stripos($body, strtok($srch, ' '));
            } else {
                $startpos = stripos($body, strtok($srch, ' '));
            }
            $startpos = $startpos - 45; // Move back for a bit of context.
            if ($body_len - $startpos < $GLOBALS['MNAV_MAX_LEN']) {
                $startpos = $body_len - $GLOBALS['MNAV_MAX_LEN'];
            }
            if ($startpos < 0) {
                $startpos = 0;
            }
        }

        // Move to starting position.
        if (function_exists('mb_substr')) {
            $body = mb_substr($body, $startpos);
        } else {
            $body = substr($body, $startpos);
        }
        $body = '…' . preg_replace('/^\w+\s/', '', $body);

        // Cut off after max length.
        if (preg_match('/^(.{1,' . $GLOBALS['MNAV_MAX_LEN'] . '})\b/su', $body, $match)) {
            $body = $match[0] . '…';
        } else {
            $body = mb_substr($body, 0, $GLOBALS['MNAV_MAX_LEN']) . '…';
        }
    }
    return $body;
}

$TITLE_EXTRA = ': Message Navigator';

ses_update_status($usr->sid, 'Browsing Messages using <a href="/index.php?t=mnav">Message Navigator</a>');

if ($forum_limiter) {
    if ($forum_limiter[0] != 'c') {
        $qry_lmt = ' AND f.id=' . (int)$forum_limiter . ' ';
    } else {
        $qry_lmt = ' AND c.id=' . (int)substr($forum_limiter, 1) . ' ';
    }
} else {
    $qry_lmt = '';
}

$mnav_time_unit = tmpl_draw_select_opt(
    "60\n3600\n86400\n604800\n2635200",
    "Minute(s)\nHour(s)\nDay(s)\nWeek(s)\nMonth(s)",
    $unit
);

$mnav_pager = '';
if (!$rng) {
    $rng = '';
    $unit = 86400;
    $mnav_data = '<br />
<div class="ctb">
<table cellspacing="1" cellpadding="2" class="mnavWarnTbl">
<tr>
	<td class="GenTextRed">You must enter a valid date range. This value can contain a decimal point, (0.12) but it must be greater than zero.</td>
</tr>
</table>
</div>';
} else {
    if ($unit <= 0) {
        $rng = '';
        $unit = 86400;
        $mnav_data = '<br />
<div class="ctb">
<table cellspacing="1" cellpadding="2" class="mnavWarnTbl">
<tr>
	<td class="GenTextRed">You must specify a valid time unit.</td>
</tr>
</table>
</div>';
    } else {
        if (($mage = round($rng * $unit)) > ($MNAV_MAX_DATE * 86400) && $MNAV_MAX_DATE > 0) {
            $mnav_data = '<br />
<div class="ctb">
<table cellspacing="1" cellpadding="2" class="mnavWarnTbl">
<tr>
	<td class="GenTextRed">The date range you specified is larger than the one allowed by the administrator. Try a smaller range of dates.</td>
</tr>
</table>
</div>';
        } else {
            if (isset($_GET['u'])) {
                $tm = __request_timestamp__ - $mage;

                if ($rng2 > 0) {
                    $date_limit = ' AND m.post_stamp < ' . (__request_timestamp__ - ($rng2 * $unit));
                } else {
                    $date_limit = '';
                }

                if (_uid && $subl) {
                    if ($sf = db_all('SELECT forum_id FROM fud30_forum_notify WHERE user_id=' . _uid)) {
                        $qry_lmt .= ' AND f.id IN(' . implode(',', $sf) . ') ';
                    } else {
                        $qry_lmt .= ' AND f.id=-1 ';
                    }
                }

                $c = q(
                    q_limit(
                        'SELECT /*!40000 SQL_CALC_FOUND_ROWS */ u.alias, f.name AS forum_name, f.id AS forum_id,
				m.poster_id, m.id, m.thread_id, m.subject, m.foff, m.length, m.post_stamp, m.file_id, m.icon
				FROM fud30_msg m
				INNER JOIN fud30_thread t ON m.thread_id=t.id
				INNER JOIN fud30_forum f ON t.forum_id=f.id
				INNER JOIN fud30_cat c ON f.cat_id=c.id
				INNER JOIN fud30_group_cache g1 ON g1.user_id=' .
                        (_uid ? '2147483647' : '0') .
                        ' AND g1.resource_id=f.id
				LEFT JOIN fud30_users u ON m.poster_id=u.id
				LEFT JOIN fud30_mod mm ON mm.forum_id=f.id AND mm.user_id=' .
                        _uid .
                        '
				LEFT JOIN fud30_group_cache g2 ON g2.user_id=' .
                        _uid .
                        ' AND g2.resource_id=f.id
			WHERE
				m.post_stamp > ' .
                        $tm .
                        ' ' .
                        $date_limit .
                        ' AND m.apr=1 ' .
                        $qry_lmt .
                        '
				' .
                        ($is_a ? '' : ' AND (mm.id IS NOT NULL OR ' .
                            q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 2) .
                            ' > 0)') .
                        '
				ORDER BY m.thread_id, t.forum_id, m.post_stamp DESC',
                        $ppg,
                        $start
                    )
                );

                $oldf = $oldt = 0;
                $mnav_data = '<div class="ctb">
<table cellspacing="0" cellpadding="0" class="ContentTable">';
                while ($r = db_rowobj($c)) {
                    if ($oldf != $r->forum_id) {
                        $mnav_data .= '<tr><th colspan="3"> Forum: <a class="thLnk" href="/index.php?t=' .
                            t_thread_view .
                            '&amp;frm_id=' .
                            $r->forum_id .
                            '&amp;' .
                            _rsid .
                            '"><span class="lg">' .
                            htmlspecialchars($r->forum_name, null, null, false) .
                            '</span></a></th></tr>';
                        $oldf = $r->forum_id;
                    }
                    if ($oldt != $r->thread_id) {
                        $mnav_data .= '<tr><th class="RowStyleC">&nbsp;&nbsp;&nbsp;</th><th colspan="2"> Topic: <a class="thLnk" href="/index.php?t=' .
                            d_thread_view .
                            '&amp;goto=' .
                            $r->id .
                            '&amp;' .
                            _rsid .
                            '#msg_' .
                            $r->id .
                            '">' .
                            $r->subject .
                            '</a></th></tr>';
                        $oldt = $r->thread_id;
                    }
                    $mnav_data .= '<tr>
	<td class="RowStyleC">&nbsp;&nbsp;&nbsp;</td><td class="RowStyleC">&nbsp;&nbsp;&nbsp;</td>
	<td>
		<table cellspacing="0" cellpadding="2" border="0" class="mnavMsg">
		<tr class="mnavH">
			<td class="nw al"><a href="/index.php?t=' .
                        d_thread_view .
                        '&amp;goto=' .
                        $r->id .
                        '&amp;' .
                        _rsid .
                        '#msg_' .
                        $r->id .
                        '">' .
                        $r->subject .
                        '</a></td>
			<td class="TopBy wa ac">Posted By: ' .
                        (!empty($r->poster_id) ? '<a href="/index.php?t=usrinfo&amp;id=' .
                            $r->poster_id .
                            '&amp;' .
                            _rsid .
                            '">' .
                            htmlspecialchars($r->alias, null, null, false) .
                            '</a>' : $GLOBALS['ANON_NICK'] . '') .
                        '</td>
			<td class="DateText nw ar">' .
                        strftime('%a, %d %B %Y %H:%M', $r->post_stamp) .
                        '</td>
		</tr>
		<tr class="mnavM SmallText">
			<td colspan="3">' .
                        trim_body(read_msg_body($r->foff, $r->length, $r->file_id)) .
                        ' <a href="/index.php?t=' .
                        d_thread_view .
                        '&amp;goto=' .
                        $r->id .
                        '&amp;' .
                        _rsid .
                        '#msg_' .
                        $r->id .
                        '">More &raquo;&raquo;</a></td>
		</tr>
		</table>
	</td>
</tr>';
                }
                unset($c);

                if (($total = (int)q_singleval('SELECT /*!40000 FOUND_ROWS(), */ -1')) < 0) {
                    $total = q_singleval(
                        'SELECT count(*) FROM fud30_msg m
					INNER JOIN fud30_thread t ON m.thread_id=t.id
					INNER JOIN fud30_forum f ON t.forum_id=f.id
					INNER JOIN fud30_cat c ON f.cat_id=c.id
					INNER JOIN fud30_group_cache g1 ON g1.user_id=' .
                        (_uid ? '2147483647' : '0') .
                        ' AND g1.resource_id=f.id
					LEFT JOIN fud30_mod mm ON mm.forum_id=f.id AND mm.user_id=' .
                        _uid .
                        '
					LEFT JOIN fud30_group_cache g2 ON g2.user_id=' .
                        _uid .
                        ' AND g2.resource_id=f.id
				WHERE
					m.post_stamp > ' .
                        $tm .
                        ' ' .
                        $date_limit .
                        ' AND m.apr=1 ' .
                        $qry_lmt .
                        '
					' .
                        ($is_a ? '' : ' AND (mm.id IS NOT NULL OR ' .
                            q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 2) .
                            ' > 0)')
                    );
                }

                if (!$total) {
                    $mnav_data = '<div class="GenText mnavNoRes ac"><p>There are no messages matching the query.</p></div>';
                } else {
                    $mnav_data .= '</table>
</div>';

                    /* Handle pager if needed. */
                    if ($total > $ppg) {
                        if ($FUD_OPT_2 & 32768) {
                            $mnav_pager = tmpl_create_pager(
                                $start,
                                $ppg,
                                $total,
                                '/index.php/ma/' . $rng . '/' . $rng2 . '/' . $unit . '/',
                                '/' . $subl . '/' . _rsid
                            );
                        } else {
                            $mnav_pager = tmpl_create_pager(
                                $start,
                                $ppg,
                                $total,
                                '/index.php?t=mnav&amp;rng=' .
                                $rng .
                                '&amp;u=' .
                                $unit .
                                '&amp;' .
                                _rsid .
                                '&amp;forum_limiter=' .
                                $forum_limiter .
                                '&amp;rng2=' .
                                $rng2 .
                                '&amp;sub=' .
                                $subl
                            );
                        }
                    }
                }
            } else {
                $mnav_data = '';
            }
        }
    }
}

F()->response->rng = $rng;
F()->response->rng2 = $rng2;
F()->response->mnavTimeUnit = $mnav_time_unit;
F()->response->forumLimitData = $forum_limit_data;
F()->response->altVar = alt_var('color_alt', 'RowStyleA', 'RowStyleB');
F()->response->subl = $subl;
F()->response->mnavData = $mnav_data;
F()->response->mnavPager = $mnav_pager;
