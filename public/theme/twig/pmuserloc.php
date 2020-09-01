<?php
/**
 * copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: pmuserloc.php.t 4994 2010-09-02 17:33:29Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

define('plain_form', 1);

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

if (empty($_GET['js_redr'])) {
    exit;
}

if (!_uid) {
    std_error('login');
} else if (!($FUD_OPT_1 & (8388608 | 4194304))) {
    std_error('disabled');
}


$usr_login = isset($_GET['usr_login']) && is_string($_GET['usr_login']) ? trim($_GET['usr_login']) : '';
$overwrite = isset($_GET['overwrite']) ? (int)$_GET['overwrite'] : 0;

$js_redr = $_GET['js_redr'];
switch ($js_redr) {
    case 'post_form.msg_to_list':
    case 'groupmgr.gr_member':
    case 'buddy_add.add_login':
        break;
    default:
        exit;
}
list($frm, $fld) = explode('.', $js_redr);

$find_user_data = '';
if ($usr_login) {
    $c = uq('SELECT alias FROM fud30_users WHERE alias LIKE ' . _esc(char_fix(htmlspecialchars(addcslashes($usr_login . '%', '\\')))) . ' AND id>1');
    $i = 0;
    while ($r = db_rowarr($c)) {
        if ($overwrite) {
            $retlink = 'javascript: window.opener.document.forms[\'' . $frm . '\'].' . $fld . '.value=\'' . addcslashes($r[0], "'\\") . '\'; window.close();';
        } else {
            $retlink = 'javascript:
						if (!window.opener.document.forms[\'' . $frm . '\'].' . $fld . '.value) {
							window.opener.document.forms[\'' . $frm . '\'].' . $fld . '.value = \'' . addcslashes($r[0], "'\\") . '\';
						} else {
							window.opener.document.forms[\'' . $frm . '\'].' . $fld . '.value = window.opener.document.forms[\'' . $frm . '\'].' . $fld . '.value + \'; \' + \'' . addcslashes($r[0], "'\\") . '; \';
						}
					window.close();';
        }
        $find_user_data .= '<tr class="' . alt_var('pmuserloc_alt', 'RowStyleA', 'RowStyleB') . '">
	<td><a href="' . $retlink . '">' . $r[0] . '</a></td>
</tr>';
        ++$i;
    }
    unset($c);
    if (!$find_user_data) {
        $find_user_data = '<tr>
	<td colspan="2">No Result</td>
</tr>';
    }
}

F()->response->username = char_fix(htmlspecialchars($usr_login));
F()->response->jsRedr = $js_redr;
F()->response->overwrite = $overwrite;
F()->response->findUserData = $find_user_data;
