<?php
/**
 * copyright            : (C) 2001-2019 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: index.php.t 6280 2019-05-25 15:29:51Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
}
$collapse = $usr->cat_collapse_status ? unserialize($usr->cat_collapse_status) : [];
$cat_id = !empty($_GET['cat']) ? (int)$_GET['cat'] : 0;
$frm_id = !empty($_GET['frm_id']) ? (int)$_GET['frm_id'] : 0;

if ($cat_id && !empty($collapse[$cat_id])) {
    $collapse[$cat_id] = 0;
}

require $FORUM_SETTINGS_PATH . 'idx.inc';
if (!isset($cidxc[$cat_id])) {
    $cat_id = 0;
}

$cbuf = $forum_list_table_data = $cat_path = '';

if ($cat_id) {
    $cid = $cat_id;
    while (($cid = $cidxc[$cid][4]) > 0) {
        $cat_path = '&nbsp;&raquo; <a href="/index.php?t=i&amp;cat=' .
            $cid .
            '&amp;' .
            _rsid .
            '">' .
            $cidxc[$cid][1] .
            '</a>' .
            $cat_path;
    }
    $variables['cat_path'] = $cat_path = '<br />
<a href="/index.php?t=i&amp;' . _rsid . '">Home</a>
' . $cat_path . '&nbsp;&raquo; <b>' . $cidxc[$cat_id][1] . '</b>';
}

/* List of fetched fields & their ids
  0	msg.subject,
  1	msg.id AS msg_id,
  2	msg.post_stamp,
  3	users.id AS user_id,
  4	users.alias
  5	forum.cat_id,
  6	forum.forum_icon
  7	forum.id
  8	forum.last_post_id
  9	forum.moderators
  10	forum.name
  11	forum.descr
  12	forum.url_redirect
  13	forum.post_count
  14	forum.thread_count
  15	forum_read.last_view
  16	is_moderator
  17	read perm
  18	is the category using compact view
*/
$c = uq(
    'SELECT
				m.subject, m.id, m.post_stamp,
				u.id, u.alias,
				f.cat_id, f.forum_icon, f.id, f.last_post_id, f.moderators, f.name, f.descr, f.url_redirect, f.post_count, f.thread_count,
				' .
    (_uid ? 'fr.last_view, mo.id, COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS group_cache_opt' : '0,0,g1.group_cache_opt') .
    ',
				c.cat_opt
			FROM fud30_fc_view v
			INNER JOIN fud30_cat c ON c.id=v.c
			INNER JOIN fud30_forum f ON f.id=v.f
			INNER JOIN fud30_group_cache g1 ON g1.user_id=' .
    (_uid ? 2147483647 : 0) .
    ' AND g1.resource_id=f.id
			LEFT JOIN fud30_msg m ON f.last_post_id=m.id
			LEFT JOIN fud30_users u ON u.id=m.poster_id ' .
    (_uid ? ' LEFT JOIN fud30_forum_read fr ON fr.forum_id=f.id AND fr.user_id=' .
        _uid .
        ' LEFT JOIN fud30_mod mo ON mo.user_id=' .
        _uid .
        ' AND mo.forum_id=f.id LEFT JOIN fud30_group_cache g2 ON g2.user_id=' .
        _uid .
        ' AND g2.resource_id=f.id' : '') .
    ' WHERE f.parent = ' .
    $frm_id .
    ((!$is_a || $cat_id) ? ' AND ' : '') .
    ($is_a ? '' : (_uid ? ' (mo.id IS NOT NULL OR (' .
        q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 1) .
        ' > 0))' : ' (' . q_bitand('g1.group_cache_opt', 1) . ' > 0)')) .
    ($cat_id ? ($is_a ? '' : ' AND ') . ' v.c IN(' . implode(',', ($cf = $cidxc[$cat_id][5])) . ') ' : '') .
    ' ORDER BY v.id'
);

$post_count = $thread_count = $last_msg_id = $cat = 0;
while ($r = db_rowarr($c)) {
    /* Increase thread & post count. */
    $post_count += $r[13];
    $thread_count += $r[14];

    $cid = (int)$r[5];

    if ($cat != $cid && !$frm_id) {
        if ($cbuf) { /* If previous category was using compact view, print forum row. */
            if (empty($collapse[$i[4]])) { /* Only show if parent is not collapsed as well. */
                $forum_list_table_data .= '<tr class="row child-c' . $cat . '">
	<td class="RowStyleA wo hide2">&nbsp;</td>
	<td class="RowStyleB ac wo hide2">&nbsp;</td>
	<td  class="RowStyleA wa" colspan="4">Available Forums:' . $cbuf . '</td>
</tr>';
            }
            $cbuf = '';
        }

        foreach ($cidxc as $k => $i) {
            /* 2nd check ensures that we don't end up displaying categories without any children. */
            if (($cat_id && !isset($cf[$k])) || ($cid != $k && $i[4] >= $cidxc[$cid][4])) {
                continue;
            }

            /* If parent category is collapsed, hide child category. */
            if ($i[4] && !empty($collapse[$i[4]])) {
                $collapse[$k] = 1;
            }

            if ($k == $cid) {
                break;    // Got it!
            }
        }
        $cat = $cid;
        if ($i[3] & 1 && $k != $cat_id && !($i[3] & 4)) {
            if (!isset($collapse[$k])) {
                $collapse[$k] = !($i[3] & 2);
            }
            $forum_list_table_data .= '<tr id="c' .
                $r[5] .
                '" style="display: table-row;">
	<td class="CatDesc ' .
                (empty($collapse[$cid]) ? 'expanded' : 'collapsed') .
                '" colspan="5" style="padding-left: ' .
                ($i[0] ? $i[0] * 20 : '0') .
                'px;">
		<a href="/index.php?t=index&amp;cat=' .
                $k .
                '&amp;' .
                _rsid .
                '" class="CatLink">' .
                $i[1] .
                '</a> ' .
                $i[2] .
                '
	</td>
	<td class="CatDesc hide1">
	' .
                (key($cidxc) ? '<a href="javascript://" onclick=\'nextCat("c' .
                    $k .
                    '")\'><img src="/theme/twig/images/down.png" alt="" border="0" style="vertical-align: top; float: right;" /></a>' : '') .
                '
	' .
                ($cat ? '<a href="javascript://" onclick=\'prevCat("c' .
                    $k .
                    '")\'><img src="/theme/twig/images/up.png" border="0" alt="" style="vertical-align: top; float: right;" /></a>' : '') .
                '
</td>
</tr>';
        } else {
            if ($i[3] & 4) {
                ++$i[0];
            }
            $forum_list_table_data .= '<tr id="c' .
                $r[5] .
                '" style="display: table-row;">
	<td class="CatDesc CatLockPad" colspan="5" style="padding-left: ' .
                ($i[0] ? $i[0] * 20 : '0') .
                'px;">
		<span class="CatLockedName"><a href="/index.php?t=index&amp;cat=' .
                $k .
                '&amp;' .
                _rsid .
                '" class="CatLink">' .
                $i[1] .
                '</a></span> ' .
                $i[2] .
                '
	</td>
	<td class="CatDesc hide1">
	' .
                (key($cidxc) ? '<a href="javascript://" onclick=\'nextCat("c' .
                    $k .
                    '")\'><img src="/theme/twig/images/down.png" alt="" border="0" style="vertical-align: top; float: right;" /></a>' : '') .
                '
	' .
                ($cat ? '<a href="javascript://" onclick=\'prevCat("c' .
                    $k .
                    '")\'><img src="/theme/twig/images/up.png" border="0" alt="" style="vertical-align: top; float: right;" /></a>' : '') .
                '
</td>
</tr>';
        }
    }

    /* Compact category view (ignore when expanded). */
    if ($r[18] & 4 && $cat_id != $cid) {
        $cbuf .= '&nbsp; ' .
            (_uid && $r[15] < $r[2] && $usr->last_read < $r[2] ? '**' : '') .
            '
<a href="' .
            (empty($r[12]) ? '/index.php?t=' . t_thread_view . '&amp;frm_id=' . $r[7] . '&amp;' . _rsid : $r[12]) .
            '">' .
            $r[10] .
            '</a>';
        continue;
    }

    /* Visible forum with no 'read' permission. */
    if (!($r[17] & 2) && !$is_a && !$r[16]) {
        $forum_list_table_data .= '<tr style="display: ' .
            (empty($collapse[$cid]) ? 'table-row' : 'none') .
            '" class="child-c' .
            $r[5] .
            '">
	<td class="RowStyleA" colspan="6">' .
            $r[10] .
            ($r[11] ? '<br />' . $r[11] : '') .
            '</td>
</tr>';
        continue;
    }

    /* Code to determine the last post id for 'latest' forum message. */
    if ($r[8] > $last_msg_id) {
        $last_msg_id = $r[8];
    }

    if (!_uid) { /* Anon user. */
        $forum_read_indicator = '<img title="Only registered forum members can track read &amp; unread messages" src="/theme/twig/images/existing_content.png" alt="Only registered forum members can track read &amp; unread messages" />';
    } else {
        if ($r[15] < $r[2] && $usr->last_read < $r[2]) {
            $forum_read_indicator = '<img title="New messages" src="/theme/twig/images/new_content.png" alt="New messages" />';
        } else {
            $forum_read_indicator = '<img title="No new messages" src="/theme/twig/images/existing_content.png" alt="No new messages" />';
        }
    }

    if ($r[9] && ($mods = unserialize($r[9]))) {
        $moderators = '';    // List of forum moderators.
        $modcount = 0;        // Use singular or plural message form.

        foreach ($mods as $k => $v) {
            $moderators .= '<a href="/index.php?t=usrinfo&amp;id=' . $k . '&amp;' . _rsid . '">' . $v . '</a> &nbsp;';
            $modcount++;
        }
        $moderators = '<div class="TopBy"><b>' .
            convertPlural($modcount, ['Moderator', 'Moderators']) .
            ':</b> ' .
            $moderators .
            '</div>';
    } else {
        $moderators = '&nbsp;';
    }

    $forum_list_table_data .= '<tr style="display: ' .
        (empty($collapse[$cid]) ? 'table-row' : 'none') .
        '" class="row child-c' .
        $r[5] .
        '">
	<td class="RowStyleA wo hide2">' .
        ($r[6] ? '<img src="/images/forum_icons/' . $r[6] . '" alt="Forum Icon" />' : '&nbsp;') .
        '</td>
	<td class="RowStyleB ac wo hide2">' .
        (empty($r[12]) ? $forum_read_indicator : '<img title="Redirection" src="/theme/twig/images/moved.png" alt="" />') .
        '</td>
	<td class="RowStyleA wa"><a href="' .
        (empty($r[12]) ? '/index.php?t=' . t_thread_view . '&amp;frm_id=' . $r[7] . '&amp;' . _rsid : $r[12]) .
        '" class="big">' .
        $r[10] .
        '</a>' .
        ($r[11] ? '<br />' . $r[11] : '') .
        $moderators .
        '</td>
	<td class="RowStyleB ac hide1">' .
        (empty($r[12]) ? $r[13] : '--') .
        '</td>
	<td class="RowStyleB ac hide1">' .
        (empty($r[12]) ? $r[14] : '--') .
        '</td>
	<td class="RowStyleA ac nw hide2">' .
        (empty($r[12]) ? ($r[8] ? '<span class="DateText">' .
            strftime('%a, %d %B %Y', $r[2]) .
            '</span><br />By: ' .
            ($r[3] ? '<a href="/index.php?t=usrinfo&amp;id=' .
                $r[3] .
                '&amp;' .
                _rsid .
                '">' .
                $r[4] .
                '</a>' : $GLOBALS['ANON_NICK']) .
            ' <a href="/index.php?t=' .
            d_thread_view .
            '&amp;goto=' .
            $r[8] .
            '&amp;' .
            _rsid .
            '#msg_' .
            $r[8] .
            '"><img title="' .
            $r[0] .
            '" src="/theme/twig/images/goto.gif" alt="' .
            $r[0] .
            '" /></a>' : 'n/a') : '--') .
        '</td>
</tr>';
}
unset($c);

if ($cbuf) { /* If previous category was using compact view, print forum row. */
    $forum_list_table_data .= '<tr class="row child-c' . $cat . '">
	<td class="RowStyleA wo hide2">&nbsp;</td>
	<td class="RowStyleB ac wo hide2">&nbsp;</td>
	<td  class="RowStyleA wa" colspan="4">Available Forums:' . $cbuf . '</td>
</tr>';
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

$RSS = ($FUD_OPT_2 & 1048576 ? '<link rel="alternate" type="application/rss+xml" title="Syndicate this forum (XML)" href="/feed.php?mode=m&amp;l=1&amp;basic=1" />
' : '');
ses_update_status($usr->sid, 'Browsing the <a href="/index.php?t=index">forum list</a>');

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
if (!isset($th)) {
    $th = 0;
}
if (!isset($frm->id)) {
    $frm = new stdClass();    // Initialize to prevent 'strict standards' notice.
    $frm->id = 0;
}

$TITLE_EXTRA = ': Welcome to the forum';

// @TODO: Merge with forum level announcements in thread_view_common.inc.t.
/* Display non-forum related announcements. */
include $GLOBALS['FORUM_SETTINGS_PATH'] . 'announce_cache';
$announcements = '';
foreach ($announce_cache as $a_id => $a) {
    if (!_uid && $a['ann_opt'] & 2) {
        continue;    // Only for logged in users.
    }
    if (_uid && $a['ann_opt'] & 4) {
        continue;    // Only for anonomous users.
    }
    if ($a['start'] <= __request_timestamp__ && $a['end'] >= __request_timestamp__) {
        $announce_subj = $a['subject'];
        $announce_body = $a['text'];
        if (defined('plugins')) {
            list($announce_subj, $announce_body) = plugin_call_hook('ANNOUNCEMENT', [$announce_subj, $announce_body]);
        }
        $announcements .= '<fieldset class="AnnText">
	<legend class="AnnSubjText">' . $announce_subj . '</legend>
	' . $announce_body . '
</fieldset>';
    }
}

function &rebuild_stats_cache($last_msg_id)
{
    $tm_expire = __request_timestamp__ - ($GLOBALS['LOGEDIN_TIMEOUT'] * 60);

    $obj = new stdClass();    // Initialize to prevent 'strict standards' notice.
    list($obj->last_user_id, $obj->user_count) = db_saq('SELECT MAX(id), count(*)-1 FROM fud30_users');

    $obj->online_users_anon = q_singleval(
        'SELECT count(*) FROM fud30_ses s WHERE time_sec>' . $tm_expire . ' AND user_id>2000000000'
    );
    $obj->online_users_hidden = q_singleval(
        'SELECT count(*) FROM fud30_ses s INNER JOIN fud30_users u ON u.id=s.user_id WHERE s.time_sec>' .
        $tm_expire .
        ' AND ' .
        q_bitand('u.users_opt', 32768) .
        '>0'
    );
    $obj->online_users_reg = q_singleval(
        'SELECT count(*) FROM fud30_ses s INNER JOIN fud30_users u ON u.id=s.user_id WHERE s.time_sec>' .
        $tm_expire .
        ' AND ' .
        q_bitand('u.users_opt', 32768) .
        '=0'
    );
    $c = uq(
        q_limit(
            'SELECT u.id, u.alias, u.users_opt, u.custom_color FROM fud30_ses s INNER JOIN fud30_users u ON u.id=s.user_id WHERE s.time_sec>' .
            $tm_expire .
            ' AND ' .
            q_bitand('u.users_opt', 32768) .
            '=0 ORDER BY s.time_sec DESC',
            $GLOBALS['MAX_LOGGEDIN_USERS']
        )
    );
    $obj->online_users_text = [];
    while ($r = db_rowarr($c)) {
        $obj->online_users_text[$r[0]] = draw_user_link($r[1], $r[2], $r[3]);
    }
    unset($c);

    q(
        'UPDATE fud30_stats_cache SET
		cache_age=' . __request_timestamp__ . ',
		last_user_id=' . (int)$obj->last_user_id . ',
		user_count=' . (int)$obj->user_count . ',
		online_users_anon=' . (int)$obj->online_users_anon . ',
		online_users_hidden=' . (int)$obj->online_users_hidden . ',
		online_users_reg=' . (int)$obj->online_users_reg . ',
		online_users_text=' . ssn(serialize($obj->online_users_text))
    );

    $obj->last_user_alias = q_singleval('SELECT alias FROM fud30_users WHERE id=' . $obj->last_user_id);
    $obj->last_msg_subject = q_singleval('SELECT subject FROM fud30_msg WHERE id=' . $last_msg_id);

    list($obj->most_online, $obj->most_online_time) = db_saq(
        'SELECT most_online, most_online_time FROM fud30_stats_cache'
    );
    /* Update most online users stats if needed. */
    if (($obj->online_users_reg + $obj->online_users_hidden + $obj->online_users_anon) > $obj->most_online) {
        $obj->most_online = $obj->online_users_reg + $obj->online_users_hidden + $obj->online_users_anon;
        $obj->most_online_time = __request_timestamp__;
        q(
            'UPDATE fud30_stats_cache SET most_online=' .
            $obj->most_online .
            ', most_online_time=' .
            $obj->most_online_time
        );
    } else {
        if (!$obj->most_online_time) {
            $obj->most_online_time = __request_timestamp__;
        }
    }

    return $obj;
}

$logedin = $forum_info = '';

if ($FUD_OPT_1 & 1073741824 || $FUD_OPT_2 & 16) {
    if (!($st_obj = db_sab(
        'SELECT sc.*, m.subject AS last_msg_subject, u.alias AS last_user_alias FROM fud30_stats_cache sc INNER JOIN fud30_users u ON u.id=sc.last_user_id LEFT JOIN fud30_msg m ON m.id=' .
        $last_msg_id .
        ' WHERE sc.cache_age>' .
        (__request_timestamp__ - $STATS_CACHE_AGE)
    ))) {
        $st_obj = rebuild_stats_cache($last_msg_id);
    } else {
        if ($st_obj->online_users_text && (_uid || !($FUD_OPT_3 & 262144))) {
            $st_obj->online_users_text = unserialize($st_obj->online_users_text);
        }
    }

    if (!$st_obj->most_online_time) {
        $st_obj->most_online_time = __request_timestamp__;
    }

    if ($FUD_OPT_1 & 1073741824 && (_uid || !($FUD_OPT_3 & 262144))) {
        if (!empty($st_obj->online_users_text)) {
            foreach ($st_obj->online_users_text as $k => $v) {
                $logedin .= '<a href="/index.php?t=usrinfo&amp;id=' . $k . '&amp;' . _rsid . '">' . $v . '</a> ';
            }
        }
        $logedin = '<tr>
	<th class="wa">Logged in users list ' .
            (($FUD_OPT_1 & 536870912) ? (_uid || !($FUD_OPT_3 & 131072) ? '[ <a href="/index.php?t=actions&amp;' .
                    _rsid .
                    '" class="thLnk" rel="nofollow">User Activity</a> ]' : '') .
                '
' .
                (_uid || !($FUD_OPT_3 & 262144) ? '[ <a href="/index.php?t=online_today&amp;' .
                    _rsid .
                    '" class="thLnk" rel="nofollow">Today&#39;s Visitors</a> ]' : '') : '') .
            '</th>
</tr>
<tr>
	<td class="RowStyleA">
		<span class="SmallText">There are <b>' .
            convertPlural(
                $st_obj->online_users_reg,
                ['' . $st_obj->online_users_reg . ' member', '' . $st_obj->online_users_reg . ' members']
            ) .
            '</b>, <b>' .
            convertPlural(
                $st_obj->online_users_hidden,
                [
                    '' . $st_obj->online_users_hidden . ' invisible member',
                    '' . $st_obj->online_users_hidden . ' invisible members',
                ]
            ) .
            '</b> and <b>' .
            convertPlural(
                $st_obj->online_users_anon,
                ['' . $st_obj->online_users_anon . ' guest', '' . $st_obj->online_users_anon . ' guests']
            ) .
            '</b> visiting this board.&nbsp;&nbsp;&nbsp;
		<span class="adminColor">[Administrator]</span>&nbsp;&nbsp;
		<span class="modsColor">[Moderator]</span></span><br />
		' .
            $logedin .
            '
	</td>
</tr>';
    }
    if ($FUD_OPT_2 & 16) {
        $forum_info = '<tr>
	<td class="RowStyleB SmallText">
		Our users have posted a total of <b>' .
            convertPlural($post_count, ['' . $post_count . ' message', '' . $post_count . ' messages']) .
            '</b> inside <b>' .
            convertPlural($thread_count, ['' . $thread_count . ' topic', '' . $thread_count . ' topics']) .
            '</b>.<br />
		Most users ever online was <b>' .
            $st_obj->most_online .
            '</b> on <b>' .
            strftime('%a, %d %B %Y %H:%M', $st_obj->most_online_time) .
            '</b><br />
		We have <b>' .
            $st_obj->user_count .
            '</b> registered ' .
            convertPlural($st_obj->user_count, ['user', 'users']) .
            '.<br />
		The newest registered user is <a href="/index.php?t=usrinfo&amp;id=' .
            $st_obj->last_user_id .
            '&amp;' .
            _rsid .
            '"><b>' .
            htmlspecialchars($st_obj->last_user_alias, null, null, false) .
            '</b></a>
		' .
            ($last_msg_id ? '<br />Last message on the forum: <a href="/index.php?t=' .
                d_thread_view .
                '&amp;goto=' .
                $last_msg_id .
                '&amp;' .
                _rsid .
                '#msg_' .
                $last_msg_id .
                '"><b>' .
                htmlspecialchars($st_obj->last_msg_subject, null, null, false) .
                '</b></a>' : '') .
            '
	</td>
</tr>';
    }
}
if ($FUD_OPT_2 & 2 || $is_a) {    // PUBLIC_STATS is enabled or Admin user.
    $page_gen_time = number_format(microtime(true) - __request_timestamp_exact__, 5);
    $variables['page_stats'] = $FUD_OPT_2 & 2 ? '<br /><div class="SmallText al">Total time taken to generate the page: ' .
        convertPlural($page_gen_time, ['' . $page_gen_time . ' seconds']) .
        '</div>' : '<br /><div class="SmallText al">Total time taken to generate the page: ' .
        convertPlural($page_gen_time, ['' . $page_gen_time . ' seconds']) .
        '</div>';
} else {
    $variables['page_stats'] = '';
}
$variables['RSS'] = $RSS;
$variables['TITLE_EXTRA'] = $TITLE_EXTRA;
$variables['forum_list_table_data'] = $forum_list_table_data;
?>
