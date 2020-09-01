<?php
/**
 * copyright            : (C) 2001-2018 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: mvthread.php.t 6174 2018-07-10 07:20:06Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

define('plain_form', 1);

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
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

$th = isset($_POST['th']) ? (int)$_POST['th'] : (isset($_GET['th']) ? (int)$_GET['th'] : 0);
$thx = isset($_POST['thx']) ? (int)$_POST['thx'] : (isset($_GET['thx']) ? (int)$_GET['thx'] : 0);
$to = isset($_GET['to']) ? (int)$_GET['to'] : 0;

if (!$th) {
    invl_inp_err();
}

/* thread x-change */
if ($thx) {
    if (!$GLOBALS['is_post'] && !sq_check(0, $usr->sq)) {
        return;
    }

    if (!$is_a && q_singleval('SELECT id FROM fud30_mod WHERE forum_id=' . $thx . ' AND user_id=' . _uid)) {
        std_error('access');
    }

    if (!empty($_POST['reason_msg'])) {
        fud_use('thrx_adm.inc', true);
        if (thx_add($_POST['reason_msg'], $th, $thx, _uid)) {
            logaction(_uid, 'THRXREQUEST', $th);
        }
        exit('<html><script>window.close();</script></html>');
    } else {
        $thr = db_sab('SELECT f.name AS frm_name, m.subject FROM fud30_forum f INNER JOIN fud30_thread t ON t.id=' . $th . ' INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE f.id=' . $thx);
        if (!$thr) {
            invl_inp_err();
        }
        $table_data = '<tr>
	<td class="small fb">' . $thr->frm_name . '</td>
</tr>
<tr>
	<td class="small">
		Why do you wish the topic to be moved?<br />
		<textarea name="reason_msg" rows="7" cols="30"></textarea>
	</td>
</tr>
<tr>
	<td class="ar"><input type="submit" class="button" name="submit" value="Submit Request" /></td>
</tr>';
    }
}

/* moving a thread */
if ($to) {
    if (!$GLOBALS['is_post'] && !sq_check(0, $usr->sq)) {
        return;
    }

    $thr = db_sab('SELECT
				t.id, t.forum_id, t.last_post_id, t.root_msg_id, t.last_post_date, t.last_post_id, t.tdescr,
				f1.last_post_id AS f1_lpi, f2.last_post_id AS f2_lpi,
				' . ($is_a ? ' 1 AS mod1, 1 AS mod2' : ' mm1.id AS mod1, mm2.id AS mod2') . ',
				COALESCE(gs2.group_cache_opt, gs1.group_cache_opt) AS sgco,
				COALESCE(gd2.group_cache_opt, gd1.group_cache_opt) AS dgco
			FROM fud30_thread t
			INNER JOIN fud30_forum f1 ON t.forum_id=f1.id
			INNER JOIN fud30_forum f2 ON f2.id=' . $to . '
			LEFT JOIN fud30_mod mm1 ON mm1.forum_id=f1.id AND mm1.user_id=' . _uid . '
			LEFT JOIN fud30_mod mm2 ON mm2.forum_id=f2.id AND mm2.user_id=' . _uid . '
			INNER JOIN fud30_group_cache gs1 ON gs1.user_id=2147483647 AND gs1.resource_id=f1.id
			LEFT JOIN fud30_group_cache gs2 ON gs2.user_id=' . _uid . ' AND gs2.resource_id=f1.id
			INNER JOIN fud30_group_cache gd1 ON gd1.user_id=2147483647 AND gd1.resource_id=f2.id
			LEFT JOIN fud30_group_cache gd2 ON gd2.user_id=' . _uid . ' AND gd2.resource_id=f2.id
			WHERE t.id=' . $th);

    if (!$thr) {
        invl_inp_err();
    }

    if ((!$thr->mod1 && !($thr->sgco & 8192)) || (!$thr->mod2 && !($thr->dgco & 8192))) {
        std_error('access');
    }

    /* Fetch data about source thread & forum. */
    $src_frm_lpi = (int)$thr->f1_lpi;
    /* Fetch data about dest forum. */
    $dst_frm_lpi = (int)$thr->f2_lpi;

    th_move($thr->id, $to, $thr->root_msg_id, $thr->forum_id, $thr->last_post_date, $thr->last_post_id, $thr->tdescr);

    if ($src_frm_lpi == $thr->last_post_id) {
        $mid = (int)q_singleval('SELECT MAX(last_post_id) FROM fud30_thread t INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE t.forum_id=' . $thr->forum_id . ' AND t.moved_to=0 AND m.apr=1');
        q('UPDATE fud30_forum SET last_post_id=' . $mid . ' WHERE id=' . $thr->forum_id);
    }

    if ($dst_frm_lpi < $thr->last_post_id) {
        q('UPDATE fud30_forum SET last_post_id=' . $thr->last_post_id . ' WHERE id=' . $to);
    }

    logaction(_uid, 'THRMOVE', $th);

    if ($FUD_OPT_2 & 32768 && !empty($_SERVER['PATH_INFO'])) {
        exit('<html><script>window.opener.location=\'/index.php/f/' . $thr->forum_id . '/' . _rsid . '\'; window.close();</script></html>');
    } else {
        exit('<html><script>window.opener.location=\'/index.php?t=' . t_thread_view . '&' . _rsid . '&frm_id=' . $thr->forum_id . '\'; window.close();</script></html>');
    }
}


if (!$thx) {
    $thr = db_sab('SELECT f.name AS frm_name, m.subject, t.forum_id, t.id FROM fud30_thread t INNER JOIN fud30_forum f ON f.id=t.forum_id INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE t.id=' . $th);
    if (!$thr) {
        invl_inp_err();
    }

    $c = uq('SELECT f.name, f.id, c.id, m.user_id, COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS gco
			FROM fud30_forum f
			INNER JOIN fud30_fc_view v ON v.f=f.id
			INNER JOIN fud30_cat c ON c.id=v.c
			LEFT JOIN fud30_mod m ON m.user_id=' . _uid . ' AND m.forum_id=f.id
			INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id=f.id
			LEFT JOIN fud30_group_cache g2 ON g2.user_id=' . _uid . ' AND g2.resource_id=f.id
			WHERE c.id!=0 AND f.url_redirect IS NULL AND f.id!=' . $thr->forum_id . ($is_a ? '' : ' AND (CASE WHEN m.user_id IS NOT NULL OR ' . q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 1) . ' > 0 THEN 1 ELSE 0 END)=1') . '
			ORDER BY v.id');

    $table_data = $oldc = '';

    require $FORUM_SETTINGS_PATH . 'cat_cache.inc';
    while ($r = db_rowarr($c)) {
        if ($oldc != $r[2]) {    // Control break on Catagory ID
            foreach ($cat_cache as $k => $i) {
                if ($k == $r[2]) {
                    break;
                }
            }
            $table_data .= '<tr>
	<td class="mvTc" style="padding-left: ' . ($tabw = ($i[0] * 10 + 2)) . 'px">' . $i[1] . '</td>
</tr>';
            $oldc = $r[2];
        }

        if ($r[3] || $is_a || $r[4] & 8192) {
            $table_data .= '<tr>
	<td style="padding-left: ' . $tabw . 'px"><a href="/index.php?t=mvthread&amp;th=' . $thr->id . '&amp;to=' . $r[1] . '&amp;' . _rsid . '&amp;SQ=' . $GLOBALS['sq'] . '">' . $r[0] . '</a></td>
</tr>';
        } else {
            $table_data .= '<tr>
	<td style="padding-left: ' . $tabw . 'px">' . $r[0] . ' [<a href="/index.php?t=mvthread&amp;th=' . $thr->id . '&amp;' . _rsid . '&amp;thx=' . $r[1] . '&amp;SQ=' . $GLOBALS['sq'] . '">request a move</a>]</td>
</tr>';
        }
    }
    unset($c);
}

F()->response->thr = $thr;
F()->response->th = $th;
F()->response->thx = $thx;
F()->response->tableData = $table_data;
