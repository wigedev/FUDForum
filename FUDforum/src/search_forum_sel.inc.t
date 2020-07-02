<?php
/**
* copyright            : (C) 2001-2019 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: search_forum_sel.inc.t 6334 2019-11-14 19:23:19Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

/* Draw search engine selection boxes. */
if ($is_a) {
	$c = uq('SELECT f.id, f.name, c.id
			FROM {SQL_TABLE_PREFIX}fc_view v
			INNER JOIN {SQL_TABLE_PREFIX}forum f ON f.id=v.f
			INNER JOIN {SQL_TABLE_PREFIX}cat c ON f.cat_id=c.id
			WHERE f.url_redirect IS NULL ORDER BY v.id');
} else {
	$c = uq('SELECT f.id, f.name, c.id
			FROM {SQL_TABLE_PREFIX}fc_view v
			INNER JOIN {SQL_TABLE_PREFIX}forum f ON f.id=v.f
			INNER JOIN {SQL_TABLE_PREFIX}cat c ON f.cat_id=c.id
			INNER JOIN {SQL_TABLE_PREFIX}group_cache g1 ON g1.user_id='. (_uid ? '2147483647' : '0') .' AND g1.resource_id=f.id
			LEFT JOIN {SQL_TABLE_PREFIX}mod mm ON mm.forum_id=f.id AND mm.user_id='. _uid .'
			LEFT JOIN {SQL_TABLE_PREFIX}group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=f.id
			WHERE f.url_redirect IS NULL AND mm.id IS NOT NULL OR '. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', (1|262144)) .' >= '. (1|262144) .'
			ORDER BY v.id');
}
$oldc = $forum_limit_data = ''; $g = $f = array();
if ($forum_limiter) {
	if ($forum_limiter{0} != 'c') {
		$f[$forum_limiter] = 1;
	} else {
		$g[(int)ltrim($forum_limiter, 'c')] = 1;
	}
}

while ($r = db_rowarr($c)) {
	if ($oldc != $r[2]) {
		foreach ($cat_cache as $k => $i) {
			if ($k == $r[2]) {	// Control break on Catagory ID
				break;
			}
		}
		$forum_limit_data .= '{TEMPLATE: forum_limit_cat_option}';
		$oldc = $r[2];
	}
	$forum_limit_data .= '{TEMPLATE: forum_limit_frm_option}';
}
unset($c);

/* User has no permissions to any forum, so as far as they are concerned the search is disabled. */
if (!$forum_limit_data) {
	std_error('disabled');
}

function trim_body($body)
{
	/* Remove stuff in old bad quote tags - remove in future release. */
	while (($p = strpos($body, '<table border="0" align="center" width="90%" cellpadding="3" cellspacing="1"><tr><td class="SmallText"><b>')) !== false) {
		if (($pos = strpos($body, '<br></td></tr></table>', $p)) === false) {
			$pos = strpos($body, '<br /></td></tr></table>', $p);
			if ($pos === false) {
				break;
			}
			$e = $pos + strlen('<br /></td></tr></table>');
		} else {
			$e = $pos + strlen('<br></td></tr></table>');
		}
		$body = substr($body, 0, $p) . substr($body, $e);
	}

	/* Remove stuff in quotes */
	while (preg_match('!{TEMPLATE: post_html_quote_start_p1}(.*?){TEMPLATE: post_html_quote_start_p2}(.*?){TEMPLATE: post_html_quote_end}!is', $body)) {
		$body = preg_replace('!{TEMPLATE: post_html_quote_start_p1}(.*?){TEMPLATE: post_html_quote_start_p2}(.*?){TEMPLATE: post_html_quote_end}!is', '', $body);
	}

	$body = strip_tags($body);
	$body_len = strlen($body);

	if ($body_len > $GLOBALS['MNAV_MAX_LEN']) {
		$startpos = 0;
		$srch = isset($_GET['srch']) ? trim((string)$_GET['srch']) : '';
		if (!empty($srch)) {
			// Focus on first search term.
			if (function_exists('mb_substr')) {
				$startpos = mb_stripos($body, strtok($srch, ' '));
			} else {
				$startpos = stripos($body, strtok($srch, ' '));
			}
			$startpos = $startpos - 45; // Move back for a bit of context.
			if ($body_len - $startpos < $GLOBALS['MNAV_MAX_LEN']) $startpos = $body_len - $GLOBALS['MNAV_MAX_LEN'];
			if ($startpos < 0) $startpos = 0;
		}

                // Move to starting position.
                if (function_exists('mb_substr')) {
                        $body = mb_substr($body, $startpos);
                } else {
                        $body = substr($body, $startpos);
                }
                $body = '…'. preg_replace('/^\w+\s/','',$body);

                // Cut off after max length.
                if (preg_match('/^(.{1,'. $GLOBALS['MNAV_MAX_LEN'] .'})\b/su', $body, $match)) {
                        $body=$match[0] .'…';
                } else {
                        $body = mb_substr($body, 0, $GLOBALS['MNAV_MAX_LEN']) .'…';
                }
	}
	return $body;
}
?>
