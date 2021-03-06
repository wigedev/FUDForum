<?php
/**
 * copyright            : (C) 2001-2011 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: groupmgr.php.t 5366 2011-08-26 16:14:37Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

function draw_tmpl_perm_table($perm, $perms, $names)
{
    $str = '';
    foreach ($perms as $k => $v) {
        $str .= ($perm & $v[0]) ? '<td title="' . $names[$k] . '" class="permYES">Yes</td>' : '<td title="' .
            $names[$k] .
            '" class="permNO">No</td>';
    }
    return $str;
}

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
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

function grp_delete_member($id, $user_id)
{
    if (!$user_id || $user_id == '2147483647') {
        return;
    }

    q('DELETE FROM fud30_group_members WHERE group_id=' . $id . ' AND user_id=' . $user_id);

    if (q_singleval(q_limit('SELECT id FROM fud30_group_members WHERE user_id=' . $user_id, 1))) {
        /* We rebuild cache, since this user's permission for a particular resource are controled by
         * more the one group. */
        grp_rebuild_cache([$user_id]);
    } else {
        q('DELETE FROM fud30_group_cache WHERE user_id=' . $user_id);
    }
}

function grp_update_member($id, $user_id, $perm)
{
    q(
        'UPDATE fud30_group_members SET group_members_opt=' .
        $perm .
        ' WHERE group_id=' .
        $id .
        ' AND user_id=' .
        $user_id
    );
    grp_rebuild_cache([$user_id]);
}

function grp_rebuild_cache($user_id = null)
{
    $list = [];
    if ($user_id !== null) {
        $lmt = ' user_id IN(' . implode(',', $user_id) . ') ';
    } else {
        $lmt = '';
    }

    /* Generate an array of permissions, in the end we end up with 1ist of permissions. */
    $r = uq(
        'SELECT gm.user_id, gm.group_members_opt, gr.resource_id FROM fud30_group_members gm INNER JOIN fud30_group_resources gr ON gr.group_id=gm.group_id WHERE gm.group_members_opt>=65536 AND ' .
        q_bitand('gm.group_members_opt', 65536) .
        ' > 0' .
        ($lmt ? ' AND ' . $lmt : '')
    );
    while ($o = db_rowobj($r)) {
        foreach ($o as $k => $v) {
            $o->{$k} = (int)$v;
        }
        if (isset($list[$o->resource_id][$o->user_id])) {
            if ($o->group_members_opt & 131072) {
                $list[$o->resource_id][$o->user_id] |= $o->group_members_opt;
            } else {
                $list[$o->resource_id][$o->user_id] &= $o->group_members_opt;
            }
        } else {
            $list[$o->resource_id][$o->user_id] = $o->group_members_opt;
        }
    }
    unset($r);

    $tmp = [];
    foreach ($list as $k => $v) {
        foreach ($v as $u => $p) {
            $tmp[] = $k . ',' . $p . ',' . $u;
        }
    }

    if (!$tmp) {
        q('DELETE FROM fud30_group_cache' . ($lmt ? ' WHERE ' . $lmt : ''));
        return;
    }

    if (__dbtype__ == 'mysql') {
        q(
            'REPLACE INTO fud30_group_cache (resource_id, group_cache_opt, user_id) VALUES (' .
            implode('),(', $tmp) .
            ')'
        );
        q('DELETE FROM fud30_group_cache WHERE ' . ($lmt ? $lmt . ' AND ' : '') . ' id < LAST_INSERT_ID()');
        return;
    }

    if (($ll = !db_locked())) {
        db_lock('fud30_group_cache WRITE');
    }

    q('DELETE FROM fud30_group_cache' . ($lmt ? ' WHERE ' . $lmt : ''));
    ins_m('fud30_group_cache', 'resource_id, group_cache_opt, user_id', 'integer, integer, integer', $tmp);

    if ($ll) {
        db_unlock();
    }
}

function group_perm_array()
{
    return [
        'p_VISIBLE' => [1, 'Visible'],
        'p_READ' => [2, 'Read'],
        'p_POST' => [4, 'Create new topics'],
        'p_REPLY' => [8, 'Reply to messages'],
        'p_EDIT' => [16, 'Edit messages'],
        'p_DEL' => [32, 'Delete messages'],
        'p_STICKY' => [64, 'Make topics sticky'],
        'p_POLL' => [128, 'Create polls'],
        'p_FILE' => [256, 'Attach files'],
        'p_VOTE' => [512, 'Vote on polls'],
        'p_RATE' => [1024, 'Rate topics'],
        'p_SPLIT' => [2048, 'Split/Merge topics'],
        'p_LOCK' => [4096, 'Lock/Unlock topics'],
        'p_MOVE' => [8192, 'Move topics'],
        'p_SML' => [16384, 'Use smilies/emoticons'],
        'p_IMG' => [32768, 'Use [img] tags'],
        'p_SEARCH' => [262144, 'Can Search'],
    ];
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

/** Log action to the forum's Action Log Viewer ACP. */
function logaction($user_id, $res, $res_id = 0, $action = null)
{
    q(
        'INSERT INTO fud30_action_log (logtime, logaction, user_id, a_res, a_res_id)
		VALUES(' .
        __request_timestamp__ .
        ', ' .
        ssn($action) .
        ', ' .
        $user_id .
        ', ' .
        ssn($res) .
        ', ' .
        (int)$res_id .
        ')'
    );
}

if (!_uid) {
    std_error('login');
}
$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : (isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0);

if ($group_id &&
    !$is_a &&
    !q_singleval(
        'SELECT id FROM fud30_group_members WHERE group_id=' .
        $group_id .
        ' AND user_id=' .
        _uid .
        ' AND group_members_opt>=131072 AND ' .
        q_bitand('group_members_opt', 131072) .
        ' > 0'
    )) {
    std_error('access');
}

$hdr = group_perm_array();
/* Fetch all the groups user has access to. */
if ($is_a) {
    $r = uq(
        'SELECT id, name, forum_id FROM fud30_groups WHERE id>2 AND forum_id NOT IN (SELECT id FROM fud30_forum WHERE cat_id=0 OR url_redirect IS NOT NULL) ORDER BY name'
    );
} else {
    $r = uq(
        'SELECT g.id, g.name, g.forum_id FROM fud30_group_members gm INNER JOIN fud30_groups g ON gm.group_id=g.id WHERE gm.user_id=' .
        _uid .
        ' AND group_members_opt>=131072 AND ' .
        q_bitand('group_members_opt', 131072) .
        ' > 0 ORDER BY g.name'
    );
}

/* Make a group selection form. */
$n = 0;
$vl = $kl = '';
while ($e = db_rowarr($r)) {
    $vl .= $e[0] . "\n";
    $kl .= ($e[2] ? '* ' : '') . htmlspecialchars($e[1]) . "\n";
    $n++;
}
unset($r);

if (!$n) {
    std_error('access');
} else {
    if ($n == 1) {
        $group_id = rtrim($vl);
        $group_selection = '';
    } else {
        if (!$group_id) {
            $group_id = (int)$vl;
        }
        $group_selection = '<br /><br />
<form method="post" action="/index.php?t=groupmgr">
<div class="ctb"><table cellspacing="1" cellpadding="2" class="MiniTable">
<tr>
	<th colspan="3">Group Editor Selection</th>
</tr>
<tr class="RowStyleC">
	<td class="nw fb">Group:</td>
	<td><select name="group_id">' . tmpl_draw_select_opt(rtrim($vl), rtrim($kl), $group_id) . '</select></td>
	<td class="ar"><input type="submit" class="button" name="btn_groupswitch" value="Edit Group" /></td>
</tr>
</table></div>' . _hs . '</form>';
    }
}

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

if (isset($_POST['btn_cancel'])) {
    unset($_POST);
}
if (!($grp = db_sab('SELECT * FROM fud30_groups WHERE id=' . $group_id))) {
    invl_inp_err();
}

/* Fetch controlled resources. */
if (!$grp->forum_id) {
    $group_resources = '<b>This group controls permissions of the following forums:</b><br />';
    $c = uq(
        'SELECT f.name FROM fud30_group_resources gr INNER JOIN fud30_forum f ON gr.resource_id=f.id WHERE gr.group_id=' .
        $group_id
    );
    while ($r = db_rowarr($c)) {
        $group_resources .= '&nbsp;&nbsp;&nbsp;' . $r[0] . '<br />';
    }
    unset($c);
} else {
    $fname = q_singleval('SELECT name FROM fud30_forum WHERE id=' . $grp->forum_id);
    $group_resources = '<b>Primary group for forum:</b> ' . $fname;
}

if ($is_a) {
    $maxperms = 2147483647;
} else {
    $maxperms = (int)$grp->groups_opt;
    $inh = (int)$grp->groups_opti;
    $inh_id = (int)$grp->inherit_id;
    if ($inh_id && $inh) {
        $res = [$group_id => $group_id];
        while ($inh > 0) {
            if (isset($res[$inh_id])) { // Permissions loop.
                break;
            } else {
                if (!($row = db_saq(
                    'SELECT groups_opt, groups_opti, inherit_id FROM fud30_groups WHERE id=' . $inh_id
                ))) {
                    break; // Invalid group id.
                }
            }
            $maxperms |= $inh & $row[0]; // Fetch permissions of new group.
            if (!$row[2] || !$row[1]) { // Nothing more to inherit.
                break;
            }
            $inh &= (int)$row[1];
            $inh_id = (int)$row[2];
            $res[$inh_id] = $inh_id;
        }
    }
}

$login_error = '';
$perm = 0;

if (isset($_POST['btn_submit'])) {
    foreach ($hdr as $k => $v) {
        if (isset($_POST[$k]) && $_POST[$k] & $v[0]) {
            $perm |= $v[0];
        }
    }

    /* Auto approve members. */
    $perm |= 65536;

    if (empty($_POST['edit'])) {
        $gr_member = $_POST['gr_member'];

        if (!($usr_id = q_singleval(
            'SELECT id FROM fud30_users WHERE alias=' . _esc(char_fix(htmlspecialchars($gr_member)))
        ))) {
            $login_error = '<span class="ErrorText">There is no user with a login of "' .
                char_fix(htmlspecialchars($gr_member)) .
                '"</span><br />';
        } else {
            if (q_singleval(
                'SELECT id FROM fud30_group_members WHERE group_id=' . $group_id . ' AND user_id=' . $usr_id
            )) {
                $login_error = '<span class="ErrorText">User "' .
                    char_fix(htmlspecialchars($gr_member)) .
                    '" already exists in this group.</span><br />';
            } else {
                q(
                    'INSERT INTO fud30_group_members (group_members_opt, user_id, group_id) VALUES (' .
                    $perm .
                    ', ' .
                    $usr_id .
                    ', ' .
                    $group_id .
                    ')'
                );
                grp_rebuild_cache([$usr_id]);
                logaction(_uid, 'ADDGRP', $group_id, $gr_member);
            }
        }
    } else {
        if (($usr_id = q_singleval(
                'SELECT user_id FROM fud30_group_members WHERE group_id=' . $group_id . ' AND id=' . (int)$_POST['edit']
            )) !== null) {
            if (q_singleval(
                'SELECT user_id FROM fud30_group_members WHERE group_id=' .
                $group_id .
                ' AND user_id=' .
                $usr_id .
                ' AND group_members_opt>=131072 AND ' .
                q_bitand('group_members_opt', 131072) .
                ' > 0'
            )) {
                $perm |= 131072;
            }
            q('UPDATE fud30_group_members SET group_members_opt=' . $perm . ' WHERE id=' . (int)$_POST['edit']);
            grp_rebuild_cache([$usr_id]);

            if ($usr_id == 0) {
                $usr_id = 1;
            } // Correct log entry for Anonymous.
            $gr_member = q_singleval('SELECT alias FROM fud30_users WHERE id=' . $usr_id);
            logaction(_uid, 'EDITGRP', $group_id, $gr_member);
        }
    }
    if (!$login_error) {
        unset($_POST);
        $gr_member = '';
    }
}

if (isset($_GET['del']) && ($del = (int)$_GET['del']) && $group_id && sq_check(0, $usr->sq)) {
    $is_gl = q_singleval(
        'SELECT user_id FROM fud30_group_members WHERE group_id=' .
        $group_id .
        ' AND user_id=' .
        $del .
        ' AND group_members_opt>=131072 AND ' .
        q_bitand('group_members_opt', 131072) .
        ' > 0'
    );
    grp_delete_member($group_id, $del);

    $gr_member = q_singleval('SELECT alias FROM fud30_users WHERE id=' . $del);
    logaction(_uid, 'DELGRP', $group_id, $gr_member);

    /* If the user was a group moderator, rebuild moderation cache. */
    if ($is_gl) {
        fud_use('groups_adm.inc', true);
        rebuild_group_ldr_cache($del);
    }
}

$edit = 0;
if (isset($_GET['edit']) && ($edit = (int)$_GET['edit'])) {
    if (!($mbr = db_sab(
        'SELECT gm.*, u.alias FROM fud30_group_members gm LEFT JOIN fud30_users u ON u.id=gm.user_id WHERE gm.group_id=' .
        $group_id .
        ' AND gm.id=' .
        $edit
    ))) {
        invl_inp_err();
    }
    if ($mbr->user_id == 0) {
        $gr_member = '<span class="anon">Anonymous</span>';
    } else {
        if ($mbr->user_id == '2147483647') {
            $gr_member = '<span class="reg">All Registered Users</span>';
        } else {
            $gr_member = $mbr->alias;
        }
    }
    $perm = $mbr->group_members_opt;
} else {
    if ($group_id > 2 &&
        !isset($_POST['btn_submit']) &&
        ($luser_id = q_singleval('SELECT MAX(id) FROM fud30_group_members WHERE group_id=' . $group_id))) {
        /* Help trick, we fetch the last user added to the group. */
        if (!($mbr = db_sab('SELECT 1 AS user_id, group_members_opt FROM fud30_group_members WHERE id=' . $luser_id))) {
            invl_inp_err();
        }
        $perm = $mbr->group_members_opt;
    } else {
        $mbr = 0;
    }
}

/* Anon users cannot rate topics. */
if ($mbr && !$mbr->user_id) {
    $maxperms = $maxperms & ~1024;
}

/* No members inside the group. */
if (!$perm && !$mbr) {
    $perm = $maxperms;
}

/* Translated permission names. */
$ts_list = [
    'p_VISIBLE' => 'Visible',
    'p_READ' => 'Read',
    'p_POST' => 'Post',
    'p_REPLY' => 'Reply',
    'p_EDIT' => 'Edit',
    'p_DEL' => 'Delete',
    'p_STICKY' => 'Sticky messages',
    'p_POLL' => 'Create polls',
    'p_FILE' => 'Attach files',
    'p_VOTE' => 'Vote',
    'p_RATE' => 'Rate topics/members',
    'p_SPLIT' => 'Split topics',
    'p_LOCK' => 'Lock topics',
    'p_MOVE' => 'Move topics',
    'p_SML' => 'Use smilies',
    'p_IMG' => 'Use image tags',
    'p_SEARCH' => 'Can Search',
];

$perm_sel_hdr = $perm_select = $tmp = '';
$i = 0;
foreach ($hdr as $k => $v) {
    $selyes = '';
    if ($maxperms & $v[0]) {
        if ($perm & $v[0]) {
            $selyes = ' selected="selected"';
        }
        $perm_select .= '<td class="ac">
<select name="' . $k . '" class="SmallText">
	<option value="0">No</option>
	<option value="' . $v[0] . '"' . $selyes . '>Yes</option>
</select>
</td>';
    } else {
        /* Only show the permissions the user can modify. */
        continue;
    }
    $tmp .= '<th class="ac">' . $ts_list[$k] . '</th>';

    if (++$i == '6') {
        $perm_sel_hdr .= '<tr>' . $tmp . '</tr>
<tr class="RowStyleB">' . $perm_select . '</tr>';
        $perm_select = $tmp = '';
        $i = 0;
    }
}

if ($tmp) {
    while (++$i < '6' + 1) {
        $tmp .= '<th> </th>';
        $perm_select .= '<td> </td>';
    }
    $perm_sel_hdr .= '<tr>' . $tmp . '</tr>
<tr class="RowStyleB">' . $perm_select . '</tr>';
}

/* Draw list of group members. */
$group_members_list = '';
$r = uq(
    'SELECT gm.id AS mmid, gm.*, g.*, u.alias FROM fud30_group_members gm INNER JOIN fud30_groups g ON gm.group_id=g.id LEFT JOIN fud30_users u ON gm.user_id=u.id WHERE gm.group_id=' .
    $group_id .
    ' ORDER BY gm.id'
);
while ($obj = db_rowobj($r)) {
    $perm_table = draw_tmpl_perm_table($obj->group_members_opt, $hdr, $ts_list);

    if ($obj->user_id == '0') {
        $member_name = '<span class="anon">Anonymous</span>';
        $group_members_list .= '<tr class="' .
            alt_var('mem_list_alt', 'RowStyleA', 'RowStyleB') .
            '">
	<td class="nw">' .
            $member_name .
            '</td>
	' .
            $perm_table .
            '
	<td class="nw">[<a href="/index.php?t=groupmgr&amp;' .
            _rsid .
            '&amp;edit=' .
            $obj->mmid .
            '&amp;group_id=' .
            $obj->group_id .
            '">Edit</a>]</td>
</tr>';
    } else {
        if ($obj->user_id == '2147483647') {
            $member_name = '<span class="reg">All Registered Users</span>';
            $group_members_list .= '<tr class="' .
                alt_var('mem_list_alt', 'RowStyleA', 'RowStyleB') .
                '">
	<td class="nw">' .
                $member_name .
                '</td>
	' .
                $perm_table .
                '
	<td class="nw">[<a href="/index.php?t=groupmgr&amp;' .
                _rsid .
                '&amp;edit=' .
                $obj->mmid .
                '&amp;group_id=' .
                $obj->group_id .
                '">Edit</a>]</td>
</tr>';
        } else {
            $member_name = $obj->alias;
            if ($obj->user_id == _uid && !$is_a) {
                $group_members_list .= '<tr class="' .
                    alt_var('mem_list_alt', 'RowStyleA', 'RowStyleB') .
                    '">
	<td class="nw">' .
                    $member_name .
                    '</td>
	' .
                    $perm_table .
                    '
	<td class="nw">[<a href="/index.php?t=groupmgr&amp;' .
                    _rsid .
                    '&amp;edit=' .
                    $obj->mmid .
                    '&amp;group_id=' .
                    $obj->group_id .
                    '">Edit</a>]</td>
</tr>';
            } else {
                $group_members_list .= '<tr class="' .
                    alt_var('mem_list_alt', 'RowStyleA', 'RowStyleB') .
                    '">
	<td class="nw">' .
                    $member_name .
                    '</td>
	' .
                    $perm_table .
                    '
	<td class="nw">[<a href="/index.php?t=groupmgr&amp;' .
                    _rsid .
                    '&amp;edit=' .
                    $obj->mmid .
                    '&amp;group_id=' .
                    $obj->group_id .
                    '">Edit</a>] [<a href="/index.php?t=groupmgr&amp;' .
                    _rsid .
                    '&amp;del=' .
                    $obj->user_id .
                    '&amp;group_id=' .
                    $obj->group_id .
                    '&amp;SQ=' .
                    $GLOBALS['sq'] .
                    '">Delete</a>]</td>
</tr>';
            }
        }
    }
}
unset($r);

F()->response->admin_cp = $admin_cp;
F()->response->group_selection = $group_selection;
F()->response->grp = $grp;
F()->response->group_resources = $group_resources;
F()->response->edit = $edit;
F()->response->mbr = $mbr;
F()->response->gr_member = $gr_member;
F()->response->showMemberLink = F()->response->mbr->user_id > 0 && F()->response->mbr->user_id < 2147483647;
F()->response->login_error = $login_error;
F()->response->memberSearchEnabled = $FUD_OPT_1 & (8388608|4194304); // TODO: replace with options
F()->response->perm_sel_hdr = $perm_sel_hdr;
F()->response->group_id = $group_id;
F()->response->hdr = $hdr;
F()->response->group_members_list = $group_members_list;
