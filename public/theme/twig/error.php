<?php
/**
 * copyright            : (C) 2001-2018 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: error.php.t 6286 2019-05-25 15:44:15Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
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

if (isset($_POST['ok'])) {
    check_return($usr->returnto);
}
$TITLE_EXTRA = ': Error Form';

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

if (isset($usr->data['er_msg'], $usr->data['err_t'])) {
    $error_message = $usr->data['er_msg'];
    $error_title = $usr->data['err_t'];
    ses_putvar((int)$usr->sid, null);
} else {
    $error_message = 'Invalid URL';
    $error_title = 'Error';
    ses_update_status($usr->sid, $error_message, 0, 0);
}

F()->response->error_title = $error_title;
F()->response->error_message = $error_message;
