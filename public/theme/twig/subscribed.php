<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: subscribed.php.t 5072 2010-11-11 17:12:40Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function is_forum_notified($user_id, $forum_id)
{
	return q_singleval('SELECT 1 FROM fud30_forum_notify WHERE user_id='. $user_id .' AND forum_id='. $forum_id);
}

function forum_notify_add($user_id, $forum_id)
{
	db_li('INSERT INTO fud30_forum_notify (user_id, forum_id) VALUES ('. $user_id .', '. $forum_id .')', $ret);
}

function forum_notify_del($user_id, $forum_id)
{
	q('DELETE FROM fud30_forum_notify WHERE user_id='. $user_id .' AND forum_id='. $forum_id);
}function is_notified($user_id, $thread_id)
{
	return q_singleval('SELECT * FROM fud30_thread_notify WHERE thread_id='. (int)$thread_id .' AND user_id='. $user_id);
}

function thread_notify_add($user_id, $thread_id)
{
	db_li('INSERT INTO fud30_thread_notify (user_id, thread_id) VALUES ('. $user_id .', '. (int)$thread_id .')', $ret);
}

function thread_notify_del($user_id, $thread_id)
{
	q('DELETE FROM fud30_thread_notify WHERE thread_id='. (int)$thread_id .' AND user_id='. $user_id);
}

function thread_bookmark_add($user_id, $thread_id)
{
	db_li('INSERT INTO fud30_bookmarks (user_id, thread_id) VALUES ('. $user_id .', '. (int)$thread_id .')', $ret);
}

function thread_bookmark_del($user_id, $thread_id)
{
	q('DELETE FROM fud30_bookmarks WHERE thread_id='. (int)$thread_id .' AND user_id='. $user_id);
}function pager_replace(&$str, $s, $c)
{
	$str = str_replace(array('%s', '%c'), array($s, $c), $str);
}

function tmpl_create_pager($start, $count, $total, $arg, $suf='', $append=1, $js_pager=0, $no_append=0)
{
	if (!$count) {
		$count =& $GLOBALS['POSTS_PER_PAGE'];
	}
	if ($total <= $count) {
		return;
	}

	$upfx = '';
	if ($GLOBALS['FUD_OPT_2'] & 32768 && (!empty($_SERVER['PATH_INFO']) || strpos($arg, '?') === false)) {
		if (!$suf) {
			$suf = '/';
		} else if (strpos($suf, '//') !== false) {
			$suf = preg_replace('!/+!', '/', $suf);
		}
	} else if (!$no_append) {
		$upfx = '&amp;start=';
	}

	$cur_pg = ceil($start / $count);
	$ttl_pg = ceil($total / $count);

	$page_pager_data = '';

	if (($page_start = $start - $count) > -1) {
		if ($append) {
			$page_first_url = $arg . $upfx . $suf;
			$page_prev_url = $arg . $upfx . $page_start . $suf;
		} else {
			$page_first_url = $page_prev_url = $arg;
			pager_replace($page_first_url, 0, $count);
			pager_replace($page_prev_url, $page_start, $count);
		}

		$page_pager_data .= !$js_pager ? '&nbsp;<a href="'.$page_first_url.'" class="PagerLink">&laquo;</a>&nbsp;&nbsp;<a href="'.$page_prev_url.'" accesskey="p" class="PagerLink">&lsaquo;</a>&nbsp;&nbsp;' : '&nbsp;<a href="javascript://" onclick="'.$page_first_url.'" class="PagerLink">&laquo;</a>&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_prev_url.'" class="PagerLink">&lsaquo;</a>&nbsp;&nbsp;';
	}

	$mid = ceil($GLOBALS['GENERAL_PAGER_COUNT'] / 2);

	if ($ttl_pg > $GLOBALS['GENERAL_PAGER_COUNT']) {
		if (($mid + $cur_pg) >= $ttl_pg) {
			$end = $ttl_pg;
			$mid += $mid + $cur_pg - $ttl_pg;
			$st = $cur_pg - $mid;
		} else if (($cur_pg - $mid) <= 0) {
			$st = 0;
			$mid += $mid - $cur_pg;
			$end = $mid + $cur_pg;
		} else {
			$st = $cur_pg - $mid;
			$end = $mid + $cur_pg;
		}

		if ($st < 0) {
			$start = 0;
		}
		if ($end > $ttl_pg) {
			$end = $ttl_pg;
		}
		if ($end - $start > $GLOBALS['GENERAL_PAGER_COUNT']) {
			$end = $start + $GLOBALS['GENERAL_PAGER_COUNT'];
		}
	} else {
		$end = $ttl_pg;
		$st = 0;
	}

	while ($st < $end) {
		if ($st != $cur_pg) {
			$page_start = $st * $count;
			if ($append) {
				$page_page_url = $arg . $upfx . $page_start . $suf;
			} else {
				$page_page_url = $arg;
				pager_replace($page_page_url, $page_start, $count);
			}
			$st++;
			$page_pager_data .= !$js_pager ? '<a href="'.$page_page_url.'" class="PagerLink">'.$st.'</a>&nbsp;&nbsp;' : '<a href="javascript://" onclick="'.$page_page_url.'" class="PagerLink">'.$st.'</a>&nbsp;&nbsp;';
		} else {
			$st++;
			$page_pager_data .= !$js_pager ? $st.'&nbsp;&nbsp;' : $st.'&nbsp;&nbsp;';
		}
	}

	$page_pager_data = substr($page_pager_data, 0 , strlen((!$js_pager ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;')) * -1);

	if (($page_start = $start + $count) < $total) {
		$page_start_2 = ($st - 1) * $count;
		if ($append) {
			$page_next_url = $arg . $upfx . $page_start . $suf;
			// $page_last_url = $arg . $upfx . $page_start_2 . $suf;
			$page_last_url = $arg . $upfx . floor($total-1/$count)*$count . $suf;
		} else {
			$page_next_url = $page_last_url = $arg;
			pager_replace($page_next_url, $upfx . $page_start, $count);
			pager_replace($page_last_url, $upfx . $page_start_2, $count);
		}
		$page_pager_data .= !$js_pager ? '&nbsp;&nbsp;<a href="'.$page_next_url.'" accesskey="n" class="PagerLink">&rsaquo;</a>&nbsp;&nbsp;<a href="'.$page_last_url.'" class="PagerLink">&raquo;</a>' : '&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_next_url.'" class="PagerLink">&rsaquo;</a>&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_last_url.'" class="PagerLink">&raquo;</a>';
	}

	return !$js_pager ? '<span class="SmallText fb">Pages ('.$ttl_pg.'): ['.$page_pager_data.']</span>' : '<span class="SmallText fb">Pages ('.$ttl_pg.'): ['.$page_pager_data.']</span>';
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

	if (!_uid) {
		std_error('login');
	}

	/* Delete forum subscription. */
	if (isset($_GET['frm_id']) && ($_GET['frm_id'] = (int)$_GET['frm_id']) && sq_check(0, $usr->sq)) {
		forum_notify_del(_uid, $_GET['frm_id']);
	}

	/* Delete thread subscription. */
	if (isset($_GET['th']) && ($_GET['th'] = (int)$_GET['th']) && sq_check(0, $usr->sq)) {
		thread_notify_del(_uid, $_GET['th']);
	}

	if (!empty($_POST['f_unsub_all'])) {
		q('DELETE FROM fud30_forum_notify WHERE user_id='. _uid);
	} else if (!empty($_POST['t_unsub_all'])) {
		q('DELETE FROM fud30_thread_notify WHERE user_id='. _uid);
	} else if (isset($_POST['f_unsub_sel'], $_POST['fe'])) {
		$list = array();
		foreach((array)$_POST['fe'] as $v) {
			$list[(int)$v] = (int) $v;
		}
		q('DELETE FROM fud30_forum_notify WHERE user_id='. _uid .' AND forum_id IN('. implode(',', $list) .')');
	} else if (isset($_POST['t_unsub_sel'], $_POST['te'])) {
		$list = array();
		foreach((array)$_POST['te'] as $v) {
			$list[(int)$v] = (int) $v;
		}
		q('DELETE FROM fud30_thread_notify WHERE user_id='. _uid .' AND thread_id IN('. implode(',', $list) .')');
	}

	ses_update_status($usr->sid, 'Browsing own subscriptions');

/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> You have <span class="GenTextRed">('.$c.')</span> unread '.convertPlural($c, array('private message','private messages')).'</a></li>' : '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> Private Messaging</a></li>';
	} else {
		$ucp_private_msg = '';
	}$tabs = '';
if (_uid) {
	$tablist = array(
'Notifications'=>'uc',
'Account Settings'=>'register',
'Subscriptions'=>'subscribed',
'Bookmarks'=>'bookmarked',
'Referrals'=>'referals',
'Buddy List'=>'buddy_list',
'Ignore List'=>'ignore_list',
'Show Own Posts'=>'showposts'
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

		foreach($tablist as $tab_name => $tab) {
			$tab_url = '/index.php?t='. $tab . (s ? '&amp;S='. s : '');
			if ($tab == 'referals') {
				if (!($FUD_OPT_2 & 8192)) {
					continue;
				}
				$tab_url .= '&amp;id='. _uid;
			} else if ($tab == 'showposts') {
				$tab_url .= '&amp;id='. _uid;
			}
			$tabs .= $pg == $tab ? '<td class="tabON"><div class="tabT"><a class="tabON" href="'.$tab_url.'">'.$tab_name.'</a></div></td>' : '<td class="tabI"><div class="tabT"><a href="'.$tab_url.'">'.$tab_name.'</a></div></td>';
		}

		$tabs = '<table cellspacing="1" cellpadding="0" class="tab">
<tr>
	'.$tabs.'
</tr>
</table>';
	}
}

	/* Fetch a list of all the accessible forums. */
	$lmt = '';
	if (!$is_a) {
		$c = uq('SELECT g1.resource_id
				FROM fud30_group_cache g1
				LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g1.resource_id=g2.resource_id
				LEFT JOIN fud30_mod m ON m.forum_id=g1.resource_id AND m.user_id='. _uid .'
				WHERE g1.user_id=2147483647 AND (m.id IS NULL AND '. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 2) .'=0)');
		while ($r = db_rowarr($c)) {
			$lmt .= $r[0] .',';
		}
		unset($c);
		if ($lmt) {
			$lmt[strlen($lmt) - 1] = ' ';
			$lmt = ' AND forum_id NOT IN('. $lmt .') ';
		} else {
			$lmt = ' AND forum_id NOT IN(0) ';
		}
	}

	$c = uq('SELECT f.id, f.name FROM fud30_forum_notify fn LEFT JOIN fud30_forum f ON fn.forum_id=f.id WHERE fn.user_id='. _uid .' '. $lmt .' ORDER BY f.last_post_id DESC');

	$subscribed_thread_data = $subscribed_forum_data = '';
	while (($r = db_rowarr($c))) {
		$subscribed_forum_data .= '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td><input type="checkbox" name="fe[]" value="'.$r[0].'" /></td>
	<td class="nw"><a href="/index.php?t=subscribed&amp;frm_id='.$r[0].'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">Unsubscribe</a></td>
	<td class="wa"><a href="/index.php?t='.t_thread_view.'&amp;frm_id='.$r[0].'&amp;'._rsid.'">'.$r[1].'</a></td>
</tr>';
	}
	unset($c);

	if (!isset($_GET['start']) || !($start = (int)$_GET['start'])) {
		$start = 0;
	}

	$c = uq(q_limit('SELECT /*!40000 SQL_CALC_FOUND_ROWS */ t.id, m.subject, f.name FROM fud30_thread_notify tn INNER JOIN fud30_thread t ON tn.thread_id=t.id INNER JOIN fud30_forum f ON f.id=t.forum_id INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE tn.user_id='. _uid .' '. $lmt .' ORDER BY t.last_post_id DESC',
			$THREADS_PER_PAGE, $start));

	while (($r = db_rowarr($c))) {
		$subscribed_thread_data .= '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td><input type="checkbox" name="te[]" value="'.$r[0].'" /></td>
	<td class="nw"><a href="/index.php?t=subscribed&amp;th='.$r[0].'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">Unsubscribe</a></td>
	<td class="wa">'.$r[2].' &raquo; <a href="/index.php?t='.d_thread_view.'&amp;th='.$r[0].'&amp;unread=1&amp;'._rsid.'">'.$r[1].'</a></td>
</tr>';
	}
	unset($c);

	/* Since a person can have MANY subscribed threads, we need a pager & for the pager we need a entry count. */
	if (($total = (int) q_singleval('SELECT /*!40000 FOUND_ROWS(), */ -1')) < 0) {
		$total = q_singleval('SELECT count(*) FROM fud30_thread_notify tn LEFT JOIN fud30_thread t ON tn.thread_id=t.id INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE tn.user_id='. _uid .' '. $lmt);
	}

	if ($FUD_OPT_2 & 32768) {
		$pager = tmpl_create_pager($start, $THREADS_PER_PAGE, $total, '/index.php/sl/start/', '/'. _rsid .'#fff');
	} else {
		$pager = tmpl_create_pager($start, $THREADS_PER_PAGE, $total, '/index.php?t=subscribed&a=1&'. _rsid, '#fff');
	}

if ($FUD_OPT_2 & 2 || $is_a) {	// PUBLIC_STATS is enabled or Admin user.
	$page_gen_time = number_format(microtime(true) - __request_timestamp_exact__, 5);
	$page_stats = $FUD_OPT_2 & 2 ? '<br /><div class="SmallText al">Total time taken to generate the page: '.convertPlural($page_gen_time, array(''.$page_gen_time.' seconds')).'</div>' : '<br /><div class="SmallText al">Total time taken to generate the page: '.convertPlural($page_gen_time, array(''.$page_gen_time.' seconds')).'</div>';
} else {
	$page_stats = '';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
	<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="<?php echo (!empty($META_DESCR) ? $META_DESCR.'' : $GLOBALS['FORUM_DESCR'].''); ?>" />
	<title><?php echo $GLOBALS['FORUM_TITLE'].$TITLE_EXTRA; ?></title>
	<link rel="search" type="application/opensearchdescription+xml" title="<?php echo $GLOBALS['FORUM_TITLE']; ?> Search" href="/open_search.php" />
	<?php echo $RSS; ?>
	<link rel="stylesheet" href="/theme/twig/forum.css" media="screen" title="Default Forum Theme" />
	<link rel="stylesheet" href="/js/ui/jquery-ui.css" media="screen" />
	<script src="/js/jquery.js"></script>
	<script async src="/js/ui/jquery-ui.js"></script>
	<script src="/js/lib.js"></script>
</head>
<body>
<!--  -->
<div class="header">
  <?php echo ($GLOBALS['FUD_OPT_1'] & 1 && $GLOBALS['FUD_OPT_1'] & 16777216 ? '
  <div class="headsearch">
    <form id="headsearch" method="get" action="/index.php">'._hs.'
      <input type="hidden" name="t" value="search" />
      <br /><label accesskey="f" title="Forum Search">Forum Search:<br />
      <input type="search" name="srch" value="" size="20" placeholder="Forum Search" /></label>
      <input type="image" src="/theme/twig/images/search.png" title="Search" name="btn_submit">&nbsp;
    </form>
  </div>
  ' : ''); ?>
  <a href="/" title="Home">
    <img class="headimg" src="/theme/twig/images/header.gif" alt="" align="left" height="80" />
    <span class="headtitle"><?php echo $GLOBALS['FORUM_TITLE']; ?></span>
  </a><br />
  <span class="headdescr"><?php echo $GLOBALS['FORUM_DESCR']; ?><br /><br /></span>
</div>
<div class="content">

<!-- Table for sidebars. -->
<table width="100%"><tr><td>
<div id="UserControlPanel">
<ul>
	<?php echo $ucp_private_msg; ?>
	<?php echo ($FUD_OPT_4 & 16 ? '<li><a href="/index.php?t=blog&amp;'._rsid.'" title="Blog"><img src="/theme/twig/images/blog.png" alt="" /> Blog</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_4 & 8 ? '<li><a href="/index.php?t=page&amp;'._rsid.'" title="Pages"><img src="/theme/twig/images/pages.png" alt="" /> Pages</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_3 & 134217728 ? '<li><a href="/index.php?t=cal&amp;'._rsid.'" title="Calendar"><img src="/theme/twig/images/calendar.png" alt="" /> Calendar</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_1 & 16777216 ? ' <li><a href="/index.php?t=search'.(isset($frm->forum_id) ? '&amp;forum_limiter='.(int)$frm->forum_id.'' : '' )  .'&amp;'._rsid.'" title="Search"><img src="/theme/twig/images/top_search.png" alt="" /> Search</a></li>' : ''); ?>
	<li><a accesskey="h" href="/index.php?t=help_index&amp;<?php echo _rsid; ?>" title="Help"><img src="/theme/twig/images/top_help.png" alt="" /> Help</a></li>
	<?php echo (($FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304) || $usr->users_opt & 1048576) ? '<li><a href="/index.php?t=finduser&amp;btn_submit=Find&amp;'._rsid.'" title="Members"><img src="/theme/twig/images/top_members.png" alt="" /> Members</a></li>' : ''); ?>
	<?php echo (__fud_real_user__ ? '<li><a href="/index.php?t=uc&amp;'._rsid.'" title="Access the user control panel"><img src="/theme/twig/images/top_profile.png" alt="" /> Control Panel</a></li>' : ($FUD_OPT_1 & 2 ? '<li><a href="/index.php?t=register&amp;'._rsid.'" title="Register"><img src="/theme/twig/images/top_register.png" alt="" /> Register</a></li>' : '')).'
	'.(__fud_real_user__ ? '<li><a href="/index.php?t=login&amp;'._rsid.'&amp;logout=1&amp;SQ='.$GLOBALS['sq'].'" title="Logout"><img src="/theme/twig/images/top_logout.png" alt="" /> Logout [ '.htmlspecialchars($usr->alias, null, null, false).' ]</a></li>' : '<li><a href="/index.php?t=login&amp;'._rsid.'" title="Login"><img src="/theme/twig/images/top_login.png" alt="" /> Login</a></li>'); ?>
	<li><a href="/index.php?t=index&amp;<?php echo _rsid; ?>" title="Home"><img src="/theme/twig/images/top_home.png" alt="" /> Home</a></li>
	<?php echo ($is_a || ($usr->users_opt & 268435456) ? '<li><a href="/adm/index.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'" title="Administration"><img src="/theme/twig/images/top_admin.png" alt="" /> Administration</a></li>' : ''); ?>
</ul>
</div>
<?php echo $tabs; ?>
<form method="post" id="subscribe" action="/index.php?t=subscribed">
<?php echo _hs; ?>
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th colspan="3">Subscribed Forums<a name="fff"></a></th>
</tr>
<?php echo ($subscribed_forum_data ? '
'.$subscribed_forum_data.'
<tr class="RowStyleC">
	<td class="ac" colspan="2"><input type="submit" class="button" name="f_unsub_sel" value="Unsubscribe" /></td>
	<td class="ar"><input type="submit" class="button" name="f_unsub_all" value="Unsubscribe From All Forums" /></td>
</tr>
' : '
<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td colspan="3">No subscribed forums</td>
</tr>
'); ?>
<tr>
	<th colspan="3">Subscribed Topics</th>
</tr>
<?php echo ($subscribed_thread_data ? '
'.$subscribed_thread_data.'
<tr class="RowStyleC">
	<td class="ac" colspan="2"><input type="submit" class="button" name="t_unsub_sel" value="Unsubscribe" /></td>
	<td class="ar"><input type="submit" class="button" name="t_unsub_all" value="Unsubscribe From All Topics" /></td>
</tr>
' : '
<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td colspan="3">No subscribed topics</td>
</tr>
'); ?>
</table>
</form>
<?php echo $pager; ?>
<br /><div class="ac"><span class="curtime"><b>Current Time:</b> <?php echo strftime('%a %b %d %H:%M:%S %Z %Y', __request_timestamp__); ?></span></div>
<?php echo $page_stats; ?>
<?php echo (!empty($RIGHT_SIDEBAR) ? '
</td><td width="200px" align-"right" valign="top" class="sidebar-right">
	'.$RIGHT_SIDEBAR.'
' : ''); ?>
</td></tr></table>

</div>
<div class="footer ac">
	<b>.::</b>
	<a href="mailto:<?php echo $GLOBALS['ADMIN_EMAIL']; ?>">Contact</a>
	<b>::</b>
	<a href="/index.php?t=index&amp;<?php echo _rsid; ?>">Home</a>
	<b>::.</b>
	<p class="SmallText">Powered by: FUDforum <?php echo $GLOBALS['FORUM_VERSION']; ?>.<br />Copyright &copy;2001-2020 <a href="http://fudforum.org/">FUDforum Bulletin Board Software</a></p>
</div>

</body></html>
