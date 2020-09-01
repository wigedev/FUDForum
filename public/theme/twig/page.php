<?php
/**
* copyright            : (C) 2001-2018 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: page.php.t 6201 2018-09-15 18:17:02Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}

if (isset($_GET['id'])) {
	// Show a page.
	if (is_numeric($_GET['id'])) {
	      $page = db_sab('SELECT id, slug, title, foff, length, page_opt FROM fud30_pages WHERE id='. (int)$_GET['id'] . ($is_a ? '' : ' AND '. q_bitand('page_opt', 1) .' = 1'));
	} else {
	      $page = db_sab('SELECT id, slug, title, foff, length, page_opt FROM fud30_pages WHERE slug='. _esc($_GET['id']) . ($is_a ? '' : ' AND '. q_bitand('page_opt', 1) .' = 1'));
	}

	$TITLE_EXTRA = ': '. $page->title;

	fud_use('page_adm.inc', true);
	$page->body = fud_page::read_page_body($page->foff, $page->length, ($page->page_opt & 4));
} else {
	if (!($FUD_OPT_4 & 8)) {
		std_error('disabled');	// No pages to list.
	}

	// Show a list of pages.
	$page_list = '';
	$i = 0;
	$c = q('SELECT id, slug, title FROM fud30_pages WHERE '. q_bitand('page_opt', 1) .' = 1 AND '. q_bitand('page_opt', 2) .' = 2');
	while ($r = db_rowobj($c)) {
		$page_list .= '<li><a href="/index.php?t=page&id='.$r->slug.'&amp;'._rsid.'">'.$r->title.'</a>';
		$i++;
	}
}

/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> You have <span class="GenTextRed">('.$c.')</span> unread '.convertPlural($c, array('private message','private messages')).'</a></li>' : '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> Private Messaging</a></li>';
	} else {
		$ucp_private_msg = '';
	}

ses_update_status($usr->sid, 'Browsing page');

F()->response->page = $page;
F()->response->pageList = $page_list;
F()->response->pluralPageCount = convertPlural($i, array(''.$i.' page',''.$i.' pages'));
