<?php
/**
 * copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: referals.php.t 4994 2010-09-02 17:33:29Z naudefj $
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

/* Print number of unread private messages in User Control Panel. */
if (__fud_real_user__ && $FUD_OPT_1 & 1024) {    // PM_ENABLED
    $c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id=' . _uid . ' AND fldr=1 AND read_stamp=0');
    $ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;' . _rsid . '" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> You have <span class="GenTextRed">(' . $c . ')</span> unread ' . convertPlural($c, array('private message', 'private messages')) . '</a></li>' : '<li><a href="/index.php?t=pmsg&amp;' . _rsid . '" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> Private Messaging</a></li>';
} else {
    $ucp_private_msg = '';
}
$tabs = '';
if (_uid) {
    $tablist = array(
        'Notifications' => 'uc',
        'Account Settings' => 'register',
        'Subscriptions' => 'subscribed',
        'Bookmarks' => 'bookmarked',
        'Referrals' => 'referals',
        'Buddy List' => 'buddy_list',
        'Ignore List' => 'ignore_list',
        'Show Own Posts' => 'showposts'
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

        foreach ($tablist as $tab_name => $tab) {
            $tab_url = '/index.php?t=' . $tab . (s ? '&amp;S=' . s : '');
            if ($tab == 'referals') {
                if (!($FUD_OPT_2 & 8192)) {
                    continue;
                }
                $tab_url .= '&amp;id=' . _uid;
            } else if ($tab == 'showposts') {
                $tab_url .= '&amp;id=' . _uid;
            }
            $tabs .= $pg == $tab ? '<td class="tabON"><div class="tabT"><a class="tabON" href="' . $tab_url . '">' . $tab_name . '</a></div></td>' : '<td class="tabI"><div class="tabT"><a href="' . $tab_url . '">' . $tab_name . '</a></div></td>';
        }

        $tabs = '<table cellspacing="1" cellpadding="0" class="tab">
<tr>
	' . $tabs . '
</tr>
</table>';
    }
}

if (!isset($_GET['id']) || !(int)$_GET['id']) {
    $_GET['id'] = $usr->id;
}

if (!$_GET['id'] || ($p_user = db_saq('SELECT id, alias FROM fud30_users WHERE id=' . (int)$_GET['id']))) {
    ses_update_status($usr->sid, 'Browsing referrals');

    $c = uq('SELECT alias, id, join_date, posted_msg_count, home_page FROM fud30_users WHERE referer_id=' . (int)$_GET['id']);
    if (($r = db_rowarr($c))) {
        $refered_entry_data = '';
        do {
            $refered_entry_data .= '<tr class="' . alt_var('ref_alt', 'RowStyleA', 'RowStyleB') . '">
	<td class="wa nwGenText"><a href="/index.php?t=usrinfo&amp;id=' . $r[1] . '&amp;' . _rsid . '">' . $r[0] . '</a></td>
	<td class="ac nw Gentext">' . $r[3] . '</td>
	<td class="nw DateText">' . strftime('%a, %d %B %Y', $r[2]) . '</td>
	<td class="nw GenText"><a href="/index.php?t=showposts&amp;id=' . $r[1] . '&amp;' . _rsid . '"><img src="/theme/twig/images/show_posts.gif" alt="Show Posts" /></a>' . ((_uid && $FUD_OPT_1 & 1024) ? '&nbsp;<a href="/index.php?t=ppost&amp;' . _rsid . '&amp;toi=' . $r[1] . '"><img src="/theme/twig/images/msg_pm.gif" alt="PM" /></a>' : '') . (!empty($r[4]) ? '&nbsp;<a href="' . $r[4] . '"><img src="/theme/twig/images/homepage.gif" alt="Home"/></a>' : '') . ($FUD_OPT_2 & 1073741824 ? '&nbsp;<a href="/index.php?t=email&amp;toi=' . $r[1] . '&amp;' . _rsid . '" rel="nofollow"><img src="/theme/twig/images/msg_email.gif" alt="E-mail" /></a>' : '') . '</td>
</tr>';
        } while (($r = db_rowarr($c)));
    } else {
        $refered_entry_data = '<tr><td colspan="4" class="RowStyleB">No referrals yet.</th></tr>';
    }
    unset($c);
} else {
    invl_inp_err();
}

F()->response->tabs = $tabs;
F()->response->pUser = $p_user;
F()->response->referedEntryData = $refered_entry_data;
