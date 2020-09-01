<?php
/**
 * copyright            : (C) 2001-2016 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: pmsg_view.php.t 5963 2016-06-30 07:00:44Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
}
$folders = array(1 => 'Inbox', 2 => 'Saved', 4 => 'Draft', 3 => 'Sent', 5 => 'Trash');

function tmpl_cur_ppage($folder_id, $folders, $msg_subject = '')
{
    if (!$folder_id || (!$msg_subject && $_GET['t'] == 'ppost')) {
        $user_action = 'Writing a Private Message';
    } else {
        $user_action = $msg_subject ? '<a href="/index.php?t=pmsg&amp;folder_id=' . $folder_id . '&amp;' . _rsid . '">' . $folders[$folder_id] . '</a> &raquo; ' . $msg_subject : 'Browsing <b>' . $folders[$folder_id] . '</b> folder';
    }

    return '<span class="GenText"><a href="/index.php?t=pmsg&amp;' . _rsid . '">Private Messaging</a>&nbsp;&raquo;&nbsp;' . $user_action . '</span><br /><img src="/blank.gif" alt="" height="4" width="1" /><br />';
}

function tmpl_drawpmsg($obj, $usr, $mini)
{
    $o1 =& $GLOBALS['FUD_OPT_1'];
    $o2 =& $GLOBALS['FUD_OPT_2'];
    $a = (int)$obj->users_opt;
    $b =& $usr->users_opt;

    if (!$mini) {
        $custom_tag = $obj->custom_status ? '<br />' . $obj->custom_status : '';
        $c = (int)$obj->level_opt;

        if ($obj->avatar_loc && $a & 8388608 && $b & 8192 && $o1 & 28 && !($c & 2)) {
            if (!($c & 1)) {
                $level_name =& $obj->level_name;
                $level_image = $obj->level_img ? '&nbsp;<img src="/images/' . $obj->level_img . '" alt="" />' : '';
            } else {
                $level_name = $level_image = '';
            }
        } else {
            $level_image = $obj->level_img ? '&nbsp;<img src="/images/' . $obj->level_img . '" alt="" />' : '';
            $obj->avatar_loc = '';
            $level_name =& $obj->level_name;
        }
        $avatar = ($obj->avatar_loc || $level_image) ? '<td class="avatarPad wo">' . $obj->avatar_loc . $level_image . '</td>' : '';
        $dmsg_tags = ($custom_tag || $level_name) ? '<div class="ctags">' . $level_name . $custom_tag . '</div>' : '';

        if (($o2 & 32 && !($a & 32768)) || $b & 1048576) {
            $obj->login = $obj->alias;
            $online_indicator = (($obj->last_visit + $GLOBALS['LOGEDIN_TIMEOUT'] * 60) > __request_timestamp__) ? '<img src="/theme/twig/images/online.png" alt="' . $obj->login . ' is currently online" title="' . $obj->login . ' is currently online" />' : '<img src="/theme/twig/images/offline.png" alt="' . $obj->login . ' is currently offline" title="' . $obj->login . ' is currently offline" />';
        } else {
            $online_indicator = '';
        }

        if ($obj->location) {
            if (strlen($obj->location) > $GLOBALS['MAX_LOCATION_SHOW']) {
                $location = substr($obj->location, 0, $GLOBALS['MAX_LOCATION_SHOW']) . '...';
            } else {
                $location = $obj->location;
            }
            $location = '<br /><b>Location:</b> ' . $location;
        } else {
            $location = '';
        }
        $usr->buddy_list = $usr->buddy_list ? unserialize($usr->buddy_list) : array();
        if ($obj->user_id != _uid && $obj->user_id > 0) {
            $buddy_link = !isset($usr->buddy_list[$obj->user_id]) ? '<a href="/index.php?t=buddy_list&amp;' . _rsid . '&amp;add=' . $obj->user_id . '&amp;SQ=' . $GLOBALS['sq'] . '">add to buddy list</a><br />' : '<br />[<a href="/index.php?t=buddy_list&amp;del=' . $obj->user_id . '&amp;redr=1&amp;' . _rsid . '&amp;SQ=' . $GLOBALS['sq'] . '">remove from buddy list</a>]';
        } else {
            $buddy_link = '';
        }
        /* Show im buttons if need be. */
        if ($b & 16384) {
            $im = '';
            if ($obj->icq) {
                $im .= '<a href="/index.php?t=usrinfo&amp;id=' . $obj->user_id . '&amp;' . _rsid . '#icq_msg"><img src="/theme/twig/images/icq.png" alt="" title="' . $obj->icq . '" /></a>&nbsp;';
            }
            if ($obj->aim) {
                $im .= '<a href="aim:goim?screenname=' . $obj->aim . '&amp;message=Hi.+Are+you+there?"><img src="/theme/twig/images/aim.png" title="' . $obj->aim . '" alt="" /></a>&nbsp;';
            }
            if ($obj->yahoo) {
                $im .= '<a href="http://edit.yahoo.com/config/send_webmesg?.target=' . $obj->yahoo . '&amp;.src=pg"><img src="/theme/twig/images/yahoo.png" alt="" title="' . $obj->yahoo . '" /></a>&nbsp;';
            }
            if ($obj->msnm) {
                $im .= '<a href="mailto:' . $obj->msnm . '"><img src="/theme/twig/images/msnm.png" title="' . $obj->msnm . '" alt="" /></a>';
            }
            if ($obj->jabber) {
                $im .= '<img src="/theme/twig/images/jabber.png" title="' . $obj->jabber . '" alt="" />';
            }
            if ($obj->google) {
                $im .= '<img src="/theme/twig/images/google.png" title="' . $obj->google . '" alt="" />';
            }
            if ($obj->skype) {
                $im .= '<a href="callto://' . $obj->skype . '"><img src="/theme/twig/images/skype.png" title="' . $obj->skype . '" alt="" /></a>';
            }
            if ($obj->twitter) {
                $im .= '<a href="http://twitter.com/' . $obj->twitter . '"><img src="/theme/twig/images/twitter.png" title="' . $obj->twitter . '" alt="" /></a>';
            }
            if ($im) {
                $dmsg_im_row = $im . '<br />';
            } else {
                $dmsg_im_row = '';
            }
        } else {
            $dmsg_im_row = '';
        }
        if ($obj->ouser_id != _uid) {
            $user_profile = '<a href="/index.php?t=usrinfo&amp;id=' . $obj->user_id . '&amp;' . _rsid . '"><img src="/theme/twig/images/msg_about.gif" alt="" /></a>';
            $email_link = ($o1 & 4194304 && $a & 16) ? '<a href="/index.php?t=email&amp;toi=' . $obj->user_id . '&amp;' . _rsid . '" rel="nofollow"><img src="/theme/twig/images/msg_email.gif" alt="" /></a>' : '';
            $private_msg_link = '<a href="/index.php?t=ppost&amp;toi=' . $obj->user_id . '&amp;' . _rsid . '"><img title="Send a private message to this user" src="/theme/twig/images/msg_pm.gif" alt="" /></a>';
        } else {
            $user_profile = $email_link = $private_msg_link = '';
        }
        $msg_toolbar = '<tr><td colspan="2" class="MsgToolBar"><table border="0" cellspacing="0" cellpadding="0" class="wa"><tr>
<td class="nw al">' . $user_profile . '&nbsp;' . $email_link . '&nbsp;' . $private_msg_link . '</td>
<td class="nw ar"><a href="/index.php?t=pmsg&amp;' . _rsid . '&amp;btn_delete=1&amp;sel=' . $obj->id . '&amp;SQ=' . $GLOBALS['sq'] . '"><img src="/theme/twig/images/msg_delete.gif" alt="" /></a>&nbsp;' . ($obj->fldr == 4 ? '<a href="/index.php?t=ppost&amp;msg_id=' . $obj->id . '&amp;' . _rsid . '"><img src="/theme/twig/images/msg_edit.gif" alt="" /></a>&nbsp;&nbsp;&nbsp;&nbsp;' : '') . ($obj->fldr == 1 ? '<a href="/index.php?t=ppost&amp;reply=' . $obj->id . '&amp;' . _rsid . '"><img src="/theme/twig/images/msg_reply.gif" alt="" /></a>&nbsp;<a href="/index.php?t=ppost&amp;quote=' . $obj->id . '&amp;' . _rsid . '"><img src="/theme/twig/images/msg_quote.gif" alt="" /></a>&nbsp;' : '') . '<a href="/index.php?t=ppost&amp;forward=' . $obj->id . '&amp;' . _rsid . '"><img src="/theme/twig/images/msg_forward.gif" alt="" /></a></td>
</tr></table></td></tr>';
    } else {
        $dmsg_tags = $dmsg_im_row = $user_profile = $msg_toolbar = $buddy_link = $avatar = $online_indicator = $host_name = $location = '';
    }
    if ($obj->length > 0) {
        $msg_body = read_pmsg_body($obj->foff, $obj->length);
    } else {
        $msg_body = 'No Message Body';
    }

    $msg_body = $obj->length ? read_pmsg_body($obj->foff, $obj->length) : 'No Message Body';

    $file_attachments = '';
    if ($obj->attach_cnt) {
        $c = uq('SELECT a.id, a.original_name, a.dlcount, m.icon, a.fsize FROM fud30_attach a LEFT JOIN fud30_mime m ON a.mime_type=m.id WHERE a.message_id=' . $obj->id . ' AND attach_opt=1');
        while ($r = db_rowobj($c)) {
            $sz = $r->fsize / 1024;
            $sz = $sz < 1000 ? number_format($sz, 2) . 'KB' : number_format($sz / 1024, 2) . 'MB';
            if (!$r->icon) {
                $r->icon = 'unknown.gif';
            }
            $file_attachments .= '<li>
	<img alt="" src="/images/mime/' . $r->icon . '" class="at" />
	<span class="GenText fb">Attachment:</span> <a href="/index.php?t=getfile&amp;id=' . $r->id . '&amp;' . _rsid . '&amp;private=1" title="' . $r->original_name . '">' . $r->original_name . '</a>
	<br />
	<span class="SmallText">(Size: ' . $sz . ', Downloaded ' . convertPlural($r->dlcount, array('' . $r->dlcount . ' time', '' . $r->dlcount . ' times')) . ')</span>
</li>';
        }
        unset($c);
        if ($file_attachments) {
            $file_attachments = '<ul class="AttachmentsList">
	' . $file_attachments . '
</ul>';
            /* Append session to getfile. */
            if ($o1 & 128 && !isset($_COOKIE[$GLOBALS['COOKIE_NAME']])) {
                $msg_body = str_replace('<img src="index.php?t=getfile', '<img src="index.php?t=getfile&amp;S=' . s, $msg_body);
                $tap = 1;
            }
            if ($o2 & 32768 && (isset($tap) || $o2 & 8192)) {
                $pos = 0;
                while (($pos = strpos($msg_body, '<img src="index.php/fa/', $pos)) !== false) {
                    $pos = strpos($msg_body, '"', $pos + 11);
                    $msg_body = substr_replace($msg_body, _rsid, $pos, 0);
                }
            }
        }
    }

    return '<tr>
	<td>
		<table cellspacing="0" cellpadding="0" class="MsgTable">
		<tr>
			<td class="MsgR1 al vt expanded">' . (!$mini && $obj->icon ? '<img src="/images/message_icons/' . $obj->icon . '" alt="" />&nbsp;&nbsp;' : '') . '<span class="MsgSubText">' . $obj->subject . '</span></td>
			<td class="MsgR1 vt ar DateText">' . strftime('%a, %d %B %Y %H:%M', $obj->post_stamp) . '</td>
		</tr>
		<tr class="MsgR2"><td class="MsgR2" colspan="2">
			<table cellspacing="0" cellpadding="0" class="ContentTable">
			<tr class="MsgR2">
			' . $avatar . '
				<td class="msgud">' . $online_indicator . (!$mini ? '<a href="/index.php?t=usrinfo&amp;id=' . $obj->user_id . '&amp;' . _rsid . '">' . htmlspecialchars($obj->alias, null, null, false) . '</a>' : htmlspecialchars($obj->alias, null, null, false)) . (!$mini ? '<br /><b>Messages:</b> ' . $obj->posted_msg_count . '<br /><b>Registered:</b> ' . strftime('%B %Y', $obj->join_date) . ' ' . $location : '') . '</td>
				<td class="msgud">' . $dmsg_tags . '</td>
				<td class="msgot">' . $buddy_link . $dmsg_im_row . (!$mini && $obj->host_name && $o1 & 268435456 ? '<b>From:</b> ' . $obj->host_name . '<br />' : '') . '</td>
			</tr>
			</table>
		</tr>
		<tr>
			<td class="MsgR3" colspan="2">
				' . $msg_body . '
				' . $file_attachments . '
				' . (($obj->sig && $o1 & 32768 && $obj->pmsg_opt & 1 && $b & 4096) ? '<br /><br /><div class="signature">' . $obj->sig . '</div>' : '') . '
			</td>
		</tr>
		' . $msg_toolbar . '
		<tr>
			<td class="MsgR2 ac" colspan="2">' . $GLOBALS['dpmsg_prev_message'] . ' ' . $GLOBALS['dpmsg_next_message'] . '</td>
		</tr>
		</table>
	</td>
</tr>';
}

include $GLOBALS['FORUM_SETTINGS_PATH'] . 'ip_filter_cache';
include $GLOBALS['FORUM_SETTINGS_PATH'] . 'login_filter_cache';
include $GLOBALS['FORUM_SETTINGS_PATH'] . 'email_filter_cache';

function is_ip_blocked($ip)
{
    if (empty($GLOBALS['__FUD_IP_FILTER__'])) {
        return;
    }
    $block =& $GLOBALS['__FUD_IP_FILTER__'];
    list($a, $b, $c, $d) = explode('.', $ip);

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

function is_allowed_user(&$usr, $simple = 0)
{
    /* Check if the ban expired. */
    if (($banned = $usr->users_opt & 65536) && $usr->ban_expiry && $usr->ban_expiry < __request_timestamp__) {
        q('UPDATE fud30_users SET users_opt = ' . q_bitand('users_opt', ~65536) . ' WHERE id=' . $usr->id);
        $usr->users_opt ^= 65536;
        $banned = 0;
    }

    if ($banned || is_email_blocked($usr->email) || is_login_blocked($usr->login) || is_ip_blocked(get_ip())) {
        $ban_expiry = (int)$usr->ban_expiry;
        $ban_reason = $usr->ban_reason;
        if (!$simple) { // On login page we already have anon session.
            ses_delete($usr->sid);
            $usr = ses_anon_make();
        }
        setcookie($GLOBALS['COOKIE_NAME'] . '1', 'd34db33fd34db33fd34db33fd34db33f', ($ban_expiry ? $ban_expiry : (__request_timestamp__ + 63072000)), $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
        if ($banned) {
            error_dialog('ERROR: You have been banned.', 'Your account was ' . ($ban_expiry ? 'temporarily banned until ' . strftime('%a, %d %B %Y %H:%M', $ban_expiry) : 'permanently banned') . ' from accessing the site, due to a violation of the forum&#39;s rules.
<br />
<br />
<span class="GenTextRed">' . $ban_reason . '</span>');
        } else {
            error_dialog('ERROR: Your account has been filtered out.', 'Your account has been blocked from accessing the forum due to one of the installed user filters.');
        }
    }

    if ($simple) {
        return;
    }

    if ($GLOBALS['FUD_OPT_1'] & 1048576 && $usr->users_opt & 262144) {
        error_dialog('ERROR: Your account is not yet confirmed', 'We have not received a confirmation from your parent and/or legal guardian, which would allow you to post messages. If you lost your COPPA form, <a href="/index.php?t=coppa_fax&amp;' . _rsid . '">view it again</a>.');
    }

    if ($GLOBALS['FUD_OPT_2'] & 1 && !($usr->users_opt & 131072)) {
        std_error('emailconf');
    }

    if ($GLOBALS['FUD_OPT_2'] & 1024 && $usr->users_opt & 2097152) {
        error_dialog('Unverified Account', 'The administrator had chosen to review all accounts manually prior to activation. Until your account has been validated by the administrator you will not be able to utilize the full capabilities of your account.');
    }
}

function read_msg_body($off, $len, $id)
{
    if ($off == -1) {    // Fetch from DB and return.
        return q_singleval('SELECT data FROM fud30_msg_store WHERE id=' . $id);
    }

    if (!$len) {    // Empty message.
        return;
    }

    // Open file if it's not already open.
    if (!isset($GLOBALS['__MSG_FP__'][$id])) {
        $GLOBALS['__MSG_FP__'][$id] = fopen($GLOBALS['MSG_STORE_DIR'] . 'msg_' . $id, 'rb');
    }

    // Read from file.
    fseek($GLOBALS['__MSG_FP__'][$id], $off);
    return fread($GLOBALS['__MSG_FP__'][$id], $len);
}

$GLOBALS['recv_user_id'] = array();

class fud_pmsg
{
    var $id, $to_list, $ouser_id, $duser_id, $pdest, $ip_addr, $host_name, $post_stamp, $icon, $fldr,
        $subject, $attach_cnt, $pmsg_opt, $length, $foff, $login, $ref_msg_id, $body;

    function add($track = '')
    {
        $this->post_stamp = __request_timestamp__;
        $this->ip_addr = get_ip();
        $this->host_name = $GLOBALS['FUD_OPT_1'] & 268435456 ? _esc(get_host($this->ip_addr)) : 'NULL';

        if ($this->fldr != 1) {
            $this->read_stamp = $this->post_stamp;
        }

        if ($GLOBALS['FUD_OPT_3'] & 32768) {
            $this->foff = $this->length = -1;
        } else {
            list($this->foff, $this->length) = write_pmsg_body($this->body);
        }

        $this->id = db_qid('INSERT INTO fud30_pmsg (
			ouser_id,
			duser_id,
			pdest,
			to_list,
			ip_addr,
			host_name,
			post_stamp,
			icon,
			fldr,
			subject,
			attach_cnt,
			read_stamp,
			ref_msg_id,
			foff,
			length,
			pmsg_opt
			) VALUES(
				' . $this->ouser_id . ',
				' . ($this->duser_id ? $this->duser_id : $this->ouser_id) . ',
				' . (isset($GLOBALS['recv_user_id'][0]) ? (int)$GLOBALS['recv_user_id'][0] : '0') . ',
				' . ssn($this->to_list) . ',
				\'' . $this->ip_addr . '\',
				' . $this->host_name . ',
				' . $this->post_stamp . ',
				' . ssn($this->icon) . ',
				' . $this->fldr . ',
				' . _esc($this->subject) . ',
				' . (int)$this->attach_cnt . ',
				' . $this->read_stamp . ',
				' . ssn($this->ref_msg_id) . ',
				' . (int)$this->foff . ',
				' . (int)$this->length . ',
				' . $this->pmsg_opt . '
			)');

        if ($GLOBALS['FUD_OPT_3'] & 32768 && $this->body) {
            $fid = db_qid('INSERT INTO fud30_msg_store (data) VALUES(' . _esc($this->body) . ')');
            q('UPDATE fud30_pmsg SET length=' . $fid . ' WHERE id=' . $this->id);
        }

        if ($this->fldr == 3 && !$track) {
            $this->send_pmsg();
        }
    }

    function send_pmsg()
    {
        $this->pmsg_opt |= 16 | 32;
        $this->pmsg_opt &= 16 | 32 | 1 | 2 | 4;

        foreach ($GLOBALS['recv_user_id'] as $v) {
            $id = db_qid('INSERT INTO fud30_pmsg (
				to_list,
				ouser_id,
				ip_addr,
				host_name,
				post_stamp,
				icon,
				fldr,
				subject,
				attach_cnt,
				foff,
				length,
				duser_id,
				ref_msg_id,
				pmsg_opt
			) VALUES (
				' . ssn($this->to_list) . ',
				' . $this->ouser_id . ',
				\'' . $this->ip_addr . '\',
				' . $this->host_name . ',
				' . $this->post_stamp . ',
				' . ssn($this->icon) . ',
				1,
				' . _esc($this->subject) . ',
				' . (int)$this->attach_cnt . ',
				' . $this->foff . ',
				' . $this->length . ',
				' . $v . ',
				' . ssn($this->ref_msg_id) . ',
				' . $this->pmsg_opt . ')');

            if ($GLOBALS['FUD_OPT_3'] & 32768 && $this->body) {
                $fid = db_qid('INSERT INTO fud30_msg_store (data) VALUES(' . _esc($this->body) . ')');
                q('UPDATE fud30_pmsg SET length=' . $fid . ' WHERE id=' . $id);
            }

            $GLOBALS['send_to_array'][] = array($v, $id);
            $um[$v] = $id;
        }
        $c = uq('SELECT id, email FROM fud30_users WHERE id IN(' . implode(',', $GLOBALS['recv_user_id']) . ') AND users_opt>=64 AND ' . q_bitand('users_opt', 64) . ' > 0');

        $from = reverse_fmt($GLOBALS['usr']->alias);
        $subject = reverse_fmt($this->subject);

        while ($r = db_rowarr($c)) {
            /* Do not send notifications about messages sent to self. */
            if ($r[0] == $this->ouser_id) {
                continue;
            }
            send_pm_notification($r[1], $um[$r[0]], $subject, $from);
        }
        unset($c);
    }

    function sync()
    {
        $this->post_stamp = __request_timestamp__;
        $this->ip_addr = get_ip();
        $this->host_name = $GLOBALS['FUD_OPT_1'] & 268435456 ? _esc(get_host($this->ip_addr)) : 'NULL';

        if ($GLOBALS['FUD_OPT_3'] & 32768) {    // DB_MESSAGE_STORAGE
            if ($fid = q_singleval('SELECT length FROM fud30_pmsg WHERE id=' . $this->id . ' AND foff!=-1')) {
                q('DELETE FROM fud30_msg_store WHERE id=' . $this->length);
            }
            $this->foff = $this->length = -1;
        } else {
            list($this->foff, $this->length) = write_pmsg_body($this->body);
        }

        q('UPDATE fud30_pmsg SET
			to_list=' . ssn($this->to_list) . ',
			icon=' . ssn($this->icon) . ',
			ouser_id=' . $this->ouser_id . ',
			duser_id=' . $this->ouser_id . ',
			post_stamp=' . $this->post_stamp . ',
			subject=' . _esc($this->subject) . ',
			ip_addr=\'' . $this->ip_addr . '\',
			host_name=' . $this->host_name . ',
			attach_cnt=' . (int)$this->attach_cnt . ',
			fldr=' . $this->fldr . ',
			foff=' . (int)$this->foff . ',
			length=' . (int)$this->length . ',
			pmsg_opt=' . $this->pmsg_opt . '
		WHERE id=' . $this->id);

        if ($GLOBALS['FUD_OPT_3'] & 32768 && $this->body) {
            $fid = db_qid('INSERT INTO fud30_msg_store (data) VALUES(' . _esc($this->body) . ')');
            q('UPDATE fud30_pmsg SET length=' . $fid . ' WHERE id=' . $this->id);
        }

        if ($this->fldr == 3) {
            $this->send_pmsg();
        }
    }
}

function write_pmsg_body($text)
{
    if (($ll = !db_locked())) {
        db_lock('fud30_fl_pm WRITE');
    }

    $fp = fopen($GLOBALS['MSG_STORE_DIR'] . 'private', 'ab');
    if (!$fp) {
        exit("FATAL ERROR: cannot open private message store<br />\n");
    }

    fseek($fp, 0, SEEK_END);
    if (!($s = ftell($fp))) {
        $s = __ffilesize($fp);
    }

    if (($len = fwrite($fp, $text)) !== strlen($text)) {
        exit("FATAL ERROR: system has ran out of disk space<br />\n");
    }
    fclose($fp);

    if ($ll) {
        db_unlock();
    }

    if (!$s) {
        @chmod($GLOBALS['MSG_STORE_DIR'] . 'private', ($GLOBALS['FUD_OPT_2'] & 8388608 ? 0600 : 0644));
    }

    return array($s, $len);
}

function read_pmsg_body($offset, $length)
{
    if ($length < 1) {
        return;
    }

    if ($GLOBALS['FUD_OPT_3'] & 32768 && $offset == -1) {
        return q_singleval('SELECT data FROM fud30_msg_store WHERE id=' . $length);
    }

    $fp = fopen($GLOBALS['MSG_STORE_DIR'] . 'private', 'rb');
    fseek($fp, $offset, SEEK_SET);
    $str = fread($fp, $length);
    fclose($fp);

    return $str;
}

function pmsg_move($mid, $fid, $validate)
{
    if (!$validate && !q_singleval('SELECT id FROM fud30_pmsg WHERE duser_id=' . _uid . ' AND id=' . $mid)) {
        return;
    }

    q('UPDATE fud30_pmsg SET fldr=' . $fid . ' WHERE duser_id=' . _uid . ' AND id=' . $mid);
}

function pmsg_del($mid, $fldr = 0)
{
    if (!$fldr && !($fldr = q_singleval('SELECT fldr FROM fud30_pmsg WHERE duser_id=' . _uid . ' AND id=' . $mid))) {
        return;
    }

    if ($fldr != 5) {
        pmsg_move($mid, 5, 0);
    } else {
        if ($GLOBALS['FUD_OPT_3'] & 32768 && ($fid = q_singleval('SELECT length FROM fud30_pmsg WHERE id=' . $mid . ' AND foff=-1'))) {
            q('DELETE FROM fud30_msg_store WHERE id=' . $fid);
        }
        q('DELETE FROM fud30_pmsg WHERE id=' . $mid);
        $c = uq('SELECT id FROM fud30_attach WHERE message_id=' . $mid . ' AND attach_opt=1');
        while ($r = db_rowarr($c)) {
            @unlink($GLOBALS['FILE_STORE'] . $r[0] . '.atch');
        }
        unset($c);
        q('DELETE FROM fud30_attach WHERE message_id=' . $mid . ' AND attach_opt=1');
    }
}

function send_pm_notification($email, $pid, $subject, $from)
{
    send_email($GLOBALS['NOTIFY_FROM'], $email, '[' . $GLOBALS['FORUM_TITLE'] . '] New Private Message Notification', 'You have a new private message titled "' . $subject . '", from "' . $from . '", in the forum "' . $GLOBALS['FORUM_TITLE'] . '".\nTo view the message, click here: https://forum.wigedev.com/index.php?t=pmsg_view&id=' . $pid . '\n\nTo stop future notifications, disable "Private Message Notification" in your profile.');
}

function validate_email($email)
{
    $bits = explode('@', $email);
    if (count($bits) != 2) {
        return 1;
    }
    $doms = explode('.', $bits[1]);
    $last = array_pop($doms);

    // Validate domain extension 2-4 characters A-Z
    if (!preg_match('!^[A-Za-z]{2,4}$!', $last)) {
        return 1;
    }

    // (Sub)domain name 63 chars long max A-Za-z0-9_
    foreach ($doms as $v) {
        if (!$v || strlen($v) > 63 || !preg_match('!^[A-Za-z0-9_-]+$!', $v)) {
            return 1;
        }
    }

    // Now the hard part, validate the e-mail address itself.
    if (!$bits[0] || strlen($bits[0]) > 255 || !preg_match('!^[-A-Za-z0-9_.+{}~\']+$!', $bits[0])) {
        return 1;
    }
}

function encode_subject($text)
{
    if (preg_match('![\x7f-\xff]!', $text)) {
        $text = '=?utf-8?B?' . base64_encode($text) . '?=';
    }

    return $text;
}

function send_email($from, $to, $subj, $body, $header = '', $munge_newlines = 1)
{
    if (empty($to)) {
        return 0;
    }

    /* HTML entities check. */
    if (strpos($subj, '&') !== false) {
        $subj = html_entity_decode($subj);
    }

    if ($header) {
        $header = "\n" . str_replace("\r", '', $header);
    }
    $extra_header = '';
    if (strpos($header, 'MIME-Version') === false) {
        $extra_header = "\nMIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit" . $header;
    }
    $header = 'From: ' . $from . "\nErrors-To: " . $from . "\nReturn-Path: " . $from . "\nX-Mailer: FUDforum v" . $GLOBALS['FORUM_VERSION'] . $extra_header . $header;

    $body = str_replace("\r", '', $body);
    if ($munge_newlines) {
        $body = str_replace('\n', "\n", $body);
    }
    $subj = encode_subject($subj);

    // Call PRE mail plugins.
    if (defined('plugins')) {
        list($to, $subj, $body, $header) = plugin_call_hook('PRE_MAIL', array($to, $subj, $body, $header));
    }

    if (defined('fud_logging')) {
        if (!function_exists('logaction')) {
            fud_use('logaction.inc');
        }
        logaction(_uid, 'SEND EMAIL', 0, 'To=[' . implode(',', (array)$to) . ']<br />Subject=[' . $subj . ']<br />Headers=[' . str_replace("\n", '<br />', htmlentities($header)) . ']<br />Message=[' . $body . ']');
    }

    if ($GLOBALS['FUD_OPT_1'] & 512) {
        if (!class_exists('fud_smtp')) {
            fud_use('smtp.inc');
        }
        $smtp = new fud_smtp;
        $smtp->msg = str_replace(array('\n', "\n."), array("\n", "\n.."), $body);
        $smtp->subject = encode_subject($subj);
        $smtp->to = $to;
        $smtp->from = $from;
        $smtp->headers = $header;
        $smtp->send_smtp_email();
        return 1;
    }

    foreach ((array)$to as $email) {
        if (!@mail($email, $subj, $body, $header)) {
            fud_logerror('Your system didn\'t accept E-mail [' . $subj . '] to [' . $email . '] for delivery.', 'fud_errors', $header . "\n\n" . $body);
            return -1;
        }
    }

    return 1;
}

$GLOBALS['__revfs'] = array('&quot;', '&lt;', '&gt;', '&amp;');
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
}

function get_host($ip)
{
    if (!$ip || $ip == '0.0.0.0') {
        return;
    }

    $name = gethostbyaddr($ip);

    if ($name == $ip) {
        $name = substr($name, 0, strrpos($name, '.')) . '*';
    } else if (substr_count($name, '.') > 1) {
        $name = '*' . substr($name, strpos($name, '.') + 1);
    }

    return $name;
}

class fud_smtp
{
    var $fs, $last_ret, $msg, $subject, $to, $from, $headers;

    function get_return_code($cmp_code = '250')
    {
        if (!($this->last_ret = @fgets($this->fs, 515))) {
            return;
        }
        if ((int)$this->last_ret == $cmp_code) {
            return 1;
        }
        return;
    }

    function wts($string)
    {
        /* Write to stream. */
        fwrite($this->fs, $string . "\r\n");
    }

    function open_smtp_connex()
    {
        if (!($this->fs = @fsockopen($GLOBALS['FUD_SMTP_SERVER'], $GLOBALS['FUD_SMTP_PORT'], $errno, $errstr, $GLOBALS['FUD_SMTP_TIMEOUT']))) {
            fud_logerror('ERROR: SMTP server at ' . $GLOBALS['FUD_SMTP_SERVER'] . " is not available<br />\n" . ($errno ? "Additional Problem Info: $errno -> $errstr <br />\n" : ''), 'fud_errors');
            return;
        }
        if (!$this->get_return_code(220)) {    // 220 == Ready to speak SMTP.
            return;
        }

        $es = strpos($this->last_ret, 'ESMTP') !== false;
        $smtp_srv = $_SERVER['SERVER_NAME'];
        if ($smtp_srv == 'localhost' || $smtp_srv == '127.0.0.1' || $smtp_srv == '::1') {
            $smtp_srv = 'FUDforum SMTP server';
        }

        $this->wts(($es ? 'EHLO ' : 'HELO ') . $smtp_srv);
        if (!$this->get_return_code()) {
            return;
        }

        /* Scan all lines and look for TLS support. */
        $tls = false;
        if ($es) {
            while ($str = @fgets($this->fs, 515)) {
                if (substr($str, 0, 12) == '250-STARTTLS') $tls = true;
                if (substr($str, 3, 1) == ' ') break;    // Done reading if 4th char is a space.

            }
        }

        /* Do SMTP Auth if needed. */
        if ($GLOBALS['FUD_SMTP_LOGIN']) {
            if ($tls) {
                /*  Initiate TSL communication with server. */
                $this->wts('STARTTLS');
                if (!$this->get_return_code(220)) {
                    return;
                }
                /* Encrypt the connection. */
                if (!stream_socket_enable_crypto($this->fs, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    return false;
                }
                /* Say hi again. */
                $this->wts(($es ? 'EHLO ' : 'HELO ') . $smtp_srv);
                if (!$this->get_return_code()) {
                    return;
                }
                /* we need to scan all other lines */
                while ($str = @fgets($this->fs, 515)) {
                    if (substr($str, 3, 1) == ' ') break;
                }
            }

            $this->wts('AUTH LOGIN');
            if (!$this->get_return_code(334)) {
                return;
            }
            $this->wts(base64_encode($GLOBALS['FUD_SMTP_LOGIN']));
            if (!$this->get_return_code(334)) {
                return;
            }
            $this->wts(base64_encode($GLOBALS['FUD_SMTP_PASS']));
            if (!$this->get_return_code(235)) {
                return;
            }
        }

        return 1;
    }

    function send_from_hdr()
    {
        $this->wts('MAIL FROM: <' . $GLOBALS['NOTIFY_FROM'] . '>');
        return $this->get_return_code();
    }

    function send_to_hdr()
    {
        $this->to = (array)$this->to;

        foreach ($this->to as $to_addr) {
            $this->wts('RCPT TO: <' . $to_addr . '>');
            if (!$this->get_return_code()) {
                return;
            }
        }
        return 1;
    }

    function send_data()
    {
        $this->wts('DATA');
        if (!$this->get_return_code(354)) {
            return;
        }

        /* This is done to ensure what we comply with RFC requiring each line to end with \r\n */
        $this->msg = preg_replace('!(\r)?\n!si', "\r\n", $this->msg);

        if (empty($this->from)) $this->from = $GLOBALS['NOTIFY_FROM'];

        $this->wts('Subject: ' . $this->subject);
        $this->wts('Date: ' . date('r'));
        $this->wts('To: ' . (count($this->to) == 1 ? $this->to[0] : $GLOBALS['NOTIFY_FROM']));
        $this->wts($this->headers . "\r\n");
        $this->wts($this->msg);
        $this->wts('.');

        return $this->get_return_code();
    }

    function close_connex()
    {
        $this->wts('QUIT');
        fclose($this->fs);
    }

    function send_smtp_email()
    {
        if (!$this->open_smtp_connex()) {
            if ($this->last_ret) {
                fud_logerror('Open SMTP connection - invalid return code: ' . $this->last_ret, 'fud_errors');
            }
            return false;
        }
        if (!$this->send_from_hdr()) {
            fud_logerror('Send "From:" header - invalid SMTP return code: ' . $this->last_ret, 'fud_errors');
            $this->close_connex();
            return false;
        }
        if (!$this->send_to_hdr()) {
            fud_logerror('Send "To:" header - invalid SMTP return code: ' . $this->last_ret, 'fud_errors');
            $this->close_connex();
            return false;
        }
        if (!$this->send_data()) {
            fud_logerror('Send data - invalid SMTP return code: ' . $this->last_ret, 'fud_errors');
            $this->close_connex();
            return false;
        }

        $this->close_connex();
        return true;
    }
}

if (!($FUD_OPT_1 & 1024)) {
    error_dialog('ERROR: Private Messaging Disabled', 'You cannot use the private messaging system. It has been disabled by the administrator.');
}

if (__fud_real_user__) {
    is_allowed_user($usr);
} else {
    std_error('login');
}

if (_uid) {
    $admin_cp = $accounts_pending_approval = $group_mgr = $reported_msgs = $custom_avatar_queue = $mod_que = $thr_exch = '';

    if ($usr->users_opt & 524288 || $is_a) {    // is_mod or admin.
        if ($is_a) {
            // Approval of custom Avatars.
            if ($FUD_OPT_1 & 32 && ($avatar_count = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=16777216 AND ' . q_bitand('users_opt', 16777216) . ' > 0'))) {
                $custom_avatar_queue = '| <a href="/adm/admavatarapr.php?S=' . s . '&amp;SQ=' . $GLOBALS['sq'] . '">Custom Avatar Queue</a> <span class="GenTextRed">(' . $avatar_count . ')</span>';
            }

            // All reported messages.
            if ($report_count = q_singleval('SELECT count(*) FROM fud30_msg_report')) {
                $reported_msgs = '| <a href="/index.php?t=reported&amp;' . _rsid . '" rel="nofollow">Reported Messages</a> <span class="GenTextRed">(' . $report_count . ')</span>';
            }

            // All thread exchange requests.
            if ($thr_exchc = q_singleval('SELECT count(*) FROM fud30_thr_exchange')) {
                $thr_exch = '| <a href="/index.php?t=thr_exch&amp;' . _rsid . '">Topic Exchange</a> <span class="GenTextRed">(' . $thr_exchc . ')</span>';
            }

            // All account approvals.
            if ($FUD_OPT_2 & 1024 && ($accounts_pending_approval = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND ' . q_bitand('users_opt', 2097152) . ' > 0 AND id > 0'))) {
                $accounts_pending_approval = '| <a href="/adm/admuserapr.php?S=' . s . '&amp;SQ=' . $GLOBALS['sq'] . '">Accounts Pending Approval</a> <span class="GenTextRed">(' . $accounts_pending_approval . ')</span>';
            } else {
                $accounts_pending_approval = '';
            }

            $q_limit = '';
        } else {
            // Messages reported in moderated forums.
            if ($report_count = q_singleval('SELECT count(*) FROM fud30_msg_report mr INNER JOIN fud30_msg m ON mr.msg_id=m.id INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_mod mm ON t.forum_id=mm.forum_id AND mm.user_id=' . _uid)) {
                $reported_msgs = '| <a href="/index.php?t=reported&amp;' . _rsid . '" rel="nofollow">Reported Messages</a> <span class="GenTextRed">(' . $report_count . ')</span>';
            }

            // Thread move requests in moderated forums.
            if ($thr_exchc = q_singleval('SELECT count(*) FROM fud30_thr_exchange te INNER JOIN fud30_mod m ON m.user_id=' . _uid . ' AND te.frm=m.forum_id')) {
                $thr_exch = '| <a href="/index.php?t=thr_exch&amp;' . _rsid . '">Topic Exchange</a> <span class="GenTextRed">(' . $thr_exchc . ')</span>';
            }

            $q_limit = ' INNER JOIN fud30_mod mm ON f.id=mm.forum_id AND mm.user_id=' . _uid;
        }

        // Messages requiring approval.
        if ($approve_count = q_singleval('SELECT count(*) FROM fud30_msg m INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_forum f ON t.forum_id=f.id ' . $q_limit . ' WHERE m.apr=0 AND f.forum_opt>=2')) {
            $mod_que = '<a href="/index.php?t=modque&amp;' . _rsid . '">Moderation Queue</a> <span class="GenTextRed">(' . $approve_count . ')</span>';
        }
    } else if ($usr->users_opt & 268435456 && $FUD_OPT_2 & 1024 && ($accounts_pending_approval = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND ' . q_bitand('users_opt', 2097152) . ' > 0 AND id > 0'))) {
        $accounts_pending_approval = '| <a href="/adm/admuserapr.php?S=' . s . '&amp;SQ=' . $GLOBALS['sq'] . '">Accounts Pending Approval</a> <span class="GenTextRed">(' . $accounts_pending_approval . ')</span>';
    } else {
        $accounts_pending_approval = '';
    }
    if ($is_a || $usr->group_leader_list) {
        $group_mgr = '| <a href="/index.php?t=groupmgr&amp;' . _rsid . '">Group Manager</a>';
    }

    if ($thr_exch || $accounts_pending_approval || $group_mgr || $reported_msgs || $custom_avatar_queue || $mod_que) {
        $admin_cp = '<br /><span class="GenText fb">Admin:</span> ' . $mod_que . ' ' . $reported_msgs . ' ' . $thr_exch . ' ' . $custom_avatar_queue . ' ' . $group_mgr . ' ' . $accounts_pending_approval . '<br />';
    }
} else {
    $admin_cp = '';
}/* Print number of unread private messages in User Control Panel. */
if (__fud_real_user__ && $FUD_OPT_1 & 1024) {    // PM_ENABLED
    $c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id=' . _uid . ' AND fldr=1 AND read_stamp=0');
    $ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;' . _rsid . '" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> You have <span class="GenTextRed">(' . $c . ')</span> unread ' . convertPlural($c, array('private message', 'private messages')) . '</a></li>' : '<li><a href="/index.php?t=pmsg&amp;' . _rsid . '" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> Private Messaging</a></li>';
} else {
    $ucp_private_msg = '';
}
$tabs = '';
if (_uid) {
    $tablist = array(
        'Notifications' => 'uc',
        'Account Settings' => 'register',
        'Subscriptions' => 'subscribed',
        'Bookmarks' => 'bookmarked',
        'Referrals' => 'referals',
        'Buddy List' => 'buddy_list',
        'Ignore List' => 'ignore_list',
        'Show Own Posts' => 'showposts'
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

        foreach ($tablist as $tab_name => $tab) {
            $tab_url = '/index.php?t=' . $tab . (s ? '&amp;S=' . s : '');
            if ($tab == 'referals') {
                if (!($FUD_OPT_2 & 8192)) {
                    continue;
                }
                $tab_url .= '&amp;id=' . _uid;
            } else if ($tab == 'showposts') {
                $tab_url .= '&amp;id=' . _uid;
            }
            $tabs .= $pg == $tab ? '<td class="tabON"><div class="tabT"><a class="tabON" href="' . $tab_url . '">' . $tab_name . '</a></div></td>' : '<td class="tabI"><div class="tabT"><a href="' . $tab_url . '">' . $tab_name . '</a></div></td>';
        }

        $tabs = '<table cellspacing="1" cellpadding="0" class="tab">
<tr>
	' . $tabs . '
</tr>
</table>';
    }
}

if (!isset($_GET['id']) || !($id = (int)$_GET['id'])) {
    invl_inp_err();
}

$m = db_sab('SELECT
		p.*,
		u.id AS user_id, u.alias, u.users_opt, u.avatar_loc, u.email, u.posted_msg_count, u.join_date,
		u.location, u.sig, u.icq, u.aim, u.msnm, u.yahoo, u.jabber, u.google, u.skype, u.twitter, u.custom_status, u.last_visit,
		l.name AS level_name, l.level_opt, l.img AS level_img
	FROM
		fud30_pmsg p
		INNER JOIN fud30_users u ON p.ouser_id=u.id
		LEFT JOIN fud30_level l ON u.level_id=l.id
	WHERE p.duser_id=' . _uid . ' AND p.id=' . $id);

if (!$m) {
    invl_inp_err();
}

ses_update_status($usr->sid, 'Using private messaging');

/* Next Msg */
if (($nid = q_singleval(q_limit('SELECT p.id FROM fud30_pmsg p INNER JOIN fud30_users u ON u.id=p.ouser_id WHERE p.duser_id=' . _uid . ' AND p.fldr=' . $m->fldr . ' AND post_stamp>' . $m->post_stamp . ' ORDER BY p.post_stamp ASC', 1)))) {
    $dpmsg_next_message = '<a href="/index.php?t=pmsg_view&amp;' . _rsid . '&amp;id=' . $nid . '">Next message <img src="/theme/twig/images/goto.gif" alt="" /></a>';
} else {
    $dpmsg_next_message = '';
}

/* Prev Msg */
if (($pid = q_singleval(q_limit('SELECT p.id FROM fud30_pmsg p INNER JOIN fud30_users u ON u.id=p.ouser_id WHERE p.duser_id=' . _uid . ' AND p.fldr=' . $m->fldr . ' AND p.post_stamp<' . $m->post_stamp . ' ORDER BY p.post_stamp DESC', 1)))) {
    $dpmsg_prev_message = '<a href="/index.php?t=pmsg_view&amp;' . _rsid . '&amp;id=' . $pid . '"><img src="/theme/twig/images/goback.gif" alt="" /> Previous message</a>';
} else {
    $dpmsg_prev_message = '';
}

if (!$m->read_stamp && $m->pmsg_opt & 16) {
    q('UPDATE fud30_pmsg SET read_stamp=' . __request_timestamp__ . ', pmsg_opt=' . q_bitor(q_bitand('pmsg_opt', ~4), 8) . ' WHERE id=' . $m->id);
    if ($m->ouser_id != _uid && $m->pmsg_opt & 4 && !isset($_GET['dr'])) {
        $track_msg = new fud_pmsg;
        $track_msg->ouser_id = $track_msg->duser_id = $m->ouser_id;
        $track_msg->ip_addr = $track_msg->host_name = null;
        $track_msg->post_stamp = __request_timestamp__;
        $track_msg->read_stamp = 0;
        $track_msg->fldr = 1;
        $track_msg->pmsg_opt = 16 | 32;
        $track_msg->subject = 'Read Notification For: ' . $m->subject;
        $track_msg->body = 'Hello,<br />' . $usr->login . ' has opened your private message titled, "' . $m->subject . '".<br />This is an automated notification generated at ' . strftime('%a, %d %B %Y %H:%M', __request_timestamp__) . '<br />';
        $track_msg->add(1);
    }
}

F()->response->currentPageTemplate = tmpl_cur_ppage($m->fldr, $folders, $m->subject);
F()->response->tabs = $tabs;
F()->response->messageTemplate = tmpl_drawpmsg($m, $usr, false);
