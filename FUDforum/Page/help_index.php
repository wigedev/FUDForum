<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: help_index.php.t 5030 2010-10-08 18:27:42Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}

	$section = isset($_GET['section']) ? $_GET['section'] : '';
	switch ($section) {
		case 'usermaintance':
		case 'boardusage':
		case 'readingposting':
			$file = '/var/www/forum.wigedev.com/public/theme/twig/help/'. $section .'.hlp';
			$show_return = true;
			break;
		default:
			$file = '/var/www/forum.wigedev.com/public/theme/twig/help/faq_index.hlp';
			$show_return = false;
	}

	ses_update_status($usr->sid, 'Reading the <a href="/index.php?t=help_index">Help</a>');
	$TITLE_EXTRA = ': Help';

/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> You have <span class="GenTextRed">('.$c.')</span> unread '.convertPlural($c, array('private message','private messages')).'</a></li>' : '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> Private Messaging</a></li>';
	} else {
		$ucp_private_msg = '';
	}

	$str = file_get_contents($file);

	$tt_len = strlen('TOPIC_TITLE:');
	$th_len = strlen('TOPIC_HELP:');
	$help_section_data = '';
	while (($str = strstr($str, 'TOPIC_TITLE:')) !== false) {
		$end_of = strpos($str, "\n");
		$topic_title = substr($str, $tt_len, $end_of-$tt_len);
		$str = strstr($str, 'TOPIC_HELP:');
		$str = substr($str, $th_len);
		$end_of_str = strstr($str, 'TOPIC_TITLE:');
		$topic_help = substr($str, 0, strlen($str)-strlen($end_of_str));
		$str = $end_of_str;
		if ($FUD_OPT_2 & 32768 && !empty($_SERVER['PATH_INFO'])) {
			$rs = 'S='. str_replace(array('/', '?'), array('&amp;', ''), _rsid);
		} else {
			$rs = _rsid;
		}
		$topic_help = str_replace(array('%_rsid%', '&amp;#', '&#'), array($rs, '#', '#'), $topic_help);

		$help_section_data .= '<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th>'.$topic_title.'</th>
</tr>
<tr>
	<td class="content">
		<div class="GenText wa">
			'.$topic_help.'
		</div>
		<div class="GenText ar">
			<a href="javascript://" onclick="chng_focus(\'top\');">back to top</a>
		</div>
	</td>
</tr>
</table>
<br />';
	}
$renderer->show_return = $show_return;
$renderer->help_section_data = $help_section_data;
