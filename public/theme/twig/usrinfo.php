<?php
/**
* copyright            : (C) 2001-2013 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: usrinfo.php.t 5668 2013-09-10 07:42:45Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}$GLOBALS['__revfs'] = array('&quot;', '&lt;', '&gt;', '&amp;');
$GLOBALS['__revfd'] = array('"', '<', '>', '&');

function reverse_fmt($data)
{
	$s = $d = array();
	foreach ($GLOBALS['__revfs'] as $k => $v) {
		if (strpos($data, $v) !== false) {
			$s[] = $v;
			$d[] = $GLOBALS['__revfd'][$k];
		}
	}

	return $s ? str_replace($s, $d, $data) : $data;
}function &get_all_read_perms($uid, $mod)
{
	$limit = array(0);

	$r = uq('SELECT resource_id, group_cache_opt FROM fud30_group_cache WHERE user_id='. _uid);
	while ($ent = db_rowarr($r)) {
		$limit[$ent[0]] = $ent[1] & 2;
	}
	unset($r);

	if (_uid) {
		if ($mod) {
			$r = uq('SELECT forum_id FROM fud30_mod WHERE user_id='. _uid);
			while ($ent = db_rowarr($r)) {
				$limit[$ent[0]] = 2;
			}
			unset($r);
		}

		$r = uq('SELECT resource_id FROM fud30_group_cache WHERE resource_id NOT IN ('. implode(',', array_keys($limit)) .') AND user_id=2147483647 AND '. q_bitand('group_cache_opt', 2) .' > 0');
		while ($ent = db_rowarr($r)) {
			if (!isset($limit[$ent[0]])) {
				$limit[$ent[0]] = 2;
			}
		}
		unset($r);
	}

	return $limit;
}

function perms_from_obj($obj, $adm)
{
	$perms = 1|2|4|8|16|32|64|128|256|512|1024|2048|4096|8192|16384|32768|262144;

	if ($adm || $obj->md) {
		return $perms;
	}

	return ($perms & $obj->group_cache_opt);
}

function make_perms_query(&$fields, &$join, $fid='')
{
	if (!$fid) {
		$fid = 'f.id';
	}

	if (_uid) {
		$join = ' INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id='. $fid .' LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id='. $fid .' ';
		$fields = ' COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS group_cache_opt ';
	} else {
		$join = ' INNER JOIN fud30_group_cache g1 ON g1.user_id=0 AND g1.resource_id='. $fid .' ';
		$fields = ' g1.group_cache_opt ';
	}
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
}include $GLOBALS['FORUM_SETTINGS_PATH'] .'ip_filter_cache';
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'login_filter_cache';
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'email_filter_cache';

function is_ip_blocked($ip)
{
	if (empty($GLOBALS['__FUD_IP_FILTER__'])) {
		return;
	}
	$block =& $GLOBALS['__FUD_IP_FILTER__'];
	list($a,$b,$c,$d) = explode('.', $ip);

	if (!isset($block[$a])) {
		return;
	}
	if (isset($block[$a][$b][$c][$d])) {
		return 1;
	}

	if (isset($block[$a][256])) {
		$t = $block[$a][256];
	} else if (isset($block[$a][$b])) {
		$t = $block[$a][$b];
	} else {
		return;
	}

	if (isset($t[$c])) {
		$t = $t[$c];
	} else if (isset($t[256])) {
		$t = $t[256];
	} else {
		return;
	}

	if (isset($t[$d]) || isset($t[256])) {
		return 1;
	}
}

function is_login_blocked($l)
{
	foreach ($GLOBALS['__FUD_LGN_FILTER__'] as $v) {
		if (preg_match($v, $l)) {
			return 1;
		}
	}
	return;
}

function is_email_blocked($addr)
{
	if (empty($GLOBALS['__FUD_EMAIL_FILTER__'])) {
		return;
	}
	$addr = strtolower($addr);
	foreach ($GLOBALS['__FUD_EMAIL_FILTER__'] as $k => $v) {
		if (($v && (strpos($addr, $k) !== false)) || (!$v && preg_match($k, $addr))) {
			return 1;
		}
	}
	return;
}

function is_allowed_user(&$usr, $simple=0)
{
	/* Check if the ban expired. */
	if (($banned = $usr->users_opt & 65536) && $usr->ban_expiry && $usr->ban_expiry < __request_timestamp__) {
		q('UPDATE fud30_users SET users_opt = '. q_bitand('users_opt', ~65536) .' WHERE id='. $usr->id);
		$usr->users_opt ^= 65536;
		$banned = 0;
	} 

	if ($banned || is_email_blocked($usr->email) || is_login_blocked($usr->login) || is_ip_blocked(get_ip())) {
		$ban_expiry = (int) $usr->ban_expiry;
		$ban_reason = $usr->ban_reason;
		if (!$simple) { // On login page we already have anon session.
			ses_delete($usr->sid);
			$usr = ses_anon_make();
		}
		setcookie($GLOBALS['COOKIE_NAME'].'1', 'd34db33fd34db33fd34db33fd34db33f', ($ban_expiry ? $ban_expiry : (__request_timestamp__ + 63072000)), $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
		if ($banned) {
			error_dialog('ERROR: You have been banned.', 'Your account was '.($ban_expiry ? 'temporarily banned until '.strftime('%a, %d %B %Y %H:%M', $ban_expiry) : 'permanently banned' )  .' from accessing the site, due to a violation of the forum&#39;s rules.
<br />
<br />
<span class="GenTextRed">'.$ban_reason.'</span>');
		} else {
			error_dialog('ERROR: Your account has been filtered out.', 'Your account has been blocked from accessing the forum due to one of the installed user filters.');
		}
	}

	if ($simple) {
		return;
	}

	if ($GLOBALS['FUD_OPT_1'] & 1048576 && $usr->users_opt & 262144) {
		error_dialog('ERROR: Your account is not yet confirmed', 'We have not received a confirmation from your parent and/or legal guardian, which would allow you to post messages. If you lost your COPPA form, <a href="/index.php?t=coppa_fax&amp;'._rsid.'">view it again</a>.');
	}

	if ($GLOBALS['FUD_OPT_2'] & 1 && !($usr->users_opt & 131072)) {
		std_error('emailconf');
	}

	if ($GLOBALS['FUD_OPT_2'] & 1024 && $usr->users_opt & 2097152) {
		error_dialog('Unverified Account', 'The administrator had chosen to review all accounts manually prior to activation. Until your account has been validated by the administrator you will not be able to utilize the full capabilities of your account.');
	}
}

	if (!isset($_GET['id']) || !(int)$_GET['id']) {
		invl_inp_err();
	}
	if ($FUD_OPT_3 & 32 && !_uid) {
		if (__fud_real_user__) {
			is_allowed_user($usr);
		} else {
			std_error('login');
		}
	}

	if (!($u = db_sab('SELECT s.time_sec, u.*, u.alias AS login, l.name AS level_name, l.level_opt, l.img AS level_img FROM fud30_users u LEFT JOIN fud30_ses s ON u.id=s.user_id LEFT JOIN fud30_level l ON l.id=u.level_id WHERE u.id='. (int)$_GET['id']))) {
		std_error('user');
	}

	if (!_uid && __fud_cache($u->last_visit)) {
		return;
	}

	$obj = $u; // A little hack for online status, so we don't need to add more messages.

	if ($FUD_OPT_1 & 28 && $u->users_opt & 8388608 && $u->level_opt & (2|1) == 1) {
		$level_name = $level_image = '';
	} else {
		$level_name = $u->level_name ? $u->level_name.'<br />' : '';
		$level_image = $u->level_img ? '<img src="images/'.$u->level_img.'" alt="" /><br />' : '';
	}

	if (!$is_a) {
		$frm_perms = get_all_read_perms(_uid, ($usr->users_opt & 524288));
		$forum_list = implode(',', array_keys($frm_perms, 2));
	} else {
		$forum_list = 1;
	}

	$moderation = '';
	if ($u->users_opt & 524288 && $forum_list) {
		$c = uq('SELECT f.id, f.name FROM fud30_mod mm INNER JOIN fud30_forum f ON mm.forum_id=f.id INNER JOIN fud30_cat c ON f.cat_id=c.id WHERE '. ($is_a ? '' : 'f.id IN('. $forum_list .') AND ') .'mm.user_id='. $u->id);
		while ($r = db_rowarr($c)) {
			$moderation .= '<a href="/index.php?t='.t_thread_view.'&amp;frm_id='.$r[0].'&amp;'._rsid.'">'.$r[1].'</a>&nbsp;';
		}
		unset($c);
		if ($moderation) {
			$moderation = 'Moderator of:&nbsp;'.$moderation;
		}
	}

/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> You have <span class="GenTextRed">('.$c.')</span> unread '.convertPlural($c, array('private message','private messages')).'</a></li>' : '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> Private Messaging</a></li>';
	} else {
		$ucp_private_msg = '';
	}

	$TITLE_EXTRA = ': User Information '.$u->alias;

	ses_update_status($usr->sid, 'Looking at <a href="/index.php?t=usrinfo&amp;id='.$u->id.'">'.$u->alias.'&#39;s</a> profile');

	$avg = round($u->posted_msg_count / ((__request_timestamp__ - $u->join_date) / 86400), 2);
	if ($avg > $u->posted_msg_count) {
		$avg = $u->posted_msg_count;
	}

	$last_post = '';
	if ($u->u_last_post_id) {
		$r = db_saq('SELECT m.subject, m.id, m.post_stamp, t.forum_id FROM fud30_msg m INNER JOIN fud30_thread t ON m.thread_id=t.id WHERE m.id='. $u->u_last_post_id);
		if ($is_a || !empty($frm_perms[$r[3]])) {
			$last_post = '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="vt nw GenText">Last Message:</td>
	<td class="GenText"><span class="DateText">'.strftime('%a, %d %B %Y %H:%M', $r[2]).'</span><br /><a href="/index.php?t='.d_thread_view.'&amp;goto='.$r[1].'&amp;'._rsid.'#msg_'.$r[1].'">'.$r[0].'</a></td>
</tr>';
		}
	}

	if ($u->users_opt & 1) {
		$email_link = '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="GenText nw">E-mail:</td>
	<td class="GenText"><a href="mailto:'.$u->email.'">'.$u->email.'</a></td>
</tr>';
	} else if ($FUD_OPT_2 & 1073741824) {
		$email_link = '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="nw GenText">E-mail:</td>
	<td class="GenText">[<a href="/index.php?t=email&amp;toi='.$u->id.'&amp;'._rsid.'" rel="nofollow">Send user an e-mail message</a>]</td>
</tr>';
	} else {
		$email_link = '';
	}

	if ($FUD_OPT_2 & 8192 && ($referals = q_singleval('SELECT count(*) FROM fud30_users WHERE referer_id='. $u->id))) {
		$referals = '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="nw GenText">Referred Users:</td>
	<td class="GenText"><a href="/index.php?t=list_referers&amp;'._rsid.'">'.$referals.' Members</a></td>
</tr>';
	} else {
		$referals = '';
	}

	if (_uid && _uid != $u->id && !q_singleval('SELECT id FROM fud30_buddy WHERE user_id='. _uid .' AND bud_id='. $u->id)) {
		$buddy = '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'"><td class="nw GenText">Buddy:</td><td class="GenText"><a href="/index.php?t=buddy_list&amp;add='.$u->id.'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">add to buddy list</a></td></tr>';
	} else {
		$buddy = '';
	}

	if ($forum_list && ($polls = q_singleval('SELECT count(*) FROM fud30_poll p INNER JOIN fud30_forum f ON p.forum_id=f.id WHERE p.owner='. $u->id .' AND f.cat_id>0 '.($is_a ? '' : ' AND f.id IN('. $forum_list .')')))) {
		$polls = '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'"><td class="nw GenText">Polls:</td><td class="GenText"><a href="/index.php?t=polllist&amp;uid='.$u->id.'&amp;'._rsid.'">'.$polls.'</a></td></tr>';
	} else {
		$polls = '';
	}

	if ($u->users_opt & 1024) {
		$gender = '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'"><td class="nw GenText">Gender:</td><td class="GenText">Male</td></tr>';
	} else if (!($u->users_opt & 512)) {
		$gender = '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'"><td class="nw GenText">Gender:</td><td class="GenText">Female</td></tr>';
	} else {
		$gender = '';
	}

	if ($u->birthday) {
		// Convert birthday string to a date.
		$yyyy = (int)substr($u->birthday, 4);
		if ($yyyy == 0) {
			$yyyy = date('Y');
		}
		$mm   = (int)substr($u->birthday, 0, 2);
		$dd   = (int)substr($u->birthday, 2, 2);
		$u->birthday = mktime(0, 0, 0, $mm, $dd, $yyyy);
		$birth_date = '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="nw GenText">Date Of Birth:</td>
	<td class="GenText">'.strftime('%a, %B %d, %Y', $u->birthday).'</td>
</tr>';
	} else {
		$birth_date = '';
	}

	// Setup custom fields for display.
	$custom_fields_disp = '';
	if ($u->custom_fields) {
		require $GLOBALS['FORUM_SETTINGS_PATH'] .'custom_field_cache';
		if (!empty($custom_field_cache)) {
			$custom_field_vals = unserialize($u->custom_fields);
			foreach ($custom_field_cache as $k => $r) {
				if (!empty($custom_field_vals[$k])) {	// Have a value to display?
					$custom_field_name = $r['name'];
					$custom_field_val  = $custom_field_vals[$k];
					if ($r['field_opt'] & 2 || ($r['field_opt'] & 4) && _uid) {
						$custom_fields_disp .= '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'"><td class="nw GenText">'.$custom_field_name.':</td><td class="GenText">'.$custom_field_val.'</td></tr>';
					}
				}
			}
		}
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
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th colspan="2" class="wa"><?php echo (!($u->users_opt & 32768) && (($u->time_sec + $LOGEDIN_TIMEOUT * 60) > __request_timestamp__) ? '<img src="/theme/twig/images/online.png" alt="'.$obj->login.' is currently online" title="'.$obj->login.' is currently online" />' : '<img src="/theme/twig/images/offline.png" alt="'.$obj->login.' is currently offline" title="'.$obj->login.' is currently offline" />'); ?>&nbsp;<?php echo $u->alias; ?>&#39;s Profile</th>
</tr>
<tr class="RowStyleA">
	<td class="nw GenText">Date Registered:</td>
	<td class="wa DateText"><?php echo strftime('%a, %B %d, %Y', $u->join_date); ?></td>
</tr>
<tr class="RowStyleB">
	<td class="vt nw GenText">Message Count:</td>
	<td class="GenText"><?php echo convertPlural($u->posted_msg_count, array(''. $u->posted_msg_count.' Message',''. $u->posted_msg_count.' Messages')); ?> (<?php echo convertPlural($avg, array(''. $avg.' average message',''. $avg.' average messages')); ?> per day)<br /><a href="/index.php?t=showposts&amp;id=<?php echo $u->id; ?>&amp;<?php echo _rsid; ?>">Show all messages by <?php echo $u->alias; ?></a></td>
</tr>
<?php echo ($u->users_opt & 32768 ? '' : '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="nw GenText">Real Name:</td>
	<td class="GenText">'.$u->name.'</td>
</tr>'); ?>
<?php echo (($level_name || $moderation || $level_image || $u->custom_status) ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="nw vt GenText">Status:</td>
	<td class="GenText">
		<span class="LevelText">
		'.$level_name.'
		'.$level_image.'
		'.($u->custom_status ? $u->custom_status.'<br />' : '' )  .'
		</span>
		'.$moderation.'
	</td>
</tr>' : ''); ?>
<?php echo (($FUD_OPT_1 & 28 && $u->users_opt & 8388608 && !($u->level_opt & 2)) ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="vt nw GenText">Avatar:</td>
	<td class="GenText">'.$u->avatar_loc.'</td>
</tr>' : ''); ?>
<?php echo $last_post; ?>
<?php echo ($u->last_visit && !($u->users_opt & 32768) ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="vt nw GenText">Last Visited:</td>
	<td class="GenText">
		<span class="DateText">'.strftime('%a, %d %B %Y %H:%M', $u->last_visit).'</span>
		'.($u->last_used_ip && $is_a ? '<br />
			<a href="/index.php?t=ip&amp;ip='.$u->last_used_ip.'&amp;'._rsid.'">'.$u->last_used_ip.'</a>
		' : '' )  .'
	</td>
</tr>' : ''); ?>
<?php echo $polls; ?>
<?php echo (($FUD_OPT_2 & 65536 && $u->user_image && strpos($u->user_image, '://')) ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="vt nw GenText">Image:</td>
	<td class="GenText"><img src="'.$u->user_image.'" alt="" /></td>
</tr>' : ''); ?>
<?php echo $email_link; ?>
<?php echo (($FUD_OPT_1 & 1024 && _uid) ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="nw GenText">Private Message:</td>
	<td class="GenText"><a href="/index.php?t=ppost&amp;'._rsid.'&amp;toi='.$u->id.'"><img src="/theme/twig/images/msg_pm.gif" alt="" /></a></td>
</tr>' : ''); ?>
<?php echo $buddy; ?>
<?php echo $referals; ?>
<?php echo ($u->home_page ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="nw GenText">Homepage:</td>
	<td class="GenText"><a href="'.$u->home_page.'" rel="nofollow">'.$u->home_page.'</a></td>
</tr>' : ''); ?>
<?php echo $gender; ?>
<?php echo ($u->location ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="nw GenText">Location:</td>
	<td class="GenText">'.$u->location.'</td>
</tr>' : ''); ?>
<?php echo ($u->occupation ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="nw GenText">Occupation:</td>
	<td class="GenText">'.$u->occupation.'</td>
</tr>' : ''); ?>
<?php echo ($u->interests ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="nw GenText">Interests:</td>
	<td class="GenText">'.$u->interests.'</td>
</tr>' : ''); ?>
<?php echo ($u->bio ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	  <td class="nw GenText">Biography:</td>
	  <td class="GenText">'.$u->bio.'</td>
</tr>' : ''); ?>
<?php echo $birth_date; ?>
<?php echo $custom_fields_disp; ?>
<?php echo ($u->icq ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="nw vt GenText"><a name="icq_msg">ICQ Message Form:</a></td>
	<td class="GenText">
		'.$u->icq.' <img src="http://web.icq.com/whitepages/online?icq='.$u->icq.'&amp;img=5" /><br />
		<table class="icqCP">
		<tr><td colspan="2">
			<form action="http://wwp.icq.com/scripts/WWPMsg.dll" method="post">
			<b>ICQ Online-Message Panel</b>
		</td></tr>
		<tr>
			<td>
				Sender Name:<br />
				<input type="text" name="from" value="" size="15" maxlength="40" onfocus="this.select()" />
			</td>
			<td>
				Sender E-mail:<br />
				<input type="text" name="fromemail" value="" size="15" maxlength="40" onfocus="this.select()" />
			</td>
		</tr>
		<tr>
			<td colspan="2">
				Subject<br />
				<input type="text" spellcheck="true" name="subject" value="" size="32" /><br />
				Message<br />
				<textarea name="body" rows="3" cols="32" wrap="Virtual"></textarea>
				<input type="hidden" name="to" value="'.$u->icq.'" /><br />
			</td>
		</tr>
		<tr><td colspan="2" align="right"><input type="submit" class="button" name="Send" value="Send" /></td></tr>
		</form>
		</table>
	</td>
</tr>' : ''); ?>
<?php echo ($u->aim ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'"><td class="nw GenText">AIM Handle:</td><td class="GenText"><a href="aim:goim?screenname='.$u->aim.'&amp;message=Hello+Are+you+there?"><img src="/theme/twig/images/aim.png" title="'.$obj->aim.'" alt="" />'.htmlentities(urldecode($u->aim)).'</a></td></tr>' : ''); ?>
<?php echo ($u->yahoo ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'"><td class="nw GenText">Yahoo Messenger:</td><td class="GenText"><a href="http://edit.yahoo.com/config/send_webmesg?.target='.$u->yahoo.'&amp;.src=pg"><img src="/theme/twig/images/yahoo.png" title="'.$obj->yahoo.'" alt="" />'.htmlentities(urldecode($u->yahoo)).'</a></td></tr>' : ''); ?>
<?php echo ($u->msnm ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'"><td class="nw GenText">MSN Messenger:</td><td class="GenText"><img src="/theme/twig/images/msnm.png" title="'.$obj->msnm.'" alt="" />'.char_fix(htmlspecialchars(urldecode($u->msnm))).'</td></tr>' : ''); ?>
<?php echo ($u->jabber ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'"><td class="nw GenText">Jabber:</td><td class="GenText"><img src="/theme/twig/images/jabber.png" title="'.$obj->jabber.'" alt="" />'.$u->jabber.'</td></tr>' : ''); ?>
<?php echo ($u->google ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'"><td class="nw GenText">Google Talk:</td><td class="GenText"><img src="/theme/twig/images/google.png" title="'.$obj->google.'" alt="" />'.$u->google.'</td></tr>' : ''); ?>
<?php echo ($u->skype ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'"><td class="nw GenText">Skype:</td><td class="GenText"><a href="callto://'.$u->skype.'"><img src="/theme/twig/images/skype.png" title="'.$obj->skype.'" alt="" />'.$u->skype.'</a></td></tr>' : ''); ?>
<?php echo ($u->twitter ? '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'"><td class="nw GenText">Twitter:</td><td class="GenText"><a href="http://twitter.com/'.$u->twitter.'"><img src="/theme/twig/images/twitter.png" title="'.$obj->twitter.'" alt="" />'.$u->twitter.'</a></td></tr>' : ''); ?>
<?php echo ($is_a ? '
<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'"><td class="nw GenText">Admin Opts.</td>
<td>
<a href="/adm/admuser.php?usr_id='.$u->id.'&amp;S='.s.'&amp;act=1&amp;SQ='.$GLOBALS['sq'].'">Edit</a> || <a href="/adm/admuser.php?usr_id='.$u->id.'&amp;S='.s.'&amp;act=del&amp;SQ='.$GLOBALS['sq'].'">Delete</a> || 
'.($u->users_opt & 65536 ? '
<a href="/adm/admuser.php?act=block&amp;usr_id='.$u->id.'&amp;S='.s.'&amp;SQ='.$GLOBALS['sq'].'">UnBan</a>
' : '
<a href="/adm/admuser.php?act=block&amp;usr_id='.$u->id.'&amp;S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Ban</a>
' )  .'
</td></tr>
' : ''); ?>

<tr class="RowStyleC"><td class="nw ar GenText" colspan="2"><a href="/index.php?t=showposts&amp;id=<?php echo $u->id; ?>&amp;<?php echo _rsid; ?>">Show all messages by <?php echo $u->alias; ?></a></td></tr>
</table>
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
