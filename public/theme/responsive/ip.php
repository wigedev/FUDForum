<?php
/**
* copyright            : (C) 2001-2012 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: ip.php.t 5505 2012-06-06 17:38:29Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function check_return($returnto)
{
	if ($GLOBALS['FUD_OPT_2'] & 32768 && !empty($_SERVER['PATH_INFO'])) {
		if (!$returnto || !strncmp($returnto, '/er/', 4)) {
			header('Location: /index.php/i/'. _rsidl);
		} else if ($returnto[0] == '/') { /* Unusual situation, path_info & normal themes are active. */
			header('Location: /index.php'. $returnto);
		} else {
			header('Location: /index.php?'. $returnto);
		}
	} else if (!$returnto || !strncmp($returnto, 't=error', 7)) {
		header('Location: /index.php?t=index&'. _rsidl);
	} else if (strpos($returnto, 'S=') === false && $GLOBALS['FUD_OPT_1'] & 128) {
		header('Location: /index.php?'. $returnto .'&S='. s);
	} else {
		header('Location: /index.php?'. $returnto);
	}
	exit;
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

	/* Permissions check, this form is only allowed for moderators & admins unless public.
	 * Check if IP display is allowed.
	 */
	if (!($usr->users_opt & (524288|1048576)) && !($FUD_OPT_1 & 134217728)) {
		invl_inp_err();
	}

function __fud_whois($ip, $whois_server='')
{
	if (!$whois_server) {
		$whois_server = $GLOBALS['FUD_WHOIS_SERVER'];
	}

	if (!$sock = @fsockopen($whois_server, 43, $errno, $errstr, 20)) {
		$errstr = preg_match('/WIN/', PHP_OS) ? utf8_encode($errstr) : $errstr;	// Windows silliness.
		return 'Unable to connect to WHOIS server ('.$whois_server.'): '.$errstr;
	}
	fputs($sock, $ip ."\n");
	$buffer = '';
	do {
		$buffer .= fread($sock, 10240);
	} while (!feof($sock));
	fclose($sock);

	return $buffer;
}

function fud_whois($ip)
{
	$result = __fud_whois($ip);

	/* Check if ARIN can handle the request or if we need to
	 * request information from another server.
	 */
	if (($p = strpos($result, 'ReferralServer: whois://')) !== false) {
		$p += strlen('ReferralServer: whois://');
		$e = strpos($result, "\n", $p);
		$whois = substr($result, $p, ($e - $p));
		if ($whois) {
			$result = __fud_whois($ip, $whois);
		}
	}

	return ($result ? $result : 'WHOIS information for <b>'.$ip.'</b> is not available.');
}

/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/responsive/images/top_pm.png" alt="" /> You have <span class="GenTextRed">('.$c.')</span> unread '.convertPlural($c, array('private message','private messages')).'</a></li>' : '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/responsive/images/top_pm.png" alt="" /> Private Messaging</a></li>';
	} else {
		$ucp_private_msg = '';
	}

	if (isset($_POST['ip'])) {
		$_GET['ip'] = $_POST['ip'];
	}
	$ip = isset($_GET['ip']) ? filter_var($_GET['ip'], FILTER_VALIDATE_IP) : '';

	if (isset($_POST['user'])) {
		$_GET['user'] = $_POST['user'];
	}
	if (isset($_GET['user'])) {
		if (($user_id = (int) $_GET['user'])) {
			$user = q_singleval('SELECT alias FROM fud30_users WHERE id='. $user_id);
		} else {
			list($user_id, $user) = db_saq('SELECT id, alias FROM fud30_users WHERE alias='. _esc(char_fix(htmlspecialchars($_GET['user']))));
		}
	} else {
		$user = '';
	}

	$TITLE_EXTRA = ': IP Browser';

	if ($ip) {
		if (substr_count($ip, '.') == 3) {
			$cond = 'm.ip_addr=\''. $ip .'\'';
		} else {
			$cond = 'm.ip_addr LIKE \''. $ip .'%\'';
		}

		$o = uq('SELECT DISTINCT(m.poster_id), u.alias FROM fud30_msg m INNER JOIN fud30_users u ON m.poster_id=u.id WHERE '. $cond);
		$user_list = '';
		$i = 0;
		while ($r = db_rowarr($o)) {
			$user_list .= '<tr><td class="'.alt_var('ip_alt','RowStyleA','RowStyleB').'">'.++$i.'. <a href="/index.php?t=usrinfo&amp;id='.$r[0].'&amp;'._rsid.'">'.$r[1].'</a></td></tr>';
		}
		unset($o);
		$o = uq('SELECT id, alias FROM fud30_users WHERE registration_ip='. _esc($ip));
		while ($r = db_rowarr($o)) {
			$user_list .= '<tr><td class="'.alt_var('ip_alt','RowStyleA','RowStyleB').'">'.++$i.'. <a href="/index.php?t=usrinfo&amp;id='.$r[0].'&amp;'._rsid.'">'.$r[1].'</a></td></tr>';
		}
		unset($o);
		$page_data = '<table cellspacing="2" cellpadding="2" class="MiniTable">
<tr>
	<td class="vt">
		<table cellspacing="0" cellpadding="2" class="ContentTable">
		<tr><th>Users using &#39;'.$ip.'&#39; IP address</th></tr>'.$user_list.'
		</table>
	</td>
	<td width="50"> </td>
	<td class="vt"><b>ISP Information</b><br /><div class="ip"><pre>'.fud_whois($ip).'</pre></div></td>
</tr>
</table>';
	} else if ($user) {
		$o = uq('SELECT DISTINCT(ip_addr) FROM fud30_msg WHERE poster_id='. $user_id);
		$ip_list = '';
		$i = 0;
		while ($r = db_rowarr($o)) {
			$ip_list .= '<tr>
	<td class="'.alt_var('ip_alt','RowStyleA','RowStyleB').'">'.++$i.'. <a href="/index.php?t=ip&amp;ip='.$r[0].'&amp;'._rsid.'">'.$r[0].'</a></td>
</tr>';
		}
		unset($o);
		
		$o = uq('SELECT registration_ip FROM fud30_users WHERE id='. $user_id);
		while ($r = db_rowarr($o)) {
			$ip_list .= '<tr>
	<td class="'.alt_var('ip_alt','RowStyleA','RowStyleB').'">'.++$i.'. <a href="/index.php?t=ip&amp;ip='.$r[0].'&amp;'._rsid.'">'.$r[0].'</a></td>
</tr>';
		}
		unset($o);

		$page_data = '<table cellspacing="2" cellpadding="2" class="MiniTable">
<tr>
	<th>All IPs used by &#39;'.$user.'&#39;</th>
</tr>
'.$ip_list.'
</table>';
	} else {
		$page_data = '';
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
	<link rel="stylesheet" href="/theme/responsive/forum.css" media="screen" title="Default Forum Theme" />
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
      <input type="image" src="/theme/responsive/images/search.png" title="Search" name="btn_submit">&nbsp;
    </form>
  </div>
  ' : ''); ?>
  <a href="/" title="Home">
    <img class="headimg" src="/theme/responsive/images/header.gif" alt="" align="left" height="80" />
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
	<?php echo ($FUD_OPT_4 & 16 ? '<li><a href="/index.php?t=blog&amp;'._rsid.'" title="Blog"><img src="/theme/responsive/images/blog.png" alt="" /> Blog</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_4 & 8 ? '<li><a href="/index.php?t=page&amp;'._rsid.'" title="Pages"><img src="/theme/responsive/images/pages.png" alt="" /> Pages</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_3 & 134217728 ? '<li><a href="/index.php?t=cal&amp;'._rsid.'" title="Calendar"><img src="/theme/responsive/images/calendar.png" alt="" /> Calendar</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_1 & 16777216 ? ' <li><a href="/index.php?t=search'.(isset($frm->forum_id) ? '&amp;forum_limiter='.(int)$frm->forum_id.'' : '' )  .'&amp;'._rsid.'" title="Search"><img src="/theme/responsive/images/top_search.png" alt="" /> Search</a></li>' : ''); ?>
	<li><a accesskey="h" href="/index.php?t=help_index&amp;<?php echo _rsid; ?>" title="Help"><img src="/theme/responsive/images/top_help.png" alt="" /> Help</a></li>
	<?php echo (($FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304) || $usr->users_opt & 1048576) ? '<li><a href="/index.php?t=finduser&amp;btn_submit=Find&amp;'._rsid.'" title="Members"><img src="/theme/responsive/images/top_members.png" alt="" /> Members</a></li>' : ''); ?>
	<?php echo (__fud_real_user__ ? '<li><a href="/index.php?t=uc&amp;'._rsid.'" title="Access the user control panel"><img src="/theme/responsive/images/top_profile.png" alt="" /> Control Panel</a></li>' : ($FUD_OPT_1 & 2 ? '<li><a href="/index.php?t=register&amp;'._rsid.'" title="Register"><img src="/theme/responsive/images/top_register.png" alt="" /> Register</a></li>' : '')).'
	'.(__fud_real_user__ ? '<li><a href="/index.php?t=login&amp;'._rsid.'&amp;logout=1&amp;SQ='.$GLOBALS['sq'].'" title="Logout"><img src="/theme/responsive/images/top_logout.png" alt="" /> Logout [ '.htmlspecialchars($usr->alias, null, null, false).' ]</a></li>' : '<li><a href="/index.php?t=login&amp;'._rsid.'" title="Login"><img src="/theme/responsive/images/top_login.png" alt="" /> Login</a></li>'); ?>
	<li><a href="/index.php?t=index&amp;<?php echo _rsid; ?>" title="Home"><img src="/theme/responsive/images/top_home.png" alt="" /> Home</a></li>
	<?php echo ($is_a || ($usr->users_opt & 268435456) ? '<li><a href="/adm/index.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'" title="Administration"><img src="/theme/responsive/images/top_admin.png" alt="" /> Administration</a></li>' : ''); ?>
</ul>
</div>

<div class="ctb">
<table cellspacing="0" cellpadding="0" class="MiniTable">
<tr>
	<td>
		<fieldset>
		<legend>Search users by IP</legend>
		<form method="post" action="/index.php?t=ip"><?php echo _hs; ?>
		<span class="SmallText">Supported syntax: 1.2.3.4, 1.2.3, 1.2, 1<br /></span>
		<input type="text" name="ip" value="<?php echo $ip; ?>" size="20" maxlength="15" />
		<input type="submit" value="Search" />
		</form>
		</fieldset>
	</td>
	<td width="50"> </td>
	<td>
		<fieldset>
		<legend>Analyze IP usage</legend>
		<form method="post" action="/index.php?t=ip"><?php echo _hs; ?>
		<span class="SmallText">Please specify the user&#39;s exact login.<br /></span>
		<input type="text" name="user" value="<?php echo $user; ?>" size="20" />
		<input type="submit" value="Search" />
		</form>
		</fieldset>
	</td>
</tr>
</table>
<br /><br />
<?php echo $page_data; ?>
</div>
<br /><div class="ac"><span class="curtime"><b>Current Time:</b> <?php echo strftime('%a %b %d %H:%M:%S %Z %Y', __request_timestamp__); ?></span></div>
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
