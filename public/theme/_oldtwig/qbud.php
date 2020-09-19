<?php
/**
 * copyright            : (C) 2001-2011 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: qbud.php.t 5324 2011-07-15 14:04:17Z naudefj $
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

if (!_uid) {
    std_error('login');
}

if (isset($_POST['names']) && is_array($_POST['names'])) {
    $names = addcslashes(implode(';', $_POST['names']), '"\\');
    ?>
    <html>
    <body>
    <script>
        if (window.opener.document.forms['post_form'].msg_to_list.value.length > 0) {
            window.opener.document.forms['post_form'].msg_to_list.value = window.opener.document.forms['post_form'].msg_to_list.value + ';' + "<?php echo $names; ?>";
        } else {
            window.opener.document.forms['post_form'].msg_to_list.value = window.opener.document.forms['post_form'].msg_to_list.value + "<?php echo $names; ?>";
        }
        window.close();
    </script>
    </body>
    </html>
    <?php
    exit;
}

$buddies = '';
$c = uq('SELECT u.alias FROM fud30_buddy b INNER JOIN fud30_users u ON b.bud_id=u.id WHERE b.user_id=' . _uid . ' AND b.user_id>1');
while ($r = db_rowarr($c)) {
    $buddies .= '<tr class="' . alt_var('search_alt', 'RowStyleA', 'RowStyleB') . '">
	<td class="GenText">' . $r[0] . '</td>
	<td class="ac"><input type="checkbox" name="names[]" value="' . $r[0] . '" /></td>
</tr>';
}
unset($c);

F()->response->buddies = $buddies;
F()->response->altVar = function ($a, $firstStyle, $secondStyle) {
    return alt_var($a, $firstStyle, $secondStyle);
};
