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
}
function check_return($returnto)
{
    if ($GLOBALS['FUD_OPT_2'] & 32768 && !empty($_SERVER['PATH_INFO'])) {
        if (!$returnto || !strncmp($returnto, '/er/', 4)) {
            header('Location: /index.php/i/' . _rsidl);
        } else {
            if ($returnto[0] == '/') { /* Unusual situation, path_info & normal themes are active. */
                header('Location: /index.php' . $returnto);
            } else {
                header('Location: /index.php?' . $returnto);
            }
        }
    } else {
        if (!$returnto || !strncmp($returnto, 't=error', 7)) {
            header('Location: /index.php?t=index&' . _rsidl);
        } else {
            if (strpos($returnto, 'S=') === false && $GLOBALS['FUD_OPT_1'] & 128) {
                header('Location: /index.php?' . $returnto . '&S=' . s);
            } else {
                header('Location: /index.php?' . $returnto);
            }
        }
    }
    exit;
}

function alt_var($key)
{
    if (!isset($GLOBALS['_ALTERNATOR_'][$key])) {
        $args = func_get_args();
        unset($args[0]);
        $GLOBALS['_ALTERNATOR_'][$key] = ['p' => 2, 't' => func_num_args(), 'v' => $args];
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
if (!($usr->users_opt & (524288 | 1048576)) && !($FUD_OPT_1 & 134217728)) {
    invl_inp_err();
}

function __fud_whois($ip, $whois_server = '')
{
    if (!$whois_server) {
        $whois_server = $GLOBALS['FUD_WHOIS_SERVER'];
    }

    if (!$sock = @fsockopen($whois_server, 43, $errno, $errstr, 20)) {
        $errstr = preg_match('/WIN/', PHP_OS) ? utf8_encode($errstr) : $errstr;    // Windows silliness.
        return 'Unable to connect to WHOIS server (' . $whois_server . '): ' . $errstr;
    }
    fputs($sock, $ip . "\n");
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

    return ($result ? $result : 'WHOIS information for <b>' . $ip . '</b> is not available.');
}

/* Print number of unread private messages in User Control Panel. */
if (__fud_real_user__ && $FUD_OPT_1 & 1024) {    // PM_ENABLED
    $c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id=' . _uid . ' AND fldr=1 AND read_stamp=0');
    $ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;' .
        _rsid .
        '" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> You have <span class="GenTextRed">(' .
        $c .
        ')</span> unread ' .
        convertPlural($c, ['private message', 'private messages']) .
        '</a></li>' : '<li><a href="/index.php?t=pmsg&amp;' .
        _rsid .
        '" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> Private Messaging</a></li>';
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
    if (($user_id = (int)$_GET['user'])) {
        $user = q_singleval('SELECT alias FROM fud30_users WHERE id=' . $user_id);
    } else {
        [$user_id, $user] = db_saq(
            'SELECT id, alias FROM fud30_users WHERE alias=' . _esc(char_fix(htmlspecialchars($_GET['user'])))
        );
    }
} else {
    $user = '';
}

$TITLE_EXTRA = ': IP Browser';

if ($ip) {
    if (substr_count($ip, '.') == 3) {
        $cond = 'm.ip_addr=\'' . $ip . '\'';
    } else {
        $cond = 'm.ip_addr LIKE \'' . $ip . '%\'';
    }

    $o = uq(
        'SELECT DISTINCT(m.poster_id), u.alias FROM fud30_msg m INNER JOIN fud30_users u ON m.poster_id=u.id WHERE ' .
        $cond
    );
    $user_list = '';
    $i = 0;
    while ($r = db_rowarr($o)) {
        $user_list .= '<tr><td class="' .
            alt_var('ip_alt', 'RowStyleA', 'RowStyleB') .
            '">' .
            ++$i .
            '. <a href="/index.php?t=usrinfo&amp;id=' .
            $r[0] .
            '&amp;' .
            _rsid .
            '">' .
            $r[1] .
            '</a></td></tr>';
    }
    unset($o);
    $o = uq('SELECT id, alias FROM fud30_users WHERE registration_ip=' . _esc($ip));
    while ($r = db_rowarr($o)) {
        $user_list .= '<tr><td class="' .
            alt_var('ip_alt', 'RowStyleA', 'RowStyleB') .
            '">' .
            ++$i .
            '. <a href="/index.php?t=usrinfo&amp;id=' .
            $r[0] .
            '&amp;' .
            _rsid .
            '">' .
            $r[1] .
            '</a></td></tr>';
    }
    unset($o);
    $page_data = '<table cellspacing="2" cellpadding="2" class="MiniTable">
<tr>
	<td class="vt">
		<table cellspacing="0" cellpadding="2" class="ContentTable">
		<tr><th>Users using &#39;' . $ip . '&#39; IP address</th></tr>' . $user_list . '
		</table>
	</td>
	<td width="50"> </td>
	<td class="vt"><b>ISP Information</b><br /><div class="ip"><pre>' . fud_whois($ip) . '</pre></div></td>
</tr>
</table>';
} else {
    if ($user) {
        $o = uq('SELECT DISTINCT(ip_addr) FROM fud30_msg WHERE poster_id=' . $user_id);
        $ip_list = '';
        $i = 0;
        while ($r = db_rowarr($o)) {
            $ip_list .= '<tr>
	<td class="' .
                alt_var('ip_alt', 'RowStyleA', 'RowStyleB') .
                '">' .
                ++$i .
                '. <a href="/index.php?t=ip&amp;ip=' .
                $r[0] .
                '&amp;' .
                _rsid .
                '">' .
                $r[0] .
                '</a></td>
</tr>';
        }
        unset($o);

        $o = uq('SELECT registration_ip FROM fud30_users WHERE id=' . $user_id);
        while ($r = db_rowarr($o)) {
            $ip_list .= '<tr>
	<td class="' .
                alt_var('ip_alt', 'RowStyleA', 'RowStyleB') .
                '">' .
                ++$i .
                '. <a href="/index.php?t=ip&amp;ip=' .
                $r[0] .
                '&amp;' .
                _rsid .
                '">' .
                $r[0] .
                '</a></td>
</tr>';
        }
        unset($o);

        $page_data = '<table cellspacing="2" cellpadding="2" class="MiniTable">
<tr>
	<th>All IPs used by &#39;' . $user . '&#39;</th>
</tr>
' . $ip_list . '
</table>';
    } else {
        $page_data = '';
    }
}

F()->response->user = $user;
F()->response->page_data = $page_data;
