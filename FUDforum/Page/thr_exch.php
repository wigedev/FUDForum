<?php
/**
 * copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: thr_exch.php.t 4994 2010-09-02 17:33:29Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
}
function send_status_update($uid, $ulogin, $uemail, $title, $msg)
{
    if ($GLOBALS['FUD_OPT_1'] & 1024) {    // PM_ENABLED
        if (defined('no_inline')) {
            fud_use('private.inc');
            fud_use('iemail.inc');
            fud_use('rev_fmt.inc');
        }
        $GLOBALS['recv_user_id'] = (array)$uid;
        $pmsg = new fud_pmsg;
        $pmsg->to_list = $ulogin;
        $pmsg->ouser_id = _uid;
        $pmsg->post_stamp = __request_timestamp__;
        $pmsg->subject = $title;
        $pmsg->host_name = 'NULL';
        $pmsg->ip_addr = '0.0.0.0';
        list($pmsg->foff, $pmsg->length) = write_pmsg_body(nl2br($msg));
        $pmsg->send_pmsg();
        return;
    }

    if (defined('no_inline')) {
        fud_use('iemail.inc');
    }
    send_email($GLOBALS['NOTIFY_FROM'], $uemail, $title, $msg);
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

/** Log action to the forum's Action Log Viewer ACP. */
function logaction($user_id, $res, $res_id = 0, $action = null)
{
    q('INSERT INTO fud30_action_log (logtime, logaction, user_id, a_res, a_res_id)
		VALUES(' . __request_timestamp__ . ', ' . ssn($action) . ', ' . $user_id . ', ' . ssn($res) . ', ' . (int)$res_id . ')');
}

function th_add($root, $forum_id, $last_post_date, $thread_opt, $orderexpiry, $replies = 0, $views = 0, $lpi = 0, $descr = '')
{
    if (!$lpi) {
        $lpi = $root;
    }

    return db_qid('INSERT INTO
		fud30_thread
			(forum_id, root_msg_id, last_post_date, replies, views, rating, last_post_id, thread_opt, orderexpiry, tdescr)
		VALUES
			(' . $forum_id . ', ' . $root . ', ' . $last_post_date . ', ' . $replies . ', ' . $views . ', 0, ' . $lpi . ', ' . $thread_opt . ', ' . $orderexpiry . ',' . _esc($descr) . ')');
}

function th_move($id, $to_forum, $root_msg_id, $forum_id, $last_post_date, $last_post_id, $descr)
{
    if (!db_locked()) {
        if ($to_forum != $forum_id) {
            $lock = 'fud30_tv_' . $to_forum . ' WRITE, fud30_tv_' . $forum_id;
        } else {
            $lock = 'fud30_tv_' . $to_forum;
        }

        db_lock('fud30_poll WRITE, ' . $lock . ' WRITE, fud30_thread WRITE, fud30_forum WRITE, fud30_msg WRITE');
        $ll = 1;
    }
    $msg_count = q_singleval('SELECT count(*) FROM fud30_thread LEFT JOIN fud30_msg ON fud30_msg.thread_id=fud30_thread.id WHERE fud30_msg.apr=1 AND fud30_thread.id=' . $id);

    q('UPDATE fud30_thread SET forum_id=' . $to_forum . ' WHERE id=' . $id);
    q('UPDATE fud30_forum SET post_count=post_count-' . $msg_count . ' WHERE id=' . $forum_id);
    q('UPDATE fud30_forum SET thread_count=thread_count+1,post_count=post_count+' . $msg_count . ' WHERE id=' . $to_forum);
    q('DELETE FROM fud30_thread WHERE forum_id=' . $to_forum . ' AND root_msg_id=' . $root_msg_id . ' AND moved_to=' . $forum_id);
    if (($aff_rows = db_affected())) {
        q('UPDATE fud30_forum SET thread_count=thread_count-' . $aff_rows . ' WHERE id=' . $to_forum);
    }
    q('UPDATE fud30_thread SET moved_to=' . $to_forum . ' WHERE id!=' . $id . ' AND root_msg_id=' . $root_msg_id);

    q('INSERT INTO fud30_thread
		(forum_id, root_msg_id, last_post_date, last_post_id, moved_to, tdescr)
	VALUES
		(' . $forum_id . ', ' . $root_msg_id . ', ' . $last_post_date . ', ' . $last_post_id . ', ' . $to_forum . ',' . _esc($descr) . ')');

    rebuild_forum_view_ttl($forum_id);
    rebuild_forum_view_ttl($to_forum);

    $p = db_all('SELECT poll_id FROM fud30_msg WHERE thread_id=' . $id . ' AND apr=1 AND poll_id>0');
    if ($p) {
        q('UPDATE fud30_poll SET forum_id=' . $to_forum . ' WHERE id IN(' . implode(',', $p) . ')');
    }

    if (isset($ll)) {
        db_unlock();
    }
}

function __th_cron_emu($forum_id, $run = 1)
{
    /* Let's see if we have sticky threads that have expired. */
    $exp = db_all('SELECT fud30_thread.id FROM fud30_tv_' . $forum_id . '
			INNER JOIN fud30_thread ON fud30_thread.id=fud30_tv_' . $forum_id . '.thread_id
			INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id
			WHERE fud30_tv_' . $forum_id . '.seq>' . (q_singleval(q_limit('SELECT /* USE MASTER */ seq FROM fud30_tv_' . $forum_id . ' ORDER BY seq DESC', 1)) - 50) . ' 
				AND fud30_tv_' . $forum_id . '.iss>0
				AND fud30_thread.thread_opt>=2 
				AND (fud30_msg.post_stamp+fud30_thread.orderexpiry)<=' . __request_timestamp__);
    if ($exp) {
        q('UPDATE fud30_thread SET orderexpiry=0, thread_opt=(thread_opt & ~(2|4)) WHERE id IN(' . implode(',', $exp) . ')');
        $exp = 1;
    }

    /* Remove expired moved thread pointers. */
    q('DELETE FROM fud30_thread WHERE forum_id=' . $forum_id . ' AND moved_to>0 AND last_post_date<' . (__request_timestamp__ - 86400 * $GLOBALS['MOVED_THR_PTR_EXPIRY']));
    if (($aff_rows = db_affected())) {
        q('UPDATE fud30_forum SET thread_count=thread_count-' . $aff_rows . ' WHERE thread_count>0 AND id=' . $forum_id);
        if (!$exp) {
            $exp = 1;
        }
    }

    if ($exp && $run) {
        rebuild_forum_view_ttl($forum_id, 1);
    }

    return $exp;
}

function rebuild_forum_view_ttl($forum_id, $skip_cron = 0)
{
// 1 topic locked
// 2 is_sticky ANNOUNCE
// 4 is_sticky STICKY
// 8 important (always on top)

    if (!$skip_cron) {
        __th_cron_emu($forum_id, 0);
    }

    if (!db_locked()) {
        $ll = 1;
        db_lock('fud30_tv_' . $forum_id . ' WRITE, fud30_thread READ, fud30_msg READ');
    }

    q('DELETE FROM fud30_tv_' . $forum_id);

    if (__dbtype__ == 'mssql') {
        // Add "TOP(1000000000)" as workaround for ERROR Msg 1033:
        // "The ORDER BY clause is invalid in views, inline functions, derived tables, subqueries, and common table expressions, unless TOP or FOR XML is also specified."
        // See http://support.microsoft.com/kb/841845/en
        q('INSERT INTO fud30_tv_' . $forum_id . ' (seq, thread_id, iss) SELECT ' . q_rownum() . ', id, iss FROM
			(SELECT TOP(1000000000) fud30_thread.id AS id, ' . q_bitand('thread_opt', (2 | 4 | 8)) . ' AS iss FROM fud30_thread 
			INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id 
			WHERE forum_id=' . $forum_id . ' AND fud30_msg.apr=1 
			ORDER BY (CASE WHEN thread_opt>=2 THEN (4294967294 + ((' . q_bitand('thread_opt', 8) . ') * 100000000) + fud30_thread.last_post_date) ELSE fud30_thread.last_post_date END) ASC) q1');
    } else if (__dbtype__ == 'sqlite') {
        // Prevent subquery flattening by adding "LIMIT -1 OFFSET 0" as it will prevent the rowid() code to work.
        // See http://stackoverflow.com/questions/17809644/how-to-disable-subquery-flattening-in-sqlite
        q('INSERT INTO fud30_tv_' . $forum_id . ' (seq, thread_id, iss) SELECT ' . q_rownum() . ', id, iss FROM
			(SELECT fud30_thread.id AS id, ' . q_bitand('thread_opt', (2 | 4 | 8)) . ' AS iss FROM fud30_thread 
			INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id 
			WHERE forum_id=' . $forum_id . ' AND fud30_msg.apr=1 
			ORDER BY (CASE WHEN thread_opt>=2 THEN (4294967294 + ((' . q_bitand('thread_opt', 8) . ') * 100000000) + fud30_thread.last_post_date) ELSE fud30_thread.last_post_date END) ASC LIMIT -1 OFFSET 0) q1');
    } else {
        //q('INSERT INTO fud30_tv_'. $forum_id .' (seq, thread_id, iss) SELECT '. q_rownum() .', id, iss FROM
        //	(SELECT fud30_thread.id AS id, '. q_bitand('thread_opt', (2|4|8)) .' AS iss FROM fud30_thread
        //	INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id
        //	WHERE forum_id='. $forum_id .' AND fud30_msg.apr=1
        //	ORDER BY (CASE WHEN thread_opt>=2 THEN (4294967294 + (('. q_bitand('thread_opt', 8) .') * 100000000) + fud30_thread.last_post_date) ELSE fud30_thread.last_post_date END) ASC) q1');

        q('INSERT INTO fud30_tv_' . $forum_id . ' (seq, thread_id, iss)
			SELECT ' . q_rownum() . ', fud30_thread.id, ' . q_bitand('thread_opt', (2 | 4 | 8)) . ' FROM fud30_thread 
			INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id 
			WHERE forum_id=' . $forum_id . ' AND fud30_msg.apr=1 
			ORDER BY ' . q_bitand('thread_opt', (2 | 4 | 8)) . ' ASC, fud30_thread.last_post_date ASC');
    }

    if (isset($ll)) {
        db_unlock();
    }
}

function th_delete_rebuild($forum_id, $th)
{
    if (!db_locked()) {
        $ll = 1;
        db_lock('fud30_tv_' . $forum_id . ' WRITE');
    }

    /* Get position. */
    if (($pos = q_singleval('SELECT /* USE MASTER */ seq FROM fud30_tv_' . $forum_id . ' WHERE thread_id=' . $th))) {
        q('DELETE FROM fud30_tv_' . $forum_id . ' WHERE thread_id=' . $th);
        /* Move every one down one, if placed after removed topic. */
        q('UPDATE fud30_tv_' . $forum_id . ' SET seq=seq-1 WHERE seq>' . $pos);
    }

    if (isset($ll)) {
        db_unlock();
    }
}

function th_new_rebuild($forum_id, $th, $sticky)
{
    if (__th_cron_emu($forum_id)) {
        return;
    }

    if (!db_locked()) {
        $ll = 1;
        db_lock('fud30_tv_' . $forum_id . ' WRITE');
    }

    list($max, $iss) = db_saq(q_limit('SELECT /* USE MASTER */ seq, iss FROM fud30_tv_' . $forum_id . ' ORDER BY seq DESC', 1));
    if ((!$sticky && $iss) || $iss >= 8) { /* Sub-optimal case, non-sticky topic and thre are stickies in the forum. */
        /* Find oldest sticky message. */
        if ($sticky && $iss >= 8) {
            $iss = q_singleval(q_limit('SELECT /* USE MASTER */ seq FROM fud30_tv_' . $forum_id . ' WHERE seq>' . ($max - 50) . ' AND iss>=8 ORDER BY seq ASC', 1));
        } else {
            $iss = q_singleval(q_limit('SELECT /* USE MASTER */ seq FROM fud30_tv_' . $forum_id . ' WHERE seq>' . ($max - 50) . ' AND iss>0 ORDER BY seq ASC', 1));
        }
        /* Move all stickies up one. */
        q('UPDATE fud30_tv_' . $forum_id . ' SET seq=seq+1 WHERE seq>=' . $iss);
        /* We do this, since in optimal case we just do ++max. */
        $max = --$iss;
    }
    q('INSERT INTO fud30_tv_' . $forum_id . ' (thread_id,iss,seq) VALUES(' . $th . ',' . (int)$sticky . ',' . (++$max) . ')');

    if (isset($ll)) {
        db_unlock();
    }
}

function th_reply_rebuild($forum_id, $th, $sticky)
{
    if (!db_locked()) {
        $ll = 1;
        db_lock('fud30_tv_' . $forum_id . ' WRITE');
    }

    /* Get first topic of forum (highest seq). */
    list($max, $tid, $iss) = db_saq(q_limit('SELECT /* USE MASTER */ seq,thread_id,iss FROM fud30_tv_' . $forum_id . ' ORDER BY seq DESC', 1));

    if ($tid == $th) {
        /* NOOP: quick elimination, topic is already 1st. */
    } else if (!$iss || ($sticky && $iss < 8)) { /* Moving to the very top. */
        /* Get position. */
        $pos = q_singleval('SELECT /* USE MASTER */ seq FROM fud30_tv_' . $forum_id . ' WHERE thread_id=' . $th);
        /* Move everyone ahead, 1 down. */
        q('UPDATE fud30_tv_' . $forum_id . ' SET seq=seq-1 WHERE seq>' . $pos);
        /* Move to top of the stack. */
        q('UPDATE fud30_tv_' . $forum_id . ' SET seq=' . $max . ' WHERE thread_id=' . $th);
    } else {
        /* Get position. */
        $pos = q_singleval('SELECT /* USE MASTER */ seq FROM fud30_tv_' . $forum_id . ' WHERE thread_id=' . $th);
        /* Find oldest sticky message. */
        $iss = q_singleval(q_limit('SELECT /* USE MASTER */ seq FROM fud30_tv_' . $forum_id . ' WHERE seq>' . ($max - 50) . ' AND iss>' . ($sticky && $iss >= 8 ? '=8' : '0') . ' ORDER BY seq ASC', 1));
        /* Move everyone ahead, unless sticky, 1 down. */
        q('UPDATE fud30_tv_' . $forum_id . ' SET seq=seq-1 WHERE seq BETWEEN ' . ($pos + 1) . ' AND ' . ($iss - 1));
        /* Move to top of the stack. */
        q('UPDATE fud30_tv_' . $forum_id . ' SET seq=' . ($iss - 1) . ' WHERE thread_id=' . $th);
    }

    if (isset($ll)) {
        db_unlock();
    }
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

/* Only admins & moderators have access to this control panel. */
if (!_uid) {
    std_error('login');
}
if (!($usr->users_opt & (1048576 | 524288))) {
    std_error('access');
}

if (isset($_GET['appr']) || isset($_GET['decl']) || isset($_POST['decl'])) {
    fud_use('thrx_adm.inc', true);
}
$decl = 0;

/* Verify that we got a valid thread-x-change approval. */
if (isset($_GET['appr']) && ($thrx = thx_get((int)$_GET['appr']))) {
    $data = db_sab('SELECT
					t.forum_id, t.last_post_id, t.root_msg_id, t.last_post_date, t.last_post_id, t.tdescr,
					f1.id, f1.last_post_id as f1_lpi, f2.last_post_id AS f2_lpi,
					' . ($is_a ? ' 1 ' : ' mm.id ') . ' AS md
				FROM fud30_thread t
				INNER JOIN fud30_forum f1 ON t.forum_id=f1.id
				INNER JOIN fud30_forum f2 ON f2.id=' . $thrx->frm . '
				LEFT JOIN fud30_mod mm ON mm.forum_id=f2.id AND mm.user_id=' . _uid . '
				WHERE t.id=' . $thrx->th);
    if (!$data) {
        invl_inp_err();
    }
    if (!$data->md) {
        std_error('access');
    }

    th_move($thrx->th, $thrx->frm, $data->root_msg_id, $data->forum_id, $data->last_post_date, $data->last_post_id, $data->tdescr);

    if ($data->f1_lpi == $data->last_post_id) {
        $mid = (int)q_singleval('SELECT MAX(last_post_id) FROM fud30_thread t INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE t.forum_id=' . $data->forum_id . ' AND t.moved_to=0 AND m.apr=1');
        q('UPDATE fud30_forum SET last_post_id=' . $mid . ' WHERE id=' . $data->forum_id);
    }

    if ($data->f2_lpi < $data->last_post_id) {
        q('UPDATE fud30_forum SET last_post_id=' . $data->last_post_id . ' WHERE id=' . $thrx->frm);
    }

    thx_delete($thrx->id);
    logaction($usr->id, 'THRXAPPROVE', $thrx->th);
} else if ((isset($_GET['decl']) || isset($_POST['decl'])) && ($thrx = thx_get(($decl = (int)(isset($_GET['decl']) ? $_GET['decl'] : $_POST['decl']))))) {
    $data = db_sab('SELECT u.email, u.login, u.id, m.subject, f1.name AS f1_name, f2.name AS f2_name, ' . ($is_a ? ' 1 ' : ' mm.id ') . ' AS md
				FROM fud30_thread t
				INNER JOIN fud30_forum f1 ON t.forum_id=f1.id
				INNER JOIN fud30_forum f2 ON f2.id=' . $thrx->frm . '
				INNER JOIN fud30_msg m ON m.id=t.root_msg_id
				INNER JOIN fud30_users u ON u.id=' . $thrx->req_by . '
				LEFT JOIN fud30_mod mm ON mm.forum_id=' . $thrx->frm . ' AND mm.user_id=' . _uid . '
				WHERE t.id=' . $thrx->th);
    if (!$data) {
        invl_inp_err();
    }
    if (!$data->md) {
        std_error('access');
    }

    if (!empty($_POST['reason'])) {
        send_status_update($data->id, $data->login, $data->email, 'Moving of topic ' . $data->subject . ' into forum ' . $data->f2_name . ' was declined.', htmlspecialchars($_POST['reason']));
        thx_delete($thrx->id);
        $decl = 0;
    } else {
        $thr_exch_data = '<form method="post" action="/index.php?t=thr_exch" name="thr_exch">
' . _hs . '<input type="hidden" name="decl" value="' . $decl . '" />
<tr>
	<td class="RowStyleC">Reason for declining topic <b>' . $data->subject . '</b> into forum <b>' . $data->f2_name . '</b><br /><textarea name="reason" cols="60" rows="10"></textarea><br /><input type="submit" class="button" name="btn_submit" value="Submit" /></td>
<tr>
</form>';
    }

    logaction($usr->id, 'THRXDECLINE', $thrx->th);
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

if (!$decl) {
    $thr_exch_data = '';
    $thx = array();

    $r = uq('SELECT
				thx.*, m.subject, f1.name AS sf_name, f2.name AS df_name, u.alias
			 FROM fud30_thr_exchange thx
			 INNER JOIN fud30_thread t ON thx.th=t.id
			 INNER JOIN fud30_msg m ON t.root_msg_id=m.id
			 INNER JOIN fud30_forum f1 ON t.forum_id=f1.id
			 INNER JOIN fud30_forum f2 ON thx.frm=f2.id
			 INNER JOIN fud30_users u ON thx.req_by=u.id
			 LEFT JOIN fud30_mod mm ON mm.forum_id=f2.id AND mm.user_id=' . _uid .
        ($is_a ? '' : ' WHERE mm.id IS NOT NULL'));

    while ($obj = db_rowobj($r)) {
        if ($is_a) {
            $thx[] = $obj->id;
        }
        $thr_exch_data .= '<tr><td>
<table border="0" cellspacing="0" cellpadding="3" class="wa">
<tr class="RowStyleB">
	<td class="al nw vt SmallText"><b>Topic move requested by:</b> <a href="/index.php?t=usrinfo&amp;id=' . $obj->req_by . '&amp;' . _rsid . '">' . htmlspecialchars($obj->alias, null, null, false) . '</a><br /></td>
	<td class="ac wa vt SmallText"><b>Reason for topic move:</b><br /><table border="1" cellspacing="1" cellpadding="0"><tr><td class="al">&nbsp;' . $obj->reason_msg . ' &nbsp;</td></tr></table></td>
</tr>
<tr class="RowStyleC">
	<td colspan="2">
		<table border="0" cellspacing="0" cellpadding="3" class="wa">
		<tr>
			<td class="al SmallText"><b>Original Forum:</b> ' . $obj->sf_name . '<br /><b>Destination Forum:</b> ' . $obj->df_name . '<br /><b>Topic:</b> <a href="/index.php?t=msg&amp;' . _rsid . '&amp;th=' . $obj->th . '">' . $obj->subject . '</a></td>
			<td class="ar">[<a href="/index.php?t=thr_exch&amp;appr=' . $obj->id . '&amp;' . _rsid . '">accept topic</a>]&nbsp;&nbsp;[<a href="/index.php?t=thr_exch&amp;decl=' . $obj->id . '&amp;' . _rsid . '">decline topic</a>]</td>
		</tr>
		</table>
	</td>
</tr>
</table>
</td></tr>';
    }
    unset($r);

    if (!$thr_exch_data) {
        $thr_exch_data = '<tr>
	<td class="RowStyleA ac">No topics waiting approval.</td>
</tr>';
    } else if ($is_a && $thx) {
        q('DELETE FROM fud30_thr_exchange WHERE id NOT IN(' . implode(',', $thx) . ')');
    }
}

F()->response->threadExchData = $thr_exch_data;
