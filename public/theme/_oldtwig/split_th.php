<?php
/**
* copyright            : (C) 2001-2017 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: split_th.php.t 6078 2017-09-25 14:57:31Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function th_lock($id, $lck)
{
	q('UPDATE fud30_thread SET thread_opt=('. (!$lck ? q_bitand('thread_opt', ~1) : q_bitor('thread_opt', 1)) .') WHERE id='. $id);
}

function th_inc_view_count($id)
{
	global $plugin_hooks;
	if (isset($plugin_hooks['CACHEGET'], $plugin_hooks['CACHESET'])) {
		// Increment view counters in cache.
		$th_views = call_user_func($plugin_hooks['CACHEGET'][0], 'th_views');
		$th_views[$id] = (!empty($th_views) && array_key_exists($id, $th_views)) ? $th_views[$id]+1 : 1;

		if ($th_views[$id] > 10 || count($th_views) > 100) {
			call_user_func($plugin_hooks['CACHESET'][0], 'th_views', array());	// Clear cache.
			// Start delayed database updating.
			foreach($th_views as $id => $views) {
				q('UPDATE fud30_thread SET views=views+'. $views .' WHERE id='. $id);
			}
		} else {
			call_user_func($plugin_hooks['CACHESET'][0], 'th_views', $th_views);
		}
	} else {
		// No caching plugins available.
		q('UPDATE fud30_thread SET views=views+1 WHERE id='. $id);
	}
}

function th_inc_post_count($id, $r, $lpi=0, $lpd=0)
{
	if ($lpi && $lpd) {
		q('UPDATE fud30_thread SET replies=replies+'. $r .', last_post_id='. $lpi .', last_post_date='. $lpd .' WHERE id='. $id);
	} else {
		q('UPDATE fud30_thread SET replies=replies+'. $r .' WHERE id='. $id);
	}
}function read_msg_body($off, $len, $id)
{
	if ($off == -1) {	// Fetch from DB and return.
		return q_singleval('SELECT data FROM fud30_msg_store WHERE id='. $id);
	}

	if (!$len) {	// Empty message.
		return;
	}

	// Open file if it's not already open.
	if (!isset($GLOBALS['__MSG_FP__'][$id])) {
		$GLOBALS['__MSG_FP__'][$id] = fopen($GLOBALS['MSG_STORE_DIR'] .'msg_'. $id, 'rb');
	}

	// Read from file.
	fseek($GLOBALS['__MSG_FP__'][$id], $off);
	return fread($GLOBALS['__MSG_FP__'][$id], $len);
}function tmpl_draw_select_opt($values, $names, $selected)
{
	$vls = explode("\n", $values);
	$nms = explode("\n", $names);

	if (count($vls) != count($nms)) {
		exit("FATAL ERROR: inconsistent number of values inside a select<br />\n");
	}

	$options = '';
	foreach ($vls as $k => $v) {
		$options .= '<option value="'.$v.'"'.($v == $selected ? ' selected="selected"' : '' )  .'>'.$nms[$k].'</option>';
	}

	return $options;
}/** Log action to the forum's Action Log Viewer ACP. */
function logaction($user_id, $res, $res_id=0, $action=null)
{
	q('INSERT INTO fud30_action_log (logtime, logaction, user_id, a_res, a_res_id)
		VALUES('. __request_timestamp__ .', '. ssn($action) .', '. $user_id .', '. ssn($res) .', '. (int)$res_id .')');
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
			$a[] = '/'.str_replace('/', '\\/', preg_quote(stripslashes($r[1]))).'/';
			preg_match('/\/(.+)\/(.*)/', $r[2], $regs);
			$b[] = str_replace('\\/', '/', $regs[1]);
		}
	}
	unset($c);

	define('__fud_replacer_init', 1);
}function th_add($root, $forum_id, $last_post_date, $thread_opt, $orderexpiry, $replies=0, $views=0, $lpi=0, $descr='')
{
	if (!$lpi) {
		$lpi = $root;
	}

	return db_qid('INSERT INTO
		fud30_thread
			(forum_id, root_msg_id, last_post_date, replies, views, rating, last_post_id, thread_opt, orderexpiry, tdescr)
		VALUES
			('. $forum_id .', '. $root .', '. $last_post_date .', '. $replies .', '. $views .', 0, '. $lpi .', '. $thread_opt .', '. $orderexpiry.','. _esc($descr) .')');
}

function th_move($id, $to_forum, $root_msg_id, $forum_id, $last_post_date, $last_post_id, $descr)
{
	if (!db_locked()) {
		if ($to_forum != $forum_id) {
			$lock = 'fud30_tv_'. $to_forum .' WRITE, fud30_tv_'. $forum_id;
		} else {
			$lock = 'fud30_tv_'. $to_forum;
		}
		
		db_lock('fud30_poll WRITE, '. $lock .' WRITE, fud30_thread WRITE, fud30_forum WRITE, fud30_msg WRITE');
		$ll = 1;
	}
	$msg_count = q_singleval('SELECT count(*) FROM fud30_thread LEFT JOIN fud30_msg ON fud30_msg.thread_id=fud30_thread.id WHERE fud30_msg.apr=1 AND fud30_thread.id='. $id);

	q('UPDATE fud30_thread SET forum_id='. $to_forum .' WHERE id='. $id);
	q('UPDATE fud30_forum SET post_count=post_count-'. $msg_count .' WHERE id='. $forum_id);
	q('UPDATE fud30_forum SET thread_count=thread_count+1,post_count=post_count+'. $msg_count .' WHERE id='. $to_forum);
	q('DELETE FROM fud30_thread WHERE forum_id='. $to_forum .' AND root_msg_id='. $root_msg_id .' AND moved_to='. $forum_id);
	if (($aff_rows = db_affected())) {
		q('UPDATE fud30_forum SET thread_count=thread_count-'. $aff_rows .' WHERE id='. $to_forum);
	}
	q('UPDATE fud30_thread SET moved_to='. $to_forum .' WHERE id!='. $id .' AND root_msg_id='. $root_msg_id);

	q('INSERT INTO fud30_thread
		(forum_id, root_msg_id, last_post_date, last_post_id, moved_to, tdescr)
	VALUES
		('. $forum_id .', '. $root_msg_id .', '. $last_post_date .', '. $last_post_id .', '. $to_forum .','. _esc($descr) .')');

	rebuild_forum_view_ttl($forum_id);
	rebuild_forum_view_ttl($to_forum);

	$p = db_all('SELECT poll_id FROM fud30_msg WHERE thread_id='. $id .' AND apr=1 AND poll_id>0');
	if ($p) {
		q('UPDATE fud30_poll SET forum_id='. $to_forum .' WHERE id IN('. implode(',', $p) .')');
	}

	if (isset($ll)) {
		db_unlock();
	}
}

function __th_cron_emu($forum_id, $run=1)
{
	/* Let's see if we have sticky threads that have expired. */
	$exp = db_all('SELECT fud30_thread.id FROM fud30_tv_'. $forum_id .'
			INNER JOIN fud30_thread ON fud30_thread.id=fud30_tv_'. $forum_id .'.thread_id
			INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id
			WHERE fud30_tv_'. $forum_id .'.seq>'. (q_singleval(q_limit('SELECT /* USE MASTER */ seq FROM fud30_tv_'. $forum_id .' ORDER BY seq DESC', 1)) - 50).' 
				AND fud30_tv_'. $forum_id .'.iss>0
				AND fud30_thread.thread_opt>=2 
				AND (fud30_msg.post_stamp+fud30_thread.orderexpiry)<='. __request_timestamp__);
	if ($exp) {
		q('UPDATE fud30_thread SET orderexpiry=0, thread_opt=(thread_opt & ~(2|4)) WHERE id IN('. implode(',', $exp) .')');
		$exp = 1;
	}

	/* Remove expired moved thread pointers. */
	q('DELETE FROM fud30_thread WHERE forum_id='. $forum_id .' AND moved_to>0 AND last_post_date<'.(__request_timestamp__ - 86400 * $GLOBALS['MOVED_THR_PTR_EXPIRY']));
	if (($aff_rows = db_affected())) {
		q('UPDATE fud30_forum SET thread_count=thread_count-'. $aff_rows .' WHERE thread_count>0 AND id='. $forum_id);
		if (!$exp) {
			$exp = 1;
		}
	}

	if ($exp && $run) {
		rebuild_forum_view_ttl($forum_id,1);
	}

	return $exp;
}

function rebuild_forum_view_ttl($forum_id, $skip_cron=0)
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
		db_lock('fud30_tv_'. $forum_id .' WRITE, fud30_thread READ, fud30_msg READ');
	}

	q('DELETE FROM fud30_tv_'. $forum_id);

	if (__dbtype__ == 'mssql') {
		// Add "TOP(1000000000)" as workaround for ERROR Msg 1033:
		// "The ORDER BY clause is invalid in views, inline functions, derived tables, subqueries, and common table expressions, unless TOP or FOR XML is also specified."
		// See http://support.microsoft.com/kb/841845/en
		q('INSERT INTO fud30_tv_'. $forum_id .' (seq, thread_id, iss) SELECT '. q_rownum() .', id, iss FROM
			(SELECT TOP(1000000000) fud30_thread.id AS id, '. q_bitand('thread_opt', (2|4|8)) .' AS iss FROM fud30_thread 
			INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id 
			WHERE forum_id='. $forum_id .' AND fud30_msg.apr=1 
			ORDER BY (CASE WHEN thread_opt>=2 THEN (4294967294 + (('. q_bitand('thread_opt', 8) .') * 100000000) + fud30_thread.last_post_date) ELSE fud30_thread.last_post_date END) ASC) q1');
	} else if (__dbtype__ == 'sqlite') {
		// Prevent subquery flattening by adding "LIMIT -1 OFFSET 0" as it will prevent the rowid() code to work.
		// See http://stackoverflow.com/questions/17809644/how-to-disable-subquery-flattening-in-sqlite
		q('INSERT INTO fud30_tv_'. $forum_id .' (seq, thread_id, iss) SELECT '. q_rownum() .', id, iss FROM
			(SELECT fud30_thread.id AS id, '. q_bitand('thread_opt', (2|4|8)) .' AS iss FROM fud30_thread 
			INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id 
			WHERE forum_id='. $forum_id .' AND fud30_msg.apr=1 
			ORDER BY (CASE WHEN thread_opt>=2 THEN (4294967294 + (('. q_bitand('thread_opt', 8) .') * 100000000) + fud30_thread.last_post_date) ELSE fud30_thread.last_post_date END) ASC LIMIT -1 OFFSET 0) q1');
	} else {
		//q('INSERT INTO fud30_tv_'. $forum_id .' (seq, thread_id, iss) SELECT '. q_rownum() .', id, iss FROM
		//	(SELECT fud30_thread.id AS id, '. q_bitand('thread_opt', (2|4|8)) .' AS iss FROM fud30_thread 
		//	INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id 
		//	WHERE forum_id='. $forum_id .' AND fud30_msg.apr=1 
		//	ORDER BY (CASE WHEN thread_opt>=2 THEN (4294967294 + (('. q_bitand('thread_opt', 8) .') * 100000000) + fud30_thread.last_post_date) ELSE fud30_thread.last_post_date END) ASC) q1');

		q('INSERT INTO fud30_tv_'. $forum_id .' (seq, thread_id, iss)
			SELECT '. q_rownum() .', fud30_thread.id, '. q_bitand('thread_opt', (2|4|8)) .' FROM fud30_thread 
			INNER JOIN fud30_msg ON fud30_thread.root_msg_id=fud30_msg.id 
			WHERE forum_id='. $forum_id .' AND fud30_msg.apr=1 
			ORDER BY '. q_bitand('thread_opt', (2|4|8)) .' ASC, fud30_thread.last_post_date ASC');
	}

	if (isset($ll)) {
		db_unlock();
	}
}

function th_delete_rebuild($forum_id, $th)
{
	if (!db_locked()) {
		$ll = 1;
		db_lock('fud30_tv_'. $forum_id .' WRITE');
	}

	/* Get position. */
	if (($pos = q_singleval('SELECT /* USE MASTER */ seq FROM fud30_tv_'. $forum_id .' WHERE thread_id='. $th))) {
		q('DELETE FROM fud30_tv_'. $forum_id .' WHERE thread_id='. $th);
		/* Move every one down one, if placed after removed topic. */
		q('UPDATE fud30_tv_'. $forum_id .' SET seq=seq-1 WHERE seq>'. $pos);
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
		db_lock('fud30_tv_'. $forum_id .' WRITE');
	}

	list($max,$iss) = db_saq(q_limit('SELECT /* USE MASTER */ seq, iss FROM fud30_tv_'. $forum_id .' ORDER BY seq DESC', 1));
	if ((!$sticky && $iss) || $iss >= 8) { /* Sub-optimal case, non-sticky topic and thre are stickies in the forum. */
		/* Find oldest sticky message. */
		if ($sticky && $iss >= 8) {
			$iss = q_singleval(q_limit('SELECT /* USE MASTER */ seq FROM fud30_tv_'. $forum_id .' WHERE seq>'. ($max - 50) .' AND iss>=8 ORDER BY seq ASC', 1));
		} else {
			$iss = q_singleval(q_limit('SELECT /* USE MASTER */ seq FROM fud30_tv_'. $forum_id .' WHERE seq>'. ($max - 50) .' AND iss>0 ORDER BY seq ASC', 1));
		}
		/* Move all stickies up one. */
		q('UPDATE fud30_tv_'. $forum_id .' SET seq=seq+1 WHERE seq>='. $iss);
		/* We do this, since in optimal case we just do ++max. */
		$max = --$iss;
	}
	q('INSERT INTO fud30_tv_'. $forum_id .' (thread_id,iss,seq) VALUES('. $th .','. (int)$sticky .','. (++$max) .')');

	if (isset($ll)) {
		db_unlock();
	}
}

function th_reply_rebuild($forum_id, $th, $sticky)
{
	if (!db_locked()) {
		$ll = 1;
		db_lock('fud30_tv_'. $forum_id .' WRITE');
	}

	/* Get first topic of forum (highest seq). */
	list($max,$tid,$iss) = db_saq(q_limit('SELECT /* USE MASTER */ seq,thread_id,iss FROM fud30_tv_'. $forum_id .' ORDER BY seq DESC', 1));

	if ($tid == $th) {
		/* NOOP: quick elimination, topic is already 1st. */
	} else if (!$iss || ($sticky && $iss < 8)) { /* Moving to the very top. */
		/* Get position. */
		$pos = q_singleval('SELECT /* USE MASTER */ seq FROM fud30_tv_'. $forum_id .' WHERE thread_id='. $th);
		/* Move everyone ahead, 1 down. */
		q('UPDATE fud30_tv_'. $forum_id .' SET seq=seq-1 WHERE seq>'. $pos);
		/* Move to top of the stack. */
		q('UPDATE fud30_tv_'. $forum_id .' SET seq='. $max .' WHERE thread_id='. $th);
	} else {
		/* Get position. */
		$pos = q_singleval('SELECT /* USE MASTER */ seq FROM fud30_tv_'. $forum_id .' WHERE thread_id='. $th);
		/* Find oldest sticky message. */
		$iss = q_singleval(q_limit('SELECT /* USE MASTER */ seq FROM fud30_tv_'. $forum_id .' WHERE seq>'. ($max - 50) .' AND iss>'. ($sticky && $iss >= 8 ? '=8' : '0') .' ORDER BY seq ASC', 1));
		/* Move everyone ahead, unless sticky, 1 down. */
		q('UPDATE fud30_tv_'. $forum_id .' SET seq=seq-1 WHERE seq BETWEEN '. ($pos + 1) .' AND '. ($iss - 1));
		/* Move to top of the stack. */
		q('UPDATE fud30_tv_'. $forum_id .' SET seq='. ($iss - 1) .' WHERE thread_id='. $th);
	}

	if (isset($ll)) {
		db_unlock();
	}
}function str_word_count_utf8($text) {
	if (@preg_match('/\p{L}/u', 'a') == 1) {	// PCRE unicode support is turned on
		// Match utf-8 words to index:
		// - If you also want to index numbers, use regex "/[\p{N}\p{L}][\p{L}\p{N}\p{Mn}\p{Pd}'\x{2019}]*/u".
		// - Remove the \p{N} if you don't want to index words with numbers in them.
		preg_match_all("/\p{L}[\p{L}\p{N}\p{Mn}\p{Pd}'\x{2019}]*/u", $text, $m);
		return $m[0];
	} else {
		return str_word_count($text, 1);
	}
}

function text_to_worda($text, $minlen=2, $maxlen=51, $uniq=0)
{
	$words = array();
	$text = strtolower(strip_tags(reverse_fmt($text)));

	// Throw away words that are too short or too long.
        if (!isset($minlen)) $minlen = 2;
        if (!isset($maxlen)) $maxlen = 51;

	// Languages like Chinese, Japanese and Korean can have very short and very long words.
	$lang = isset($GLOBALS['usr']->lang) ? $GLOBALS['usr']->lang : '';
	if ($lang == 'zh-hans' || $lang == 'zh-hant' || $lang == 'ja' || $lang == 'ko') {
		$minlen = 0;
		$maxlen = 100;
	}

	$t1 = str_word_count_utf8($text, 1);
	foreach ($t1 as $word) {
		if (isset($word[$maxlen]) || !isset($word[$minlen])) continue;	// Check wWord length.
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
	$w2 = text_to_worda(str_repeat($subj.' ', $weight) .' '. $body, null, null, 1);
	if (!$w2) {
		return;
	}

	// Register word so that we can get an id.
	ins_m('fud30_search', 'word', 'text', array_keys($w2));

	// Populate title index
	if ($subj && $w1) {
		foreach ($w1 as $word => $count) {
			try {
				q('INSERT INTO fud30_title_index (word_id, msg_id, frequency) SELECT id, '. $msg_id .','. $count .' FROM fud30_search WHERE word = '. $word);
			} catch(Exception $e) {}

		}
	}

	// Populate index.
	foreach ($w2 as $word => $count) {
		try {
			q('INSERT INTO fud30_index (word_id, msg_id, frequency) SELECT id, '. $msg_id .','. $count .' FROM fud30_search WHERE word = '. $word);
		} catch(Exception $e) {}
	}

	// Clear search cache.
	q('DELETE FROM fud30_search_cache');
	// "WHERE msg_id='. $msg_id" for better performance, but newly indexed text will not be immediately searchable.
}$GLOBALS['__revfs'] = array('&quot;', '&lt;', '&gt;', '&amp;');
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
}$GLOBALS['seps'] = array(' '=>' ', "\n"=>"\n", "\r"=>"\r", '\''=>'\'', '"'=>'"', '['=>'[', ']'=>']', '('=>'(', ';'=>';', ')'=>')', "\t"=>"\t", '='=>'=', '>'=>'>', '<'=>'<');

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
function tags_to_html($str, $allow_img=1, $no_char=0)
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
		if (!($epos-$pos-1)) {
			$pos = $epos + 1;
			continue;
		}
		$tag = substr($str, $pos+1, $epos-$pos-1);
		if (($pparms = strpos($tag, '=')) !== false) {
			$parms = substr($tag, $pparms+1);
			if (!$pparms) { /*[= exception */
				$pos = $epos+1;
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
				$str{$pos+1} = 'h';
				$str{$pos+2} = 'r';
				$str{$epos} = '>';
				continue 2;
		}

		if ($tag[0] == '/') {
			if (isset($end_tag[$pos])) {
				if( ($pos-$old_pos) ) $ostr .= substr($str, $old_pos, $pos-$old_pos);
				$ostr .= $end_tag[$pos];
				$pos = $old_pos = $epos+1;
			} else {
				$pos = $epos+1;
			}

			continue;
		}

		$cpos = $epos;
		$ctag = '[/'. $tag .']';
		$ctag_l = strlen($ctag);
		$otag = '['. $tag;
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
			if (($pos-$old_pos)) {
				$ostr .= substr($str, $old_pos, $pos-$old_pos);
			}
			switch ($tag) {
				case 'notag':
					$ostr .= '<span name="notag">'. substr($str, $epos+1, $cpos-1-$epos) .'</span>';
					$epos = $cepos;
					break;
				case 'url':
					if (!$parms) {
						$url = substr($str, $epos+1, ($cpos-$epos)-1);
					} else {
						$url = $parms;
					}

					$url = url_check($url);

					if (!strncasecmp($url, 'www.', 4)) {
						$url = 'http&#58;&#47;&#47;'. $url;
					} else if (!preg_match('/^(http|ftp|\.|\/)/i', $url)) {
						// Skip invalid or bad URL (like 'script:' or 'data:').
						$ostr .= substr($str, $pos, $cepos - $pos + 1);
						$epos = $cepos;
						$str[$cpos] = '<';
						break;
					} else {
						$url = str_replace('://', '&#58;&#47;&#47;', $url);
					}

					if ( strtolower(substr($str, $epos+1, 6)) == '[/url]' ) {
						$end_tag[$cpos] = $url .'</a>';  // Fill empty link.
					} else {
						$end_tag[$cpos] = '</a>';
					}
					$ostr .= '<a href="'. $url .'">';
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
					$end_tag[$cpos] = '</'. $tag .'>';
					$ostr .= '<'. $tag .'>';
					break;
				case 'h1':
				case 'h2':
				case 'h3':
				case 'h4':
				case 'h5':
				case 'h6':
					$end_tag[$cpos] = '</'.$tag.'>';
					$ostr .= '<'.$tag.'>';
					break;
				case 'email':
					if (!$parms) {
						$parms = str_replace('@', '&#64;', substr($str, $epos+1, ($cpos-$epos)-1));
						$ostr .= '<a href="mailto:'. $parms .'">'. $parms .'</a>';
						$epos = $cepos;
						$str[$cpos] = '<';
					} else {
						$end_tag[$cpos] = '</a>';
						$ostr .= '<a href="mailto:'. str_replace('@', '&#64;', $parms) .'">';
					}
					break;
				case 'color':
				case 'size':
				case 'font':
					if ($tag == 'font') {
						$tag = 'face';
					}
					$end_tag[$cpos] = '</font>';
					$ostr .= '<font '. $tag .'="'. $parms .'">';
					break;
				case 'code':
					$param = substr($str, $epos+1, ($cpos-$epos)-1);

					$ostr .= '<div class="pre"><pre>'. reverse_nl2br($param) .'</pre></div>';
					$epos = $cepos;
					$str[$cpos] = '<';
					break;
				case 'pre':
					$param = substr($str, $epos+1, ($cpos-$epos)-1);

					$ostr .= '<pre>'. reverse_nl2br($param) .'</pre>';
					$epos = $cepos;
					$str[$cpos] = '<';
					break;
				case 'php':
					$param = trim(reverse_fmt(reverse_nl2br(substr($str, $epos+1, ($cpos-$epos)-1))));

					if (strncmp($param, '<?php', 5)) {
						if (strncmp($param, '<?', 2)) {
							$param = "<?php\n". $param;
						} else {
							$param = "<?php\n". substr($param, 3);
						}
					}
					if (substr($param, -2) != '?>') {
						$param .= "\n?>";
					}

					$ostr .= '<span name="php">'. trim(@highlight_string($param, true)) .'</span><!--php-->';
					$epos = $cepos;
					$str[$cpos] = '<';
					break;
				case 'img':	// Image, image left and right.
				case 'imgl':
				case 'imgr':
					if (!$allow_img) {
						$ostr .= substr($str, $pos, ($cepos-$pos)+1);
					} else {
						$class = ($tag == 'img') ? '' : 'class="'. $tag{3} .'" ';

						if (!$parms) {
							// Relative URLs or physical with http/https/ftp.
							if ($url = url_check(substr($str, $epos+1, ($cpos-$epos)-1))) {
								$ostr .= '<img '. $class .'src="'. $url .'" border="0" alt="'. $url .'" />';
							} else {
								$ostr .= substr($str, $pos, ($cepos-$pos)+1);
							}
						} else {
							if ($url = url_check($parms)) {
								$ostr .= '<img '. $class .'src="'. $url .'" border="0" alt="'. substr($str, $epos+1, ($cpos-$epos)-1) .'" />';
							} else {
								$ostr .= substr($str, $pos, ($cepos-$pos)+1);
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
					$ostr .= '<cite>'.$parms.'</cite><blockquote>';
					$end_tag[$cpos] = '</blockquote>';
					break;
				case 'align':	// Aligh left, right or centre
					$end_tag[$cpos] = '</div><!--align-->';
					$ostr .= '<div align="'. $parms .'">';
					break;
				case 'float':	// Float left or right
					$end_tag[$cpos] = '</span><!--float-->';
					$ostr .= '<span style="float:'. $parms .'">';
					break;
				case 'left':	// Back convert to [aligh=left]
					$end_tag[$cpos] = '</div><!--align-->';
					$ostr .= '<div align="left">';
					break;
				case 'right':	// Back convert to [aligh=right]
					$end_tag[$cpos] = '</div><!--align-->';
					$ostr .= '<div align="right">';
					break;
				case 'list':
					$tmp = substr($str, $epos, ($cpos-$epos));
					$tmp_l = strlen($tmp);
					$tmp2 = str_replace(array('[*]', '[li]'), '<li>', $tmp);
					$tmp2_l = strlen($tmp2);
					$str = str_replace($tmp, $tmp2, $str);

					$diff = $tmp2_l - $tmp_l;
					$cpos += $diff;

					if (isset($end_tag)) {
						foreach($end_tag as $key => $val) {
							if ($key < $epos) {
								continue;
							}

							$end_tag[$key+$diff] = $val;
						}
					}

					switch (strtolower($parms)) {
						case '1':
						case 'decimal':
						case 'a':
							$end_tag[$cpos] = '</ol>';
							$ostr .= '<ol type="'. $parms .'">';
							break;
						case 'square':
						case 'circle':
						case 'disc':
							$end_tag[$cpos] = '</ul>';
							$ostr .= '<ul type="'. $parms .'">';
							break;
						default:
							$end_tag[$cpos] = '</ul>';
							$ostr .= '<ul>';
					}
					break;
				case 'spoiler':
					$rnd = rand();
					$end_tag[$cpos] = '</div></div>';
					$ostr .= '<div class="dashed" style="padding: 3px;" align="center"><a href="javascript://" onclick="javascript: layerVis(\'s'. $rnd .'\', 1);">'
						.($parms ? $parms : 'Toggle Spoiler') .'</a><div align="left" id="s'. $rnd .'" style="display: none;">';
					break;
				case 'acronym':
					$end_tag[$cpos] = '</acronym>';
					$ostr .= '<acronym title="'. ($parms ? $parms : ' ') .'">';
					break;
				case 'wikipedia':
					$end_tag[$cpos] = '</a>';
					$url = substr($str, $epos+1, ($cpos-$epos)-1);
					if ($parms && preg_match('!^[A-Za-z]+$!', $parms)) {
						$parms .= '.';
					} else {
						$parms = '';
					}
					$ostr .= '<a href="http://'. $parms .'wikipedia.com/wiki/'. $url .'" name="WikiPediaLink">';
					break;
				case 'indent':
				case 'tab':
					$end_tag[$cpos] = '</span><!--indent-->';
					$ostr .= '<span class="indent">';
					break;
			}

			$str[$pos] = '<';
			$pos = $old_pos = $epos+1;
		} else {
			$pos = $epos+1;
		}
	}
	$ostr .= substr($str, $old_pos, strlen($str)-$old_pos);

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
				if ($ostr[$ue+4] == ';') {
					$ue += 4;
					continue;
				}
				if ($ostr[$ue+3] == ';' || $ostr[$ue+5] == ';') {
					break;
				}
			}

			if ($ue >= $l || isset($GLOBALS['seps'][$ostr[$ue]])) {
				break;
			}
		}
		$GLOBALS['seps']['='] = '=';

		$url = url_check(substr($ostr, $us+1, $ue-$us-1));
		if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^(http|ftp)/i', $url) || ($ue - $us - 1) < 9) {
			// Skip invalid or bad URL (like 'script:' or 'data:').
			$pos = $ue;
			continue;
		}
		$html_url = '<a href="'. $url .'">'. $url .'</a>';
		$html_url_l = strlen($html_url);
		$ostr = substr_replace($ostr, $html_url, $us+1, $ue-$us-1);
		$ppos = $pos;
		$pos = $us+$html_url_l;
	}

	/* E-mail parser. */
	$pos = 0;
	$ppos = 0;

	$er = array_flip(array_merge(range(0,9), range('A', 'Z'), range('a','z'), array('.', '-', '\'', '_')));

	while (($pos = @strpos($ostr, '@', $pos)) !== false) {
		if ($pos < $ppos) {
			break;
		}

		// Check if it's inside any tag.
		$i = $pos;
		while (--$i && $i>$ppos) {
			if ( $ostr[$i] == '>' || $ostr[$i] == '<') {
				break;
			}
		}
		if ($i < 0 || $ostr[$i]=='<') {
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
			if (isset($er[ $ostr[$es] ])) continue;
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
			if (isset($er[ $ostr[$ee] ])) continue;
			break;
		}
		if ($ee == ($pos+1)) {
			$ppos = $pos += 1;
			continue;
		}

		$email = substr($ostr, $es, $ee-$es);
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$ppos = $pos += 1; continue;
		}
		$email = str_replace('@', '&#64;', $email);
		$email_url = '<a href="mailto:'. $email .'">'. $email .'</a>';
		$email_url_l = strlen($email_url);
		$ostr = substr_replace($ostr, $email_url, $es, $ee-$es);
		$ppos =	$es+$email_url_l;
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
		$fudml = str_replace($res[0], "[php]\n". $m ."\n[/php]", $fudml);
	}

	// Wikipedia tags.
	while (preg_match('!<a href="http://(?:([A-ZA-z]+)?\.)?wikipedia.com/wiki/([^"]+)"( target="_blank")? name="WikiPediaLink">(.*?)</a>!s', $fudml, $res)) {
		if (count($res) == 5) {
			$fudml = str_replace($res[0], '[wikipedia='. $res[1] .']'. $res[2] .'[/wikipedia]', $fudml);
		} else {
			$fudml = str_replace($res[0], '[wikipedia]'. $res[2] .'[/wikipedia]', $fudml);
		}
	}

	// Quote tags.
	if (strpos($fudml, '<cite>') !== false) {
               $fudml = str_replace(array('<cite>','</cite><blockquote>','</blockquote>'), array('[quote title=', ']', '[/quote]'), $fudml);
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
		while (preg_match('!<font '. $v .'=".+?">.*?</font>!is', $fudml, $m)) {
			$fudml = preg_replace('!<font '. $v .'="(.+?)">(.*?)</font>!is', '['. $v .'=\1]\2[/'. $v .']', $fudml);
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
		$fudml = str_ireplace( array('<li>', '</li>'), array("\n[*]", ''), $fudml);
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
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'file_filter_regexp';
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
if (_uid) {
	$admin_cp = $accounts_pending_approval = $group_mgr = $reported_msgs = $custom_avatar_queue = $mod_que = $thr_exch = '';

	if ($usr->users_opt & 524288 || $is_a) {	// is_mod or admin.
		if ($is_a) {
			// Approval of custom Avatars.
			if ($FUD_OPT_1 & 32 && ($avatar_count = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=16777216 AND '. q_bitand('users_opt', 16777216) .' > 0'))) {
				$custom_avatar_queue = '| <a href="/adm/admavatarapr.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Custom Avatar Queue</a> <span class="GenTextRed">('.$avatar_count.')</span>';
			}

			// All reported messages.
			if ($report_count = q_singleval('SELECT count(*) FROM fud30_msg_report')) {
				$reported_msgs = '| <a href="/index.php?t=reported&amp;'._rsid.'" rel="nofollow">Reported Messages</a> <span class="GenTextRed">('.$report_count.')</span>';
			}

			// All thread exchange requests.
			if ($thr_exchc = q_singleval('SELECT count(*) FROM fud30_thr_exchange')) {
				$thr_exch = '| <a href="/index.php?t=thr_exch&amp;'._rsid.'">Topic Exchange</a> <span class="GenTextRed">('.$thr_exchc.')</span>';
			}

			// All account approvals.
			if ($FUD_OPT_2 & 1024 && ($accounts_pending_approval = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND '. q_bitand('users_opt', 2097152) .' > 0 AND id > 0'))) {
				$accounts_pending_approval = '| <a href="/adm/admuserapr.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Accounts Pending Approval</a> <span class="GenTextRed">('.$accounts_pending_approval.')</span>';
			} else {
				$accounts_pending_approval = '';
			}

			$q_limit = '';
		} else {
			// Messages reported in moderated forums.
			if ($report_count = q_singleval('SELECT count(*) FROM fud30_msg_report mr INNER JOIN fud30_msg m ON mr.msg_id=m.id INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_mod mm ON t.forum_id=mm.forum_id AND mm.user_id='. _uid)) {
				$reported_msgs = '| <a href="/index.php?t=reported&amp;'._rsid.'" rel="nofollow">Reported Messages</a> <span class="GenTextRed">('.$report_count.')</span>';
			}

			// Thread move requests in moderated forums.
			if ($thr_exchc = q_singleval('SELECT count(*) FROM fud30_thr_exchange te INNER JOIN fud30_mod m ON m.user_id='. _uid .' AND te.frm=m.forum_id')) {
				$thr_exch = '| <a href="/index.php?t=thr_exch&amp;'._rsid.'">Topic Exchange</a> <span class="GenTextRed">('.$thr_exchc.')</span>';
			}

			$q_limit = ' INNER JOIN fud30_mod mm ON f.id=mm.forum_id AND mm.user_id='. _uid;
		}

		// Messages requiring approval.
		if ($approve_count = q_singleval('SELECT count(*) FROM fud30_msg m INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_forum f ON t.forum_id=f.id '. $q_limit .' WHERE m.apr=0 AND f.forum_opt>=2')) {
			$mod_que = '<a href="/index.php?t=modque&amp;'._rsid.'">Moderation Queue</a> <span class="GenTextRed">('.$approve_count.')</span>';
		}
	} else if ($usr->users_opt & 268435456 && $FUD_OPT_2 & 1024 && ($accounts_pending_approval = q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND '. q_bitand('users_opt', 2097152) .' > 0 AND id > 0'))) {
		$accounts_pending_approval = '| <a href="/adm/admuserapr.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Accounts Pending Approval</a> <span class="GenTextRed">('.$accounts_pending_approval.')</span>';
	} else {
		$accounts_pending_approval = '';
	}
	if ($is_a || $usr->group_leader_list) {
		$group_mgr = '| <a href="/index.php?t=groupmgr&amp;'._rsid.'">Group Manager</a>';
	}

	if ($thr_exch || $accounts_pending_approval || $group_mgr || $reported_msgs || $custom_avatar_queue || $mod_que) {
		$admin_cp = '<br /><span class="GenText fb">Admin:</span> '.$mod_que.' '.$reported_msgs.' '.$thr_exch.' '.$custom_avatar_queue.' '.$group_mgr.' '.$accounts_pending_approval.'<br />';
	}
} else {
	$admin_cp = '';
}/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> You have <span class="GenTextRed">('.$c.')</span> unread '.convertPlural($c, array('private message','private messages')).'</a></li>' : '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/twig/images/top_pm.png" alt="" /> Private Messaging</a></li>';
	} else {
		$ucp_private_msg = '';
	}

function th_frm_last_post_id($id, $th)
{
	return (int) q_singleval(q_limit('SELECT t.last_post_id FROM fud30_thread t INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE t.forum_id='. $id .' AND t.id!='. $th .' AND t.moved_to=0 AND m.apr=1 ORDER BY t.last_post_date DESC', 1));
}

	$th = isset($_GET['th']) ? (int)$_GET['th'] : (isset($_POST['th']) ? (int)$_POST['th'] : 0);
	if (!$th) {
		invl_inp_err();
	}

	/* permission check */
	if (!$is_a) {
		$perms = db_saq('SELECT mm.id, '. (_uid ? ' COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS gco ' : ' g1.group_cache_opt AS gco '). '
				FROM fud30_thread t
				LEFT JOIN fud30_mod mm ON mm.user_id='._uid.' AND mm.forum_id=t.forum_id
				'.(_uid ? 'INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id=t.forum_id LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=t.forum_id' : 'INNER JOIN fud30_group_cache g1 ON g1.user_id=0 AND g1.resource_id=t.forum_id').'
				WHERE t.id='. $th);
		if (!$perms || (!$perms[0] && !($perms[1] & 2048))) {
			std_error('access');
		}
	}

	$forum = isset($_POST['forum']) ? (int)$_POST['forum'] : 0;

	if ($forum && !empty($_POST['new_title']) && is_string($_POST['new_title']) && !empty($_POST['sel_th']) && is_array($_POST['sel_th'])) {
		/* We need to make sure that the user has access to destination forum. */
		if (!$is_a && !q_singleval('SELECT f.id FROM fud30_forum f LEFT JOIN fud30_mod mm ON mm.user_id='. _uid .' AND mm.forum_id=f.id '. (_uid ? 'INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id=f.id LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=f.id' : 'INNER JOIN fud30_group_cache g1 ON g1.user_id=0 AND g1.resource_id=f.id') .' WHERE f.id='. $forum .' AND (mm.id IS NOT NULL OR '. q_bitand(_uid ? 'COALESCE(g2.group_cache_opt, g1.group_cache_opt)' : '(g1.group_cache_opt)', 4) .' > 0)')) {
			std_error('access');
		}

		$m = array();
		foreach ($_POST['sel_th'] as $v) {
			if ((int)$v) {
				$m[] = (int) $v;
			}
		}

		/* sanity check */
		if (!$m) {
			if ($FUD_OPT_2 & 32768) {
				header('Location: /index.php/t/'. $th .'/'. _rsidl);
			} else {
				header('Location: /index.php?t='. d_thread_view .'&th='. $th .'&'. _rsidl);
			}
			exit;
		}

		$mc = count($m);
		if (isset($_POST['btn_selected'])) {
			sort($m);
			$mids = implode(',', $m);
			$start = $m[0];
			$end = $m[($mc - 1)];
		} else {
			$a = db_all('SELECT id FROM fud30_msg WHERE thread_id='. $th .' AND id NOT IN('. implode(',', $m) .') AND apr=1 ORDER BY post_stamp ASC');
			/* sanity check */
			if (!$a) {
				if ($FUD_OPT_2 & 32768) {
					header('Location: /index.php/t/'. $th .'/'. _rsidl);
				} else {
					header('Location: /index.php?t='. d_thread_view .'&th='. $th .'&'. _rsidl);
				}
				exit;
			}
			$mids = implode(',', $a);
			$mc = count($a);
			$start = $a[0];
			$end = $a[($mc - 1)];
		}

		/* Fetch all relevant information. */
		$data = db_sab('SELECT
				t.id, t.forum_id, t.replies, t.root_msg_id, t.last_post_id, t.last_post_date, t.tdescr,
				m1.post_stamp AS new_th_lps, m1.id AS new_th_lpi,
				m2.post_stamp AS old_fm_lpd,
				f1.last_post_id AS src_lpi,
				f2.last_post_id AS dst_lpi
				FROM fud30_thread t
				INNER JOIN fud30_forum f1 ON t.forum_id=f1.id
				INNER JOIN fud30_forum f2 ON f2.id='. $forum .'
				LEFT JOIN fud30_msg m1 ON m1.id='. $end .'
				LEFT JOIN fud30_msg m2 ON m2.id=f2.last_post_id
		WHERE t.id='. $th);

		if (!$data) {
			invl_inp_err();
		}

		/* Sanity check. */
		if (!$data->replies) {
			if ($FUD_OPT_2 & 32768) {
				header('Location: /index.php/t/'. $th .'/'. _rsidl);
			} else {
				header('Location: /index.php?t='. d_thread_view .'&th='. $th .'&'. _rsidl);
			}
			exit;
		}

		apply_custom_replace($_POST['new_title']);

		if ($mc != ($data->replies + 1)) { /* Check that we need to move the entire thread. */
			if ($forum != $data->forum_id) {
				$lk_pfx = 'fud30_tv_'. $forum .' WRITE,fud30_thread t WRITE,fud30_msg m WRITE,';
			} else {
				$lk_pfx = '';
			}
			db_lock($lk_pfx .'fud30_tv_'. $data->forum_id .' WRITE, fud30_thread WRITE, fud30_forum WRITE, fud30_msg WRITE, fud30_poll WRITE');

			$new_th = th_add($start, $forum, (int)$data->new_th_lps, 0, 0, ($mc - 1), 0, (int)$data->new_th_lpi);

			/* Deal with the new thread. */
			q('UPDATE fud30_msg SET thread_id='. $new_th .' WHERE id IN ('. $mids .')');
			q('UPDATE fud30_msg SET reply_to='. $start .' WHERE thread_id='. $new_th .' AND reply_to NOT IN ('. $mids .')');
			q('UPDATE fud30_msg SET reply_to=0, subject='. _esc(htmlspecialchars($_POST['new_title'])) .' WHERE id='. $start);

			/* Deal with the old thread. */
			list($lpi, $lpd) = db_saq(q_limit('SELECT id, post_stamp FROM fud30_msg WHERE thread_id='. $data->id .' AND apr=1 ORDER BY post_stamp DESC', 1));
			$old_root_msg_id = q_singleval(q_limit('SELECT id FROM fud30_msg WHERE thread_id='. $data->id .' AND apr=1 ORDER BY post_stamp ASC', 1));
			q('UPDATE fud30_msg SET reply_to='. $old_root_msg_id .' WHERE thread_id='. $data->id .' AND reply_to IN('. $mids .')');
			q('UPDATE fud30_msg SET reply_to=0 WHERE id='. $old_root_msg_id);
			q('UPDATE fud30_thread SET root_msg_id='. $old_root_msg_id .', replies=replies-'. $mc .', last_post_date='. $lpd .', last_post_id='. $lpi .' WHERE id='. $data->id);

			if ($forum != $data->forum_id) {
				$p = db_all('SELECT poll_id FROM fud30_msg WHERE thread_id='. $new_th .' AND apr=1 AND poll_id>0');
				if ($p) {
					q('UPDATE fud30_poll SET forum_id='. $data->forum_id .' WHERE id IN('. implode(',', $p) .')');
				}

				/* deal with the source forum */
				if ($data->src_lpi != $data->last_post_id || $data->last_post_date <= $lpd) {
					q('UPDATE fud30_forum SET post_count=post_count-'. $mc .' WHERE id='. $data->forum_id);
				} else {
					q('UPDATE fud30_forum SET post_count=post_count-'. $mc .', last_post_id='. th_frm_last_post_id($data->forum_id, $data->id) .' WHERE id='. $data->forum_id);
				}

				/* Deal with destination forum. */
				if ($data->old_fm_lpd > $data->new_th_lps) {
					q('UPDATE fud30_forum SET post_count=post_count+'. $mc .', thread_count=thread_count+1 WHERE id='. $forum);
				} else {
					q('UPDATE fud30_forum SET post_count=post_count+'. $mc .', thread_count=thread_count+1, last_post_id='. $data->new_th_lpi .' WHERE id='. $forum);
				}

				rebuild_forum_view_ttl($forum);
			} else {
				if ($data->src_lpi == $data->last_post_id && $data->last_post_date >= $lpd) {
					q('UPDATE fud30_forum SET thread_count=thread_count+1 WHERE id='. $data->forum_id);
				} else {
					q('UPDATE fud30_forum SET thread_count=thread_count+1, last_post_id='. $data->new_th_lpi .' WHERE id='. $data->forum_id);
				}
			}
			rebuild_forum_view_ttl($data->forum_id);
			db_unlock();
			index_text(q_singleval('SELECT subject FROM fud30_msg WHERE id='. $start), '', $start);
			logaction(_uid, 'THRSPLIT', $new_th);
			$th_id = $new_th;
		} else { /* Moving entire thread. */
			q('UPDATE fud30_msg SET subject='. _esc(htmlspecialchars($_POST['new_title'])) .' WHERE id='. $data->root_msg_id);
			if ($forum != $data->forum_id) {
				th_move($data->id, $forum, $data->root_msg_id, $thr->forum_id, $data->last_post_date, $data->last_post_id, $data->tdescr);

				if ($data->src_lpi == $data->last_post_id) {
					q('UPDATE fud30_forum SET last_post_id='. th_frm_last_post_id($data->forum_id, $data->id) .' WHERE id='. $data->forum_id);
				}
				if ($data->old_fm_lpd < $data->last_post_date) {
					q('UPDATE fud30_forum SET last_post_id='. $data->last_post_id .' WHERE id='. $forum);
				}

				logaction(_uid, 'THRMOVE', $th);
			}
			$th_id = $data->id;
		}
		if ($FUD_OPT_2 & 32768) {
			header('Location: /index.php/t/'. $th_id .'/'. _rsidl);
		} else {
			header('Location: /index.php?t='. d_thread_view .'&th='. $th_id .'&'. _rsidl);
		}
		exit;
	}
	/* Fetch a list of accesible forums. */
	$c = uq('SELECT f.id, f.name
			FROM fud30_forum f
			INNER JOIN fud30_fc_view v ON v.f=f.id
			INNER JOIN fud30_cat c ON c.id=f.cat_id
			LEFT JOIN fud30_mod mm ON mm.forum_id=f.id AND mm.user_id='. _uid .'
			INNER JOIN fud30_group_cache g1 ON g1.resource_id=f.id AND g1.user_id='. (_uid ? '2147483647' : '0') .'
			'. (_uid ? ' LEFT JOIN fud30_group_cache g2 ON g2.resource_id=f.id AND g2.user_id='. _uid : '') .'
			'. ($is_a ? '' : ' WHERE mm.id IS NOT NULL OR (
			'. q_bitand(_uid ? 'COALESCE(g2.group_cache_opt, g1.group_cache_opt)' : 'g1.group_cache_opt', 4) .' > 0)') .'
			ORDER BY v.id');
	$vl = $kl = '';
	while ($r = db_rowarr($c)) {
		$vl .= $r[0] ."\n";
		$kl .= $r[1] ."\n";
	}
	unset($c);

	if (!$forum) {
		$forum = q_singleval('SELECT forum_id FROM fud30_thread WHERE id='. $th);
	}

	$forum_sel = tmpl_draw_select_opt(rtrim($vl), rtrim($kl), $forum);
	$anon_alias = htmlspecialchars($ANON_NICK);
	$msg_entry = '';

	$c = q('SELECT m.id, m.foff, m.length, m.file_id, m.subject, m.post_stamp, u.alias FROM fud30_msg m LEFT JOIN fud30_users u ON m.poster_id=u.id WHERE m.thread_id='. $th .' AND m.apr=1 ORDER BY m.post_stamp ASC');
	while ($r = db_rowobj($c)) {
		$msg_entry .= '<tr>
	<td class="RowStyleC vt ac"><input type="checkbox" name="sel_th[]" value="'.$r->id.'" /></td>
	<td class="RowStyleA">
		<table cellspacing="1" cellpadding="2" class="ContentTable">
		<tr class="RowStyleB">
			<td class="SmallText">
				<b>Message By:</b> '.($r->alias ? htmlspecialchars($r->alias, null, null, false).'' : $anon_alias.'' )  .'<br />
				<b>Posted On:</b> '.strftime('%a, %d %B %Y %H:%M', $r->post_stamp).'<br />
				<b>Subject:</b> '.$r->subject.'
			</td>
		</tr>
		<tr class="RowStyleA">
			<td>'.read_msg_body($r->foff, $r->length, $r->file_id).'</td>
		</tr>
		</table>
	</td>
</tr>';
	}
	unset($c);

F()->response->th = $th;
F()->response->forumSelect = $forum_sel;
F()->response->messageEntry = $msg_entry;
