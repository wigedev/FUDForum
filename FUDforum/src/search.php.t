<?php
/**
* copyright            : (C) 2001-2020 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: search.php.t 6387 2020-04-27 10:14:03Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

//* Notes:
//    Subject lines that starts with 'Re:' are not indexed.
//    With X search terms, n_match=X means we do an AND-search.
//    Default search order is ASC, DESC or REL(evance)
//*

/*{PRE_HTML_PHP}*/

	// Check if Forum Search is enabled.
	if (!($FUD_OPT_1 & 16777216)) {
		std_error('disabled');
	}

	if (!isset($_GET['start']) || !($start = (int)$_GET['start'])) {
		$start = 0;
	}
	$ppg           = $usr->posts_ppg ? $usr->posts_ppg : $POSTS_PER_PAGE;
	$srch          = isset($_GET['srch']) ? trim((string)$_GET['srch']) : '';
	$forum_limiter = isset($_GET['forum_limiter']) ? (string)$_GET['forum_limiter'] : '';
	$field         = !isset($_GET['field']) ? 'all' : ($_GET['field'] == 'subject' ? 'subject' : 'all');
	$attach        = (isset($_GET['attach']) && $_GET['attach'] == '1') ? '1' : '0'; 
	$search_logic  = (isset($_GET['search_logic']) && $_GET['search_logic'] == 'OR') ? 'OR' : 'AND';
	if (isset($_GET['sort_order']) && ($_GET['sort_order'] == 'ASC' || $_GET['sort_order'] == 'DESC')) {
		$sort_order = $_GET['sort_order'];
	} else {
		$sort_order = 'REL';
	}
	if (!empty($_GET['author'])) {
		$author = (string) $_GET['author'];
		$author_id = q_singleval('SELECT id FROM {SQL_TABLE_PREFIX}users WHERE alias='. _esc($author));
	} else {
		$author = $author_id = '';
	}

	require $FORUM_SETTINGS_PATH .'cat_cache.inc';

function fetch_search_cache($qry, $start, $count, $logic, $srch_type, $order, $forum_limiter, &$total)
{

	if (!($wa = text_to_worda($qry))) {
		// Short and long words are filtered out and nothing is left. Try direct match on subject.
		$total = q_singleval('SELECT count(*)
		FROM {SQL_TABLE_PREFIX}msg m
		INNER JOIN {SQL_TABLE_PREFIX}thread t ON m.thread_id=t.id
		INNER JOIN {SQL_TABLE_PREFIX}forum f ON t.forum_id=f.id
		INNER JOIN {SQL_TABLE_PREFIX}cat c ON f.cat_id=c.id
		INNER JOIN {SQL_TABLE_PREFIX}group_cache g1 ON g1.user_id='. (_uid ? '2147483647' : '0') .' AND g1.resource_id=f.id
		LEFT JOIN {SQL_TABLE_PREFIX}users u ON m.poster_id=u.id
		LEFT JOIN {SQL_TABLE_PREFIX}mod mm ON mm.forum_id=f.id AND mm.user_id='. _uid .'
		LEFT JOIN {SQL_TABLE_PREFIX}group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=f.id
		WHERE m.reply_to = 0 and m.subject = '. _esc($qry) .'
			'. ($GLOBALS['is_a'] ? '' : ' AND (mm.id IS NOT NULL OR '. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)',  262146) .' >= 262146)'));

		return q(q_limit('SELECT u.alias, f.name AS forum_name, f.id AS forum_id,
			m.poster_id, m.id, m.thread_id, m.subject, m.foff, m.length, m.post_stamp, m.file_id, m.icon, m.attach_cnt,
			mm.id AS md, CASE WHEN t.root_msg_id = m.id THEN 1 ELSE 0 END AS is_rootm, '. q_bitand('t.thread_opt', 1) .' AS is_lckd
		FROM {SQL_TABLE_PREFIX}msg m
		INNER JOIN {SQL_TABLE_PREFIX}thread t ON m.thread_id=t.id
		INNER JOIN {SQL_TABLE_PREFIX}forum f ON t.forum_id=f.id
		INNER JOIN {SQL_TABLE_PREFIX}cat c ON f.cat_id=c.id
		INNER JOIN {SQL_TABLE_PREFIX}group_cache g1 ON g1.user_id='. (_uid ? '2147483647' : '0') .' AND g1.resource_id=f.id
		LEFT JOIN {SQL_TABLE_PREFIX}users u ON m.poster_id=u.id
		LEFT JOIN {SQL_TABLE_PREFIX}mod mm ON mm.forum_id=f.id AND mm.user_id='. _uid .'
		LEFT JOIN {SQL_TABLE_PREFIX}group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=f.id
		WHERE m.reply_to = 0 and m.subject = '. _esc($qry) .'
			'. ($GLOBALS['is_a'] ? '' : ' AND (mm.id IS NOT NULL OR '. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)',  262146) .' >= 262146)') .'
		ORDER BY m.subject DESC, m.post_stamp '. ($order=='ASC' ? 'ASC' : 'DESC'),
		$count, $start));
	}

	$lang =& $GLOBALS['usr']->lang;
	if ($lang != 'zh-hans' && $lang != 'zh-hant' && $lang != 'ja' && $lang != 'ko') {	// Not Chinese, Japanese nor Korean.
		if (count($wa) > 10) {
			$wa = array_slice($wa, 0, 10);
		}
	}

	$qr      = implode(',', $wa);
	$qry_lck = md5($qr);
	$i       = count($wa);

	if ($srch_type == 'all') {
		$tbl = 'index';
		$qt  = '0';
	} else {
		$tbl = 'title_index';
		$qt  = '1';
	}

	/* Remove expired cache entries. */
	q('DELETE FROM {SQL_TABLE_PREFIX}search_cache WHERE expiry<'. (__request_timestamp__ - $GLOBALS['SEARCH_CACHE_EXPIRY']));

	if (!($total = q_singleval('SELECT count(*) FROM {SQL_TABLE_PREFIX}search_cache WHERE srch_query=\''. $qry_lck .'\' AND query_type='. $qt))) {
		q('INSERT INTO {SQL_TABLE_PREFIX}search_cache (srch_query, query_type, expiry, msg_id, n_match, score) '. 
		  q_limit('SELECT \''. $qry_lck .'\', '. $qt .', '. __request_timestamp__ .', msg_id, count(*) as word_count, sum(frequency) FROM {SQL_TABLE_PREFIX}search s INNER JOIN {SQL_TABLE_PREFIX}'. $tbl .' i ON i.word_id=s.id WHERE word IN('. $qr .') GROUP BY msg_id ORDER BY word_count DESC', 
		          5000, 0));
	}

	if ($forum_limiter) {
		if ($forum_limiter{0} != 'c') {
			$qry_lmt = ' AND f.id='. (int)$forum_limiter .' ';
		} else {
			$cid = (int)substr($forum_limiter, 1);
			$cids = array();
			/* Fetch all sub-categories if there are any. */
			if (!empty($GLOBALS['cat_cache'][$cid][2])) {
				$cids = $GLOBALS['cat_cache'][$cid][2];
			}
			$cids[] = $cid;
			$qry_lmt = ' AND c.id IN('. implode(',', $cids) .') ';
		}
	} else {
		$qry_lmt = '';
	}

	if ($GLOBALS['attach'] > 0) {
		$qry_lmt .= ' AND m.attach_cnt>0';
	}

	if ($order == 'ASC' || $order == 'DESC') {
		$sort = 'm.post_stamp '. $order;
	} else {
                $sort = 'sc.score DESC, m.post_stamp DESC';
	}

	if ($GLOBALS['author_id']) {
		$qry_lmt .= ' AND m.poster_id='. $GLOBALS['author_id'] .' ';
	}

	$qry_lck = '\''. $qry_lck .'\'';

	$total = q_singleval('SELECT count(*)
		FROM {SQL_TABLE_PREFIX}search_cache sc
		INNER JOIN {SQL_TABLE_PREFIX}msg m ON m.id=sc.msg_id
		INNER JOIN {SQL_TABLE_PREFIX}thread t ON m.thread_id=t.id
		INNER JOIN {SQL_TABLE_PREFIX}forum f ON t.forum_id=f.id
		INNER JOIN {SQL_TABLE_PREFIX}cat c ON f.cat_id=c.id
		INNER JOIN {SQL_TABLE_PREFIX}group_cache g1 ON g1.user_id='. (_uid ? '2147483647' : '0') .' AND g1.resource_id=f.id
		LEFT JOIN {SQL_TABLE_PREFIX}mod mm ON mm.forum_id=f.id AND mm.user_id='. _uid .'
		LEFT JOIN {SQL_TABLE_PREFIX}group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=f.id
		WHERE
			sc.query_type='. $qt .' AND sc.srch_query='. $qry_lck . $qry_lmt .'
			'. ($logic == 'AND' ? ' AND sc.n_match>='. $i : '') .'
			'. ($GLOBALS['is_a'] ? '' : ' AND (mm.id IS NOT NULL OR '. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 262146) .' >= 262146)') );
	if (!$total) {
		return;
	}

	return q(q_limit('SELECT u.alias, f.name AS forum_name, f.id AS forum_id,
			m.poster_id, m.id, m.thread_id, m.subject, m.foff, m.length, m.post_stamp, m.file_id, m.icon, m.attach_cnt,
			mm.id AS md, CASE WHEN t.root_msg_id = m.id THEN 1 ELSE 0 END AS is_rootm, '. q_bitand('t.thread_opt', 1) .' AS is_lckd
		FROM {SQL_TABLE_PREFIX}search_cache sc
		INNER JOIN {SQL_TABLE_PREFIX}msg m ON m.id=sc.msg_id
		INNER JOIN {SQL_TABLE_PREFIX}thread t ON m.thread_id=t.id
		INNER JOIN {SQL_TABLE_PREFIX}forum f ON t.forum_id=f.id
		INNER JOIN {SQL_TABLE_PREFIX}cat c ON f.cat_id=c.id
		INNER JOIN {SQL_TABLE_PREFIX}group_cache g1 ON g1.user_id='. (_uid ? '2147483647' : '0') .' AND g1.resource_id=f.id
		LEFT JOIN {SQL_TABLE_PREFIX}users u ON m.poster_id=u.id
		LEFT JOIN {SQL_TABLE_PREFIX}mod mm ON mm.forum_id=f.id AND mm.user_id='. _uid .'
		LEFT JOIN {SQL_TABLE_PREFIX}group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=f.id
		WHERE
			sc.query_type='. $qt .' AND sc.srch_query='. $qry_lck . $qry_lmt .'
			'. ($logic == 'AND' ? ' AND sc.n_match>='.$i : '') .'
			'. ($GLOBALS['is_a'] ? '' : ' AND (mm.id IS NOT NULL OR '. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)',  262146) .' >= 262146)') .'
		ORDER BY sc.n_match DESC, '. $sort,
		$count, $start));
}

/*{POST_HTML_PHP}*/

	$search_options = tmpl_draw_radio_opt('field', "all\nsubject", "{TEMPLATE: search_entire_msg}\n{TEMPLATE: search_subject_only}", $field, '{TEMPLATE: radio_button_separator}');
	$logic_options  = tmpl_draw_select_opt("AND\nOR", "{TEMPLATE: search_and}\n{TEMPLATE: search_or}", $search_logic);
	$sort_options   = tmpl_draw_select_opt("REL\nDESC\nASC", "{TEMPLATE: search_relevance_order}\n{TEMPLATE: search_desc_order}\n{TEMPLATE: search_asc_order}", $sort_order);
	$attach_options = tmpl_draw_select_opt("0\n1", "{TEMPLATE: search_attach_all}\n{TEMPLATE: search_attach_with}", $attach);

	$TITLE_EXTRA = ': {TEMPLATE: search_title}';

	ses_update_status($usr->sid, '{TEMPLATE: search_update}');

	if ($srch) {

		if (defined('plugins') && isset($plugin_hooks['SEARCH'])) {
			list($search_data, $page_pager) = plugin_call_hook('SEARCH', $srch);
		} else if (!($c = fetch_search_cache($srch, $start, $ppg, $search_logic, $field, $sort_order, $forum_limiter, $total))) {
			$search_data = '{TEMPLATE: no_search_results}';
			$page_pager = '';
// TODO: Try to fix spelling errors.
//$c = uq('SELECT word from fud30_search where word SOUNDS LIKE '. _esc($srch));
//while ($r = db_rowarr($c)) {
//      echo "Probeer ". $r[0] ."</br>";
//}
		} else {
			$i = 0;
			$search_data = '';
			while ($r = db_rowobj($c)) {
				$search_data .= '{TEMPLATE: search_entry}';
			}
			unset($c);
			$search_data = '{TEMPLATE: search_results}';
			if ($FUD_OPT_2 & 32768) {	// USE_PATH_INFO?
				$page_pager = tmpl_create_pager($start, $ppg, $total, '{ROOT}/s/'. urlencode($srch) .'/'. $field .'/'. $search_logic .'/'. $sort_order .'/'. ($forum_limiter ? $forum_limiter : 0) .'/', '/'. urlencode($author) .'/'. _rsid);
			} else {
				$page_pager = tmpl_create_pager($start, $ppg, $total, '{ROOT}?t=search&amp;srch='. urlencode($srch) .'&amp;field='. $field .'&amp;'. _rsid .'&amp;search_logic='. $search_logic .'&amp;sort_order='. $sort_order .'&amp;forum_limiter='. $forum_limiter .'&amp;author='. urlencode($author));
			}
		}
	} else {
		$search_data = $page_pager = '';

                // Since we have nothing better to do, check for unindexed messages and index a few.
                $c = uq(q_limit('SELECT id, foff, length, file_id, subject FROM {SQL_TABLE_PREFIX}msg m
                                WHERE NOT EXISTS (SELECT 1 FROM {SQL_TABLE_PREFIX}index i WHERE m.id = i.msg_id)', 5));
                while ($r = db_rowobj($c)) {
                        index_text($r->subject, read_msg_body($r->foff, $r->length, $r->file_id), $r->id);
                }
                unset($r);

                // Check for messages without frquency and re-index them.
                $c = uq(q_limit('SELECT id, foff, length, file_id, subject FROM {SQL_TABLE_PREFIX}msg m
                                WHERE EXISTS (SELECT 1 FROM {SQL_TABLE_PREFIX}index i WHERE m.id = i.msg_id AND i.frequency = 0)', 5));
                while ($r = db_rowobj($c)) {
                        index_text($r->subject, read_msg_body($r->foff, $r->length, $r->file_id), $r->id);
                }
                unset($r);

	}

/*{POST_PAGE_PHP_CODE}*/
?>
{TEMPLATE: SEARCH_PAGE}
