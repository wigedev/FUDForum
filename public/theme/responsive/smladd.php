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
}


	include $FORUM_SETTINGS_PATH .'ps_cache';

	$smileys = '';
	foreach ($PS_SRC as $k => $v) {
		$smileys .= '<tr class="vb '.alt_var('sml_alt','RowStyleA','RowStyleB').'">
	<td><a href="javascript: insertSmiley(\' '.$PS_DST[$k].' \',\'\');">'.$v.'</a></td>
	<td>'.$PS_DST[$k].'</td>
</tr>';
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
<link rel="stylesheet" href="/theme/responsive/forum.css" />
</head>
<body>
<div class="content">
<table cellspacing="1"  cellpadding="0" width="100%" class="dashed">
<tr>
	<th>Emoticon</th>
	<th>Code</th>
</tr>
<?php echo ($smileys ? $smileys.'' : 'No emoticons available.'); ?>
<tr>
	<td colspan="2" class="ac RowStyleC">[<a href="javascript://" onclick="window.close();">close window</a>]</td>
</tr>
</table>
<script>
function insertSmiley(txt)
{
	var t = window.opener.document.getElementById('txtb');
	if (window.opener.document.selection) { // IE
		window.opener.document.selection.createRange();	
		if (t.createTextRange && t.caretPos) {
			var caretPos = t.caretPos;
			caretPos.text = txt + caretPos.text;
		} else {
			t.value += txt;
		}
	} else {
		var n = t.value.substring(0, t.selectionStart) + txt + t.value.substring(t.selectionStart, t.value.length);
		t.value = n;
	}
}
</script>
</div>
</body></html>
