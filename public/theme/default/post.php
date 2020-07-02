<?php
/**
* copyright            : (C) 2001-2019 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: post.php.t 6314 2019-09-21 08:15:45Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

function flood_check()
{
	$check_time = __request_timestamp__-$GLOBALS['FLOOD_CHECK_TIME'];

	if (($v = q_singleval(q_limit('SELECT /* USE MASTER */ post_stamp FROM fud30_msg WHERE ip_addr=\''. get_ip() .'\' AND poster_id='. _uid .' AND post_stamp>'. $check_time .' ORDER BY post_stamp DESC', 1)))) {
		return ($v - $check_time);
	}

	return;
}

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
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
}function tmpl_draw_radio_opt($name, $values, $names, $selected, $sep)
{
	$vls = explode("\n", $values);
	$nms = explode("\n", $names);

	if (count($vls) != count($nms)) {
		exit("FATAL ERROR: inconsistent number of values<br />\n");
	}

	$checkboxes = '';
	foreach ($vls as $k => $v) {
		$checkboxes .= '<label><input type="radio" name="'.$name.'" value="'.$v.'" '.($v == $selected ? 'checked="checked" ' : '' )  .' />'.$nms[$k].'</label>'.$sep;
	}

	return $checkboxes;
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
}function tmpl_post_options($arg, $perms=0)
{
	$post_opt_html		= '<b>HTML</b> code is <b>off</b>';
	$post_opt_fud		= '<b>BBcode</b> is <b>off</b>';
	$post_opt_images 	= '<b>Images</b> are <b>off</b>';
	$post_opt_smilies	= '<b>Smilies</b> are <b>off</b>';
	$edit_time_limit	= '';

	if (is_int($arg)) {
		if ($arg & 16) {
			$post_opt_fud = '<a href="/index.php?section=readingposting&amp;t=help_index&amp;'._rsid.'#style" target="_blank"><b>BBcode</b> is <b>on</b></a>';
		} else if (!($arg & 8)) {
			$post_opt_html = '<b>HTML</b> is <b>on</b>';
		}
		if ($perms & 16384) {
			$post_opt_smilies = '<a href="/index.php?section=readingposting&amp;t=help_index&amp;'._rsid.'#sml" target="_blank"><b>Smilies</b> are <b>on</b></a>';
		}
		if ($perms & 32768) {
			$post_opt_images = '<b>Images</b> are <b>on</b>';
		}
		if ($GLOBALS['EDIT_TIME_LIMIT'] >= 0) {	// Time limit enabled,
			$edit_time_limit = $GLOBALS['EDIT_TIME_LIMIT'] ? '<br /><b>Editing Time Limit</b>: '.$GLOBALS['EDIT_TIME_LIMIT'].' minutes' : '<br /><b>Editing Time Limit</b>: Unlimited';
		}
	} else if ($arg == 'private') {
		$o =& $GLOBALS['FUD_OPT_1'];

		if ($o & 4096) {
			$post_opt_fud = '<a href="/index.php?section=readingposting&amp;t=help_index&amp;'._rsid.'#style" target="_blank"><b>BBcode</b> is <b>on</b></a>';
		} else if (!($o & 2048)) {
			$post_opt_html = '<b>HTML</b> is <b>on</b>';
		}
		if ($o & 16384) {
			$post_opt_images = '<b>Images</b> are <b>on</b>';
		}
		if ($o & 8192) {
			$post_opt_smilies = '<a href="/index.php?section=readingposting&amp;t=help_index&amp;'._rsid.'#sml" target="_blank"><b>Smilies</b> are <b>on</b></a>';
		}
	} else if ($arg == 'sig') {
		$o =& $GLOBALS['FUD_OPT_1'];

		if ($o & 131072) {
			$post_opt_fud = '<a href="/index.php?section=readingposting&amp;t=help_index&amp;'._rsid.'#style" target="_blank"><b>BBcode</b> is <b>on</b></a>';
		} else if (!($o & 65536)) {
			$post_opt_html = '<b>HTML</b> is <b>on</b>';
		}
		if ($o & 524288) {
			$post_opt_images = '<b>Images</b> are <b>on</b>';
		}
		if ($o & 262144) {
			$post_opt_smilies = '<a href="/index.php?section=readingposting&amp;t=help_index&amp;'._rsid.'#sml" target="_blank"><b>Smilies</b> are <b>on</b></a>';
		}
	}

	return 'Forum Options:<br /><span class="SmallText">
'.$post_opt_html.'<br />
'.$post_opt_fud.'<br />
'.$post_opt_images.'<br />
'.$post_opt_smilies.$edit_time_limit.'</span>';
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
}function fud_wrap_tok($data)
{
	$wa = array();
	$len = strlen($data);

	$i=$j=$p=0;
	$str = '';
	while ($i < $len) {
		switch ($data{$i}) {
			case ' ':
			case "\n":
			case "\t":
				if ($j) {
					$wa[] = array('word'=>$str, 'len'=>($j+1));
					$j=0;
					$str ='';
				}

				$wa[] = array('word'=>$data[$i]);

				break;
			case '<':
				if (($p = strpos($data, '>', $i)) !== false) {
					if ($j) {
						$wa[] = array('word'=>$str, 'len'=>($j+1));
						$j=0;
						$str ='';
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
							$wa[] = array('word'=>$str, 'len'=>($j+1));
							$j=0;
							$str ='';
						}

						$wa[] = array('word' => $st, 'sp' => 1);
						$i=$e;
						$j=0;
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
		$wa[] = array('word'=>$str, 'len'=>($j+1));
	}

	return $wa;
}

/* Wrap messages by inserting a space into strings longer the spesified length. */
function fud_wordwrap(&$data)
{
	$m = (int) $GLOBALS['WORD_WRAP'];
	if (!$m || $m >= strlen($data)) {
		return;
	}

	$wa = fud_wrap_tok($data);
	$l = 0;
	$data = '';
	foreach($wa as $v) {
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
}function init_spell($type, $dict)
{
	$pspell_config = pspell_config_create($dict);
	pspell_config_mode($pspell_config, $type);
	pspell_config_personal($pspell_config, $GLOBALS['FORUM_SETTINGS_PATH'] .'forum.pws');
	pspell_config_ignore($pspell_config, 2);
	define('__FUD_PSPELL_LINK__', pspell_new_config($pspell_config));

	return true;
}

function tokenize_string($data)
{
	if (!($len = strlen($data))) {
		return array();
	}
	$wa = array();

	$i = $p = 0;
	$seps = array(','=>1,' '=>1,'/'=>1,'\\'=>1,'.'=>1,','=>1,'!'=>1,'>'=>1,'?'=>1,"\n"=>1,"\r"=>1,"\t"=>1,')'=>1,'('=>1,'}'=>1,'{'=>1,'['=>1,']'=>1,'*'=>1,';'=>1,'='=>1,':'=>1,'1'=>1,'2'=>1,'3'=>1,'4'=>1,'5'=>1,'6'=>1,'7'=>1,'8'=>1,'9'=>1,'0'=>1);

	while ($i < $len) {
		if (isset($seps[$data{$i}])) {
			if (isset($str)) {
				$wa[] = array('token'=>$str, 'check'=>1);
				unset($str);
			}
			$wa[] = array('token'=>$data[$i], 'check'=>0);
		} else if ($data{$i} == '<') {
			if (($p = strpos($data, '>', $i)) !== false) {
				if (isset($str)) {
					$wa[] = array('token'=>$str, 'check'=>1);
					unset($str);
				}

				$wrd = substr($data,$i,($p-$i)+1);
				$p3 = $l = null;

				/* remove code blocks */
				if ($wrd == '<pre>') {
					$l = 'pre';
					
				/* Deal with bad old style quotes - remove in future release. */
				} else if ($wrd == '<table border="0" align="center" width="90%" cellpadding="3" cellspacing="1">') {
					$l = 1;
					$p3 = $p;

					while ($l > 0) {
						$p3 = strpos($data, 'table', $p3);

						if ($data[$p3-1] == '<') {
							$l++;
						} else if ($data[$p3-1] == '/' && $data[$p3-2] == '<') {
							$l--;
						}

						$p3 = strpos($data, '>', $p3);
					}
					
				/* Remove new style quotes. */
				} else if ($wrd == '<blockquote>') {
					$l = 1;
					$p3 = $p;

					while ($l > 0) {
						$p3 = strpos($data, 'blockquote', $p3);

						if ($data[$p3-1] == '<') {
							$l++;
						} else if ($data[$p3-1] == '/' && $data[$p3-2] == '<') {
							$l--;
						}

						$p3 = strpos($data, '>', $p3);
					}
				}

				if ($p3) {
					$p = $p3;
					$wrd = substr($data, $i, ($p-$i)+1);
				} else if ($l && ($p2 = strpos($data, '</'.$l.'>', $p))) {
					$p = $p2+1+strlen($l)+1;
					$wrd = substr($data,$i,($p-$i)+1);
				}

				$wa[] = array('token'=>$wrd, 'check'=>0);
				$i = $p;
			} else {
				$str .= $data[$i];
			}
		} else if ($data{$i} == '&') {
			if (isset($str)) {
				$wa[] = array('token'=>$str, 'check'=>1);
				unset($str);
			}

			$regs = array();
			if (preg_match('!(\&[A-Za-z0-9]{2,5}\;)!', substr($data,$i,6), $regs)) {
				$wa[] = array('token'=>$regs[1], 'check'=>0);
				$i += strlen($regs[1])-1;
			} else {
				$wa[] = array('token'=>$data[$i], 'check'=>0);
			}
		} else if (isset($str)) {
			$str .= $data[$i];
		} else {
			$str = $data[$i];
		}
		$i++;
	}

	if (isset($str)) {
		$wa[] = array('token'=>$str, 'check'=>1);
	}

	return $wa;
}

function draw_spell_sug_select($v,$k,$type)
{
	$sel_name = 'spell_chk_'. $type .'_'. $k;
	$data = '<select name="'. $sel_name .'">';
	$data .= '<option value="'. htmlspecialchars($v['token']) .'">'. htmlspecialchars($v['token']) .'</option>';
	$i = 0;
	foreach(pspell_suggest(__FUD_PSPELL_LINK__, $v['token']) as $va) {
		$data .= '<option value="'. $va .'">'. ++$i .') '. $va .'</option>';
	}

	if (!$i) {
		$data .= '<option value="">no alternatives</option>';
	}

	$data .= '</select>';

	return $data;
}

function spell_replace($wa,$type)
{
	$data = '';

	foreach($wa as $k => $v) {
		if( $v['check']==1 && isset($_POST['spell_chk_'. $type .'_'. $k]) && strlen($_POST['spell_chk_'. $type .'_'. $k])) {
			$data .= $_POST['spell_chk_'. $type .'_'. $k];
		} else {
			$data .= $v['token'];
		}
	}

	return $data;
}

function spell_check_ar($wa,$type)
{
	foreach($wa as $k => $v) {
		if ($v['check'] > 0 && !pspell_check(__FUD_PSPELL_LINK__, $v['token'])) {
			$wa[$k]['token'] = draw_spell_sug_select($v, $k, $type);
		}
	}

	return $wa;
}

function reasemble_string($wa)
{
	$data = '';
	foreach($wa as $v) {
		$data .= $v['token'];
	}

	return $data;
}

function check_data_spell($data, $type, $dict)
{
	if (!$data || (!defined('__FUD_PSPELL_LINK__') && !init_spell(PSPELL_FAST, $dict))) {
		return $data;
	}

	return reasemble_string(spell_check_ar(tokenize_string($data), $type));
}function is_notified($user_id, $thread_id)
{
	return q_singleval('SELECT * FROM fud30_thread_notify WHERE thread_id='. (int)$thread_id .' AND user_id='. $user_id);
}

function thread_notify_add($user_id, $thread_id)
{
	db_li('INSERT INTO fud30_thread_notify (user_id, thread_id) VALUES ('. $user_id .', '. (int)$thread_id .')', $ret);
}

function thread_notify_del($user_id, $thread_id)
{
	q('DELETE FROM fud30_thread_notify WHERE thread_id='. (int)$thread_id .' AND user_id='. $user_id);
}

function thread_bookmark_add($user_id, $thread_id)
{
	db_li('INSERT INTO fud30_bookmarks (user_id, thread_id) VALUES ('. $user_id .', '. (int)$thread_id .')', $ret);
}

function thread_bookmark_del($user_id, $thread_id)
{
	q('DELETE FROM fud30_bookmarks WHERE thread_id='. (int)$thread_id .' AND user_id='. $user_id);
}$GLOBALS['__error__'] = 0;
$GLOBALS['__err_msg__'] = array();

function set_err($err, $msg)
{
	$GLOBALS['__err_msg__'][$err] = $msg;
	$GLOBALS['__error__'] = 1;
}

function is_post_error()
{
	return $GLOBALS['__error__'];
}

function get_err($err, $br=0)
{
	if (isset($err, $GLOBALS['__err_msg__'][$err])) {
		return ($br ? '<span class="ErrorText">'.$GLOBALS['__err_msg__'][$err].'</span><br />' : '<br /><span class="ErrorText">'.$GLOBALS['__err_msg__'][$err].'</span>');
	}
}

function post_check_images()
{
	if (!empty($_POST['msg_body']) && $GLOBALS['MAX_IMAGE_COUNT'] && $GLOBALS['MAX_IMAGE_COUNT'] < count_images((string)$_POST['msg_body'])) {
		return -1;
	}

	return 0;
}

function check_post_form()
{
	/* Make sure we got a valid subject. */
	if (!strlen(trim((string)$_POST['msg_subject']))) {
		set_err('msg_subject', 'Subject required');
	}

	/* Make sure the number of images [img] inside the body do not exceed the allowed limit. */
	if (post_check_images()) {
		set_err('msg_body', 'No more than '.$GLOBALS['MAX_IMAGE_COUNT'].' images are allowed per message. Please reduce the number of images.');
	}

	/* Captcha check for anon users. */
	if (!_uid && $GLOBALS['FUD_OPT_3'] & 8192 ) {
		if (empty($_POST['turing_test']) || empty($_POST['turing_res']) || !test_turing_answer($_POST['turing_test'], $_POST['turing_res'])) {
			set_err('reg_turing', 'Invalid validation code.');
		}
	}

	if (defined('fud_bad_sq')) {
		unset($_POST['submitted']);
		set_err('msg_session', '<h4 class="ErrorText ac">Your session has expired. Please re-submit the form. Sorry for the inconvenience.</h4>');
	}

	/* Check for duplicate topics (exclude replies and edits). */
	if (($GLOBALS['FUD_OPT_3'] & 67108864) && $_POST['reply_to'] == 0 && $_POST['msg_id'] == 0) {
		$c = q_singleval('SELECT count(*) FROM fud30_msg WHERE subject='. _esc($_POST['msg_subject']) .' AND reply_to=0 AND poster_id='. _uid .' AND post_stamp >= '. (__request_timestamp__ - 86400));
		if ( $c > 0 ) {
			set_err('msg_body', 'Please do not create duplicate topics.');
		}
	}

	/* Check against minimum post length. */
	if ($GLOBALS['POST_MIN_LEN']) {
		$body_without_bbcode = preg_replace('/\[(.*?)\]|\s+/', '', $_POST['msg_body']);	// Remove tags and whitespace.
		if (strlen($body_without_bbcode) < $GLOBALS['POST_MIN_LEN']) {
			$post_min_len = $GLOBALS['POST_MIN_LEN'];
			set_err('msg_body', 'Your message is too short. The minimum length is '.convertPlural($post_min_len, array(''.$post_min_len.' character',''.$post_min_len.' characters')).'.');
		}
		unset($body_without_bbcode);
	}

	/* Check if user is allowed to post links. */
	if ($GLOBALS['POSTS_BEFORE_LINKS'] && !empty($_POST['msg_body'])) {
		if (preg_match('?(\[url)|(http://)|(https://)?i', $_POST['msg_body'])) {
			$c = q_singleval('SELECT posted_msg_count FROM fud30_users WHERE id='. _uid);
			if ( $GLOBALS['POSTS_BEFORE_LINKS'] > $c ) {
				$posts_before_links = $GLOBALS['POSTS_BEFORE_LINKS'];
				set_err('msg_body', 'You cannot use links until you have posted more than '.convertPlural($posts_before_links, array(''.$posts_before_links.' message',''.$posts_before_links.' messages')).'.');
			}
		}
	}

	return $GLOBALS['__error__'];
}

function check_ppost_form($msg_subject)
{
	if (!strlen(trim($msg_subject))) {
		set_err('msg_subject', 'Subject required');
	}

	if (post_check_images()) {
		set_err('msg_body', 'No more than '.$GLOBALS['MAX_IMAGE_COUNT'].' images are allowed per message. Please reduce the number of images.');
	}

	if (empty($_POST['msg_to_list'])) {
		set_err('msg_to_list', 'Cannot send a message, missing recipient');
	} else {
		$GLOBALS['recv_user_id'] = array();
		/* Hack for login names containing HTML entities ex. &#123; */
		if (($hack = strpos($_POST['msg_to_list'], '&#')) !== false) {
			$hack_str = preg_replace('!&#([0-9]+);!', '&#\1#', $_POST['msg_to_list']);
		} else {
			$hack_str = $_POST['msg_to_list'];
		}
		foreach(explode(';', $hack_str) as $v) {
			$v = trim($v);
			if (strlen($v)) {
				if ($hack !== false) {
					$v = preg_replace('!&#([0-9]+)#!', '&#\1;', $v);
				}
				if (!($obj = db_sab('SELECT u.users_opt, u.id, ui.ignore_id FROM fud30_users u LEFT JOIN fud30_user_ignore ui ON ui.user_id=u.id AND ui.ignore_id='. _uid .' WHERE u.alias='. ssn(char_fix(htmlspecialchars($v)))))) {
					set_err('msg_to_list', 'There is no user named "'.char_fix(htmlspecialchars($v)).'" in this forum.');
					break;
				}
				if (!empty($obj->ignore_id)) {
					set_err('msg_to_list', 'You cannot send a private message to "'.char_fix(htmlspecialchars($v)).'", because this user is ignoring you.');
					break;
				} else if (!($obj->users_opt & 32) && !$GLOBALS['is_a']) {
					set_err('msg_to_list', 'You cannot send a private message to "'.htmlspecialchars($v, null, null, false).'", because this person is not accepting private messages.');
					break;
				} else {
					$GLOBALS['recv_user_id'][] = $obj->id;
				}
			}
		}
	}

	if (defined('fud_bad_sq')) {
		unset($_POST['btn_action']);
		set_err('msg_session', '<h4 class="ErrorText ac">Your session has expired. Please re-submit the form. Sorry for the inconvenience.</h4>');
	}

	return $GLOBALS['__error__'];
}

function check_femail_form()
{
	if (empty($_POST['femail']) || validate_email($_POST['femail'])) {
		set_err('femail', 'Please enter a valid e-mail address for your friend.');
	}
	if (empty($_POST['subj'])) {
		set_err('subj', 'You cannot send an e-mail without a subject.');
	}
	if (empty($_POST['body'])) {
		set_err('body', 'You cannot send an e-mail without the message body.');
	}
	if (defined('fud_bad_sq')) {
		unset($_POST['posted']);
		set_err('msg_session', '<h4 class="ErrorText ac">Your session has expired. Please re-submit the form. Sorry for the inconvenience.</h4>');
	}

	return $GLOBALS['__error__'];
}

function count_images($text)
{
	$text = strtolower($text);
	$a = substr_count($text, '[img]');
	$b = substr_count($text, '[/img]');

	return (($a > $b) ? $b : $a);
}function poll_delete($id)
{
	if (!$id) {
		return;
	}

	q('UPDATE fud30_msg SET poll_id=0 WHERE poll_id='. $id);
	q('DELETE FROM fud30_poll_opt WHERE poll_id='. $id);
	q('DELETE FROM fud30_poll_opt_track WHERE poll_id='. $id);
	q('DELETE FROM fud30_poll WHERE id='. $id);
}

function poll_fetch_opts($id)
{
	$a = array();
	$c = uq('SELECT id,name FROM fud30_poll_opt WHERE poll_id='. $id);
	while ($r = db_rowarr($c)) {
		$a[$r[0]] = $r[1];
	}
	unset($c);

	return $a;
}

function poll_del_opt($id, $poll_id)
{
	q('DELETE FROM fud30_poll_opt WHERE poll_id='. $poll_id .' AND id='. $id);
	q('DELETE FROM fud30_poll_opt_track WHERE poll_id='. $poll_id .' AND poll_opt='. $id);
	q('UPDATE fud30_poll SET total_votes=(SELECT SUM(count) FROM fud30_poll_opt WHERE poll_id='. $poll_id .') WHERE id='. $poll_id);
}

function poll_activate($poll_id, $frm_id)
{
	q('UPDATE fud30_poll SET forum_id='. $frm_id .' WHERE id='. $poll_id);
}

function poll_sync($poll_id, $name, $max_votes, $expiry)
{
	q('UPDATE fud30_poll SET name='. _esc(htmlspecialchars($name)) .', expiry_date='. (int)$expiry .', max_votes='. (int)$max_votes .' WHERE id='. $poll_id);
}

function poll_add($name, $max_votes, $expiry, $uid=_uid)
{
	return db_qid('INSERT INTO fud30_poll (name, owner, creation_date, expiry_date, max_votes) VALUES ('. _esc(htmlspecialchars($name)) .', '. $uid .', '. __request_timestamp__ .', '. (int)$expiry .', '. (int)$max_votes .')');
}

function poll_opt_sync($id, $name)
{
	q('UPDATE fud30_poll_opt SET name='. _esc($name) .' WHERE id='. $id);
}

function poll_opt_add($name, $poll_id)
{
	return db_qid('INSERT INTO fud30_poll_opt (poll_id,name) VALUES('. $poll_id .', '. _esc($name) .')');
}

function poll_validate($poll_id, $msg_id)
{
	if (($mid = (int) q_singleval('SELECT id FROM fud30_msg WHERE poll_id='. $poll_id)) && $mid != $msg_id) {
		return 0;
	}
	return $poll_id;
}class fud_msg
{
	var $id, $thread_id, $poster_id, $reply_to, $ip_addr, $host_name, $post_stamp, $subject, $attach_cnt, $poll_id,
	    $update_stamp, $icon, $apr, $updated_by, $login, $length, $foff, $file_id, $msg_opt,
	    $file_id_preview, $length_preview, $offset_preview, $body, $mlist_msg_id;
}

$GLOBALS['CHARSET'] = 'utf-8';

class fud_msg_edit extends fud_msg
{
	function add_reply($reply_to, $th_id=null, $perm, $autoapprove=1)
	{
		if ($reply_to) {
			$this->reply_to = $reply_to;
			$fd = db_saq('SELECT t.forum_id, f.message_threshold, f.forum_opt FROM fud30_msg m INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_forum f ON f.id=t.forum_id WHERE m.id='. $reply_to);
		} else {
			$fd = db_saq('SELECT t.forum_id, f.message_threshold, f.forum_opt FROM fud30_thread t INNER JOIN fud30_forum f ON f.id=t.forum_id WHERE t.id='. $th_id);
		}

		return $this->add($fd[0], $fd[1], $fd[2], $perm, $autoapprove);
	}

	function add($forum_id, $message_threshold, $forum_opt, $perm, $autoapprove=1, $msg_tdescr='')
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
		$this->subject = substr($this->subject, 0, 255);	// Subject col is VARCHAR(255).

		if ($GLOBALS['FUD_OPT_3'] & 32768) {	// DB_MESSAGE_STORAGE
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
		if ($GLOBALS['FUD_OPT_3'] & 524288) {	// ENABLE_GEO_LOCATION.
			$flag = db_saq('SELECT cc, country FROM fud30_geoip WHERE '. sprintf('%u', 	ip2long($this->ip_addr)) .' BETWEEN ips AND ipe');
		}
		if (empty($flag)) {
			$flag = array(null, null);
		}

		$this->id = db_qid('INSERT INTO fud30_msg (
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
			'. $this->thread_id .',
			'. $this->poster_id .',
			'. (int)$this->reply_to .',
			\''. $this->ip_addr .'\',
			'. $this->host_name .',
			'. $this->post_stamp .',
			'. ssn($this->subject) .',
			'. (int)$this->attach_cnt .',
			'. (int)$this->poll_id .',
			'. ssn($this->icon) .',
			'. $this->msg_opt .',
			'. $file_id .',
			'. (int)$offset .',
			'. (int)$length .',
			'. $file_id_preview .',
			'. $offset_preview .',
			'. $length_preview .',
			'. ssn($this->mlist_msg_id) .',
			'. ssn(poll_cache_rebuild($this->poll_id)) .',
			'. ssn($flag[0]) .',
			'. ssn($flag[1]) .'
		)');

		if ($GLOBALS['FUD_OPT_3'] & 32768) {	// DB_MESSAGE_STORAGE
			$file_id = db_qid('INSERT INTO fud30_msg_store (data) VALUES('. _esc($this->body) .')');
			if ($message_threshold && $length > $message_threshold) {
				$file_id_preview = db_qid('INSERT INTO fud30_msg_store (data) VALUES('. _esc(trim_html($this->body, $message_threshold)) .')');
			}
			q('UPDATE fud30_msg SET file_id='. $file_id .', file_id_preview='. $file_id_preview .' WHERE id='. $this->id);
		}

		$thread_opt = (int) ($perm & 4096 && isset($_POST['thr_locked']));

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

			$this->thread_id = th_add($this->id, $forum_id, $this->post_stamp, $thread_opt, (isset($thr_orderexpiry) ? $thr_orderexpiry : 0), 0, 0, 0, $msg_tdescr);

			q('UPDATE fud30_msg SET thread_id='. $this->thread_id .' WHERE id='. $this->id);
		} else {
			th_lock($this->thread_id, $thread_opt & 1);
		}

		if ($autoapprove && $forum_opt & 2) {
			$this->approve($this->id);
		}

		return $this->id;
	}

	function sync($id, $frm_id, $message_threshold, $perm, $msg_tdescr='')
	{
		$this->subject = substr($this->subject, 0, 255);	// Subject col is VARCHAR(255).

		if ($GLOBALS['FUD_OPT_3'] & 32768) {	// DB_MESSAGE_STORAGE
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

		q('UPDATE fud30_msg SET
			file_id='. $file_id .',
			foff='. (int)$offset .',
			length='. (int)$length .',
			mlist_msg_id='. ssn($this->mlist_msg_id) .',
			file_id_preview='. $file_id_preview .',
			offset_preview='. $offset_preview .',
			length_preview='. $length_preview .',
			updated_by='. $id .',
			msg_opt='. $this->msg_opt .',
			attach_cnt='. (int)$this->attach_cnt .',
			poll_id='. (int)$this->poll_id .',
			update_stamp='. __request_timestamp__ .',
			icon='. ssn($this->icon) .' ,
			poll_cache='. ssn(poll_cache_rebuild($this->poll_id)) .',
			subject='. ssn($this->subject) .'
		WHERE id='. $this->id);

		if ($GLOBALS['FUD_OPT_3'] & 32768) {	// DB_MESSAGE_STORAGE
//TODO: Why DELETE? Can't we just UPDATE the DB?
			q('DELETE FROM fud30_msg_store WHERE id IN('. $this->file_id .','. $this->file_id_preview .')');
			$file_id = db_qid('INSERT INTO fud30_msg_store (data) VALUES('. _esc($this->body) .')');
			if ($message_threshold && $length > $message_threshold) {
				$file_id_preview = db_qid('INSERT INTO fud30_msg_store (data) VALUES('. _esc(trim_html($this->body, $message_threshold)) .')');
			}
			q('UPDATE fud30_msg SET file_id='. $file_id .', file_id_preview='. $file_id_preview .' WHERE id='. $this->id);
		}

		/* Determine wether or not we should deal with locked & sticky stuff
		 * current approach may seem a little redundant, but for (most) users who
		 * do not have access to locking & sticky this eliminated a query.
		 */
		$th_data = db_saq('SELECT orderexpiry, thread_opt, root_msg_id, tdescr FROM fud30_thread WHERE id='. $this->thread_id);
		$locked = (int) isset($_POST['thr_locked']);
		if (isset($_POST['thr_ordertype'], $_POST['thr_orderexpiry']) || (($th_data[1] ^ $locked) & 1)) {
			$thread_opt = (int) $th_data[1];
			$orderexpiry = isset($_POST['thr_orderexpiry']) ? (int) $_POST['thr_orderexpiry'] : 0;

			/* Confirm that user has ability to change lock status of the thread. */
			if ($perm & 4096) {
				if ($locked && !($thread_opt & $locked)) {
					$thread_opt |= 1;
				} else if (!$locked && $thread_opt & 1) {
					$thread_opt &= ~1;
				}
			}

			/* Confirm that user has ability to change sticky status of the thread. */
			if ($th_data[2] == $this->id && isset($_POST['thr_ordertype'], $_POST['thr_orderexpiry']) && $perm & 64) {
				if (!$_POST['thr_ordertype'] && $thread_opt > 1) {
					$orderexpiry = 0;
					$thread_opt &= ~6;
				} else if ($thread_opt < 2 && (int) $_POST['thr_ordertype']) {
					$thread_opt |= $_POST['thr_ordertype'];
				} else if (!($thread_opt & (int) $_POST['thr_ordertype'])) {
					$thread_opt = $_POST['thr_ordertype'] | ($thread_opt & 1);
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
				q('UPDATE fud30_thread SET '. ($th_data[2] == $this->id ? 'tdescr='. _esc($msg_tdescr) .',' : '') .' thread_opt='.$thread_opt.', orderexpiry='. $orderexpiry .' WHERE id='. $this->thread_id);
				/* Avoid rebuilding the forum view whenever possible, since it's a rather slow process.
				 * Only rebuild if expiry time has changed or message gained/lost sticky status.
				 */
				$diff = $thread_opt ^ $th_data[1];
				if (($diff > 1 && $diff & 14) || $orderexpiry != $th_data[0]) {
					rebuild_forum_view_ttl($frm_id);
				}
			} else if ($msg_tdescr != $th_data[3] && $th_data[2] == $this->id) {
				q('UPDATE fud30_thread SET tdescr='. _esc($msg_tdescr) .' WHERE id='. $this->thread_id);
			}
		} else if ($msg_tdescr != $th_data[3] && $th_data[2] == $this->id) {
			q('UPDATE fud30_thread SET tdescr='. _esc($msg_tdescr) .' WHERE id='. $this->thread_id);
		}

		if ($GLOBALS['FUD_OPT_1'] & 16777216) {	// FORUM_SEARCH enabled? If so, reindex message.
			q('DELETE FROM fud30_index WHERE msg_id='. $this->id);
			q('DELETE FROM fud30_title_index WHERE msg_id='. $this->id);
			index_text((!strncasecmp('Re: ', $this->subject, 4) ? '' : $this->subject), $this->body, $this->id);
		}
	}

	/**  Delete a message & cleanup. */
	static function delete($rebuild_view=1, $mid=0, $th_rm=0)
	{
		if (!$mid) {
			$mid = $this->id;
		}

		if (!($del = db_sab('SELECT m.file_id, m.file_id_preview, m.id, m.attach_cnt, m.poll_id, m.thread_id, m.reply_to, m.apr, m.poster_id, t.replies, t.root_msg_id AS root_msg_id, t.last_post_id AS thread_lip, t.forum_id, f.last_post_id AS forum_lip 
					FROM fud30_msg m 
					LEFT JOIN fud30_thread t ON m.thread_id=t.id 
					LEFT JOIN fud30_forum f ON t.forum_id=f.id WHERE m.id='. $mid))) {
			return;
		}

		if (!db_locked()) {
			db_lock('fud30_msg_store WRITE, fud30_forum f WRITE, fud30_thr_exchange WRITE, fud30_tv_'. $del->forum_id .' WRITE, fud30_tv_'. $del->forum_id .' tv WRITE, fud30_msg m WRITE, fud30_thread t WRITE, fud30_level WRITE, fud30_forum WRITE, fud30_forum_read WRITE, fud30_thread WRITE, fud30_msg WRITE, fud30_attach WRITE, fud30_poll WRITE, fud30_poll_opt WRITE, fud30_poll_opt_track WRITE, fud30_users WRITE, fud30_thread_notify WRITE, fud30_bookmarks WRITE, fud30_msg_report WRITE, fud30_thread_rate_track WRITE, fud30_index WRITE, fud30_title_index WRITE, fud30_search_cache WRITE');
			$ll = 1;
		}

		q('DELETE FROM fud30_msg WHERE id='. $mid);

		/* Remove attachments. */
		if ($del->attach_cnt) {
			$res = q('SELECT location FROM fud30_attach WHERE message_id='. $mid .' AND attach_opt=0');
			while ($loc = db_rowarr($res)) {
				@unlink($loc[0]);
			}
			unset($res);
			q('DELETE FROM fud30_attach WHERE message_id='. $mid .' AND attach_opt=0');
		}

		/* Remove message reports. */
		q('DELETE FROM fud30_msg_report WHERE msg_id='. $mid);

		/* Cleanup index entries. */
		if ($GLOBALS['FUD_OPT_1'] & 16777216) {	// FORUM_SEARCH enabled?
			q('DELETE FROM fud30_index WHERE msg_id='. $mid);
			q('DELETE FROM fud30_title_index WHERE msg_id='. $mid);
			q('DELETE FROM fud30_search_cache WHERE msg_id='. $mid);
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
				$rmsg = q('SELECT id FROM fud30_msg WHERE thread_id='. $del->thread_id .' AND id != '. $del->id);
				while ($dim = db_rowarr($rmsg)) {
					fud_msg_edit::delete(0, $dim[0], 1);
				}
				unset($rmsg);
			}

			q('DELETE FROM fud30_thread_notify WHERE thread_id='. $del->thread_id);
			q('DELETE FROM fud30_bookmarks WHERE thread_id='. $del->thread_id);
			q('DELETE FROM fud30_thread WHERE id='. $del->thread_id);
			q('DELETE FROM fud30_thread_rate_track WHERE thread_id='. $del->thread_id);
			q('DELETE FROM fud30_thr_exchange WHERE th='. $del->thread_id);

			if ($del->apr) {
				/* We need to determine the last post id for the forum, it can be null. */
				$lpi = (int) q_singleval(q_limit('SELECT t.last_post_id FROM fud30_thread t INNER JOIN fud30_msg m ON t.last_post_id=m.id AND m.apr=1 WHERE t.forum_id='.$del->forum_id.' AND t.moved_to=0 ORDER BY m.post_stamp DESC', 1));
				q('UPDATE fud30_forum SET last_post_id='. $lpi .', thread_count=thread_count-1, post_count=post_count-'. $del->replies .'-1 WHERE id='. $del->forum_id);
			}
		} else if (!$th_rm  && $del->apr) {
			q('UPDATE fud30_msg SET reply_to='. $del->reply_to .' WHERE thread_id='. $del->thread_id .' AND reply_to='. $mid);

			/* Check if the message is the last in thread. */
			if ($del->thread_lip == $del->id) {
				list($lpi, $lpd) = db_saq(q_limit('SELECT id, post_stamp FROM fud30_msg WHERE thread_id='. $del->thread_id .' AND apr=1 ORDER BY post_stamp DESC', 1));
				q('UPDATE fud30_thread SET last_post_id='. $lpi .', last_post_date='. $lpd .', replies=replies-1 WHERE id='. $del->thread_id);
			} else {
				q('UPDATE fud30_thread SET replies=replies-1 WHERE id='. $del->thread_id);
			}

			/* Check if the message is the last in the forum. */
			if ($del->forum_lip == $del->id) {
				$page = q_singleval('SELECT seq FROM fud30_tv_'. $del->forum_id .' WHERE thread_id='. $del->thread_id);
				$lp = db_saq(q_limit('SELECT t.last_post_id, t.last_post_date 
					FROM fud30_tv_'. $del->forum_id .' tv
					INNER JOIN fud30_thread t ON tv.thread_id=t.id 
					WHERE tv.seq IN('. $page .','. ($page - 1) .') AND t.moved_to=0 ORDER BY t.last_post_date DESC', 1));
				if (!isset($lpd) || $lp[1] > $lpd) {
					$lpi = $lp[0];
				}
				q('UPDATE fud30_forum SET post_count=post_count-1, last_post_id='. $lpi .' WHERE id='. $del->forum_id);
			} else {
				q('UPDATE fud30_forum SET post_count=post_count-1 WHERE id='. $del->forum_id);
			}
		}

		if ($del->apr) {
			if ($del->poster_id) {
				user_set_post_count($del->poster_id);
			}
			if ($rebuild_view) {
				if ($th_rm) {
					th_delete_rebuild($del->forum_id, $del->thread_id);
				} else if ($del->thread_lip == $del->id) {
					rebuild_forum_view_ttl($del->forum_id);
				}
			}
		}
		if (isset($ll)) {
			db_unlock();
		}

		if ($GLOBALS['FUD_OPT_3'] & 32768) {	// DB_MESSAGE_STORAGE
			q('DELETE FROM fud30_msg_store WHERE id IN('. $del->file_id .','. $del->file_id_preview .')');
		}

		if (!$del->apr || !$th_rm || ($del->root_msg_id != $del->id)) {
			return;
		}

		/* Needed for moved thread pointers. */
		$r = q('SELECT forum_id, id FROM fud30_thread WHERE root_msg_id='. $del->root_msg_id);
		while (($res = db_rowarr($r))) {
			q('DELETE FROM fud30_thread WHERE id='. $res[1]);
			q('UPDATE fud30_forum SET thread_count=thread_count-1 WHERE id='. $res[0]);
			th_delete_rebuild($res[0], $res[1]);
		}
		unset($r);
	}

	static function approve($id)
	{
		/* Fetch info about the message, poll (if one exists), thread & forum. */
		$mtf = db_sab('SELECT /* USE MASTER */
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
				LEFT JOIN fud30_mlist ml ON ml.forum_id=f.id AND '. q_bitand('ml.mlist_opt', 2) .' > 0
				LEFT JOIN fud30_nntp n ON n.forum_id=f.id AND '. q_bitand('n.nntp_opt', 2) .' > 0
				WHERE m.id='. $id .' AND m.apr=0');

		/* Nothing to do or bad message id. */
		if (!$mtf) {
			return;
		}

		if ($mtf->alias) {
			$mtf->alias = reverse_fmt($mtf->alias);
		} else {
			$mtf->alias = $GLOBALS['ANON_NICK'];
		}

		q('UPDATE fud30_msg SET apr=1 WHERE id='.$mtf->id);

		if ($mtf->poster_id) {
			user_set_post_count($mtf->poster_id);
		}

		if ($mtf->post_stamp > $mtf->frm_last_post_date) {
			$mtf->last_post_id = $mtf->id;
		}		

		if ($mtf->root_msg_id == $mtf->id) {	/* New thread. */
			th_new_rebuild($mtf->forum_id, $mtf->thread_id, $mtf->thread_opt & (2|4|8));
			$threads = 1;
		} else {				/* Reply to thread. */
			if ($mtf->post_stamp > $mtf->last_post_date) {
				th_inc_post_count($mtf->thread_id, 1, $mtf->id, $mtf->post_stamp);
			} else {
				th_inc_post_count($mtf->thread_id, 1);
			}
			th_reply_rebuild($mtf->forum_id, $mtf->thread_id, $mtf->thread_opt & (2|4|8));
			$threads = 0;
		}

		/* Update forum thread & post count as well as last_post_id field. */
		q('UPDATE fud30_forum SET post_count=post_count+1, thread_count=thread_count+'. $threads .', last_post_id='. $mtf->last_post_id .' WHERE id='. $mtf->forum_id);

		if ($mtf->poll_id) {
			poll_activate($mtf->poll_id, $mtf->forum_id);
		}

		$mtf->body = read_msg_body($mtf->foff, $mtf->length, $mtf->file_id);

		if ($GLOBALS['FUD_OPT_1'] & 16777216) {	// FORUM_SEARCH enabled?
			index_text((strncasecmp($mtf->subject, 'Re: ', 4) ? $mtf->subject : ''), $mtf->body, $mtf->id);
		}

		/* Handle notifications. */
		if (!($GLOBALS['FUD_OPT_3'] & 1048576)) {	// not DISABLE_NOTIFICATION_EMAIL
			if ($mtf->root_msg_id == $mtf->id || $GLOBALS['FUD_OPT_3'] & 16384) {	// FORUM_NOTIFY_ALL
				if (empty($mtf->frm_last_post_date)) {
					$mtf->frm_last_post_date = 0;
				}

				/* Send new thread notifications to forum subscribers. */
				$to = db_all('SELECT u.email
						FROM fud30_forum_notify fn
						INNER JOIN fud30_users u ON fn.user_id=u.id AND '. q_bitand('u.users_opt', 134217728) .' = 0
						INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id='. $mtf->forum_id .
						($GLOBALS['FUD_OPT_3'] & 64 ? ' LEFT JOIN fud30_forum_read r ON r.forum_id=fn.forum_id AND r.user_id=fn.user_id ' : '').
						' LEFT JOIN fud30_group_cache g2 ON g2.user_id=fn.user_id AND g2.resource_id='. $mtf->forum_id .
						' LEFT JOIN fud30_mod mm ON mm.forum_id='. $mtf->forum_id .' AND mm.user_id=u.id
					WHERE
						fn.forum_id='. $mtf->forum_id .' AND fn.user_id!='. (int)$mtf->poster_id .
						($GLOBALS['FUD_OPT_3'] & 64 ? ' AND (CASE WHEN (r.last_view IS NULL AND (u.last_read=0 OR u.last_read >= '. $mtf->frm_last_post_date .')) OR r.last_view > '. $mtf->frm_last_post_date .' THEN 1 ELSE 0 END)=1 ' : '').
						' AND ('. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 2) .' > 0 OR '. q_bitand('u.users_opt', 1048576) .' > 0 OR mm.id IS NOT NULL)'.
						' AND '. q_bitand('u.users_opt', 65536) .' = 0');
				if ($GLOBALS['FUD_OPT_3'] & 16384) {
					$notify_type = 'thr';
				} else {
					$notify_type = 'frm';
				}
			} else {
				$to = array();
			}
			if ($mtf->root_msg_id != $mtf->id) {
				/* Send new reply notifications to thread subscribers. */
				$tmp = db_all('SELECT u.email
						FROM fud30_thread_notify tn
						INNER JOIN fud30_users u ON tn.user_id=u.id AND '. q_bitand('u.users_opt', 134217728) .' = 0
						INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id='. $mtf->forum_id .
						($GLOBALS['FUD_OPT_3'] & 64 ? ' LEFT JOIN fud30_read r ON r.thread_id=tn.thread_id AND r.user_id=tn.user_id ' : '').
						' LEFT JOIN fud30_group_cache g2 ON g2.user_id=tn.user_id AND g2.resource_id='. $mtf->forum_id .
						' LEFT JOIN fud30_mod mm ON mm.forum_id='. $mtf->forum_id .' AND mm.user_id=u.id
					WHERE
						tn.thread_id='. $mtf->thread_id .' AND tn.user_id!='. (int)$mtf->poster_id .
						($GLOBALS['FUD_OPT_3'] & 64 ? ' AND (r.msg_id='. $mtf->last_post_id .' OR (r.msg_id IS NULL AND '. $mtf->post_stamp .' > u.last_read)) ' : '').
						' AND ('. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 2) .' > 0 OR '. q_bitand('u.users_opt', 1048576) .' > 0 OR mm.id IS NOT NULL)'.
						' AND '. q_bitand('u.users_opt', 65536) .' = 0');
				$to = !$to ? $tmp : array_unique(array_merge($to, $tmp));
				$notify_type = 'thr';
			}

			if ($mtf->forum_opt & 64) {	// always_notify_mods
				$tmp = db_all('SELECT u.email FROM fud30_mod mm INNER JOIN fud30_users u ON u.id=mm.user_id WHERE mm.forum_id='. $mtf->forum_id);
				$to = !$to ? $tmp : array_unique(array_merge($to, $tmp));
			}

			if ($to) {
				send_notifications($to, $mtf->id, $mtf->subject, $mtf->alias, $notify_type, ($notify_type == 'thr' ? $mtf->thread_id : $mtf->forum_id), $mtf->frm_name, $mtf->forum_id);
			}
		}

		// Handle Mailing List and/or Newsgroup syncronization.
		if (($mtf->nntp_id || $mtf->mlist_id) && !$mtf->mlist_msg_id) {
			fud_use('email_msg_format.inc', 1);

			$from = $mtf->poster_id ? reverse_fmt($mtf->real_name) .' <'. $mtf->email .'>' : $GLOBALS['ANON_NICK'] .' <'. $GLOBALS['NOTIFY_FROM'] .'>';
			$body = $mtf->body . (($mtf->msg_opt & 1 && $mtf->sig) ? "\n-- \n" . $mtf->sig : '');
			$body = plain_text($body, '<cite>', '</cite><blockquote>', '</blockquote>');
			$mtf->subject = reverse_fmt($mtf->subject);

			if ($mtf->reply_to) {
				// Get the parent message's Message-ID:
				if ( !($replyto_id = q_singleval('SELECT mlist_msg_id FROM fud30_msg WHERE id='. $mtf->reply_to))) {
					fud_logerror('WARNING: Send reply with no Message-ID. The import script is not running or may be lagging.', 'fud_errors');
				}
			} else {
				$replyto_id = 0;
			}

			if ($mtf->attach_cnt) {
				$r = uq('SELECT a.id, a.original_name, COALESCE(m.mime_hdr, \'application/octet-stream\')
						FROM fud30_attach a
						LEFT JOIN fud30_mime m ON a.mime_type=m.id
						WHERE a.message_id='. $mtf->id .' AND a.attach_opt=0');
				while ($ent = db_rowarr($r)) {
					$attach[$ent[1]] = file_get_contents($GLOBALS['FILE_STORE'] . $ent[0] .'.atch');
					$attach_mime[$ent[1]] = $ent[2];
				}
				unset($r);
			} else {
				$attach_mime = $attach = null;
			}

			if ($mtf->nntp_id) {	// Push out to usenet group.
				fud_use('nntp.inc', true);

				$nntp_adm = db_sab('SELECT * FROM fud30_nntp WHERE id='. $mtf->nntp_id);
				if (!empty($nntp_adm->custom_sig)) {	// Add signature marker.
					$nntp_adm->custom_sig = "\n-- \n". $nntp_adm->custom_sig;
				}

				$nntp = new fud_nntp;
				$nntp->server    = $nntp_adm->server;
				$nntp->newsgroup = $nntp_adm->newsgroup;
				$nntp->port      = $nntp_adm->port;
				$nntp->timeout   = $nntp_adm->timeout;
				$nntp->nntp_opt  = $nntp_adm->nntp_opt;
				$nntp->user      = $nntp_adm->login;
				$nntp->pass      = $nntp_adm->pass;

				define('sql_p', 'fud30_');

				$lock = $nntp->get_lock();
				$nntp->post_message($mtf->subject, $body . $nntp_adm->custom_sig, $from, $mtf->id, $replyto_id, $attach, $attach_mime);
				$nntp->close_connection();
				$nntp->release_lock($lock);
			} else {	// Push out to mailing list.
				fud_use('mlist_post.inc', true);

				$r = db_saq('SELECT name, additional_headers, custom_sig, fixed_from_address FROM fud30_mlist WHERE id='. $mtf->mlist_id);
				
				// Add forum's signature to the messages.
				if (!empty($r[2])) {
					$body .= "\n-- \n". $r[2];
				}

				if (!empty($r[3])) {	// Use the forum's fixed "From:" address.
					mail_list_post($r[0], $r[3], $mtf->subject, $body, $mtf->id, $replyto_id, $attach, $attach_mime, $r[1]);
				} else {				// Use poster's e-mail as the "From" address.
					mail_list_post($r[0], $from, $mtf->subject, $body, $mtf->id, $replyto_id, $attach, $attach_mime, $r[1]);
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

	db_lock('fud30_fl_'. $fid .' WRITE');

	$s = $fid * 10000;
	$e = $s + 100;
	
	while ($s < $e) {
		$fp = fopen($GLOBALS['MSG_STORE_DIR'] .'msg_'. $s, 'ab');
		if (!$fp) {
			exit('FATAL ERROR: could not open message store for forum id#'. $s ."<br />\n");
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
		@chmod('msg_'. $s, ($GLOBALS['FUD_OPT_2'] & 8388608 ? 0600 : 0644));
	}
	$offset = $off;

	return $s;
}

function trim_html($str, $maxlen)
{
	$n = strlen($str);
	$ln = 0;
	$tree = array();
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

		if ($str[$i+1] == '/') {
			$tagname = strtolower(substr($str, $i+2, $k-$i-2));
			if (@end($tagindex[$tagname])) {
				$k = key($tagindex[$tagname]);
				unset($tagindex[$tagname][$k], $tree[$k]);
			}
		} else {
			$tagname = strtolower(substr($str, $i+1, $k-$i-1));
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
			$data .= '</'. $v .'>';
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
				$iemail_poll .= '<tr class="'.alt_var('msg_poll_alt_clr','RowStyleB','RowStyleA').'">
	<td>'.$k.'.</td>
	<td>'.$v[0].'</td>
	<td>
		<img src="/theme/default/images/poll_pix.gif" alt="" height="10" width="'.$length.'" />
		'.$v[1].' / '.$length.'%
	</td>
</tr>';
			}
			$iemail_poll = '<table cellspacing="1" cellpadding="2" class="PollTable">
<tr>
	<th colspan="3">'.$obj->poll_name.'
		<img src="/blank.gif" alt="" height="1" width="10" class="nw" />
		<span class="small">[ '.$obj->total_votes.' '.convertPlural($obj->total_votes, array('vote','votes')).' ]</span>
	</th>
</tr>
'.$iemail_poll.'
</table>
<br /><br />';
		}
	}
	if ($obj->attach_cnt && $obj->attach_cache) {
		$atch = unserialize($obj->attach_cache);
		if (!empty($atch)) {
			foreach ($atch as $v) {
				$sz = $v[2] / 1024;
				$sz = $sz < 1000 ? number_format($sz, 2) .'KB' : number_format($sz/1024, 2) .'MB';
				$iemail_attach .= '<tr>
	<td class="vm"><a href="https://forum.wigedev.com/index.php?t=getfile&amp;id='.$v[0].'"><img alt="" src="/images/mime/'.$v[4].'" /></a></td>
	<td>
		<span class="GenText fb">Attachment:</span> <a href="https://forum.wigedev.com/index.php?t=getfile&amp;id='.$v[0].'">'.$v[1].'</a><br />
		<span class="SmallText">(Size: '.$sz.', Downloaded '.convertPlural($v[3], array(''.$v[3].' time',''.$v[3].' times')).')</span>
	</td>
</tr>';
			}
			$iemail_attach = '<br /><br />
<table border="0" cellspacing="0" cellpadding="2">
	'.$iemail_attach.'
</table>';
		}
	}

	if ($GLOBALS['FUD_OPT_2'] & 32768 && defined('_rsid')) {
		$pfx = str_repeat('/', substr_count(_rsid, '/'));
	}

	// Remove all JavaScript. Spam filters like SpamAssassin don't like them.
	return preg_replace('#<script[^>]*>.*?</script>#is', '', '<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name=viewport content="width=device-width, initial-scale=1">
<title>'.$GLOBALS['FORUM_TITLE'].$TITLE_EXTRA.'</title>
<script src="/js/lib.js"></script>
<script async src="/js/jquery.js"></script>
<script async src="/js/ui/jquery-ui.js"></script>
<link rel="stylesheet" href="/theme/default/forum.css" />
</head>
<body>
<div class="content">
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr class="RowStyleB">
	<td width="33%"><b>Subject:</b> '.$obj->subject.'</td>
	<td width="33%"><b>Author:</b> '.$obj->alias.'</td>
	<td width="33%"><b>Date:</b> '.strftime('%a, %d %B %Y %H:%M', $obj->post_stamp).'</td>
</tr>
<tr class="RowStyleA">
	<td colspan="3">
		'.$iemail_poll.'
		'.$body.'
		'.$iemail_attach.'
	</td>
</tr>
<tr class="RowStyleB">
	<td colspan="3">
		[ <a href="https://forum.wigedev.com/index.php?t=post&reply_to='.$obj->id.'">Reply</a> ][ <a href="https://forum.wigedev.com/index.php?t=post&reply_to='.$obj->id.'&quote=true">Quote</a> ][ <a href="https://forum.wigedev.com/index.php?t=rview&goto='.$obj->id.'#msg_'.$obj->id.'">View Topic/Message</a> ]'.$iemail_unsub.'
	</td>
</tr>
</table>
</div>
</body></html>');
}

function poll_cache_rebuild($poll_id)
{
	if (!$poll_id) {
		return;
	}

	$data = array();
	$c = uq('SELECT id, name, votes FROM fud30_poll_opt WHERE poll_id='. $poll_id);
	while ($r = db_rowarr($c)) {
		$data[$r[0]] = array($r[1], $r[2]);
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

	$goto_url['email'] = ''.$GLOBALS['WWW_ROOT'].'?t=rview&goto='. $msg_id .'#msg_'. $msg_id;
	$CHARSET = $GLOBALS['CHARSET'];
	if ($GLOBALS['FUD_OPT_2'] & 64) {	// NOTIFY_WITH_BODY
		$munge_newlines = 0;
		$obj = db_sab('SELECT p.total_votes, p.name AS poll_name, m.reply_to, m.subject, m.id, m.post_stamp, m.poster_id, m.foff, m.length, m.file_id, u.alias, m.attach_cnt, m.attach_cache, m.poll_cache FROM fud30_msg m LEFT JOIN fud30_users u ON m.poster_id=u.id LEFT JOIN fud30_poll p ON m.poll_id=p.id WHERE m.id='. $msg_id .' AND m.apr=1');

		if (!$obj->alias) { /* anon user */
			$obj->alias = htmlspecialchars($GLOBALS['ANON_NICK']);
		}

		$headers  = "MIME-Version: 1.0\r\n";
		if ($obj->reply_to) {
			$headers .= 'In-Reply-To: '. $obj->reply_to ."\r\n";
		}
		$headers .= 'List-Id: '. $frm_id .'.'. (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost') ."\r\n";
		$split = get_random_value(128);
		$headers .= "Content-Type: multipart/alternative;\n  boundary=\"------------". $split ."\"\r\n";
		$boundry = "\r\n--------------". $split ."\r\n";

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
		$iemail_unsub = html_entity_decode($id_type == 'thr' ? '[ <a href="https://forum.wigedev.com/index.php?t=rview&th='.$id.'">Unsubscribe from this topic</a> ]' : '[ <a href="https://forum.wigedev.com/index.php?t=rview&frm_id='.$id.'">Unsubscribe from this forum</a> ]');

		$body_email = $boundry .'Content-Type: text/plain; charset='. $CHARSET ."; format=flowed\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . html_entity_decode(strip_tags($plain_text)) . "\r\n\r\n" . html_entity_decode('To participate in the discussion, go here:') .' '. ''.$GLOBALS['WWW_ROOT'].'?t=rview&'. ($id_type == 'thr' ? 'th' : 'frm_id') .'='. $id ."\r\n".
				$boundry .'Content-Type: text/html; charset='. $CHARSET ."\r\nContent-Transfer-Encoding: 8bit\r\n\r\n". make_email_message($plain_text, $obj, $iemail_unsub) ."\r\n". substr($boundry, 0, -2) ."--\r\n";
	} else {
		$munge_newlines = 1;
		$headers = '';
	}

	$thr_subject = reverse_fmt($thr_subject);
	$poster_login = reverse_fmt($poster_login);

	if ($id_type == 'thr') {
		$subj = html_entity_decode('New reply to '.$thr_subject.' by '.$poster_login);

		if (!isset($body_email)) {
			$unsub_url['email'] = ''.$GLOBALS['WWW_ROOT'].'?t=rview&th='. $id .'&notify=1&opt=off';
			$body_email = html_entity_decode('To view unread replies go to '.$goto_url['email'].'\n\nIf you do not wish to receive further notifications about replies in this topic, please go here: '.$unsub_url['email']);
		}
	} else if ($id_type == 'frm') {
		$frm_name = reverse_fmt($frm_name);

		$subj = html_entity_decode('New topic in forum '.$frm_name.', called '.$thr_subject.', by '.$poster_login);

		if (!isset($body_email)) {
			$unsub_url['email'] = ''.$GLOBALS['WWW_ROOT'].'?t=rview&unsub=1&frm_id='. $id;
			$body_email = html_entity_decode('To view the topic go to:\n'.$goto_url['email'].'\n\nTo stop receiving notifications about new topics in this forum, please go here: '.$unsub_url['email']);
		}
	}

	send_email($GLOBALS['NOTIFY_FROM'], $to, $subj, $body_email, $headers, $munge_newlines);
}function check_return($returnto)
{
	if ($GLOBALS['FUD_OPT_2'] & 32768 && !empty($_SERVER['PATH_INFO'])) {
		if (!$returnto || !strncmp($returnto, '/er/', 4)) {
			header('Location: /index.php/i/'. _rsidl);
		} else if ($returnto[0] == '/') { /* Unusual situation, path_info & normal themes are active. */
			header('Location: /index.php'. $returnto);
		} else {
			header('Location: /index.php?'. $returnto);
		}
	} else if (!$returnto || !strncmp($returnto, 't=error', 7)) {
		header('Location: /index.php?t=index&'. _rsidl);
	} else if (strpos($returnto, 'S=') === false && $GLOBALS['FUD_OPT_1'] & 128) {
		header('Location: /index.php?'. $returnto .'&S='. s);
	} else {
		header('Location: /index.php?'. $returnto);
	}
	exit;
}include $GLOBALS['FORUM_SETTINGS_PATH'] .'ip_filter_cache';
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'login_filter_cache';
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'email_filter_cache';

function is_ip_blocked($ip)
{
	if (empty($GLOBALS['__FUD_IP_FILTER__'])) {
		return;
	}
	$block =& $GLOBALS['__FUD_IP_FILTER__'];
	list($a,$b,$c,$d) = explode('.', $ip);

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

function is_allowed_user(&$usr, $simple=0)
{
	/* Check if the ban expired. */
	if (($banned = $usr->users_opt & 65536) && $usr->ban_expiry && $usr->ban_expiry < __request_timestamp__) {
		q('UPDATE fud30_users SET users_opt = '. q_bitand('users_opt', ~65536) .' WHERE id='. $usr->id);
		$usr->users_opt ^= 65536;
		$banned = 0;
	} 

	if ($banned || is_email_blocked($usr->email) || is_login_blocked($usr->login) || is_ip_blocked(get_ip())) {
		$ban_expiry = (int) $usr->ban_expiry;
		$ban_reason = $usr->ban_reason;
		if (!$simple) { // On login page we already have anon session.
			ses_delete($usr->sid);
			$usr = ses_anon_make();
		}
		setcookie($GLOBALS['COOKIE_NAME'].'1', 'd34db33fd34db33fd34db33fd34db33f', ($ban_expiry ? $ban_expiry : (__request_timestamp__ + 63072000)), $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
		if ($banned) {
			error_dialog('ERROR: You have been banned.', 'Your account was '.($ban_expiry ? 'temporarily banned until '.strftime('%a, %d %B %Y %H:%M', $ban_expiry) : 'permanently banned' )  .' from accessing the site, due to a violation of the forum&#39;s rules.
<br />
<br />
<span class="GenTextRed">'.$ban_reason.'</span>');
		} else {
			error_dialog('ERROR: Your account has been filtered out.', 'Your account has been blocked from accessing the forum due to one of the installed user filters.');
		}
	}

	if ($simple) {
		return;
	}

	if ($GLOBALS['FUD_OPT_1'] & 1048576 && $usr->users_opt & 262144) {
		error_dialog('ERROR: Your account is not yet confirmed', 'We have not received a confirmation from your parent and/or legal guardian, which would allow you to post messages. If you lost your COPPA form, <a href="/index.php?t=coppa_fax&amp;'._rsid.'">view it again</a>.');
	}

	if ($GLOBALS['FUD_OPT_2'] & 1 && !($usr->users_opt & 131072)) {
		std_error('emailconf');
	}

	if ($GLOBALS['FUD_OPT_2'] & 1024 && $usr->users_opt & 2097152) {
		error_dialog('Unverified Account', 'The administrator had chosen to review all accounts manually prior to activation. Until your account has been validated by the administrator you will not be able to utilize the full capabilities of your account.');
	}
}/** Log action to the forum's Action Log Viewer ACP. */
function logaction($user_id, $res, $res_id=0, $action=null)
{
	q('INSERT INTO fud30_action_log (logtime, logaction, user_id, a_res, a_res_id)
		VALUES('. __request_timestamp__ .', '. ssn($action) .', '. $user_id .', '. ssn($res) .', '. (int)$res_id .')');
}function draw_post_smiley_cntrl()
{
	global $PS_SRC, $PS_DST; /* Import from global scope, if possible. */

	include_once $GLOBALS['FORUM_SETTINGS_PATH'] .'ps_cache';

	/* Nothing to do. */
	if ($GLOBALS['MAX_SMILIES_SHOWN'] < 1 || !$PS_SRC) {
		return;
	}
	$limit = count($PS_SRC);
	if ($limit > $GLOBALS['MAX_SMILIES_SHOWN']) {
		$limit = $GLOBALS['MAX_SMILIES_SHOWN'];
	}

	$smilies = '';
	$i = 0;
	while ($i < $limit) {
		$smilies .= '<a href="javascript: insertTag(\'txtb\', \'\', \' '.$PS_DST[$i].' \');">'.$PS_SRC[$i++].'</a>&nbsp;';
	}
	return '<tr class="RowStyleA">
	<td class="nw vt GenText">
		Smiley Shortcuts:<br />
		 <span class="SmallText">[ <a href="javascript://" onclick="window_open(\'/index.php?t=smladd\', \'sml_list\', 220, 200);">list all smilies</a> ]</span>
	</td>
	<td class="vm">
		<span class="FormattingToolsBG">'.$smilies.'</span>
	</td>
</tr>';
}

function draw_post_icons($msg_icon)
{
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'icon_cache';

 	/* Nothing to do. */
	if (!$ICON_L) {
		return;
	}

	$tmp = $data = '';
	$rl = (int) $GLOBALS['POST_ICONS_PER_ROW'];

	foreach ($ICON_L as $k => $f) {
		if ($k && !($k % $rl)) {
			$data .= '<tr>'.$tmp.'</tr>';
			$tmp = '';
		}
		$tmp .= '<td class="ac nw"><input type="radio" name="msg_icon" value="'.$f.'"'.($f == $msg_icon ? ' checked="checked"' : '' ) .' /><img src="/images/message_icons/'.$f.'" alt="" /></td>';
	}
	if ($tmp) {
		$data .= '<tr>'.$tmp.'</tr>';
	}

	return '<tr class="RowStyleA">
	<td class="vt GenText">Message Icon:</td>
	<td>
		<table border="0" cellspacing="0" cellpadding="2">
		<tr>
			<td class="GenText" colspan="'.$GLOBALS['POST_ICONS_PER_ROW'].'">
				<input type="radio" name="msg_icon" value=""'.(!$msg_icon ? ' checked="checked"' : '' ) .' />No Icon
			</td>
		</tr>
		'.$data.'
		</table>
	</td>
</tr>';
}

function draw_post_attachments($al, $max_as, $max_a, $attach_control_error, $private=0, $msg_id)
{
	$attached_files = '';
	$i = 0;

	if (!empty($al)) {
		$enc = base64_encode(serialize($al));

		ses_putvar((int)$GLOBALS['usr']->sid, md5($enc));

		$c = uq('SELECT a.id,a.fsize,a.original_name,m.mime_hdr
		FROM fud30_attach a
		LEFT JOIN fud30_mime m ON a.mime_type=m.id
		WHERE a.id IN('. implode(',', $al) .') AND message_id IN(0, '. $msg_id .') AND attach_opt='. ($private ? 1 : 0));
		while ($r = db_rowarr($c)) {
			$sz = ( $r[1] < 100000 ) ? number_format($r[1]/1024,2) .'KB' : number_format($r[1]/1048576,2) .'MB';
			$insert_uploaded_image = strncasecmp('image/', $r[3], 6) ? '' : '&nbsp;|&nbsp;<a href="javascript: insertTag(\'txtb\', \'[img]/index.php?t=getfile&id='.$r[0].'&private='.$private.'\', \'[/img]\');">Insert image into message body</a>';
			$attached_files .= '<tr>
	<td class="RowStyleB">'.$r[2].'</td>
	<td class="RowStyleB">'.$sz.'</td>
	<td class="RowStyleB"><a href="javascript: document.forms[\'post_form\'].file_del_opt.value=\''.$r[0].'\'; document.forms[\'post_form\'].submit();">Delete</a>'.$insert_uploaded_image.'</td>
</tr>';
			$i++;
		}
		unset($c);
	}

	if (!$private && $GLOBALS['MOD'] && $GLOBALS['frm']->forum_opt & 32) {
		$allowed_extensions = '(unrestricted)';
	} else {
		include $GLOBALS['FORUM_SETTINGS_PATH'] .'file_filter_regexp';
		if (empty($GLOBALS['__FUD_EXT_FILER__'])) {
			$allowed_extensions = '(unrestricted)';
		} else {
			$allowed_extensions = implode(' ', $GLOBALS['__FUD_EXT_FILER__']);
		}
	}
	$max_as_k = round($max_as / 1024);	// We display max attch size in KB.
	return '<tr class="RowStyleB"><td class="GenText vt nw">File Attachments:</td><td>
'.($i ? '
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th>Name</th>
	<th>Size</th>
	<th>Action</th>
</tr>
'.$attached_files.'
</table>
<input type="hidden" name="file_del_opt" value="" />
' : '' )  .'
'.(isset($enc) ? '<input type="hidden" name="file_array" value="'.$enc.'" />' : '' ) .'
'.$attach_control_error.'
<span class="SmallText">
	<b>Allowed File Extensions:</b>     '.$allowed_extensions.'<br />
	<b>Maximum File Size:</b>     '.$max_as_k.'KB<br />
	<b>Maximum Files Per Message:</b> '.$max_a.($i ? '; currently attached: '.$i.' '.convertPlural($i, array('file','files')) : '' )  .'
</span>
'.((($i + 1) <= $max_a) ? '<input type="hidden" id="MAX_FILE_SIZE" name="MAX_FILE_SIZE" value="'.$max_as.'" />
<input type="file" name="attach_control[]" multiple="multiple" />
<input type="submit" class="button" name="attach_control_add" value="Upload File" />
<input type="hidden" name="tmp_f_val" value="1" />' : '' ) .'
</td></tr>';
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
}function &get_all_read_perms($uid, $mod)
{
	$limit = array(0);

	$r = uq('SELECT resource_id, group_cache_opt FROM fud30_group_cache WHERE user_id='. _uid);
	while ($ent = db_rowarr($r)) {
		$limit[$ent[0]] = $ent[1] & 2;
	}
	unset($r);

	if (_uid) {
		if ($mod) {
			$r = uq('SELECT forum_id FROM fud30_mod WHERE user_id='. _uid);
			while ($ent = db_rowarr($r)) {
				$limit[$ent[0]] = 2;
			}
			unset($r);
		}

		$r = uq('SELECT resource_id FROM fud30_group_cache WHERE resource_id NOT IN ('. implode(',', array_keys($limit)) .') AND user_id=2147483647 AND '. q_bitand('group_cache_opt', 2) .' > 0');
		while ($ent = db_rowarr($r)) {
			if (!isset($limit[$ent[0]])) {
				$limit[$ent[0]] = 2;
			}
		}
		unset($r);
	}

	return $limit;
}

function perms_from_obj($obj, $adm)
{
	$perms = 1|2|4|8|16|32|64|128|256|512|1024|2048|4096|8192|16384|32768|262144;

	if ($adm || $obj->md) {
		return $perms;
	}

	return ($perms & $obj->group_cache_opt);
}

function make_perms_query(&$fields, &$join, $fid='')
{
	if (!$fid) {
		$fid = 'f.id';
	}

	if (_uid) {
		$join = ' INNER JOIN fud30_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id='. $fid .' LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id='. $fid .' ';
		$fields = ' COALESCE(g2.group_cache_opt, g1.group_cache_opt) AS group_cache_opt ';
	} else {
		$join = ' INNER JOIN fud30_group_cache g1 ON g1.user_id=0 AND g1.resource_id='. $fid .' ';
		$fields = ' g1.group_cache_opt ';
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
		array('..#####..','..#####..','.#.......','.#######.','..#####..','.#######.','..#####..','..#####..','....###....','.########..','..######..','.########.','.########.','..######...','.##.....##.','.####.','.......##.','.##....##.','.##.......','.##.....##.','.##....##.','.########..','..#######..','.########..','..######..','.########.','.##.....##.','.##.....##.','.##......##.','.##.....##.','.##....##.','.########.'),
		array('.#.....#.','.#.....#.','.#....#..','.#.......','.#.....#.','.#....#..','.#.....#.','.#.....#.','...##.##...','.##.....##.','.##....##.','.##.......','.##.......','.##....##..','.##.....##.','..##..','.......##.','.##...##..','.##.......','.###...###.','.###...##.','.##.....##.','.##.....##.','.##.....##.','.##....##.','....##....','.##.....##.','.##.....##.','.##..##..##.','..##...##..','..##..##..','......##..'),
		array('.......#.','.......#.','.#....#..','.#.......','.#.......','.....#...','.#.....#.','.#.....#.','..##...##..','.##.....##.','.##.......','.##.......','.##.......','.##........','.##.....##.','..##..','.......##.','.##..##...','.##.......','.####.####.','.####..##.','.##.....##.','.##.....##.','.##.....##.','.##.......','....##....','.##.....##.','.##.....##.','.##..##..##.','...##.##...','...####...','.....##...'),
		array('..#####..','....###..','.#....#..','.######..','.######..','....#....','..#####..','..######.','.##.....##.','.########..','.##.......','.######...','.######...','.##...####.','.#########.','..##..','.......##.','.#####....','.##.......','.##.###.##.','.##.##.##.','.########..','.##.....##.','.########..','..######..','....##....','.##.....##.','.##.....##.','.##..##..##.','....###....','....##....','....##....'),
		array('.#.......','.......#.','.#######.','.......#.','.#.....#.','...#.....','.#.....#.','.......#.','.#########.','.##.....##.','.##.......','.##.......','.##.......','.##....##..','.##.....##.','..##..','.##....##.','.##..##...','.##.......','.##.....##.','.##..####.','.##........','.##..##.##.','.##...##...','.......##.','....##....','.##.....##.','..##...##..','.##..##..##.','...##.##...','....##....','...##.....'),
		array('.#.......','.#.....#.','......#..','.#.....#.','.#.....#.','...#.....','.#.....#.','.#.....#.','.##.....##.','.##.....##.','.##....##.','.##.......','.##.......','.##....##..','.##.....##.','..##..','.##....##.','.##...##..','.##.......','.##.....##.','.##...###.','.##........','.##....##..','.##....##..','.##....##.','....##....','.##.....##.','...##.##...','.##..##..##.','..##...##..','....##....','..##......'),
		array('.#######.','..#####..','......#..','..#####..','..#####..','...#.....','..#####..','..#####..','.##.....##.','.########..','..######..','.########.','.##.......','..######...','.##.....##.','.####.','..######..','.##....##.','.########.','.##.....##.','.##....##.','.##........','..#####.##.','.##.....##.','..######..','....##....','..#######..','....###....','..###..###..','.##.....##.','....##....','.########.'),
		array('2','3','4','5','6','7','8','9','A','B','C','E','F','G','H','I','J','K','L','M','N','P','Q','R','S','T','U','V','W','X','Y','Z')
	);

	$rv      = array_rand($t[0], 4);
	$captcha = $t[7][$rv[0]] . $t[7][$rv[1]] . $t[7][$rv[2]] . $t[7][$rv[3]];
	$rt      = md5($captcha);

	$text = '<input type="text" name="turing_test" id="turing_test" size="25" required="required" placeholder="There is no zero or one in the image." />';
	$text .= '<input type="hidden" name="turing_res" value="'. $rt .'" />';

	if (($GLOBALS['FUD_OPT_3'] & 33554432) && extension_loaded('gd') && function_exists('imagecreate') ) {
		// Graphical captcha.
		ses_putvar((int)$GLOBALS['usr']->sid, $captcha);
		return $text .'<br />
<img src="index.php?t=captchaimg" alt="Captcha Verification: you will need to recognize the text in this image." />';
	} else {
		// Text based captcha.
		$bg_fill_chars = array(' ', '.', ',', '`', '_', '\'');
		$bg_fill       = $bg_fill_chars[array_rand($bg_fill_chars)];
		$fg_fill_chars = array('&#35;', '&#64;', '&#36;', '&#42;', '&#88;');
		$fg_fill       = $fg_fill_chars[array_rand($fg_fill_chars)];

		$text .= '<pre>';
		// Generate turing text.
		for ($i = 0; $i < 7; $i++) {
			foreach ($rv as $v) {
				$text .= str_replace('#', $fg_fill, str_replace('.', $bg_fill, $t[$i][$v]));
			}
			$text .= '<br />';
		}
	 	return $text .'</pre>';
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
	$res  = $_POST['turing_res'];
	if (empty($test) || empty($res)) {
		return false;
	}

	if (md5(strtoupper(trim($test))) != $res) {
		return false;
	} else {
		return true;
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
}function validate_email($email)
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
		$text = '=?utf-8?B?'. base64_encode($text) .'?=';
	}

	return $text;
}

function send_email($from, $to, $subj, $body, $header='', $munge_newlines=1)
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
		$extra_header = "\nMIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit". $header;
	}
	$header = 'From: '. $from ."\nErrors-To: ". $from ."\nReturn-Path: ". $from ."\nX-Mailer: FUDforum v". $GLOBALS['FORUM_VERSION']. $extra_header. $header;

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
		logaction(_uid, 'SEND EMAIL', 0, 'To=['. implode(',', (array)$to) .']<br />Subject=['. $subj .']<br />Headers=['. str_replace("\n", '<br />', htmlentities($header)) .']<br />Message=['. $body .']');
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
			fud_logerror('Your system didn\'t accept E-mail ['. $subj .'] to ['. $email .'] for delivery.', 'fud_errors', $header ."\n\n". $body);
			return -1;
		}
	}
	
	return 1;
}class fud_smtp
{
	var $fs, $last_ret, $msg, $subject, $to, $from, $headers;

	function get_return_code($cmp_code='250')
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
		fwrite($this->fs, $string ."\r\n");
	}

	function open_smtp_connex()
	{
		if( !($this->fs = @fsockopen($GLOBALS['FUD_SMTP_SERVER'], $GLOBALS['FUD_SMTP_PORT'], $errno, $errstr, $GLOBALS['FUD_SMTP_TIMEOUT'])) ) {
			fud_logerror('ERROR: SMTP server at '. $GLOBALS['FUD_SMTP_SERVER'] ." is not available<br />\n". ($errno ? "Additional Problem Info: $errno -> $errstr <br />\n" : ''), 'fud_errors');
			return;
		}
		if (!$this->get_return_code(220)) {	// 220 == Ready to speak SMTP.
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
			while($str = @fgets($this->fs, 515)) {
				if (substr($str, 0, 12) == '250-STARTTLS') $tls = true;
				if (substr($str, 3,  1) == ' ') break;	// Done reading if 4th char is a space.

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
				$this->wts(($es ? 'EHLO ' : 'HELO ').$smtp_srv);
				if (!$this->get_return_code()) {
					return;
				}
				/* we need to scan all other lines */
				while($str = @fgets($this->fs, 515)) {
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
		$this->wts('MAIL FROM: <'. $GLOBALS['NOTIFY_FROM'] .'>');
		return $this->get_return_code();
	}

	function send_to_hdr()
	{
		$this->to = (array) $this->to;

		foreach ($this->to as $to_addr) {
			$this->wts('RCPT TO: <'. $to_addr .'>');
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

		if( empty($this->from) ) $this->from = $GLOBALS['NOTIFY_FROM'];

		$this->wts('Subject: '. $this->subject);
		$this->wts('Date: '. date('r'));
		$this->wts('To: '. (count($this->to) == 1 ? $this->to[0] : $GLOBALS['NOTIFY_FROM']));
		$this->wts($this->headers ."\r\n");
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
				fud_logerror('Open SMTP connection - invalid return code: '. $this->last_ret, 'fud_errors');
			}
			return false;
		}
		if (!$this->send_from_hdr()) {
			fud_logerror('Send "From:" header - invalid SMTP return code: '. $this->last_ret, 'fud_errors');
			$this->close_connex();
			return false;
		}
		if (!$this->send_to_hdr()) {
			fud_logerror('Send "To:" header - invalid SMTP return code: '. $this->last_ret, 'fud_errors');
			$this->close_connex();
			return false;
		}
		if (!$this->send_data()) {
			fud_logerror('Send data - invalid SMTP return code: '. $this->last_ret, 'fud_errors');
			$this->close_connex();
			return false;
		}

		$this->close_connex();
		return true;
	}
}function safe_attachment_copy($source, $id, $ext)
{
	$loc = $GLOBALS['FILE_STORE'] . $id .'.atch';
	if (!$ext && !move_uploaded_file($source, $loc)) {
		error_dialog('Unable to move uploaded file', 'From: '. $source .' To: '. $loc, 'LOG&RETURN');
	} else if ($ext && !copy($source, $loc)) {
		error_dialog('Unable to handle file attachment', 'From: '. $source .' To: '. $loc, 'LOG&RETURN');
	}
	@unlink($source);

	@chmod($loc, ($GLOBALS['FUD_OPT_2'] & 8388608 ? 0600 : 0644));

	return $loc;
}

function attach_add($at, $owner, $attach_opt=0, $ext=0)
{
	$id = db_qid('INSERT INTO fud30_attach (location, message_id, original_name, owner, attach_opt, mime_type,fsize) '.
		q_limit('SELECT null AS location, 0 AS message_id, '. _esc($at['name']) .' AS original_name, '. $owner .' AS owner, '. $attach_opt .' AS attach_opt, id AS mime_type, '. $at['size'] .' AS fsize 
			FROM fud30_mime WHERE fl_ext IN(\'*\', '. _esc(strtolower(substr(strrchr($at['name'], '.'), 1))) .')
			ORDER BY fl_ext DESC'
		, 1)
	);

	safe_attachment_copy($at['tmp_name'], $id, $ext);

	return $id;
}

function attach_finalize($attach_list, $mid, $attach_opt=0)
{
	$id_list = '';
	$attach_count = 0;

	$tbl = !$attach_opt ? 'msg' : 'pmsg';

	foreach ($attach_list as $key => $val) {
		if (!$val) {
			@unlink($GLOBALS['FILE_STORE'] . (int)$key .'.atch');
		} else {
			$attach_count++;
			$id_list .= (int)$key .',';
		}
	}

	if ($id_list) {
		$id_list = substr($id_list, 0, -1);
		$cc = q_concat(_esc($GLOBALS['FILE_STORE']), 'id', _esc('.atch'));
		q('UPDATE fud30_attach SET location='. $cc .', message_id='. $mid .' WHERE id IN('. $id_list .') AND attach_opt='. $attach_opt);
		$id_list = ' AND id NOT IN('. $id_list .')';
	} else {
		$id_list = '';
	}

	/* Delete any unneeded (removed, temporary) attachments. */
	q('DELETE FROM fud30_attach WHERE message_id='. $mid .' '. $id_list);

	if (!$attach_opt && ($atl = attach_rebuild_cache($mid))) {
		q('UPDATE fud30_msg SET attach_cnt='. $attach_count .', attach_cache='. _esc(serialize($atl)) .' WHERE id='. $mid);
	}

	if (!empty($GLOBALS['usr']->sid)) {
		ses_putvar((int)$GLOBALS['usr']->sid, null);
	}
}

function attach_rebuild_cache($id)
{
	$ret = array();
	$c = uq('SELECT a.id, a.original_name, a.fsize, a.dlcount, COALESCE(m.icon, \'unknown.gif\') FROM fud30_attach a LEFT JOIN fud30_mime m ON a.mime_type=m.id WHERE message_id='. $id .' AND attach_opt=0');
	while ($r = db_rowarr($c)) {
		$ret[] = $r;
	}
	unset($c);
	return $ret;
}

/* Increment download counter for an attachment. */
function attach_inc_dl_count($id, $mid)
{
	q('UPDATE fud30_attach SET dlcount=dlcount+1 WHERE id='. $id);
	if (($a = attach_rebuild_cache($mid))) {
		q('UPDATE fud30_msg SET attach_cache='. _esc(serialize($a)) .' WHERE id='. $mid);
	}
}function get_host($ip)
{
	if (!$ip || $ip == '0.0.0.0') {
		return;
	}

	$name = gethostbyaddr($ip);

	if ($name == $ip) {
		$name = substr($name, 0, strrpos($name, '.')) .'*';
	} else if (substr_count($name, '.') > 1) {
		$name = '*'. substr($name, strpos($name, '.')+1);
	}

	return $name;
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
}function pager_replace(&$str, $s, $c)
{
	$str = str_replace(array('%s', '%c'), array($s, $c), $str);
}

function tmpl_create_pager($start, $count, $total, $arg, $suf='', $append=1, $js_pager=0, $no_append=0)
{
	if (!$count) {
		$count =& $GLOBALS['POSTS_PER_PAGE'];
	}
	if ($total <= $count) {
		return;
	}

	$upfx = '';
	if ($GLOBALS['FUD_OPT_2'] & 32768 && (!empty($_SERVER['PATH_INFO']) || strpos($arg, '?') === false)) {
		if (!$suf) {
			$suf = '/';
		} else if (strpos($suf, '//') !== false) {
			$suf = preg_replace('!/+!', '/', $suf);
		}
	} else if (!$no_append) {
		$upfx = '&amp;start=';
	}

	$cur_pg = ceil($start / $count);
	$ttl_pg = ceil($total / $count);

	$page_pager_data = '';

	if (($page_start = $start - $count) > -1) {
		if ($append) {
			$page_first_url = $arg . $upfx . $suf;
			$page_prev_url = $arg . $upfx . $page_start . $suf;
		} else {
			$page_first_url = $page_prev_url = $arg;
			pager_replace($page_first_url, 0, $count);
			pager_replace($page_prev_url, $page_start, $count);
		}

		$page_pager_data .= !$js_pager ? '&nbsp;<a href="'.$page_first_url.'" class="PagerLink">&laquo;</a>&nbsp;&nbsp;<a href="'.$page_prev_url.'" accesskey="p" class="PagerLink">&lsaquo;</a>&nbsp;&nbsp;' : '&nbsp;<a href="javascript://" onclick="'.$page_first_url.'" class="PagerLink">&laquo;</a>&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_prev_url.'" class="PagerLink">&lsaquo;</a>&nbsp;&nbsp;';
	}

	$mid = ceil($GLOBALS['GENERAL_PAGER_COUNT'] / 2);

	if ($ttl_pg > $GLOBALS['GENERAL_PAGER_COUNT']) {
		if (($mid + $cur_pg) >= $ttl_pg) {
			$end = $ttl_pg;
			$mid += $mid + $cur_pg - $ttl_pg;
			$st = $cur_pg - $mid;
		} else if (($cur_pg - $mid) <= 0) {
			$st = 0;
			$mid += $mid - $cur_pg;
			$end = $mid + $cur_pg;
		} else {
			$st = $cur_pg - $mid;
			$end = $mid + $cur_pg;
		}

		if ($st < 0) {
			$start = 0;
		}
		if ($end > $ttl_pg) {
			$end = $ttl_pg;
		}
		if ($end - $start > $GLOBALS['GENERAL_PAGER_COUNT']) {
			$end = $start + $GLOBALS['GENERAL_PAGER_COUNT'];
		}
	} else {
		$end = $ttl_pg;
		$st = 0;
	}

	while ($st < $end) {
		if ($st != $cur_pg) {
			$page_start = $st * $count;
			if ($append) {
				$page_page_url = $arg . $upfx . $page_start . $suf;
			} else {
				$page_page_url = $arg;
				pager_replace($page_page_url, $page_start, $count);
			}
			$st++;
			$page_pager_data .= !$js_pager ? '<a href="'.$page_page_url.'" class="PagerLink">'.$st.'</a>&nbsp;&nbsp;' : '<a href="javascript://" onclick="'.$page_page_url.'" class="PagerLink">'.$st.'</a>&nbsp;&nbsp;';
		} else {
			$st++;
			$page_pager_data .= !$js_pager ? $st.'&nbsp;&nbsp;' : $st.'&nbsp;&nbsp;';
		}
	}

	$page_pager_data = substr($page_pager_data, 0 , strlen((!$js_pager ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;')) * -1);

	if (($page_start = $start + $count) < $total) {
		$page_start_2 = ($st - 1) * $count;
		if ($append) {
			$page_next_url = $arg . $upfx . $page_start . $suf;
			// $page_last_url = $arg . $upfx . $page_start_2 . $suf;
			$page_last_url = $arg . $upfx . floor($total-1/$count)*$count . $suf;
		} else {
			$page_next_url = $page_last_url = $arg;
			pager_replace($page_next_url, $upfx . $page_start, $count);
			pager_replace($page_last_url, $upfx . $page_start_2, $count);
		}
		$page_pager_data .= !$js_pager ? '&nbsp;&nbsp;<a href="'.$page_next_url.'" accesskey="n" class="PagerLink">&rsaquo;</a>&nbsp;&nbsp;<a href="'.$page_last_url.'" class="PagerLink">&raquo;</a>' : '&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_next_url.'" class="PagerLink">&rsaquo;</a>&nbsp;&nbsp;<a href="javascript://" onclick="'.$page_last_url.'" class="PagerLink">&raquo;</a>';
	}

	return !$js_pager ? '<span class="SmallText fb">Pages ('.$ttl_pg.'): ['.$page_pager_data.']</span>' : '<span class="SmallText fb">Pages ('.$ttl_pg.'): ['.$page_pager_data.']</span>';
}/* Handle poll votes if any are present. */
function register_vote(&$options, $poll_id, $opt_id, $mid)
{
	/* Invalid option or previously voted. */
	if (!isset($options[$opt_id]) || q_singleval('SELECT id FROM fud30_poll_opt_track WHERE poll_id='. $poll_id .' AND user_id='. _uid)) {
		return;
	}

	if (db_li('INSERT INTO fud30_poll_opt_track(poll_id, user_id, ip_addr, poll_opt) VALUES('. $poll_id .', '. _uid .', '. (!_uid ? _esc(get_ip()) : 'null') .', '. $opt_id .')', $a)) {
		q('UPDATE fud30_poll_opt SET votes=votes+1 WHERE id='. $opt_id);
		q('UPDATE fud30_poll SET total_votes=total_votes+1 WHERE id='. $poll_id);
		$options[$opt_id][1] += 1;
		q('UPDATE fud30_msg SET poll_cache='. _esc(serialize($options)) .' WHERE id='. $mid);
	}

	return 1;
}

$GLOBALS['__FMDSP__'] = array();

/* Needed for message threshold & reveling messages. */
if (isset($_GET['rev'])) {
	$_GET['rev'] = htmlspecialchars((string)$_GET['rev']);
	foreach (explode(':', $_GET['rev']) as $v) {
		$GLOBALS['__FMDSP__'][(int)$v] = 1;
	}
	if ($GLOBALS['FUD_OPT_2'] & 32768) {
		define('reveal_lnk', '/'. $_GET['rev']);
	} else {
		define('reveal_lnk', '&amp;rev='. $_GET['rev']);
	}
} else {
	define('reveal_lnk', '');
}

/* Initialize buddy & ignore list for registered users. */
if (_uid) {
	if ($usr->buddy_list) {
		$usr->buddy_list = unserialize($usr->buddy_list);
	}
	if ($usr->ignore_list) {
		$usr->ignore_list = unserialize($usr->ignore_list);
		if (isset($usr->ignore_list[1])) {
			$usr->ignore_list[0] =& $usr->ignore_list[1];
		}
	}

	/* Handle temporarily un-hidden users. */
	if (isset($_GET['reveal'])) {
		$_GET['reveal'] = htmlspecialchars((string)$_GET['reveal']);
		foreach(explode(':', $_GET['reveal']) as $v) {
			$v = (int) $v;
			if (isset($usr->ignore_list[$v])) {
				$usr->ignore_list[$v] = 0;
			}
		}
		if ($GLOBALS['FUD_OPT_2'] & 32768) {
			define('unignore_tmp', '/'. $_GET['reveal']);
		} else {
			define('unignore_tmp', '&amp;reveal='. $_GET['reveal']);
		}
	} else {
		define('unignore_tmp', '');
	}
} else {
	define('unignore_tmp', '');
	if (isset($_GET['reveal'])) {
		unset($_GET['reveal']);
	}
}

$_SERVER['QUERY_STRING_ENC'] = htmlspecialchars($_SERVER['QUERY_STRING']);

function make_tmp_unignore_lnk($id)
{
	if ($GLOBALS['FUD_OPT_2'] & 32768 && strpos($_SERVER['QUERY_STRING_ENC'], '?') === false) {
		$_SERVER['QUERY_STRING_ENC'] .= '?1=1';
	}

	if (!isset($_GET['reveal'])) {
		return $_SERVER['QUERY_STRING_ENC'] .'&amp;reveal='. $id;
	} else {
		return str_replace('&amp;reveal='. $_GET['reveal'], unignore_tmp .':'. $id, $_SERVER['QUERY_STRING_ENC']);
	}
}

function make_reveal_link($id)
{
	if ($GLOBALS['FUD_OPT_2'] & 32768 && strpos($_SERVER['QUERY_STRING_ENC'], '?') === false) {
		$_SERVER['QUERY_STRING_ENC'] .= '?1=1';
	}

	if (empty($GLOBALS['__FMDSP__'])) {
		return $_SERVER['QUERY_STRING_ENC'] .'&amp;rev='. $id;
	} else {
		return str_replace('&amp;rev='. $_GET['rev'], reveal_lnk .':'. $id, $_SERVER['QUERY_STRING_ENC']);
	}
}

/* Draws a message, needs a message object, user object, permissions array,
 * flag indicating wether or not to show controls and a variable indicating
 * the number of the current message (needed for cross message pager)
 * last argument can be anything, allowing forms to specify various vars they
 * need to.
 */
function tmpl_drawmsg($obj, $usr, $perms, $hide_controls, &$m_num, $misc)
{
	$o1 =& $GLOBALS['FUD_OPT_1'];
	$o2 =& $GLOBALS['FUD_OPT_2'];
	$a = (int) $obj->users_opt;
	$b =& $usr->users_opt;
	$MOD =& $GLOBALS['MOD'];

	$next_page = $next_message = $prev_message = '';
	/* Draw next/prev message controls. */
	if (!$hide_controls && $misc) {
		/* Tree view is a special condition, we only show 1 message per page. */
		if ($_GET['t'] == 'tree' || $_GET['t'] == 'tree_msg') {
			$prev_message = $misc[0] ? '<a href="javascript://" onclick="fud_tree_msg_focus('.$misc[0].', \''.s.'\', \'utf-8\'); return false;"><img src="/theme/default/images/up.png" title="Go to previous message" alt="Go to previous message" width="16" height="11" /></a>' : '';
			$next_message = $misc[1] ? '<a href="javascript://" onclick="fud_tree_msg_focus('.$misc[1].', \''.s.'\', \'utf-8\'); return false;"><img alt="Go to previous message" title="Go to next message" src="/theme/default/images/down.png" width="16" height="11" /></a>' : '';
		} else {
			/* Handle previous link. */
			if (!$m_num && $obj->id > $obj->root_msg_id) { /* prev link on different page */
				$prev_message = '<a href="/index.php?t='.$_GET['t'].'&amp;'._rsid.'&amp;prevloaded=1&amp;th='.$obj->thread_id.'&amp;start='.($misc[0] - $misc[1]).reveal_lnk.unignore_tmp.'"><img src="/theme/default/images/up.png" title="Go to previous message" alt="Go to previous message" width="16" height="11" /></a>';
			} else if ($m_num) { /* Inline link, same page. */
				$prev_message = '<a href="javascript://" onclick="chng_focus(\'#msg_num_'.$m_num.'\');"><img alt="Go to previous message" title="Go to previous message" src="/theme/default/images/up.png" width="16" height="11" /></a>';
			}

			/* Handle next link. */
			if ($obj->id < $obj->last_post_id) {
				if ($m_num && !($misc[1] - $m_num - 1)) { /* next page link */
					$next_message = '<a href="/index.php?t='.$_GET['t'].'&amp;'._rsid.'&amp;prevloaded=1&amp;th='.$obj->thread_id.'&amp;start='.($misc[0] + $misc[1]).reveal_lnk.unignore_tmp.'"><img alt="Go to previous message" title="Go to next message" src="/theme/default/images/down.png" width="16" height="11" /></a>';
					$next_page = '<a href="/index.php?t='.$_GET['t'].'&amp;'._rsid.'&amp;prevloaded=1&amp;th='.$obj->thread_id.'&amp;start='.($misc[0] + $misc[1]).reveal_lnk.unignore_tmp.'">Next Page <img src="/theme/default/images/goto.gif" alt="" /></a>';
				} else {
					$next_message = '<a href="javascript://" onclick="chng_focus(\'#msg_num_'.($m_num + 2).'\');"><img alt="Go to next message" title="Go to next message" src="/theme/default/images/down.png" width="16" height="11" /></a>';
				}
			}
		}
		++$m_num;
	}

	$user_login = $obj->user_id ? $obj->login : $GLOBALS['ANON_NICK'];

	/* Check if the message should be ignored and it is not temporarily revelead. */
	if ($usr->ignore_list && !empty($usr->ignore_list[$obj->poster_id]) && !isset($GLOBALS['__FMDSP__'][$obj->id])) {
		return !$hide_controls ? '<tr>
	<td>
		<table border="0" cellspacing="0" cellpadding="0" class="MsgTable">
		<tr>
			<td class="MsgIg al">
				<a name="msg_num_'.$m_num.'"></a>
				<a name="msg_'.$obj->id.'"></a>
				'.($obj->user_id ? 'Message by <a href="/index.php?t=usrinfo&amp;'._rsid.'&amp;id='.$obj->user_id.'">'.$obj->login.'</a> is ignored' : $GLOBALS['ANON_NICK'].' is ignored' )  .'&nbsp;
				[<a href="/index.php?'. make_reveal_link($obj->id).'">reveal message</a>]&nbsp;
				[<a href="/index.php?'.make_tmp_unignore_lnk($obj->poster_id).'">reveal all messages by '.$user_login.'</a>]&nbsp;
				[<a href="/index.php?t=ignore_list&amp;del='.$obj->poster_id.'&amp;redr=1&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">stop ignoring this user</a>]</td>
				<td class="MsgIg" align="right">'.$prev_message.$next_message.'
			</td>
		</tr>
		</table>
	</td>
</tr>' : '<tr class="MsgR1 GenText">
	<td><a name="msg_num_'.$m_num.'"></a> <a name="msg_'.$obj->id.'"></a>Post by '.$user_login.' is ignored&nbsp;</td>
</tr>';
	}

	if ($obj->user_id && !$hide_controls) {
		$custom_tag = $obj->custom_status ? '<br />'.$obj->custom_status : '';
		$c = (int) $obj->level_opt;

		if ($obj->avatar_loc && $a & 8388608 && $b & 8192 && $o1 & 28 && !($c & 2)) {
			if (!($c & 1)) {
				$level_name =& $obj->level_name;
				$level_image = $obj->level_img ? '&nbsp;<img src="/images/'.$obj->level_img.'" alt="" />' : '';
			} else {
				$level_name = $level_image = '';
			}
		} else {
			$level_image = $obj->level_img ? '&nbsp;<img src="/images/'.$obj->level_img.'" alt="" />' : '';
			$obj->avatar_loc = '';
			$level_name =& $obj->level_name;
		}
		$avatar = ($obj->avatar_loc || $level_image) ? '<td class="avatarPad wo">'.$obj->avatar_loc.$level_image.'</td>' : '';
		$dmsg_tags = ($custom_tag || $level_name) ? '<div class="ctags">'.$level_name.$custom_tag.'</div>' : '';

		if (($o2 & 32 && !($a & 32768)) || $b & 1048576) {
			$online_indicator = (($obj->time_sec + $GLOBALS['LOGEDIN_TIMEOUT'] * 60) > __request_timestamp__) ? '<img src="/theme/default/images/online.png" alt="'.$obj->login.' is currently online" title="'.$obj->login.' is currently online" />&nbsp;' : '<img src="/theme/default/images/offline.png" alt="'.$obj->login.' is currently offline" title="'.$obj->login.' is currently offline" />&nbsp;';
		} else {
			$online_indicator = '';
		}

		$user_link = '<a href="/index.php?t=usrinfo&amp;id='.$obj->user_id.'&amp;'._rsid.'">'.$user_login.'</a>';

		$location = $obj->location ? '<br /><b>Location: </b>'.(strlen($obj->location) > $GLOBALS['MAX_LOCATION_SHOW'] ? substr($obj->location, 0, $GLOBALS['MAX_LOCATION_SHOW']) . '...' : $obj->location) : '';

		if (_uid && _uid != $obj->user_id) {
			$buddy_link	= !isset($usr->buddy_list[$obj->user_id]) ? '<a href="/index.php?t=buddy_list&amp;add='.$obj->user_id.'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">add to buddy list</a><br />' : '<a href="/index.php?t=buddy_list&amp;del='.$obj->user_id.'&amp;redr=1&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">remove from buddy list</a><br />';
			$ignore_link	= !isset($usr->ignore_list[$obj->user_id]) ? '<a href="/index.php?t=ignore_list&amp;add='.$obj->user_id.'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">ignore all messages by this user</a>' : '<a href="/index.php?t=ignore_list&amp;del='.$obj->user_id.'&amp;redr=1&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">stop ignoring messages by this user</a>';
			$dmsg_bd_il	= $buddy_link.$ignore_link.'<br />';
		} else {
			$dmsg_bd_il = '';
		}

		/* Show im buttons if need be. */
		if ($b & 16384) {
			$im = '';
			if ($obj->icq) {
				$im .= '<a href="/index.php?t=usrinfo&amp;id='.$obj->poster_id.'&amp;'._rsid.'#icq_msg"><img title="'.$obj->icq.'" src="/theme/default/images/icq.png" alt="" /></a>';
			}
			if ($obj->aim) {
				$im .= '<a href="aim:goim?screenname='.$obj->aim.'&amp;message=Hi.+Are+you+there?"><img alt="" src="/theme/default/images/aim.png" title="'.$obj->aim.'" /></a>';
			}
			if ($obj->yahoo) {
				$im .= '<a href="http://edit.yahoo.com/config/send_webmesg?.target='.$obj->yahoo.'&amp;.src=pg"><img alt="" src="/theme/default/images/yahoo.png" title="'.$obj->yahoo.'" /></a>';
			}
			if ($obj->msnm) {
				$im .= '<a href="mailto: '.$obj->msnm.'"><img alt="" src="/theme/default/images/msnm.png" title="'.$obj->msnm.'" /></a>';
			}
			if ($obj->jabber) {
				$im .=  '<img src="/theme/default/images/jabber.png" title="'.$obj->jabber.'" alt="" />';
			}
			if ($obj->google) {
				$im .= '<img src="/theme/default/images/google.png" title="'.$obj->google.'" alt="" />';
			}
			if ($obj->skype) {
				$im .=  '<a href="callto://'.$obj->skype.'"><img src="/theme/default/images/skype.png" title="'.$obj->skype.'" alt="" /></a>';
			}
			if ($obj->twitter) {
				$im .=  '<a href="http://twitter.com/'.$obj->twitter.'"><img src="/theme/default/images/twitter.png" title="'.$obj->twitter.'" alt="" /></a>';
			}
			if ($im) {
				$dmsg_im_row = $im.'<br />';
			} else {
				$dmsg_im_row = '';
			}
		} else {
			$dmsg_im_row = '';
		}
	} else {
		$user_link = $obj->user_id ? $user_login : $user_login;
		$dmsg_tags = $dmsg_im_row = $dmsg_bd_il = $location = $online_indicator = $avatar = '';
	}

	/* Display message body.
	 * If we have message threshold & the entirity of the post has been revelead show a
	 * preview otherwise if the message body exists show an actual body.
	 * If there is no body show a 'no-body' message.
	 */
	if (!$hide_controls && $obj->message_threshold && $obj->length_preview && $obj->length > $obj->message_threshold && !isset($GLOBALS['__FMDSP__'][$obj->id])) {
		$msg_body = '<span class="MsgBodyText">'.read_msg_body($obj->offset_preview, $obj->length_preview, $obj->file_id_preview).'</span>
...<br /><br /><div class="ac">[ <a href="/index.php?'.make_reveal_link($obj->id).'">Show the rest of the message</a> ]</div>';
	} else if ($obj->length) {
		$msg_body = '<span class="MsgBodyText">'.read_msg_body($obj->foff, $obj->length, $obj->file_id).'</span>';
	} else {
		$msg_body = 'No Message Body';
	}

	/* Draw file attachments if there are any. */
	$drawmsg_file_attachments = '';
	if ($obj->attach_cnt && !empty($obj->attach_cache)) {
		$atch = unserialize($obj->attach_cache);
		if (!empty($atch)) {
			foreach ($atch as $v) {
				$sz = $v[2] / 1024;
				$drawmsg_file_attachments .= '<li>
	<img alt="" src="/images/mime/'.$v[4].'" class="at" />
	<span class="GenText fb">Attachment:</span> <a href="/index.php?t=getfile&amp;id='.$v[0].'&amp;'._rsid.'" title="'.$v[1].'">'.$v[1].'</a>
	<br />
	<span class="SmallText">(Size: '.($sz < 1000 ? number_format($sz, 2).'KB' : number_format($sz/1024, 2).'MB').', Downloaded '.convertPlural($v[3], array(''.$v[3].' time',''.$v[3].' times')).')</span>
</li>';
			}
			$drawmsg_file_attachments = '<ul class="AttachmentsList">
	'.$drawmsg_file_attachments.'
</ul>';
		}
		/* Append session to getfile. */
		if (_uid) {
			if ($o1 & 128 && !isset($_COOKIE[$GLOBALS['COOKIE_NAME']])) {
				$msg_body = str_replace('<img src="index.php?t=getfile', '<img src="index.php?t=getfile&amp;S='. s, $msg_body);
				$tap = 1;
			}
			if ($o2 & 32768 && (isset($tap) || $o2 & 8192)) {
				$pos = 0;
				while (($pos = strpos($msg_body, '<img src="index.php/fa/', $pos)) !== false) {
					$pos = strpos($msg_body, '"', $pos + 11);
					$msg_body = substr_replace($msg_body, _rsid, $pos, 0);
				}
			}
		}
	}

	if ($obj->poll_cache) {
		$obj->poll_cache = unserialize($obj->poll_cache);
	}

	/* Handle poll votes. */
	if (!empty($_POST['poll_opt']) && ($_POST['poll_opt'] = (int)$_POST['poll_opt']) && !($obj->thread_opt & 1) && $perms & 512) {
		if (register_vote($obj->poll_cache, $obj->poll_id, $_POST['poll_opt'], $obj->id)) {
			$obj->total_votes += 1;
			$obj->cant_vote = 1;
		}
		unset($_GET['poll_opt']);
	}

	/* Display poll if there is one. */
	if ($obj->poll_id && $obj->poll_cache) {
		/* We need to determine if we allow the user to vote or see poll results. */
		$show_res = 1;

		if (isset($_GET['pl_view']) && !isset($_POST['pl_view'])) {
			$_POST['pl_view'] = $_GET['pl_view'];
		}

		/* Various conditions that may prevent poll voting. */
		if (!$hide_controls && !$obj->cant_vote &&
			(!isset($_POST['pl_view']) || $_POST['pl_view'] != $obj->poll_id) &&
			($perms & 512 && (!($obj->thread_opt & 1) || $perms & 4096)) &&
			(!$obj->expiry_date || ($obj->creation_date + $obj->expiry_date) > __request_timestamp__) &&
			/* Check if the max # of poll votes was reached. */
			(!$obj->max_votes || $obj->total_votes < $obj->max_votes)
		) {
			$show_res = 0;
		}

		$i = 0;

		$poll_data = '';
		foreach ($obj->poll_cache as $k => $v) {
			++$i;
			if ($show_res) {
				$length = ($v[1] && $obj->total_votes) ? round($v[1] / $obj->total_votes * 100) : 0;
				$poll_data .= '<tr class="'.alt_var('msg_poll_alt_clr','RowStyleB','RowStyleA').'">
	<td>'.$i.'.</td>
	<td>'.$v[0].'</td>
	<td><img src="/theme/default/images/poll_pix.gif" alt="" height="10" width="'.$length.'" /> '.$v[1].' / '.$length.'%</td>
</tr>';
			} else {
				$poll_data .= '<tr class="'.alt_var('msg_poll_alt_clr','RowStyleB','RowStyleA').'">
	<td>'.$i.'.</td>
	<td colspan="2"><label><input type="radio" name="poll_opt" value="'.$k.'" />&nbsp;&nbsp;'.$v[0].'</label></td>
</tr>';
			}
		}

		if (!$show_res) {
			$poll = '<br />
<form action="/index.php?'.htmlspecialchars($_SERVER['QUERY_STRING']).'#msg_'.$obj->id.'" method="post">'._hs.'
<table cellspacing="1" cellpadding="2" class="PollTable">
<tr>
	<th class="nw" colspan="3">'.$obj->poll_name.'<span class="ptp">[ '.$obj->total_votes.' '.convertPlural($obj->total_votes, array('vote','votes')).' ]</span></th>
</tr>
'.$poll_data.'
<tr class="'.alt_var('msg_poll_alt_clr','RowStyleB','RowStyleA').' ar">
	<td colspan="3">
		<input type="submit" class="button" name="pl_vote" value="Vote" />
		&nbsp;'.($obj->total_votes ? '<input type="submit" class="button" name="pl_res" value="View Results" />' : '' )  .'
	</td>
</tr>
</table>
<input type="hidden" name="pl_view" value="'.$obj->poll_id.'" />
</form>
<br />';
		} else {
			$poll = '<br />
<table cellspacing="1" cellpadding="2" class="PollTable">
<tr>
	<th class="nw" colspan="3">'.$obj->poll_name.'<span class="vt">[ '.$obj->total_votes.' '.convertPlural($obj->total_votes, array('vote','votes')).' ]</span></th>
</tr>
'.$poll_data.'
</table>
<br />';
		}

		if (($p = strpos($msg_body, '{POLL}')) !== false) {
			$msg_body = substr_replace($msg_body, $poll, $p, 6);
		} else {
			$msg_body = $poll . $msg_body;
		}
	}

	/* Determine if the message was updated and if this needs to be shown. */
	if ($obj->update_stamp) {
		if ($obj->updated_by != $obj->poster_id && $o1 & 67108864) {
			$modified_message = '<p class="fl">[Updated on: '.strftime('%a, %d %B %Y %H:%M', $obj->update_stamp).'] by Moderator</p>';
		} else if ($obj->updated_by == $obj->poster_id && $o1 & 33554432) {
			$modified_message = '<p class="fl">[Updated on: '.strftime('%a, %d %B %Y %H:%M', $obj->update_stamp).']</p>';
		} else {
			$modified_message = '';
		}
	} else {
		$modified_message = '';
	}

	if ($_GET['t'] != 'tree' && $_GET['t'] != 'msg') {
		$lnk = d_thread_view;
	} else {
		$lnk =& $_GET['t'];
	}

	$rpl = '';
	if (!$hide_controls) {

		/* Show reply links, eg: [message #1 is a reply to message #2]. */
		if ($o2 & 536870912) {
			if ($obj->reply_to && $obj->reply_to != $obj->id) {
				$rpl = '<span class="SmallText">[<a href="/index.php?t='.$lnk.'&amp;th='.$obj->thread_id.'&amp;goto='.$obj->id.'&amp;'._rsid.'#msg_'.$obj->id.'">message #'.$obj->id.'</a> is a reply to <a href="/index.php?t='.$lnk.'&amp;th='.$obj->thread_id.'&amp;goto='.$obj->reply_to.'&amp;'._rsid.'#msg_'.$obj->reply_to.'">message #'.$obj->reply_to.'</a>]</span>';
			} else {
				$rpl = '<span class="SmallText">[<a href="/index.php?t='.$lnk.'&amp;th='.$obj->thread_id.'&amp;goto='.$obj->id.'&amp;'._rsid.'#msg_'.$obj->id.'">message #'.$obj->id.'</a>]</span>';
			}
		}

		/* Little trick, this variable will only be available if we have a next link leading to another page. */
		if (empty($next_page)) {
			$next_page = '&nbsp;';
		}

		// Edit button if editing is enabled, EDIT_TIME_LIMIT has not transpired, and there are no replies.
		if (_uid && 
			($perms & 16 ||
				(_uid == $obj->poster_id && 
					(!$GLOBALS['EDIT_TIME_LIMIT'] ||
					__request_timestamp__ - $obj->post_stamp < $GLOBALS['EDIT_TIME_LIMIT'] * 60
					) &&
				(($GLOBALS['FUD_OPT_3'] & 1024) || $obj->id == $obj->last_post_id))
			)
		   )
		{
			$edit_link = '<a href="/index.php?t=post&amp;msg_id='.$obj->id.'&amp;'._rsid.'"><img alt="" src="/theme/default/images/msg_edit.gif" /></a>&nbsp;&nbsp;&nbsp;&nbsp;';
		} else {
			$edit_link = '';
		}

		if (!($obj->thread_opt & 1) || $perms & 4096) {
			$reply_link = '<a href="/index.php?t=post&amp;reply_to='.$obj->id.'&amp;'._rsid.'"><img alt="" src="/theme/default/images/msg_reply.gif" /></a>&nbsp;';
			$quote_link = '<a href="/index.php?t=post&amp;reply_to='.$obj->id.'&amp;quote=true&amp;'._rsid.'"><img alt="" src="/theme/default/images/msg_quote.gif" /></a>';
		} else {
			$reply_link = $quote_link = '';
		}
	}

	return '<tr>
	<td class="MsgSpacer">
		<table cellspacing="0" cellpadding="0" class="MsgTable">
		<tr>
			<td class="MsgR1 vt al expanded"><a name="msg_num_'.$m_num.'"></a><a name="msg_'.$obj->id.'"></a>'.($obj->icon && !$hide_controls ? '<img src="/images/message_icons/'.$obj->icon.'" alt="'.$obj->icon.'" />&nbsp;&nbsp;' : '' )  .'<span class="MsgSubText"><a href="/index.php?t='.$lnk.'&amp;th='.$obj->thread_id.'&amp;goto='.$obj->id.'&amp;'._rsid.'#msg_'.$obj->id.'" class="MsgSubText">'.$obj->subject.'</a></span> '.$rpl.'</td>
			<td class="MsgR1 vt ar"><span class="DateText">'.strftime('%a, %d %B %Y %H:%M', $obj->post_stamp).'</span> '.$prev_message.$next_message.'</td>
		</tr>
		<tr class="MsgR2">
			<td class="MsgR2" colspan="2">
				<table cellspacing="0" cellpadding="0" class="ContentTable">
				<tr class="MsgR2">
				'.$avatar.'
					<td class="msgud">
						'.$online_indicator.'
						'.$user_link.'
						'.(!$hide_controls ? ($obj->disp_flag_cc && $GLOBALS['FUD_OPT_3'] & 524288 ? '&nbsp;&nbsp;<img src="/images/flags/'.$obj->disp_flag_cc.'.png" border="0" width="16" height="11" title="'.$obj->flag_country.'" alt="'.$obj->flag_country.'"/>' : '' )  .($obj->user_id ? '<br /><b>Messages:</b> '.$obj->posted_msg_count.'<br /><b>Registered:</b> '.strftime('%B %Y', $obj->join_date).' '.$location : '' )   : '' )  .'
						'.($GLOBALS['FUD_OPT_4'] & 4 && $obj->poster_id > 0 ? '<div class="karma_usr_'.$obj->poster_id.' SmallText">
'.($MOD ? '<a href="javascript://" onclick="window_open(\'/index.php?t=karma_track&amp;'._rsid.'&amp;msgid='.$obj->id.'\', \'karma_rating_track\', 300, 400);" class="karma">' : '' )  .'
	<b>Karma:</b> '.$obj->karma.'
'.($MOD ? '</a>' : '' )  .'
' : '' )  .'</div>
					</td>
					<td class="msgud">'.$dmsg_tags.'</td>
					<td class="msgot">'.$dmsg_bd_il.$dmsg_im_row.(!$hide_controls ? (($obj->host_name && $o1 & 268435456) ? '<b>From:</b> '.$obj->host_name.'<br />' : '' )  .(($b & 1048576 || $usr->md || $o1 & 134217728) ? '<b>IP:</b> <a href="/index.php?t=ip&amp;ip='.$obj->ip_addr.'&amp;'._rsid.'">'.$obj->ip_addr.'</a>' : '' )   : '' )  .'</td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td colspan="2" class="MsgR3">
		'.$msg_body.'
		'.$drawmsg_file_attachments.'
		'.(!$hide_controls ? (($obj->sig && $o1 & 32768 && $obj->msg_opt & 1 && $b & 4096 && !($a & 67108864)) ? '<br /><br /><div class="signature" />'.$obj->sig.'</div>' : '' )  .'
		<div class="SmallText clear">'.$modified_message.'<p class="fr"><a href="/index.php?t=report&amp;msg_id='.$obj->id.'&amp;'._rsid.'" rel="nofollow">Report message to a moderator</a></p>' : '' )  .'</div>
</td></tr>
'.(!$hide_controls ? '<tr>
	<td colspan="2" class="MsgToolBar">
		<table border="0" cellspacing="0" cellpadding="0" class="wa">
		<tr>
			<td class="al nw">
				'.($obj->user_id ? '<a href="/index.php?t=usrinfo&amp;id='.$obj->user_id.'&amp;'._rsid.'"><img alt="" src="/theme/default/images/msg_about.gif" /></a>&nbsp;'.(($o1 & 4194304 && $a & 16) ? '<a href="/index.php?t=email&amp;toi='.$obj->user_id.'&amp;'._rsid.'" rel="nofollow"><img alt="" src="/theme/default/images/msg_email.gif" /></a>&nbsp;' : '' )  .($o1 & 1024 ? '<a href="/index.php?t=ppost&amp;toi='.$obj->user_id.'&amp;rmid='.$obj->id.'&amp;'._rsid.'"><img alt="Send a private message to this user" title="Send a private message to this user" src="/theme/default/images/msg_pm.gif" /></a>' : '' )   : '' )  .'
				'.(($GLOBALS['FUD_OPT_4'] & 4 && $perms & 1024 && $obj->poster_id > 0 && !$obj->cant_karma && $obj->poster_id != $usr->id) ? '
    <span id=karma_link_'.$obj->id.' class="SmallText">Rate author:
	<a href="javascript://" onclick="changeKarma('.$obj->id.','.$obj->poster_id.',\'up\',\''.s.'\',\''.$usr->sq.'\');" class="karma up">+1</a>
	<a href="javascript://" onclick="changeKarma('.$obj->id.','.$obj->poster_id.',\'down\',\''.s.'\',\''.$usr->sq.'\');" class="karma down">-1</a>
    </span>
' : '' )  .'
			</td>
			<td class="GenText wa ac">'.$next_page.'</td>
			<td class="nw ar">
				'.($perms & 32 ? '<a href="/index.php?t=mmod&amp;del='.$obj->id.'&amp;'._rsid.'"><img alt="" src="/theme/default/images/msg_delete.gif" /></a>&nbsp;' : '' )  .'
				'.$edit_link.'
				'.$reply_link.'
				'.$quote_link.'
			</td>
		</tr>
		</table>
	</td>
</tr>' : '' )  .'
</table>
</td></tr>';
}function alt_var($key)
{
	if (!isset($GLOBALS['_ALTERNATOR_'][$key])) {
		$args = func_get_args(); unset($args[0]);
		$GLOBALS['_ALTERNATOR_'][$key] = array('p' => 2, 't' => func_num_args(), 'v' => $args);
		return $args[1];
	}
	$k =& $GLOBALS['_ALTERNATOR_'][$key];
	if ($k['p'] == $k['t']) {
		$k['p'] = 1;
	}
	return $k['v'][$k['p']++];
}$GLOBALS['__SML_CHR_CHK__'] = array("\n"=>1, "\r"=>1, "\t"=>1, ' '=>1, ']'=>1, '['=>1, '<'=>1, '>'=>1, '\''=>1, '"'=>1, '('=>1, ')'=>1, '.'=>1, ','=>1, '!'=>1, '?'=>1);

function smiley_to_post($text)
{
	$text_l = strtolower($text);
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'sp_cache';

	/* remove all non-formatting blocks */
	foreach (array('</pre>'=>'<pre>', '</span>' => '<span name="php">') as $k => $v) {
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
	include_once $GLOBALS['FORUM_SETTINGS_PATH'].'ps_cache';
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
}

	$pl_id = 0;
	$old_subject = $attach_control_error = '';

	/* Redirect user where need be in moderated forums after they've seen the moderation message. */
	if (isset($_POST['moderated_redr'])) {
		check_return($usr->returnto);
	}

	/* We do this because we don't want to take a chance that data is passed via cookies. */
	$src = empty($_POST) ? '_GET' : '_POST';
	foreach (array('reply_to', 'msg_id', 'th_id', 'frm_id') as $v) {
		$$v = isset(${$src}[$v]) ? (int) ${$src}[$v] : 0;
	}

	/* Replying or editing a message. */
	if ($reply_to || $msg_id) {
		if (($msg = db_sab('SELECT /* USE MASTER */ * FROM fud30_msg WHERE id='. ($reply_to ? $reply_to : $msg_id)))) {
			$msg->body = read_msg_body($msg->foff, $msg->length, $msg->file_id);
		} else {
			error_dialog('Invalid Message', 'The message you are trying to view does not exist.');
		}
	 	$th_id = $msg->thread_id;
	 	$msg->login = q_singleval('SELECT alias FROM fud30_users WHERE id='. $msg->poster_id);
	}

	if ($th_id) {
		$thr = db_sab('SELECT /* USE MASTER */ t.forum_id, t.replies, t.thread_opt, t.root_msg_id, t.orderexpiry, t.tdescr, m.subject FROM fud30_thread t INNER JOIN fud30_msg m ON t.root_msg_id=m.id WHERE t.id='. $th_id);
		if (!$thr) {
			invl_inp_err();
		}
		$frm_id = $thr->forum_id;
	} else if ($frm_id) {
		$thr = $th_id = null;
	} else {
		std_error('systemerr');
	}
	$frm = db_sab('SELECT /* USE MASTER */ id, cat_id, name, max_attach_size, forum_opt, max_file_attachments, post_passwd, message_threshold FROM fud30_forum WHERE id='. $frm_id);
	if (!$frm) {
		std_error('systemerr');
	}
	$frm->forum_opt = (int)$frm->forum_opt;

	/* Fetch permissions & moderation status. */
	$MOD = (int) ($is_a || ($usr->users_opt & 524288 && q_singleval('SELECT id FROM fud30_mod WHERE user_id='. _uid .' AND forum_id='. $frm->id)));
	// 0 = Anonymous & 2147483647 is a generic id for all registered users inside the group.
	$perms = perms_from_obj(db_sab(q_limit('SELECT group_cache_opt, '. $MOD .' as md FROM fud30_group_cache WHERE user_id IN('. _uid .','. (_uid ? '2147483647': '0') .') AND resource_id='. $frm->id .' ORDER BY user_id ASC', 1)), $is_a);

	/* More Security. */
	if ($thr && !($perms & 4096) && $thr->thread_opt & 1) {
		error_dialog('ERROR: Locked Topic', 'This topic is locked. Posting is no longer allowed.');
	}

	if (_uid) {
		/* All sorts of user blocking filters. */
		is_allowed_user($usr);

		/* If not moderator, validate user permissions. */
		if (!$reply_to && !$msg_id && !($perms & 4)) {
			std_error('perms');
		} else if (!$msg_id && ($th_id || $reply_to) && !($perms & 8)) {
			std_error('perms');
		} else if ($msg_id && $msg->poster_id != $usr->id && !($perms & 16)) {
			std_error('perms');
		} else if ($msg_id && $EDIT_TIME_LIMIT && (!$MOD && !($perms & 16)) && ($msg->post_stamp + $EDIT_TIME_LIMIT * 60 <__request_timestamp__)) {
			error_dialog('ERROR', 'You can no longer edit this message');
		} else if ($msg_id && !$MOD && $frm->forum_opt & 2) {
			error_dialog('ERROR', 'You cannot edit messages in a moderated forum.');
		}
	} else {
		if (__fud_real_user__) {
			is_allowed_user($usr);
		}

		if (!$th_id && !($perms & 4)) {
			ses_anonuser_auth($usr->sid, '<fieldset><legend>ERROR: Insufficient Privileges</legend>Anonymous users are not allowed to create topics.</fieldset><br />');
		} else if ($reply_to && !($perms & 8)) {
			ses_anonuser_auth($usr->sid, '<fieldset><legend>ERROR: Insufficient Privileges</legend>Anonymous users are not allowed to reply.</fieldset><br />');
		} else if (($msg_id && !($perms & 16)) || is_ip_blocked(get_ip())) {
			invl_inp_err();
		}
	}

	if (isset($_GET['prev_loaded'])) {
		$_POST['prev_loaded'] = $_GET['prev_loaded'];
	}

	$attach_list = array();
	$msg_tdescr = $msg_smiley_disabled = $msg_subject = $msg_body = '';

	/* Retrieve Message. */
	if (!isset($_POST['prev_loaded'])) {
		if (_uid) {
			$msg_show_sig = !$msg_id ? ($usr->users_opt & 2048) : ($msg->msg_opt & 1);

			if ($msg_id || $reply_to) {
				$msg_poster_notif = (($usr->users_opt & 2) && !q_singleval('SELECT /* USE MASTER */ id FROM fud30_msg WHERE thread_id='. $msg->thread_id .' AND poster_id='. _uid)) || is_notified(_uid, $msg->thread_id);
			} else {
				$msg_poster_notif = ($usr->users_opt & 2);
			}
		}

		if ($msg_id) {
			$msg_subject = apply_reverse_replace(reverse_fmt($msg->subject));
			$msg_tdescr = apply_reverse_replace(reverse_fmt($thr->tdescr));

			$msg_body = post_to_smiley($msg->body);
	 		if ($frm->forum_opt & 16) {
	 			$msg_body = html_to_tags($msg_body);
	 		} else if ($frm->forum_opt & 8) {
	 			$msg_body = reverse_nl2br(reverse_fmt($msg_body));
	 		}
	 		$msg_body = apply_reverse_replace($msg_body);

	 		$msg_smiley_disabled = ($msg->msg_opt & 2);
			$_POST['msg_icon'] = $msg->icon;

	 		if ($msg->attach_cnt) {
	 			$r = q('SELECT /* USE MASTER */ id FROM fud30_attach WHERE message_id='. $msg->id .' AND attach_opt=0');
	 			while ($fa_id = db_rowarr($r)) {
	 				$attach_list[$fa_id[0]] = $fa_id[0];
	 			}
	 			unset($r);
	 			$attach_count = count($attach_list);
		 	}
		 	$pl_id = (int) $msg->poll_id;
		} else if ($reply_to || $th_id) {
			$subj = reverse_fmt($reply_to ? $msg->subject : $thr->subject);

			$msg_subject = strncmp('Re:', $subj, strlen('Re:')) ? 'Re:' .' '. $subj : $subj;
			$old_subject = $msg_subject;

			if (isset($_GET['quote']) && $reply_to) {
				$msg_body = post_to_smiley(str_replace("\r", '', $msg->body));

				if (!strlen($msg->login)) {
					$msg->login =& $ANON_NICK;
				}
				$msg->login = reverse_fmt($msg->login);

				if ($frm->forum_opt & 16) {
					$msg_body = html_to_tags($msg_body);
				 	$msg_body = '[quote title='.$msg->login.' wrote on '.strftime('%a, %d %B %Y %H:%M', $msg->post_stamp).']'.$msg_body.'[/quote]';
				} else if ($frm->forum_opt & 8) {
					$msg_body = '> '. str_replace("\n", "\n> ", reverse_nl2br(reverse_fmt($msg_body)));
					$msg_body = str_replace('<br />', "\n", 'Quote: '.$msg->login.' wrote on '.strftime('%a, %d %B %Y %H:%M', $msg->post_stamp).'<br />----------------------------------------------------<br />'.$msg_body.'<br />----------------------------------------------------<br />');
				} else {
					$msg_body = '<cite>'.$msg->login.' wrote on '.strftime('%a, %d %B %Y %H:%M', $msg->post_stamp).'</cite><blockquote>'.$msg_body.'</blockquote>';
				}
				$msg_body .= "\n";
			}
		}
		$GLOBALS['MINIMSG_OPT_DISABLED'] = 0;
	} else { /* $_POST['prev_loaded'] */
		if ($FLOOD_CHECK_TIME && !$MOD && !$msg_id && ($tm = flood_check())) {
			error_dialog('ERROR: Post flood triggered.', 'Please try again in '.convertPlural($tm, array(''.$tm.' second',''.$tm.' seconds')).'');
		}

		/* Import message options. */
		$msg_show_sig		= isset($_POST['msg_show_sig']) ? (string)$_POST['msg_show_sig'] : '';
		$msg_smiley_disabled	= isset($_POST['msg_smiley_disabled']) ? (string)$_POST['msg_smiley_disabled'] : '';
		$msg_poster_notif	= isset($_POST['msg_poster_notif']) ? (string)$_POST['msg_poster_notif'] : '';
		$pl_id			= !empty($_POST['pl_id']) ? poll_validate((int)$_POST['pl_id'], $msg_id) : 0;
		$msg_body		= isset($_POST['msg_body']) ? (string)$_POST['msg_body'] : '';
		$msg_subject		= isset($_POST['msg_subject']) ? (string)$_POST['msg_subject'] : '';
		$msg_tdescr		= isset($_POST['msg_tdescr']) ? (string)$_POST['msg_tdescr'] : '';

		if ($perms & 256) {
			$attach_count = 0;

			/* Restore the attachment array. */
			if (!empty($_POST['file_array'])) {
				if ($usr->data === md5($_POST['file_array'])) {
					if (($attach_list = unserialize(base64_decode($_POST['file_array'])))) {
						foreach ($attach_list as $v) {
							if ($v) {
								$attach_count++;
							}
						}
					}
				} else if ($msg_id) { /* If checksum fails and we're editing a message, get attachment data from db. */
					$r = q('SELECT /* USE MASTER */ id FROM fud30_attach WHERE message_id='. $msg_id .' AND attach_opt=0');
		 			while ($fa_id = db_rowarr($r)) {
		 				$attach_list[$fa_id[0]] = $fa_id[0];
	 				}
	 				unset($r);
	 				$attach_count = count($attach_list);
				}
			}

			/* Remove file attachment. */
			if (!empty($_POST['file_del_opt']) && isset($attach_list[$_POST['file_del_opt']])) {
				$attach_list[$_POST['file_del_opt']] = 0;
				/* Remove any reference to the image from the body to prevent broken images. */
				if (strpos($msg_body, '[img]/index.php?t=getfile&id='. $_POST['file_del_opt'] .'[/img]') !== false) {
					$msg_body = str_replace('[img]/index.php?t=getfile&id='. $_POST['file_del_opt'] .'[/img]', '', $msg_body);
				}
				if (strpos($msg_body, '[img]'.$GLOBALS['WWW_ROOT'].'?t=getfile&id='. $_POST['file_del_opt'] .'[/img]') !== false) {
					$msg_body = str_replace('[img]'.$GLOBALS['WWW_ROOT'].'?t=getfile&id='.$_POST['file_del_opt'] .'[/img]', '', $msg_body);
				}
				$attach_count--;
			}

			if ($frm->forum_opt & 32 && $MOD) {
				$frm->max_attach_size = (int) ini_get('upload_max_filesize');
				$unit = str_replace($frm->max_attach_size, '', ini_get('upload_max_filesize'));
				if ($unit == 'M' || $unit == 'm') {
					$frm->max_attach_size *= 1024;
				}
				$frm->max_file_attachments = 100;
			}
			$MAX_F_SIZE = $frm->max_attach_size * 1024;

			/* Deal with newly uploaded files. */
			if (isset($_FILES['attach_control'])) {
				// Old themes may still have non-array upload controls without ...name="attach_control[]" multiple="multiple".
				// We do this so that even file upload fields that are not arrays, are processed as arrays... it's easier.
				if (isset($_FILES['attach_control']['name']) && !is_array($_FILES['attach_control']['name'])) {
					$_FILES['attach_control'] = array(
						'tmp_name' => array($_FILES['attach_control']['tmp_name']),
						'name'     => array($_FILES['attach_control']['name']),
						'size'     => array($_FILES['attach_control']['size']),
						'error'    => array($_FILES['attach_control']['error']),
						'type'     => array($_FILES['attach_control']['type']),
					);
				}
				foreach ($_FILES['attach_control']['error'] as $i => $error) {
					if ($error == UPLOAD_ERR_NO_FILE) {
						// No file uploaded, so no errors.
					} else if ($error == UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE || $_FILES['attach_control']['size'][$i] > $MAX_F_SIZE) {
						$attach_control_error = '<span class="ErrorText">File Attachment is too big (over allowed limit of '.$MAX_F_SIZE.' bytes)</span><br />';
					} else if (!($MOD && $frm->forum_opt & 32) && filter_ext($_FILES['attach_control']['name'][$i])) {
						$attach_control_error = '<span class="ErrorText">The file you are trying to upload does not match the allowed file types.</span><br />';
					} else if (($attach_count+1) > $frm->max_file_attachments) {
						$attach_control_error = '<span class="ErrorText">You are trying to upload more files than are allowed.</span><br />';
					} else if (empty($_FILES['attach_control']['tmp_name']) || $error != UPLOAD_ERR_OK) {
						continue;
					} else {
						$file = array();
						$file['tmp_name'] = $_FILES['attach_control']['tmp_name'][$i];
						$file['name']     = $_FILES['attach_control']['name'][$i];
						$file['size']     = $_FILES['attach_control']['size'][$i];
						$val = attach_add($file, _uid);
						$attach_list[$val] = $val;
						$attach_count++;
					}
				}
			}
			$attach_cnt = $attach_count;
		} else {
			$attach_cnt = 0;
		}

		/* Removal of a poll. */
		if (!empty($_POST['pl_del']) && $pl_id && $perms & 128) {
			poll_delete($pl_id);
			$pl_id = 0;
		}

		$no_spell_subject = ($reply_to && $old_subject == $msg_subject);

		if (($GLOBALS['MINIMSG_OPT_DISABLED'] = isset($_POST['btn_spell']))) {
			$text = apply_custom_replace($msg_body);
			$text_s = apply_custom_replace($msg_subject);

			if ($frm->forum_opt & 16) {
				$text = char_fix(tags_to_html($text, $perms & 32768));
			} else if ($frm->forum_opt & 8) {
				$text = char_fix(htmlspecialchars($text));
			}

			if ($perms & 16384 && !$msg_smiley_disabled) {
				$text = smiley_to_post($text);
			}

	 		if (strlen($text)) {
				$wa = tokenize_string($text);
				$msg_body = spell_replace($wa, 'body');

				if ($perms & 16384 && !$msg_smiley_disabled) {
					$msg_body = post_to_smiley($msg_body);
				}
				if ($frm->forum_opt & 16) {
					$msg_body = html_to_tags($msg_body);
				} else if ($frm->forum_opt & 8) {
					$msg_body = reverse_fmt($msg_body);
				}

				$msg_body = apply_reverse_replace($msg_body);
			}
			$wa = '';

			if (strlen($_POST['msg_subject']) && !$no_spell_subject) {
				$text_s = char_fix(htmlspecialchars($text_s));
				$wa = tokenize_string($text_s);
				$text_s = spell_replace($wa, 'subject');
				$msg_subject = apply_reverse_replace(reverse_fmt($text_s));
			}
		} else if (isset($_POST['spell'])) {
			$GLOBALS['MINIMSG_OPT_DISABLED'] = 1;
		}

		if (!empty($_POST['submitted']) && !isset($_POST['spell']) && !isset($_POST['preview'])) {
			$_POST['btn_submit'] = 1;
		}

		if (!$is_a && isset($_POST['btn_submit']) && $frm->forum_opt & 4 && (!isset($_POST['frm_passwd']) || $frm->post_passwd != $_POST['frm_passwd'])) {
			set_err('password', 'Incorrect password.');
		}

		/* Submit processing. */
		if (isset($_POST['btn_submit']) && !check_post_form()) {
			$msg_post = new fud_msg_edit;

			/* Process Message Data. */
			$msg_post->poster_id  = _uid;
			$msg_post->poll_id    = $pl_id;
			$msg_post->subject    = $msg_subject;
			$msg_post->body       = $msg_body;
			$msg_post->icon       = (isset($_POST['msg_icon']) && is_string($_POST['msg_icon']) && basename($_POST['msg_icon']) == $_POST['msg_icon'] && @file_exists($WWW_ROOT_DISK.'images/message_icons/'.$_POST['msg_icon'])) ? $_POST['msg_icon'] : '';
		 	$msg_post->msg_opt    =  $msg_smiley_disabled ? 2 : 0;
		 	$msg_post->msg_opt   |= $msg_show_sig ? 1 : 0;
		 	$msg_post->attach_cnt = (int) $attach_cnt;
			$msg_post->body       = apply_custom_replace($msg_post->body);

			if ($frm->forum_opt & 16) {
				$msg_post->body = char_fix(tags_to_html($msg_post->body, $perms & 32768));
			} else if ($frm->forum_opt & 8) {
				$msg_post->body = char_fix(nl2br(htmlspecialchars($msg_post->body)));
			}

	 		if ($perms & 16384 && !($msg_post->msg_opt & 2)) {
	 			$msg_post->body = smiley_to_post($msg_post->body);
	 		}

			fud_wordwrap($msg_post->body);

			$msg_post->subject = char_fix(htmlspecialchars(apply_custom_replace($msg_post->subject)));
			if (!empty($_POST['msg_tdescr'])) {
				$msg_tdescr = char_fix(htmlspecialchars(apply_custom_replace($_POST['msg_tdescr'])));
			} else {
				$msg_tdescr = '';
			}

			// PREPOST plugins.
			if (defined('plugins')) {
				$msg_post = plugin_call_hook('PRE_POST', $msg_post);
			}

		 	/* Choose to create thread OR add message OR update message. */
		 	if (!$th_id) {
		 		$create_thread = 1;
		 		$msg_post->add($frm->id, $frm->message_threshold, $frm->forum_opt, ($perms & (64|4096)), 0, $msg_tdescr);
		 	} else if ($th_id && !$msg_id) {
				$msg_post->thread_id = $th_id;
		 		$msg_post->add_reply($reply_to, $th_id, ($perms & (64|4096)), 0);
			} else if ($msg_id) {
				$msg_post->id              = $msg_id;
				$msg_post->thread_id       = $th_id;
				$msg_post->post_stamp      = $msg->post_stamp;
				$msg_post->mlist_msg_id    = $msg->mlist_msg_id;
				$msg_post->file_id         = $msg->file_id;
				$msg_post->file_id_preview = $msg->file_id_preview;
				$msg_post->sync(_uid, $frm->id, $frm->message_threshold, ($perms & (64|4096)), $msg_tdescr);
				/* Log moderator edit. */
			 	if (_uid && _uid != $msg->poster_id) {
			 		logaction($usr->id, 'MSGEDIT', $msg_post->id);
			 	}
			} else {
				std_error('systemerr');
			}

			/* Write file attachments. */
			if ($perms & 256 && $attach_list) {
				attach_finalize($attach_list, $msg_post->id);
			}

			if (!$msg_id &&	// New post.
			    (!($frm->forum_opt & 2) || $MOD) &&	// Forum not moderated.
				(!($usr->users_opt & 536870912)) &&	// User not moderated.
			    ($usr->posted_msg_count >= $MOD_FIRST_N_POSTS || $MOD))	// Min quota posts reached.
			{
				$msg_post->approve($msg_post->id);
			}

			if (_uid && !$msg_id) {
				/* Deal with notifications. */
	 			if (!empty($_POST['msg_poster_notif'])) {
	 				thread_notify_add(_uid, $msg_post->thread_id);
	 			} else {
	 				thread_notify_del(_uid, $msg_post->thread_id);
	 			}

				/* Register a view, so the forum marked as read. */
				user_register_forum_view($frm->id);
			}

			/* Where to redirect, to the tree view or the flat view and consider what to do for a moderated forum or post-only forum. */
			if (!$MOD && 		// Not a moderator.
			    !($perms & 2))	// p_READ (cannot read forum)
			{
				check_return();
			} else if (($frm->forum_opt & 2 && !$MOD) ||	// Forum is moderated & not a mod.
					   ($usr->users_opt & 536870912) ||	// User is moderated.
			           ($usr->posted_msg_count < $MOD_FIRST_N_POSTS && !$MOD))	// Min quota posts not reached.
			{
				if ($FUD_OPT_2 & 262144) {	// MODERATED_POST_NOTIFY
					$modl = db_all('SELECT u.email FROM fud30_mod mm INNER JOIN fud30_users u ON u.id=mm.user_id WHERE mm.forum_id='. $frm->id);
					if ($modl) {
						send_email($NOTIFY_FROM, $modl, 'New message in forum "'.$frm->name.'" pending approval', 'A new message titled "'.$msg_post->subject.'" was just posted in a forum that you moderate. To review this message go to: https://forum.wigedev.com/index.php?t=modque#'.$msg_post->id.'\n\nThis is an automated process. Do not reply to this message.\n', '');
					}
				}
				$data = file_get_contents($GLOBALS['INCLUDE'] .'theme/'. $usr->theme_name .'/usercp.inc');
				$s = strpos($data, '<?php') + 5;
				eval(substr($data, $s, (strrpos($data, '?>') - $s)));
				?>
				<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
	<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="<?php echo (!empty($META_DESCR) ? $META_DESCR.'' : $GLOBALS['FORUM_DESCR'].''); ?>" />
	<title><?php echo $GLOBALS['FORUM_TITLE'].$TITLE_EXTRA; ?></title>
	<link rel="search" type="application/opensearchdescription+xml" title="<?php echo $GLOBALS['FORUM_TITLE']; ?> Search" href="/open_search.php" />
	<?php echo $RSS; ?>
	<link rel="stylesheet" href="/theme/default/forum.css" media="screen" title="Default Forum Theme" />
	<link rel="stylesheet" href="/js/ui/jquery-ui.css" media="screen" />
	<script src="/js/jquery.js"></script>
	<script async src="/js/ui/jquery-ui.js"></script>
	<script src="/js/lib.js"></script>
</head>
<body>
<!--  -->
<div class="header">
  <?php echo ($GLOBALS['FUD_OPT_1'] & 1 && $GLOBALS['FUD_OPT_1'] & 16777216 ? '
  <div class="headsearch">
    <form id="headsearch" method="get" action="/index.php">'._hs.'
      <input type="hidden" name="t" value="search" />
      <br /><label accesskey="f" title="Forum Search">Forum Search:<br />
      <input type="search" name="srch" value="" size="20" placeholder="Forum Search" /></label>
      <input type="image" src="/theme/default/images/search.png" title="Search" name="btn_submit">&nbsp;
    </form>
  </div>
  ' : ''); ?>
  <a href="/" title="Home">
    <img class="headimg" src="/theme/default/images/header.gif" alt="" align="left" height="80" />
    <span class="headtitle"><?php echo $GLOBALS['FORUM_TITLE']; ?></span>
  </a><br />
  <span class="headdescr"><?php echo $GLOBALS['FORUM_DESCR']; ?><br /><br /></span>
</div>
<div class="content">

<!-- Table for sidebars. -->
<table width="100%"><tr><td>
<div id="UserControlPanel">
<ul>
	<?php echo $ucp_private_msg; ?>
	<?php echo ($FUD_OPT_4 & 16 ? '<li><a href="/index.php?t=blog&amp;'._rsid.'" title="Blog"><img src="/theme/default/images/blog.png" alt="" /> Blog</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_4 & 8 ? '<li><a href="/index.php?t=page&amp;'._rsid.'" title="Pages"><img src="/theme/default/images/pages.png" alt="" /> Pages</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_3 & 134217728 ? '<li><a href="/index.php?t=cal&amp;'._rsid.'" title="Calendar"><img src="/theme/default/images/calendar.png" alt="" /> Calendar</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_1 & 16777216 ? ' <li><a href="/index.php?t=search'.(isset($frm->forum_id) ? '&amp;forum_limiter='.(int)$frm->forum_id.'' : '' )  .'&amp;'._rsid.'" title="Search"><img src="/theme/default/images/top_search.png" alt="" /> Search</a></li>' : ''); ?>
	<li><a accesskey="h" href="/index.php?t=help_index&amp;<?php echo _rsid; ?>" title="Help"><img src="/theme/default/images/top_help.png" alt="" /> Help</a></li>
	<?php echo (($FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304) || $usr->users_opt & 1048576) ? '<li><a href="/index.php?t=finduser&amp;btn_submit=Find&amp;'._rsid.'" title="Members"><img src="/theme/default/images/top_members.png" alt="" /> Members</a></li>' : ''); ?>
	<?php echo (__fud_real_user__ ? '<li><a href="/index.php?t=uc&amp;'._rsid.'" title="Access the user control panel"><img src="/theme/default/images/top_profile.png" alt="" /> Control Panel</a></li>' : ($FUD_OPT_1 & 2 ? '<li><a href="/index.php?t=register&amp;'._rsid.'" title="Register"><img src="/theme/default/images/top_register.png" alt="" /> Register</a></li>' : '')).'
	'.(__fud_real_user__ ? '<li><a href="/index.php?t=login&amp;'._rsid.'&amp;logout=1&amp;SQ='.$GLOBALS['sq'].'" title="Logout"><img src="/theme/default/images/top_logout.png" alt="" /> Logout [ '.htmlspecialchars($usr->alias, null, null, false).' ]</a></li>' : '<li><a href="/index.php?t=login&amp;'._rsid.'" title="Login"><img src="/theme/default/images/top_login.png" alt="" /> Login</a></li>'); ?>
	<li><a href="/index.php?t=index&amp;<?php echo _rsid; ?>" title="Home"><img src="/theme/default/images/top_home.png" alt="" /> Home</a></li>
	<?php echo ($is_a || ($usr->users_opt & 268435456) ? '<li><a href="/adm/index.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'" title="Administration"><img src="/theme/default/images/top_admin.png" alt="" /> Administration</a></li>' : ''); ?>
</ul>
</div>
<div class="ctb"><table cellspacing="1" cellpadding="2" class="DialogTable">
<tr>
	<th>Moderated Forum Notice</th>
</tr>
<tr class="RowStyleA">
	<td class="GenText ac">
		You have made a post in a moderated forum. Your message will not be visible to others until it is approved by one of the forum&#39;s moderators or administrators.
		<br /><br /><form action="/index.php?t=post" method="post"><?php echo _hs; ?>
		<input type="submit" class="button" name="proceed" value="Proceed" />
		<input type="hidden" name="moderated_redr" value="1" />
		</form>
	</td>
</tr>
</table></div>
<br /><div class="ac"><span class="curtime"><b>Current Time:</b> <?php echo strftime('%a %b %d %H:%M:%S %Z %Y', __request_timestamp__); ?></span></div>
<?php echo (!empty($RIGHT_SIDEBAR) ? '
</td><td width="200px" align-"right" valign="top" class="sidebar-right">
	'.$RIGHT_SIDEBAR.'
' : ''); ?>
</td></tr></table>

</div>
<div class="footer ac">
	<b>.::</b>
	<a href="mailto:<?php echo $GLOBALS['ADMIN_EMAIL']; ?>">Contact</a>
	<b>::</b>
	<a href="/index.php?t=index&amp;<?php echo _rsid; ?>">Home</a>
	<b>::.</b>
	<p class="SmallText">Powered by: FUDforum <?php echo $GLOBALS['FORUM_VERSION']; ?>.<br />Copyright &copy;2001-2020 <a href="http://fudforum.org/">FUDforum Bulletin Board Software</a></p>
</div>

</body></html>
				<?php
				exit;
			} else {
				$t = d_thread_view;

				if ($msg_id && ($frm->forum_opt & 2) && !q_singleval('SELECT /* USE MASTER */ apr FROM fud30_msg WHERE id='. $msg_id)) { /* Editing unapproved message in moderated forum. */
					check_return($usr->returnto);
				}

				if ($usr->returnto) {
					if (!strncmp('t=selmsg', $usr->returnto, 8) || !strncmp('/sel/', $usr->returnto, 5)) {
						check_return($usr->returnto);
					}
					if (preg_match('!t=(tree|msg)!', $usr->returnto, $tmp)) {
						$t = $tmp[1];
					}
				}
				/* Redirect the user to their message. */
				if ($FUD_OPT_2 & 32768) {
					header('Location: /index.php/m/'. $msg_post->id .'/'. _rsidl .'#msg_'. $msg_post->id);
				} else {
					header('Location: /index.php?t='. $t .'&goto='. $msg_post->id .'&'. _rsidl .'#msg_'. $msg_post->id);
				}
				exit;
			}
		} /* Form submitted and user redirected to own message. */
	} /* $prevloaded is SET, this form has been submitted. */

	if ($reply_to || $th_id && !$msg_id) {
		ses_update_status($usr->sid, 'Replying to <a href="/index.php?t=msg&amp;goto='.$thr->root_msg_id.'#msg_'.$thr->root_msg_id.'">'.$thr->subject.'</a> in '.$frm->name.'', $frm->id, 0);
	} else if ($msg_id) {
		ses_update_status($usr->sid, 'Replying to <a href="/index.php?t=msg&amp;goto='.$thr->root_msg_id.'#msg_'.$thr->root_msg_id.'">'.$thr->subject.'</a> in '.$frm->name.'', $frm->id, 0);
	} else  {
		ses_update_status($usr->sid, 'Writing new topic in <a href="/index.php?t=rview&amp;frm_id='.$frm->id.'">'.$frm->name.'</a>', $frm->id, 0);
	}

/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/default/images/top_pm.png" alt="" /> You have <span class="GenTextRed">('.$c.')</span> unread '.convertPlural($c, array('private message','private messages')).'</a></li>' : '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/default/images/top_pm.png" alt="" /> Private Messaging</a></li>';
	} else {
		$ucp_private_msg = '';
	}$start = isset($_GET['start']) ? (int)$_GET['start'] : (isset($_POST['minimsg_pager_switch']) ? (int)$_POST['minimsg_pager_switch'] : 0);
if ($start < 0) {
	$start = 0;
}
if ($th_id && !$GLOBALS['MINIMSG_OPT_DISABLED']) {
	$count = $usr->posts_ppg ? $usr->posts_ppg : $POSTS_PER_PAGE;
	$total = $thr->replies + 1;

	if ($reply_to && !isset($_POST['minimsg_pager_switch']) && $total > $count) {
		$start = ($total - q_singleval('SELECT count(*) FROM fud30_msg WHERE thread_id='. $th_id .' AND apr=1 AND id>='. $reply_to));
		if ($start < 0) {
			$start = 0;
		}
		$msg_order_by = 'ASC';
	} else {
		$msg_order_by = 'DESC';
	}

	$use_tmp = $FUD_OPT_3 & 4096 && $total > 250;

	/* This is an optimization intended for topics with many messages. */
	if ($use_tmp) {
		q(q_limit('CREATE TEMPORARY TABLE fud30__mtmp_'. __request_timestamp__ .' AS SELECT id FROM fud30_msg WHERE thread_id='. $th_id .' AND apr=1 ORDER BY id '. $msg_order_by,
			$count, $start));
	}

	$q = 'SELECT m.*, t.thread_opt, t.root_msg_id, t.last_post_id, t.forum_id,
			u.id AS user_id, u.alias AS login, u.users_opt, u.last_visit AS time_sec,
			p.max_votes, p.expiry_date, p.creation_date, p.name AS poll_name,  p.total_votes
		FROM
			'.($use_tmp ? 'fud30__mtmp_'. __request_timestamp__ .' mt INNER JOIN fud30_msg m ON m.id=mt.id' : ' fud30_msg m') .'
			INNER JOIN fud30_thread t ON m.thread_id=t.id
			LEFT JOIN fud30_users u ON m.poster_id=u.id
			LEFT JOIN fud30_poll p ON m.poll_id=p.id';
	if ($use_tmp) {
		$q .= ' ORDER BY m.id '. $msg_order_by;
	} else {
		$q = q_limit($q .' WHERE m.thread_id='. $th_id .' AND m.apr=1 ORDER BY m.id '. $msg_order_by, $count, $start);
	}
	$c = q($q);

	$message_data='';
	$m_count = 0;
	while ($obj = db_rowobj($c)) {
		$message_data .= tmpl_drawmsg($obj, $usr, $perms, true, $m_count, '');
	}
	unset($c);

	if ($use_tmp && $FUD_OPT_1 & 256) {
		q('DROP TEMPORARY TABLE fud30__mtmp_'. __request_timestamp__);
	}

	$minimsg_pager = tmpl_create_pager($start, $count, $total, 'javascript: document.post_form.minimsg_pager_switch.value=\'%s\'; document.post_form.submit();', '', 0, 0, 1);
	$minimsg = '<br /><br />
<table cellspacing="0" cellpadding="3" class="wa dashed">
<tr>
	<td class="miniMH">Topic View</td>
</tr>
<tr>
	<td>
		<table cellspacing="1" cellpadding="2" class="ContentTable">
		'.$message_data.'
		</table>
	</td>
</tr>
<tr>
	<td>'.$minimsg_pager.'</td>
</tr>
</table>
<input type="hidden" name="minimsg_pager_switch" value="'.$start.'" />';
} else if ($th_id) {
	$minimsg = '<br /><br />
<table cellspacing="0" cellpadding="3" class="dashed wa">
<tr>
	<td class="ac">[<a href="javascript: document.forms[\'post_form\'].submit();">Reveal Thread</a>]</td>
</tr>
</table>
<input type="hidden" name="minimsg_pager_switch" value="'.$start.'" />';
} else {
	$minimsg = '';
}

	/* User cancelled operation. */
	if (isset($_POST['cancel'])) {
		// header('Location: /index.php?'. $usr->returnto);
		check_return($usr->returnto);
	}

	if (!$th_id) {
		$label = 'Create Topic';
	} else if ($msg_id) {
		$label = 'Apply Message Changes';
	} else {
		$label = 'Submit Reply';
	}

	$spell_check_button = ($FUD_OPT_1 & 2097152 && extension_loaded('pspell') && $usr->pspell_lang) ? '<input accesskey="k" type="submit" class="button" value="Spell-check Message" name="spell" />&nbsp;' : '';

	if (isset($_POST['preview']) || isset($_POST['spell'])) {
		$text = apply_custom_replace($msg_body);
		$text_s = apply_custom_replace($msg_subject);

		if ($frm->forum_opt & 16) {
			$text = char_fix(tags_to_html($text, $perms & 32768));
		} else if ($frm->forum_opt & 8) {
			$text = char_fix(nl2br(htmlspecialchars($text)));
		}

		if ($perms & 16384 && !$msg_smiley_disabled) {
			$text = smiley_to_post($text);
		}

		$text_s = char_fix(htmlspecialchars($text_s));

		$spell = $spell_check_button && isset($_POST['spell']);

		if ($spell && $text) {
			$text = check_data_spell($text, 'body', $usr->pspell_lang);
		}
		fud_wordwrap($text);

		if ($spell && !$no_spell_subject && $text_s) {
			$subj = check_data_spell($text_s, 'subject', $usr->pspell_lang);
		} else {
			$subj = $text_s;
		}

		if ($FUD_OPT_1 & 32768 && $msg_show_sig) {
			if ($msg_id && $msg->poster_id && $msg->poster_id != _uid && !$reply_to) {
				$sig = q_singleval('SELECT sig FROM fud30_users WHERE id='. $msg->poster_id);
			} else {
				$sig = $usr->sig;
			}

			$signature = $sig ? '<br /><br /><div class="signature">'.$sig.'</div>' : '';
		} else {
			$signature = '';
		}

		$preview_message = '<div id="preview" class="ctb">
<table cellspacing="1" cellpadding="2" class="PreviewTable">
<tr>
	<th colspan="2">Message Preview</th>
</tr>
<tr>
	<td class="RowStyleA MsgSubText">'.$subj.'</td>
</tr>
<tr>
        <td class="MsgR3">
                <span class="MsgBodyText">'.$text.$signature.'</span>
        </td>
</tr>
<tr>
	<td class="RowStyleB al">
		'.($spell ? '<input accesskey="a" type="submit" class="button" name="btn_spell" value="Apply Spelling Changes" />&nbsp;' : '' )  .'
		<input type="submit" class="button" value="Preview Message" tabindex="4" name="preview" />&nbsp;
		'.$spell_check_button.'
		<input type="submit" class="button" value="'.$label.'" tabindex="5" name="btn_submit" onclick="document.forms[\'post_form\'].submitted.value=1;" />
	</td>
</tr>
</table>
<br />
</div>';
	} else {
		$preview_message = '';
	}

	$post_error = get_err('msg_session');
	if (!$post_error && is_post_error()) {
		$post_error = '<h4 class="ac ErrorText">There was an error</h4>';
	}

	/* handle polls */
	$poll = '';
	if ($perms & 128) {
		if (!$pl_id) {
			$poll = '<tr class="RowStyleB">
	<td class="GenText">Poll:</td>
	<td class="GenText"><a href="javascript://" accesskey="o" onclick="window_open(\'/index.php?t=poll&amp;'._rsid.'&amp;frm_id='.$frm->id.'\', \'poll_creator\', 400, 300);">[CREATE POLL]</a></td>
</tr>';
		} else if (($poll = db_saq('SELECT /* USE MASTER */ id, name FROM fud30_poll WHERE id='. $pl_id))) {
			$poll = '<tr class="RowStyleB">
	<td class="GenText">Poll:</td>
	<td class="GenText">
		'.$poll[1].'
		[<a href="javascript://" accesskey="o" onclick="window_open(\'/index.php?t=poll&amp;'._rsid.'&amp;pl_id='.$poll[0].'&amp;frm_id='.$frm->id.'\', \'poll\', 400, 300);">EDIT</a>]
		<input type="hidden" name="pl_del" value="" />
		[<a href="javascript: document.forms[\'post_form\'].pl_del.value=\'1\'; document.forms[\'post_form\'].submit();">DELETE</a>]
	</td>
</tr>';
		}
	}

	/* Sticky/announcment controls. */
	if ($perms & 64 && (!$thr || $msg_id == $thr->root_msg_id)) {
		if (!isset($_POST['prev_loaded'])) {
			if (!$thr) {
				$thr_ordertype = $thr_orderexpiry = '';
			} else {
				$thr_ordertype = ($thr->thread_opt|1) ^ 1;
				$thr_orderexpiry = $thr->orderexpiry;
			}
		} else {
			$thr_ordertype = isset($_POST['thr_ordertype']) ? (int) $_POST['thr_ordertype'] : '';
			$thr_orderexpiry = isset($_POST['thr_orderexpiry']) ? (int) $_POST['thr_orderexpiry'] : '';
		}

		$thread_type_select = tmpl_draw_select_opt("0\n4\n2", "Normal\nSticky\nAnnouncement", $thr_ordertype);
		$thread_expiry_select = tmpl_draw_select_opt("1000000000\n3600\n7200\n14400\n28800\n57600\n86400\n172800\n345600\n604800\n1209600\n2635200\n5270400\n10540800\n938131200", "Never\n1 Hour\n3 Hours\n4 Hours\n8 Hours\n16 Hours\n1 Day\n2 Days\n4 Days\n1 Week\n2 Weeks\n1 Month\n2 Months\n4 Months\n1 Year", $thr_orderexpiry);

		$admin_options = '<tr class="RowStyleB">
	<td class="GenText nw">Moderator Options:</td>
	<td>
		Topic Type: <select name="thr_ordertype">'.$thread_type_select.'</select>
		Topic Expiration: <select name="thr_orderexpiry">'.$thread_expiry_select.'</select>
	</td>
</tr>';
	} else {
		$admin_options = '';
	}

	/* Thread locking controls. */
	if ($perms & 4096) {
		$thr_locked_checked = '';
		if (!isset($_POST['prev_loaded']) && $thr && $thr->thread_opt & 1) {
			$thr_locked_checked = ' checked';
		} else if (isset($_POST['prev_loaded']) && isset($_POST['thr_locked'])) {
			$thr_locked_checked = ' checked';
		}
		$mod_post_opts = '<tr>
	<td><input type="checkbox" name="thr_locked" id="thr_locked" value="Y"'.$thr_locked_checked.' /></td>
	<td class="GenText fb"><label for="thr_locked">Topic Locked</label></td>
</tr>';
	} else {
		$mod_post_opts = '';
	}

	$thr_always_on_top = '';
	if ($perms & 64) {
		if (!isset($_POST['prev_loaded']) && $thr && $thr->thread_opt & 8) {
			$thr_always_on_top = ' checked';
		} else if (isset($_POST['prev_loaded']) && isset($_POST['thr_always_on_top'])) {
			$thr_always_on_top = ' checked';
		}
	}

	$msg_body = $msg_body ? char_fix(htmlspecialchars(str_replace("\r", '', $msg_body))) : '';
	if ($msg_subject) {
		$msg_subject = char_fix(htmlspecialchars($msg_subject));
	}
	$msg_tdescr = char_fix(htmlspecialchars($msg_tdescr));

	/* Handle file attachments. */
	if ($perms & 256) {
		if ($frm->forum_opt & 32 && $MOD) {
			$frm->max_attach_size = (int) ini_get('upload_max_filesize');
			$t = str_replace($frm->max_attach_size, '', ini_get('upload_max_filesize'));
			if ($t == 'M' || $t == 'm') {
				$frm->max_attach_size *= 1024;
			}
			$frm->max_file_attachments = 100;
		}
		$file_attachments = draw_post_attachments($attach_list, $frm->max_attach_size*1024, $frm->max_file_attachments, $attach_control_error, 0, $msg_id);
	} else {
		$file_attachments = '';
	}

if ($FUD_OPT_2 & 2 || $is_a) {	// PUBLIC_STATS is enabled or Admin user.
	$page_gen_time = number_format(microtime(true) - __request_timestamp_exact__, 5);
	$page_stats = $FUD_OPT_2 & 2 ? '<br /><div class="SmallText al">Total time taken to generate the page: '.convertPlural($page_gen_time, array(''.$page_gen_time.' seconds')).'</div>' : '<br /><div class="SmallText al">Total time taken to generate the page: '.convertPlural($page_gen_time, array(''.$page_gen_time.' seconds')).'</div>';
} else {
	$page_stats = '';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
	<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="<?php echo (!empty($META_DESCR) ? $META_DESCR.'' : $GLOBALS['FORUM_DESCR'].''); ?>" />
	<title><?php echo $GLOBALS['FORUM_TITLE'].$TITLE_EXTRA; ?></title>
	<link rel="search" type="application/opensearchdescription+xml" title="<?php echo $GLOBALS['FORUM_TITLE']; ?> Search" href="/open_search.php" />
	<?php echo $RSS; ?>
	<link rel="stylesheet" href="/theme/default/forum.css" media="screen" title="Default Forum Theme" />
	<link rel="stylesheet" href="/js/ui/jquery-ui.css" media="screen" />
	<script src="/js/jquery.js"></script>
	<script async src="/js/ui/jquery-ui.js"></script>
	<script src="/js/lib.js"></script>
</head>
<body>
<!--  -->
<div class="header">
  <?php echo ($GLOBALS['FUD_OPT_1'] & 1 && $GLOBALS['FUD_OPT_1'] & 16777216 ? '
  <div class="headsearch">
    <form id="headsearch" method="get" action="/index.php">'._hs.'
      <input type="hidden" name="t" value="search" />
      <br /><label accesskey="f" title="Forum Search">Forum Search:<br />
      <input type="search" name="srch" value="" size="20" placeholder="Forum Search" /></label>
      <input type="image" src="/theme/default/images/search.png" title="Search" name="btn_submit">&nbsp;
    </form>
  </div>
  ' : ''); ?>
  <a href="/" title="Home">
    <img class="headimg" src="/theme/default/images/header.gif" alt="" align="left" height="80" />
    <span class="headtitle"><?php echo $GLOBALS['FORUM_TITLE']; ?></span>
  </a><br />
  <span class="headdescr"><?php echo $GLOBALS['FORUM_DESCR']; ?><br /><br /></span>
</div>
<div class="content">

<!-- Table for sidebars. -->
<table width="100%"><tr><td>
<div id="UserControlPanel">
<ul>
	<?php echo $ucp_private_msg; ?>
	<?php echo ($FUD_OPT_4 & 16 ? '<li><a href="/index.php?t=blog&amp;'._rsid.'" title="Blog"><img src="/theme/default/images/blog.png" alt="" /> Blog</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_4 & 8 ? '<li><a href="/index.php?t=page&amp;'._rsid.'" title="Pages"><img src="/theme/default/images/pages.png" alt="" /> Pages</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_3 & 134217728 ? '<li><a href="/index.php?t=cal&amp;'._rsid.'" title="Calendar"><img src="/theme/default/images/calendar.png" alt="" /> Calendar</a></li>' : ''); ?>
	<?php echo ($FUD_OPT_1 & 16777216 ? ' <li><a href="/index.php?t=search'.(isset($frm->forum_id) ? '&amp;forum_limiter='.(int)$frm->forum_id.'' : '' )  .'&amp;'._rsid.'" title="Search"><img src="/theme/default/images/top_search.png" alt="" /> Search</a></li>' : ''); ?>
	<li><a accesskey="h" href="/index.php?t=help_index&amp;<?php echo _rsid; ?>" title="Help"><img src="/theme/default/images/top_help.png" alt="" /> Help</a></li>
	<?php echo (($FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304) || $usr->users_opt & 1048576) ? '<li><a href="/index.php?t=finduser&amp;btn_submit=Find&amp;'._rsid.'" title="Members"><img src="/theme/default/images/top_members.png" alt="" /> Members</a></li>' : ''); ?>
	<?php echo (__fud_real_user__ ? '<li><a href="/index.php?t=uc&amp;'._rsid.'" title="Access the user control panel"><img src="/theme/default/images/top_profile.png" alt="" /> Control Panel</a></li>' : ($FUD_OPT_1 & 2 ? '<li><a href="/index.php?t=register&amp;'._rsid.'" title="Register"><img src="/theme/default/images/top_register.png" alt="" /> Register</a></li>' : '')).'
	'.(__fud_real_user__ ? '<li><a href="/index.php?t=login&amp;'._rsid.'&amp;logout=1&amp;SQ='.$GLOBALS['sq'].'" title="Logout"><img src="/theme/default/images/top_logout.png" alt="" /> Logout [ '.htmlspecialchars($usr->alias, null, null, false).' ]</a></li>' : '<li><a href="/index.php?t=login&amp;'._rsid.'" title="Login"><img src="/theme/default/images/top_login.png" alt="" /> Login</a></li>'); ?>
	<li><a href="/index.php?t=index&amp;<?php echo _rsid; ?>" title="Home"><img src="/theme/default/images/top_home.png" alt="" /> Home</a></li>
	<?php echo ($is_a || ($usr->users_opt & 268435456) ? '<li><a href="/adm/index.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'" title="Administration"><img src="/theme/default/images/top_admin.png" alt="" /> Administration</a></li>' : ''); ?>
</ul>
</div>
<form action="/index.php?t=post" method="post" id="post_form" name="post_form" enctype="multipart/form-data" onsubmit="document.forms['post_form'].btn_submit.disabled = true;">
<?php echo _hs; ?>
<input type="hidden" name="submitted" value="" />
<input type="hidden" name="reply_to" value="<?php echo $reply_to; ?>" />
<input type="hidden" name="th_id" value="<?php echo $th_id; ?>" />
<input type="hidden" name="frm_id" value="<?php echo $frm_id; ?>" />
<input type="hidden" name="start" value="<?php echo $start; ?>" />
<input type="hidden" name="msg_id" value="<?php echo $msg_id; ?>" />
<input type="hidden" name="pl_id" value="<?php echo $pl_id; ?>" />
<input type="hidden" name="old_subject" value="<?php echo htmlspecialchars($old_subject, null, null, false); ?>" />
<input type="hidden" name="prev_loaded" value="1" />
<?php echo $post_error; ?>
<?php echo $preview_message; ?>
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th colspan="2"><a name="ptop"> </a>Post Form</th>
</tr>
<?php echo (_uid ? '<tr class="RowStyleB">
	<td class="GenText nw">Logged in user:</td>
	<td class="GenText wa">'.htmlspecialchars($usr->alias, null, null, false).' [<a href="/index.php?t=login&amp;'._rsid.'&amp;logout=1&amp;SQ='.$GLOBALS['sq'].'">logout</a>]</td>
</tr>' : '
<tr class="RowStyleA">
	<td colspan="2" class="GenTextRed fb">You are not currently logged in, the message will be posted anonymously.</td>
</tr>
' )  .'
'.($frm->forum_opt & 4 && !$is_a ? '<tr class="RowStyleB">
	<td class="GenText">Posting Password:</td>
	<td><input type="password" name="frm_passwd" value="" tabindex="1" />'.get_err('password').'</td>
</tr>' : ''); ?>
<tr class="RowStyleB">
	<td class="GenText">Forum:</td>
	<td class="GenText"><?php echo $frm->name; ?></td>
</tr>
<tr class="RowStyleB">
	<td class="GenText">Title:</td>
	<td class="GenText"><input type="text" spellcheck="true" maxlength="100" name="msg_subject" value="<?php echo $msg_subject; ?>" size="50" tabindex="2" /> <?php echo get_err('msg_subject'); ?></td>
</tr>
<?php echo (!$th_id || ($msg_id && $msg_id == $thr->root_msg_id) ? '
<tr class="RowStyleB">
	<td class="GenText">Topic Description:</td>
	<td><input size="60" type="text" name="msg_tdescr" tabindex="3" value="'.$msg_tdescr.'" /></td>
</tr>
' : ''); ?>
<?php echo $poll; ?>
<?php echo $admin_options; ?>
<?php echo draw_post_icons((isset($_POST['msg_icon']) ? $_POST['msg_icon'] : '')); ?>
<?php echo ($perms & 16384 ? draw_post_smiley_cntrl().'' : ''); ?>
<?php echo ($frm->forum_opt & 16 ? '<tr class="RowStyleA"><td class="GenText nw">Formatting Tools:</td><td class="nw">
<span class="FormattingToolsBG">
	<span class="FormattingToolsCLR"><a title="Bold" accesskey="b" href="javascript: insertTag(\'txtb\', \'[b]\', \'[/b]\');"><img alt="" src="/theme/default/images/b_bold.gif" /></a></span>
	<span class="FormattingToolsCLR"><a title="Italics" accesskey="i" href="javascript: insertTag(\'txtb\', \'[i]\', \'[/i]\');"><img alt="" src="/theme/default/images/b_italic.gif" /></a></span>
	<span class="FormattingToolsCLR"><a title="Underline" accesskey="u" href="javascript: insertTag(\'txtb\', \'[u]\', \'[/u]\');"><img alt="" src="/theme/default/images/b_underline.gif" /></a></span>
	<span class="FormattingToolsCLR"><a title="Align Left" href="javascript: insertTag(\'txtb\', \'[ALIGN=left]\', \'[/ALIGN]\');"><img alt="" src="/theme/default/images/b_aleft.gif" /></a></span>
	<span class="FormattingToolsCLR"><a title="Align Center" href="javascript: insertTag(\'txtb\', \'[ALIGN=center]\', \'[/ALIGN]\');"><img alt="" src="/theme/default/images/b_acenter.gif" /></a></span>
	<span class="FormattingToolsCLR"><a title="Align Right" href="javascript: insertTag(\'txtb\', \'[ALIGN=right]\', \'[/ALIGN]\');"><img alt="" src="/theme/default/images/b_aright.gif" /></a></span>
	<span class="FormattingToolsCLR"><a title="Insert a Link" accesskey="w" href="javascript: url_insert(\'Link location:\');"><img alt="" src="/theme/default/images/b_url.gif" /></a></span>
	<span class="FormattingToolsCLR"><a title="Insert an E-mail address" accesskey="e" href="javascript: email_insert(\'E-mail address:\');"><img alt="" src="/theme/default/images/b_email.gif" /></a></span>
	<span class="FormattingToolsCLR"><a title="Insert an image" accesskey="m" href="javascript: image_insert(\'Image URL:\');"><img alt="" src="/theme/default/images/b_image.gif" /></a></span>
	<span class="FormattingToolsCLR"><a title="Add numbered list" accesskey="l" href="javascript: window_open(\'/index.php?t=mklist&amp;'._rsid.'&amp;tp=OL:1\', \'listmaker\', 350, 350);"><img alt="" src="/theme/default/images/b_numlist.gif" /></a></span>
	<span class="FormattingToolsCLR"><a title="Add bulleted list" href="javascript: window_open(\'/index.php?t=mklist&amp;'._rsid.'&amp;tp=UL:square\', \'listmaker\', 350, 350);"><img alt="" src="/theme/default/images/b_bulletlist.gif" /></a></span>
	<span class="FormattingToolsCLR"><a title="Add Quote" accesskey="q" href="javascript: insertTag(\'txtb\', \'[quote]\', \'[/quote]\');"><img alt="" src="/theme/default/images/b_quote.gif" /></a></span>
	<span class="FormattingToolsCLR"><a title="Add Code" accesskey="c" href="javascript: insertTag(\'txtb\', \'[code]\', \'[/code]\');"><img alt="" src="/theme/default/images/b_code.gif" /></a></span>
</span>
<span class="hide1">
&nbsp;&nbsp;
<select name="fnt_size" onchange="insertTag(\'txtb\', \'[size=\'+document.post_form.fnt_size.options[this.selectedIndex].value+\']\', \'[/size]\'); document.post_form.fnt_size.options[0].selected=true">
	<option value="" selected="selected">Size</option>
	<option value="1">1</option>
	<option value="2">2</option>
	<option value="3">3</option>
	<option value="4">4</option>
	<option value="5">5</option>
	<option value="6">6</option>
	<option value="7">7</option>
</select>
<select name="fnt_color" onchange="insertTag(\'txtb\', \'[color=\'+document.post_form.fnt_color.options[this.selectedIndex].value+\']\', \'[/color]\'); document.post_form.fnt_color.options[0].selected=true">
	<option value="">Color</option>
	<option value="skyblue" style="color:skyblue">Sky Blue</option>
	<option value="royalblue" style="color:royalblue">Royal Blue</option>
	<option value="blue" style="color:blue">Blue</option>
	<option value="darkblue" style="color:darkblue">Dark Blue</option>
	<option value="orange" style="color:orange">Orange</option>
	<option value="orangered" style="color:orangered">Orange Red</option>
	<option value="crimson" style="color:crimson">Crimson</option>
	<option value="red" style="color:red">Red</option>
	<option value="firebrick" style="color:firebrick">Firebrick</option>
	<option value="darkred" style="color:darkred">Dark Red</option>
	<option value="green" style="color:green">Green</option>
	<option value="limegreen" style="color:limegreen">Lime Green</option>
	<option value="seagreen" style="color:seagreen">Sea Green</option>
	<option value="deeppink" style="color:deeppink">Deep Pink</option>
	<option value="tomato" style="color:tomato">Tomato</option>
	<option value="coral" style="color:coral">Coral</option>
	<option value="purple" style="color:purple">Purple</option>
	<option value="indigo" style="color:indigo">Indigo</option>
	<option value="burlywood" style="color:burlywood">Burly Wood</option>
	<option value="sandybrown" style="color:sandybrown">Sandy Brown</option>
	<option value="sienna" style="color:sienna">Sienna</option>
	<option value="chocolate" style="color:chocolate">Chocolate</option>
	<option value="teal" style="color:teal">Teal</option>
	<option value="silver" style="color:silver">Silver</option>
</select>
<select name="fnt_face" onchange="insertTag(\'txtb\', \'[font=\'+document.post_form.fnt_face.options[this.selectedIndex].value+\']\', \'[/font]\'); document.post_form.fnt_face.options[0].selected=true">
	<option value="">Font</option>
	<option value="Arial" style="font-family:Arial">Arial</option>
	<option value="Times" style="font-family:Times">Times</option>
	<option value="Courier" style="font-family:Courier">Courier</option>
	<option value="Century" style="font-family:Century">Century</option>
</select>
</span>
</td></tr>' : ''); ?>

<tr class="RowStyleA">
	<td class="vt nw GenText">
		Body:<br /><br /><?php echo tmpl_post_options($frm->forum_opt, $perms); ?>
	</td>
	<td>
		<?php echo get_err('msg_body', 1); ?>
		<textarea rows="" cols="" tabindex="4" wrap="virtual" id="txtb" name="msg_body" style="width:98%; height:220px;"><?php echo $msg_body; ?></textarea>
	</td>
</tr>

<?php echo $file_attachments; ?>
<?php echo (!_uid && $FUD_OPT_3 & 8192 ? '<tr class="RowStyleA">
	<td>Verification:'.get_err('reg_turing').'</td>
	<td class="vt"><input type="text" name="turing_test" value="" /></td>
</tr>
<tr class="RowStyleB">
      <td colspan="2"><div style="white-space: pre; font-family: Courier, monospace; color: black; background-color: #C0C0C0;">'.generate_turing_val($turing_res).'
      <input type="hidden" name="turing_res" value="'.$turing_res.'" /></div></td>
</tr>' : ''); ?>
<tr class="RowStyleB vt">
	<td class="GenText">Options:</td>
	<td>
		<table border="0" cellspacing="0" cellpadding="1">
			<?php echo (_uid ? '<tr>
	<td><input type="checkbox" name="msg_poster_notif" id="msg_poster_notif" value="Y"'.($msg_poster_notif ? ' checked="checked"' : '' )  .' /></td>
	<td class="GenText fb"><label for="msg_poster_notif">Post Notification</label></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td class="SmallText">Notify me when someone replies to this message.</td>
</tr>
<tr>
	<td><input type="checkbox" name="msg_show_sig" id="msg_show_sig" value="Y"'.($msg_show_sig ? ' checked="checked"' : '' )  .' /></td>
	<td class="GenText fb"><label for="msg_show_sig">Include Signature</label></td>
</tr>
<tr>
	<td>&nbsp;</td><td class="SmallText">Include your profile signature.</td>
</tr>
'.$mod_post_opts.'
'.($perms & 64 && (!$th_id || $msg_id == $thr->root_msg_id) ? '
<tr>
	<td><input type="checkbox" name="thr_always_on_top" id="thr_always_on_top" value="Y"'.($thr_always_on_top ? ' checked="checked"' : '' )  .' /></td>
	<td class="GenText fb"><label for="thr_always_on_top">Make the topic always appear at the top of the topic listing</label></td>
</tr>
' : '' )  : ''); ?>
			<?php echo ($perms & 16384 ? '<tr>
	<td><input type="checkbox" name="msg_smiley_disabled" id="msg_smiley_disabled" value="Y"'.($msg_smiley_disabled ? ' checked="checked"' : '' )  .' /></td>
	<td class="GenText fb"><label for="msg_smiley_disabled">Disable smilies in this message</label></td>
</tr>' : ''); ?>
		</table>
	</td>
</tr>
<tr class="RowStyleA">
	<td class="GenText ar" colspan="2">
		<input type="submit" accesskey="c" class="button" value="Cancel" tabindex="4" name="cancel" />&nbsp;
		<input type="submit" accesskey="r" class="button" value="Preview Message" tabindex="5" name="preview" />&nbsp;
		<?php echo $spell_check_button; ?>
		<input type="submit" accesskey="s" class="button" value="<?php echo $label; ?>" tabindex="6" name="btn_submit" onclick="document.forms['post_form'].submitted.value=1;" />
	</td>
</tr>
</table>
<?php echo $minimsg; ?>
</form>
<br /><div class="ac"><span class="curtime"><b>Current Time:</b> <?php echo strftime('%a %b %d %H:%M:%S %Z %Y', __request_timestamp__); ?></span></div>
<?php echo $page_stats; ?>
<script>
quote_selected_text('Quote Selected Text');

if (!document.getElementById('preview')) {
	if (!document.post_form.msg_subject.value.length) {
		document.post_form.msg_subject.focus();
	} else {
		document.post_form.msg_body.focus();
	}
}
</script>
<?php echo (!empty($RIGHT_SIDEBAR) ? '
</td><td width="200px" align-"right" valign="top" class="sidebar-right">
	'.$RIGHT_SIDEBAR.'
' : ''); ?>
</td></tr></table>

</div>
<div class="footer ac">
	<b>.::</b>
	<a href="mailto:<?php echo $GLOBALS['ADMIN_EMAIL']; ?>">Contact</a>
	<b>::</b>
	<a href="/index.php?t=index&amp;<?php echo _rsid; ?>">Home</a>
	<b>::.</b>
	<p class="SmallText">Powered by: FUDforum <?php echo $GLOBALS['FORUM_VERSION']; ?>.<br />Copyright &copy;2001-2020 <a href="http://fudforum.org/">FUDforum Bulletin Board Software</a></p>
</div>

</body></html>
