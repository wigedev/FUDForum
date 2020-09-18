<?php
/**
 * copyright            : (C) 2001-2017 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: pre_reg.php.t 6078 2017-09-25 14:57:31Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
}

if (isset($_POST['disagree'])) {
    if ($FUD_OPT_2 & 32768) {
        header('Location: /index.php/i/' . _rsidl);
    } else {
        header('Location: /index.php?' . _rsidl);
    }
    exit;
} else if (isset($_POST['agree'])) {
    if ($FUD_OPT_2 & 32768) {
        header('Location: /index.php/re/' . ($FUD_OPT_1 & 1048576 ? (int)$_POST['coppa'] : 0) . '/' . _rsidl);
    } else {
        header('Location: /index.php?t=register&' . _rsidl . '&reg_coppa=' . ($FUD_OPT_1 & 1048576 ? (int)$_POST['coppa'] : 0));
    }
    exit;
}

ses_update_status($usr->sid, 'Reading the forum rules', 0, 0);

$TITLE_EXTRA = ': Forum Terms';

/* Print number of unread private messages in User Control Panel. */
if (__fud_real_user__ && $FUD_OPT_1 & 1024) {    // PM_ENABLED
    $c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id=' . _uid . ' AND fldr=1 AND read_stamp=0');
    $ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;' . _rsid . '" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> You have <span class="GenTextRed">(' . $c . ')</span> unread ' . convertPlural($c, array('private message', 'private messages')) . '</a></li>' : '<li><a href="/index.php?t=pmsg&amp;' . _rsid . '" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> Private Messaging</a></li>';
} else {
    $ucp_private_msg = '';
}
F()->response->coppa = isset($_GET['coppa']) ? (int)$_GET['coppa'] : 0; //TODO: This should be sanitized.
