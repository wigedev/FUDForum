<?php
/**
 * copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: ignore_list.php.t 4994 2010-09-02 17:33:29Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
}
function ignore_add($user_id, $ignore_id)
{
    q('INSERT INTO fud30_user_ignore (ignore_id, user_id) VALUES (' . $ignore_id . ', ' . $user_id . ')');
    q('DELETE FROM fud30_buddy WHERE user_id=' . $ignore_id . ' AND bud_id=' . $user_id);
    if (db_affected()) {
        fud_use('buddy.inc');
        buddy_rebuild_cache($ignore_id);
    }

    return ignore_rebuild_cache($user_id);
}

function ignore_delete($user_id, $ignore_id)
{
    q('DELETE FROM fud30_user_ignore WHERE user_id=' . $user_id . ' AND ignore_id=' . $ignore_id);
    return ignore_rebuild_cache($user_id);
}

function ignore_rebuild_cache($uid)
{
    $arr = [];
    $q = uq('SELECT ignore_id FROM fud30_user_ignore WHERE user_id=' . $uid);
    while ($ent = db_rowarr($q)) {
        $arr[$ent[0]] = 1;
    }
    unset($q);

    if ($arr) {
        q('UPDATE fud30_users SET ignore_list=' . _esc(serialize($arr)) . ' WHERE id=' . $uid);
        return $arr;
    }
    q('UPDATE fud30_users SET ignore_list=NULL WHERE id=' . $uid);
}

function check_return($returnto)
{
    if ($GLOBALS['FUD_OPT_2'] & 32768 && !empty($_SERVER['PATH_INFO'])) {
        if (!$returnto || !strncmp($returnto, '/er/', 4)) {
            header('Location: /index.php/i/' . _rsidl);
        } else {
            if ($returnto[0] == '/') { /* Unusual situation, path_info & normal themes are active. */
                header('Location: /index.php' . $returnto);
            } else {
                header('Location: /index.php?' . $returnto);
            }
        }
    } else {
        if (!$returnto || !strncmp($returnto, 't=error', 7)) {
            header('Location: /index.php?t=index&' . _rsidl);
        } else {
            if (strpos($returnto, 'S=') === false && $GLOBALS['FUD_OPT_1'] & 128) {
                header('Location: /index.php?' . $returnto . '&S=' . s);
            } else {
                header('Location: /index.php?' . $returnto);
            }
        }
    }
    exit;
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

if (!_uid) {
    std_error('login');
}

function ignore_alias_fetch($al, &$is_mod)
{
    if (!($tmp = db_saq(
        'SELECT id, ' .
        q_bitand('users_opt', 1048576) .
        ' FROM fud30_users WHERE alias=' .
        _esc(char_fix(htmlspecialchars($al)))
    ))) {
        return;
    }
    $is_mod = $tmp[1];

    return $tmp[0];
}

if (isset($_POST['add_login']) && is_string($_POST['add_login'])) {
    if (!($ignore_id = ignore_alias_fetch($_POST['add_login'], $is_mod))) {
        error_dialog('User not found', 'The user you tried to add to your ignore list was not found.');
    }
    if ($is_mod) {
        error_dialog('Info', 'You cannot ignore this user');
    }
    if (!empty($usr->ignore_list)) {
        $usr->ignore_list = unserialize($usr->ignore_list);
    }
    if (!isset($usr->ignore_list[$ignore_id])) {
        ignore_add(_uid, $ignore_id);
    } else {
        error_dialog('Info', 'You already have this user on your ignore list');
    }
}

/* Incomming from message display page (ignore link). */
if (isset($_GET['add']) && ($_GET['add'] = (int)$_GET['add'])) {
    if (!sq_check(0, $usr->sq)) {
        check_return($usr->returnto);
    }

    if (!empty($usr->ignore_list)) {
        $usr->ignore_list = unserialize($usr->ignore_list);
    }

    if (($ignore_id = q_singleval(
            'SELECT id FROM fud30_users WHERE id=' . $_GET['add'] . ' AND ' . q_bitand('users_opt', 1048576) . '=0'
        )) && !isset($usr->ignore_list[$ignore_id])) {
        ignore_add(_uid, $ignore_id);
    }
    check_return($usr->returnto);
}

/* Anon user hack. */
if (isset($_GET['del']) && $_GET['del'] === '0') {
    $_GET['del'] = 1;
}

if (isset($_GET['del']) && ($_GET['del'] = (int)$_GET['del'])) {
    if (!sq_check(0, $usr->sq)) {
        check_return($usr->returnto);
    }

    ignore_delete(_uid, $_GET['del']);
    /* Needed for external links to this form. */
    if (isset($_GET['redr'])) {
        check_return($usr->returnto);
    }
}

ses_update_status($usr->sid, 'Browsing own ignore list');

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
$tabs = '';
if (_uid) {
    $tablist = [
        'Notifications' => 'uc',
        'Account Settings' => 'register',
        'Subscriptions' => 'subscribed',
        'Bookmarks' => 'bookmarked',
        'Referrals' => 'referals',
        'Buddy List' => 'buddy_list',
        'Ignore List' => 'ignore_list',
        'Show Own Posts' => 'showposts',
    ];

    if (!($FUD_OPT_2 & 8192)) {
        unset($tablist['Referrals']);
    }

    if (isset($_POST['mod_id'])) {
        $mod_id_chk = $_POST['mod_id'];
    } else {
        if (isset($_GET['mod_id'])) {
            $mod_id_chk = $_GET['mod_id'];
        } else {
            $mod_id_chk = null;
        }
    }

    if (!$mod_id_chk) {
        if ($FUD_OPT_1 & 1024) {
            $tablist['Private Messaging'] = 'pmsg';
        }
        $pg = ($_GET['t'] == 'pmsg_view' || $_GET['t'] == 'ppost') ? 'pmsg' : $_GET['t'];

        foreach ($tablist as $tab_name => $tab) {
            $tab_url = '/index.php?t=' . $tab . (s ? '&amp;S=' . s : '');
            if ($tab == 'referals') {
                if (!($FUD_OPT_2 & 8192)) {
                    continue;
                }
                $tab_url .= '&amp;id=' . _uid;
            } else {
                if ($tab == 'showposts') {
                    $tab_url .= '&amp;id=' . _uid;
                }
            }
            $tabs .= $pg == $tab ? '<td class="tabON"><div class="tabT"><a class="tabON" href="' .
                $tab_url .
                '">' .
                $tab_name .
                '</a></div></td>' : '<td class="tabI"><div class="tabT"><a href="' .
                $tab_url .
                '">' .
                $tab_name .
                '</a></div></td>';
        }

        $tabs = '<table cellspacing="1" cellpadding="0" class="tab">
<tr>
	' . $tabs . '
</tr>
</table>';
    }
}

$c = uq(
    'SELECT ui.ignore_id, ui.id as ignoreent_id,
			u.id, u.alias AS login, u.join_date, u.posted_msg_count, u.home_page
		FROM fud30_user_ignore ui
		LEFT JOIN fud30_users u ON ui.ignore_id=u.id
		WHERE ui.user_id=' . _uid
);

$ignore_list = '';
if (($r = db_rowarr($c))) {
    do {
        $ignore_list .= $r[0] ? '<tr class="' .
            alt_var('ignore_alt', 'RowStyleA', 'RowStyleB') .
            '">
	<td class="GenText wa"><a href="/index.php?t=usrinfo&amp;id=' .
            $r[2] .
            '&amp;' .
            _rsid .
            '">' .
            $r[3] .
            '</a>&nbsp;<span class="SmallText">(<a href="/index.php?t=ignore_list&amp;del=' .
            $r[0] .
            '&amp;' .
            _rsid .
            '&amp;SQ=' .
            $GLOBALS['sq'] .
            '">remove</a>)</span></td>
	<td class="ac">' .
            $r[5] .
            '</td>
	<td class="ac nw">' .
            strftime('%a, %d %B %Y %H:%M', $r[4]) .
            '</td>
	<td class="GenText nw"><a href="/index.php?t=showposts&amp;' .
            _rsid .
            '&amp;id=' .
            $r[2] .
            '"><img src="/theme/twig/images/show_posts.gif" alt="" /></a> ' .
            ($FUD_OPT_2 & 1073741824 ? '<a href="/index.php?t=email&amp;toi=' .
                $r[2] .
                '&amp;' .
                _rsid .
                '" rel="nofollow"><img src="/theme/twig/images/msg_email.gif" alt="" /></a>' : '') .
            ($r[6] ? '<a href="' . $r[6] . '"><img src="/theme/twig/images/homepage.gif" alt="" /></a>' : '') .
            '</td>
</tr>' : '<tr class="' .
            alt_var('ignore_alt', 'RowStyleA', 'RowStyleB') .
            '">
	<td colspan="4" class="wa GenText"><span class="anon">' .
            $GLOBALS['ANON_NICK'] .
            '</span>&nbsp;<span class="SmallText">(<a href="/index.php?t=ignore_list&amp;del=' .
            $r[1] .
            '&amp;' .
            _rsid .
            '">remove</a>)</span></td>
</tr>';
    } while (($r = db_rowarr($c)));
    $ignore_list = '<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th>Ignored Users</th>
	<th class="nw ac">Message Count</th>
	<th class="nw ac">Registered on</th>
	<th class="nw ac">Action</th>
</tr>
' . $ignore_list . '
</table>';
}
unset($c);

F()->response->tabs = $tabs;
F()->response->ignore_list = $ignore_list;
F()->response->memberSearchEnabled = $FUD_OPT_1 & (8388608|4194304); // TODO: replace with options
