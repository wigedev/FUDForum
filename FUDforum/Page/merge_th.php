<?php
/**
 * copyright            : (C) 2001-2017 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: merge_th.php.t 6078 2017-09-25 14:57:31Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
}
function th_lock($id, $lck)
{
    q(
        'UPDATE fud30_thread SET thread_opt=(' .
        (!$lck ? q_bitand('thread_opt', ~1) : q_bitor('thread_opt', 1)) .
        ') WHERE id=' .
        $id
    );
}

function th_inc_view_count($id)
{
    global $plugin_hooks;
    if (isset($plugin_hooks['CACHEGET'], $plugin_hooks['CACHESET'])) {
        // Increment view counters in cache.
        $th_views = call_user_func($plugin_hooks['CACHEGET'][0], 'th_views');
        $th_views[$id] = (!empty($th_views) && array_key_exists($id, $th_views)) ? $th_views[$id] + 1 : 1;

        if ($th_views[$id] > 10 || count($th_views) > 100) {
            call_user_func($plugin_hooks['CACHESET'][0], 'th_views', []);    // Clear cache.
            // Start delayed database updating.
            foreach ($th_views as $id => $views) {
                q('UPDATE fud30_thread SET views=views+' . $views . ' WHERE id=' . $id);
            }
        } else {
            call_user_func($plugin_hooks['CACHESET'][0], 'th_views', $th_views);
        }
    } else {
        // No caching plugins available.
        q('UPDATE fud30_thread SET views=views+1 WHERE id=' . $id);
    }
}

function th_inc_post_count($id, $r, $lpi = 0, $lpd = 0)
{
    if ($lpi && $lpd) {
        q(
            'UPDATE fud30_thread SET replies=replies+' .
            $r .
            ', last_post_id=' .
            $lpi .
            ', last_post_date=' .
            $lpd .
            ' WHERE id=' .
            $id
        );
    } else {
        q('UPDATE fud30_thread SET replies=replies+' . $r . ' WHERE id=' . $id);
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

function tmpl_draw_select_opt($values, $names, $selected)
{
    $vls = explode("\n", $values);
    $nms = explode("\n", $names);

    if (count($vls) != count($nms)) {
        exit("FATAL ERROR: inconsistent number of values inside a select<br />\n");
    }

    $options = '';
    foreach ($vls as $k => $v) {
        $options .= '<option value="' .
            $v .
            '"' .
            ($v == $selected ? ' selected="selected"' : '') .
            '>' .
            $nms[$k] .
            '</option>';
    }

    return $options;
}

/** Log action to the forum's Action Log Viewer ACP. */
function logaction($user_id, $res, $res_id = 0, $action = null)
{
    q(
        'INSERT INTO fud30_action_log (logtime, logaction, user_id, a_res, a_res_id)
		VALUES(' .
        __request_timestamp__ .
        ', ' .
        ssn($action) .
        ', ' .
        $user_id .
        ', ' .
        ssn($res) .
        ', ' .
        (int)$res_id .
        ')'
    );
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
    $GLOBALS['__FUD_REPL__']['pattern'] = $GLOBALS['__FUD_REPL__']['replace'] = [];
    $a =& $GLOBALS['__FUD_REPL__']['pattern'];
    $b =& $GLOBALS['__FUD_REPL__']['replace'];

    $c = uq(
        'SELECT with_str, replace_str FROM fud30_replace WHERE replace_str IS NOT NULL AND with_str IS NOT NULL AND LENGTH(replace_str)>0'
    );
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
    $GLOBALS['__FUD_REPLR__']['pattern'] = $GLOBALS['__FUD_REPLR__']['replace'] = [];
    $a =& $GLOBALS['__FUD_REPLR__']['pattern'];
    $b =& $GLOBALS['__FUD_REPLR__']['replace'];

    $c = uq('SELECT replace_opt, with_str, replace_str, from_post, to_msg FROM fud30_replace');
    while ($r = db_rowarr($c)) {
        if (!$r[0]) {
            $a[] = $r[3];
            $b[] = $r[4];
        } else {
            if ($r[0] && strlen($r[1]) && strlen($r[2])) {
                $a[] = '/' . str_replace('/', '\\/', preg_quote(stripslashes($r[1]))) . '/';
                preg_match('/\/(.+)\/(.*)/', $r[2], $regs);
                $b[] = str_replace('\\/', '/', $regs[1]);
            }
        }
    }
    unset($c);

    define('__fud_replacer_init', 1);
}

function th_add(
    $root,
    $forum_id,
    $last_post_date,
    $thread_opt,
    $orderexpiry,
    $replies = 0,
    $views = 0,
    $lpi = 0,
    $descr = ''
) {
    if (!$lpi) {
        $lpi = $root;
    }

    return db_qid(
        'INSERT INTO
		fud30_thread
			(forum_id, root_msg_id, last_post_date, replies, views, rating, last_post_id, thread_opt, orderexpiry, tdescr)
		VALUES
			(' .
        $forum_id .
        ', ' .
        $root .
        ', ' .
        $last_post_date .
        ', ' .
        $replies .
        ', ' .
        $views .
        ', 0, ' .
        $lpi .
        ', ' .
        $thread_opt .
        ', ' .
        $orderexpiry .
        ',' .
        _esc($descr) .
        ')'
    );
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
    $msg_count = q_singleval(
        'SELECT count(*) FROM fud30_thread LEFT JOIN fud30_msg ON fud30_msg.thread_id=fud30_thread.id WHERE fud30_msg.apr=1 AND fud30_thread.id=' .
        $id
    );

    q('UPDATE fud30_thread SET forum_id=' . $to_forum . ' WHERE id=' . $id);
    q('UPDATE fud30_forum SET post_count=post_count-' . $msg_count . ' WHERE id=' . $forum_id);
    q(
        'UPDATE fud30_forum SET thread_count=thread_count+1,post_count=post_count+' .
        $msg_count .
        ' WHERE id=' .
        $to_forum
    );
    q(
        'DELETE FROM fud30_thread WHERE forum_id=' .
        $to_forum .
        ' AND root_msg_id=' .
        $root_msg_id .
        ' AND moved_to=' .
        $forum_id
    );
    if (($aff_rows = db_affected())) {
        q('UPDATE fud30_forum SET thread_count=thread_count-' . $aff_rows . ' WHERE id=' . $to_forum);
    }
    q('UPDATE fud30_thread SET moved_to=' . $to_forum . ' WHERE id!=' . $id . ' AND root_msg_id=' . $root_msg_id);

    q(
        'INSERT INTO fud30_thread
		(forum_id, root_msg_id, last_post_date, last_post_id, moved_to, tdescr)
	VALUES
		(' .
        $forum_id .
        ', ' .
        $root_msg_id .
        ', ' .
        $last_post_date .
        ', ' .
        $last_post_id .
        ', ' .
        $to_forum .
        ',' .
        _esc($descr) .
        ')'
    );

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
    $exp = db_all(
        'SELECT fud30_thread.id FROM fud30_tv_' .
        $forum_id .
        '
			INNER JOIN fud30_thread ON fud30_thread.id=fud30_tv_' .
        $forum_id .
        '.thread_id
			INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id
			WHERE fud30_tv_' .
        $forum_id .
        '.seq>' .
        (q_singleval(q_limit('SELECT /* USE MASTER */ seq FROM fud30_tv_' . $forum_id . ' ORDER BY seq DESC', 1)) -
            50) .
        ' 
				AND fud30_tv_' .
        $forum_id .
        '.iss>0
				AND fud30_thread.thread_opt>=2 
				AND (fud30_msg.post_stamp+fud30_thread.orderexpiry)<=' .
        __request_timestamp__
    );
    if ($exp) {
        q(
            'UPDATE fud30_thread SET orderexpiry=0, thread_opt=(thread_opt & ~(2|4)) WHERE id IN(' .
            implode(',', $exp) .
            ')'
        );
        $exp = 1;
    }

    /* Remove expired moved thread pointers. */
    q(
        'DELETE FROM fud30_thread WHERE forum_id=' .
        $forum_id .
        ' AND moved_to>0 AND last_post_date<' .
        (__request_timestamp__ - 86400 * $GLOBALS['MOVED_THR_PTR_EXPIRY'])
    );
    if (($aff_rows = db_affected())) {
        q(
            'UPDATE fud30_forum SET thread_count=thread_count-' .
            $aff_rows .
            ' WHERE thread_count>0 AND id=' .
            $forum_id
        );
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
        q(
            'INSERT INTO fud30_tv_' .
            $forum_id .
            ' (seq, thread_id, iss) SELECT ' .
            q_rownum() .
            ', id, iss FROM
			(SELECT TOP(1000000000) fud30_thread.id AS id, ' .
            q_bitand('thread_opt', (2 | 4 | 8)) .
            ' AS iss FROM fud30_thread 
			INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id 
			WHERE forum_id=' .
            $forum_id .
            ' AND fud30_msg.apr=1 
			ORDER BY (CASE WHEN thread_opt>=2 THEN (4294967294 + ((' .
            q_bitand('thread_opt', 8) .
            ') * 100000000) + fud30_thread.last_post_date) ELSE fud30_thread.last_post_date END) ASC) q1'
        );
    } else {
        if (__dbtype__ == 'sqlite') {
            // Prevent subquery flattening by adding "LIMIT -1 OFFSET 0" as it will prevent the rowid() code to work.
            // See http://stackoverflow.com/questions/17809644/how-to-disable-subquery-flattening-in-sqlite
            q(
                'INSERT INTO fud30_tv_' .
                $forum_id .
                ' (seq, thread_id, iss) SELECT ' .
                q_rownum() .
                ', id, iss FROM
			(SELECT fud30_thread.id AS id, ' .
                q_bitand('thread_opt', (2 | 4 | 8)) .
                ' AS iss FROM fud30_thread 
			INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id 
			WHERE forum_id=' .
                $forum_id .
                ' AND fud30_msg.apr=1 
			ORDER BY (CASE WHEN thread_opt>=2 THEN (4294967294 + ((' .
                q_bitand('thread_opt', 8) .
                ') * 100000000) + fud30_thread.last_post_date) ELSE fud30_thread.last_post_date END) ASC LIMIT -1 OFFSET 0) q1'
            );
        } else {
            //q('INSERT INTO fud30_tv_'. $forum_id .' (seq, thread_id, iss) SELECT '. q_rownum() .', id, iss FROM
            //	(SELECT fud30_thread.id AS id, '. q_bitand('thread_opt', (2|4|8)) .' AS iss FROM fud30_thread
            //	INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id
            //	WHERE forum_id='. $forum_id .' AND fud30_msg.apr=1
            //	ORDER BY (CASE WHEN thread_opt>=2 THEN (4294967294 + (('. q_bitand('thread_opt', 8) .') * 100000000) + fud30_thread.last_post_date) ELSE fud30_thread.last_post_date END) ASC) q1');

            q(
                'INSERT INTO fud30_tv_' . $forum_id . ' (seq, thread_id, iss)
			SELECT ' . q_rownum() . ', fud30_thread.id, ' . q_bitand('thread_opt', (2 | 4 | 8)) . ' FROM fud30_thread 
			INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id 
			WHERE forum_id=' . $forum_id . ' AND fud30_msg.apr=1 
			ORDER BY ' . q_bitand('thread_opt', (2 | 4 | 8)) . ' ASC, fud30_thread.last_post_date ASC'
            );
        }
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

    [$max, $iss] = db_saq(
        q_limit('SELECT /* USE MASTER */ seq, iss FROM fud30_tv_' . $forum_id . ' ORDER BY seq DESC', 1)
    );
    if ((!$sticky && $iss) || $iss >= 8) { /* Sub-optimal case, non-sticky topic and thre are stickies in the forum. */
        /* Find oldest sticky message. */
        if ($sticky && $iss >= 8) {
            $iss = q_singleval(
                q_limit(
                    'SELECT /* USE MASTER */ seq FROM fud30_tv_' .
                    $forum_id .
                    ' WHERE seq>' .
                    ($max - 50) .
                    ' AND iss>=8 ORDER BY seq ASC',
                    1
                )
            );
        } else {
            $iss = q_singleval(
                q_limit(
                    'SELECT /* USE MASTER */ seq FROM fud30_tv_' .
                    $forum_id .
                    ' WHERE seq>' .
                    ($max - 50) .
                    ' AND iss>0 ORDER BY seq ASC',
                    1
                )
            );
        }
        /* Move all stickies up one. */
        q('UPDATE fud30_tv_' . $forum_id . ' SET seq=seq+1 WHERE seq>=' . $iss);
        /* We do this, since in optimal case we just do ++max. */
        $max = --$iss;
    }
    q(
        'INSERT INTO fud30_tv_' .
        $forum_id .
        ' (thread_id,iss,seq) VALUES(' .
        $th .
        ',' .
        (int)$sticky .
        ',' .
        (++$max) .
        ')'
    );

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
    [$max, $tid, $iss] = db_saq(
        q_limit('SELECT /* USE MASTER */ seq,thread_id,iss FROM fud30_tv_' . $forum_id . ' ORDER BY seq DESC', 1)
    );

    if ($tid == $th) {
        /* NOOP: quick elimination, topic is already 1st. */
    } else {
        if (!$iss || ($sticky && $iss < 8)) { /* Moving to the very top. */
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
            $iss = q_singleval(
                q_limit(
                    'SELECT /* USE MASTER */ seq FROM fud30_tv_' .
                    $forum_id .
                    ' WHERE seq>' .
                    ($max - 50) .
                    ' AND iss>' .
                    ($sticky && $iss >= 8 ? '=8' : '0') .
                    ' ORDER BY seq ASC',
                    1
                )
            );
            /* Move everyone ahead, unless sticky, 1 down. */
            q('UPDATE fud30_tv_' . $forum_id . ' SET seq=seq-1 WHERE seq BETWEEN ' . ($pos + 1) . ' AND ' . ($iss - 1));
            /* Move to top of the stack. */
            q('UPDATE fud30_tv_' . $forum_id . ' SET seq=' . ($iss - 1) . ' WHERE thread_id=' . $th);
        }
    }

    if (isset($ll)) {
        db_unlock();
    }
}

if (_uid) {
    $admin_cp = $accounts_pending_approval = $group_mgr = $reported_msgs = $custom_avatar_queue = $mod_que = $thr_exch = '';

    if ($usr->users_opt & 524288 || $is_a) {    // is_mod or admin.
        if ($is_a) {
            // Approval of custom Avatars.
            if ($FUD_OPT_1 & 32 &&
                ($avatar_count = q_singleval(
                    'SELECT count(*) FROM fud30_users WHERE users_opt>=16777216 AND ' .
                    q_bitand('users_opt', 16777216) .
                    ' > 0'
                ))) {
                $custom_avatar_queue = '| <a href="/adm/admavatarapr.php?S=' .
                    s .
                    '&amp;SQ=' .
                    $GLOBALS['sq'] .
                    '">Custom Avatar Queue</a> <span class="GenTextRed">(' .
                    $avatar_count .
                    ')</span>';
            }

            // All reported messages.
            if ($report_count = q_singleval('SELECT count(*) FROM fud30_msg_report')) {
                $reported_msgs = '| <a href="/index.php?t=reported&amp;' .
                    _rsid .
                    '" rel="nofollow">Reported Messages</a> <span class="GenTextRed">(' .
                    $report_count .
                    ')</span>';
            }

            // All thread exchange requests.
            if ($thr_exchc = q_singleval('SELECT count(*) FROM fud30_thr_exchange')) {
                $thr_exch = '| <a href="/index.php?t=thr_exch&amp;' .
                    _rsid .
                    '">Topic Exchange</a> <span class="GenTextRed">(' .
                    $thr_exchc .
                    ')</span>';
            }

            // All account approvals.
            if ($FUD_OPT_2 & 1024 &&
                ($accounts_pending_approval = q_singleval(
                    'SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND ' .
                    q_bitand('users_opt', 2097152) .
                    ' > 0 AND id > 0'
                ))) {
                $accounts_pending_approval = '| <a href="/adm/admuserapr.php?S=' .
                    s .
                    '&amp;SQ=' .
                    $GLOBALS['sq'] .
                    '">Accounts Pending Approval</a> <span class="GenTextRed">(' .
                    $accounts_pending_approval .
                    ')</span>';
            } else {
                $accounts_pending_approval = '';
            }

            $q_limit = '';
        } else {
            // Messages reported in moderated forums.
            if ($report_count = q_singleval(
                'SELECT count(*) FROM fud30_msg_report mr INNER JOIN fud30_msg m ON mr.msg_id=m.id INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_mod mm ON t.forum_id=mm.forum_id AND mm.user_id=' .
                _uid
            )) {
                $reported_msgs = '| <a href="/index.php?t=reported&amp;' .
                    _rsid .
                    '" rel="nofollow">Reported Messages</a> <span class="GenTextRed">(' .
                    $report_count .
                    ')</span>';
            }

            // Thread move requests in moderated forums.
            if ($thr_exchc = q_singleval(
                'SELECT count(*) FROM fud30_thr_exchange te INNER JOIN fud30_mod m ON m.user_id=' .
                _uid .
                ' AND te.frm=m.forum_id'
            )) {
                $thr_exch = '| <a href="/index.php?t=thr_exch&amp;' .
                    _rsid .
                    '">Topic Exchange</a> <span class="GenTextRed">(' .
                    $thr_exchc .
                    ')</span>';
            }

            $q_limit = ' INNER JOIN fud30_mod mm ON f.id=mm.forum_id AND mm.user_id=' . _uid;
        }

        // Messages requiring approval.
        if ($approve_count = q_singleval(
            'SELECT count(*) FROM fud30_msg m INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_forum f ON t.forum_id=f.id ' .
            $q_limit .
            ' WHERE m.apr=0 AND f.forum_opt>=2'
        )) {
            $mod_que = '<a href="/index.php?t=modque&amp;' .
                _rsid .
                '">Moderation Queue</a> <span class="GenTextRed">(' .
                $approve_count .
                ')</span>';
        }
    } else {
        if ($usr->users_opt & 268435456 &&
            $FUD_OPT_2 & 1024 &&
            ($accounts_pending_approval = q_singleval(
                'SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND ' .
                q_bitand('users_opt', 2097152) .
                ' > 0 AND id > 0'
            ))) {
            $accounts_pending_approval = '| <a href="/adm/admuserapr.php?S=' .
                s .
                '&amp;SQ=' .
                $GLOBALS['sq'] .
                '">Accounts Pending Approval</a> <span class="GenTextRed">(' .
                $accounts_pending_approval .
                ')</span>';
        } else {
            $accounts_pending_approval = '';
        }
    }
    if ($is_a || $usr->group_leader_list) {
        $group_mgr = '| <a href="/index.php?t=groupmgr&amp;' . _rsid . '">Group Manager</a>';
    }

    if ($thr_exch || $accounts_pending_approval || $group_mgr || $reported_msgs || $custom_avatar_queue || $mod_que) {
        $admin_cp = '<br /><span class="GenText fb">Admin:</span> ' .
            $mod_que .
            ' ' .
            $reported_msgs .
            ' ' .
            $thr_exch .
            ' ' .
            $custom_avatar_queue .
            ' ' .
            $group_mgr .
            ' ' .
            $accounts_pending_approval .
            '<br />';
    }
} else {
    $admin_cp = '';
}/* Print number of unread private messages in User Control Panel. */
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

$frm = isset($_GET['frm_id']) ? (int)$_GET['frm_id'] : (isset($_POST['frm_id']) ? (int)$_POST['frm_id'] : 0);
if (!$frm) {
    invl_inp_err();
}

/* Permission check. */
if (!$is_a) {
    $perms = db_saq(
        'SELECT mm.id, ' .
        (_uid ? ' COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS gco ' : ' g1.group_cache_opt AS gco ') .
        '
				FROM fud30_forum f
				LEFT JOIN fud30_mod mm ON mm.user_id=' .
        _uid .
        ' AND mm.forum_id=f.id
				' .
        (_uid ? 'INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id=f.id LEFT JOIN fud30_group_cache g2 ON g2.user_id=' .
            _uid .
            ' AND g2.resource_id=f.id' : 'INNER JOIN fud30_group_cache g1 ON g1.user_id=0 AND g1.resource_id=f.id') .
        '
				WHERE f.id=' .
        $frm
    );
    if (!$perms || !$perms[0] && !($perms[1] & 2048)) {
        std_error('access');
    }
}

$forum = isset($_POST['forum']) ? (int)$_POST['forum'] : 0;
$error = '';
$post = (isset($_POST['next']) || isset($_POST['prev'])) ? 0 : 1;

if (isset($_GET['sel_th'])) {
    $_POST['sel_th'] = unserialize($_GET['sel_th']);
}
if (isset($_POST['sel_th'])) {
    foreach ($_POST['sel_th'] as $k => $v) {
        if (!(int)$v) {
            unset($_POST['sel_th'][$k]);
        }
        $_POST['sel_th'][$k] = (int)$v;
    }
    if (count($_POST['sel_th']) !=
        q_singleval(
            'SELECT count(*) FROM fud30_thread WHERE forum_id=' .
            $frm .
            ' AND id IN(' .
            implode(',', $_POST['sel_th']) .
            ')'
        )) {
        std_error('access');
    }
}

$new_title = isset($_GET['new_title']) ? $_GET['new_title'] : (isset($_POST['new_title']) ? $_POST['new_title'] : '');

if ($frm && $post && !empty($_POST['new_title']) && !empty($_POST['sel_th'])) {
    /* We need to make sure that the user has access to destination forum. */
    if (!$is_a &&
        !q_singleval(
            'SELECT f.id FROM fud30_forum f LEFT JOIN fud30_mod mm ON mm.user_id=' .
            _uid .
            ' AND mm.forum_id=f.id ' .
            (_uid ? 'INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id=f.id LEFT JOIN fud30_group_cache g2 ON g2.user_id=' .
                _uid .
                ' AND g2.resource_id=f.id' : 'INNER JOIN fud30_group_cache g1 ON g1.user_id=0 AND g1.resource_id=f.id') .
            ' WHERE f.id=' .
            $forum .
            ' AND (mm.id IS NOT NULL OR ' .
            q_bitand(_uid ? 'COALESCE(g2.group_cache_opt, g1.group_cache_opt)' : 'g1.group_cache_opt', 4) .
            ' > 0)'
        )) {
        std_error('access');
    }

    /* Sanity check. */
    if (empty($_POST['sel_th'])) {
        if ($FUD_OPT_2 & 32768) {
            header('Location: /index.php/t/' . $th . '/' . _rsidl);
        } else {
            header('Location: /index.php?t=' . d_thread_view . '&th=' . $th . '&' . _rsidl);
        }
        exit;
    } else {
        if (count($_POST['sel_th']) > 1) {
            apply_custom_replace($_POST['new_title']);

            if ($forum != $frm) {
                $lk_pfx = 'fud30_tv_' . $frm . ' WRITE,';
            } else {
                $lk_pfx = '';
            }
            db_lock(
                $lk_pfx .
                'fud30_tv_' .
                $forum .
                ' WRITE, fud30_thread WRITE, fud30_forum WRITE, fud30_msg WRITE, fud30_poll WRITE'
            );

            $tl = implode(',', $_POST['sel_th']);

            [$start, $replies, $views] = db_saq(
                'SELECT MIN(root_msg_id), SUM(replies), SUM(views) FROM fud30_thread WHERE id IN(' . $tl . ')'
            );
            $replies += count($_POST['sel_th']) - 1;
            [$lpi, $lpd, $tdescr] = db_saq(
                q_limit(
                    'SELECT last_post_id, last_post_date, tdescr FROM fud30_thread WHERE id IN(' .
                    $tl .
                    ') ORDER BY last_post_date DESC',
                    1
                )
            );

            $new_th = th_add($start, $forum, $lpd, 0, 0, $replies, $views, $lpi, $tdescr);
            q(
                'UPDATE fud30_msg SET reply_to=0, subject=' .
                _esc(htmlspecialchars($_POST['new_title'])) .
                ' WHERE id=' .
                $start
            );
            q(
                'UPDATE fud30_msg SET reply_to=' .
                $start .
                ' WHERE thread_id IN(' .
                $tl .
                ') AND (reply_to=0 OR reply_to=id) AND id!=' .
                $start
            );
            if ($forum != $frm) {
                $p = db_all('SELECT poll_id FROM fud30_msg WHERE thread_id IN(' . $tl . ') AND apr=1 AND poll_id>0');
                if ($p) {
                    q('UPDATE fud30_poll SET forum_id=' . $forum . ' WHERE id IN(' . implode(',', $p) . ')');
                }
            }
            q('UPDATE fud30_msg SET thread_id=' . $new_th . ' WHERE thread_id IN(' . $tl . ')');
            q('DELETE FROM fud30_thread WHERE id IN(' . $tl . ')');

            rebuild_forum_view_ttl($forum);
            if ($forum != $frm) {
                rebuild_forum_view_ttl($frm);
                foreach ([$frm, $forum] as $v) {
                    $r = db_saq(
                        'SELECT MAX(last_post_id), SUM(replies), COUNT(*) FROM fud30_thread INNER JOIN fud30_msg ON root_msg_id=fud30_msg.id AND fud30_msg.apr=1 WHERE forum_id=' .
                        $v
                    );
                    if (empty($r[2])) {
                        $r = [0, 0, 0];
                    }
                    q(
                        'UPDATE fud30_forum SET thread_count=' .
                        $r[2] .
                        ', post_count=' .
                        $r[1] .
                        ', last_post_id=' .
                        $r[0] .
                        ' WHERE id=' .
                        $v
                    );
                }
            } else {
                q(
                    'UPDATE fud30_forum SET thread_count=thread_count-' .
                    (count($_POST['sel_th']) - 1) .
                    ' WHERE id=' .
                    $frm
                );
            }
            db_unlock();

            /* Handle thread subscriptions and message read indicators. */
            if (__dbtype__ == 'mysql') {
                q('UPDATE IGNORE fud30_thread_notify SET thread_id=' . $new_th . ' WHERE thread_id IN(' . $tl . ')');
                q('UPDATE IGNORE fud30_bookmarks SET thread_id=' . $new_th . ' WHERE thread_id IN(' . $tl . ')');
                q('UPDATE IGNORE fud30_read SET thread_id=' . $new_th . ' WHERE thread_id IN(' . $tl . ')');
            } else {
                if (__dbtype__ == 'sqlite') {
                    q(
                        'UPDATE OR IGNORE fud30_thread_notify SET thread_id=' .
                        $new_th .
                        ' WHERE thread_id IN(' .
                        $tl .
                        ')'
                    );
                    q('UPDATE OR IGNORE fud30_bookmarks SET thread_id=' . $new_th . ' WHERE thread_id IN(' . $tl . ')');
                    q('UPDATE OR IGNORE fud30_read SET thread_id=' . $new_th . ' WHERE thread_id IN(' . $tl . ')');
                } else {
                    foreach (
                        db_all(
                            'SELECT user_id FROM fud30_thread_notify WHERE thread_id IN(' .
                            $tl .
                            ') AND thread_id!=' .
                            $new_th
                        ) as $v
                    ) {
                        db_li(
                            'INSERT INTO fud30_thread_notify (user_id, thread_id) VALUES(' . $v . ',' . $new_th . ')',
                            $tmp
                        );
                    }
                    foreach (
                        db_all(
                            'SELECT user_id FROM fud30_bookmarks WHERE thread_id IN(' .
                            $tl .
                            ') AND thread_id!=' .
                            $new_th
                        ) as $v
                    ) {
                        db_li(
                            'INSERT INTO fud30_bookmarks (user_id, thread_id) VALUES(' . $v . ',' . $new_th . ')',
                            $tmp
                        );
                    }
                }
            }
            q('DELETE FROM fud30_thread_notify WHERE thread_id IN(' . $tl . ')');
            q('DELETE FROM fud30_bookmarks WHERE thread_id IN(' . $tl . ')');
            q('DELETE FROM fud30_read WHERE thread_id IN(' . $tl . ')');

            logaction(_uid, 'THRMERGE', $new_th, count($_POST['sel_th']));
            unset($_POST['sel_th']);
        }
    }
}

/* Fetch a list of accesible forums. */
$c = uq(
    'SELECT f.id, f.name
			FROM fud30_forum f
			INNER JOIN fud30_fc_view v ON v.f=f.id
			INNER JOIN fud30_cat c ON c.id=f.cat_id
			LEFT JOIN fud30_mod mm ON mm.forum_id=f.id AND mm.user_id=' .
    _uid .
    '
			INNER JOIN fud30_group_cache g1 ON g1.resource_id=f.id AND g1.user_id=' .
    (_uid ? '2147483647' : '0') .
    '
			' .
    (_uid ? ' LEFT JOIN fud30_group_cache g2 ON g2.resource_id=f.id AND g2.user_id=' . _uid : '') .
    '
			' .
    ($is_a ? '' : ' WHERE mm.id IS NOT NULL OR ' .
        q_bitand(_uid ? 'COALESCE(g2.group_cache_opt, g1.group_cache_opt)' : 'g1.group_cache_opt', 2) .
        ' > 0') .
    '
			ORDER BY v.id'
);
$vl = $kl = '';
while ($r = db_rowarr($c)) {
    $vl .= $r[0] . "\n";
    $kl .= $r[1] . "\n";
}
unset($c);

$forum_sel = tmpl_draw_select_opt(rtrim($vl), rtrim($kl), $frm);

$page = !empty($_POST['page']) ? (int)$_POST['page'] : 1;
if ($page > 1 && isset($_POST['prev'])) {
    --$page;
} else {
    if (isset($_POST['next'])) {
        ++$page;
    }
}

$lwi = q_singleval(q_limit('SELECT seq FROM fud30_tv_' . $frm . ' ORDER BY seq DESC', 1));
$max_p = ceil($lwi / $THREADS_PER_PAGE);
if ($page > $max_p || $page < 1) {
    $page = 1;
}

$thread_sel = '';
if (isset($_POST['sel_th'])) {
    $c = uq(
        'SELECT t.id, m.subject FROM fud30_thread t INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE t.id IN(' .
        implode(',', $_POST['sel_th']) .
        ')'
    );
    while ($r = db_rowarr($c)) {
        $thread_sel .= '<option value="' . $r[0] . '" selected="selected">' . $r[1] . '</option>';
    }
    unset($c);
}

$c = uq(
    'SELECT t.id, m.subject FROM fud30_tv_' .
    $frm .
    ' tv 
			INNER JOIN fud30_thread t ON t.id=tv.thread_id 
			INNER JOIN fud30_msg m ON m.id=t.root_msg_id 
			WHERE tv.seq BETWEEN ' .
    ($lwi - ($page * $THREADS_PER_PAGE) + 1) .
    ' AND ' .
    ($lwi - (($page - 1) * $THREADS_PER_PAGE)) .
    '
			' .
    (isset($_POST['sel_th']) ? 'AND t.id NOT IN(' . implode(',', $_POST['sel_th']) . ')' : '') .
    '
			ORDER BY tv.seq DESC'
);
while ($r = db_rowarr($c)) {
    $thread_sel .= '<option value="' . $r[0] . '">' . $r[1] . '</option>';
}
unset($c, $_POST['sel_th']);

$pages = implode("\n", range(1, $max_p));

F()->response->newTH = $new_th;
F()->response->newTitle = $new_title;
F()->response->forumSel = $forum_sel;
F()->response->tmplSelectOptions = tmpl_draw_select_opt($pages, $pages, $page);
F()->response->threadSel = $thread_sel;
F()->response->page = $page;
F()->response->maxPage = $max_p;
F()->response->frm = $frm;
