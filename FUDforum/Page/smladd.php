<?php
/**
 * copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: smladd.php.t 4994 2010-09-02 17:33:29Z naudefj $
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


include $FORUM_SETTINGS_PATH . 'ps_cache';

F()->response->smileys = '';
foreach ($PS_SRC as $k => $v) {
    F()->response->smileys .= '<tr class="vb ' . alt_var('sml_alt', 'RowStyleA', 'RowStyleB') . '">
	<td><a href="javascript: insertSmiley(\' ' . $PS_DST[$k] . ' \',\'\');">' . $v . '</a></td>
	<td>' . $PS_DST[$k] . '</td>
</tr>';
}
