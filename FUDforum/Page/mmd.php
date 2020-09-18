<?php
/**
 * copyright            : (C) 2001-2020 Advanced Internet Designs Inc.
 * email                : forum@prohost.org
 * $Id: mmd.php.t 6355 2020-01-25 19:27:50Z naudefj $
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; version 2 of the License.
 **/

if (_uid === '_uid') {
    exit('Sorry, you can not access this page.');
}

class fud_msg
{
    var $id, $thread_id, $poster_id, $reply_to, $ip_addr, $host_name, $post_stamp, $subject, $attach_cnt, $poll_id,
        $update_stamp, $icon, $apr, $updated_by, $login, $length, $foff, $file_id, $msg_opt,
        $file_id_preview, $length_preview, $offset_preview, $body, $mlist_msg_id;
}

$GLOBALS['CHARSET'] = 'utf-8';

class fud_msg_edit extends fud_msg
{
    function add_reply($reply_to, $th_id = null, $perm, $autoapprove = 1)
    {
        if ($reply_to) {
            $this->reply_to = $reply_to;
            $fd = db_saq(
                'SELECT t.forum_id, f.message_threshold, f.forum_opt FROM fud30_msg m INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_forum f ON f.id=t.forum_id WHERE m.id=' .
                $reply_to
            );
        } else {
            $fd = db_saq(
                'SELECT t.forum_id, f.message_threshold, f.forum_opt FROM fud30_thread t INNER JOIN fud30_forum f ON f.id=t.forum_id WHERE t.id=' .
                $th_id
            );
        }

        return $this->add($fd[0], $fd[1], $fd[2], $perm, $autoapprove);
    }

    function add($forum_id, $message_threshold, $forum_opt, $perm, $autoapprove = 1, $msg_tdescr = '')
    {
        if (!$this->post_stamp) {
            $this->post_stamp = __request_timestamp__;
        }

        if (!isset($this->ip_addr)) {
            $this->ip_addr = get_ip();
        }
        $this->host_name = $GLOBALS['FUD_OPT_1'] & 268435456 ? _esc(get_host($this->ip_addr)) : 'NULL';
        $this->thread_id = isset($this->thread_id) ? $this->thread_id : 0;
        $this->reply_to = isset($this->reply_to) ? $this->reply_to : 0;
        $this->subject = substr($this->subject, 0, 255);    // Subject col is VARCHAR(255).

        if ($GLOBALS['FUD_OPT_3'] & 32768) {    // DB_MESSAGE_STORAGE
            $file_id = $file_id_preview = $length_preview = 0;
            $offset = $offset_preview = -1;
            $length = strlen($this->body);
        } else {
            $file_id = write_body($this->body, $length, $offset, $forum_id);

            /* Determine if preview needs building. */
            if ($message_threshold && $message_threshold < strlen($this->body)) {
                $thres_body = trim_html($this->body, $message_threshold);
                $file_id_preview = write_body($thres_body, $length_preview, $offset_preview, $forum_id);
            } else {
                $file_id_preview = $offset_preview = $length_preview = 0;
            }
        }

        /* Lookup country and flag. */
        if ($GLOBALS['FUD_OPT_3'] & 524288) {    // ENABLE_GEO_LOCATION.
            $flag = db_saq(
                'SELECT cc, country FROM fud30_geoip WHERE ' .
                sprintf('%u', ip2long($this->ip_addr)) .
                ' BETWEEN ips AND ipe'
            );
        }
        if (empty($flag)) {
            $flag = [null, null];
        }

        $this->id = db_qid(
            'INSERT INTO fud30_msg (
			thread_id,
			poster_id,
			reply_to,
			ip_addr,
			host_name,
			post_stamp,
			subject,
			attach_cnt,
			poll_id,
			icon,
			msg_opt,
			file_id,
			foff,
			length,
			file_id_preview,
			offset_preview,
			length_preview,
			mlist_msg_id,
			poll_cache,
			flag_cc,
			flag_country
		) VALUES(
			' . $this->thread_id . ',
			' . $this->poster_id . ',
			' . (int)$this->reply_to . ',
			\'' . $this->ip_addr . '\',
			' . $this->host_name . ',
			' . $this->post_stamp . ',
			' . ssn($this->subject) . ',
			' . (int)$this->attach_cnt . ',
			' . (int)$this->poll_id . ',
			' . ssn($this->icon) . ',
			' . $this->msg_opt . ',
			' . $file_id . ',
			' . (int)$offset . ',
			' . (int)$length . ',
			' . $file_id_preview . ',
			' . $offset_preview . ',
			' . $length_preview . ',
			' . ssn($this->mlist_msg_id) . ',
			' . ssn(poll_cache_rebuild($this->poll_id)) . ',
			' . ssn($flag[0]) . ',
			' . ssn($flag[1]) . '
		)'
        );

        if ($GLOBALS['FUD_OPT_3'] & 32768) {    // DB_MESSAGE_STORAGE
            $file_id = db_qid('INSERT INTO fud30_msg_store (data) VALUES(' . _esc($this->body) . ')');
            if ($message_threshold && $length > $message_threshold) {
                $file_id_preview = db_qid(
                    'INSERT INTO fud30_msg_store (data) VALUES(' .
                    _esc(trim_html($this->body, $message_threshold)) .
                    ')'
                );
            }
            q(
                'UPDATE fud30_msg SET file_id=' .
                $file_id .
                ', file_id_preview=' .
                $file_id_preview .
                ' WHERE id=' .
                $this->id
            );
        }

        $thread_opt = (int)($perm & 4096 && isset($_POST['thr_locked']));

        if (!$this->thread_id) { /* New thread. */
            if ($perm & 64) {
                if (isset($_POST['thr_ordertype'], $_POST['thr_orderexpiry']) && (int)$_POST['thr_ordertype']) {
                    $thread_opt |= (int)$_POST['thr_ordertype'];
                    $thr_orderexpiry = (int)$_POST['thr_orderexpiry'];
                }
                if (!empty($_POST['thr_always_on_top'])) {
                    $thread_opt |= 8;
                }
            }

            $this->thread_id = th_add(
                $this->id,
                $forum_id,
                $this->post_stamp,
                $thread_opt,
                (isset($thr_orderexpiry) ? $thr_orderexpiry : 0),
                0,
                0,
                0,
                $msg_tdescr
            );

            q('UPDATE fud30_msg SET thread_id=' . $this->thread_id . ' WHERE id=' . $this->id);
        } else {
            th_lock($this->thread_id, $thread_opt & 1);
        }

        if ($autoapprove && $forum_opt & 2) {
            $this->approve($this->id);
        }

        return $this->id;
    }

    function sync($id, $frm_id, $message_threshold, $perm, $msg_tdescr = '')
    {
        $this->subject = substr($this->subject, 0, 255);    // Subject col is VARCHAR(255).

        if ($GLOBALS['FUD_OPT_3'] & 32768) {    // DB_MESSAGE_STORAGE
            $file_id = $file_id_preview = $length_preview = 0;
            $offset = $offset_preview = -1;
            $length = strlen($this->body);
        } else {
            $file_id = write_body($this->body, $length, $offset, $frm_id);

            /* Determine if preview needs building. */
            if ($message_threshold && $message_threshold < strlen($this->body)) {
                $thres_body = trim_html($this->body, $message_threshold);
                $file_id_preview = write_body($thres_body, $length_preview, $offset_preview, $frm_id);
            } else {
                $file_id_preview = $offset_preview = $length_preview = 0;
            }
        }

        q(
            'UPDATE fud30_msg SET
			file_id=' . $file_id . ',
			foff=' . (int)$offset . ',
			length=' . (int)$length . ',
			mlist_msg_id=' . ssn($this->mlist_msg_id) . ',
			file_id_preview=' . $file_id_preview . ',
			offset_preview=' . $offset_preview . ',
			length_preview=' . $length_preview . ',
			updated_by=' . $id . ',
			msg_opt=' . $this->msg_opt . ',
			attach_cnt=' . (int)$this->attach_cnt . ',
			poll_id=' . (int)$this->poll_id . ',
			update_stamp=' . __request_timestamp__ . ',
			icon=' . ssn($this->icon) . ' ,
			poll_cache=' . ssn(poll_cache_rebuild($this->poll_id)) . ',
			subject=' . ssn($this->subject) . '
		WHERE id=' . $this->id
        );

        if ($GLOBALS['FUD_OPT_3'] & 32768) {    // DB_MESSAGE_STORAGE
//TODO: Why DELETE? Can't we just UPDATE the DB?
            q('DELETE FROM fud30_msg_store WHERE id IN(' . $this->file_id . ',' . $this->file_id_preview . ')');
            $file_id = db_qid('INSERT INTO fud30_msg_store (data) VALUES(' . _esc($this->body) . ')');
            if ($message_threshold && $length > $message_threshold) {
                $file_id_preview = db_qid(
                    'INSERT INTO fud30_msg_store (data) VALUES(' .
                    _esc(trim_html($this->body, $message_threshold)) .
                    ')'
                );
            }
            q(
                'UPDATE fud30_msg SET file_id=' .
                $file_id .
                ', file_id_preview=' .
                $file_id_preview .
                ' WHERE id=' .
                $this->id
            );
        }

        /* Determine wether or not we should deal with locked & sticky stuff
		 * current approach may seem a little redundant, but for (most) users who
		 * do not have access to locking & sticky this eliminated a query.
		 */
        $th_data = db_saq(
            'SELECT orderexpiry, thread_opt, root_msg_id, tdescr FROM fud30_thread WHERE id=' . $this->thread_id
        );
        $locked = (int)isset($_POST['thr_locked']);
        if (isset($_POST['thr_ordertype'], $_POST['thr_orderexpiry']) || (($th_data[1] ^ $locked) & 1)) {
            $thread_opt = (int)$th_data[1];
            $orderexpiry = isset($_POST['thr_orderexpiry']) ? (int)$_POST['thr_orderexpiry'] : 0;

            /* Confirm that user has ability to change lock status of the thread. */
            if ($perm & 4096) {
                if ($locked && !($thread_opt & $locked)) {
                    $thread_opt |= 1;
                } else {
                    if (!$locked && $thread_opt & 1) {
                        $thread_opt &= ~1;
                    }
                }
            }

            /* Confirm that user has ability to change sticky status of the thread. */
            if ($th_data[2] == $this->id && isset($_POST['thr_ordertype'], $_POST['thr_orderexpiry']) && $perm & 64) {
                if (!$_POST['thr_ordertype'] && $thread_opt > 1) {
                    $orderexpiry = 0;
                    $thread_opt &= ~6;
                } else {
                    if ($thread_opt < 2 && (int)$_POST['thr_ordertype']) {
                        $thread_opt |= $_POST['thr_ordertype'];
                    } else {
                        if (!($thread_opt & (int)$_POST['thr_ordertype'])) {
                            $thread_opt = $_POST['thr_ordertype'] | ($thread_opt & 1);
                        }
                    }
                }
            }

            if ($perm & 64) {
                if (!empty($_POST['thr_always_on_top'])) {
                    $thread_opt |= 8;
                } else {
                    $thread_opt &= ~8;
                }
            }

            /* Determine if any work needs to be done. */
            if ($thread_opt != $th_data[1] || $orderexpiry != $th_data[0]) {
                q(
                    'UPDATE fud30_thread SET ' .
                    ($th_data[2] == $this->id ? 'tdescr=' . _esc($msg_tdescr) . ',' : '') .
                    ' thread_opt=' .
                    $thread_opt .
                    ', orderexpiry=' .
                    $orderexpiry .
                    ' WHERE id=' .
                    $this->thread_id
                );
                /* Avoid rebuilding the forum view whenever possible, since it's a rather slow process.
				 * Only rebuild if expiry time has changed or message gained/lost sticky status.
				 */
                $diff = $thread_opt ^ $th_data[1];
                if (($diff > 1 && $diff & 14) || $orderexpiry != $th_data[0]) {
                    rebuild_forum_view_ttl($frm_id);
                }
            } else {
                if ($msg_tdescr != $th_data[3] && $th_data[2] == $this->id) {
                    q('UPDATE fud30_thread SET tdescr=' . _esc($msg_tdescr) . ' WHERE id=' . $this->thread_id);
                }
            }
        } else {
            if ($msg_tdescr != $th_data[3] && $th_data[2] == $this->id) {
                q('UPDATE fud30_thread SET tdescr=' . _esc($msg_tdescr) . ' WHERE id=' . $this->thread_id);
            }
        }

        if ($GLOBALS['FUD_OPT_1'] & 16777216) {    // FORUM_SEARCH enabled? If so, reindex message.
            q('DELETE FROM fud30_index WHERE msg_id=' . $this->id);
            q('DELETE FROM fud30_title_index WHERE msg_id=' . $this->id);
            index_text((!strncasecmp('Re: ', $this->subject, 4) ? '' : $this->subject), $this->body, $this->id);
        }
    }

    /**  Delete a message & cleanup. */
    static function delete($rebuild_view = 1, $mid = 0, $th_rm = 0)
    {
        if (!$mid) {
            $mid = $this->id;
        }

        if (!($del = db_sab(
            'SELECT m.file_id, m.file_id_preview, m.id, m.attach_cnt, m.poll_id, m.thread_id, m.reply_to, m.apr, m.poster_id, t.replies, t.root_msg_id AS root_msg_id, t.last_post_id AS thread_lip, t.forum_id, f.last_post_id AS forum_lip 
					FROM fud30_msg m 
					LEFT JOIN fud30_thread t ON m.thread_id=t.id 
					LEFT JOIN fud30_forum f ON t.forum_id=f.id WHERE m.id=' . $mid
        ))) {
            return;
        }

        if (!db_locked()) {
            db_lock(
                'fud30_msg_store WRITE, fud30_forum f WRITE, fud30_thr_exchange WRITE, fud30_tv_' .
                $del->forum_id .
                ' WRITE, fud30_tv_' .
                $del->forum_id .
                ' tv WRITE, fud30_msg m WRITE, fud30_thread t WRITE, fud30_level WRITE, fud30_forum WRITE, fud30_forum_read WRITE, fud30_thread WRITE, fud30_msg WRITE, fud30_attach WRITE, fud30_poll WRITE, fud30_poll_opt WRITE, fud30_poll_opt_track WRITE, fud30_users WRITE, fud30_thread_notify WRITE, fud30_bookmarks WRITE, fud30_msg_report WRITE, fud30_thread_rate_track WRITE, fud30_index WRITE, fud30_title_index WRITE, fud30_search_cache WRITE'
            );
            $ll = 1;
        }

        q('DELETE FROM fud30_msg WHERE id=' . $mid);

        /* Remove attachments. */
        if ($del->attach_cnt) {
            $res = q('SELECT location FROM fud30_attach WHERE message_id=' . $mid . ' AND attach_opt=0');
            while ($loc = db_rowarr($res)) {
                @unlink($loc[0]);
            }
            unset($res);
            q('DELETE FROM fud30_attach WHERE message_id=' . $mid . ' AND attach_opt=0');
        }

        /* Remove message reports. */
        q('DELETE FROM fud30_msg_report WHERE msg_id=' . $mid);

        /* Cleanup index entries. */
        if ($GLOBALS['FUD_OPT_1'] & 16777216) {    // FORUM_SEARCH enabled?
            q('DELETE FROM fud30_index WHERE msg_id=' . $mid);
            q('DELETE FROM fud30_title_index WHERE msg_id=' . $mid);
            q('DELETE FROM fud30_search_cache WHERE msg_id=' . $mid);
        }

        /* Remove poll. */
        if ($del->poll_id) {
            poll_delete($del->poll_id);
        }

        /* Check if thread. */
        if ($del->root_msg_id == $del->id) {
            $th_rm = 1;
            /* Delete all messages in the thread if there is more than 1 message. */
            if ($del->replies) {
                $rmsg = q('SELECT id FROM fud30_msg WHERE thread_id=' . $del->thread_id . ' AND id != ' . $del->id);
                while ($dim = db_rowarr($rmsg)) {
                    fud_msg_edit::delete(0, $dim[0], 1);
                }
                unset($rmsg);
            }

            q('DELETE FROM fud30_thread_notify WHERE thread_id=' . $del->thread_id);
            q('DELETE FROM fud30_bookmarks WHERE thread_id=' . $del->thread_id);
            q('DELETE FROM fud30_thread WHERE id=' . $del->thread_id);
            q('DELETE FROM fud30_thread_rate_track WHERE thread_id=' . $del->thread_id);
            q('DELETE FROM fud30_thr_exchange WHERE th=' . $del->thread_id);

            if ($del->apr) {
                /* We need to determine the last post id for the forum, it can be null. */
                $lpi = (int)q_singleval(
                    q_limit(
                        'SELECT t.last_post_id FROM fud30_thread t INNER JOIN fud30_msg m ON t.last_post_id=m.id AND m.apr=1 WHERE t.forum_id=' .
                        $del->forum_id .
                        ' AND t.moved_to=0 ORDER BY m.post_stamp DESC',
                        1
                    )
                );
                q(
                    'UPDATE fud30_forum SET last_post_id=' .
                    $lpi .
                    ', thread_count=thread_count-1, post_count=post_count-' .
                    $del->replies .
                    '-1 WHERE id=' .
                    $del->forum_id
                );
            }
        } else {
            if (!$th_rm && $del->apr) {
                q(
                    'UPDATE fud30_msg SET reply_to=' .
                    $del->reply_to .
                    ' WHERE thread_id=' .
                    $del->thread_id .
                    ' AND reply_to=' .
                    $mid
                );

                /* Check if the message is the last in thread. */
                if ($del->thread_lip == $del->id) {
                    [$lpi, $lpd] = db_saq(
                        q_limit(
                            'SELECT id, post_stamp FROM fud30_msg WHERE thread_id=' .
                            $del->thread_id .
                            ' AND apr=1 ORDER BY post_stamp DESC',
                            1
                        )
                    );
                    q(
                        'UPDATE fud30_thread SET last_post_id=' .
                        $lpi .
                        ', last_post_date=' .
                        $lpd .
                        ', replies=replies-1 WHERE id=' .
                        $del->thread_id
                    );
                } else {
                    q('UPDATE fud30_thread SET replies=replies-1 WHERE id=' . $del->thread_id);
                }

                /* Check if the message is the last in the forum. */
                if ($del->forum_lip == $del->id) {
                    $page = q_singleval(
                        'SELECT seq FROM fud30_tv_' . $del->forum_id . ' WHERE thread_id=' . $del->thread_id
                    );
                    $lp = db_saq(
                        q_limit(
                            'SELECT t.last_post_id, t.last_post_date 
					FROM fud30_tv_' . $del->forum_id . ' tv
					INNER JOIN fud30_thread t ON tv.thread_id=t.id 
					WHERE tv.seq IN(' . $page . ',' . ($page - 1) . ') AND t.moved_to=0 ORDER BY t.last_post_date DESC',
                            1
                        )
                    );
                    if (!isset($lpd) || $lp[1] > $lpd) {
                        $lpi = $lp[0];
                    }
                    q(
                        'UPDATE fud30_forum SET post_count=post_count-1, last_post_id=' .
                        $lpi .
                        ' WHERE id=' .
                        $del->forum_id
                    );
                } else {
                    q('UPDATE fud30_forum SET post_count=post_count-1 WHERE id=' . $del->forum_id);
                }
            }
        }

        if ($del->apr) {
            if ($del->poster_id) {
                user_set_post_count($del->poster_id);
            }
            if ($rebuild_view) {
                if ($th_rm) {
                    th_delete_rebuild($del->forum_id, $del->thread_id);
                } else {
                    if ($del->thread_lip == $del->id) {
                        rebuild_forum_view_ttl($del->forum_id);
                    }
                }
            }
        }
        if (isset($ll)) {
            db_unlock();
        }

        if ($GLOBALS['FUD_OPT_3'] & 32768) {    // DB_MESSAGE_STORAGE
            q('DELETE FROM fud30_msg_store WHERE id IN(' . $del->file_id . ',' . $del->file_id_preview . ')');
        }

        if (!$del->apr || !$th_rm || ($del->root_msg_id != $del->id)) {
            return;
        }

        /* Needed for moved thread pointers. */
        $r = q('SELECT forum_id, id FROM fud30_thread WHERE root_msg_id=' . $del->root_msg_id);
        while (($res = db_rowarr($r))) {
            q('DELETE FROM fud30_thread WHERE id=' . $res[1]);
            q('UPDATE fud30_forum SET thread_count=thread_count-1 WHERE id=' . $res[0]);
            th_delete_rebuild($res[0], $res[1]);
        }
        unset($r);
    }

    static function approve($id)
    {
        /* Fetch info about the message, poll (if one exists), thread & forum. */
        $mtf = db_sab(
            'SELECT /* USE MASTER */
					m.id, m.poster_id, m.apr, m.subject, m.foff, m.length, m.file_id, m.thread_id, m.poll_id, m.attach_cnt,
					m.post_stamp, m.reply_to, m.mlist_msg_id, m.msg_opt,
					t.forum_id, t.last_post_id, t.root_msg_id, t.last_post_date, t.thread_opt,
					m2.post_stamp AS frm_last_post_date,
					f.name AS frm_name, f.forum_opt,
					u.alias, u.email, u.sig, u.name as real_name,
					n.id AS nntp_id, ml.id AS mlist_id
				FROM fud30_msg m
				INNER JOIN fud30_thread t ON m.thread_id=t.id
				INNER JOIN fud30_forum f ON t.forum_id=f.id
				LEFT JOIN fud30_msg m2 ON f.last_post_id=m2.id
				LEFT JOIN fud30_users u ON m.poster_id=u.id
				LEFT JOIN fud30_mlist ml ON ml.forum_id=f.id AND ' . q_bitand('ml.mlist_opt', 2) . ' > 0
				LEFT JOIN fud30_nntp n ON n.forum_id=f.id AND ' . q_bitand('n.nntp_opt', 2) . ' > 0
				WHERE m.id=' . $id . ' AND m.apr=0'
        );

        /* Nothing to do or bad message id. */
        if (!$mtf) {
            return;
        }

        if ($mtf->alias) {
            $mtf->alias = reverse_fmt($mtf->alias);
        } else {
            $mtf->alias = $GLOBALS['ANON_NICK'];
        }

        q('UPDATE fud30_msg SET apr=1 WHERE id=' . $mtf->id);

        if ($mtf->poster_id) {
            user_set_post_count($mtf->poster_id);
        }

        if ($mtf->post_stamp > $mtf->frm_last_post_date) {
            $mtf->last_post_id = $mtf->id;
        }

        if ($mtf->root_msg_id == $mtf->id) {    /* New thread. */
            th_new_rebuild($mtf->forum_id, $mtf->thread_id, $mtf->thread_opt & (2 | 4 | 8));
            $threads = 1;
        } else {                /* Reply to thread. */
            if ($mtf->post_stamp > $mtf->last_post_date) {
                th_inc_post_count($mtf->thread_id, 1, $mtf->id, $mtf->post_stamp);
            } else {
                th_inc_post_count($mtf->thread_id, 1);
            }
            th_reply_rebuild($mtf->forum_id, $mtf->thread_id, $mtf->thread_opt & (2 | 4 | 8));
            $threads = 0;
        }

        /* Update forum thread & post count as well as last_post_id field. */
        q(
            'UPDATE fud30_forum SET post_count=post_count+1, thread_count=thread_count+' .
            $threads .
            ', last_post_id=' .
            $mtf->last_post_id .
            ' WHERE id=' .
            $mtf->forum_id
        );

        if ($mtf->poll_id) {
            poll_activate($mtf->poll_id, $mtf->forum_id);
        }

        $mtf->body = read_msg_body($mtf->foff, $mtf->length, $mtf->file_id);

        if ($GLOBALS['FUD_OPT_1'] & 16777216) {    // FORUM_SEARCH enabled?
            index_text((strncasecmp($mtf->subject, 'Re: ', 4) ? $mtf->subject : ''), $mtf->body, $mtf->id);
        }

        /* Handle notifications. */
        if (!($GLOBALS['FUD_OPT_3'] & 1048576)) {    // not DISABLE_NOTIFICATION_EMAIL
            if ($mtf->root_msg_id == $mtf->id || $GLOBALS['FUD_OPT_3'] & 16384) {    // FORUM_NOTIFY_ALL
                if (empty($mtf->frm_last_post_date)) {
                    $mtf->frm_last_post_date = 0;
                }

                /* Send new thread notifications to forum subscribers. */
                $to = db_all(
                    'SELECT u.email
						FROM fud30_forum_notify fn
						INNER JOIN fud30_users u ON fn.user_id=u.id AND ' .
                    q_bitand('u.users_opt', 134217728) .
                    ' = 0
						INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id=' .
                    $mtf->forum_id .
                    ($GLOBALS['FUD_OPT_3'] &
                    64 ? ' LEFT JOIN fud30_forum_read r ON r.forum_id=fn.forum_id AND r.user_id=fn.user_id ' : '') .
                    ' LEFT JOIN fud30_group_cache g2 ON g2.user_id=fn.user_id AND g2.resource_id=' .
                    $mtf->forum_id .
                    ' LEFT JOIN fud30_mod mm ON mm.forum_id=' .
                    $mtf->forum_id .
                    ' AND mm.user_id=u.id
					WHERE
						fn.forum_id=' .
                    $mtf->forum_id .
                    ' AND fn.user_id!=' .
                    (int)$mtf->poster_id .
                    ($GLOBALS['FUD_OPT_3'] &
                    64 ? ' AND (CASE WHEN (r.last_view IS NULL AND (u.last_read=0 OR u.last_read >= ' .
                        $mtf->frm_last_post_date .
                        ')) OR r.last_view > ' .
                        $mtf->frm_last_post_date .
                        ' THEN 1 ELSE 0 END)=1 ' : '') .
                    ' AND (' .
                    q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 2) .
                    ' > 0 OR ' .
                    q_bitand('u.users_opt', 1048576) .
                    ' > 0 OR mm.id IS NOT NULL)' .
                    ' AND ' .
                    q_bitand('u.users_opt', 65536) .
                    ' = 0'
                );
                if ($GLOBALS['FUD_OPT_3'] & 16384) {
                    $notify_type = 'thr';
                } else {
                    $notify_type = 'frm';
                }
            } else {
                $to = [];
            }
            if ($mtf->root_msg_id != $mtf->id) {
                /* Send new reply notifications to thread subscribers. */
                $tmp = db_all(
                    'SELECT u.email
						FROM fud30_thread_notify tn
						INNER JOIN fud30_users u ON tn.user_id=u.id AND ' .
                    q_bitand('u.users_opt', 134217728) .
                    ' = 0
						INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id=' .
                    $mtf->forum_id .
                    ($GLOBALS['FUD_OPT_3'] &
                    64 ? ' LEFT JOIN fud30_read r ON r.thread_id=tn.thread_id AND r.user_id=tn.user_id ' : '') .
                    ' LEFT JOIN fud30_group_cache g2 ON g2.user_id=tn.user_id AND g2.resource_id=' .
                    $mtf->forum_id .
                    ' LEFT JOIN fud30_mod mm ON mm.forum_id=' .
                    $mtf->forum_id .
                    ' AND mm.user_id=u.id
					WHERE
						tn.thread_id=' .
                    $mtf->thread_id .
                    ' AND tn.user_id!=' .
                    (int)$mtf->poster_id .
                    ($GLOBALS['FUD_OPT_3'] & 64 ? ' AND (r.msg_id=' .
                        $mtf->last_post_id .
                        ' OR (r.msg_id IS NULL AND ' .
                        $mtf->post_stamp .
                        ' > u.last_read)) ' : '') .
                    ' AND (' .
                    q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 2) .
                    ' > 0 OR ' .
                    q_bitand('u.users_opt', 1048576) .
                    ' > 0 OR mm.id IS NOT NULL)' .
                    ' AND ' .
                    q_bitand('u.users_opt', 65536) .
                    ' = 0'
                );
                $to = !$to ? $tmp : array_unique(array_merge($to, $tmp));
                $notify_type = 'thr';
            }

            if ($mtf->forum_opt & 64) {    // always_notify_mods
                $tmp = db_all(
                    'SELECT u.email FROM fud30_mod mm INNER JOIN fud30_users u ON u.id=mm.user_id WHERE mm.forum_id=' .
                    $mtf->forum_id
                );
                $to = !$to ? $tmp : array_unique(array_merge($to, $tmp));
            }

            if ($to) {
                send_notifications(
                    $to,
                    $mtf->id,
                    $mtf->subject,
                    $mtf->alias,
                    $notify_type,
                    ($notify_type == 'thr' ? $mtf->thread_id : $mtf->forum_id),
                    $mtf->frm_name,
                    $mtf->forum_id
                );
            }
        }

        // Handle Mailing List and/or Newsgroup syncronization.
        if (($mtf->nntp_id || $mtf->mlist_id) && !$mtf->mlist_msg_id) {
            fud_use('email_msg_format.inc', 1);

            $from = $mtf->poster_id ? reverse_fmt($mtf->real_name) . ' <' . $mtf->email . '>' : $GLOBALS['ANON_NICK'] .
                ' <' .
                $GLOBALS['NOTIFY_FROM'] .
                '>';
            $body = $mtf->body . (($mtf->msg_opt & 1 && $mtf->sig) ? "\n-- \n" . $mtf->sig : '');
            $body = plain_text($body, '<cite>', '</cite><blockquote>', '</blockquote>');
            $mtf->subject = reverse_fmt($mtf->subject);

            if ($mtf->reply_to) {
                // Get the parent message's Message-ID:
                if (!($replyto_id = q_singleval('SELECT mlist_msg_id FROM fud30_msg WHERE id=' . $mtf->reply_to))) {
                    fud_logerror(
                        'WARNING: Send reply with no Message-ID. The import script is not running or may be lagging.',
                        'fud_errors'
                    );
                }
            } else {
                $replyto_id = 0;
            }

            if ($mtf->attach_cnt) {
                $r = uq(
                    'SELECT a.id, a.original_name, COALESCE(m.mime_hdr, \'application/octet-stream\')
						FROM fud30_attach a
						LEFT JOIN fud30_mime m ON a.mime_type=m.id
						WHERE a.message_id=' . $mtf->id . ' AND a.attach_opt=0'
                );
                while ($ent = db_rowarr($r)) {
                    $attach[$ent[1]] = file_get_contents($GLOBALS['FILE_STORE'] . $ent[0] . '.atch');
                    $attach_mime[$ent[1]] = $ent[2];
                }
                unset($r);
            } else {
                $attach_mime = $attach = null;
            }

            if ($mtf->nntp_id) {    // Push out to usenet group.
                fud_use('nntp.inc', true);

                $nntp_adm = db_sab('SELECT * FROM fud30_nntp WHERE id=' . $mtf->nntp_id);
                if (!empty($nntp_adm->custom_sig)) {    // Add signature marker.
                    $nntp_adm->custom_sig = "\n-- \n" . $nntp_adm->custom_sig;
                }

                $nntp = new fud_nntp;
                $nntp->server = $nntp_adm->server;
                $nntp->newsgroup = $nntp_adm->newsgroup;
                $nntp->port = $nntp_adm->port;
                $nntp->timeout = $nntp_adm->timeout;
                $nntp->nntp_opt = $nntp_adm->nntp_opt;
                $nntp->user = $nntp_adm->login;
                $nntp->pass = $nntp_adm->pass;

                define('sql_p', 'fud30_');

                $lock = $nntp->get_lock();
                $nntp->post_message(
                    $mtf->subject,
                    $body . $nntp_adm->custom_sig,
                    $from,
                    $mtf->id,
                    $replyto_id,
                    $attach,
                    $attach_mime
                );
                $nntp->close_connection();
                $nntp->release_lock($lock);
            } else {    // Push out to mailing list.
                fud_use('mlist_post.inc', true);

                $r = db_saq(
                    'SELECT name, additional_headers, custom_sig, fixed_from_address FROM fud30_mlist WHERE id=' .
                    $mtf->mlist_id
                );

                // Add forum's signature to the messages.
                if (!empty($r[2])) {
                    $body .= "\n-- \n" . $r[2];
                }

                if (!empty($r[3])) {    // Use the forum's fixed "From:" address.
                    mail_list_post(
                        $r[0],
                        $r[3],
                        $mtf->subject,
                        $body,
                        $mtf->id,
                        $replyto_id,
                        $attach,
                        $attach_mime,
                        $r[1]
                    );
                } else {                // Use poster's e-mail as the "From" address.
                    mail_list_post(
                        $r[0],
                        $from,
                        $mtf->subject,
                        $body,
                        $mtf->id,
                        $replyto_id,
                        $attach,
                        $attach_mime,
                        $r[1]
                    );
                }
            }
        }

        // Message Approved plugins.
        if (defined('plugins')) {
            plugin_call_hook('POST_APPROVE', $mtf);
        }
    }
}

function write_body($data, &$len, &$offset, $fid)
{
    $MAX_FILE_SIZE = 2140000000;

    $len = strlen($data);
    $i = 1;

    db_lock('fud30_fl_' . $fid . ' WRITE');

    $s = $fid * 10000;
    $e = $s + 100;

    while ($s < $e) {
        $fp = fopen($GLOBALS['MSG_STORE_DIR'] . 'msg_' . $s, 'ab');
        if (!$fp) {
            exit('FATAL ERROR: could not open message store for forum id#' . $s . "<br />\n");
        }
        fseek($fp, 0, SEEK_END);
        if (!($off = ftell($fp))) {
            $off = __ffilesize($fp);
        }
        if (!$off || ($off + $len) < $MAX_FILE_SIZE) {
            break;
        }
        fclose($fp);
        $s++;
    }

    if (fwrite($fp, $data) !== $len) {
        if ($fid) {
            db_unlock();
        }
        exit("FATAL ERROR: system has ran out of disk space.<br />\n");
    }
    fclose($fp);

    db_unlock();

    if (!$off) {
        @chmod('msg_' . $s, ($GLOBALS['FUD_OPT_2'] & 8388608 ? 0600 : 0644));
    }
    $offset = $off;

    return $s;
}

function trim_html($str, $maxlen)
{
    $n = strlen($str);
    $ln = 0;
    $tree = [];
    for ($i = 0; $i < $n; $i++) {
        if ($str[$i] != '<') {
            $ln++;
            if ($ln > $maxlen) {
                break;
            }
            continue;
        }

        if (($p = strpos($str, '>', $i)) === false) {
            break;
        }

        for ($k = $i; $k < $p; $k++) {
            switch ($str[$k]) {
                case ' ':
                case "\r":
                case "\n":
                case "\t":
                case '>':
                    break 2;
            }
        }

        if ($str[$i + 1] == '/') {
            $tagname = strtolower(substr($str, $i + 2, $k - $i - 2));
            if (@end($tagindex[$tagname])) {
                $k = key($tagindex[$tagname]);
                unset($tagindex[$tagname][$k], $tree[$k]);
            }
        } else {
            $tagname = strtolower(substr($str, $i + 1, $k - $i - 1));
            switch ($tagname) {
                case 'br':
                case 'img':
                case 'meta':
                    break;
                default:
                    $tree[] = $tagname;
                    end($tree);
                    $tagindex[$tagname][key($tree)] = 1;
            }
        }
        $i = $p;
    }

    $data = substr($str, 0, $i);
    if ($tree) {
        foreach (array_reverse($tree) as $v) {
            $data .= '</' . $v . '>';
        }
    }

    return $data;
}

function make_email_message(&$body, &$obj, $iemail_unsub)
{
    $TITLE_EXTRA = $iemail_poll = $iemail_attach = '';
    if ($obj->poll_cache) {
        $pl = unserialize($obj->poll_cache);
        if (!empty($pl)) {
            foreach ($pl as $k => $v) {
                $length = ($v[1] && $obj->total_votes) ? round($v[1] / $obj->total_votes * 100) : 0;
                $iemail_poll .= '<tr class="' . alt_var('msg_poll_alt_clr', 'RowStyleB', 'RowStyleA') . '">
	<td>' . $k . '.</td>
	<td>' . $v[0] . '</td>
	<td>
		<img src="/theme/twig/images/poll_pix.gif" alt="" height="10" width="' . $length . '" />
		' . $v[1] . ' / ' . $length . '%
	</td>
</tr>';
            }
            $iemail_poll = '<table cellspacing="1" cellpadding="2" class="PollTable">
<tr>
	<th colspan="3">' . $obj->poll_name . '
		<img src="/blank.gif" alt="" height="1" width="10" class="nw" />
		<span class="small">[ ' . $obj->total_votes . ' ' . convertPlural($obj->total_votes, ['vote', 'votes']) . ' ]</span>
	</th>
</tr>
' . $iemail_poll . '
</table>
<br /><br />';
        }
    }
    if ($obj->attach_cnt && $obj->attach_cache) {
        $atch = unserialize($obj->attach_cache);
        if (!empty($atch)) {
            foreach ($atch as $v) {
                $sz = $v[2] / 1024;
                $sz = $sz < 1000 ? number_format($sz, 2) . 'KB' : number_format($sz / 1024, 2) . 'MB';
                $iemail_attach .= '<tr>
	<td class="vm"><a href="https://forum.wigedev.com/index.php?t=getfile&amp;id=' .
                    $v[0] .
                    '"><img alt="" src="/images/mime/' .
                    $v[4] .
                    '" /></a></td>
	<td>
		<span class="GenText fb">Attachment:</span> <a href="https://forum.wigedev.com/index.php?t=getfile&amp;id=' .
                    $v[0] .
                    '">' .
                    $v[1] .
                    '</a><br />
		<span class="SmallText">(Size: ' .
                    $sz .
                    ', Downloaded ' .
                    convertPlural($v[3], ['' . $v[3] . ' time', '' . $v[3] . ' times']) .
                    ')</span>
	</td>
</tr>';
            }
            $iemail_attach = '<br /><br />
<table border="0" cellspacing="0" cellpadding="2">
	' . $iemail_attach . '
</table>';
        }
    }

    if ($GLOBALS['FUD_OPT_2'] & 32768 && defined('_rsid')) {
        $pfx = str_repeat('/', substr_count(_rsid, '/'));
    }

    // Remove all JavaScript. Spam filters like SpamAssassin don't like them.
    return preg_replace(
        '#<script[^>]*>.*?</script>#is',
        '',
        '<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' .
        $GLOBALS['FORUM_TITLE'] .
        $TITLE_EXTRA .
        '</title>
<script src="/js/lib.js"></script>
<script async src="/js/jquery.js"></script>
<script async src="/js/ui/jquery-ui.js"></script>
<link rel="stylesheet" href="/theme/twig/forum.css" />
</head>
<body>
<div class="content">
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr class="RowStyleB">
	<td width="33%"><b>Subject:</b> ' .
        $obj->subject .
        '</td>
	<td width="33%"><b>Author:</b> ' .
        $obj->alias .
        '</td>
	<td width="33%"><b>Date:</b> ' .
        strftime('%a, %d %B %Y %H:%M', $obj->post_stamp) .
        '</td>
</tr>
<tr class="RowStyleA">
	<td colspan="3">
		' .
        $iemail_poll .
        '
		' .
        $body .
        '
		' .
        $iemail_attach .
        '
	</td>
</tr>
<tr class="RowStyleB">
	<td colspan="3">
		[ <a href="https://forum.wigedev.com/index.php?t=post&reply_to=' .
        $obj->id .
        '">Reply</a> ][ <a href="https://forum.wigedev.com/index.php?t=post&reply_to=' .
        $obj->id .
        '&quote=true">Quote</a> ][ <a href="https://forum.wigedev.com/index.php?t=rview&goto=' .
        $obj->id .
        '#msg_' .
        $obj->id .
        '">View Topic/Message</a> ]' .
        $iemail_unsub .
        '
	</td>
</tr>
</table>
</div>
</body></html>'
    );
}

function poll_cache_rebuild($poll_id)
{
    if (!$poll_id) {
        return;
    }

    $data = [];
    $c = uq('SELECT id, name, votes FROM fud30_poll_opt WHERE poll_id=' . $poll_id);
    while ($r = db_rowarr($c)) {
        $data[$r[0]] = [$r[1], $r[2]];
    }
    unset($c);

    if ($data) {
        return serialize($data);
    } else {
        return;
    }
}

function send_notifications($to, $msg_id, $thr_subject, $poster_login, $id_type, $id, $frm_name, $frm_id)
{
    if (!$to) {
        return;
    }

    $goto_url['email'] = '' . $GLOBALS['WWW_ROOT'] . '?t=rview&goto=' . $msg_id . '#msg_' . $msg_id;
    $CHARSET = $GLOBALS['CHARSET'];
    if ($GLOBALS['FUD_OPT_2'] & 64) {    // NOTIFY_WITH_BODY
        $munge_newlines = 0;
        $obj = db_sab(
            'SELECT p.total_votes, p.name AS poll_name, m.reply_to, m.subject, m.id, m.post_stamp, m.poster_id, m.foff, m.length, m.file_id, u.alias, m.attach_cnt, m.attach_cache, m.poll_cache FROM fud30_msg m LEFT JOIN fud30_users u ON m.poster_id=u.id LEFT JOIN fud30_poll p ON m.poll_id=p.id WHERE m.id=' .
            $msg_id .
            ' AND m.apr=1'
        );

        if (!$obj->alias) { /* anon user */
            $obj->alias = htmlspecialchars($GLOBALS['ANON_NICK']);
        }

        $headers = "MIME-Version: 1.0\r\n";
        if ($obj->reply_to) {
            $headers .= 'In-Reply-To: ' . $obj->reply_to . "\r\n";
        }
        $headers .= 'List-Id: ' .
            $frm_id .
            '.' .
            (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost') .
            "\r\n";
        $split = get_random_value(128);
        $headers .= "Content-Type: multipart/alternative;\n  boundary=\"------------" . $split . "\"\r\n";
        $boundry = "\r\n--------------" . $split . "\r\n";

        $pfx = '';
        if ($GLOBALS['FUD_OPT_2'] & 32768 && !empty($_SERVER['PATH_INFO'])) {
            if ($GLOBALS['FUD_OPT_1'] & 128) {
                $pfx .= '0/';
            }
            if ($GLOBALS['FUD_OPT_2'] & 8192) {
                $pfx .= '0/';
            }
        }

        $plain_text = read_msg_body($obj->foff, $obj->length, $obj->file_id);
        $iemail_unsub = html_entity_decode(
            $id_type == 'thr' ? '[ <a href="https://forum.wigedev.com/index.php?t=rview&th=' .
                $id .
                '">Unsubscribe from this topic</a> ]' : '[ <a href="https://forum.wigedev.com/index.php?t=rview&frm_id=' .
                $id .
                '">Unsubscribe from this forum</a> ]'
        );

        $body_email = $boundry .
            'Content-Type: text/plain; charset=' .
            $CHARSET .
            "; format=flowed\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" .
            html_entity_decode(strip_tags($plain_text)) .
            "\r\n\r\n" .
            html_entity_decode('To participate in the discussion, go here:') .
            ' ' .
            '' .
            $GLOBALS['WWW_ROOT'] .
            '?t=rview&' .
            ($id_type == 'thr' ? 'th' : 'frm_id') .
            '=' .
            $id .
            "\r\n" .
            $boundry .
            'Content-Type: text/html; charset=' .
            $CHARSET .
            "\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" .
            make_email_message($plain_text, $obj, $iemail_unsub) .
            "\r\n" .
            substr($boundry, 0, -2) .
            "--\r\n";
    } else {
        $munge_newlines = 1;
        $headers = '';
    }

    $thr_subject = reverse_fmt($thr_subject);
    $poster_login = reverse_fmt($poster_login);

    if ($id_type == 'thr') {
        $subj = html_entity_decode('New reply to ' . $thr_subject . ' by ' . $poster_login);

        if (!isset($body_email)) {
            $unsub_url['email'] = '' . $GLOBALS['WWW_ROOT'] . '?t=rview&th=' . $id . '&notify=1&opt=off';
            $body_email = html_entity_decode(
                'To view unread replies go to ' .
                $goto_url['email'] .
                '\n\nIf you do not wish to receive further notifications about replies in this topic, please go here: ' .
                $unsub_url['email']
            );
        }
    } else {
        if ($id_type == 'frm') {
            $frm_name = reverse_fmt($frm_name);

            $subj = html_entity_decode(
                'New topic in forum ' . $frm_name . ', called ' . $thr_subject . ', by ' . $poster_login
            );

            if (!isset($body_email)) {
                $unsub_url['email'] = '' . $GLOBALS['WWW_ROOT'] . '?t=rview&unsub=1&frm_id=' . $id;
                $body_email = html_entity_decode(
                    'To view the topic go to:\n' .
                    $goto_url['email'] .
                    '\n\nTo stop receiving notifications about new topics in this forum, please go here: ' .
                    $unsub_url['email']
                );
            }
        }
    }

    send_email($GLOBALS['NOTIFY_FROM'], $to, $subj, $body_email, $headers, $munge_newlines);
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

function poll_delete($id)
{
    if (!$id) {
        return;
    }

    q('UPDATE fud30_msg SET poll_id=0 WHERE poll_id=' . $id);
    q('DELETE FROM fud30_poll_opt WHERE poll_id=' . $id);
    q('DELETE FROM fud30_poll_opt_track WHERE poll_id=' . $id);
    q('DELETE FROM fud30_poll WHERE id=' . $id);
}

function poll_fetch_opts($id)
{
    $a = [];
    $c = uq('SELECT id,name FROM fud30_poll_opt WHERE poll_id=' . $id);
    while ($r = db_rowarr($c)) {
        $a[$r[0]] = $r[1];
    }
    unset($c);

    return $a;
}

function poll_del_opt($id, $poll_id)
{
    q('DELETE FROM fud30_poll_opt WHERE poll_id=' . $poll_id . ' AND id=' . $id);
    q('DELETE FROM fud30_poll_opt_track WHERE poll_id=' . $poll_id . ' AND poll_opt=' . $id);
    q(
        'UPDATE fud30_poll SET total_votes=(SELECT SUM(count) FROM fud30_poll_opt WHERE poll_id=' .
        $poll_id .
        ') WHERE id=' .
        $poll_id
    );
}

function poll_activate($poll_id, $frm_id)
{
    q('UPDATE fud30_poll SET forum_id=' . $frm_id . ' WHERE id=' . $poll_id);
}

function poll_sync($poll_id, $name, $max_votes, $expiry)
{
    q(
        'UPDATE fud30_poll SET name=' .
        _esc(htmlspecialchars($name)) .
        ', expiry_date=' .
        (int)$expiry .
        ', max_votes=' .
        (int)$max_votes .
        ' WHERE id=' .
        $poll_id
    );
}

function poll_add($name, $max_votes, $expiry, $uid = _uid)
{
    return db_qid(
        'INSERT INTO fud30_poll (name, owner, creation_date, expiry_date, max_votes) VALUES (' .
        _esc(htmlspecialchars($name)) .
        ', ' .
        $uid .
        ', ' .
        __request_timestamp__ .
        ', ' .
        (int)$expiry .
        ', ' .
        (int)$max_votes .
        ')'
    );
}

function poll_opt_sync($id, $name)
{
    q('UPDATE fud30_poll_opt SET name=' . _esc($name) . ' WHERE id=' . $id);
}

function poll_opt_add($name, $poll_id)
{
    return db_qid('INSERT INTO fud30_poll_opt (poll_id,name) VALUES(' . $poll_id . ', ' . _esc($name) . ')');
}

function poll_validate($poll_id, $msg_id)
{
    if (($mid = (int)q_singleval('SELECT id FROM fud30_msg WHERE poll_id=' . $poll_id)) && $mid != $msg_id) {
        return 0;
    }
    return $poll_id;
}

function safe_attachment_copy($source, $id, $ext)
{
    $loc = $GLOBALS['FILE_STORE'] . $id . '.atch';
    if (!$ext && !move_uploaded_file($source, $loc)) {
        error_dialog('Unable to move uploaded file', 'From: ' . $source . ' To: ' . $loc, 'LOG&RETURN');
    } else {
        if ($ext && !copy($source, $loc)) {
            error_dialog('Unable to handle file attachment', 'From: ' . $source . ' To: ' . $loc, 'LOG&RETURN');
        }
    }
    @unlink($source);

    @chmod($loc, ($GLOBALS['FUD_OPT_2'] & 8388608 ? 0600 : 0644));

    return $loc;
}

function attach_add($at, $owner, $attach_opt = 0, $ext = 0)
{
    $id = db_qid(
        'INSERT INTO fud30_attach (location, message_id, original_name, owner, attach_opt, mime_type,fsize) ' .
        q_limit(
            'SELECT null AS location, 0 AS message_id, ' .
            _esc($at['name']) .
            ' AS original_name, ' .
            $owner .
            ' AS owner, ' .
            $attach_opt .
            ' AS attach_opt, id AS mime_type, ' .
            $at['size'] .
            ' AS fsize 
			FROM fud30_mime WHERE fl_ext IN(\'*\', ' .
            _esc(strtolower(substr(strrchr($at['name'], '.'), 1))) .
            ')
			ORDER BY fl_ext DESC'
            ,
            1
        )
    );

    safe_attachment_copy($at['tmp_name'], $id, $ext);

    return $id;
}

function attach_finalize($attach_list, $mid, $attach_opt = 0)
{
    $id_list = '';
    $attach_count = 0;

    $tbl = !$attach_opt ? 'msg' : 'pmsg';

    foreach ($attach_list as $key => $val) {
        if (!$val) {
            @unlink($GLOBALS['FILE_STORE'] . (int)$key . '.atch');
        } else {
            $attach_count++;
            $id_list .= (int)$key . ',';
        }
    }

    if ($id_list) {
        $id_list = substr($id_list, 0, -1);
        $cc = q_concat(_esc($GLOBALS['FILE_STORE']), 'id', _esc('.atch'));
        q(
            'UPDATE fud30_attach SET location=' .
            $cc .
            ', message_id=' .
            $mid .
            ' WHERE id IN(' .
            $id_list .
            ') AND attach_opt=' .
            $attach_opt
        );
        $id_list = ' AND id NOT IN(' . $id_list . ')';
    } else {
        $id_list = '';
    }

    /* Delete any unneeded (removed, temporary) attachments. */
    q('DELETE FROM fud30_attach WHERE message_id=' . $mid . ' ' . $id_list);

    if (!$attach_opt && ($atl = attach_rebuild_cache($mid))) {
        q(
            'UPDATE fud30_msg SET attach_cnt=' .
            $attach_count .
            ', attach_cache=' .
            _esc(serialize($atl)) .
            ' WHERE id=' .
            $mid
        );
    }

    if (!empty($GLOBALS['usr']->sid)) {
        ses_putvar((int)$GLOBALS['usr']->sid, null);
    }
}

function attach_rebuild_cache($id)
{
    $ret = [];
    $c = uq(
        'SELECT a.id, a.original_name, a.fsize, a.dlcount, COALESCE(m.icon, \'unknown.gif\') FROM fud30_attach a LEFT JOIN fud30_mime m ON a.mime_type=m.id WHERE message_id=' .
        $id .
        ' AND attach_opt=0'
    );
    while ($r = db_rowarr($c)) {
        $ret[] = $r;
    }
    unset($c);
    return $ret;
}

/* Increment download counter for an attachment. */
function attach_inc_dl_count($id, $mid)
{
    q('UPDATE fud30_attach SET dlcount=dlcount+1 WHERE id=' . $id);
    if (($a = attach_rebuild_cache($mid))) {
        q('UPDATE fud30_msg SET attach_cache=' . _esc(serialize($a)) . ' WHERE id=' . $mid);
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
        $extra_header = "\nMIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit" .
            $header;
    }
    $header = 'From: ' .
        $from .
        "\nErrors-To: " .
        $from .
        "\nReturn-Path: " .
        $from .
        "\nX-Mailer: FUDforum v" .
        $GLOBALS['FORUM_VERSION'] .
        $extra_header .
        $header;

    $body = str_replace("\r", '', $body);
    if ($munge_newlines) {
        $body = str_replace('\n', "\n", $body);
    }
    $subj = encode_subject($subj);

    // Call PRE mail plugins.
    if (defined('plugins')) {
        [$to, $subj, $body, $header] = plugin_call_hook('PRE_MAIL', [$to, $subj, $body, $header]);
    }

    if (defined('fud_logging')) {
        if (!function_exists('logaction')) {
            fud_use('logaction.inc');
        }
        logaction(
            _uid,
            'SEND EMAIL',
            0,
            'To=[' .
            implode(',', (array)$to) .
            ']<br />Subject=[' .
            $subj .
            ']<br />Headers=[' .
            str_replace("\n", '<br />', htmlentities($header)) .
            ']<br />Message=[' .
            $body .
            ']'
        );
    }

    if ($GLOBALS['FUD_OPT_1'] & 512) {
        if (!class_exists('fud_smtp')) {
            fud_use('smtp.inc');
        }
        $smtp = new fud_smtp;
        $smtp->msg = str_replace(['\n', "\n."], ["\n", "\n.."], $body);
        $smtp->subject = encode_subject($subj);
        $smtp->to = $to;
        $smtp->from = $from;
        $smtp->headers = $header;
        $smtp->send_smtp_email();
        return 1;
    }

    foreach ((array)$to as $email) {
        if (!@mail($email, $subj, $body, $header)) {
            fud_logerror(
                'Your system didn\'t accept E-mail [' . $subj . '] to [' . $email . '] for delivery.',
                'fud_errors',
                $header . "\n\n" . $body
            );
            return -1;
        }
    }

    return 1;
}

$GLOBALS['__revfs'] = ['&quot;', '&lt;', '&gt;', '&amp;'];
$GLOBALS['__revfd'] = ['"', '<', '>', '&'];

function reverse_fmt($data)
{
    $s = $d = [];
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
    } else {
        if (substr_count($name, '.') > 1) {
            $name = '*' . substr($name, strpos($name, '.') + 1);
        }
    }

    return $name;
}

function str_word_count_utf8($text)
{
    if (@preg_match('/\p{L}/u', 'a') == 1) {    // PCRE unicode support is turned on
        // Match utf-8 words to index:
        // - If you also want to index numbers, use regex "/[\p{N}\p{L}][\p{L}\p{N}\p{Mn}\p{Pd}'\x{2019}]*/u".
        // - Remove the \p{N} if you don't want to index words with numbers in them.
        preg_match_all("/\p{L}[\p{L}\p{N}\p{Mn}\p{Pd}'\x{2019}]*/u", $text, $m);
        return $m[0];
    } else {
        return str_word_count($text, 1);
    }
}

function text_to_worda($text, $minlen = 2, $maxlen = 51, $uniq = 0)
{
    $words = [];
    $text = strtolower(strip_tags(reverse_fmt($text)));

    // Throw away words that are too short or too long.
    if (!isset($minlen)) {
        $minlen = 2;
    }
    if (!isset($maxlen)) {
        $maxlen = 51;
    }

    // Languages like Chinese, Japanese and Korean can have very short and very long words.
    $lang = isset($GLOBALS['usr']->lang) ? $GLOBALS['usr']->lang : '';
    if ($lang == 'zh-hans' || $lang == 'zh-hant' || $lang == 'ja' || $lang == 'ko') {
        $minlen = 0;
        $maxlen = 100;
    }

    $t1 = str_word_count_utf8($text, 1);
    foreach ($t1 as $word) {
        if (isset($word[$maxlen]) || !isset($word[$minlen])) {
            continue;
        }    // Check wWord length.
        $word = _esc($word);

        // Count the frequency of each unique word.
        if (isset($words[$word])) {
            $words[$word]++;
        } else {
            $words[$word] = 1;
        }
    }

    // Return unique words, with or without word counts.
    return $uniq ? $words : array_keys($words);
}

function index_text($subj, $body, $msg_id)
{
    // Remove stuff in [quote] tags.
    while (preg_match('!<cite>(.*?)</cite><blockquote>(.*?)</blockquote>!is', $body)) {
        $body = preg_replace('!<cite>(.*?)</cite><blockquote>(.*?)</blockquote>!is', '', $body);
    }

    // Remove quotes imported Usenet/ Mailing lists.
    while (preg_match('/<font color="[^"]*">&gt;[^<]*<\/font><br \/>/s', $body)) {
        $body = preg_replace('/<font color="[^"]*">&gt;[^<]*<\/font><br \/>/s', '', $body);
    }

    // Give more weight to short descriptive subjects and penalize long descriptions.
    $spaces = substr_count($subj, ' ') + 1;
    $weight = 10 / $spaces;

    // Spilt text into word arrays, note how $subj is repeated for increaded relevancy.
    $w1 = text_to_worda($subj, null, null, 1);
    $w2 = text_to_worda(str_repeat($subj . ' ', $weight) . ' ' . $body, null, null, 1);
    if (!$w2) {
        return;
    }

    // Register word so that we can get an id.
    ins_m('fud30_search', 'word', 'text', array_keys($w2));

    // Populate title index
    if ($subj && $w1) {
        foreach ($w1 as $word => $count) {
            try {
                q(
                    'INSERT INTO fud30_title_index (word_id, msg_id, frequency) SELECT id, ' .
                    $msg_id .
                    ',' .
                    $count .
                    ' FROM fud30_search WHERE word = ' .
                    $word
                );
            } catch (Exception $e) {
            }
        }
    }

    // Populate index.
    foreach ($w2 as $word => $count) {
        try {
            q(
                'INSERT INTO fud30_index (word_id, msg_id, frequency) SELECT id, ' .
                $msg_id .
                ',' .
                $count .
                ' FROM fud30_search WHERE word = ' .
                $word
            );
        } catch (Exception $e) {
        }
    }

    // Clear search cache.
    q('DELETE FROM fud30_search_cache');
    // "WHERE msg_id='. $msg_id" for better performance, but newly indexed text will not be immediately searchable.
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
        if (!($this->fs = @fsockopen(
            $GLOBALS['FUD_SMTP_SERVER'],
            $GLOBALS['FUD_SMTP_PORT'],
            $errno,
            $errstr,
            $GLOBALS['FUD_SMTP_TIMEOUT']
        ))) {
            fud_logerror(
                'ERROR: SMTP server at ' .
                $GLOBALS['FUD_SMTP_SERVER'] .
                " is not available<br />\n" .
                ($errno ? "Additional Problem Info: $errno -> $errstr <br />\n" : ''),
                'fud_errors'
            );
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
                if (substr($str, 0, 12) == '250-STARTTLS') {
                    $tls = true;
                }
                if (substr($str, 3, 1) == ' ') {
                    break;
                }    // Done reading if 4th char is a space.

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
                    if (substr($str, 3, 1) == ' ') {
                        break;
                    }
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

        if (empty($this->from)) {
            $this->from = $GLOBALS['NOTIFY_FROM'];
        }

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

$GLOBALS['seps'] = [
    ' ' => ' ',
    "\n" => "\n",
    "\r" => "\r",
    '\'' => '\'',
    '"' => '"',
    '[' => '[',
    ']' => ']',
    '(' => '(',
    ';' => ';',
    ')' => ')',
    "\t" => "\t",
    '=' => '=',
    '>' => '>',
    '<' => '<',
];

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
    if (preg_match('/(script:|data:)/', $url)) {
        return false;
    }

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
        [$str] = plugin_call_hook('BBCODE2HTML', [$str]);
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
                if (($pos - $old_pos)) {
                    $ostr .= substr($str, $old_pos, $pos - $old_pos);
                }
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
            } else {
                if (strcasecmp(substr($str, $cpos, $otag_l), $otag) == 0) {
                    ++$rf;
                } else {
                    $nt_tag++;
                    ++$cpos;
                    continue;
                }
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
                    } else {
                        if (!preg_match('/^(http|ftp|\.|\/)/i', $url)) {
                            // Skip invalid or bad URL (like 'script:' or 'data:').
                            $ostr .= substr($str, $pos, $cepos - $pos + 1);
                            $epos = $cepos;
                            $str[$cpos] = '<';
                            break;
                        } else {
                            $url = str_replace('://', '&#58;&#47;&#47;', $url);
                        }
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
                                $ostr .= '<img ' .
                                    $class .
                                    'src="' .
                                    $url .
                                    '" border="0" alt="' .
                                    substr($str, $epos + 1, ($cpos - $epos) - 1) .
                                    '" />';
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
                        $parms = str_replace(['@', ':'], ['&#64;', '&#58;'], $parms);
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
                    $tmp2 = str_replace(['[*]', '[li]'], '<li>', $tmp);
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
                    $ostr .= '<div class="dashed" style="padding: 3px;" align="center"><a href="javascript://" onclick="javascript: layerVis(\'s' .
                        $rnd .
                        '\', 1);">'
                        .
                        ($parms ? $parms : 'Toggle Spoiler') .
                        '</a><div align="left" id="s' .
                        $rnd .
                        '" style="display: none;">';
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

    $er = array_flip(array_merge(range(0, 9), range('A', 'Z'), range('a', 'z'), ['.', '-', '\'', '_']));

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
            if (isset($er[$ostr[$es]])) {
                continue;
            }
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
            if (isset($er[$ostr[$ee]])) {
                continue;
            }
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
    $ostr = preg_replace(
        '!</(ul|ol|table|pre|code|blockquote|div|hr|h1|h2|h3|h4|h5|h6)>\s*<br\s*/?\s*>!is',
        '</\\1>',
        $ostr
    );
    $ostr = preg_replace('!<(hr)>\s*<br\s*/?\s*>!is', '<\\1>', $ostr);

    return $ostr;
}

/** Convert HTML back to BBCode tags. */
function html_to_tags($fudml)
{
    // Call all HTML to BBcode conversion plugins.
    if (defined('plugins')) {
        [$fudml] = plugin_call_hook('HTML2BBCODE', [$fudml]);
    }

    // Remove PHP code blocks so they can't interfere with parsing.
    while (preg_match('/<span name="php">(.*?)<\/span><!--php-->/is', $fudml, $res)) {
        $tmp = trim(html_entity_decode(strip_tags(str_replace('<br />', "\n", $res[1]))));
        $m = md5($tmp);
        $php[$m] = $tmp;
        $fudml = str_replace($res[0], "[php]\n" . $m . "\n[/php]", $fudml);
    }

    // Wikipedia tags.
    while (preg_match(
        '!<a href="http://(?:([A-ZA-z]+)?\.)?wikipedia.com/wiki/([^"]+)"( target="_blank")? name="WikiPediaLink">(.*?)</a>!s',
        $fudml,
        $res
    )) {
        if (count($res) == 5) {
            $fudml = str_replace($res[0], '[wikipedia=' . $res[1] . ']' . $res[2] . '[/wikipedia]', $fudml);
        } else {
            $fudml = str_replace($res[0], '[wikipedia]' . $res[2] . '[/wikipedia]', $fudml);
        }
    }

    // Quote tags.
    if (strpos($fudml, '<cite>') !== false) {
        $fudml = str_replace(
            ['<cite>', '</cite><blockquote>', '</blockquote>'],
            ['[quote title=', ']', '[/quote]'],
            $fudml
        );
    }
    // Old bad quote tags.
    if (preg_match('!class="quote"!', $fudml)) {
        $fudml = preg_replace(
            '!<table border="0" align="center" width="90%" cellpadding="3" cellspacing="1">(<tbody>)?<tr><td class="SmallText"><b>!',
            '[quote title=',
            $fudml
        );
        $fudml = preg_replace('!</b></td></tr><tr><td class="quote">(<br>)?!', ']', $fudml);
        $fudml = preg_replace('!(<br>)?</td></tr>(</tbody>)?</table>!', '[/quote]', $fudml);
    }

    // Spoiler tags.
    if (preg_match(
        '!<div class="dashed" style="padding: 3px;" align="center"( width="100%")?><a href="javascript://" OnClick="javascript: layerVis\(\'.*?\', 1\);">.*?</a><div align="left" id="(.*?)" style="display: none;">!is',
        $fudml
    )) {
        $fudml = preg_replace(
            '!\<div class\="dashed" style\="padding: 3px;" align\="center"( width\="100%")?\>\<a href\="javascript://" OnClick\="javascript: layerVis\(\'.*?\', 1\);">(.*?)\</a\>\<div align\="left" id\=".*?" style\="display: none;"\>!is',
            '[spoiler=\2]',
            $fudml
        );
        $fudml = str_replace('</div></div>', '[/spoiler]', $fudml);
    }
    // Old bad spoiler format.
    if (preg_match(
        '!<div class="dashed" style="padding: 3px;" align="center" width="100%"><a href="javascript://" OnClick="javascript: layerVis\(\'.*?\', 1\);">.*?</a><div align="left" id="(.*?)" style="visibility: hidden;">!is',
        $fudml
    )) {
        $fudml = preg_replace(
            '!\<div class\="dashed" style\="padding: 3px;" align\="center" width\="100%"\>\<a href\="javascript://" OnClick\="javascript: layerVis\(\'.*?\', 1\);">(.*?)\</a\>\<div align\="left" id\=".*?" style\="visibility: hidden;"\>!is',
            '[spoiler=\1]',
            $fudml
        );
        $fudml = str_replace('</div></div>', '[/spoiler]', $fudml);
    }

    // Color, font and size tags.
    $fudml = str_replace('<font face=', '<font font=', $fudml);
    foreach (['color', 'font', 'size'] as $v) {
        while (preg_match('!<font ' . $v . '=".+?">.*?</font>!is', $fudml, $m)) {
            $fudml = preg_replace(
                '!<font ' . $v . '="(.+?)">(.*?)</font>!is',
                '[' . $v . '=\1]\2[/' . $v . ']',
                $fudml
            );
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
        $fudml = str_ireplace(['<li>', '</li>'], ["\n[*]", ''], $fudml);
    }

    $fudml = str_replace(
        [
            '<b>',
            '</b>',
            '<i>',
            '</i>',
            '<u>',
            '</u>',
            '<s>',
            '</s>',
            '<sub>',
            '</sub>',
            '<sup>',
            '</sup>',
            '<del>',
            '</del>',
            '<big>',
            '</big>',
            '<small>',
            '</small>',
            '<center>',
            '</center>',
            '<div class="pre"><pre>',
            '</pre></div>',
            '<div align="left">',
            '<div align="right">',
            '<div align="center">',
            '</div><!--align-->',
            '<span style="float:left">',
            '<span style="float:right">',
            '</span><!--float-->',
            '<span class="indent">',
            '</span><!--indent-->',
            '<span name="notag">',
            '</span>',
            '&#64;',
            '&#58;&#47;&#47;',
            '<br />',
            '<pre>',
            '</pre>',
            '<hr>',
            '<h1>',
            '</h1>',
            '<h2>',
            '</h2>',
            '<h3>',
            '</h3>',
            '<h4>',
            '</h4>',
            '<h5>',
            '</h5>',
            '<h6>',
            '</h6>',
        ],
        [
            '[b]',
            '[/b]',
            '[i]',
            '[/i]',
            '[u]',
            '[/u]',
            '[s]',
            '[/s]',
            '[sub]',
            '[/sub]',
            '[sup]',
            '[/sup]',
            '[del]',
            '[/del]',
            '[big]',
            '[/big]',
            '[small]',
            '[/small]',
            '[center]',
            '[/center]',
            '[code]',
            '[/code]',
            '[align=left]',
            '[align=right]',
            '[align=center]',
            '[/align]',
            '[float=left]',
            '[float=right]',
            '[/float]',
            '[indent]',
            '[/indent]',
            '[notag]',
            '[/notag]',
            '@',
            '://',
            '',
            '[pre]',
            '[/pre]',
            '[hr]',
            '[h1]',
            '[/h1]',
            '[h2]',
            '[/h2]',
            '[h3]',
            '[/h3]',
            '[h4]',
            '[/h4]',
            '[h5]',
            '[/h5]',
            '[h6]',
            '[/h6]',
        ],
        $fudml
    );

    // Image, Email and URL tags/
    while (preg_match('!<img src="(.*?)" border="?0"? alt="\\1" ?/?>!is', $fudml)) {
        $fudml = preg_replace('!<img src="(.*?)" border="?0"? alt="\\1" ?/?>!is', '[img]\1[/img]', $fudml);
    }
    while (preg_match('!<img class="(r|l)" src="(.*?)" border="?0"? alt="\\2" ?/?>!is', $fudml)) {
        $fudml = preg_replace(
            '!<img class="(r|l)" src="(.*?)" border="?0"? alt="\\2" ?/?>!is',
            '[img\1]\2[/img\1]',
            $fudml
        );
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
        $fudml = preg_replace(
            '!<img class="(r|l)" src="(.*?)" border="?0"? alt="(.*?)" ?/?>!is',
            '[img\1=\2]\3[/img\1]',
            $fudml
        );
    }
    if (strpos($fudml, '<a href="mailto:') !== false) {
        $fudml = preg_replace(
            '!<a href="mailto:(.+?)"( target="_blank")?>(.+?)</a>!is',
            '[email=\1]\3[/email]',
            $fudml
        );
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

if (!empty($_POST['NO']) ||
    empty($_POST['_sel']) ||
    (empty($_POST['mov_sel_all']) &&
        empty($_POST['del_sel_all']) &&
        empty($_POST['loc_sel_all']) &&
        empty($_POST['merge_sel_all']))) {
    check_return($usr->returnto);
}

$list = [];
foreach ($_POST['_sel'] as $v) {
    if ($v = (int)$v) {
        $list[$v] = $v;
    }
}

if (!$list) {
    check_return($usr->returnto);
}

/* Permission check, based on last thread since all threads are supposed to be from the same forum. */
if (!($perms = db_saq(
    'SELECT t.forum_id, COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS gco, mm.id AS md
				FROM fud30_thread t
				LEFT JOIN fud30_mod mm ON mm.forum_id=t.forum_id AND mm.user_id=' . _uid . '
				INNER JOIN fud30_group_cache g1 ON g1.user_id=' . (_uid ? '2147483647' : '0') . ' AND g1.resource_id=t.forum_id
				LEFT JOIN fud30_group_cache g2 ON g2.user_id=' . _uid . ' AND g2.resource_id=t.forum_id
				WHERE t.id=' . end($list)
))) {
    check_return($usr->returnto);
}
if (!$is_a && !$perms[2]) {
    if (!empty($_POST['mov_sel_all']) && !($perms[1] & 8192)) {    // p_MOVE
        std_error('access');
    } else {
        if (!empty($_POST['loc_sel_all']) && !($perms[1] & 4096)) {    // p_LOCK
            std_error('access');
        } else {
            if (!empty($_POST['del_sel_all']) && !($perms[1] & 32)) {    // p_DEL
                std_error('access');
            } else {
                if (!empty($_POST['merge_sel_all']) && !($perms[1] & 2048)) {    // p_SPLIT
                    std_error('access');
                }
            }
        }
    }
}

$final_del = !empty($_POST['del_sel_all']) && !empty($_POST['del_conf']);
$final_loc = !empty($_POST['loc_sel_all']);
$final_mv = !empty($_POST['mov_sel_all']) && !empty($_POST['forum_id']);
$final_merge = !empty($_POST['merge_sel_all']);

/* Ensure that all threads are from the same forum and that they exist. */
$c = uq(
    'SELECT m.subject, t.id, t.root_msg_id, t.replies, t.last_post_date, t.last_post_id, t.tdescr, t.thread_opt
			FROM fud30_thread t 
			INNER JOIN fud30_msg m ON m.id=t.root_msg_id
			WHERE t.id IN(' . implode(',', $list) . ') AND t.forum_id=' . $perms[0]
);
$ext = $list = [];
while ($r = db_rowarr($c)) {
    $list[$r[1]] = $r[0];
    if ($final_del) {
        $ext[$r[1]] = [$r[2], $r[3]];
    } else {
        if ($final_loc) {
            $ext[$r[1]] = [$r[7]];
        } else {
            if ($final_mv) {
                $ext[$r[1]] = [$r[2], $r[4], $r[5], $r[6]];
            }
        }
    }
}
unset($c);
if (!$list) {
    invl_inp_err();
}

if ($final_del) { /* Remove threads, one by one. */
    foreach ($ext as $k => $v) {
        logaction(_uid, 'DELTHR', 0, '"' . $list[$k] . '" w/' . $v[1] . ' replies');
        fud_msg_edit::delete(1, $v[0], 1);
    }
    check_return($usr->returnto);
} else {
    if ($final_loc) { /* Lock threads, one by one. */
        foreach ($ext as $k => $v) {
            if ($v[0] & 1) {
                logaction(_uid, 'THRUNLOCK', $k);
                th_lock($k, 0);
            } else {
                logaction(_uid, 'THRLOCK', $k);
                th_lock($k, 1);
            }
        }
        check_return($usr->returnto);
    } else {
        if ($final_mv) { /* Move threads one by one. */
            /* Validate permissions for destination forum. */
            if (!($_POST['forum_id'] = (int)$_POST['forum_id'])) {
                invl_inp_err();
            }
            if (!($mv_perms = db_saq(
                'SELECT COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS gco, mm.id AS md
				FROM fud30_forum f
				LEFT JOIN fud30_mod mm ON mm.forum_id=f.id AND mm.user_id=' . _uid . '
				INNER JOIN fud30_group_cache g1 ON g1.user_id=' . (_uid ? '2147483647' : '0') . ' AND g1.resource_id=f.id
				LEFT JOIN fud30_group_cache g2 ON g2.user_id=' . _uid . ' AND g2.resource_id=f.id
				WHERE f.id=' . $_POST['forum_id']
            ))) {
                invl_inp_err();
            }
            if (!$is_a && !$mv_perms[1] && !($mv_perms[0] & 8192)) {
                std_error('access');
            }

            foreach ($list as $k => $v) {
                logaction(_uid, 'THRMOVE', $k);
                th_move($k, $_POST['forum_id'], $ext[$k][0], $perms[0], $ext[$k][1], $ext[$k][2], $ext[$k][3]);
            }

            /* Update last post ids in source & destination forums. */
            foreach ([$perms[0], $_POST['forum_id']] as $v) {
                $mid = (int)q_singleval(
                    'SELECT MAX(last_post_id) FROM fud30_thread t INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE t.forum_id=' .
                    $v .
                    ' AND t.moved_to=0 AND m.apr=1'
                );
                q('UPDATE fud30_forum SET last_post_id=' . $mid . ' WHERE id=' . $v);
            }

            check_return($usr->returnto);
        } else {
            if ($final_merge) { /* Redirect merge request to merge_th.php. */
                foreach ($list as $k => $v) {
                    $sel_th[] = $k;
                    if (empty($new_title)) {
                        $new_title = $v;
                    }
                }
                header(
                    'Location: /index.php?t=merge_th&frm_id=' .
                    $perms[0] .
                    '&new_title=' .
                    urlencode($new_title) .
                    '&sel_th=' .
                    serialize($sel_th) .
                    '&' .
                    _rsidl
                );
                exit;
            }
        }
    }
}

$mmd_topic_ents = '';
foreach ($list as $k => $v) {
    $mmd_topic_ents .= $v . '<br />
<input type="hidden" name="_sel[]" value="' . $k . '" />';
}

if (!empty($_POST['mov_sel_all'])) {
    $table_data = $oldc = '';

    $c = uq(
        'SELECT f.name, f.id, c.id, m.user_id, COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS gco
				FROM fud30_forum f
				INNER JOIN fud30_fc_view v ON v.f=f.id
				INNER JOIN fud30_cat c ON c.id=v.c
				LEFT JOIN fud30_mod m ON m.user_id=' .
        _uid .
        ' AND m.forum_id=f.id
				INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id=f.id
				LEFT JOIN fud30_group_cache g2 ON g2.user_id=' .
        _uid .
        ' AND g2.resource_id=f.id
				WHERE c.id!=0 AND f.id!=' .
        $perms[0] .
        ($is_a ? '' : ' AND (CASE WHEN m.user_id IS NOT NULL OR ' .
            q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 1) .
            ' > 0 THEN 1 ELSE 0 END)=1') .
        '
				ORDER BY v.id'
    );

    require $FORUM_SETTINGS_PATH . 'cat_cache.inc';
    while ($r = db_rowarr($c)) {
        if ($oldc != $r[2]) {
            foreach ($cat_cache as $k => $i) {
                if ($k == $r[2]) {
                    break;
                }
            }
            $table_data .= '<tr>
	<td class="RowStyleC" style="padding-left: ' . ($tabw = ($i[0] * 10 + 2)) . 'px">' . $i[1] . '</td>
</tr>';
            $oldc = $r[2];
        }

        if ($r[3] || $is_a || $r[4] & 8192) {
            $table_data .= '<tr>
	<td class="RowStyleB" style="padding-left: ' .
                $tabw .
                'px"><label><input type="radio" name="forum_id" value="' .
                $r[1] .
                '" />' .
                $r[0] .
                '</label></td>
</tr>';
        }
    }
    unset($c);
}

F()->response->delSelAll = $_POST['del_sel_all'];
F()->response->mmdTopicEnts = $mmd_topic_ents;
F()->response->tableData = $table_data;
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
                        <?php echo($FUD_OPT_4 & 16 ? '<li><a href="/index.php?t=blog&amp;' .
                            _rsid .
                            '" title="Blog"><img src="/theme/twig/images/blog.png" alt="" /> Blog</a></li>' : ''); ?>
                        <?php echo($FUD_OPT_4 & 8 ? '<li><a href="/index.php?t=page&amp;' .
                            _rsid .
                            '" title="Pages"><img src="/theme/twig/images/pages.png" alt="" /> Pages</a></li>' : ''); ?>
                        <?php echo($FUD_OPT_3 & 134217728 ? '<li><a href="/index.php?t=cal&amp;' .
                            _rsid .
                            '" title="Calendar"><img src="/theme/twig/images/calendar.png" alt="" /> Calendar</a></li>' : ''); ?>
                        <?php echo($FUD_OPT_1 & 16777216 ? ' <li><a href="/index.php?t=search' .
                            (isset($frm->forum_id) ? '&amp;forum_limiter=' . (int)$frm->forum_id . '' : '') .
                            '&amp;' .
                            _rsid .
                            '" title="Search"><img src="/theme/twig/images/top_search.png" alt="" /> Search</a></li>' : ''); ?>
                        <li><a accesskey="h" href="/index.php?t=help_index&amp;<?php echo _rsid; ?>" title="Help"><img
                                    src="/theme/twig/images/top_help.png" alt=""/> Help</a></li>
                        <?php echo(($FUD_OPT_1 & 8388608 ||
                            (_uid && $FUD_OPT_1 & 4194304) ||
                            $usr->users_opt & 1048576) ? '<li><a href="/index.php?t=finduser&amp;btn_submit=Find&amp;' .
                            _rsid .
                            '" title="Members"><img src="/theme/twig/images/top_members.png" alt="" /> Members</a></li>' : ''); ?>
                        <?php echo (__fud_real_user__ ? '<li><a href="/index.php?t=uc&amp;' .
                                _rsid .
                                '" title="Access the user control panel"><img src="/theme/twig/images/top_profile.png" alt="" /> Control Panel</a></li>' : ($FUD_OPT_1 &
                            2 ? '<li><a href="/index.php?t=register&amp;' .
                                _rsid .
                                '" title="Register"><img src="/theme/twig/images/top_register.png" alt="" /> Register</a></li>' : '')) .
                            '
	' .
                            (__fud_real_user__ ? '<li><a href="/index.php?t=login&amp;' .
                                _rsid .
                                '&amp;logout=1&amp;SQ=' .
                                $GLOBALS['sq'] .
                                '" title="Logout"><img src="/theme/twig/images/top_logout.png" alt="" /> Logout [ ' .
                                htmlspecialchars($usr->alias, null, null, false) .
                                ' ]</a></li>' : '<li><a href="/index.php?t=login&amp;' .
                                _rsid .
                                '" title="Login"><img src="/theme/twig/images/top_login.png" alt="" /> Login</a></li>'); ?>
                        <li><a href="/index.php?t=index&amp;<?php echo _rsid; ?>" title="Home"><img
                                    src="/theme/twig/images/top_home.png" alt=""/> Home</a></li>
                        <?php echo($is_a || ($usr->users_opt & 268435456) ? '<li><a href="/adm/index.php?S=' .
                            s .
                            '&amp;SQ=' .
                            $GLOBALS['sq'] .
                            '" title="Administration"><img src="/theme/twig/images/top_admin.png" alt="" /> Administration</a></li>' : ''); ?>
                    </ul>
                </div>
                <form method="post" action="/index.php?t=mmd"><?php echo _hs; ?>
                    <div align="center">
                        <b><?php echo(!empty($_POST['del_sel_all']) ? 'Delete Topics' : 'Move Topics'); ?>:</b><br/>
                        <span class="SmallText">
<?php echo $mmd_topic_ents; ?>
</span><br/><br/>
                        <?php echo(!empty($_POST['del_sel_all']) ? '
<input type="submit" name="NO" value="No" /> 
<input type="hidden" name="del_sel_all" value="1" />
<input type="submit" name="del_conf" value="Yes" /> 
' : '
<table cellspacing="0" cellpadding="3" class="DialogTable dashed">
<tr>
	<th>Destination Forum:</th>
</tr>
' . $table_data . '
</table>
<input type="submit" name="NO" value="Cancel" /> 
<input type="hidden" name="mov_sel_all" value="1" />
<input type="submit" name="submit" value="Move Topics" /> 
'); ?>
                    </div>
                </form>
                <br/>
                <div class="ac"><span class="curtime"><b>Current Time:</b> <?php echo strftime(
                            '%a %b %d %H:%M:%S %Z %Y',
                            __request_timestamp__
                        ); ?></span></div>
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
