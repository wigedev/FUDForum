<?php
/**
* copyright            : (C) 2001-2018 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: thread_view_common.inc.t 6179 2018-07-25 10:56:59Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

/* Check moved topic permissions. */
function th_moved_perm_chk($frm_id)
{
	make_perms_query($fields, $join, $frm_id);
	$res = db_sab(q_limit('SELECT m.forum_id, '. $fields.
		' FROM {SQL_TABLE_PREFIX}forum f '. $join.
		' LEFT JOIN {SQL_TABLE_PREFIX}mod m ON m.user_id='._uid.' AND m.forum_id='. $frm_id .
		' WHERE f.id='. $frm_id, 1));
	if (!$res || (!($res->group_cache_opt & 2) && !$res->forum_id)) {
		return;
	}
	return 1;
}

/* Make sure that we have what appears to be a valid forum id. */
if (!isset($_GET['frm_id']) || (!($frm_id = (int)$_GET['frm_id']))) {
	invl_inp_err();
}

if (!isset($_GET['start']) || ($start = (int)$_GET['start']) < 1) {
	$start = 0;
}

/* This query creates frm object that contains info about the current
 * forum, category & user's subscription status & permissions to the
 * forum.
 */

make_perms_query($fields, $join, $frm_id);

$frm = db_sab(q_limit('SELECT	f.id, f.name, f.thread_count, f.cat_id,'.
			(_uid ? ' fn.forum_id AS subscribed, m.forum_id AS md, ' : ' 0 AS subscribed, 0 AS md, ').
			'a.ann_id AS is_ann, ms.post_stamp, '. $fields .'
		FROM {SQL_TABLE_PREFIX}forum f
		INNER JOIN {SQL_TABLE_PREFIX}cat c ON c.id=f.cat_id '.
		(_uid ? ' LEFT JOIN {SQL_TABLE_PREFIX}forum_notify fn ON fn.user_id='._uid.' AND fn.forum_id='. $frm_id .' LEFT JOIN {SQL_TABLE_PREFIX}mod m ON m.user_id='. _uid .' AND m.forum_id='. $frm_id : ' ')
		.$join.'
		LEFT JOIN {SQL_TABLE_PREFIX}ann_forums a ON a.forum_id='. $frm_id .'
		LEFT JOIN {SQL_TABLE_PREFIX}msg ms ON ms.id=f.last_post_id
		WHERE f.id='. $frm_id, 1));

if (!$frm) {
	invl_inp_err();
}
$frm->forum_id = $frm->id;
$MOD = ($is_a || $frm->md);
$lwi = q_singleval(q_limit('SELECT seq FROM {SQL_TABLE_PREFIX}tv_'. $frm_id .' ORDER BY seq DESC', 1));

/* Check that the user has permissions to access this forum. */
if (!($frm->group_cache_opt & 2) && !$MOD) {
	if (!isset($_GET['logoff'])) {
		std_error('login');
	}
	if ($FUD_OPT_2 & 32768) {
		header('Location: {ROOT}/i/'. _rsidl);
	} else {
		header('Location: {ROOT}?'. _rsidl);
	}
	exit;
}

if ($_GET['t'] == 'threadt') {
	$cur_frm_page = $start + 1;
} else {
	$cur_frm_page = floor($start / $THREADS_PER_PAGE) + 1;
}

/* Do various things for registered users. */
if (_uid) {
	if (isset($_GET['sub']) && sq_check(0, $usr->sq)) {
		forum_notify_add(_uid, $frm->id);
		$frm->subscribed = 1;
	} else if (isset($_GET['unsub']) && sq_check(0, $usr->sq)) {
		forum_notify_del(_uid, $frm->id);
		$frm->subscribed = 0;
	}
} else if (__fud_cache((int)$frm->post_stamp)) {
	return;
}

$ppg = $usr->posts_ppg ? $usr->posts_ppg : $POSTS_PER_PAGE;

/* Handling of forum level announcements (should be merged with non-forum announcements in index.php.t). */
$announcements = '';
if ($frm->is_ann) {
	$today = gmdate('Ymd', __request_timestamp__);
	$res = uq('SELECT a.subject, a.text, a.ann_opt FROM {SQL_TABLE_PREFIX}announce a INNER JOIN {SQL_TABLE_PREFIX}ann_forums af ON a.id=af.ann_id AND af.forum_id='. $frm->id .' WHERE a.date_started<='. $today .' AND a.date_ended>='. $today);
	while ($r = db_rowarr($res)) {
		if (!_uid && $r[2] & 2) {
			continue;	// Only for logged in users.
		}
		if (_uid && $r['2'] & 4) {
			continue;	// Only for anonomous users.
		}
		if (defined('plugins')) {
			list($r[0], $r[1]) = plugin_call_hook('ANNOUNCEMENT', array($r[0], $r[1]));
		}
		$announcements .= '{TEMPLATE: announce_entry}';
	}
	unset($res);
}
?>
