<?php
/**
 * copyright            : (C) 2001-2018 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: register.php.t 6327 2019-11-14 15:31:30Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 *
 * TODO: This one last
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
}
function tmpl_draw_select_opt($values, $names, $selected)
{
    $vls = explode("\n", $values);
    $nms = explode("\n", $names);

    if (count($vls) != count($nms)) {
        exit("FATAL ERROR: inconsistent number of values inside a select<br />\n");
    }

    $options = '';
    foreach ($vls as $k => $v) {
        $options .= '<option value="' . $v . '"' . ($v == $selected ? ' selected="selected"' : '') . '>' . $nms[$k] . '</option>';
    }

    return $options;
}

function tmpl_draw_radio_opt($name, $values, $names, $selected, $sep)
{
    $vls = explode("\n", $values);
    $nms = explode("\n", $names);

    if (count($vls) != count($nms)) {
        exit("FATAL ERROR: inconsistent number of values<br />\n");
    }

    $checkboxes = '';
    foreach ($vls as $k => $v) {
        $checkboxes .= '<label><input type="radio" name="' . $name . '" value="' . $v . '" ' . ($v == $selected ? 'checked="checked" ' : '') . ' />' . $nms[$k] . '</label>' . $sep;
    }

    return $checkboxes;
}

function tmpl_post_options($arg, $perms = 0)
{
    $post_opt_html = '<b>HTML</b> code is <b>off</b>';
    $post_opt_fud = '<b>BBcode</b> is <b>off</b>';
    $post_opt_images = '<b>Images</b> are <b>off</b>';
    $post_opt_smilies = '<b>Smilies</b> are <b>off</b>';
    $edit_time_limit = '';

    if (is_int($arg)) {
        if ($arg & 16) {
            $post_opt_fud = '<a href="/index.php?section=readingposting&amp;t=help_index&amp;' . _rsid . '#style" target="_blank"><b>BBcode</b> is <b>on</b></a>';
        } else if (!($arg & 8)) {
            $post_opt_html = '<b>HTML</b> is <b>on</b>';
        }
        if ($perms & 16384) {
            $post_opt_smilies = '<a href="/index.php?section=readingposting&amp;t=help_index&amp;' . _rsid . '#sml" target="_blank"><b>Smilies</b> are <b>on</b></a>';
        }
        if ($perms & 32768) {
            $post_opt_images = '<b>Images</b> are <b>on</b>';
        }
        if ($GLOBALS['EDIT_TIME_LIMIT'] >= 0) {    // Time limit enabled,
            $edit_time_limit = $GLOBALS['EDIT_TIME_LIMIT'] ? '<br /><b>Editing Time Limit</b>: ' . $GLOBALS['EDIT_TIME_LIMIT'] . ' minutes' : '<br /><b>Editing Time Limit</b>: Unlimited';
        }
    } else if ($arg == 'private') {
        $o =& $GLOBALS['FUD_OPT_1'];

        if ($o & 4096) {
            $post_opt_fud = '<a href="/index.php?section=readingposting&amp;t=help_index&amp;' . _rsid . '#style" target="_blank"><b>BBcode</b> is <b>on</b></a>';
        } else if (!($o & 2048)) {
            $post_opt_html = '<b>HTML</b> is <b>on</b>';
        }
        if ($o & 16384) {
            $post_opt_images = '<b>Images</b> are <b>on</b>';
        }
        if ($o & 8192) {
            $post_opt_smilies = '<a href="/index.php?section=readingposting&amp;t=help_index&amp;' . _rsid . '#sml" target="_blank"><b>Smilies</b> are <b>on</b></a>';
        }
    } else if ($arg == 'sig') {
        $o =& $GLOBALS['FUD_OPT_1'];

        if ($o & 131072) {
            $post_opt_fud = '<a href="/index.php?section=readingposting&amp;t=help_index&amp;' . _rsid . '#style" target="_blank"><b>BBcode</b> is <b>on</b></a>';
        } else if (!($o & 65536)) {
            $post_opt_html = '<b>HTML</b> is <b>on</b>';
        }
        if ($o & 524288) {
            $post_opt_images = '<b>Images</b> are <b>on</b>';
        }
        if ($o & 262144) {
            $post_opt_smilies = '<a href="/index.php?section=readingposting&amp;t=help_index&amp;' . _rsid . '#sml" target="_blank"><b>Smilies</b> are <b>on</b></a>';
        }
    }

    return 'Forum Options:<br /><span class="SmallText">
' . $post_opt_html . '<br />
' . $post_opt_fud . '<br />
' . $post_opt_images . '<br />
' . $post_opt_smilies . $edit_time_limit . '</span>';
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

$GLOBALS['seps'] = array(' ' => ' ', "\n" => "\n", "\r" => "\r", '\'' => '\'', '"' => '"', '[' => '[', ']' => ']', '(' => '(', ';' => ';', ')' => ')', "\t" => "\t", '=' => '=', '>' => '>', '<' => '<');

/** Validate and sanitize a given URL. */
function url_check($url)
{
    // Remove spaces.
    $url = preg_replace('!\s+!', '', $url);

    // Remove quotes around URLs like in [url="http://..."].
    $url = str_replace('&quot;', '', $url);

    // Fix URL encoding.
    if (strpos($url, '&amp;#') !== false) {
        $url = preg_replace('!&#([0-9]{2,3});!e', "chr(\\1)", char_fix($url));
    }

    // Bad URL's (like 'script:' or 'data:').
    if (preg_match('/(script:|data:)/', $url)) return false;

    // International domains not recodnised - https://bugs.php.net/bug.php?id=73176
    // return filter_var($url, FILTER_SANITIZE_URL);

    return strip_tags($url);
}

/** Convert BBCode tags to HTML. */
function tags_to_html($str, $allow_img = 1, $no_char = 0)
{
    if (!$no_char) {
        $str = htmlspecialchars($str);
    }

    $str = nl2br($str);

    $ostr = '';
    $pos = $old_pos = 0;

    // Call all BBcode to HTML conversion plugins.
    if (defined('plugins')) {
        list($str) = plugin_call_hook('BBCODE2HTML', array($str));
    }

    while (($pos = strpos($str, '[', $pos)) !== false) {
        if (isset($str[$pos + 1], $GLOBALS['seps'][$str[$pos + 1]])) {
            ++$pos;
            continue;
        }

        if (($epos = strpos($str, ']', $pos)) === false) {
            break;
        }
        if (!($epos - $pos - 1)) {
            $pos = $epos + 1;
            continue;
        }
        $tag = substr($str, $pos + 1, $epos - $pos - 1);
        if (($pparms = strpos($tag, '=')) !== false) {
            $parms = substr($tag, $pparms + 1);
            if (!$pparms) { /*[= exception */
                $pos = $epos + 1;
                continue;
            }
            $tag = substr($tag, 0, $pparms);
        } else {
            $parms = '';
        }

        if (!$parms && ($tpos = strpos($tag, '[')) !== false) {
            $pos += $tpos;
            continue;
        }
        $tag = strtolower($tag);

        switch ($tag) {
            case 'quote title':
                $tag = 'quote';
                break;
            case 'list type':
                $tag = 'list';
                break;
            case 'hr':
                $str{$pos} = '<';
                $str{$pos + 1} = 'h';
                $str{$pos + 2} = 'r';
                $str{$epos} = '>';
                continue 2;
        }

        if ($tag[0] == '/') {
            if (isset($end_tag[$pos])) {
                if (($pos - $old_pos)) $ostr .= substr($str, $old_pos, $pos - $old_pos);
                $ostr .= $end_tag[$pos];
                $pos = $old_pos = $epos + 1;
            } else {
                $pos = $epos + 1;
            }

            continue;
        }

        $cpos = $epos;
        $ctag = '[/' . $tag . ']';
        $ctag_l = strlen($ctag);
        $otag = '[' . $tag;
        $otag_l = strlen($otag);
        $rf = 1;
        $nt_tag = 0;
        while (($cpos = strpos($str, '[', $cpos)) !== false) {
            if (isset($end_tag[$cpos]) || isset($GLOBALS['seps'][$str[$cpos + 1]])) {
                ++$cpos;
                continue;
            }

            if (($cepos = strpos($str, ']', $cpos)) === false) {
                if (!$nt_tag) {
                    break 2;
                } else {
                    break;
                }
            }

            if (strcasecmp(substr($str, $cpos, $ctag_l), $ctag) == 0) {
                --$rf;
            } else if (strcasecmp(substr($str, $cpos, $otag_l), $otag) == 0) {
                ++$rf;
            } else {
                $nt_tag++;
                ++$cpos;
                continue;
            }

            if (!$rf) {
                break;
            }
            $cpos = $cepos;
        }

        if (!$cpos || ($rf && $str[$cpos] == '<')) { /* Left over [ handler. */
            ++$pos;
            continue;
        }

        if ($cpos !== false) {
            if (($pos - $old_pos)) {
                $ostr .= substr($str, $old_pos, $pos - $old_pos);
            }
            switch ($tag) {
                case 'notag':
                    $ostr .= '<span name="notag">' . substr($str, $epos + 1, $cpos - 1 - $epos) . '</span>';
                    $epos = $cepos;
                    break;
                case 'url':
                    if (!$parms) {
                        $url = substr($str, $epos + 1, ($cpos - $epos) - 1);
                    } else {
                        $url = $parms;
                    }

                    $url = url_check($url);

                    if (!strncasecmp($url, 'www.', 4)) {
                        $url = 'http&#58;&#47;&#47;' . $url;
                    } else if (!preg_match('/^(http|ftp|\.|\/)/i', $url)) {
                        // Skip invalid or bad URL (like 'script:' or 'data:').
                        $ostr .= substr($str, $pos, $cepos - $pos + 1);
                        $epos = $cepos;
                        $str[$cpos] = '<';
                        break;
                    } else {
                        $url = str_replace('://', '&#58;&#47;&#47;', $url);
                    }

                    if (strtolower(substr($str, $epos + 1, 6)) == '[/url]') {
                        $end_tag[$cpos] = $url . '</a>';  // Fill empty link.
                    } else {
                        $end_tag[$cpos] = '</a>';
                    }
                    $ostr .= '<a href="' . $url . '">';
                    break;
                case 'i':
                case 'u':
                case 'b':
                case 's':
                case 'sub':
                case 'sup':
                case 'del':
                case 'big':
                case 'small':
                case 'center':
                    $end_tag[$cpos] = '</' . $tag . '>';
                    $ostr .= '<' . $tag . '>';
                    break;
                case 'h1':
                case 'h2':
                case 'h3':
                case 'h4':
                case 'h5':
                case 'h6':
                    $end_tag[$cpos] = '</' . $tag . '>';
                    $ostr .= '<' . $tag . '>';
                    break;
                case 'email':
                    if (!$parms) {
                        $parms = str_replace('@', '&#64;', substr($str, $epos + 1, ($cpos - $epos) - 1));
                        $ostr .= '<a href="mailto:' . $parms . '">' . $parms . '</a>';
                        $epos = $cepos;
                        $str[$cpos] = '<';
                    } else {
                        $end_tag[$cpos] = '</a>';
                        $ostr .= '<a href="mailto:' . str_replace('@', '&#64;', $parms) . '">';
                    }
                    break;
                case 'color':
                case 'size':
                case 'font':
                    if ($tag == 'font') {
                        $tag = 'face';
                    }
                    $end_tag[$cpos] = '</font>';
                    $ostr .= '<font ' . $tag . '="' . $parms . '">';
                    break;
                case 'code':
                    $param = substr($str, $epos + 1, ($cpos - $epos) - 1);

                    $ostr .= '<div class="pre"><pre>' . reverse_nl2br($param) . '</pre></div>';
                    $epos = $cepos;
                    $str[$cpos] = '<';
                    break;
                case 'pre':
                    $param = substr($str, $epos + 1, ($cpos - $epos) - 1);

                    $ostr .= '<pre>' . reverse_nl2br($param) . '</pre>';
                    $epos = $cepos;
                    $str[$cpos] = '<';
                    break;
                case 'php':
                    $param = trim(reverse_fmt(reverse_nl2br(substr($str, $epos + 1, ($cpos - $epos) - 1))));

                    if (strncmp($param, '<?php', 5)) {
                        if (strncmp($param, '<?', 2)) {
                            $param = "<?php\n" . $param;
                        } else {
                            $param = "<?php\n" . substr($param, 3);
                        }
                    }
                    if (substr($param, -2) != '?>') {
                        $param .= "\n?>";
                    }

                    $ostr .= '<span name="php">' . trim(@highlight_string($param, true)) . '</span><!--php-->';
                    $epos = $cepos;
                    $str[$cpos] = '<';
                    break;
                case 'img':    // Image, image left and right.
                case 'imgl':
                case 'imgr':
                    if (!$allow_img) {
                        $ostr .= substr($str, $pos, ($cepos - $pos) + 1);
                    } else {
                        $class = ($tag == 'img') ? '' : 'class="' . $tag{3} . '" ';

                        if (!$parms) {
                            // Relative URLs or physical with http/https/ftp.
                            if ($url = url_check(substr($str, $epos + 1, ($cpos - $epos) - 1))) {
                                $ostr .= '<img ' . $class . 'src="' . $url . '" border="0" alt="' . $url . '" />';
                            } else {
                                $ostr .= substr($str, $pos, ($cepos - $pos) + 1);
                            }
                        } else {
                            if ($url = url_check($parms)) {
                                $ostr .= '<img ' . $class . 'src="' . $url . '" border="0" alt="' . substr($str, $epos + 1, ($cpos - $epos) - 1) . '" />';
                            } else {
                                $ostr .= substr($str, $pos, ($cepos - $pos) + 1);
                            }
                        }
                    }
                    $epos = $cepos;
                    $str[$cpos] = '<';
                    break;
                case 'quote':
                    if (!$parms) {
                        $parms = 'Quote:';
                    } else {
                        $parms = str_replace(array('@', ':'), array('&#64;', '&#58;'), $parms);
                    }
                    $ostr .= '<cite>' . $parms . '</cite><blockquote>';
                    $end_tag[$cpos] = '</blockquote>';
                    break;
                case 'align':    // Aligh left, right or centre
                    $end_tag[$cpos] = '</div><!--align-->';
                    $ostr .= '<div align="' . $parms . '">';
                    break;
                case 'float':    // Float left or right
                    $end_tag[$cpos] = '</span><!--float-->';
                    $ostr .= '<span style="float:' . $parms . '">';
                    break;
                case 'left':    // Back convert to [aligh=left]
                    $end_tag[$cpos] = '</div><!--align-->';
                    $ostr .= '<div align="left">';
                    break;
                case 'right':    // Back convert to [aligh=right]
                    $end_tag[$cpos] = '</div><!--align-->';
                    $ostr .= '<div align="right">';
                    break;
                case 'list':
                    $tmp = substr($str, $epos, ($cpos - $epos));
                    $tmp_l = strlen($tmp);
                    $tmp2 = str_replace(array('[*]', '[li]'), '<li>', $tmp);
                    $tmp2_l = strlen($tmp2);
                    $str = str_replace($tmp, $tmp2, $str);

                    $diff = $tmp2_l - $tmp_l;
                    $cpos += $diff;

                    if (isset($end_tag)) {
                        foreach ($end_tag as $key => $val) {
                            if ($key < $epos) {
                                continue;
                            }

                            $end_tag[$key + $diff] = $val;
                        }
                    }

                    switch (strtolower($parms)) {
                        case '1':
                        case 'decimal':
                        case 'a':
                            $end_tag[$cpos] = '</ol>';
                            $ostr .= '<ol type="' . $parms . '">';
                            break;
                        case 'square':
                        case 'circle':
                        case 'disc':
                            $end_tag[$cpos] = '</ul>';
                            $ostr .= '<ul type="' . $parms . '">';
                            break;
                        default:
                            $end_tag[$cpos] = '</ul>';
                            $ostr .= '<ul>';
                    }
                    break;
                case 'spoiler':
                    $rnd = rand();
                    $end_tag[$cpos] = '</div></div>';
                    $ostr .= '<div class="dashed" style="padding: 3px;" align="center"><a href="javascript://" onclick="javascript: layerVis(\'s' . $rnd . '\', 1);">'
                        . ($parms ? $parms : 'Toggle Spoiler') . '</a><div align="left" id="s' . $rnd . '" style="display: none;">';
                    break;
                case 'acronym':
                    $end_tag[$cpos] = '</acronym>';
                    $ostr .= '<acronym title="' . ($parms ? $parms : ' ') . '">';
                    break;
                case 'wikipedia':
                    $end_tag[$cpos] = '</a>';
                    $url = substr($str, $epos + 1, ($cpos - $epos) - 1);
                    if ($parms && preg_match('!^[A-Za-z]+$!', $parms)) {
                        $parms .= '.';
                    } else {
                        $parms = '';
                    }
                    $ostr .= '<a href="http://' . $parms . 'wikipedia.com/wiki/' . $url . '" name="WikiPediaLink">';
                    break;
                case 'indent':
                case 'tab':
                    $end_tag[$cpos] = '</span><!--indent-->';
                    $ostr .= '<span class="indent">';
                    break;
            }

            $str[$pos] = '<';
            $pos = $old_pos = $epos + 1;
        } else {
            $pos = $epos + 1;
        }
    }
    $ostr .= substr($str, $old_pos, strlen($str) - $old_pos);

    /* URL paser. */
    $pos = 0;
    $ppos = 0;
    while (($pos = @strpos($ostr, '://', $pos)) !== false) {
        if ($pos < $ppos) {
            break;
        }
        // Check if it's inside any tag.
        $i = $pos;
        while (--$i && $i > $ppos) {
            if ($ostr[$i] == '>' || $ostr[$i] == '<') {
                break;
            }
        }
        if (!$pos || $ostr[$i] == '<') {
            $pos += 3;
            continue;
        }

        // Check if it's inside the A tag.
        if (($ts = strpos($ostr, '<a ', $pos)) === false) {
            $ts = strlen($ostr);
        }
        if (($te = strpos($ostr, '</a>', $pos)) == false) {
            $te = strlen($ostr);
        }
        if ($te < $ts) {
            $ppos = $pos += 3;
            continue;
        }

        // Check if it's inside the PRE tag.
        if (($ts = strpos($ostr, '<pre>', $pos)) === false) {
            $ts = strlen($ostr);
        }
        if (($te = strpos($ostr, '</pre>', $pos)) == false) {
            $te = strlen($ostr);
        }
        if ($te < $ts) {
            $ppos = $pos += 3;
            continue;
        }

        // Check if it's inside the SPAN tag.
        if (($ts = strpos($ostr, '<span>', $pos)) === false) {
            $ts = strlen($ostr);
        }
        if (($te = strpos($ostr, '</span>', $pos)) == false) {
            $te = strlen($ostr);
        }
        if ($te < $ts) {
            $ppos = $pos += 3;
            continue;
        }

        $us = $pos;
        $l = strlen($ostr);
        while (1) {
            --$us;
            if ($ppos > $us || $us >= $l || isset($GLOBALS['seps'][$ostr[$us]])) {
                break;
            }
        }

        unset($GLOBALS['seps']['=']);
        $ue = $pos;
        while (1) {
            ++$ue;
            if ($ue >= $l || isset($GLOBALS['seps'][$ostr[$ue]])) {
                break;
            }

            if ($ostr[$ue] == '&') {
                if ($ostr[$ue + 4] == ';') {
                    $ue += 4;
                    continue;
                }
                if ($ostr[$ue + 3] == ';' || $ostr[$ue + 5] == ';') {
                    break;
                }
            }

            if ($ue >= $l || isset($GLOBALS['seps'][$ostr[$ue]])) {
                break;
            }
        }
        $GLOBALS['seps']['='] = '=';

        $url = url_check(substr($ostr, $us + 1, $ue - $us - 1));
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^(http|ftp)/i', $url) || ($ue - $us - 1) < 9) {
            // Skip invalid or bad URL (like 'script:' or 'data:').
            $pos = $ue;
            continue;
        }
        $html_url = '<a href="' . $url . '">' . $url . '</a>';
        $html_url_l = strlen($html_url);
        $ostr = substr_replace($ostr, $html_url, $us + 1, $ue - $us - 1);
        $ppos = $pos;
        $pos = $us + $html_url_l;
    }

    /* E-mail parser. */
    $pos = 0;
    $ppos = 0;

    $er = array_flip(array_merge(range(0, 9), range('A', 'Z'), range('a', 'z'), array('.', '-', '\'', '_')));

    while (($pos = @strpos($ostr, '@', $pos)) !== false) {
        if ($pos < $ppos) {
            break;
        }

        // Check if it's inside any tag.
        $i = $pos;
        while (--$i && $i > $ppos) {
            if ($ostr[$i] == '>' || $ostr[$i] == '<') {
                break;
            }
        }
        if ($i < 0 || $ostr[$i] == '<') {
            ++$pos;
            continue;
        }

        // Check if it's inside the A tag.
        if (($ts = strpos($ostr, '<a ', $pos)) === false) {
            $ts = strlen($ostr);
        }
        if (($te = strpos($ostr, '</a>', $pos)) == false) {
            $te = strlen($ostr);
        }
        if ($te < $ts) {
            $ppos = $pos += 1;
            continue;
        }

        // Check if it's inside the PRE tag.
        if (($ts = strpos($ostr, '<div class="pre"><pre>', $pos)) === false) {
            $ts = strlen($ostr);
        }
        if (($te = strpos($ostr, '</pre></div>', $pos)) == false) {
            $te = strlen($ostr);
        }
        if ($te < $ts) {
            $ppos = $pos += 1;
            continue;
        }

        for ($es = ($pos - 1); $es > ($ppos - 1); $es--) {
            if (isset($er[$ostr[$es]])) continue;
            ++$es;
            break;
        }
        if ($es == $pos) {
            $ppos = $pos += 1;
            continue;
        }
        if ($es < 0) {
            $es = 0;
        }

        for ($ee = ($pos + 1); @isset($ostr[$ee]); $ee++) {
            if (isset($er[$ostr[$ee]])) continue;
            break;
        }
        if ($ee == ($pos + 1)) {
            $ppos = $pos += 1;
            continue;
        }

        $email = substr($ostr, $es, $ee - $es);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $ppos = $pos += 1;
            continue;
        }
        $email = str_replace('@', '&#64;', $email);
        $email_url = '<a href="mailto:' . $email . '">' . $email . '</a>';
        $email_url_l = strlen($email_url);
        $ostr = substr_replace($ostr, $email_url, $es, $ee - $es);
        $ppos = $es + $email_url_l;
        $pos = $ppos;
    }

    // Remove line breaks directly following list tags.
    $ostr = preg_replace('!(<[uo]l>)\s*<br\s*/?\s*>\s*(<li>)!is', '\\1\\2', $ostr);
    $ostr = preg_replace('!</(ul|ol|table|pre|code|blockquote|div)>\s*<br\s*/?\s*>!is', '</\\1>', $ostr);

    // Remove <br /> after block level HTML tags like /TABLE, /LIST, /PRE, /BLOCKQUOTE, etc.
    $ostr = preg_replace('!</(ul|ol|table|pre|code|blockquote|div|hr|h1|h2|h3|h4|h5|h6)>\s*<br\s*/?\s*>!is', '</\\1>', $ostr);
    $ostr = preg_replace('!<(hr)>\s*<br\s*/?\s*>!is', '<\\1>', $ostr);

    return $ostr;
}

/** Convert HTML back to BBCode tags. */
function html_to_tags($fudml)
{
    // Call all HTML to BBcode conversion plugins.
    if (defined('plugins')) {
        list($fudml) = plugin_call_hook('HTML2BBCODE', array($fudml));
    }

    // Remove PHP code blocks so they can't interfere with parsing.
    while (preg_match('/<span name="php">(.*?)<\/span><!--php-->/is', $fudml, $res)) {
        $tmp = trim(html_entity_decode(strip_tags(str_replace('<br />', "\n", $res[1]))));
        $m = md5($tmp);
        $php[$m] = $tmp;
        $fudml = str_replace($res[0], "[php]\n" . $m . "\n[/php]", $fudml);
    }

    // Wikipedia tags.
    while (preg_match('!<a href="http://(?:([A-ZA-z]+)?\.)?wikipedia.com/wiki/([^"]+)"( target="_blank")? name="WikiPediaLink">(.*?)</a>!s', $fudml, $res)) {
        if (count($res) == 5) {
            $fudml = str_replace($res[0], '[wikipedia=' . $res[1] . ']' . $res[2] . '[/wikipedia]', $fudml);
        } else {
            $fudml = str_replace($res[0], '[wikipedia]' . $res[2] . '[/wikipedia]', $fudml);
        }
    }

    // Quote tags.
    if (strpos($fudml, '<cite>') !== false) {
        $fudml = str_replace(array('<cite>', '</cite><blockquote>', '</blockquote>'), array('[quote title=', ']', '[/quote]'), $fudml);
    }
    // Old bad quote tags.
    if (preg_match('!class="quote"!', $fudml)) {
        $fudml = preg_replace('!<table border="0" align="center" width="90%" cellpadding="3" cellspacing="1">(<tbody>)?<tr><td class="SmallText"><b>!', '[quote title=', $fudml);
        $fudml = preg_replace('!</b></td></tr><tr><td class="quote">(<br>)?!', ']', $fudml);
        $fudml = preg_replace('!(<br>)?</td></tr>(</tbody>)?</table>!', '[/quote]', $fudml);
    }

    // Spoiler tags.
    if (preg_match('!<div class="dashed" style="padding: 3px;" align="center"( width="100%")?><a href="javascript://" OnClick="javascript: layerVis\(\'.*?\', 1\);">.*?</a><div align="left" id="(.*?)" style="display: none;">!is', $fudml)) {
        $fudml = preg_replace('!\<div class\="dashed" style\="padding: 3px;" align\="center"( width\="100%")?\>\<a href\="javascript://" OnClick\="javascript: layerVis\(\'.*?\', 1\);">(.*?)\</a\>\<div align\="left" id\=".*?" style\="display: none;"\>!is', '[spoiler=\2]', $fudml);
        $fudml = str_replace('</div></div>', '[/spoiler]', $fudml);
    }
    // Old bad spoiler format.
    if (preg_match('!<div class="dashed" style="padding: 3px;" align="center" width="100%"><a href="javascript://" OnClick="javascript: layerVis\(\'.*?\', 1\);">.*?</a><div align="left" id="(.*?)" style="visibility: hidden;">!is', $fudml)) {
        $fudml = preg_replace('!\<div class\="dashed" style\="padding: 3px;" align\="center" width\="100%"\>\<a href\="javascript://" OnClick\="javascript: layerVis\(\'.*?\', 1\);">(.*?)\</a\>\<div align\="left" id\=".*?" style\="visibility: hidden;"\>!is', '[spoiler=\1]', $fudml);
        $fudml = str_replace('</div></div>', '[/spoiler]', $fudml);
    }

    // Color, font and size tags.
    $fudml = str_replace('<font face=', '<font font=', $fudml);
    foreach (array('color', 'font', 'size') as $v) {
        while (preg_match('!<font ' . $v . '=".+?">.*?</font>!is', $fudml, $m)) {
            $fudml = preg_replace('!<font ' . $v . '="(.+?)">(.*?)</font>!is', '[' . $v . '=\1]\2[/' . $v . ']', $fudml);
        }
    }

    // Acronym tags.
    while (preg_match('!<acronym title=".+?">.*?</acronym>!is', $fudml)) {
        $fudml = preg_replace('!<acronym title="(.+?)">(.*?)</acronym>!is', '[acronym=\1]\2[/acronym]', $fudml);
    }

    // List tags.
    while (preg_match('!<(o|u)l.*?</\\1l>!is', $fudml)) {
        $fudml = preg_replace('!<(o|u)l type="(.+?)">(.*?)</\\1l>!is', "\n[list type=\\2]\\3[/list]\n", $fudml);
        $fudml = preg_replace('!<(o|u)l>(.*?)</\\1l>!is', "\n[list]\\2[/list]\n", $fudml);
        $fudml = str_ireplace(array('<li>', '</li>'), array("\n[*]", ''), $fudml);
    }

    $fudml = str_replace(
        array(
            '<b>', '</b>', '<i>', '</i>', '<u>', '</u>', '<s>', '</s>', '<sub>', '</sub>', '<sup>', '</sup>',
            '<del>', '</del>', '<big>', '</big>', '<small>', '</small>', '<center>', '</center>',
            '<div class="pre"><pre>', '</pre></div>',
            '<div align="left">', '<div align="right">', '<div align="center">', '</div><!--align-->',
            '<span style="float:left">', '<span style="float:right">', '</span><!--float-->',
            '<span class="indent">', '</span><!--indent-->',
            '<span name="notag">', '</span>', '&#64;', '&#58;&#47;&#47;', '<br />', '<pre>', '</pre>', '<hr>',
            '<h1>', '</h1>', '<h2>', '</h2>', '<h3>', '</h3>', '<h4>', '</h4>', '<h5>', '</h5>', '<h6>', '</h6>'
        ),
        array(
            '[b]', '[/b]', '[i]', '[/i]', '[u]', '[/u]', '[s]', '[/s]', '[sub]', '[/sub]', '[sup]', '[/sup]',
            '[del]', '[/del]', '[big]', '[/big]', '[small]', '[/small]', '[center]', '[/center]',
            '[code]', '[/code]',
            '[align=left]', '[align=right]', '[align=center]', '[/align]',
            '[float=left]', '[float=right]', '[/float]',
            '[indent]', '[/indent]',
            '[notag]', '[/notag]', '@', '://', '', '[pre]', '[/pre]', '[hr]',
            '[h1]', '[/h1]', '[h2]', '[/h2]', '[h3]', '[/h3]', '[h4]', '[/h4]', '[h5]', '[/h5]', '[h6]', '[/h6]'
        ),
        $fudml);

    // Image, Email and URL tags/
    while (preg_match('!<img src="(.*?)" border="?0"? alt="\\1" ?/?>!is', $fudml)) {
        $fudml = preg_replace('!<img src="(.*?)" border="?0"? alt="\\1" ?/?>!is', '[img]\1[/img]', $fudml);
    }
    while (preg_match('!<img class="(r|l)" src="(.*?)" border="?0"? alt="\\2" ?/?>!is', $fudml)) {
        $fudml = preg_replace('!<img class="(r|l)" src="(.*?)" border="?0"? alt="\\2" ?/?>!is', '[img\1]\2[/img\1]', $fudml);
    }
    while (preg_match('!<a href="mailto:(.+?)"( target="_blank")?>\\1</a>!is', $fudml)) {
        $fudml = preg_replace('!<a href="mailto:(.+?)"( target="_blank")?>\\1</a>!is', '[email]\1[/email]', $fudml);
    }
    while (preg_match('!<a href="(.+?)"( target="_blank")?>\\1</a>!is', $fudml)) {
        $fudml = preg_replace('!<a href="(.+?)"( target="_blank")?>\\1</a>!is', '[url]\1[/url]', $fudml);
    }

    if (strpos($fudml, '<img src="') !== false) {
        $fudml = preg_replace('!<img src="(.*?)" border="?0"? alt="(.*?)" ?/?>!is', '[img=\1]\2[/img]', $fudml);
    }
    if (strpos($fudml, '<img class="') !== false) {
        $fudml = preg_replace('!<img class="(r|l)" src="(.*?)" border="?0"? alt="(.*?)" ?/?>!is', '[img\1=\2]\3[/img\1]', $fudml);
    }
    if (strpos($fudml, '<a href="mailto:') !== false) {
        $fudml = preg_replace('!<a href="mailto:(.+?)"( target="_blank")?>(.+?)</a>!is', '[email=\1]\3[/email]', $fudml);
    }
    if (strpos($fudml, '<a href="') !== false) {
        $fudml = preg_replace('!<a href="(.+?)"( target="_blank")?>(.+?)</a>!is', '[url=\1]\3[/url]', $fudml);
    }

    // Re-insert PHP code blocks.
    if (isset($php)) {
        $fudml = str_replace(array_keys($php), array_values($php), $fudml);
    }

    // Un-htmlspecialchars.
    return reverse_fmt($fudml);
}

/** Check to ensure file extention is in the list of allowed extentions. */
function filter_ext($file_name)
{
    include $GLOBALS['FORUM_SETTINGS_PATH'] . 'file_filter_regexp';
    if (empty($GLOBALS['__FUD_EXT_FILER__'])) {
        return;
    }
    if (($p = strrpos($file_name, '.')) === false) {
        return 1;
    }
    return !in_array(strtolower(substr($file_name, ($p + 1))), $GLOBALS['__FUD_EXT_FILER__']);
}

/** Reverse conversion from new lines to break tags. */
function reverse_nl2br($data)
{
    if (strpos($data, '<br />') !== false) {
        return str_replace('<br />', '', $data);
    }
    return $data;
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

function fud_wrap_tok($data)
{
    $wa = array();
    $len = strlen($data);

    $i = $j = $p = 0;
    $str = '';
    while ($i < $len) {
        switch ($data{$i}) {
            case ' ':
            case "\n":
            case "\t":
                if ($j) {
                    $wa[] = array('word' => $str, 'len' => ($j + 1));
                    $j = 0;
                    $str = '';
                }

                $wa[] = array('word' => $data[$i]);

                break;
            case '<':
                if (($p = strpos($data, '>', $i)) !== false) {
                    if ($j) {
                        $wa[] = array('word' => $str, 'len' => ($j + 1));
                        $j = 0;
                        $str = '';
                    }
                    $s = substr($data, $i, ($p - $i) + 1);
                    if ($s == '<pre>') {
                        $s = substr($data, $i, ($p = (strpos($data, '</pre>', $p) + 6)) - $i);
                        --$p;
                    } else if ($s == '<span name="php">') {
                        $s = substr($data, $i, ($p = (strpos($data, '</span>', $p) + 7)) - $i);
                        --$p;
                    }

                    $wa[] = array('word' => $s);

                    $i = $p;
                    $j = 0;
                } else {
                    $str .= $data[$i];
                    $j++;
                }
                break;

            case '&':
                if (($e = strpos($data, ';', $i))) {
                    $st = substr($data, $i, ($e - $i + 1));
                    if (($st{1} == '#' && is_numeric(substr($st, 3, -1))) || !strcmp($st, '&nbsp;') || !strcmp($st, '&gt;') || !strcmp($st, '&lt;') || !strcmp($st, '&quot;')) {
                        if ($j) {
                            $wa[] = array('word' => $str, 'len' => ($j + 1));
                            $j = 0;
                            $str = '';
                        }

                        $wa[] = array('word' => $st, 'sp' => 1);
                        $i = $e;
                        $j = 0;
                        break;
                    }
                } /* fall through */
            default:
                $str .= $data[$i];
                $j++;
        }
        $i++;
    }

    if ($j) {
        $wa[] = array('word' => $str, 'len' => ($j + 1));
    }

    return $wa;
}

/* Wrap messages by inserting a space into strings longer the spesified length. */
function fud_wordwrap(&$data)
{
    $m = (int)$GLOBALS['WORD_WRAP'];
    if (!$m || $m >= strlen($data)) {
        return;
    }

    $wa = fud_wrap_tok($data);
    $l = 0;
    $data = '';
    foreach ($wa as $v) {
        if (isset($v['len']) && $v['len'] > $m) {
            if ($v['len'] + $l > $m) {
                $l = 0;
                $data .= ' ';
            }
            $data .= wordwrap($v['word'], $m, ' ', 1);
            $l += $v['len'];
        } else {
            if (isset($v['sp'])) {
                if ($l > $m) {
                    $data .= ' ';
                    $l = 0;
                }
                ++$l;
            } else if (!isset($v['len'])) {
                $l = 0;
            } else {
                $l += $v['len'];
            }
            $data .= $v['word'];
        }
    }
}

$GLOBALS['__SML_CHR_CHK__'] = array("\n" => 1, "\r" => 1, "\t" => 1, ' ' => 1, ']' => 1, '[' => 1, '<' => 1, '>' => 1, '\'' => 1, '"' => 1, '(' => 1, ')' => 1, '.' => 1, ',' => 1, '!' => 1, '?' => 1);

function smiley_to_post($text)
{
    $text_l = strtolower($text);
    include $GLOBALS['FORUM_SETTINGS_PATH'] . 'sp_cache';

    /* remove all non-formatting blocks */
    foreach (array('</pre>' => '<pre>', '</span>' => '<span name="php">') as $k => $v) {
        $p = 0;
        while (($p = strpos($text_l, $v, $p)) !== false) {
            if (($e = strpos($text_l, $k, $p)) === false) {
                $p += 5;
                continue;
            }
            $text_l = substr_replace($text_l, str_repeat(' ', $e - $p), $p, ($e - $p));
            $p = $e;
        }
    }

    foreach ($SML_REPL as $k => $v) {
        $a = 0;
        $len = strlen($k);
        while (($a = strpos($text_l, $k, $a)) !== false) {
            if ((!$a || isset($GLOBALS['__SML_CHR_CHK__'][$text_l[$a - 1]])) && ((@$ch = $text_l[$a + $len]) == '' || isset($GLOBALS['__SML_CHR_CHK__'][$ch]))) {
                $text_l = substr_replace($text_l, $v, $a, $len);
                $text = substr_replace($text, $v, $a, $len);
                $a += strlen($v) - $len;
            } else {
                $a += $len;
            }
        }
    }

    return $text;
}

function post_to_smiley($text)
{
    /* include once since draw_post_smiley_cntrl() may use it too */
    include_once $GLOBALS['FORUM_SETTINGS_PATH'] . 'ps_cache';
    if (isset($PS_SRC)) {
        $GLOBALS['PS_SRC'] = $PS_SRC;
        $GLOBALS['PS_DST'] = $PS_DST;
    } else {
        $PS_SRC = $GLOBALS['PS_SRC'];
        $PS_DST = $GLOBALS['PS_DST'];
    }

    /* check for emoticons */
    foreach ($PS_SRC as $k => $v) {
        if (strpos($text, $v) === false) {
            unset($PS_SRC[$k], $PS_DST[$k]);
        }
    }

    return $PS_SRC ? str_replace($PS_SRC, $PS_DST, $text) : $text;
}/* Replace and censor text before it's stored. */
function apply_custom_replace($text)
{
    defined('__fud_replace_init') or make_replace_array();
    if (empty($GLOBALS['__FUD_REPL__'])) {
        return $text;
    }

    return preg_replace($GLOBALS['__FUD_REPL__']['pattern'], $GLOBALS['__FUD_REPL__']['replace'], $text);
}

function make_replace_array()
{
    $GLOBALS['__FUD_REPL__']['pattern'] = $GLOBALS['__FUD_REPL__']['replace'] = array();
    $a =& $GLOBALS['__FUD_REPL__']['pattern'];
    $b =& $GLOBALS['__FUD_REPL__']['replace'];

    $c = uq('SELECT with_str, replace_str FROM fud30_replace WHERE replace_str IS NOT NULL AND with_str IS NOT NULL AND LENGTH(replace_str)>0');
    while ($r = db_rowarr($c)) {
        $a[] = $r[1];
        $b[] = $r[0];
    }
    unset($c);

    define('__fud_replace_init', 1);
}

/* Reverse replacement and censorship of text. */
function apply_reverse_replace($text)
{
    defined('__fud_replacer_init') or make_reverse_replace_array();
    if (empty($GLOBALS['__FUD_REPLR__'])) {
        return $text;
    }
    return preg_replace($GLOBALS['__FUD_REPLR__']['pattern'], $GLOBALS['__FUD_REPLR__']['replace'], $text);
}

function make_reverse_replace_array()
{
    $GLOBALS['__FUD_REPLR__']['pattern'] = $GLOBALS['__FUD_REPLR__']['replace'] = array();
    $a =& $GLOBALS['__FUD_REPLR__']['pattern'];
    $b =& $GLOBALS['__FUD_REPLR__']['replace'];

    $c = uq('SELECT replace_opt, with_str, replace_str, from_post, to_msg FROM fud30_replace');
    while ($r = db_rowarr($c)) {
        if (!$r[0]) {
            $a[] = $r[3];
            $b[] = $r[4];
        } else if ($r[0] && strlen($r[1]) && strlen($r[2])) {
            $a[] = '/' . str_replace('/', '\\/', preg_quote(stripslashes($r[1]))) . '/';
            preg_match('/\/(.+)\/(.*)/', $r[2], $regs);
            $b[] = str_replace('\\/', '/', $regs[1]);
        }
    }
    unset($c);

    define('__fud_replacer_init', 1);
}

function check_return($returnto)
{
    if ($GLOBALS['FUD_OPT_2'] & 32768 && !empty($_SERVER['PATH_INFO'])) {
        if (!$returnto || !strncmp($returnto, '/er/', 4)) {
            header('Location: /index.php/i/' . _rsidl);
        } else if ($returnto[0] == '/') { /* Unusual situation, path_info & normal themes are active. */
            header('Location: /index.php' . $returnto);
        } else {
            header('Location: /index.php?' . $returnto);
        }
    } else if (!$returnto || !strncmp($returnto, 't=error', 7)) {
        header('Location: /index.php?t=index&' . _rsidl);
    } else if (strpos($returnto, 'S=') === false && $GLOBALS['FUD_OPT_1'] & 128) {
        header('Location: /index.php?' . $returnto . '&S=' . s);
    } else {
        header('Location: /index.php?' . $returnto);
    }
    exit;
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
}/* Generate a CAPTCHA question to display. */
function generate_turing_val()
{
    if (defined('plugins')) {
        $text = plugin_call_hook('CAPTCHA');
        if (!empty($text)) {
            return $text;
        }
    }

    $t = array(
        array('..#####..', '..#####..', '.#.......', '.#######.', '..#####..', '.#######.', '..#####..', '..#####..', '....###....', '.########..', '..######..', '.########.', '.########.', '..######...', '.##.....##.', '.####.', '.......##.', '.##....##.', '.##.......', '.##.....##.', '.##....##.', '.########..', '..#######..', '.########..', '..######..', '.########.', '.##.....##.', '.##.....##.', '.##......##.', '.##.....##.', '.##....##.', '.########.'),
        array('.#.....#.', '.#.....#.', '.#....#..', '.#.......', '.#.....#.', '.#....#..', '.#.....#.', '.#.....#.', '...##.##...', '.##.....##.', '.##....##.', '.##.......', '.##.......', '.##....##..', '.##.....##.', '..##..', '.......##.', '.##...##..', '.##.......', '.###...###.', '.###...##.', '.##.....##.', '.##.....##.', '.##.....##.', '.##....##.', '....##....', '.##.....##.', '.##.....##.', '.##..##..##.', '..##...##..', '..##..##..', '......##..'),
        array('.......#.', '.......#.', '.#....#..', '.#.......', '.#.......', '.....#...', '.#.....#.', '.#.....#.', '..##...##..', '.##.....##.', '.##.......', '.##.......', '.##.......', '.##........', '.##.....##.', '..##..', '.......##.', '.##..##...', '.##.......', '.####.####.', '.####..##.', '.##.....##.', '.##.....##.', '.##.....##.', '.##.......', '....##....', '.##.....##.', '.##.....##.', '.##..##..##.', '...##.##...', '...####...', '.....##...'),
        array('..#####..', '....###..', '.#....#..', '.######..', '.######..', '....#....', '..#####..', '..######.', '.##.....##.', '.########..', '.##.......', '.######...', '.######...', '.##...####.', '.#########.', '..##..', '.......##.', '.#####....', '.##.......', '.##.###.##.', '.##.##.##.', '.########..', '.##.....##.', '.########..', '..######..', '....##....', '.##.....##.', '.##.....##.', '.##..##..##.', '....###....', '....##....', '....##....'),
        array('.#.......', '.......#.', '.#######.', '.......#.', '.#.....#.', '...#.....', '.#.....#.', '.......#.', '.#########.', '.##.....##.', '.##.......', '.##.......', '.##.......', '.##....##..', '.##.....##.', '..##..', '.##....##.', '.##..##...', '.##.......', '.##.....##.', '.##..####.', '.##........', '.##..##.##.', '.##...##...', '.......##.', '....##....', '.##.....##.', '..##...##..', '.##..##..##.', '...##.##...', '....##....', '...##.....'),
        array('.#.......', '.#.....#.', '......#..', '.#.....#.', '.#.....#.', '...#.....', '.#.....#.', '.#.....#.', '.##.....##.', '.##.....##.', '.##....##.', '.##.......', '.##.......', '.##....##..', '.##.....##.', '..##..', '.##....##.', '.##...##..', '.##.......', '.##.....##.', '.##...###.', '.##........', '.##....##..', '.##....##..', '.##....##.', '....##....', '.##.....##.', '...##.##...', '.##..##..##.', '..##...##..', '....##....', '..##......'),
        array('.#######.', '..#####..', '......#..', '..#####..', '..#####..', '...#.....', '..#####..', '..#####..', '.##.....##.', '.########..', '..######..', '.########.', '.##.......', '..######...', '.##.....##.', '.####.', '..######..', '.##....##.', '.########.', '.##.....##.', '.##....##.', '.##........', '..#####.##.', '.##.....##.', '..######..', '....##....', '..#######..', '....###....', '..###..###..', '.##.....##.', '....##....', '.########.'),
        array('2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z')
    );

    $rv = array_rand($t[0], 4);
    $captcha = $t[7][$rv[0]] . $t[7][$rv[1]] . $t[7][$rv[2]] . $t[7][$rv[3]];
    $rt = md5($captcha);

    $text = '<input type="text" name="turing_test" id="turing_test" size="25" required="required" placeholder="There is no zero or one in the image." />';
    $text .= '<input type="hidden" name="turing_res" value="' . $rt . '" />';

    if (($GLOBALS['FUD_OPT_3'] & 33554432) && extension_loaded('gd') && function_exists('imagecreate')) {
        // Graphical captcha.
        ses_putvar((int)$GLOBALS['usr']->sid, $captcha);
        return $text . '<br />
<img src="index.php?t=captchaimg" alt="Captcha Verification: you will need to recognize the text in this image." />';
    } else {
        // Text based captcha.
        $bg_fill_chars = array(' ', '.', ',', '`', '_', '\'');
        $bg_fill = $bg_fill_chars[array_rand($bg_fill_chars)];
        $fg_fill_chars = array('&#35;', '&#64;', '&#36;', '&#42;', '&#88;');
        $fg_fill = $fg_fill_chars[array_rand($fg_fill_chars)];

        $text .= '<pre>';
        // Generate turing text.
        for ($i = 0; $i < 7; $i++) {
            foreach ($rv as $v) {
                $text .= str_replace('#', $fg_fill, str_replace('.', $bg_fill, $t[$i][$v]));
            }
            $text .= '<br />';
        }
        return $text . '</pre>';
    }
}

/* Test if user entered a valid response to the CAPTCHA test. */
// function test_turing_answer($test, $res)
function test_turing_answer()
{
    if (defined('plugins')) {
        $ok = plugin_call_hook('CAPTCHA_VALIDATE');
        if ($ok == 0) {
            return false;
        } elseif ($ok == 1) {
            return true;
        }
    }

    $test = $_POST['turing_test'];
    $res = $_POST['turing_res'];
    if (empty($test) || empty($res)) {
        return false;
    }

    if (md5(strtoupper(trim($test))) != $res) {
        return false;
    } else {
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

function fetch_img($url, $user_id)
{
    $ext = array(1 => 'gif', 2 => 'jpg', 3 => 'png', 4 => 'swf');
    list($max_w, $max_y) = explode('x', $GLOBALS['CUSTOM_AVATAR_MAX_DIM']);
    if (!($img_info = @getimagesize($url)) || $img_info[0] > $max_w || $img_info[1] > $max_y || $img_info[2] > ($GLOBALS['FUD_OPT_1'] & 64 ? 4 : 3)) {
        return;
    }
    if (!($img_data = file_get_contents($url))) {
        return;
    }
    $name = $user_id . '.' . $ext[$img_info[2]] . '_';

    while (($fp = fopen(($path = tempnam($GLOBALS['TMP'], $name)), 'ab'))) {
        if (!ftell($fp)) { /* Ensure that the temporary file picked did not exist before. Yes, this is paranoid. */
            break;
        }
    }
    fwrite($fp, $img_data);
    fclose($fp);

    return $path;
}

/* Intialize error status. */
$GLOBALS['error'] = 0;
$GLOBALS['err_msg'] = array();

function sanitize_url($url)
{
    if (!$url) {
        return '';
    }

    if (strncasecmp($url, 'http://', strlen('http://')) && strncasecmp($url, 'https://', strlen('https://')) && strncasecmp($url, 'ftp://', strlen('ftp://'))) {
        if (stristr($url, 'javascript:')) {
            return '';
        } else {
            return 'http://' . $url;
        }
    }
    return $url;
}

function sanitize_login($login)
{
    if (@preg_match('/\pL/u', 'a') == 1) {
        // Remove unicode control, formatting, and surrogate characters.
        $login = preg_replace('/[\p{Cc}\p{Cf}\p{Cs}]/u', '', $login);
    } else {
        // PCRE unicode support is disabled, only keep word and whitespace characters.
        $login = preg_replace('/[^\w\s]/', '', $login);
    }

    // Bad characters to remove from login names.
    $badchars = '<>&;';

    // Control characters are also bad.
    for ($i = 0; $i < 32; $i++) $badchars .= chr($i);

    return strtr($login, $badchars, str_repeat('?', strlen($badchars)));
}

function register_form_check($user_id)
{
    /* New user specific checks. */
    if (!$user_id) {
        if ($GLOBALS['REG_TIME_LIMIT'] > 0 && ($reg_limit_reached = $GLOBALS['REG_TIME_LIMIT'] + q_singleval('SELECT join_date FROM fud30_users WHERE id=' . q_singleval('SELECT MAX(id) FROM fud30_users')) - __request_timestamp__) > 0) {
            set_err('reg_time_limit', '<tr class="RowStyleA">
	<td class="ac ErrorText" colspan="2">The limit of one registration per ' . $GLOBALS['REG_TIME_LIMIT'] . ' seconds has been reached. Please wait ' . convertPlural($reg_limit_reached, array('' . $reg_limit_reached . ' second', '' . $reg_limit_reached . ' seconds')) . ', and then try to register once again.</td>
</tr>');
        }

        $_POST['reg_plaintext_passwd'] = trim($_POST['reg_plaintext_passwd']);

        if (strlen($_POST['reg_plaintext_passwd']) < 6) {
            set_err('reg_plaintext_passwd', 'Passwords must be at least 6 characters long.');
        }

        $_POST['reg_plaintext_passwd_conf'] = trim($_POST['reg_plaintext_passwd_conf']);

        if ($_POST['reg_plaintext_passwd'] !== $_POST['reg_plaintext_passwd_conf']) {
            set_err('reg_plaintext_passwd', 'Your passwords do not match, please try again.');
        }

        $_POST['reg_login'] = trim(sanitize_login($_POST['reg_login']));

        if (strlen($_POST['reg_login']) < 2) {
            set_err('reg_login', 'The Login you have selected is too short. Login names must be at least 2 characters long.');
        } else if (is_login_blocked($_POST['reg_login'])) {
            set_err('reg_login', 'This login name is not allowed.');
        } else if (get_id_by_login($_POST['reg_login'])) {
            set_err('reg_login', 'Forum login names must be unique. There is already a user with this name.');
        }

        // Try to catch submitter bots.
        $form_completion_time = __request_timestamp__ - (int)$_POST['turing_test1'];
        if (
            $form_completion_time < 5 || $form_completion_time > 3600 ||    // Took 5 sec to 1 hour.
            !empty($_POST['turing_test2']) ||                // Must always be empty.
            $_POST['turing_test3'] !== md5($GLOBALS['FORUM_SETTINGS_PATH'])    // No cross site submitions.
        ) {
            set_err('reg_turing', 'Verification failed. Please try again.');
        }

        // Perform turing test, if enabled.
        if (!($GLOBALS['FUD_OPT_3'] & 128)) { // Captcha not disabled.
            if (!test_turing_answer()) {
                set_err('reg_turing', 'Verification failed. Please try again.');
            }
        }

        $_POST['reg_email'] = trim($_POST['reg_email']);

        /* E-mail validity check. */
        if (validate_email($_POST['reg_email'])) {
            set_err('reg_email', 'The e-mail address you have specified does not appear to be valid.');
        } else if (get_id_by_email($_POST['reg_email'])) {
            set_err('reg_email', 'This e-mail address is already associated with an account. If you have forgotten your password please use the Restore option, instead of re-registering.');
        } else if (is_email_blocked($_POST['reg_email'])) {
            set_err('reg_email', 'This e-mail address is already associated with an account. If you have forgotten your password please use the Restore option, instead of re-registering.');
        }
    } else {
        if (!($r = db_sab('SELECT id, passwd, salt, name, email FROM fud30_users WHERE id=' . (!empty($_POST['mod_id']) ? __fud_real_user__ : $user_id)))) {
            exit('Go away!');
        }

        /* Require password only for changing E-mail address and name. */
        if (empty($_POST['reg_confirm_passwd']) || !((empty($r->salt) && $r->passwd == md5($_POST['reg_confirm_passwd'])) || $r->passwd == sha1($r->salt . sha1($_POST['reg_confirm_passwd'])))) {
            if ($_POST['reg_email'] != $r->email || $_POST['reg_name'] != $r->name) {
                if (!empty($_POST['mod_id'])) {
                    set_err('reg_confirm_passwd', 'You must enter your <b>administrator</b> password to complete the changes.');
                } else {
                    set_err('reg_confirm_passwd', 'You must enter your current password to complete the changes.');
                }
            }
        }

        /* E-mail validity check. */
        if (validate_email($_POST['reg_email'])) {
            set_err('reg_email', 'The e-mail address you have specified does not appear to be valid.');
        } else if (($email_id = get_id_by_email($_POST['reg_email'])) && $email_id != $user_id) {
            set_err('reg_email', 'Someone else is already registered with this e-mail address.');
        }
    }

    $_POST['reg_name'] = trim($_POST['reg_name']);
    $_POST['reg_home_page'] = !empty($_POST['reg_home_page']) ? sanitize_url(trim($_POST['reg_home_page'])) : '';
    $_POST['reg_user_image'] = !empty($_POST['reg_user_image']) ? sanitize_url(trim($_POST['reg_user_image'])) : '';

    if (!empty($_POST['reg_icq']) && !(int)$_POST['reg_icq']) { /* ICQ # can only be an integer. */
        $_POST['reg_icq'] = '';
    }

    /* User's name or nick name. - NOW OPTIONAL! */
    // if (strlen($_POST['reg_name']) < 2) {
    // 	set_err('reg_name', 'To successfully complete this registration you must enter your name.');
    // }

    /* Image count check. */
    if ($GLOBALS['FORUM_IMG_CNT_SIG'] && $GLOBALS['FORUM_IMG_CNT_SIG'] < substr_count(strtolower($_POST['reg_sig']), '[img]')) {
        set_err('reg_sig', 'You are trying to use more than the allowed ' . $GLOBALS['FORUM_IMG_CNT_SIG'] . ' images in your signature.');
    }

    /* URL Avatar check. */
    if (!empty($_POST['reg_avatar_loc']) && !($GLOBALS['reg_avatar_loc_file'] = fetch_img($_POST['reg_avatar_loc'], $user_id))) {
        set_err('avatar', 'The specified URL does not contain a valid image.');
    }
    if (!empty($GLOBALS['reg_avatar_loc_file']) && filesize($GLOBALS['reg_avatar_loc_file']) >= $GLOBALS['CUSTOM_AVATAR_MAX_SIZE']) {
        set_err('avatar', 'The file you are trying to upload is too big, more than ' . $GLOBALS['CUSTOM_AVATAR_MAX_SIZE'] . ' bytes.');
    }

    /* Alias Check. */
    if ($GLOBALS['FUD_OPT_2'] & 128 && isset($_POST['reg_alias'])) {
        if (($_POST['reg_alias'] = trim(sanitize_login($_POST['reg_alias'])))) {
            if (is_login_blocked($_POST['reg_alias'])) {
                set_err('reg_alias', 'This alias is not allowed');
            }
            if (q_singleval('SELECT id FROM fud30_users WHERE alias=' . _esc(make_alias($_POST['reg_alias'])) . ' AND id!=' . $user_id)) {
                set_err('reg_alias', 'The alias you are trying to use is already in use by another forum member. Please choose another.');
            }
        }
    }

    /* Signature checks. */
    if (isset($_POST['reg_sig'])) {
        /* Check signature length. */
        if ($GLOBALS['FORUM_SIG_ML'] && strlen($_POST['reg_sig']) > $GLOBALS['FORUM_SIG_ML']) {
            set_err('reg_sig', 'Your signature exceeds the maximum allowed length of ' . $GLOBALS['FORUM_SIG_ML'] . ' characters.');
        }

        /* Check if user is allowed to post links in signature. */
        if (preg_match('?(\[url)|(http://)|(https://)?i', $_POST['reg_sig'])) {
            if ($GLOBALS['POSTS_BEFORE_LINKS'] > 0) {
                $c = q_singleval('SELECT posted_msg_count FROM fud30_users WHERE id=' . _uid);
                if ($GLOBALS['POSTS_BEFORE_LINKS'] > $c) {
                    $posts_before_links = $GLOBALS['POSTS_BEFORE_LINKS'];
                    set_err('reg_sig', 'You cannot use links until you have posted more than ' . convertPlural($posts_before_links, array('' . $posts_before_links . ' message', '' . $posts_before_links . ' messages')) . '.');
                }
            }
        }
    }

    /* Check if user is allowed to post a home_page link. */
    if (preg_match('?(\[url)|(http://)|(https://)?i', $_POST['reg_home_page'])) {
        if (1 /* $GLOBALS['POSTS_BEFORE_LINKS'] > 0 */) {
            $c = q_singleval('SELECT posted_msg_count FROM fud30_users WHERE id=' . _uid);
            if ($GLOBALS['POSTS_BEFORE_LINKS'] > $c) {
                $posts_before_links = $GLOBALS['POSTS_BEFORE_LINKS'];
                set_err('reg_home_page', 'You cannot use links until you have posted more than ' . convertPlural($posts_before_links, array('' . $posts_before_links . ' message', '' . $posts_before_links . ' messages')) . '.');
            }
        }
    }

    // Check if custom field values are OK.
    validate_custom_fields();

    return $GLOBALS['error'];
}

function fmt_year($val)
{
    if (!$val) {
        return '0000';
    }
    if ($val > 1000) {
        return $val;
    } else if ($val < 100 && $val > 10) {
        return (1900 + $val);
    } else if ($val < 10) {
        return (2000 + $val);
    }
}

function set_err($err_name, $err_msg)
{
    $GLOBALS['error'] = 1;
    if (isset($GLOBALS['err_msg'])) {
        $GLOBALS['err_msg'][$err_name] = $err_msg;
    } else {
        $GLOBALS['err_msg'] = array($err_name => $err_msg);
    }
}

function draw_err($err_name)
{
    if (!isset($GLOBALS['err_msg'][$err_name])) {
        return;
    }
    return '<br /><span class="ErrorText">' . $GLOBALS['err_msg'][$err_name] . '</span>';
}

function make_avatar_loc($path, $disk, $web)
{
    $img_info = @getimagesize($disk . $path);

    if ($img_info[2] < 4 && $img_info[2] > 0) {
        return '<img src="' . $web . $path . '" alt="" ' . $img_info[3] . ' />';
    } else if ($img_info[2] == 4) {
        return '<embed src="' . $web . $path . '" ' . $img_info[3] . ' />';
    } else {
        return '';
    }
}

function remove_old_avatar($avatar_str)
{
    if (preg_match('!images/custom_avatars/(([0-9]+)\.([A-Za-z]+))" width=!', $avatar_str, $tmp)) {
        @unlink($GLOBALS['WWW_ROOT_DISK'] . 'images/custom_avatars/' . basename($tmp[1]));
    }
}

function decode_uent(&$uent)
{
    $uent->home_page = reverse_fmt($uent->home_page);
    $uent->user_image = reverse_fmt($uent->user_image);
    $uent->jabber = reverse_fmt($uent->jabber);
    $uent->aim = urldecode($uent->aim);
    $uent->yahoo = urldecode($uent->yahoo);
    $uent->msnm = urldecode($uent->msnm);
    $uent->google = urldecode($uent->google);
    $uent->skype = urldecode($uent->skype);
    $uent->twitter = urldecode($uent->twitter);
}

function email_encode($val)
{
    return str_replace(array('@', '.'), array('&#64;', '&#46;'), htmlspecialchars($val));
}

/* main */
if (!__fud_real_user__ && !($FUD_OPT_1 & 2)) {
    std_error('registration_disabled');
}

if (!__fud_real_user__ && !isset($_POST['reg_coppa']) && !isset($_GET['reg_coppa'])) {
    if ($FUD_OPT_1 & 1048576) {
        if ($FUD_OPT_2 & 32768) {
            header('Location: /index.php/cp/' . _rsidl);
        } else {
            header('Location: /index.php?t=coppa&' . _rsidl);
        }
    } else {
        if ($FUD_OPT_2 & 32768) {
            header('Location: /index.php/pr/0/' . _rsidl);
        } else {
            header('Location: /index.php?t=pre_reg&' . _rsidl);
        }
    }
    exit;
}

if (isset($_GET['mod_id'])) {
    $mod_id = (int)$_GET['mod_id'];
} else if (isset($_POST['mod_id'])) {
    $mod_id = (int)$_POST['mod_id'];
} else {
    $mod_id = '';
}

if (isset($_GET['reg_coppa'])) {
    $reg_coppa = (int)$_GET['reg_coppa'];
} else if (isset($_POST['mod_id'])) {
    $reg_coppa = (int)$_POST['reg_coppa'];
} else {
    $reg_coppa = '';
}

/* IP filter. */
if (is_ip_blocked(get_ip())) {
    invl_inp_err();
}

/* Allow the root to modify settings of other users. */
if (_uid && $is_a && $mod_id) {
    if (!($uent = usr_reg_get_full($mod_id))) {
        exit('Invalid User Id.');
    }
    decode_uent($uent);
} else {
    if (__fud_real_user__) {
        $uent = usr_reg_get_full($usr->id);
        decode_uent($uent);
    } else {
        $uent = new fud_user_reg;
        $uent->id = 0;
        $uent->users_opt = 4488183;
        $uent->topics_per_page = $THREADS_PER_PAGE_F;
    }
}

$reg_avatar_loc_file = $avatar_tmp = $avatar_arr = null;
/* Deal with avatars, only done for regged users. */
if (_uid) {
    if (!empty($_POST['avatar_tmp']) && is_string($_POST['avatar_tmp'])) {
        $tmp = explode("\n", base64_decode($_POST['avatar_tmp']));
        if (count($tmp) == 3) {
            list($avatar_arr['file'], $avatar_arr['del'], $avatar_arr['leave']) = $tmp;
        }
    }
    if (isset($_POST['btn_detach'], $avatar_arr)) {
        $avatar_arr['del'] = 1;
    }
    if (!($FUD_OPT_1 & 8) && (!@file_exists($avatar_arr['file']) || empty($avatar_arr['leave']))) {
        /* Hack attempt for URL avatar. */
        $avatar_arr = null;
    } else if (($FUD_OPT_1 & 8) && isset($_FILES['avatar_upload']) && $_FILES['avatar_upload']['size'] > 0) { /* New upload. */
        if ($_FILES['avatar_upload']['size'] >= $CUSTOM_AVATAR_MAX_SIZE) {
            set_err('avatar', 'The file you are trying to upload is too big, more than ' . $GLOBALS['CUSTOM_AVATAR_MAX_SIZE'] . ' bytes.');
        } else {
            $ext = array(1 => 'gif', 2 => 'jpg', 3 => 'png', 4 => 'swf');
            if (!($img_info = @getimagesize($_FILES['avatar_upload']['tmp_name']))) {
                set_err('avatar', 'The specified URL does not contain a valid image.');
            }
            /* [user_id].[file_extension]_'random data' */
            define('real_avatar_name', $uent->id . '.' . $ext[$img_info[2]]);
            if (move_uploaded_file($_FILES['avatar_upload']['tmp_name'], ($tmp_name = tempnam($GLOBALS['TMP'], 'av_')))) {
                $tmp_name = basename($tmp_name);
            } else {
                $tmp_name = null;
            }

            list($max_w, $max_y) = explode('x', $CUSTOM_AVATAR_MAX_DIM);
            if ($img_info[2] > ($FUD_OPT_1 & 64 ? 4 : 3)) {
                set_err('avatar', 'The avatar you are trying to upload is not allowed. Check the allowed file types.');
                unlink($TMP . $tmp_name);
            } else if ($img_info[0] > $max_w || $img_info[1] > $max_y) {
                set_err('avatar', 'Avatar dimensions of <b>(' . $img_info[0] . 'x' . $img_info[1] . ')</b> exceed the allowed dimensions of <b>(' . $GLOBALS['CUSTOM_AVATAR_MAX_DIM'] . ')</b> pixels.');
                unlink($TMP . $tmp_name);
            } else {
                /* Remove old uploaded file, if one exists & is not in DB. */
                if (empty($avatar_arr['leave']) && @file_exists($avatar_arr['file'])) {
                    @unlink($TMP . $avatar_arr['file']);
                }

                $avatar_arr['file'] = $tmp_name;
                $avatar_arr['del'] = 0;
                $avatar_arr['leave'] = 0;
            }
        }
    }
}

if ($GLOBALS['is_post']) {
    $new_users_opt = 0;
    foreach (array('display_email', 'notify', 'notify_method', 'ignore_admin', 'email_messages', 'pm_messages', 'pm_notify', 'default_view', 'gender', 'append_sig', 'show_sigs', 'show_avatars', 'show_im', 'invisible_mode') as $v) {
        if (!empty($_POST['reg_' . $v])) {
            $new_users_opt |= (int)$_POST['reg_' . $v];
        }
    }

    /* Security check, prevent haxors from passing values that shouldn't. */
    if (!($new_users_opt & (131072 | 65536 | 262144 | 524288 | 1048576 | 2097152 | 4194304 | 8388608 | 16777216 | 33554432 | 67108864 | 268435456 | 536870912))) {
        // We're OK, no admin options inputted, allow existing valid admin options.
        $uent->users_opt = ($uent->users_opt & (131072 | 65536 | 262144 | 524288 | 1048576 | 2097152 | 4194304 | 8388608 | 16777216 | 33554432 | 67108864 | 268435456 | 536870912)) | $new_users_opt;
    }
}

/* SUBMITTION CODE */
if (isset($_POST['fud_submit']) && !isset($_POST['btn_detach']) && !isset($_POST['btn_upload']) && !register_form_check($uent->id)) {

    $old_email = $uent->email;
    $old_avatar_loc = $uent->avatar_loc;
    $old_avatar = $uent->avatar;

    if (!($FUD_OPT_1 & 32768)) {
        unset($_POST['reg_sig']);
    }

    /* Import data from _POST into $uent object. */
    foreach (array_keys(get_class_vars('fud_user')) as $v) {
        if (isset($_POST['reg_' . $v])) {
            $uent->{$v} = $_POST['reg_' . $v];
        }
    }

    /* Only one theme available, so no select. */
    if (!$uent->theme) {
        $uent->theme = q_singleval(q_limit('SELECT id FROM fud30_themes WHERE theme_opt>=2 AND ' . q_bitand('theme_opt', 2) . ' > 0', 1));
    }

    $uent->birthday = sprintf('%02d%02d', (int)$_POST['b_month'], (int)$_POST['b_day']) . fmt_year((int)$_POST['b_year']);
    if ($uent->birthday == '00000000') {
        $uent->birthday = '';
    }

    $uent->msnm = email_encode($uent->msnm);
    $uent->google = email_encode($uent->google);

    if ($FUD_OPT_1 & 32768 && $uent->sig) {
        $uent->sig = apply_custom_replace($uent->sig);
        if ($FUD_OPT_1 & 131072) {
            $uent->sig = tags_to_html($uent->sig, $FUD_OPT_1 & 524288);
        } else if ($FUD_OPT_1 & 65536) {
            $uent->sig = nl2br(htmlspecialchars($uent->sig));
        }

        if ($FUD_OPT_1 & 196608) {
            $uent->sig = char_fix($uent->sig);
        }

        if ($FUD_OPT_1 & 262144) {
            $uent->sig = smiley_to_post($uent->sig);
        }
        fud_wordwrap($uent->sig);
    }

    // Round-up and serialize all custom field values.
    $uent->custom_fields = serialize_custom_fields();

    if (!__fud_real_user__) { /* new user */
        /* New users do not have avatars. */
        $uent->users_opt |= 4194304;

        /* Handle coppa passed to us by pre_reg form. */
        if (!(int)$_POST['reg_coppa']) {
            $uent->users_opt ^= 262144;
        }

        /* Make the account un-validated, if admin wants to approve accounts manually. */
        if ($FUD_OPT_2 & 1024) {
            $uent->users_opt |= 2097152;
        }

        // Pre-registration plugins.
        if (defined('plugins')) {
            $uent = plugin_call_hook('PRE_REGISTER', $uent);
        }

        $uent->add_user();

        // Post-registration plugins.
        if (defined('plugins')) {
            $uent = plugin_call_hook('POST_REGISTER', $uent);
        }

        if ($FUD_OPT_2 & 1) {
            send_email($NOTIFY_FROM, $uent->email, 'Registration Confirmation', 'Thank you for registering,\nTo activate your account please go to the URL below:\n\nhttps://forum.wigedev.com/index.php?t=emailconf&conf_key=' . $uent->conf_key . '\n\nOnce your account is activated you will be logged-into the forum and\nredirected to the main page.', '');
        } else if (!($FUD_OPT_3 & 2048)) {
            send_email($NOTIFY_FROM, $uent->email, 'Forum Registration Confirmation', 'Thank you for registering,\n\nHere are your login details for the forum:\n\nForum URL: /index.php?t=index\nLogin: ' . $uent->login . '\nPassword: ' . $_POST['reg_plaintext_passwd'] . '\n\nPlease note that passwords are case sensitive!\nTo modify your settings or profile, go to this page:\nhttps://forum.wigedev.com/index.php?t=register\n', '');
        }

        /* We notify all admins about the new user, so that they can approve him. */
        if (($FUD_OPT_2 & 132096) == 132096) {
            $admins = db_all('SELECT email FROM fud30_users WHERE users_opt>=1048576 AND ' . q_bitand('users_opt', 1048576) . ' > 0');
            send_email($NOTIFY_FROM, $admins, 'A new user has registered and their account is pending confirmation', 'A new user (' . $uent->login . ') has just registered, and since account confirmation is enabled, their account will not become active until it is confirmed by you or another forum administrator. To review the account please go to: /adm/admuserapr.php\n\nThis is an automated process. Do not reply to this message.\nIf you want to turn off future e-mail notifications of new user registrations, you can do so via the Admin Control Panel. Change the "New Account Notification" setting.', '');
        }

        /* Login the new user into the forum. */
        user_login($uent->id, $usr->ses_id, 1);

        if ($FUD_OPT_1 & 1048576 && $uent->users_opt & 262144) {
            if ($FUD_OPT_2 & 32768) {
                header('Location: /index.php/cpf/' . _rsidl);
            } else {
                header('Location: /index.php?t=coppa_fax&' . _rsidl);
            }
            exit;
        } else if (!($uent->users_opt & 131072) || $FUD_OPT_2 & 1024) {
            header('Location: /index.php' . ($FUD_OPT_2 & 32768 ? '/rc/' : '?t=reg_conf&') . _rsidl);
            exit;
        }

        check_return($usr->returnto);
    } else if ($uent->id) { /* Updating a user. */
        /* Restore avatar values to their previous values. */
        $uent->avatar = $old_avatar;
        $uent->avatar_loc = $old_avatar_loc;
        $old_opt = $uent->users_opt & (4194304 | 16777216 | 8388608);
        $uent->users_opt |= 4194304 | 16777216 | 8388608;

        /* Prevent non-confirmed users from playing with avatars, yes we are that cruel. */
        if ($FUD_OPT_1 & 28 && _uid) {
            if ($_POST['avatar_type'] == 'b') { /* built-in avatar */
                if (!$old_avatar && $old_avatar_loc) {
                    remove_old_avatar($old_avatar_loc);
                    $uent->avatar_loc = '';
                } else if (isset($avatar_arr['file'])) {
                    @unlink($TMP . basename($avatar_arr['file']));
                }
                if ($_POST['reg_avatar'] == '0') {
                    $uent->avatar_loc = '';
                    $uent->avatar = 0;
                } else if ($uent->avatar != $_POST['reg_avatar'] && ($img = q_singleval('SELECT img FROM fud30_avatar WHERE id=' . (int)$_POST['reg_avatar']))) {
                    /* verify that the avatar exists and it is different from the one in DB */
                    $uent->avatar_loc = make_avatar_loc('images/avatars/' . $img, $WWW_ROOT_DISK, '/');
                    $uent->avatar = $_POST['reg_avatar'];
                }
                if ($uent->avatar && $uent->avatar_loc) {
                    $uent->users_opt ^= 4194304 | 16777216;
                }
            } else {
                if ($_POST['avatar_type'] == 'c' && $reg_avatar_loc_file) { /* New URL avatar */
                    $common_av_name = $reg_avatar_loc_file;

                    if (!empty($avatar_arr['file'])) {
                        $avatar_arr['del'] = 1;
                    }
                } else if ($_POST['avatar_type'] == 'u' && empty($avatar_arr['del']) && empty($avatar_arr['leave'])) { /* uploaded file */
                    $common_av_name = $avatar_arr['file'];
                } else {
                    $common_av_name = '';
                }

                /* Remove old avatar if need be. */
                if (!empty($avatar_arr['del'])) {
                    if (empty($avatar_arr['leave'])) {
                        @unlink($TMP . basename($avatar_arr['file']));
                    } else {
                        remove_old_avatar($old_avatar_loc);
                    }
                }

                /* Add new avatar if needed. */
                if ($common_av_name) {
                    if (defined('real_avatar_name')) {
                        $av_path = 'images/custom_avatars/' . real_avatar_name;
                    } else {
                        $common_av_name = basename($common_av_name);
                        $ext = array(1 => 'gif', 2 => 'jpg', 3 => 'png', 4 => 'swf');
                        $img_info = getimagesize($TMP . $common_av_name);
                    }
                    $av_path = 'images/custom_avatars/' . $uent->id . '.' . $ext[$img_info[2]];

                    copy($TMP . $common_av_name, $WWW_ROOT_DISK . $av_path);
                    @unlink($TMP . $common_av_name);
                    if (($uent->avatar_loc = make_avatar_loc($av_path, $WWW_ROOT_DISK, '/'))) {
                        if (!($FUD_OPT_1 & 32) || $uent->users_opt & 1048576) {
                            $uent->users_opt ^= 16777216 | 4194304;
                        } else {
                            $uent->users_opt ^= 8388608 | 4194304;
                        }
                    }
                } else if (empty($avatar_arr['leave']) || !empty($avatar_arr['del'])) {
                    $uent->avatar_loc = '';
                } else if (!empty($avatar_arr['leave'])) {
                    $uent->users_opt ^= (8388608 | 16777216 | 4194304) ^ $old_opt;
                }
                $uent->avatar = 0;
            }
            if (empty($uent->avatar_loc)) {
                $uent->users_opt ^= 8388608 | 16777216;
            }
        } else {
            $uent->users_opt ^= (8388608 | 16777216 | 4194304) ^ $old_opt;
        }

        $uent->sync();

        /* If the user had changed their e-mail, force them re-confirm their account (unless admin). */
        if ($FUD_OPT_2 & 1 && $old_email && $old_email != $uent->email && !($uent->users_opt & 1048576)) {
            $conf_key = usr_email_unconfirm($uent->id);
            send_email($NOTIFY_FROM, $uent->email, 'E-mail change confirmation', 'Please confirm your new e-mail account "' . $uent->email . '" that replaces your old e-mail account "' . $old_email . '", by going to the URL below:\nhttps://forum.wigedev.com/index.php?t=emailconf&conf_key=' . $conf_key . '\n\nOnce you confirm your new e-mail address, your forum account will be re-activated.', '');
        }
        if (!$mod_id) {
            check_return($usr->returnto);
        } else {
            if ($FUD_OPT_2 & 32768) {
                header('Location: /index.phpadm/admuser.php?usr_id=' . $uent->id . '&' . str_replace(array(s, '/?'), array('S=' . s, '&'), _rsidl) . '&act=nada');
            } else {
                header('Location: /index.phpadm/admuser.php?usr_id=' . $uent->id . '&' . _rsidl . '&act=nada');
            }
            exit;
        }
    } else {
        error_dialog('ERROR: unable to register', 'Unable to create user account, please contact the administrator at <a href="mailto:' . $ADMIN_EMAIL . '">' . $ADMIN_EMAIL . '</a>');
    }
}

$avatar_type = '';
$chr_fix = array('reg_sig', 'reg_name', 'reg_bio', 'reg_location', 'reg_occupation', 'reg_interests', 'reg_msnm', 'reg_google');
if ($FUD_OPT_2 & 128) {
    $chr_fix[] = 'reg_alias';
}
if (!__fud_real_user__) {
    $chr_fix[] = 'reg_login';
} else {
    $reg_login = char_fix(htmlspecialchars($uent->login));
}

/* Populate form variables based on user's profile. */
if (__fud_real_user__ && !isset($_POST['prev_loaded'])) {
    foreach ($uent as $k => $v) {
        ${'reg_' . $k} = htmlspecialchars($v);
    }
    foreach ($chr_fix as $v) {
        $$v = char_fix(reverse_fmt($$v));
    }

    $reg_sig = apply_reverse_replace($reg_sig);

    if ($FUD_OPT_1 & 262144) {
        $reg_sig = post_to_smiley($reg_sig);
    }

    if ($FUD_OPT_1 & 131072) {
        $reg_sig = html_to_tags($reg_sig);
    } else if ($FUD_OPT_1 & 65536) {
        $reg_sig = reverse_nl2br($reg_sig);
    }

    if ($FUD_OPT_1 & 196608) {
        $reg_sig = char_fix($reg_sig);
    }

    if ($uent->birthday) {
        $b_year = (int)substr($uent->birthday, 4);
        if ($b_year == 0) {
            $b_year = '';
        }
        $b_month = substr($uent->birthday, 0, 2);
        $b_day = substr($uent->birthday, 2, 2);
    } else {
        $b_year = $b_month = $b_day = '';
    }
    if (!$reg_avatar && $reg_avatar_loc) { /* Custom avatar. */
        if (preg_match('!src="([^"]+)"!', reverse_fmt($reg_avatar_loc), $tmp)) {
            $avatar_arr['file'] = $tmp[1];
            $avatar_arr['del'] = 0;
            $avatar_arr['leave'] = 1;
            $avatar_type = 'u';
        }
    }
} else if (isset($_POST['prev_loaded'])) { /* Import data from POST data. */
    foreach ($_POST as $k => $v) {
        if (!strncmp($k, 'reg_', 4)) {
            ${$k} = htmlspecialchars((string)$v);
        }
    }
    foreach ($chr_fix as $v) {
        $$v = isset($_POST[$v]) ? char_fix($$v) : '';
    }

    foreach (array('b_year', 'b_month', 'b_day', 'reg_theme', 'reg_posts_ppg') as $v) {
        $$v = isset($_POST[$v]) ? (int)$_POST[$v] : 0;
    }

    if (isset($_POST['avatar_type'])) {
        $avatar_type = $_POST['avatar_type'];
    }
    if (!isset($_POST['reg_time_zone'])) {
        $reg_time_zone = $SERVER_TZ;
    }
}

/* When we need to create a new user, define default values for various options. */
if (!__fud_real_user__ && !isset($_POST['prev_loaded'])) {
    foreach (array_keys(get_object_vars($uent)) as $v) {
        ${'reg_' . $v} = '';
    }

    $uent->users_opt = 4488182;
    if (!($FUD_OPT_2 & 4)) {
        $uent->users_opt ^= 128;
    }
    if (!($FUD_OPT_2 & 8)) {
        $uent->users_opt ^= 256;
    }

    $b_year = $b_month = $b_day = '';
    $reg_time_zone = $SERVER_TZ;
}

if (!$mod_id) {
    if (__fud_real_user__) {
        ses_update_status($usr->sid, 'Viewing own profile', 0, 0);
    } else {
        ses_update_status($usr->sid, 'Registration Page', 0, 0);
    }
}

$TITLE_EXTRA = ': Register Form';

/* Print number of unread private messages in User Control Panel. */
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
}/* Read custom field definitions from the DB. */
function get_custom_field_defs()
{
    require $GLOBALS['FORUM_SETTINGS_PATH'] . 'custom_field_cache';
    return $custom_field_cache;
}

/* Validate custom field values entered by users. */
function validate_custom_fields()
{
    foreach (get_custom_field_defs() as $k => $r) {
        // Call CUSTOM_FIELD_VALIDATE plugins.
        if (defined('plugins')) {
            $err = null;
            list($err) = plugin_call_hook('CUSTOM_FIELD_VALIDATE', array($err, $k, $r['name'], $_POST['custom_field_' . $k]));
            if ($err) {
                set_err('custom_field_' . $k, $err);
            }
        }

        // Check if all required custom fields have values.
        if (($r['field_opt'] & 1) && empty($_POST['custom_field_' . $k])) {    // 1==required.
            set_err('custom_field_' . $k, 'This field is required.');
        }

        // Validate/sanitize user input.
        $_POST['custom_field_' . $k] = filter_var($_POST['custom_field_' . $k], FILTER_SANITIZE_STRING);
    }
}

/* Serialize custom field values for storage. */
function serialize_custom_fields()
{
    $custom_field_vals = null;
    foreach (get_custom_field_defs() as $k => $r) {
        if (!empty($_POST['custom_field_' . $k])) {
            $custom_field_vals[$k] = $_POST['custom_field_' . $k];
        }
    }
    return serialize($custom_field_vals);
}

/* main */
// Unserialize custom fields to set display values.
$custom_field_vals = unserialize($uent->custom_fields);

// Setup custom fields for display.
$required_custom_fields = $optional_custom_fields = '';
foreach (get_custom_field_defs() as $k => $r) {
    $r['choice'] = preg_replace("/\r\n/", "\n", $r['choice']);    // Strip Windows newlines.
    $custom_field_vals[$k] = empty($custom_field_vals[$k]) ? '' : $custom_field_vals[$k];

    // Can field be edited.
    $disabled = ((($r['field_opt'] & 8) && !$is_a) || $r['field_opt'] & 16) ? 'disabled="disabled"' : '';

    // Expand required (children of c1) and collapse optional (children of c2) fields
    if ($r['field_opt'] & 1) {
        $tr = 'class="child-c1 RowStyleA"';
    } else {
        $tr = 'class="child-c2 RowStyleA" style="display: none;"';
    }

    if ($r['type_opt'] & 1) {    // # 1 == Textarea.
        $val = empty($custom_field_vals[$k]) ? $r['choice'] : $custom_field_vals[$k];
        $custom_field = '<tr ' . $tr . '>
	<td valign="top">
		' . $r['name'] . draw_err('custom_field_' . $k) . '
		<br /><span class="SmallText">' . $r['descr'] . '</span>
	</td>
	<td>
		<textarea name="custom_field_' . $k . '" rows="5" cols="50" ' . $disabled . '>' . $val . '</textarea>
	</td>
</tr>';
    } else if ($r['type_opt'] & 2) {    // # 2 == Select drop down.
        $custom_field_select = tmpl_draw_select_opt($r['choice'], $r['choice'], $custom_field_vals[$k]);
        $custom_field = '<tr ' . $tr . '>
	<td>
		' . $r['name'] . draw_err('custom_field_' . $k) . '
		<br /><span class="SmallText">' . $r['descr'] . '</span>
	</td>
	<td>
		<select name="custom_field_' . $k . '" ' . $disabled . '>' . $custom_field_select . '</select>
	</td>
</tr>';
    } else if ($r['type_opt'] & 4) {    // # 4 == Radio buttons.
        $custom_field_radio = tmpl_draw_radio_opt('custom_field_' . $k, $r['choice'], $r['choice'], $custom_field_vals[$k], '&nbsp;&nbsp;');
        $custom_field = '<tr ' . $tr . '>
	<td>
		' . $r['name'] . draw_err('custom_field_' . $k) . '
		<br />
		<span class="SmallText">' . $r['descr'] . '</span>
	</td>
	<td>
		' . $custom_field_radio . '
	</td>
</tr>';
    } else {    // # 0 == Single line.
        $val = empty($custom_field_vals[$k]) ? $r['choice'] : $custom_field_vals[$k];
        $custom_field = '<tr ' . $tr . '>
	<td>
		' . $r['name'] . draw_err('custom_field_' . $k) . '
		<br />
		<span class="SmallText">' . $r['descr'] . '</span>
	</td>
	<td>
		<input type="text" name="custom_field_' . $k . '" value="' . $val . '" maxlength="255" size="30" ' . $disabled . ' />
	</td>
</tr>';
    }

    if ($r['field_opt'] & 1) {
        $required_custom_fields .= $custom_field;
    } else {
        $optional_custom_fields .= $custom_field;
    }
}

/* Initialize avatar options. */
$avatar = $avatar_type_sel = '';

if (__fud_real_user__) {
    if ($uent->users_opt & 131072 && $FUD_OPT_2 & 1 && !($uent->users_opt & 1048576)) {
        $email_warning_msg = '<br /><span class="regEW">If you change your current e-mail address, your account will be marked unconfirmed until you confirm it via e-mail.</span>';
    } else {
        $email_warning_msg = '';
    }

    if ($FUD_OPT_1 & 28 && _uid) {
        if ($FUD_OPT_1 == 28) {
            /* If there are no built-in avatars, don't show them. */
            if (q_singleval('SELECT count(*) FROM fud30_avatar')) {
                $sel_opt = "Built In\nSpecify URL\nUpload Avatar";
                $a_type = 'b';
                $sel_val = "b\nc\nu";
            } else {
                $sel_opt = "Specify URL\nUpload Avatar";
                $a_type = 'u';
                $sel_val = "c\nu";
            }
        } else {
            $a_type = $sel_opt = $sel_val = '';

            if (q_singleval('SELECT count(*) FROM fud30_avatar') && $FUD_OPT_1 & 16) {
                $sel_opt .= "Built In\n";
                $a_type = 'b';
                $sel_val .= "b\n";
            }
            if ($FUD_OPT_1 & 8) {
                $sel_opt .= "Upload Avatar\n";
                if (!$a_type) {
                    $a_type = 'u';
                }
                $sel_val .= "u\n";
            }
            if ($FUD_OPT_1 & 4) {
                $sel_opt .= "Specify URL\n";
                if (!$a_type) {
                    $a_type = 'c';
                }
                $sel_val .= "c\n";
            }
            $sel_opt = trim($sel_opt);
            $sel_val = trim($sel_val);
        }

        if ($a_type) { /* Rare condition, no built-in avatars & no other avatars are allowed. */
            if (!$avatar_type) {
                $avatar_type = $a_type;
            }
            $avatar_type_sel_options = tmpl_draw_select_opt($sel_val, $sel_opt, $avatar_type);
            $avatar_type_sel = '<tr class="child-c2 vt RowStyleA" style="display: none;">
	<td>Avatar Type:</td>
	<td><select name="avatar_type" onchange="document.forms[\'fud_register\'].submit();">' . $avatar_type_sel_options . '</select></td>
</tr>';

            /* Preview image. */
            if (isset($_POST['prev_loaded'])) {
                if ((!empty($_POST['reg_avatar']) && $_POST['reg_avatar'] == $uent->avatar) || (!empty($avatar_arr['file']) && empty($avatar_arr['del']) && $avatar_arr['leave'])) {
                    $custom_avatar_preview = $uent->avatar_loc;
                } else if (!empty($_POST['reg_avatar']) && ($im = q_singleval('SELECT img FROM fud30_avatar WHERE id=' . (int)$_POST['reg_avatar']))) {
                    $custom_avatar_preview = make_avatar_loc('images/avatars/' . $im, $WWW_ROOT_DISK, '/');
                } else {
                    if ($reg_avatar_loc_file) {
                        $common_name = $reg_avatar_loc_file;
                    } else if (!empty($avatar_arr['file']) && empty($avatar_arr['del'])) {
                        $common_name = $avatar_arr['file'];
                    } else {
                        $common_name = '';
                    }
                    $custom_avatar_preview = $common_name ? make_avatar_loc(basename($common_name), $TMP, '/?t=tmp_view&img=') : '';
                }
            } else if ($uent->avatar_loc) {
                $custom_avatar_preview = $uent->avatar_loc;
            } else {
                $custom_avatar_preview = '';
            }

            if (!$custom_avatar_preview) {
                $custom_avatar_preview = '<img src="blank.gif" alt="" />';
            }

            /* Determine the avatar specification field to show. */
            if ($avatar_type == 'b') {
                if (empty($reg_avatar)) {
                    $reg_avatar = '0';
                    $reg_avatar_img = 'blank.gif';
                } else if (!empty($reg_avatar_loc)) {
                    preg_match('!images/avatars/([^"]+)"!', reverse_fmt($reg_avatar_loc), $tmp);
                    $reg_avatar_img = 'images/avatars/' . $tmp[1];
                } else {
                    $reg_avatar_img = 'images/avatars/' . q_singleval('SELECT img FROM fud30_avatar WHERE id=' . (int)$reg_avatar);
                }
                $del_built_in_avatar = $reg_avatar ? '[<a href="javascript://" onclick="document.reg_avatar_img.src=\'/blank.gif\'; document.forms[\'fud_register\'].reg_avatar.value=\'0\';">Delete Avatar</a>]' : '';
                $avatar = '<tr class="child-c2 vt RowStyleA" style="display: none;">
	<td>Avatar:</td>
	<td>
		<img src="' . $reg_avatar_img . '" name="reg_avatar_img" alt="" />
		<input type="hidden" name="reg_avatar" value="' . $reg_avatar . '" />[<a href="javascript: window_open(\'/index.php?t=avatarsel&amp;' . _rsid . '\', \'avtsel\', 400, 300);">Select Avatar</a>]
		' . $del_built_in_avatar . '<br />
	</td>
</tr>';
            } else if ($avatar_type == 'c') {
                if (!isset($reg_avatar_loc)) {
                    $reg_avatar_loc = '';
                }
                $avatar = '<tr class="child-c2 vt RowStyleC" style="display: none;">
	<td colspan="2">The custom avatar will not appear until it is approved by the administrator.<br /><span class="SmallText">The avatar, should be no larger than <b>' . $GLOBALS['CUSTOM_AVATAR_MAX_DIM'] . ' pixels</b> and must be in <b>jpg</b>, <b>gif</b> or <b>png</b> format.</span></td>
</tr>
<tr class="child-c2 vt RowStyleA" style="display: none;">
	<td>Custom Avatar URL: ' . draw_err('avatar') . '</td>
	<td><input type="text" value="' . $reg_avatar_loc . '" name="reg_avatar_loc" /></td>
</tr>';
            } else if ($avatar_type == 'u') {
                $avatar_tmp = $avatar_arr ? base64_encode($avatar_arr['file'] . "\n" . $avatar_arr['del'] . "\n" . $avatar_arr['leave']) : '';
                $buttons = (!empty($avatar_arr['file']) && empty($avatar_arr['del'])) ? '&nbsp;<input type="submit" class="button" name="btn_detach" value="Delete Avatar" />' : '<input type="file" name="avatar_upload" />
<input type="submit" class="button" name="btn_upload" value="Preview" />
<input type="hidden" name="tmp_f_val" value="1" />';
                $avatar = '<tr class="child-c2 vt RowStyleC" style="display: none;">
	<td colspan="2">The custom avatar will not appear until it is approved by the administrator.<br /><span class="SmallText">The avatar, should be no larger than <b>' . $GLOBALS['CUSTOM_AVATAR_MAX_DIM'] . ' pixels</b> and must be in <b>jpg</b>, <b>gif</b> or <b>png</b> format.</span></td>
</tr>
<tr class="child-c2 vt RowStyleA" style="display: none;">
	<td>Custom Avatar File: ' . draw_err('avatar') . '</td>
	<td>
		<table border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td>' . $custom_avatar_preview . '</td>
			<td>' . $buttons . '<input type="hidden" name="avatar_tmp" value="' . $avatar_tmp . '" /></td>
		</tr>
		</table>
	</td>
</tr>';
            }
        }
    }
}

$theme_select = '';
$r = uq('SELECT id, name FROM fud30_themes WHERE theme_opt>=1 AND ' . q_bitand('theme_opt', 1) . ' > 0 ORDER BY ' . q_bitand('theme_opt', 2) . ' DESC, name');
/* Only display theme select if there is >1 theme. */
while ($t = db_rowarr($r)) {
    $theme_select .= '<option value="' . $t[0] . '"' . ($t[0] == $reg_theme ? ' selected="selected"' : '') . '>' . $t[1] . '</option>';
}
unset($r);

$views[384] = 'Flat View message and topic listing';
if (!($FUD_OPT_3 & 2)) {
    $views[128] = 'Flat topic listing/Tree message listing';
}
if ($FUD_OPT_2 & 512) {
    $views[256] = 'Tree topic listing/Flat message listing';
    if (!($FUD_OPT_3 & 2)) {
        $views[0] = 'Tree View message and topic listing';
    }
}

$day_select = tmpl_draw_select_opt("\n1\n2\n3\n4\n5\n6\n7\n8\n9\n10\n11\n12\n13\n14\n15\n16\n17\n18\n19\n20\n21\n22\n23\n24\n25\n26\n27\n28\n29\n30\n31", "\n1\n2\n3\n4\n5\n6\n7\n8\n9\n10\n11\n12\n13\n14\n15\n16\n17\n18\n19\n20\n21\n22\n23\n24\n25\n26\n27\n28\n29\n30\n31", $b_day);
$month_select = tmpl_draw_select_opt("\n1\n2\n3\n4\n5\n6\n7\n8\n9\n10\n11\n12", "\nJanuary\nFebruary\nMarch\nApril\nMay\nJune\nJuly\nAugust\nSeptember\nOctober\nNovember\nDecember", $b_month);
$gender_select = tmpl_draw_select_opt("512\n1024\n0", "UNSPECIFIED\nMale\nFemale", ($uent->users_opt & 512 ? 512 : ($uent->users_opt & 1024)));
$mppg_select = tmpl_draw_select_opt("0\n5\n10\n20\n30\n40", "Use forum default\n5\n10\n20\n30\n40", $reg_posts_ppg);
$view_select = tmpl_draw_select_opt(implode("\n", array_keys($views)), implode("\n", $views), (($uent->users_opt & 128) | ($uent->users_opt & 256)));

$vals = implode("\n", timezone_identifiers_list());
$timezone_select = tmpl_draw_select_opt($vals, $vals, $reg_time_zone);

$notification_select = tmpl_draw_select_opt("4\n134217728", 'E-mail' . "\n" . 'Don&#39;t notify me', ($uent->users_opt & (4 | 134217728)));

$vals = implode("\n", range(5, $THREADS_PER_PAGE_F));
$topics_per_page = tmpl_draw_select_opt($vals, $vals, $uent->topics_per_page);

$ignore_admin_radio = tmpl_draw_radio_opt('reg_ignore_admin', "8\n0", "Yes\nNo", ($uent->users_opt & 8), '&nbsp;&nbsp;');
$invisible_mode_radio = tmpl_draw_radio_opt('reg_invisible_mode', "32768\n0", "Yes\nNo", ($uent->users_opt & 32768), '&nbsp;&nbsp;');
$show_email_radio = tmpl_draw_radio_opt('reg_display_email', "1\n0", "Yes\nNo", ($uent->users_opt & 1), '&nbsp;&nbsp;');
$notify_default_radio = tmpl_draw_radio_opt('reg_notify', "2\n0", "Yes\nNo", ($uent->users_opt & 2), '&nbsp;&nbsp;');
$pm_notify_default_radio = tmpl_draw_radio_opt('reg_pm_notify', "64\n0", "Yes\nNo", ($uent->users_opt & 64), '&nbsp;&nbsp;');
$accept_user_email = tmpl_draw_radio_opt('reg_email_messages', "16\n0", "Yes\nNo", ($uent->users_opt & 16), '&nbsp;&nbsp;');
$accept_pm = tmpl_draw_radio_opt('reg_pm_messages', "32\n0", "Yes\nNo", ($uent->users_opt & 32), '&nbsp;&nbsp;');
$show_sig_radio = tmpl_draw_radio_opt('reg_show_sigs', "4096\n0", "Yes\nNo", ($uent->users_opt & 4096), '&nbsp;&nbsp;');
$show_avatar_radio = tmpl_draw_radio_opt('reg_show_avatars', "8192\n0", "Yes\nNo", ($uent->users_opt & 8192), '&nbsp;&nbsp;');
$show_im_radio = tmpl_draw_radio_opt('reg_show_im', "16384\n0", "Yes\nNo", ($uent->users_opt & 16384), '&nbsp;&nbsp;');
$append_sig_radio = tmpl_draw_radio_opt('reg_append_sig', "2048\n0", "Yes\nNo", ($uent->users_opt & 2048), '&nbsp;&nbsp;');


if ($FUD_OPT_2 & 2 || $is_a) {    // PUBLIC_STATS is enabled or Admin user.
    $page_gen_time = number_format(microtime(true) - __request_timestamp_exact__, 5);
    $page_stats = $FUD_OPT_2 & 2 ? '<br /><div class="SmallText al">Total time taken to generate the page: ' . convertPlural($page_gen_time, array('' . $page_gen_time . ' seconds')) . '</div>' : '<br /><div class="SmallText al">Total time taken to generate the page: ' . convertPlural($page_gen_time, array('' . $page_gen_time . ' seconds')) . '</div>';
} else {
    $page_stats = '';
}
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
                <?php echo $tabs; ?>
                <form method="post" action="/index.php?t=register" id="fud_register"
                    enctype="multipart/form-data"<?php echo($FUD_OPT_3 & 256 ? ' autocomplete="off"' : ''); ?>>
                    <table cellspacing="1" cellpadding="2" class="ContentTable">
                        <tr id="c1" style="display: table-row;">
                            <th colspan="2" class="CatDesc expanded">Required Information</th>
                        </tr>
                        <tr class="child-c1">
                            <td colspan="2" class="RowStyleC">All fields are required.</td>
                        </tr>
                        <?php echo (!__fud_real_user__ ? (!__fud_real_user__ ? draw_err('reg_time_limit') . '' : '') . '
<tr class="child-c1 RowStyleA">
	<td width="55%"><label for="reg_login">
            Login:<br />
            <span class="SmallText">The name by which you would like to log-in and be known on this site.</span>
            ' . draw_err('reg_login') . '</label>
        </td>
	<td width="45%"><input type="text" size="25" name="reg_login" id="reg_login" value="' . $reg_login . '" maxlength="' . $GLOBALS['MAX_LOGIN_SHOW'] . '" required="required" />
                        <span id="reg_login-result"><span></td>
</tr>
' . ($FUD_OPT_2 & 128 ? '<tr class="child-c1 RowStyleA">
	<td>Alias:' . draw_err('reg_alias') . '<br /><span class="SmallText">If you want a nickname other than your login to be displayed in the forum, enter it here.</span></td>
	<td><input type="text" name="reg_alias" size="25" value="' . htmlspecialchars($reg_alias, null, null, false) . '" maxlength="' . $GLOBALS['MAX_LOGIN_SHOW'] . '" /></td>
</tr>' : '') . '
<tr class="child-c1 RowStyleA">
	<td><label for="reg_plaintext_passwd">
            Password:<br />
            <span class="SmallText">Password for your account. Passwords are case-sensitive and must be at least 6 characters long.</span>
            ' . draw_err('reg_plaintext_passwd') . '</label>
        </td>
	<td><input type="password" name="reg_plaintext_passwd" id="reg_plaintext_passwd" size="25" required="required" /></td>
</tr>
<tr class="child-c1 RowStyleA">
	<td><label for="reg_plaintext_passwd_conf">
            Confirm Password:<br />
            <span class="SmallText">Please re-enter your password.</span></label>
        </td>
	<td><input type="password" name="reg_plaintext_passwd_conf" id="reg_plaintext_passwd_conf" size="25" onkeyup="passwords_match(\'reg_plaintext_passwd\', this); return false;" required="required" /></td>
</tr>
<tr class="child-c1 RowStyleA">
	<td><label for="reg_email">
            E-mail Address:<br />
            <span class="SmallText">Your e-mail address. You can choose to hide it below, in the Preferences section.</span>
            ' . draw_err('reg_email') . '</label>
        </td>
        <td><input type="email" name="reg_email" id="reg_email" size="25" value="' . $reg_email . '" required="required" /></td>
	<span class="dn" style="display:none; visibility:hidden;">
		<input type="text" name="turing_test1" value="' . __request_timestamp__ . '" />
		<input type="text" name="turing_test2" value="" />
		<input type="text" name="turing_test3" value="' . md5($GLOBALS['FORUM_SETTINGS_PATH']) . '" />
	</span>

</tr>
' . (!($FUD_OPT_3 & 128) ? '<tr class="child-c1 RowStyleA">
	<td class="vt"><label for="turing_test">
                Verification:
                ' . draw_err('reg_turing') . '</label>
        </td>
	<td>
		' . generate_turing_val() . '
	</td>
</tr>' : '') : '<tr class="child-c1 RowStyleA">
	<td width="60%">Login:<br />
                        <span class="SmallText">The name by which you would like to log-in and be known on this site.</span</td>
	<td><span class="fb">' . $reg_login . '</span>' . (($FUD_OPT_4 & 1) && !$mod_id ? '&nbsp; <span class="SmallText">[ <a href="javascript://" onclick="window_open(\'/index.php?t=ruser&amp;' . _rsid . '\',\'ruser\',470,250);">change login</a> ]</span>' : '') . '</td>
</tr>
' . ($FUD_OPT_2 & 128 ? '<tr class="child-c1 RowStyleA">
	<td>Alias:' . draw_err('reg_alias') . '<br /><span class="SmallText">If you want a nickname other than your login to be displayed in the forum, enter it here.</span></td>
	<td><input type="text" name="reg_alias" size="25" value="' . htmlspecialchars($reg_alias, null, null, false) . '" maxlength="' . $GLOBALS['MAX_LOGIN_SHOW'] . '" /></td>
</tr>' : '') . '
<tr class="child-c1 RowStyleA">
	<td>Your Password:' . draw_err('reg_confirm_passwd') . '<br />
            <span class="SmallText">Password for your account. Passwords are case-sensitive and must be at least 6 characters long.</span></td>
	<td><nobr><input type="password" name="reg_confirm_passwd" size="25" />' . (($FUD_OPT_4 & 2) && !$mod_id ? '&nbsp; <span class="SmallText">[ <a href="javascript://" onclick="window_open(\'/index.php?t=rpasswd&amp;' . _rsid . '\',\'rpass\',470,250);">change password</a> ]</span>' : '') . '</nobr></td>
</tr>
<tr class="child-c1 RowStyleA">
	<td>E-mail Address:' . draw_err('reg_email') . '<br />
            <span class="SmallText">Your e-mail address. You can choose to hide it below, in the Preferences section.</span></td>
	<td><input type="email" name="reg_email" size="25" value="' . $reg_email . '" /></td>
</tr>') . '
' . $required_custom_fields . '

<tr id="c2" style="display: table-row;">
	<th colspan="2" class="CatDesc collapsed">Optional Information</th>
</tr>
<tr class="child-c2 RowStyleA" style="display: none;">
	<td colspan="2" class="RowStyleC">It is recommended that you not reveal any personal or identifying information in your profile. All information will be viewable by other forum members.</td>
</tr>
' . $optional_custom_fields . '
<tr class="child-c2 RowStyleA" style="display: none;">
	<td>Name:<br />
            <span class="SmallText">Your real name.</span></td>
	<td><input type="text" name="reg_name" size="25" value="' . $reg_name . '"></td>
</tr>
<tr class="child-c2 RowStyleA" style="display: none;">
	<td>Location:</td>
	<td><input type="text" spellcheck="true" name="reg_location" value="' . $reg_location . '" maxlength="255" size="25" /></td>
</tr>
<tr class="child-c2 RowStyleA" style="display: none;">
	<td>Occupation:</td>
	<td><input type="text" spellcheck="true" name="reg_occupation" value="' . $reg_occupation . '" maxlength="255" size="25" /></td>
</tr>
<tr class="child-c2 RowStyleA" style="display: none;">
	<td>Interests:</td>
	<td><input type="text" spellcheck="true" name="reg_interests" value="' . $reg_interests . '" maxlength="255" size="25" /></td>
</tr>
' . $avatar_type_sel . '
' . $avatar . '
<tr class="child-c2 RowStyleA vt" style="display: none;">
	<td>Birth Date:<br /><span class="SmallText">If you enter a birth date, then other forum members will be able to see it in your profile.</span></td>
	<td>
		<table border="0" cellspacing="3" cellpadding="0">
		 <tr class="GenText">
			<td class="ac">Day</td>
			<td class="ac">Month</td>
			<td class="ac">Year</td>
		</tr>
		<tr>
			<td class="ac"><select name="b_day">' . $day_select . '</select></td>
			<td class="ac"><select name="b_month">' . $month_select . '</select></td>
			<td class="ac"><input type="number" name="b_year" value="' . $b_year . '" maxlength="4" size="5" /></td>
			</tr>
		</table>
	</td>
</tr>
<tr class="child-c2 RowStyleA" style="display: none;">
	<td>Gender:</td>
	<td><select name="reg_gender">' . $gender_select . '</select></td>
</tr>
' . ($FUD_OPT_2 & 65536 ? '<tr class="child-c2 RowStyleA" style="display: none;">
	<td>Image:</td>
	<td><input type="text" name="reg_user_image" value="' . $reg_user_image . '" maxlength="255" size="25" /></td>
</tr>' : ''); ?>
                        <?php echo(__fud_real_user__ ? '
<tr class="child-c2 RowStyleA" style="display: none;">
	<td>Homepage:' . draw_err('reg_home_page') . '</td>
	<td><input type="url" name="reg_home_page" value="' . $reg_home_page . '" maxlength="255" /></td>
</tr>
' : ''); ?>
                        <tr class="child-c2 RowStyleA" style="display: none;">
                            <td class="RowStyleA" valign="top">Biography:<br/><span class="SmallText">A few details about yourself, such as your interests, job, etc...</span>
                            </td>
                            <td><textarea name="reg_bio" rows="5" cols="50"><?php echo $reg_bio; ?></textarea></td>
                        </tr>
                        <tr class="child-c2 RowStyleA" style="display: none;">
                            <td colspan="2">
                                <fieldset class="RowStyleA">
                                    <legend class="RowStyleB">Social networking sites:</legend>
                                    <table border="0" cellspacing="3" cellpadding="5" align="center">
                                        <tr>
                                            <td>
                                                <label>ICQ:<br/><img src="/theme/twig/images/icq.png" alt=""/>
                                                    <input type="text" name="reg_icq" value="<?php echo $reg_icq; ?>"
                                                        maxlength="32" size="25"/>
                                                </label>
                                            </td>
                                            <td>
                                                <label>AIM Handle:<br/><img src="/theme/twig/images/aim.png" alt=""/>
                                                    <input type="text" name="reg_aim" value="<?php echo $reg_aim; ?>"
                                                        maxlength="32" size="25"/>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <label>Yahoo Messenger:<br/><img src="/theme/twig/images/yahoo.png"
                                                        alt=""/>
                                                    <input type="text" name="reg_yahoo"
                                                        value="<?php echo $reg_yahoo; ?>" maxlength="32" size="25"/>
                                                </label>
                                            </td>
                                            <td>
                                                <label>MSN Messenger:<br/><img src="/theme/twig/images/msnm.png"
                                                        alt=""/>
                                                    <input type="text" name="reg_msnm" value="<?php echo $reg_msnm; ?>"
                                                        maxlength="32" size="25"/>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <label>Jabber Handle:<br/><img src="/theme/twig/images/jabber.png"
                                                        alt=""/>
                                                    <input type="text" name="reg_jabber"
                                                        value="<?php echo $reg_jabber; ?>" maxlength="32" size="25"/>
                                                </label>
                                            </td>
                                            <td>
                                                <label>Google Chat/IM Handle:<br/><img
                                                        src="/theme/twig/images/google.png" alt=""/>
                                                    <input type="text" name="reg_google"
                                                        value="<?php echo $reg_google; ?>" maxlength="32" size="25"/>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <label>Skype Handle:<br/><img src="/theme/twig/images/skype.png"
                                                        alt=""/>
                                                    <input type="text" name="reg_skype"
                                                        value="<?php echo $reg_skype; ?>" maxlength="32" size="25"/>
                                                </label>
                                            </td>
                                            <td>
                                                <label>Twitter Handle:<br/><img src="/theme/twig/images/twitter.png"
                                                        alt=""/>
                                                    <input type="text" name="reg_twitter"
                                                        value="<?php echo $reg_twitter; ?>" maxlength="32" size="25"/>
                                                </label>
                                            </td>
                                        </tr>
                                        <?php echo ($FUD_OPT_2 & 2048 ? '
' : '') . '
		</table>
		</fieldset>
		<br />
	</td>
</tr>

<tr id="c3" style="display: table-row;">
	<th colspan="2" class="CatDesc collapsed">Preferences</th>
</tr>
' . ($FUD_OPT_1 & 32768 ? '
<tr class="child-c3 RowStyleA" style="display: none;">
	<td class="vt">Signature:<br /><span class="SmallText">Optional signature, which will appear at the bottom of your messages.<br />' . tmpl_post_options('sig') . ($FORUM_SIG_ML ? '<br /><b>Maximum Length: </b>' . $GLOBALS['FORUM_SIG_ML'] . ' characters <a href="javascript: alert(&#39;Your Signature is &#39;+document.forms[&#39;fud_register&#39;].reg_sig.value.length+&#39; characters long. The maximum allowed signature length is ' . $GLOBALS['FORUM_SIG_ML'] . ' characters.&#39;);" class="SmallText">Check Signature Length</a>' : '') . '</span></td>
	<td>' . draw_err('reg_sig') . '<textarea name="reg_sig" rows="5" cols="50">' . $reg_sig . '</textarea></td>
</tr>
' : ''); ?>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Time Zone:</td>
                                            <td><select name="reg_time_zone"><?php echo $timezone_select; ?></select>
                                            </td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Ignore Administrative Messages:</td>
                                            <td><?php echo $ignore_admin_radio; ?></td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Invisible Mode:<br/><span
                                                    class="SmallText">Hides your online status.</span></td>
                                            <td><?php echo $invisible_mode_radio; ?></td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Show E-mail Address:<br/><span class="SmallText">Choose this option if you want your e-mail address to be displayed publicly.</span>
                                            </td>
                                            <td><?php echo $show_email_radio; ?></td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Select Notification by Default:<br/><span class="SmallText">If notification is enabled by default, it can be disabled when posting.</span>
                                            </td>
                                            <td><?php echo $notify_default_radio; ?></td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Private Message Notification:<br/><span class="SmallText">If enabled, you will be notified whenever a private message is sent to you.</span>
                                            </td>
                                            <td><?php echo $pm_notify_default_radio; ?></td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Choose Notification Method:<br/><span class="SmallText">Set your preferred notification method or disable notifications (for example when you go on vacation).</span>
                                            </td>
                                            <td><select
                                                    name="reg_notify_method"><?php echo $notification_select; ?></select>
                                            </td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Allow E-mail Messages:<br/><span class="SmallText">Allow other users to send you e-mails via this forum.</span>
                                            </td>
                                            <td><?php echo $accept_user_email; ?></td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Allow Private Messages:<br/><span class="SmallText">Allow other users to send you private messages in this forum.</span>
                                            </td>
                                            <td><?php echo $accept_pm; ?></td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Use Signature by Default:<br/><span class="SmallText">Automatically append your signature to every message you post.</span>
                                            </td>
                                            <td><?php echo $append_sig_radio; ?></td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Show Signatures:<br/><span class="SmallText">Allows you to either hide or show other forum members&#39; signatures.</span>
                                            </td>
                                            <td><?php echo $show_sig_radio; ?></td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Show Avatars:<br/><span class="SmallText">Allows you to hide avatars of other users when viewing their messages.</span>
                                            </td>
                                            <td><?php echo $show_avatar_radio; ?></td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Show IM indicators:<br/><span class="SmallText">Whether or not to show IM indicators of the author beside their messages.</span>
                                            </td>
                                            <td><?php echo $show_im_radio; ?></td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Messages Per Page:</td>
                                            <td><select name="reg_posts_ppg"><?php echo $mppg_select; ?></select></td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Topics Per Page:</td>
                                            <td><select
                                                    name="reg_topics_per_page"><?php echo $topics_per_page; ?></select>
                                            </td>
                                        </tr>
                                        <tr class="child-c3 RowStyleA" style="display: none;">
                                            <td>Default Topic View:</td>
                                            <td><select name="reg_default_view"><?php echo $view_select; ?></select>
                                            </td>
                                        </tr>
                                        <?php echo($theme_select ? '<tr class="child-c3 RowStyleA" style="display: none;">
	<td>Theme:</td>
	<td><select name="reg_theme">' . $theme_select . '</select></td>
</tr>' : ''); ?>
                                        <tr class="RowStyleC">
                                            <td colspan="2"
                                                class="ac"><?php echo(!__fud_real_user__ ? '<input type="submit" class="button" name="fud_submit" value="Register" />' : '<input type="submit" class="button" name="fud_submit" value="Update" />'); ?>
                                                &nbsp;<input type="reset" class="button" name="Reset" value="Reset"/>
                                            </td>
                                        </tr>
                                    </table>
                                    <?php echo _hs; ?>
                                    <input type="hidden" name="prev_loaded" value="1"/>
                                    <input type="hidden" name="mod_id" value="<?php echo $mod_id; ?>"/>
                                    <input type="hidden" name="reg_coppa" value="<?php echo $reg_coppa; ?>"/>
                </form>
                <script>
                    min_max_cats("/theme/twig/images", "Minimize Category", "Maximize Category", "", "");

                    $(document).ready(function () {
                        // Make rows clickable to expand/hide section
                        $(".collapsable").closest("tr").css("cursor", "pointer").click(function () {
                            jQuery(this).find("img").trigger("click");
                        });

                        $("#reg_login").change(function () {
                            $("#reg_login-result").html("<img src='/theme/twig/images/ajax-loader.gif' />");

                            $.ajax({
                                url: "index.php?t=autocomplete&check=login&term=" + $("#reg_login").val(),
                                success: function (data) {
                                    if (data == 1) {
                                        $("#reg_login-result").html("<img src='/theme/twig/images/tick.png' />Available");
                                    } else {
                                        $("#reg_login-result").html("<img src='/theme/twig/images/cross.png' /> Already taken");
                                    }
                                }
                            });
                        });
                    });

                </script>

                <br/>
                <div class="ac"><span
                        class="curtime"><b>Current Time:</b> <?php echo strftime('%a %b %d %H:%M:%S %Z %Y', __request_timestamp__); ?></span>
                </div>
                <?php echo $page_stats; ?>
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
