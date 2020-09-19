<?php
/**
 * copyright            : (C) 2001-2017 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: reset.php.t 6078 2017-09-25 14:57:31Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
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

class fud_user
{
    var $id, $login, $alias, $passwd, $salt, $plaintext_passwd,
        $name, $email, $location, $occupation, $interests, $topics_per_page,
        $icq, $aim, $yahoo, $msnm, $jabber, $google, $skype, $twitter,
        $avatar, $avatar_loc, $posts_ppg, $time_zone, $birthday, $home_page,
        $sig, $bio, $posted_msg_count, $last_visit, $last_event, $conf_key,
        $user_image, $join_date, $theme, $last_read,
        $mod_list, $mod_cur, $level_id, $karma, $u_last_post_id, $users_opt, $cat_collapse_status,
        $ignore_list, $buddy_list,
        $custom_fields;
}

function make_alias($text)
{
    if (strlen($text) > $GLOBALS['MAX_LOGIN_SHOW']) {
        $text = substr($text, 0, $GLOBALS['MAX_LOGIN_SHOW']);
    }
    return char_fix(str_replace(array(']', '['), array('&#93;', '&#91;'), htmlspecialchars($text)));
}

function generate_salt()
{
    return substr(md5(uniqid(mt_rand(), true)), 0, 9);
}

class fud_user_reg extends fud_user
{
    function html_fields()
    {
        foreach (array('name', 'location', 'occupation', 'interests', 'bio') as $v) {
            if ($this->{$v}) {
                $this->{$v} = char_fix(htmlspecialchars($this->$v));
            }
        }
    }

    /** Deprecated: Please use add(). */
    function add_user()
    {
        return $this->add();
    }

    /** Add a new user account. */
    function add()
    {
        // Track referer.
        if (isset($_COOKIE['frm_referer_id']) && (int)$_COOKIE['frm_referer_id']) {
            $ref_id = (int)$_COOKIE['frm_referer_id'];
        } else {
            $ref_id = 0;
        }

        // Geneate password & salt (if not supplied).
        if (empty($this->passwd) && empty($this->plaintext_passwd)) {
            $this->plaintext_passwd = substr(md5(get_random_value()), 0, 8);
        }
        if (!empty($this->plaintext_passwd)) {
            $this->salt = generate_salt();
            $this->passwd = sha1($this->salt . sha1($this->plaintext_passwd));
        }

        $o2 =& $GLOBALS['FUD_OPT_2'];
        $this->alias = make_alias((!($o2 & 128) || !$this->alias) ? $this->login : $this->alias);

        /* This is used when utilities create users (aka nntp/mlist/xmlagg imports). */
        if ($this->users_opt == -1) {
            $this->users_opt = 4 | 16 | 128 | 256 | 512 | 2048 | 4096 | 8192 | 16384 | 131072 | 4194304;

            if (!($o2 & 4)) {    // Flat thread listing/Tree message listing.
                $this->users_opt ^= 128;    // Unset default_topic_view=MSG.
            }
            if (!($o2 & 8)) {    // Tree thread listing/Flat message listing.
                $this->users_opt ^= 256;    //  Unset default_message_view=MSG.
            }
            if ($o2 & 1) {    // Unset EMAIL_CONFIRMATION (no confirmation email now).
                $o2 ^= 1;
            }
            $registration_ip = '::1';
        } else {
            $registration_ip = get_ip();
        }

        /* No user options? Initialize to sensible values. */
        if (empty($this->users_opt)) {
            $this->users_opt = 2 | 4 | 16 | 32 | 64 | 128 | 256 | 512 | 2048 | 4096 | 8192 | 16384 | 131072 | 4194304;
        }

        if (empty($this->theme)) {
            $this->theme = q_singleval(q_limit('SELECT id FROM fud30_themes WHERE theme_opt>=2 AND ' . q_bitand('theme_opt', 2) . ' > 0', 1));
        }
        if (empty($this->topics_per_page)) {
            $this->topics_per_page = $GLOBALS['THREADS_PER_PAGE'];
        }
        if (empty($this->posts_ppg)) {
            $this->posts_ppg =& $GLOBALS['POSTS_PER_PAGE'];
        }
        if (empty($this->join_date)) {
            $this->join_date = __request_timestamp__;
        }
        if (empty($this->time_zone)) {
            $this->time_zone =& $GLOBALS['SERVER_TZ'];
        }

        if ($o2 & 1) {    // EMAIL_CONFIRMATION
            $this->conf_key = md5(implode('', (array)$this) . __request_timestamp__ . getmypid());
        } else {
            $this->conf_key = '';
            $this->users_opt |= 131072;
        }
        $this->icq = (int)$this->icq ? (int)$this->icq : 'NULL';

        $this->html_fields();

        $flag = ret_flag($registration_ip);

        $this->id = db_qid('INSERT INTO
			fud30_users (
				login,
				alias,
				passwd,
				salt,
				name,
				email,
				avatar, 
				avatar_loc,
				icq,
				aim,
				yahoo,
				msnm,
				jabber,
				google,
				skype,
				twitter,
				posts_ppg,
				time_zone,
				birthday,
				last_visit,
				conf_key,
				user_image,
				join_date,
				location,
				theme,
				occupation,
				interests,
				referer_id,
				last_read,
				sig,
				home_page,
				bio,
				users_opt,
				registration_ip,
				topics_per_page,
				flag_cc,
				flag_country,
				custom_fields
			) VALUES (
				' . _esc($this->login) . ',
				' . _esc($this->alias) . ',
				\'' . $this->passwd . '\',
				\'' . $this->salt . '\',
				' . _esc($this->name) . ',
				' . _esc($this->email) . ',
				' . (int)$this->avatar . ',
				' . ssn($this->avatar_loc) . ',
				' . $this->icq . ',
				' . ssn(urlencode($this->aim)) . ',
				' . ssn(urlencode($this->yahoo)) . ',
				' . ssn($this->msnm) . ',
				' . ssn(htmlspecialchars($this->jabber)) . ',
				' . ssn($this->google) . ',
				' . ssn(urlencode($this->skype)) . ',
				' . ssn(urlencode($this->twitter)) . ',
				' . (int)$this->posts_ppg . ',
				' . _esc($this->time_zone) . ',
				' . ssn($this->birthday) . ',
				' . __request_timestamp__ . ',
				\'' . $this->conf_key . '\',
				' . ssn(htmlspecialchars($this->user_image)) . ',
				' . $this->join_date . ',
				' . ssn($this->location) . ',
				' . (int)$this->theme . ',
				' . ssn($this->occupation) . ',
				' . ssn($this->interests) . ',
				' . (int)$ref_id . ',
				' . __request_timestamp__ . ',
				' . ssn($this->sig) . ',
				' . ssn(htmlspecialchars($this->home_page)) . ',
				' . ssn($this->bio) . ',
				' . $this->users_opt . ',
				' . _esc($registration_ip) . ',
				' . (int)$this->topics_per_page . ',
				' . ssn($flag[0]) . ',
				' . ssn($flag[1]) . ',
				' . _esc($this->custom_fields) . '
			)
		');

        return $this->id;
    }

    /** Deprecated: Please use sync(). Remove in FUDforum 3.1. */
    function sync_user()
    {
        $this->sync();
    }

    /** Change a user account. */
    function sync()
    {
        if (!empty($this->plaintext_passwd)) {
            if (empty($this->salt)) {
                $this->salt = generate_salt();
            }
            $passwd = 'passwd=\'' . sha1($this->salt . sha1($this->plaintext_passwd)) . '\', salt=\'' . $this->salt . '\', ';
        } else {
            $passwd = '';
        }

        $this->alias = make_alias((!($GLOBALS['FUD_OPT_2'] & 128) || !$this->alias) ? $this->login : $this->alias);
        $this->icq = (int)$this->icq ? (int)$this->icq : 'NULL';

        $rb_mod_list = (!($this->users_opt & 524288) && ($is_mod = q_singleval('SELECT id FROM fud30_mod WHERE user_id=' . $this->id)) && (q_singleval('SELECT alias FROM fud30_users WHERE id=' . $this->id) == $this->alias));

        $this->html_fields();

        q('UPDATE fud30_users SET ' . $passwd . '
			name=' . _esc($this->name) . ',
			alias=' . _esc($this->alias) . ',
			email=' . _esc($this->email) . ',
			icq=' . $this->icq . ',
			aim=' . ssn(urlencode($this->aim)) . ',
			yahoo=' . ssn(urlencode($this->yahoo)) . ',
			msnm=' . ssn($this->msnm) . ',
			jabber=' . ssn(htmlspecialchars($this->jabber)) . ',
			google=' . ssn($this->google) . ',
			skype=' . ssn(urlencode($this->skype)) . ',
			twitter=' . ssn(urlencode($this->twitter)) . ',
			posts_ppg=' . (int)$this->posts_ppg . ',
			time_zone=' . _esc($this->time_zone) . ',
			birthday=' . ssn($this->birthday) . ',
			user_image=' . ssn(htmlspecialchars($this->user_image)) . ',
			location=' . ssn($this->location) . ',
			occupation=' . ssn($this->occupation) . ',
			interests=' . ssn($this->interests) . ',
			avatar=' . (int)$this->avatar . ',
			theme=' . (int)$this->theme . ',
			avatar_loc=' . ssn($this->avatar_loc) . ',
			sig=' . ssn($this->sig) . ',
			home_page=' . ssn(htmlspecialchars($this->home_page)) . ',
			bio=' . ssn($this->bio) . ',
			users_opt=' . (int)$this->users_opt . ',
			topics_per_page=' . (int)$this->topics_per_page . ',
			custom_fields=' . _esc($this->custom_fields) . '
		WHERE id=' . $this->id);

        if ($rb_mod_list) {
            rebuildmodlist();
        }
    }

    /** Delete a user account. */
    static function delete($id)
    {
        q('DELETE FROM fud30_users WHERE id = ' . (int)$id);
    }
}

function get_id_by_email($email)
{
    return q_singleval('SELECT id FROM fud30_users WHERE email=' . _esc($email));
}

function get_id_by_login($login)
{
    return q_singleval('SELECT id FROM fud30_users WHERE login=' . _esc($login));
}

function usr_email_unconfirm($id)
{
    $conf_key = md5(__request_timestamp__ . $id . get_random_value());
    q('UPDATE fud30_users SET users_opt=' . q_bitand('users_opt', ~131072) . ', conf_key=\'' . $conf_key . '\' WHERE id=' . $id);

    return $conf_key;
}

function &usr_reg_get_full($id)
{
    if (($r = db_sab('SELECT * FROM fud30_users WHERE id=' . $id))) {
        if (!extension_loaded('overload')) {
            $o = new fud_user_reg;
            foreach ($r as $k => $v) {
                $o->{$k} = $v;
            }
            $r = $o;
        } else {
            aggregate_methods($r, 'fud_user_reg');
        }
    }
    return $r;
}

function user_login($id, $cur_ses_id, $use_cookies)
{
    /* Remove cookie so it does not confuse us. */
    if (!$use_cookies && isset($_COOKIE[$GLOBALS['COOKIE_NAME']])) {
        setcookie($GLOBALS['COOKIE_NAME'], '', __request_timestamp__ - 100000, $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
    }

    /* MULTI_HOST_LOGIN */
    if ($GLOBALS['FUD_OPT_2'] & 256 && ($s = db_saq('SELECT ses_id, sys_id FROM fud30_ses WHERE user_id=' . $id))) {
        if ($use_cookies) {
            setcookie($GLOBALS['COOKIE_NAME'], $s[0], __request_timestamp__ + $GLOBALS['COOKIE_TIMEOUT'], $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
        }
        if ($s[1]) {
            // Clear system ID, we don't use it with MULTI_HOST_LOGIN's.
            q('UPDATE fud30_ses SET sys_id=\'\' WHERE ses_id=\'' . $s[0] . '\'');
        }
        return $s[0];
    }

    /* Only 1 login per account, 'remove' all other logins. */
    q('DELETE FROM fud30_ses WHERE user_id=' . $id . ' AND ses_id!=\'' . $cur_ses_id . '\'');
    q('UPDATE fud30_ses SET user_id=' . $id . ', sys_id=\'' . ses_make_sysid() . '\' WHERE ses_id=\'' . $cur_ses_id . '\'');
    $GLOBALS['new_sq'] = regen_sq($id);

    /* Lookup country and flag. */
    if ($GLOBALS['FUD_OPT_3'] & 2097152) {    // UPDATE_GEOLOC_ON_LOGIN
        $flag = ret_flag();
    } else {
        $flag = '';
    }

    q('UPDATE fud30_users SET last_used_ip=\'' . get_ip() . '\', ' . $flag . ' sq=\'' . $GLOBALS['new_sq'] . '\' WHERE id=' . $id);

    return $cur_ses_id;
}

function rebuildmodlist()
{
    $tbl =& $GLOBALS['DBHOST_TBL_PREFIX'];
    $lmt =& $GLOBALS['SHOW_N_MODS'];
    $c = uq('SELECT u.id, u.alias, f.id FROM ' . $tbl . 'mod mm INNER JOIN ' . $tbl . 'users u ON mm.user_id=u.id INNER JOIN ' . $tbl . 'forum f ON f.id=mm.forum_id ORDER BY f.id,u.alias');
    $u = $ar = array();

    while ($r = db_rowarr($c)) {
        $u[] = $r[0];
        if ($lmt < 1 || (isset($ar[$r[2]]) && count($ar[$r[2]]) >= $lmt)) {
            continue;
        }
        $ar[$r[2]][$r[0]] = $r[1];
    }
    unset($c);

    q('UPDATE ' . $tbl . 'forum SET moderators=NULL');
    foreach ($ar as $k => $v) {
        q('UPDATE ' . $tbl . 'forum SET moderators=' . ssn(serialize($v)) . ' WHERE id=' . $k);
    }
    q('UPDATE ' . $tbl . 'users SET users_opt=' . q_bitand('users_opt', ~524288) . ' WHERE users_opt>=524288 AND ' . q_bitand('users_opt', 524288) . '>0');
    if ($u) {
        q('UPDATE ' . $tbl . 'users SET users_opt=' . q_bitor('users_opt', 524288) . ' WHERE id IN(' . implode(',', $u) . ') AND ' . q_bitand('users_opt', 1048576) . '=0');
    }
}

/** Lookup geoip info (if enabled) and return SQL UPDATE fragment. */
function ret_flag($raw = 0)
{
    if ($raw) {
        $ip = $raw;
    } else {
        $ip = get_ip();
    }

    if ($GLOBALS['FUD_OPT_3'] & 524288) {    // ENABLE_GEO_LOCATION.
        $val = db_saq('SELECT cc, country FROM fud30_geoip WHERE ' . sprintf('%u', ip2long($ip)) . ' BETWEEN ips AND ipe');
        if ($raw) {
            return $val ? $val : array(null, null);
        }
        if ($val) {
            return 'flag_cc=' . _esc($val[0]) . ', flag_country=' . _esc($val[1]) . ',';
        }
    }
    if ($raw) {
        return array(null, null);
    }
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

ses_update_status($usr->sid, 'Reset password');

/* User is logged in, redirect to forum index. */
if (_uid) {
    if ($FUD_OPT_2 & 32768) {
        header('Location: /index.php/i/' . _rsidl);
    } else {
        header('Location: /index.php?t=index&' . _rsidl);
    }
    exit;
}

/* Password resets are disabled. */
if (!($FUD_OPT_4 & 2)) {
    std_error('disabled');
}

/* Process the reset key. */
if (isset($_GET['reset_key'])) {
    if (($ui = db_saq('SELECT email, login, id FROM fud30_users WHERE reset_key=' . _esc((string)$_GET['reset_key'])))) {
        // Generate new password and salt for user.
        $salt = substr(md5(uniqid(mt_rand(), true)), 0, 9);
        $passwd = dechex(get_random_value(32));    // New password that will be mailed to the user.
        q('UPDATE fud30_users SET passwd=\'' . sha1($salt . sha1($passwd)) . '\', salt=\'' . $salt . '\', reset_key=NULL WHERE id=' . $ui[2]);

        // Send new password to user via e-mail.
        send_email($NOTIFY_FROM, $ui[0], 'Reset Password', 'Hello,\n\nAs requested, your login information appears below:\n\nLogin: ' . $ui[1] . '\nPassword: ' . $passwd . '\n\nPlease note that your password has been reset to the value above. If you wish\nto change your password you may do so via the user info control panel at:\nhttps://forum.wigedev.com/index.php?t=register\n\n\n\nIf you received this message in error, please ignore it. If you are receiving multiple copies of this e-mail, which you have not requested, please contact the forum administrator at ' . $GLOBALS['ADMIN_EMAIL'] . '\n\nThis request was initiated from: ' . $_SERVER['REMOTE_ADDR'] . '.\n\n');

        // Message to display on login screen.
        ses_putvar((int)$usr->sid, 'resetmsg=Your password has been e-mailed to you. You should receive it within the next few minutes.');

        // Redirect user to login screen.
        if ($FUD_OPT_2 & 32768) {
            header('Location: /index.php/l/' . _rsidl);
        } else {
            header('Location: /index.php?t=login&' . _rsidl);
        }
        exit;
    }
    error_dialog('ERROR', 'Invalid password reset key. It is possible that your e-mail client had already automatically open this page, invalidating the reset key. If this is the case, you should shortly receive an e-mail with your new password. If such an e-mail does not arrive, please retry resetting the password.');
}

/* Check if we received an e-mail address. */
if (isset($_GET['email'])) {
    $email = (string)$_GET['email'];
} else if (isset($_POST['email'])) {
    $email = (string)$_POST['email'];
} else {
    $email = '';
}

/* Send user a reset key via e-mail. */
if ($email) {
    if ($uobj = db_sab('SELECT id, users_opt FROM fud30_users WHERE email=' . _esc($email))) {
        if ($FUD_OPT_2 & 1 && !($uobj->users_opt & 131072)) {
            // User's e-mail must be confirmed.
            $uent = new stdClass();
            $uent->conf_key = usr_email_unconfirm($uobj->id);
            send_email($NOTIFY_FROM, $email, 'Registration Confirmation', 'Thank you for registering,\nTo activate your account please go to the URL below:\n\nhttps://forum.wigedev.com/index.php?t=emailconf&conf_key=' . $uent->conf_key . '\n\nOnce your account is activated you will be logged-into the forum and\nredirected to the main page.\n\nIf you received this message in error, please ignore it. If you are receiving multiple copies of this e-mail, which you have not requested, please contact the forum administrator at ' . $GLOBALS['ADMIN_EMAIL'] . '\n\nThis request was initiated from: ' . $_SERVER['REMOTE_ADDR'] . '.\n\n');
        } else {
            // Reset it and notify user.
            q('UPDATE fud30_users SET reset_key=\'' . ($key = md5(__request_timestamp__ . $uobj->id . get_random_value())) . '\' WHERE id=' . $uobj->id);
            $url = '' . $GLOBALS['WWW_ROOT'] . '?t=reset&reset_key=' . $key;
            send_email($NOTIFY_FROM, $email, 'Reset Password', 'Hello,\n\nYou have requested for your password to be reset. To complete the process,\nplease go to this URL:\n\n' . $url . '\n\nNOTE: This forum stores the passwords in a one-way encryption mechanism, which means that\nonce you have entered your password it is encoded so that there is NO WAY to get it back.\nThis works by comparing the encoded version we have on record with the encoded version of what you type into the Login prompt.\n(If you are interested in how this mechanism works, read up on MD5 HASH algorithm)\n\nIf you received this message in error, please ignore it. If you are receiving multiple copies of this e-mail, which you have not requested, please contact the forum administrator at ' . $GLOBALS['ADMIN_EMAIL'] . '\n\nThis request was initiated from: ' . $_SERVER['REMOTE_ADDR'] . '.\n\n');
        }
        error_dialog('Information', 'You should receive instructions in your e-mail in the next few minutes.');
    } else {
        $no_such_email = '<span class="ErrorText">E-mail address not found in database</span><br />';
    }
} else {
    $no_such_email = '';
}

$TITLE_EXTRA = ': Reset Password';

/* Print number of unread private messages in User Control Panel. */
if (__fud_real_user__ && $FUD_OPT_1 & 1024) {    // PM_ENABLED
    $c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id=' . _uid . ' AND fldr=1 AND read_stamp=0');
    $ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;' . _rsid . '" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> You have <span class="GenTextRed">(' . $c . ')</span> unread ' . convertPlural($c, array('private message', 'private messages')) . '</a></li>' : '<li><a href="/index.php?t=pmsg&amp;' . _rsid . '" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> Private Messaging</a></li>';
} else {
    $ucp_private_msg = '';
}

F()->response->noSuchEmail = $no_such_email;
F()->response->email;

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description"
        content="<?php echo(!empty($META_DESCR) ? $META_DESCR . '' : $GLOBALS['FORUM_DESCR'] . ''); ?>"/>
    <title><?php echo $GLOBALS['FORUM_TITLE'] . $TITLE_EXTRA; ?></title>
    <link rel="search" type="application/opensearchdescription+xml"
        title="<?php echo $GLOBALS['FORUM_TITLE']; ?> Search" href="/open_search.php"/>
    <?php echo $RSS; ?>
    <link rel="stylesheet" href="/theme/twig/forum.css" media="screen" title="Default Forum Theme"/>
    <link rel="stylesheet" href="/js/ui/jquery-ui.css" media="screen"/>
    <script src="/js/jquery.js"></script>
    <script async src="/js/ui/jquery-ui.js"></script>
    <script src="/js/lib.js"></script>
</head>
<body>
<!--  -->
<div class="header">
    <?php echo($GLOBALS['FUD_OPT_1'] & 1 && $GLOBALS['FUD_OPT_1'] & 16777216 ? '
  <div class="headsearch">
    <form id="headsearch" method="get" action="/index.php">' . _hs . '
      <input type="hidden" name="t" value="search" />
      <br /><label accesskey="f" title="Forum Search">Forum Search:<br />
      <input type="search" name="srch" value="" size="20" placeholder="Forum Search" /></label>
      <input type="image" src="/theme/twig/images/search.png" title="Search" name="btn_submit">&nbsp;
    </form>
  </div>
  ' : ''); ?>
    <a href="/" title="Home">
        <img class="headimg" src="/theme/twig/images/header.gif" alt="" align="left" height="80"/>
        <span class="headtitle"><?php echo $GLOBALS['FORUM_TITLE']; ?></span>
    </a><br/>
    <span class="headdescr"><?php echo $GLOBALS['FORUM_DESCR']; ?><br/><br/></span>
</div>
<div class="content">

    <!-- Table for sidebars. -->
    <table width="100%">
        <tr>
            <td>
                <div id="UserControlPanel">
                    <ul>
                        <?php echo $ucp_private_msg; ?>
                        <?php echo($FUD_OPT_4 & 16 ? '<li><a href="/index.php?t=blog&amp;' . _rsid . '" title="Blog"><img src="/theme/twig/images/blog.png" alt="" /> Blog</a></li>' : ''); ?>
                        <?php echo($FUD_OPT_4 & 8 ? '<li><a href="/index.php?t=page&amp;' . _rsid . '" title="Pages"><img src="/theme/twig/images/pages.png" alt="" /> Pages</a></li>' : ''); ?>
                        <?php echo($FUD_OPT_3 & 134217728 ? '<li><a href="/index.php?t=cal&amp;' . _rsid . '" title="Calendar"><img src="/theme/twig/images/calendar.png" alt="" /> Calendar</a></li>' : ''); ?>
                        <?php echo($FUD_OPT_1 & 16777216 ? ' <li><a href="/index.php?t=search' . (isset($frm->forum_id) ? '&amp;forum_limiter=' . (int)$frm->forum_id . '' : '') . '&amp;' . _rsid . '" title="Search"><img src="/theme/twig/images/top_search.png" alt="" /> Search</a></li>' : ''); ?>
                        <li><a accesskey="h" href="/index.php?t=help_index&amp;<?php echo _rsid; ?>" title="Help"><img
                                    src="/theme/twig/images/top_help.png" alt=""/> Help</a></li>
                        <?php echo(($FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304) || $usr->users_opt & 1048576) ? '<li><a href="/index.php?t=finduser&amp;btn_submit=Find&amp;' . _rsid . '" title="Members"><img src="/theme/twig/images/top_members.png" alt="" /> Members</a></li>' : ''); ?>
                        <?php echo (__fud_real_user__ ? '<li><a href="/index.php?t=uc&amp;' . _rsid . '" title="Access the user control panel"><img src="/theme/twig/images/top_profile.png" alt="" /> Control Panel</a></li>' : ($FUD_OPT_1 & 2 ? '<li><a href="/index.php?t=register&amp;' . _rsid . '" title="Register"><img src="/theme/twig/images/top_register.png" alt="" /> Register</a></li>' : '')) . '
	' . (__fud_real_user__ ? '<li><a href="/index.php?t=login&amp;' . _rsid . '&amp;logout=1&amp;SQ=' . $GLOBALS['sq'] . '" title="Logout"><img src="/theme/twig/images/top_logout.png" alt="" /> Logout [ ' . htmlspecialchars($usr->alias, null, null, false) . ' ]</a></li>' : '<li><a href="/index.php?t=login&amp;' . _rsid . '" title="Login"><img src="/theme/twig/images/top_login.png" alt="" /> Login</a></li>'); ?>
                        <li><a href="/index.php?t=index&amp;<?php echo _rsid; ?>" title="Home"><img
                                    src="/theme/twig/images/top_home.png" alt=""/> Home</a></li>
                        <?php echo($is_a || ($usr->users_opt & 268435456) ? '<li><a href="/adm/index.php?S=' . s . '&amp;SQ=' . $GLOBALS['sq'] . '" title="Administration"><img src="/theme/twig/images/top_admin.png" alt="" /> Administration</a></li>' : ''); ?>
                    </ul>
                </div>
                <div class="ctb">
                    <form method="post" action="/index.php?t=reset">
                        <table cellspacing="1" cellpadding="2" class="DialogTable">
                            <tr>
                                <th colspan="2">Password Reminder</th>
                            </tr>
                            <tr>
                                <td colspan="2" class="RowStyleA GenText">Your password will be reset and sent to you.
                                    If you have not yet confirmed your e-mail address, the confirmation request will be
                                    re-sent to you.
                                </td>
                            </tr>
                            <tr class="RowStyleB">
                                <td class="GenText">E-mail:</td>
                                <td><?php echo $no_such_email; ?><input type="email" name="email"
                                        value="<?php echo htmlspecialchars($email, null, null, false); ?>"/></td>
                            </tr>
                            <tr class="RowStyleA">
                                <td class="nw ar" colspan="2"><input type="submit" class="button" name="reset_passwd"
                                        value="Reset Password"/></td>
                            </tr>
                        </table>
                        <?php echo _hs; ?>
                    </form>
                </div>
                <br/>
                <div class="ac"><span
                        class="curtime"><b>Current Time:</b> <?php echo strftime('%a %b %d %H:%M:%S %Z %Y', __request_timestamp__); ?></span>
                </div>
                <?php echo(!empty($RIGHT_SIDEBAR) ? '
</td><td width="200px" align-"right" valign="top" class="sidebar-right">
	' . $RIGHT_SIDEBAR . '
' : ''); ?>
            </td>
        </tr>
    </table>

</div>
<div class="footer ac">
    <b>.::</b>
    <a href="mailto:<?php echo $GLOBALS['ADMIN_EMAIL']; ?>">Contact</a>
    <b>::</b>
    <a href="/index.php?t=index&amp;<?php echo _rsid; ?>">Home</a>
    <b>::.</b>
    <p class="SmallText">Powered by: FUDforum <?php echo $GLOBALS['FORUM_VERSION']; ?>.<br/>Copyright &copy;2001-2020 <a
            href="http://fudforum.org/">FUDforum Bulletin Board Software</a></p>
</div>

</body>
</html>
