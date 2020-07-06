<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: avatarsel.php.t 4994 2010-09-02 17:33:29Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

	define('plain_form', 1);

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function alt_var($key)
{
	if (!isset($GLOBALS['_ALTERNATOR_'][$key])) {
		$args = func_get_args(); unset($args[0]);
		$GLOBALS['_ALTERNATOR_'][$key] = array('p' => 2, 't' => func_num_args(), 'v' => $args);
		return $args[1];
	}
	$k =& $GLOBALS['_ALTERNATOR_'][$key];
	if ($k['p'] == $k['t']) {
		$k['p'] = 1;
	}
	return $k['v'][$k['p']++];
}function tmpl_draw_select_opt($values, $names, $selected)
{
	$vls = explode("\n", $values);
	$nms = explode("\n", $names);

	if (count($vls) != count($nms)) {
		exit("FATAL ERROR: inconsistent number of values inside a select<br />\n");
	}

	$options = '';
	foreach ($vls as $k => $v) {
		$options .= '<option value="'.$v.'"'.($v == $selected ? ' selected="selected"' : '' )  .'>'.$nms[$k].'</option>';
	}

	return $options;
}


	$TITLE_EXTRA = ': Avatar Selection Form';

	$galleries = array();
	$c = uq('SELECT DISTINCT(gallery) FROM fud30_avatar');
	while ($r = db_rowarr($c)) {
		$galleries[$r[0]] = htmlspecialchars($r[0]);
	}
	unset($c, $r);

	if (count($galleries) > 1) {
		$gal = isset($_POST['gal'], $galleries[$_POST['gal']]) ? $_POST['gal'] : 'default';
		$select = tmpl_draw_select_opt(implode("\n", $galleries), implode("\n", array_keys($galleries)), $gal);
		$select = '<form id="avsel" method="post" action="/index.php?t=avatarsel">'._hs.'
	<select name="gal" onchange="document.forms[\'avsel\'].submit();">'.$select.'</select>
	<input type="submit" name="sbm" value="Update" />
</form>
<hr />';
	} else {
		$gal = 'default';
		$select = '';
	}

	/* Here we draw the avatar control. */
	$icons_per_row = 5;
	$c = uq('SELECT id, descr, img FROM fud30_avatar WHERE gallery=\''. $gal .'\' ORDER BY id');
	$avatars_data = '';
	$col = 0;
	while ($r = db_rowarr($c)) {
		if (!($col++ % $icons_per_row) && $col != 1) {	// New row?
			$avatars_data .= '</tr><tr>';
		}
		$avatars_data .= '<td class="'.alt_var('avatarsel_cl','Av1','Av2').'">
<a href="javascript: window.opener.document.forms[\'fud_register\'].reg_avatar.value=\''.$r[0].'\'; window.opener.document.reg_avatar_img.src=\'/images/avatars/'.$r[2].'\'; window.close();"><img src="/images/avatars/'.$r[2].'" alt="" />
<br />
<span class="SmallText">'.$r[1].'</span></a>
</td>';
	}
	unset($c);

	if (!$avatars_data) {
		$avatars_data = '<td class="NoAvatar">No Avatars available</td>';
	}


?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name=viewport content="width=device-width, initial-scale=1">
<title><?php echo $GLOBALS['FORUM_TITLE'].$TITLE_EXTRA; ?></title>
<script src="/js/lib.js"></script>
<script async src="/js/jquery.js"></script>
<script async src="/js/ui/jquery-ui.js"></script>
<link rel="stylesheet" href="/theme/twig/forum.css" />
</head>
<body>
<div class="content">
<?php echo $select; ?>
<table border="0" cellspacing="1" cellpadding="2">
<tr>
	<?php echo $avatars_data; ?>
</tr>
</table>
</div>
</body></html>
