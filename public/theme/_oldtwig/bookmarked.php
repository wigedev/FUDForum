<?php
/**
 * copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: bookmarked.php.t 4994 2010-09-02 17:33:29Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
}
function is_notified($user_id, $thread_id)
{
    return q_singleval(
        'SELECT * FROM fud30_thread_notify WHERE thread_id=' . (int)$thread_id . ' AND user_id=' . $user_id
    );
}

function thread_notify_add($user_id, $thread_id)
{
    db_li(
        'INSERT INTO fud30_thread_notify (user_id, thread_id) VALUES (' . $user_id . ', ' . (int)$thread_id . ')',
        $ret
    );
}

function thread_notify_del($user_id, $thread_id)
{
    q('DELETE FROM fud30_thread_notify WHERE thread_id=' . (int)$thread_id . ' AND user_id=' . $user_id);
}

function thread_bookmark_add($user_id, $thread_id)
{
    db_li('INSERT INTO fud30_bookmarks (user_id, thread_id) VALUES (' . $user_id . ', ' . (int)$thread_id . ')', $ret);
}

function thread_bookmark_del($user_id, $thread_id)
{
    q('DELETE FROM fud30_bookmarks WHERE thread_id=' . (int)$thread_id . ' AND user_id=' . $user_id);
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

if (!_uid) {
    std_error('login');
}

/* Delete thread bookmark. */
if (isset($_GET['th']) && ($_GET['th'] = (int)$_GET['th']) && sq_check(0, $usr->sq)) {
    thread_bookmark_del(_uid, $_GET['th']);
}

if (!empty($_POST['t_unbookmark_all'])) {
    q('DELETE FROM fud30_bookmarks WHERE user_id=' . _uid);
} else {
    if (isset($_POST['t_unbookmark_sel'], $_POST['te'])) {
        $list = [];
        foreach ((array)$_POST['te'] as $v) {
            $list[(int)$v] = (int)$v;
        }
        q('DELETE FROM fud30_bookmarks WHERE user_id=' . _uid . ' AND thread_id IN(' . implode(',', $list) . ')');
    }
}

ses_update_status($usr->sid, 'Browsing own bookmark list');

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

$bookmarked_thread_data = '';

if (!isset($_GET['start']) || !($start = (int)$_GET['start'])) {
    $start = 0;
}

$c = uq(
    q_limit(
        'SELECT /*!40000 SQL_CALC_FOUND_ROWS */ t.id, m.subject, f.name FROM fud30_bookmarks b INNER JOIN fud30_thread t ON b.thread_id=t.id INNER JOIN fud30_forum f ON f.id=t.forum_id INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE b.user_id=' .
        _uid .
        ' ORDER BY f.name, m.subject',
        $THREADS_PER_PAGE,
        $start
    )
);

while (($r = db_rowarr($c))) {
    $bookmarked_thread_data .= '<tr class="' .
        alt_var('search_alt', 'RowStyleA', 'RowStyleB') .
        '">
	<td><input type="checkbox" name="te[]" value="' .
        $r[0] .
        '" /></td>
	<td class="nw"><a href="/index.php?t=bookmarked&amp;th=' .
        $r[0] .
        '&amp;' .
        _rsid .
        '&amp;SQ=' .
        $GLOBALS['sq'] .
        '">Remove from bookmarks</a></td>
	<td class="wa">' .
        $r[2] .
        ' &raquo; <a href="/index.php?t=' .
        d_thread_view .
        '&amp;th=' .
        $r[0] .
        '&amp;unread=1&amp;' .
        _rsid .
        '">' .
        $r[1] .
        '</a></td>
</tr>';
}
unset($c);

/* Since a person can have MANY bookmarked threads, we need a pager & for the pager we need a entry count. */
if (($total = (int)q_singleval('SELECT /*!40000 FOUND_ROWS(), */ -1')) < 0) {
    $total = q_singleval(
        'SELECT count(*) FROM fud30_bookmarks b LEFT JOIN fud30_thread t ON b.thread_id=t.id INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE b.user_id=' .
        _uid
    );
}

if ($FUD_OPT_2 & 32768) {
    $pager = tmpl_create_pager($start, $THREADS_PER_PAGE, $total, '/index.php/bml/start/', '/' . _rsid . '#fff');
} else {
    $pager = tmpl_create_pager($start, $THREADS_PER_PAGE, $total, '/index.php?t=bookmarked&a=1&' . _rsid, '#fff');
}

F()->response->tabs = $tabs;
F()->response->bookmarked_thread_data = $bookmarked_thread_data;
F()->response->pager = $pager;
