<?php
/**
* copyright            : (C) 2001-2019 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: ppost.php.t 6240 2019-02-06 20:47:55Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
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
}$GLOBALS['recv_user_id'] = array();

class fud_pmsg
{
	var	$id, $to_list, $ouser_id, $duser_id, $pdest, $ip_addr, $host_name, $post_stamp, $icon, $fldr,
		$subject, $attach_cnt, $pmsg_opt, $length, $foff, $login, $ref_msg_id, $body;

	function add($track='')
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
				'. $this->ouser_id .',
				'. ($this->duser_id ? $this->duser_id : $this->ouser_id) .',
				'. (isset($GLOBALS['recv_user_id'][0]) ? (int)$GLOBALS['recv_user_id'][0] : '0') .',
				'. ssn($this->to_list) .',
				\''. $this->ip_addr .'\',
				'. $this->host_name .',
				'. $this->post_stamp .',
				'. ssn($this->icon) .',
				'. $this->fldr .',
				'. _esc($this->subject) .',
				'. (int)$this->attach_cnt .',
				'. $this->read_stamp .',
				'. ssn($this->ref_msg_id) .',
				'. (int)$this->foff .',
				'. (int)$this->length .',
				'. $this->pmsg_opt .'
			)');

		if ($GLOBALS['FUD_OPT_3'] & 32768 && $this->body) {
			$fid = db_qid('INSERT INTO fud30_msg_store (data) VALUES('. _esc($this->body) .')');
			q('UPDATE fud30_pmsg SET length='. $fid .' WHERE id='. $this->id);
		}

		if ($this->fldr == 3 && !$track) {
			$this->send_pmsg();
		}
	}

	function send_pmsg()
	{
		$this->pmsg_opt |= 16|32;
		$this->pmsg_opt &= 16|32|1|2|4;

		foreach($GLOBALS['recv_user_id'] as $v) {
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
				'. ssn($this->to_list).',
				'. $this->ouser_id .',
				\''. $this->ip_addr .'\',
				'. $this->host_name .',
				'. $this->post_stamp .',
				'. ssn($this->icon) .',
				1,
				'. _esc($this->subject) .',
				'. (int)$this->attach_cnt .',
				'. $this->foff .',
				'. $this->length .',
				'. $v .',
				'. ssn($this->ref_msg_id) .',
				'. $this->pmsg_opt .')');

			if ($GLOBALS['FUD_OPT_3'] & 32768 && $this->body) {
				$fid = db_qid('INSERT INTO fud30_msg_store (data) VALUES('. _esc($this->body) .')');
				q('UPDATE fud30_pmsg SET length='. $fid .' WHERE id='. $id);
			}

			$GLOBALS['send_to_array'][] = array($v, $id);
			$um[$v] = $id;
		}
		$c =  uq('SELECT id, email FROM fud30_users WHERE id IN('. implode(',', $GLOBALS['recv_user_id']) .') AND users_opt>=64 AND '. q_bitand('users_opt', 64) .' > 0');

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
		$this->ip_addr    = get_ip();
		$this->host_name  = $GLOBALS['FUD_OPT_1'] & 268435456 ? _esc(get_host($this->ip_addr)) : 'NULL';

		if ($GLOBALS['FUD_OPT_3'] & 32768) {	// DB_MESSAGE_STORAGE
			if ($fid = q_singleval('SELECT length FROM fud30_pmsg WHERE id='. $this->id .' AND foff!=-1')) {
				q('DELETE FROM fud30_msg_store WHERE id='. $this->length);
			}
			$this->foff = $this->length = -1;
		} else {
			list($this->foff, $this->length) = write_pmsg_body($this->body);
		}

		q('UPDATE fud30_pmsg SET
			to_list='. ssn($this->to_list) .',
			icon='. ssn($this->icon) .',
			ouser_id='. $this->ouser_id .',
			duser_id='. $this->ouser_id .',
			post_stamp='. $this->post_stamp .',
			subject='. _esc($this->subject) .',
			ip_addr=\''. $this->ip_addr .'\',
			host_name='. $this->host_name .',
			attach_cnt='. (int)$this->attach_cnt .',
			fldr='. $this->fldr .',
			foff='. (int)$this->foff .',
			length='. (int)$this->length .',
			pmsg_opt='. $this->pmsg_opt .'
		WHERE id='. $this->id);

		if ($GLOBALS['FUD_OPT_3'] & 32768 && $this->body) {
			$fid = db_qid('INSERT INTO fud30_msg_store (data) VALUES('. _esc($this->body) .')');
			q('UPDATE fud30_pmsg SET length='. $fid .' WHERE id='. $this->id);
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

	$fp = fopen($GLOBALS['MSG_STORE_DIR'] .'private', 'ab');
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
		@chmod($GLOBALS['MSG_STORE_DIR'] .'private', ($GLOBALS['FUD_OPT_2'] & 8388608 ? 0600 : 0644));
	}

	return array($s, $len);
}

function read_pmsg_body($offset, $length)
{
	if ($length < 1) {
		return;
	}

	if ($GLOBALS['FUD_OPT_3'] & 32768 && $offset == -1) {
		return q_singleval('SELECT data FROM fud30_msg_store WHERE id='. $length);
	}

	$fp = fopen($GLOBALS['MSG_STORE_DIR'].'private', 'rb');
	fseek($fp, $offset, SEEK_SET);
	$str = fread($fp, $length);
	fclose($fp);

	return $str;
}

function pmsg_move($mid, $fid, $validate)
{
	if (!$validate && !q_singleval('SELECT id FROM fud30_pmsg WHERE duser_id='. _uid .' AND id='. $mid)) {
		return;
	}

	q('UPDATE fud30_pmsg SET fldr='. $fid .' WHERE duser_id='. _uid .' AND id='. $mid);
}

function pmsg_del($mid, $fldr=0)
{
	if (!$fldr && !($fldr = q_singleval('SELECT fldr FROM fud30_pmsg WHERE duser_id='. _uid .' AND id='. $mid))) {
		return;
	}

	if ($fldr != 5) {
		pmsg_move($mid, 5, 0);
	} else {
		if ($GLOBALS['FUD_OPT_3'] & 32768 && ($fid = q_singleval('SELECT length FROM fud30_pmsg WHERE id='. $mid .' AND foff=-1'))) {
			q('DELETE FROM fud30_msg_store WHERE id='. $fid);
		}
		q('DELETE FROM fud30_pmsg WHERE id='.$mid);
		$c = uq('SELECT id FROM fud30_attach WHERE message_id='. $mid .' AND attach_opt=1');
		while ($r = db_rowarr($c)) {
			@unlink($GLOBALS['FILE_STORE'] . $r[0] .'.atch');
		}
		unset($c);
		q('DELETE FROM fud30_attach WHERE message_id='. $mid .' AND attach_opt=1');
	}
}

function send_pm_notification($email, $pid, $subject, $from)
{
	send_email($GLOBALS['NOTIFY_FROM'], $email, '['.$GLOBALS['FORUM_TITLE'].'] New Private Message Notification', 'You have a new private message titled "'.$subject.'", from "'.$from.'", in the forum "'.$GLOBALS['FORUM_TITLE'].'".\nTo view the message, click here: https://forum.wigedev.com/index.php?t=pmsg_view&id='.$pid.'\n\nTo stop future notifications, disable "Private Message Notification" in your profile.');
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
}$folders = array(1=>'Inbox', 2=>'Saved', 4=>'Draft', 3=>'Sent', 5=>'Trash');

function tmpl_cur_ppage($folder_id, $folders, $msg_subject='')
{
	if (!$folder_id || (!$msg_subject && $_GET['t'] == 'ppost')) {
		$user_action = 'Writing a Private Message';
	} else {
		$user_action = $msg_subject ? '<a href="/index.php?t=pmsg&amp;folder_id='.$folder_id.'&amp;'._rsid.'">'.$folders[$folder_id].'</a> &raquo; '.$msg_subject : 'Browsing <b>'.$folders[$folder_id].'</b> folder';
	}

	return '<span class="GenText"><a href="/index.php?t=pmsg&amp;'._rsid.'">Private Messaging</a>&nbsp;&raquo;&nbsp;'.$user_action.'</span><br /><img src="/blank.gif" alt="" height="4" width="1" /><br />';
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
}

function export_msg_data(&$m, &$msg_subject, &$msg_body, &$msg_icon, &$msg_smiley_disabled, &$msg_show_sig, &$msg_track, &$msg_to_list, $repl=0)
{
	$msg_subject = reverse_fmt($m->subject);
	$msg_body = read_pmsg_body($m->foff, $m->length);
	$msg_icon = $m->icon;
	$msg_smiley_disabled = $m->pmsg_opt & 2 ? '2' : '';
	$msg_show_sig = $m->pmsg_opt & 1 ? '1' : '';
	$msg_track = $m->pmsg_opt & 4 ? '4' : '';
	$msg_to_list = char_fix(htmlspecialchars($m->to_list));

	/* We do not revert replacment for forward/quote. */
	if ($repl) {
		$msg_subject = apply_reverse_replace($msg_subject);
		$msg_body = apply_reverse_replace($msg_body);
	}
	if (!$msg_smiley_disabled) {
		$msg_body = post_to_smiley($msg_body);
	}
	if ($GLOBALS['FUD_OPT_1'] & 4096) {
		$msg_body = html_to_tags($msg_body);
	} else if ($GLOBALS['FUD_OPT_1'] & 2048) {
		$msg_body = reverse_nl2br(reverse_fmt($msg_body));
	}
}

	if (__fud_real_user__) {
		is_allowed_user($usr);
	} else {
		std_error('login');
	}

	if (!($FUD_OPT_1 & 1024)) {
		error_dialog('ERROR: Private Messaging Disabled', 'You cannot use the private messaging system. It has been disabled by the administrator.');
	}
	if (!($usr->users_opt & 32)) {
		error_dialog('Private Messaging Disabled', 'You cannot send private messages until you enable the &#39;Allow Private Messages&#39; option in your profile.');
	}
	
	if ($usr->users_opt & 524288) {
		$ms = $MAX_PMSG_FLDR_SIZE_PM;
	} else if ($usr->users_opt & 1048576) {
		$ms = $MAX_PMSG_FLDR_SIZE_AD;
	} else {
		$ms = $MAX_PMSG_FLDR_SIZE;
	}

	if ($GLOBALS['FUD_OPT_3'] & 32768) {
		$fldr_size  = q_singleval('SELECT SUM(length) FROM fud30_pmsg WHERE foff>0 AND duser_id='. _uid);
		$fldr_size += q_singleval('SELECT SUM(LENGTH(data)) FROM fud30_pmsg p INNER JOIN fud30_msg_store m ON p.length=m.id WHERE foff<0 AND duser_id='. _uid);
	} else {
		$fldr_size = q_singleval('SELECT SUM(length) FROM fud30_pmsg WHERE duser_id='. _uid);
	}
	if ($fldr_size > $ms) {
		error_dialog('No space left!', 'Your private message folders exceed the allowed storage size of <b>'.$GLOBALS['MAX_PMSG_FLDR_SIZE'].' bytes</b>. They currently contain <b>'.$fldr_size.' bytes</b> worth of messages.<br /><br /><span class="ErrorText">Remove some old messages to clear up space.</span>');
	}

	$attach_control_error = '';

	$attach_count = 0;
	$attach_list = array();

	if (!isset($_POST['prev_loaded'])) {
		/* Setup some default values. */
		$msg_subject = $msg_body = $msg_icon = $old_subject = $msg_ref_msg_id = '';
		$msg_track = '';
		$msg_show_sig = $usr->users_opt & 2048 ? '1' : '';
		$msg_smiley_disabled = $FUD_OPT_1 & 8192 ? '' : '2';
		$reply = $forward = $msg_id = 0;

		/* Deal with users passed via GET. */
		if (isset($_GET['toi']) && ($toi = (int)$_GET['toi'])) {
			$msg_to_list = q_singleval('SELECT alias FROM fud30_users WHERE id='. $toi .' AND id>1');
		} else {
			$msg_to_list = '';
		}

		/* See if we have pre-defined subject being passed (via message id). */
		if (isset($_GET['rmid']) && ($rmid = (int)$_GET['rmid'])) {
			fud_use('is_perms.inc');
			make_perms_query($fields, $join, 't.forum_id');

			$msg_subject = q_singleval('SELECT m.subject FROM fud30_msg m 
							INNER JOIN fud30_thread t ON t.id=m.thread_id
							'.$join.'
							LEFT JOIN fud30_mod mm ON mm.forum_id=t.forum_id AND mm.user_id='. _uid .'
							WHERE m.id='. $rmid . ($GLOBALS['is_a'] ? '' : ' AND (mm.id IS NOT NULL OR '. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 2) .' > 0 )'));
			$msg_subject = html_entity_decode($msg_subject);
		}

		if (isset($_GET['msg_id']) && ($msg_id = (int)$_GET['msg_id'])) { /* Editing a message. */
			if (($msg_r = db_sab('SELECT id, subject, length, foff, to_list, icon, attach_cnt, pmsg_opt, ref_msg_id FROM fud30_pmsg WHERE id='. $msg_id .' AND duser_id='._uid))) {
				export_msg_data($msg_r, $msg_subject, $msg_body, $msg_icon, $msg_smiley_disabled, $msg_show_sig, $msg_track, $msg_to_list, 1);
			}
		} else if (isset($_GET['quote']) || isset($_GET['forward'])) { /* Quote or forward message. */
			if (($msg_r = db_sab('SELECT id, post_stamp, ouser_id, subject, length, foff, to_list, icon, attach_cnt, pmsg_opt, ref_msg_id '. (isset($_GET['quote']) ? ', to_list' : '') .' FROM fud30_pmsg WHERE id='. (int)(isset($_GET['quote']) ? $_GET['quote'] : $_GET['forward']) .' AND duser_id='. _uid))) {
				$reply = $quote = isset($_GET['quote']) ? (int)$_GET['quote'] : 0;
				$forward = isset($_GET['forward']) ? (int)$_GET['forward'] : 0;

				export_msg_data($msg_r, $msg_subject, $msg_body, $msg_icon, $msg_smiley_disabled, $msg_show_sig, $msg_track, $msg_to_list);
				$msg_id = $msg_to_list = '';

				if ($quote) {
					$msg_to_list = q_singleval('SELECT alias FROM fud30_users WHERE id='. $msg_r->ouser_id);
				}

				if ($quote) {
					if ($FUD_OPT_1 & 4096) {
						$msg_body = '[quote title='.$msg_to_list.' wrote on '.strftime('%a, %d %B %Y %H:%M', $msg_r->post_stamp).']'.$msg_body.'[/quote]';
					} else if ($FUD_OPT_1 & 2048) {
						$msg_body = "> ".str_replace("\n", "\n> ", $msg_body);
						$msg_body = str_replace('<br />', "\n", 'Quote: '.$msg_to_list.' wrote on '.strftime('%a, %d %B %Y %H:%M', $msg_r->post_stamp).'<br />----------------------------------------------------<br />'.$msg_body.'<br />----------------------------------------------------<br />');
					} else {
						$msg_body = '<cite>'.$msg_to_list.' wrote on '.strftime('%a, %d %B %Y %H:%M', $msg_r->post_stamp).'</cite><blockquote>'.$msg_body.'</blockquote>';
					}

					if (strncmp($msg_subject, 'Re: ', 4)) {
						$old_subject = $msg_subject = 'Re: '. $msg_subject;
					}
					$msg_ref_msg_id = 'R'.$reply;
					unset($msg_r);
				} else if ($forward && strncmp($msg_subject, 'Fwd: ', 5)) {
					$old_subject = $msg_subject = 'Fwd: '. $msg_subject;
					$msg_ref_msg_id = 'F'.$forward;
				}
			}
		} else if (isset($_GET['reply']) && ($reply = (int)$_GET['reply'])) {
			if (($msg_r = db_saq('SELECT p.subject, u.alias FROM fud30_pmsg p INNER JOIN fud30_users u ON p.ouser_id=u.id WHERE p.id='. $reply .' AND p.duser_id='. _uid))) {
				$msg_subject = $msg_r[0];
				$msg_to_list = $msg_r[1];

				if (strncmp($msg_subject, 'Re: ', 4)) {
					$old_subject = $msg_subject = 'Re: '. $msg_subject;
				}
				$msg_subject = reverse_fmt($msg_subject);
				unset($msg_r);
				$msg_ref_msg_id = 'R'.$reply;
			}
		}

		/* Restore file attachments. */
		if (!empty($msg_r->attach_cnt) && $PRIVATE_ATTACHMENTS > 0) {
			$c = uq('SELECT id FROM fud30_attach WHERE message_id='. $msg_r->id .' AND attach_opt=1');
	 		while ($r = db_rowarr($c)) {
	 			$attach_list[$r[0]] = $r[0];
	 		}
	 		unset($c);
		}
	} else {
		if (isset($_POST['btn_action'])) {
			if ($_POST['btn_action'] == 'draft') {
				$_POST['btn_draft'] = 1;
			} else if ($_POST['btn_action'] == 'send') {
				$_POST['btn_submit'] = 1;
			}
		}

		$msg_to_list = char_fix(htmlspecialchars($_POST['msg_to_list']));
		$msg_subject = $_POST['msg_subject'];
		$old_subject = $_POST['old_subject'];
		$msg_body = $_POST['msg_body'];
		$msg_icon = (isset($_POST['msg_icon']) && basename($_POST['msg_icon']) == $_POST['msg_icon'] && @file_exists($WWW_ROOT_DISK .'images/message_icons/'. $_POST['msg_icon'])) ? $_POST['msg_icon'] : '';
		$msg_track = isset($_POST['msg_track']) ? '4' : '';
		$msg_smiley_disabled = isset($_POST['msg_smiley_disabled']) ? '2' : '';
		$msg_show_sig = isset($_POST['msg_show_sig']) ? '1' : '';

		$reply = isset($_POST['reply']) ? (int)$_POST['reply'] : 0;
		$forward = isset($_POST['forward']) ? (int)$_POST['forward'] : 0;
		$msg_id = isset($_POST['msg_id']) ? (int)$_POST['msg_id'] : 0;
		$msg_ref_msg_id = isset($_POST['msg_ref_msg_id']) ? (int)$_POST['msg_ref_msg_id'] : '';

		/* Restore file attachments. */
		if (!empty($_POST['file_array']) && $PRIVATE_ATTACHMENTS > 0 && $usr->data === md5($_POST['file_array'])) {
			$attach_list = unserialize(base64_decode($_POST['file_array']));
		}
	}

	if ($attach_list) {
		$enc = base64_encode(serialize($attach_list));
		foreach ($attach_list as $v) {
			if ($v) {
				$attach_count++;
			}
		}
		/* Remove file attachment. */
		if (isset($_POST['file_del_opt'], $attach_list[$_POST['file_del_opt']])) {
			if ($attach_list[$_POST['file_del_opt']]) {
				$attach_list[$_POST['file_del_opt']] = 0;
				/* Remove any reference to the image from the body to prevent broken images. */
				if (strpos($msg_body, '[img]/index.php?t=getfile&id='. $_POST['file_del_opt'] .'[/img]') !== false) {
					$msg_body = str_replace('[img]/index.php?t=getfile&id='. $_POST['file_del_opt'] .'[/img]', '', $msg_body);
				}
				if (strpos($msg_body, '[img]'.$GLOBALS['WWW_ROOT'].'?t=getfile&id='. $_POST['file_del_opt'] .'[/img]') !== false) {
					$msg_body = str_replace('[img]'.$GLOBALS['WWW_ROOT'].'?t=getfile&id='. $_POST['file_del_opt'] .'[/img]', '', $msg_body);
				}
				$attach_count--;
			}
		}
	}

	/* Deal with newly uploaded files. */
	if ($PRIVATE_ATTACHMENTS > 0 && isset($_FILES['attach_control'])) {
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
			} else if ($error == UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE || $_FILES['attach_control']['size'][$i] > $PRIVATE_ATTACH_SIZE) {
				$MAX_F_SIZE = $PRIVATE_ATTACH_SIZE;
				$attach_control_error = '<span class="ErrorText">File Attachment is too big (over allowed limit of '.$MAX_F_SIZE.' bytes)</span><br />';
			} else if (filter_ext($_FILES['attach_control']['name'][$i])) {
				$attach_control_error = '<span class="ErrorText">The file you are trying to upload does not match the allowed file types.</span><br />';
			} else if (($attach_count+1) > $PRIVATE_ATTACHMENTS) {
				$attach_control_error = '<span class="ErrorText">You are trying to upload more files than are allowed.</span><br />';
			} else if (empty($_FILES['attach_control']['tmp_name']) || $error != UPLOAD_ERR_OK) {
				continue;
			} else {
				$file = array();
				$file['tmp_name'] = $_FILES['attach_control']['tmp_name'][$i];
				$file['name']     = $_FILES['attach_control']['name'][$i];
				$file['size']     = $_FILES['attach_control']['size'][$i];
				$val = attach_add($file, _uid, 1);
				$attach_list[$val] = $val;
				$attach_count++;
			}
		}
	}

	if ((isset($_POST['btn_submit']) && !check_ppost_form($_POST['msg_subject'])) || isset($_POST['btn_draft'])) {
		$msg_p = new fud_pmsg;
		$msg_p->pmsg_opt = (int) $msg_smiley_disabled | (int) $msg_show_sig | (int) $msg_track;
		$msg_p->attach_cnt = $attach_count;
		$msg_p->icon = $msg_icon;
		$msg_p->body = $msg_body;
		$msg_p->subject = $msg_subject;
		$msg_p->fldr = isset($_POST['btn_submit']) ? 3 : 4;
		$msg_p->to_list = $_POST['msg_to_list'];

		$msg_p->body = apply_custom_replace($msg_p->body);
		if ($FUD_OPT_1 & 4096) {
			$msg_p->body = char_fix(tags_to_html($msg_p->body, $FUD_OPT_1 & 16384));
		} else if ($FUD_OPT_1 & 2048) {
			$msg_p->body = char_fix(nl2br(htmlspecialchars($msg_p->body)));
		}

		if (!($msg_p->pmsg_opt & 2)) {
			$msg_p->body = smiley_to_post($msg_p->body);
		}
		fud_wordwrap($msg_p->body);

		$msg_p->ouser_id = _uid;

		$msg_p->subject = char_fix(htmlspecialchars(apply_custom_replace($msg_p->subject)));

		if (empty($_POST['msg_id'])) {
			$msg_p->pmsg_opt = $msg_p->pmsg_opt &~ 96;
			if ($_POST['reply']) {
				$msg_p->ref_msg_id = 'R'. $_POST['reply'];
				$msg_p->pmsg_opt |= 64;
			} else if ($_POST['forward']) {
				$msg_p->ref_msg_id = 'F'. $_POST['forward'];
			} else {
				$msg_p->ref_msg_id = null;
				$msg_p->pmsg_opt |= 32;
			}

			$msg_p->add();
		} else {
			$msg_p->id = (int) $_POST['msg_id'];
			$msg_p->sync();
		}

		if ($attach_list) {
			attach_finalize($attach_list, $msg_p->id, 1);

			/* We need to add attachments to all copies of the message. */
			if (!isset($_POST['btn_draft'])) {
				$atl = array();
				$c = uq('SELECT id, original_name, mime_type, fsize FROM fud30_attach WHERE message_id='. $msg_p->id .' AND attach_opt=1');
				while ($r = db_rowarr($c)) {
					$atl[$r[0]] = _esc($r[1]) .', '. $r[2] .', '. $r[3];
				}
				unset($c);
				if ($atl) {
					$aidl = array();

					foreach ($GLOBALS['send_to_array'] as $mid) {
						foreach ($atl as $k => $v) {
							$aid = db_qid('INSERT INTO fud30_attach (owner, attach_opt, message_id, original_name, mime_type, fsize, location) VALUES('. $mid[0] .', 1, '. $mid[1] .', '. $v .', \'placeholder\')');
							$aidl[] = $aid;
							copy($FILE_STORE . $k .'.atch', $FILE_STORE . $aid .'.atch');
							@chmod($FILE_STORE . $aid .'.atch', ($FUD_OPT_2 & 8388608 ? 0600 : 0644));
						}
					}
					$cc = q_concat(_esc($FILE_STORE), 'id', _esc('.atch'));
					q('UPDATE fud30_attach SET location='. $cc .' WHERE id IN('. implode(',', $aidl) .')');
				}
			}
		}

		if ($usr->returnto) {
			check_return($usr->returnto);
		}

		if ($FUD_OPT_2 & 32768) {
			header('Location: /index.php/pdm/1/'. _rsidl);
		} else {
			header('Location: /index.php?t=pmsg&'. _rsidl .'&fldr=1');
		}
		exit;
	}

	$no_spell_subject = ($reply && $old_subject == $msg_subject);

	if (isset($_POST['btn_spell'])) {
		$text = apply_custom_replace($_POST['msg_body']);
		$text_s = apply_custom_replace($_POST['msg_subject']);

		if ($FUD_OPT_1 & 4096) {
			$text = char_fix(tags_to_html($text, $FUD_OPT_1 & 16384));
		} else if ($FUD_OPT_1 & 2048) {
			$text = char_fix(htmlspecialchars($text));
		}

		if ($FUD_OPT_1 & 8192 && !$msg_smiley_disabled) {
			$text = smiley_to_post($text);
		}

	 	if ($text) {
			$text = spell_replace(tokenize_string($text), 'body');

			if ($FUD_OPT_1 & 8192 && !$msg_smiley_disabled) {
				$msg_body = post_to_smiley($text);
			}

			if ($FUD_OPT_1 & 4096) {
				$msg_body = html_to_tags($msg_body);
			} else if ($FUD_OPT_1 & 2048) {
				$msg_body = reverse_fmt($msg_body);
			}
			$msg_body = apply_reverse_replace($msg_body);
		}

		if ($text_s && !$no_spell_subject) {
			$text_s = char_fix(htmlspecialchars($text_s));
			$text_s = spell_replace(tokenize_string($text_s), 'subject');
			$msg_subject = apply_reverse_replace(reverse_fmt($text_s));
		}
	}

	ses_update_status($usr->sid, 'Using private messaging', 0, 1);

/* Print number of unread private messages in User Control Panel. */
	if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$ucp_private_msg = $c ? '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/default/images/top_pm.png" alt="" /> You have <span class="GenTextRed">('.$c.')</span> unread '.convertPlural($c, array('private message','private messages')).'</a></li>' : '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/default/images/top_pm.png" alt="" /> Private Messaging</a></li>';
	} else {
		$ucp_private_msg = '';
	}$tabs = '';
if (_uid) {
	$tablist = array(
'Notifications'=>'uc',
'Account Settings'=>'register',
'Subscriptions'=>'subscribed',
'Bookmarks'=>'bookmarked',
'Referrals'=>'referals',
'Buddy List'=>'buddy_list',
'Ignore List'=>'ignore_list',
'Show Own Posts'=>'showposts'
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

		foreach($tablist as $tab_name => $tab) {
			$tab_url = '/index.php?t='. $tab . (s ? '&amp;S='. s : '');
			if ($tab == 'referals') {
				if (!($FUD_OPT_2 & 8192)) {
					continue;
				}
				$tab_url .= '&amp;id='. _uid;
			} else if ($tab == 'showposts') {
				$tab_url .= '&amp;id='. _uid;
			}
			$tabs .= $pg == $tab ? '<td class="tabON"><div class="tabT"><a class="tabON" href="'.$tab_url.'">'.$tab_name.'</a></div></td>' : '<td class="tabI"><div class="tabT"><a href="'.$tab_url.'">'.$tab_name.'</a></div></td>';
		}

		$tabs = '<table cellspacing="1" cellpadding="0" class="tab">
<tr>
	'.$tabs.'
</tr>
</table>';
	}
}

	$spell_check_button = ($FUD_OPT_1 & 2097152 && extension_loaded('pspell') && $usr->pspell_lang) ? '<input accesskey="k" type="submit" class="button" value="Spell-check Message" name="spell" />&nbsp;' : '';

	if (isset($_POST['preview']) || isset($_POST['spell'])) {
		$text = apply_custom_replace($_POST['msg_body']);
		$text_s = apply_custom_replace($_POST['msg_subject']);

		if ($FUD_OPT_1 & 4096) {
			$text = char_fix(tags_to_html($text, $FUD_OPT_1 & 16384));
		} else if ($FUD_OPT_1 & 2048) {
			$text = char_fix(nl2br(htmlspecialchars($text)));
		}

		if ($FUD_OPT_1 & 8192 && !$msg_smiley_disabled) {
			$text = smiley_to_post($text);
		}
		$text_s = char_fix(htmlspecialchars($text_s));

		$spell = $spell_check_button && isset($_POST['spell']);

		if ($spell && strlen($text)) {
			$text = check_data_spell($text, 'body', $usr->pspell_lang);
		}
		fud_wordwrap($text);

		$subj = ($spell && !$no_spell_subject && $text_s) ? check_data_spell($text_s, 'subject', $usr->pspell_lang) : $text_s;

		$signature = ($FUD_OPT_1 & 32768 && $usr->sig && $msg_show_sig) ? '<br /><br /><div class="signature">'.$usr->sig.'</div>' : '';
		$apply_spell_changes = $spell ? '<input accesskey="a" type="submit" class="button" name="btn_spell" value="Apply Spelling Changes" />&nbsp;' : '';
		$preview_message = '<div id="preview" class="ctb"><table cellspacing="1" cellpadding="2" class="PreviewTable">
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
	<td class="al RowStyleB">
		'.$apply_spell_changes.'
		<input type="submit" class="button" name="btn_submit" value="Send" tabindex="5" onclick="document.post_form.btn_action.value=\'send\';">&nbsp;
		<input type="submit" tabindex="4" class="button" value="Preview Message" name="preview" />&nbsp;'.$spell_check_button.'<input type="submit" class="button" name="btn_draft" value="Save Draft" onclick="document.post_form.btn_action.value=\'draft\';" />
	</td>
</tr>
</table></div><br />';
	} else {
		$preview_message = '';
	}

	$post_error = is_post_error() ? '<h4 class="ErrorText ac">There was an error</h4>' : '';
	$session_error = get_err('msg_session');
	if ($session_error) {
		$post_error = $session_error;
	}

	$msg_body = $msg_body ? char_fix(htmlspecialchars(str_replace("\r", '', $msg_body))) : '';
	if ($msg_subject) {
		$msg_subject = char_fix(htmlspecialchars($msg_subject));
	}

	if ($PRIVATE_ATTACHMENTS > 0) {
		$file_attachments = draw_post_attachments($attach_list, $PRIVATE_ATTACH_SIZE, $PRIVATE_ATTACHMENTS, $attach_control_error, 1, $msg_id ? $msg_id : (isset($_GET['forward']) ? (int)$_GET['forward'] : 0));
	} else {
		$file_attachments = '';
	}

	if ($reply && ($mm = db_sab('SELECT p.*, u.id AS user_id, u.sig, u.alias, u.users_opt, u.posted_msg_count, u.join_date, u.last_visit FROM fud30_pmsg p INNER JOIN fud30_users u ON p.ouser_id=u.id WHERE p.duser_id='. _uid .' AND p.id='. $reply))) {
		fud_use('drawpmsg.inc');
		$dpmsg_prev_message = $dpmsg_next_message = '';
		$reference_msg = '<br /><br />
<div class="ac">message you are forwarding or replying to</div>
<table cellspacing="0" cellpadding="3" class="dashed wa">
<tr>
	<td>
		<table cellspacing="1" cellpadding="2" class="ContentTable">
			'.tmpl_drawpmsg($mm, $usr, true).'
		</table>
	</td>
</tr>
</table>';
	} else {
		$reference_msg = '';
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
<?php echo $tabs; ?>
<br /><?php echo tmpl_cur_ppage('', $folders); ?>
<form action="/index.php?t=ppost" method="post" id="post_form" name="post_form" enctype="multipart/form-data" onsubmit="document.post_form.btn_submit.disabled = true; document.post_form.btn_draft.disabled = true;">
<?php echo _hs; ?>
<input type="hidden" name="btn_action" value="" />
<input type="hidden" name="msg_id" value="<?php echo $msg_id; ?>" />
<input type="hidden" name="reply" value="<?php echo $reply; ?>" />
<input type="hidden" name="forward" value="<?php echo $forward; ?>" />
<input type="hidden" name="old_subject" value="<?php echo $old_subject; ?>" />
<input type="hidden" name="msg_ref_msg_id" value="<?php echo $msg_ref_msg_id; ?>" />
<input type="hidden" name="prev_loaded" value="1" />
<?php echo $post_error; ?>
<?php echo $preview_message; ?>
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr>
	<th colspan="2">Post Form<a name="ptop"> </a></th>
</tr>
<tr class="RowStyleB">
	<td class="GenText nw">Logged in user:</td>
	<td class="GenText wa"><?php echo htmlspecialchars($usr->alias, null, null, false); ?> [<a href="/index.php?t=login&amp;<?php echo _rsid; ?>&amp;logout=1&amp;SQ=<?php echo $GLOBALS['sq']; ?>">logout</a>]</td>
</tr>
<tr class="RowStyleB">
	<td class="GenText">To:</td>
	<td class="GenText"><input type="text" name="msg_to_list" id="msg_to_list" value="<?php echo $msg_to_list; ?>" tabindex="1" /> <?php echo ($FUD_OPT_1 & (8388608|4194304) ? '[<a href="javascript://" onclick="window_open(\'/index.php?t=pmuserloc&amp;'._rsid.'&amp;js_redr=post_form.msg_to_list\',\'user_list\',400,250);">Find User</a>]' : ''); ?> [<a href="javascript://" onclick="window_open('/index.php?t=qbud&amp;<?php echo _rsid; ?>&amp;1=1', 'buddy_list',275,300);">Select from Buddy List</a>]<?php echo get_err('msg_to_list'); ?></td>
</tr>
<tr class="RowStyleB">
	<td class="GenText">Title:</td>
	<td class="GenText"><input type="text" spellcheck="true" maxlength="100" name="msg_subject" value="<?php echo $msg_subject; ?>" size="50" tabindex="2" /> <?php echo get_err('msg_subject'); ?></td>
</tr>
<?php echo draw_post_icons($msg_icon); ?>
<?php echo ($FUD_OPT_1 & 8192 ? draw_post_smiley_cntrl().'' : ''); ?>
<?php echo ($FUD_OPT_1 & 4096 ? '<tr class="RowStyleA"><td class="GenText nw">Formatting Tools:</td><td class="nw">
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

<tr class="RowStyleA"><td class="nw vt GenText">
	Body:<br /><br /><?php echo tmpl_post_options('private'); ?>
</td><td>
	<?php echo get_err('msg_body',1); ?>
	<textarea id="txtb" name="msg_body" rows="" cols="" wrap="virtual" tabindex="3" style="width:98%; height:220px;"><?php echo $msg_body; ?></textarea>
</td></tr>

<?php echo $file_attachments; ?>
<tr class="RowStyleB vt">
	<td class="GenText">Options:</td>
	<td>
		<table border="0" cellspacing="0" cellpadding="1">
		<tr>
			<td><input type="checkbox" name="msg_track" id="msg_track" value="Y"<?php echo ($msg_track ? ' checked="checked"' : ''); ?> /></td>
			<td class="GenText fb"><label for="msg_track">Track This Message</label></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td class="SmallText">Notify me (via private message) when this message gets read.</td>
		</tr>
		<tr>
			<td><input type="checkbox" name="msg_show_sig" id="msg_show_sig" value="Y"<?php echo ($msg_show_sig ? ' checked="checked"' : ''); ?> /></td>
			<td class="GenText fb"><label for="msg_show_sig">Include Signature</label></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td class="SmallText">Include your profile signature.</td>
		</tr>
		<?php echo ($FUD_OPT_1 & 8192 ? '<tr>
	<td><input type="checkbox" name="msg_smiley_disabled" id="msg_smiley_disabled" value="Y" '.($msg_smiley_disabled ? ' checked="checked"' : '' )  .' /></td>
	<td class="GenText"><b><label for="msg_smiley_disabled">Disable smilies in this message</label></b></td>
</tr>' : ''); ?>
		</table>
	</td>
</tr>
<tr class="RowStyleA">
	<td class="GenText ar" colspan="2">
		<input accesskey="r" type="submit" tabindex="4" class="button" value="Preview Message" name="preview" />&nbsp;
		<?php echo $spell_check_button; ?>
		<input type="submit" accesskey="d" class="button" name="btn_draft" value="Save Draft" onclick="document.post_form.btn_action.value='draft';" />&nbsp;
		<input type="submit" class="button" name="btn_submit" value="Send" tabindex="5" onclick="document.post_form.btn_action.value='send';" accesskey="s" />
	</td>
</tr>
</table>
</form>
<?php echo $reference_msg; ?>
<br /><div class="ac"><span class="curtime"><b>Current Time:</b> <?php echo strftime('%a %b %d %H:%M:%S %Z %Y', __request_timestamp__); ?></span></div>
<?php echo $page_stats; ?>

<style>
	.ui-autocomplete-loading { background: white url("/theme/default/images/ajax-loader.gif") right center no-repeat; }
</style>
<script>
	jQuery(function() {
		jQuery("#msg_to_list").autocomplete({
			source: "index.php?t=autocomplete&lookup=alias", minLength: 1
		});
	});
</script>

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
