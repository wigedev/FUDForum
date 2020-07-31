<?php
/**
* copyright            : (C) 2001-2019 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: root_index.php.t 6283 2019-05-25 15:36:52Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

use Model\DB as Database;
use TemplateEngine\Renderer\TwigRenderer;

// TODO: Remove this
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('xdebug.var_display_max_depth', 5);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);

if (function_exists('mb_internal_encoding')) {
		mb_internal_encoding('utf-8');
	}
	require('./GLOBALS.php');

	/* Activation check. */
	if (!($FUD_OPT_1 & 1)) {	// FORUM_ENABLED
		fud_use('errmsg.inc');
		exit_forum_disabled();
	}

# define('fud_query_stats', 1);

#### Additions by WigeDev ##############################################################################################
// Load the composer autoloader
require_once('../vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

#### End Additions #####################################################################################################

class db { public static $db, $slave, $affected_rows, $res; }

if (empty(db::$db)) {
	db::$db = Database::getDB();
}

function db_close()
{
	Database::i()->db_close();
	//db::$db = null;
}

function db_version()
{
	return Database::i()->db_version();
}

function db_lock($tables)
{
	Database::i()->db_lock($tables);
}

function db_unlock()
{
	Database::i()->db_unlock();
}

function db_locked()
{
	return Database::i()->db_locked();
}

function db_affected()
{
	return Database::i()->db_affected();
}

function __enifo($a)
{
	return end($a);	// Return last element of error array.
}

function uq($query, $buf=0)
{
	return Database::i()->uq($query, $buf);
}

function q($query)
{
	return Database::i()->q($query);
}

function db_rowobj($result)
{
	return Database::i()->db_rowobj($result);
}

function db_rowarr($result)
{
	return Database::i()->db_rowarr($result);
}

function q_singleval($query)
{
	return Database::i()->q_singleval($query);
}

function q_limit($query, $limit, $off=0)
{
	return Database::i()->q_limit($query, $limit, $off);
}

function q_concat($arg)
{
	return Database::i()->q_concat($arg);
}

function q_rownum() {
	return Database::i()->q_rownum();
}

function q_bitand($fieldLeft, $fieldRight) {
	return $fieldLeft .' & '. $fieldRight;
}

function q_bitor($fieldLeft, $fieldRight) {
	return '('. $fieldLeft .' | '. $fieldRight .')';
}

function q_bitnot($bitField) {
	return '~'. $bitField;
}

function db_saq($q)
{
	return Database::i()->db_saq($q);
}

function db_sab($q)
{
	return Database::i()->db_sab($q);
}

function db_qid($q)
{
	return Database::i()->db_qid($q);
}

function db_arr_assoc($q)
{
	return Database::i()->db_arr_assoc($q);
}

function db_fetch_array($q)
{
	return is_object($q) ? $q->fetch(PDO::FETCH_ASSOC) : null;
}

function db_li($q, &$ef, $li=0)
{
	return Database::i()->db_li($q, $ef, $li);
}

function ins_m($tbl, $flds, $types, $vals)
{
	return Database::i()->ins_m($tbl, $flds, $types, $vals);
}

function db_all($q)
{
	return Database::i()->db_all($q);
}

function _esc($s)
{
	return Database::i()->_esc($s);
}

function ses_make_sysid()
{
	if ($GLOBALS['FUD_OPT_2'] & 256) {	// MULTI_HOST_LOGIN
		return;
	}

	$keys = array('REMOTE_USER', 'HTTP_USER_AGENT', 'SERVER_PROTOCOL', 'HTTP_ACCEPT_CHARSET', 'HTTP_ACCEPT_LANGUAGE');
	if ($GLOBALS['FUD_OPT_3'] & 16) {	// SESSION_IP_CHECK
		$keys[] = 'HTTP_X_FORWARDED_FOR';
		$keys[] = 'REMOTE_ADDR';
	}
	$pfx = '';
	foreach ($keys as $v) {
		if (isset($_SERVER[$v])) {
			$pfx .= $_SERVER[$v];
		}
	}
	return md5($pfx);
}

function ses_get($id=0)
{
	if (!$id) {
		/* Cookie or URL session? If not, check for known bots. */
		if (!empty($_COOKIE[$GLOBALS['COOKIE_NAME']])) {
			/* Have cookie */
			$q_opt = 's.ses_id='. _esc($_COOKIE[$GLOBALS['COOKIE_NAME']]);
		} else if ((isset($_GET['S']) || isset($_POST['S'])) && $GLOBALS['FUD_OPT_1'] & 128) {
			/* Have session string */
			$url_session = 1;
			$q_opt = 's.ses_id='. _esc((isset($_GET['S']) ? (string) $_GET['S'] : (string) $_POST['S']));
			/* Do not validate against expired URL sessions. */
			$q_opt .= ' AND s.time_sec > '. (__request_timestamp__ - $GLOBALS['SESSION_TIMEOUT']);
		} else {
			/* Unknown user, maybe bot? */
			// Auto login authorized bots.
			// To test: wget --user-agent="Googlebot 1.2" http://127.0.0.1:8080/forum
			$spider_session = 0;
			$my_ip = get_ip();

			include $GLOBALS['FORUM_SETTINGS_PATH'] .'spider_cache';
			foreach ($spider_cache as $spider_id => $spider) {
				if (preg_match('/'. $spider['useragent'] .'/i', $_SERVER['HTTP_USER_AGENT'])) {
					if (empty($spider['bot_ip'])) {
						$spider_session = 1;	// Agent matched, no IPs to check.
						break; 
					} else {
						foreach (explode(',', $spider['bot_ip']) as $bot_ip) {
							if (!($bot_ip = trim($bot_ip))) {
								continue;
							}
							if (strpos($bot_ip, $my_ip) === 0)	{
								$spider_session = 1;	// Agent and an IP matched.
								break;
							}
						}
					}
				}
			}
			if ($spider_session) {
				if ($spider['bot_opts'] & 2) {	// Access blocked.
					die('Go away!');
				}
				if ($id = db_li('INSERT INTO fud30_ses (ses_id, time_sec, sys_id, ip_addr, useragent, user_id) VALUES (\''. $spider['botname'] .'\', '. __request_timestamp__ .', '. _esc(ses_make_sysid()) .', '. _esc($my_ip) .', '. _esc(substr($_SERVER['HTTP_USER_AGENT'], 0, 64)) .', '. $spider['user_id'] .')', $ef, 1)) {
					$q_opt = 's.id='. $id;
				} else {
					$q_opt = 's.ses_id='. _esc($spider['botname']);
				}
				$GLOBALS['FUD_OPT_1'] ^= 128;	// Disable URL sessions for user.
			} else {
				/* NeXuS: What is this? Return if user unknown? Function should
				   return only after the query is run. */
				//return;
				
				// Check sys_id, ip_addr and useragent for a possible match
				$q_opt = 's.sys_id= '._esc(ses_make_sysid()).
				         ' AND s.ip_addr='._esc(get_ip()).
						 ' AND s.useragent='._esc(substr($_SERVER['HTTP_USER_AGENT'], 0, 64));
			}
		}

		/* ENABLE_REFERRER_CHECK */
		if ($GLOBALS['FUD_OPT_3'] & 4 && isset($_SERVER['HTTP_REFERER']) && strncmp($_SERVER['HTTP_REFERER'], $GLOBALS['WWW_ROOT'], strlen($GLOBALS['WWW_ROOT']))) {
			/* More checks, we need those because some proxies mangle referer field. */
			$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
			/* $p > 8 https:// or http:// */
			if (($p = strpos($_SERVER['HTTP_REFERER'], $host)) === false || $p > 8) {
				$q_opt .= ' AND s.user_id > 2000000000 ';	// Different referrer, force anonymous.
			}
		}
	} else {
		$q_opt = 's.id='. $id;
	}

	$u = db_sab('SELECT
		s.id AS sid, s.ses_id, s.data, s.returnto, s.sys_id,
		t.id AS theme_id, t.lang, t.name AS theme_name, t.locale, t.theme, t.pspell_lang, t.theme_opt,
		u.alias, u.posts_ppg, u.time_zone, u.sig, u.last_visit, u.last_read, u.cat_collapse_status, u.users_opt, u.posted_msg_count, u.topics_per_page,
		u.ignore_list, u.ignore_list, u.buddy_list, u.id, u.group_leader_list, u.email, u.login, u.sq, u.ban_expiry, u.ban_reason, u.flag_cc
	FROM fud30_ses s
		INNER JOIN fud30_users u ON u.id=(CASE WHEN s.user_id>2000000000 THEN 1 ELSE s.user_id END)
		LEFT OUTER JOIN fud30_themes t ON t.id=u.theme
	WHERE '. $q_opt);

	/* Anon user, no session or login. */
	if (!$u || $u->id == 1 || $id) {
		return $u;
	}

	if ($u->sys_id == ses_make_sysid()) {
		return $u;
	} else if ($GLOBALS['FUD_OPT_3'] & 16 || isset($url_session)) {
		/* URL sessions must validate sys_id check and SESSION_IP_CHECK must be disabled */
		return;
	}

	/* Try doing a strict SQ match in last-ditch effort to make things 'work'. */
	if (isset($_POST['SQ']) && $_POST['SQ'] == $u->sq) {
		return $u;
	}

	return;
}

/** Create an anonymous session. */
function ses_anon_make()
{
	do {
		$uid = 2000000000 + mt_rand(1, 147483647);
		$ses_id = md5($uid . __request_timestamp__ . getmypid());
	} while (!($id = db_li('INSERT INTO fud30_ses (ses_id, time_sec, sys_id, ip_addr, useragent, user_id) VALUES (\''. $ses_id .'\', '. __request_timestamp__ .', '. _esc(ses_make_sysid()) .', '. _esc(get_ip()) .', '. _esc(substr($_SERVER['HTTP_USER_AGENT'], 0, 64)) .', '. $uid .')', $ef, 1)));

	/* When we have an anon user, we set a special cookie allowing us to see who referred this user. */
	if (isset($_GET['rid']) && !isset($_COOKIE['frm_referer_id']) && $GLOBALS['FUD_OPT_2'] & 8192) {
		setcookie($GLOBALS['COOKIE_NAME'] .'_referer_id', $_GET['rid'], __request_timestamp__+31536000, $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
	}

	if ($GLOBALS['FUD_OPT_3'] & 1) {        // SESSION_COOKIES
		setcookie($GLOBALS['COOKIE_NAME'], $ses_id, 0,                                                $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
	} else {
		setcookie($GLOBALS['COOKIE_NAME'], $ses_id, __request_timestamp__+$GLOBALS['COOKIE_TIMEOUT'], $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
	}


	return ses_get($id);
}

/** Update session status to indicate last known action. */
function ses_update_status($ses_id, $str=null, $forum_id=0, $ret='')
{
	if (empty($ses_id)) {
		die('FATAL ERROR: No session, check your forum\'s URL and COOKIE settings.');
	}
	q('UPDATE fud30_ses SET sys_id=\''. ses_make_sysid() .'\', forum_id='. $forum_id .', time_sec='. __request_timestamp__ .', action='. ($str ? _esc($str) : 'NULL') .', returnto='. (!is_int($ret) ? (isset($_SERVER['QUERY_STRING']) ? _esc($_SERVER['QUERY_STRING']) : 'NULL') : 'returnto') .' WHERE id='. $ses_id);
}

/** Save/ clear a session variable. */
function ses_putvar($ses_id, $data)
{
	$cond = is_int($ses_id) ? 'id='. (int)$ses_id : 'ses_id=\''. $ses_id .'\'';

	if (empty($data)) {
		q('UPDATE fud30_ses SET data=NULL WHERE '. $cond);
	} else {
		q('UPDATE fud30_ses SET data='. _esc(serialize($data)) .' WHERE '. $cond);
	}
}

/** Destroy a session. */
function ses_delete($ses_id)
{
	// Delete all forum sessions.
	// Regardless of MULTI_HOST_LOGIN, all sessions will be terminated.
	q('DELETE FROM fud30_ses WHERE id='. $ses_id);
	setcookie($GLOBALS['COOKIE_NAME'], '', __request_timestamp__-100000, $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);

	return 1;
}

function ses_anonuser_auth($id, $error)
{
	if (!empty($_POST)) {
		$_SERVER['QUERY_STRING'] = '';
	}
	q('UPDATE fud30_ses SET data='. _esc(serialize($error)) .', returnto='. ssn($_SERVER['QUERY_STRING']) .' WHERE id='. $id);
	if ($GLOBALS['FUD_OPT_2'] & 32768) {	// USE_PATH_INFO
		header('Location: /index.php/l/'. _rsidl);
	} else {
		header('Location: /index.php?t=login&'. _rsidl);
	}
	exit;
}function &init_user()
{
	$o1 =& $GLOBALS['FUD_OPT_1'];
	$o2 =& $GLOBALS['FUD_OPT_2'];

	if ($o2 & 32768 && empty($_SERVER['PATH_INFO']) && !empty($_SERVER['ORIG_PATH_INFO'])) {
		$_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
	}

	/* We need to parse S & rid right away since they are used during user init. */
	if ($o2 & 32768 && !empty($_SERVER['PATH_INFO']) && empty($_GET['t'])) {	// USE_PATH_INFO
		$pb = $p = explode('/', trim($_SERVER['PATH_INFO'], '/'));
		if ($o1 & 128) {	// SESSION_USE_URL
			$_GET['S'] = array_pop($p);
		}
		if ($o2 & 8192) {	// TRACK_REFERRALS
			$_GET['rid'] = array_pop($p);
		}
		$_SERVER['QUERY_STRING'] = htmlspecialchars($_SERVER['PATH_INFO']) .'?'. $_SERVER['QUERY_STRING'];

		/* Default to index page. */
		if (!isset($p[0])) {
			$p[0] = 'i';
		}
		/* Notice prevention code. */
		for ($i = 1; $i < 5; $i++) {
			if (!isset($p[$i])) {
				$p[$i] = null;
			}
		}

		switch ($p[0]) {
			case 'm': /* goto specific message */
				$_GET['t'] = 0;
				$_GET['goto'] = $p[1];
				if (isset($p[2])) {
					$_GET['th'] = $p[2];
					if (isset($p[3]) && is_numeric($p[3])) {
						$_GET['start'] = $p[3];
						if ($p[3]) {
							$_GET['t'] = 'msg';
							unset($_GET['goto']);
						}

						if (isset($p[4])) {
							if ($p[4] === 'prevloaded') {
								$_GET['prevloaded'] = 1;
								$i = 5;
							} else {
								$i = 4;
							}

							if (isset($p[$i])) {
								$_GET['rev'] = $p[$i];
								if (isset($p[$i+1])) {
									$_GET['reveal'] = $p[$i+1];
								}
							}
						}
					}
				}
				break;

			case 't': /* view thread */
				$_GET['t'] = 0;
				$_GET['th'] = $p[1];
				if (isset($p[2]) && is_numeric($p[2])) {
					// START is not currently used for thread paging.
					// Set to 0, but keep code for possible future implementation.
					// $_GET['start'] = $p[2];
					$_GET['start'] = 0;
					if (!empty($p[3])) {
						$_GET[$p[3]] = 1;
					}
				}
				break;

			case 'f': /* view forum */
				$_GET['t'] = 1;
				$_GET['frm_id'] = $p[1];
				if (isset($p[2])) {
					$_GET['start'] = $p[2];
					if (isset($p[3])) {
						if ($p[3] === '0') {
							$_GET['sub'] = 1;
						} else {
							$_GET['unsub'] = 1;
						}
					}
				}
				break;

			case 'r':
				$_GET['t'] = 'post';
				$_GET[$p[1]] = $p[2];
				if (isset($p[3])) {
					$_GET['reply_to'] = $p[3];
					if (isset($p[4])) {
						if ($p[4]) {
							$_GET['quote'] = 'true';
						}
						if (isset($p[5])) {
							$_GET['start'] = $p[5];
						}
					}
				}
				break;

			case 'u': /* view user's info */
				$_GET['t'] = 'usrinfo';
				$_GET['id'] = $p[1];
				break;

			case 'i':
				$_GET['t'] = 'index';
				if (isset($p[1])) {
					$_GET['cat'] = (int) $p[1];
				}
				break;

			case 'fa':
				$_GET['t'] = 'getfile';
				$_GET['id'] = isset($p[1]) ? $p[1] : $pb[1];
				if (!empty($p[2])) {
					$_GET['private'] = 1;
				}
				break;

			case 'sp': /* show posts */
				$_GET['t'] = 'showposts';
				$_GET['id'] = $p[1];
				if (isset($p[2])) {
					$_GET['so'] = $p[2];
					if (isset($p[3])) {
						$_GET['start'] = $p[3];
					}
				}
				break;

			case 'l': /* login/logout */
				$_GET['t'] = 'login';
				if (isset($p[1])) {
					$_GET['logout'] = 1;
				}
				break;

			case 'e':
				$_GET['t'] = 'error';
				break;

			case 'st':
				$_GET['t'] = $p[1];
				$_GET['th'] = $p[2];
				$_GET['notify'] = $p[3];
				$_GET['opt'] = $p[4] ? 'on' : 'off';
				if (isset($p[5])) {
					$_GET['start'] = $p[5];
				}
				break;

			case 'sf':
				$_GET['t'] = $p[1];
				$_GET['frm_id'] = $p[2];
				$_GET[$p[3]] = 1;
				$_GET['start'] = $p[4];
				break;

			case 'sl': /* subscribed topic list */
				$_GET['t'] = 'subscribed';
				if ($p[1] == 'start') {
					$_GET['start'] = $p[2];
				} else {
					if (isset($p[2])) {
						$_GET['th'] = $p[2];
					} else if (isset($p[1])) {
						$_GET['frm_id'] = $p[1];
					}
				}
				break;

			case 'bml': /* bookmark list */
				$_GET['t'] = 'bookmarked';
				if ($p[1] == 'start') {
					$_GET['start'] = $p[2];
				} else {
					if (isset($p[2])) {
						$_GET['th'] = $p[2];
					}
				}
				break;

			case 'pmm':
				$_GET['t'] = 'ppost';
				if (isset($p[1], $p[2])) {
					$_GET[$p[1]] = $p[2];
					if (isset($p[3])) {
						$_GET['rmid'] = $p[3];
					}
				}
				break;

			case 'pmv':
				$_GET['t'] = 'pmsg_view';
				$_GET['id'] = $p[1];
				if (isset($p[2])) {
					$_GET['dr'] = 1;
				}
				break;

			case 'pdm':
				$_GET['t'] = 'pmsg';
				if (isset($p[1])) {
					if ($p[1] !== 'btn_delete') {
						$_GET['folder_id'] = $p[1];
					} else {
						$_GET['btn_delete'] = 1;
						$_GET['sel'] = $p[2];
					}
					if (isset($p[3])) {
						$_GET['s'] = $p[3];
						$_GET['o'] = $p[4];
						$_GET['start'] = $p[5];
					}
				}
				break;

			case 'pl': /* poll list */
				$_GET['t'] = 'polllist';
				if (isset($p[1])) {
					$_GET['uid'] = $p[1];
					if (isset($p[2])) {
						$_GET['start'] = $p[2];
						if (isset($p[3])) {
							$_GET['oby'] = $p[3];
						}
					}
				}
				break;

			case 'ml': /* member list */
				$_GET['t'] = 'finduser';
				if (isset($p[1])) {
					switch ($p[1]) {
						case 1: case 2: $_GET['pc'] = $p[1]; break;
						case 3: case 4: $_GET['us'] = $p[1]; break;
						case 5: case 6: $_GET['rd'] = $p[1]; break;
						case 7: case 8: $_GET['fl'] = $p[1]; break;
						case 9: case 10: $_GET['lv'] = $p[1]; break;
					}
					if (isset($p[2])) {
						$_GET['start'] = $p[2];
						if (isset($p[3])) {
							$_GET['usr_login'] = urldecode($p[3]);
							if (isset($p[4])) {
								$_GET['js_redr'] = $p[5];
							}
						}
					}
				}
				break;

			case 'h': /* help */
				$_GET['t'] = 'help_index';
				if (isset($p[1])) {
					$_GET['section'] = $p[1];
				}
				break;

			case 'cv': /* change thread view mode */
				$_GET['t'] = $p[1];
				$_GET['frm_id'] = $p[2];
				break;

			case 'mv': /* change message view mode */
				$_GET['t'] = $p[1];
				$_GET['th'] = $p[2];
				if (isset($p[3])) {
					if ($p[3] !== '0') {
						$_GET['goto'] = $p[3];
					} else {
						$_GET['prevloaded'] = 1;
						$_GET['start'] = $p[4];
						if (isset($p[5])) {
							$_GET['rev'] = $p[5];
							if (isset($p[6])) {
								$_GET['reveal'] = $p[6];
							}
						}
					}
				}
				break;

			case 'pv':
				$_GET['t'] = 0;
				if (isset($p[1])) {
					$_GET['goto'] = q_singleval('SELECT id FROM fud30_msg WHERE poll_id='.(int)$p[1]);
					$_GET['pl_view'] = empty($p[2]) ? 0 : (int)$p[2];
				}
				break;

			case 'rm': /* report message */
				$_GET['t'] = 'report';
				$_GET['msg_id'] = $p[1];
				break;

			case 'rl': /* list of reported messages */
				$_GET['t'] = 'reported';
				if (isset($p[1])) {
					$_GET['del'] = $p[1];
				}
				break;

			case 'd': /* delete thread/message */
				$_GET['t'] = 'mmod';
				$_GET['del'] = $p[1];
				if (isset($p[2])) {
					$_GET['th'] = $p[2];
				}
				break;

			case 'em': /* email forum member */
				$_GET['t'] = 'email';
				$_GET['toi'] = $p[1];
				break;

			case 'mar': /* mark all/forum read */
				$_GET['t'] = 'markread';
				if (isset($p[1])) {
					$_GET['id'] = $p[1];
					if (isset($p[2])) {
						$_GET['cat'] = $p[2];
					}
				}
				break;

			case 'bl': /* buddy list */
				$_GET['t'] = 'buddy_list';
				if (isset($p[1])) {
					if (!empty($p[2])) {
						$_GET['add'] = $p[1];
					} else {
						$_GET['del'] = $p[1];
					}
					if (isset($p[3])) {
						$_GET['redr'] = 1;
					}
				}
				break;

			case 'il': /* ignore list */
				$_GET['t'] = 'ignore_list';
				if (isset($p[1])) {
					if (!empty($p[2])) {
						$_GET['add'] = $p[1];
					} else {
						$_GET['del'] = $p[1];
					}
					if (isset($p[3])) {
						$_GET['redr'] = 1;
					}
				}
				break;

			case 'lk': /* lock/unlock thread */
				$_GET['t'] = 'mmod';
				$_GET['th'] = $p[1];
				$_GET[$p[2]] = 1;
				break;

			case 'stt': /* split thread */
				$_GET['t'] = 'split_th';
				if (isset($p[1])) {
					$_GET['th'] = $p[1];
				}
				break;

			case 'ef': /* email to friend */
				$_GET['t'] = 'remail';
				$_GET['th'] = $p[1];
				break;

			case 'lr': /* list referers */
				$_GET['t'] = 'list_referers';
				if (isset($p[1])) {
					$_GET['start'] = $p[1];
				}
				break;

			case 'a':
				$_GET['t'] = 'actions';
				if (isset($p[1], $p[2])) {
					$_GET['o'] = $p[1];
					$_GET['s'] = $p[2];
				}
				break;

			case 's':
				$_GET['t'] = 'search';
				if (isset($p[1])) {
					$_GET['srch'] = urldecode($p[1]);
					$_GET['field'] = isset($p[2]) ? $p[2] : '';
					$_GET['search_logic'] = isset($p[3]) ? $p[3] : '';
					$_GET['sort_order'] = isset($p[4]) ? $p[4] : '';
					$_GET['forum_limiter'] = isset($p[5]) ? $p[5] : '';
					$_GET['start'] = isset($p[6]) ? $p[6] : '';
					$_GET['author'] = isset($p[7]) ? $p[7] : '';
				}
				break;

			case 'p':
				if (!is_numeric($p[1])) {
					$_GET[$p[1]] = $p[2];
				} else {
					$_GET['frm']  = $p[1];
					$_GET['page'] = $p[2];
				}
				break;

			case 'ot':
				$_GET['t'] = 'online_today';
				if (isset($p[1], $p[2])) {
					$_GET['o'] = $p[1];
					$_GET['s'] = $p[2];
				}
				break;

			case 're':
				$_GET['t'] = 'register';
				if (isset($p[1])) {
					$_GET['reg_coppa'] = $p[1];
				}
				break;

			case 'tt':
				$_GET['t'] = $p[1];
				$_GET['frm_id'] = $p[2];
				break;

			case 'mh':
				$_GET['t'] = 'mvthread';
				$_GET['th'] = $p[1];
				if (isset($p[2], $p[3])) {
					$_GET[$p[2]] = $p[3];
				}
				break;

			case 'mn':
				$_GET['t'] = $p[1];
				$_GET['th'] = $p[2];
				$_GET['notify'] = $p[3];
				$_GET['opt'] = $p[4];
				if (isset($p[5])) {
					if ($p[1] == 'msg') {
						$_GET['start'] = $p[5];
					} else {
						$_GET['mid'] = $p[5];
					}
				}
				break;

			case 'bm': /* bookmark/unbookmark a topic */
				$_GET['t'] = $p[1];
				$_GET['th'] = $p[2];
				$_GET['bookmark'] = $p[3];
				$_GET['opt'] = $p[4];
				if (isset($p[5])) {
					if ($p[1] == 'msg') {
						$_GET['start'] = $p[5];
					} else {
						$_GET['mid'] = $p[5];
					}
				}
				break;

			case 'tr':
				$_GET['t'] = 'ratethread';
				break;

			case 'gm':
				$_GET['t'] = 'groupmgr';
				if (isset($p[1], $p[2], $p[3])) {
					$_GET[$p[1]] = $p[2];
					$_GET['group_id'] = $p[3];
				}
				break;

			case 'te':
				$_GET['t'] = 'thr_exch';
				if (isset($p[1], $p[2])) {
					$_GET[$p[1]] = $p[2];
				}
				break;

			case 'mq':
				$_GET['t'] = 'modque';
				if (isset($p[1], $p[2])) {
					$_GET[$p[1]] = $p[2];
				}
				break;

			case 'pr':
				$_GET['t'] = 'pre_reg';
				$_GET['coppa'] = $p[1];
				break;

			case 'qb':
				$_GET['t'] = 'qbud';
				break;

			case 'po':
				$_GET['t'] = 'poll';
				$_GET['frm_id'] = $p[1];
				if (isset($p[2])) {
					$_GET['pl_id'] = $p[2];
					if (isset($p[3], $p[4])) {
						$_GET[$p[3]] = $p[4];
					}
				}
				break;

			case 'sm':
				$_GET['t'] = 'smladd';
				break;

			case 'mk':
				$_GET['t'] = 'mklist';
				$_GET['tp'] = $p[1];
				break;

			case 'rp':
				$_GET['t'] = 'rpasswd';
				break;

			case 'as':
				$_GET['t'] = 'avatarsel';
				break;

			case 'sel':
				$_GET['t'] = 'selmsg';
				$c = count($p) - 1;
				if ($c % 2) {
					--$c;
				}
				$c /= 2;
				$i = 0;
				while ($c--) {
					$_GET[$p[++$i]] = $p[++$i];
				}
				break;

			case 'pml':
				$_GET['t'] = 'pmuserloc';
				$_GET['js_redr'] = $p[1];
				if (isset($p[2])) {
					$_GET['overwrite'] = 1;
				}
				break;

			case 'rst':
				$_GET['t'] = 'reset';
				if (isset($p[1])) {
					$_GET['email'] = urldecode($p[1]);
				}
				break;

			case 'cpf':
				$_GET['t'] = 'coppa_fax';
				break;

			case 'cp':
				$_GET['t'] = 'coppa';
				break;

			case 'rc':
				$_GET['t'] = 'reg_conf';
				break;

			case 'ma':
				$_GET['t'] = 'mnav';
				if (isset($p[1])) {
					$_GET['rng'] = isset($p[1]) ? $p[1] : 0;
					$_GET['rng2'] = isset($p[2]) ? $p[2] : 0;
					$_GET['u'] = isset($p[3]) ? $p[3] : 0;
					$_GET['start'] = isset($p[4]) ? $p[4] : 0;
					$_GET['sub'] = !empty($p[5]);
				}
				break;

			case 'ip':
				$_GET['t'] = 'ip';
				if (isset($p[1])) {
					$_GET[($p[1][0] == 'i' ? 'ip' : 'user')] = isset($p[2]) ? $p[2] : '';
				}
				break;

			case 'met':
				$_GET['t'] = 'merge_th';
				if (isset($p[1])) {
					$_GET['frm_id'] = $p[1];
				}
				break;

			case 'uc':
				$_GET['t'] = 'uc';
				if (isset($p[1], $p[2])) {
					$_GET[$p[1]] = $p[2];
				}
				break;

			case 'mmd':
				$_GET['t'] = 'mmd';
				break;

			case 'cal':	/* Calendar */
				$_GET['t'] = 'calendar';
				break;

			case 'blog':	/* Blog */
				$_GET['t'] = 'blog';
				if ($p[1] == 'u' && isset($p[2])) {
					$_GET['user'] = $p[2];
					$_GET['start'] = isset($p[3]) ? $p[3] : 0;
				}
				if ($p[1] == 'f' && isset($p[2])) {
					$_GET['forum'] = $p[2];
					$_GET['start'] = isset($p[3]) ? $p[3] : 0;
				} else {
					$_GET['start'] = $p[1];
				}
				break;

			case 'page':	/* Static page */
				$_GET['t'] = 'page';
				if (isset($p[1])) {
					$_GET['id'] = $p[1];
				}
				break;

			default:
				// Page not found, redirect to front page.
				http_response_code(404);
				$_GET['t'] = 'index';
				break;
		}
		$GLOBALS['t'] = $_GET['t'];
	} else if (isset($_GET['t'])) {
		$GLOBALS['t'] = (string) $_GET['t'];
	} else if (isset($_POST['t'])) {
		$GLOBALS['t'] = (string) $_POST['t'];
	} else {
		$GLOBALS['t'] = 'index';
	}

	if ($GLOBALS['t'] == 'register') {
		$GLOBALS['THREADS_PER_PAGE_F'] = $GLOBALS['THREADS_PER_PAGE']; // Store old value.
	}

	header('P3P: CP="ALL CUR OUR IND UNI ONL INT CNT STA"'); /* P3P Policy. */

	$sq = 0;
	/* Fetch an object with the user's session, profile & theme info. */
	if (!($u = ses_get()) && defined('plugins')) {
		/* Call auto-login plugins. */
		$u = plugin_call_hook('AUTO_LOGIN');
	}

	if (!$u) {
		/* New anon user. */
		$u = ses_anon_make();
	} else if ($u->id != 1 && (!$GLOBALS['is_post'] || sq_check(1, $u->sq, $u->id, $u->ses_id))) {
		/* Store the last visit date for registered user. */
		q('UPDATE fud30_users SET last_visit='. __request_timestamp__ .' WHERE id='. $u->id);
		if ($GLOBALS['FUD_OPT_3'] & 1) {	// SESSION_COOKIES
			setcookie($GLOBALS['COOKIE_NAME'], $u->ses_id, 0, $GLOBALS['COOKIE_PATH'], $GLOBALS['COOKIE_DOMAIN']);
		}
		if (!$u->sq || __request_timestamp__ - $u->last_visit > 180) {	// 3 min.
			$u->sq = $sq = regen_sq($u->id);
			if (!$GLOBALS['is_post']) {
				$_GET['SQ'] = $sq;
			} else {
				$_POST['SQ'] = $sq;
			}
		} else {
			$sq =& $u->sq;
		}
	}

	// Prevent spiders from doing funny stuff.
	if (($u->users_opt & 1073741824) && $GLOBALS['is_post']) {	// is_spider
		die('Bad bot!');
	}

	/* Disable caching for registered users and POST requests. */
	if ($GLOBALS['is_post'] || $u->id > 1) {
		header('Cache-Control: no-store, private, must-revalidate, proxy-revalidate, post-check=0, pre-check=0, max-age=0, s-maxage=0');
		header('Expires: Mon, 21 Jan 1980 06:01:01 GMT');
		header('Pragma: no-cache');
	}

	if ($u->data) {
		$u->data = unserialize($u->data);
	}
	$uo = $u->users_opt = (int)$u->users_opt;

	/* This should allow path_info & normal themes to work properly within 1 forum. */
	if ($o2 & 32768 && !($u->theme_opt & 4)) {
		$o2 ^= 32768;
	}

	/* Handle PM disabling for users. */
	if (!($GLOBALS['is_a'] = $uo & 1048576) && $uo & 33554432) {
		$o1 = $o1 &~ 1024;
	}

	/* Set timezone. */
	if (empty($u->time_zone) || @date_default_timezone_set($u->time_zone) === FALSE) {
		date_default_timezone_set($GLOBALS['SERVER_TZ']);
	}

	/* Set locale. */
	$GLOBALS['good_locale'] = setlocale(LC_ALL, $u->locale);

	/* Call inituser plugins. */
	if (defined('plugins')) {
		plugin_call_hook('INITUSER', $u);
	}

	/* View format for threads & messages. */
	define('d_thread_view', $uo & 256 ? 'msg' : 'tree');
	define('t_thread_view', $uo & 128 ? 'thread' : 'threadt');
	if ($GLOBALS['t'] === 0) {
		$GLOBALS['t'] = $_GET['t'] = d_thread_view;
	} else if ($GLOBALS['t'] === 1) {
		$GLOBALS['t'] = $_GET['t'] = t_thread_view;
	}

	/* Define theme path, may already be set by a plugin. */
	defined('fud_theme') or define('fud_theme', 'theme/'. ($u->theme_name ? $u->theme_name : 'default') .'/');

	/* Define _uid, which, will tell us if this is a 'real' user or not. */
	define('__fud_real_user__', ($u->id != 1 ? $u->id : 0));
	define('_uid', __fud_real_user__ && ($uo & 131072) && !($uo & 2097152) ? $u->id : 0);

	/* Allow user to set their own topics per page value, as long as it is smaller then the max. */
	if (__fud_real_user__ && $GLOBALS['THREADS_PER_PAGE'] > $u->topics_per_page) {
		$GLOBALS['THREADS_PER_PAGE'] = (int) $u->topics_per_page;
	}

	$GLOBALS['sq'] = $sq;

	/* Define constants used to track URL sessions & referrals. */
	if ($o1 & 128) {
		define('s', $u->ses_id); define('_hs', '<input type="hidden" name="S" value="'. s .'" /><input type="hidden" name="SQ" value="'. $sq .'" />');
		if ($o2 & 8192) {
			if ($o2 & 32768) {
				define('_rsid', __fud_real_user__ .'/'. s .'/');
			} else {
				define('_rsid', 'rid='. __fud_real_user__ .'&amp;S='. s);
			}
		} else {
			if ($o2 & 32768) {
				define('_rsid', s .'/');
			} else {
				define('_rsid', 'S='. s);
			}
		}
	} else {
		define('s', ''); define('_hs', '<input type="hidden" name="SQ" value="'. $sq .'" />');
		if ($o2 & 8192) {
			if ($o2 & 32768) {
				define('_rsid', __fud_real_user__ .'/');
			} else {
				define('_rsid', 'rid='. __fud_real_user__);
			}
		} else {
			define('_rsid', '');
		}
	}
	define('_rsidl', ($o2 & 32768 ? _rsid : str_replace('&amp;', '&', _rsid)));

	return $u;
}

function user_register_forum_view($frm_id)
{
	if (__dbtype__ == 'mysql') {	// MySQL optimization.
		q('INSERT INTO fud30_forum_read (forum_id, user_id, last_view) VALUES ('. $frm_id .', '. _uid .', '. __request_timestamp__ .') ON DUPLICATE KEY UPDATE last_view=VALUES(last_view)');
		return;
	}
	
	if (!db_li('INSERT INTO fud30_forum_read (forum_id, user_id, last_view) VALUES ('. $frm_id .', '. _uid .', '. __request_timestamp__ .')', $ef)) {
		q('UPDATE fud30_forum_read SET last_view='. __request_timestamp__ .' WHERE forum_id='. $frm_id .' AND user_id='. _uid);
	}
}

function user_register_thread_view($thread_id, $tm=__request_timestamp__, $msg_id=0)
{
	if (__dbtype__ == 'mysql') {    // MySQL optimization.
		q('INSERT INTO fud30_read (last_view, msg_id, thread_id, user_id) VALUES('. $tm .', '. $msg_id .', '. $thread_id .', '. _uid .') ON DUPLICATE KEY UPDATE last_view=VALUES(last_view), msg_id=VALUES(msg_id)');
		return;
	}

	if (!db_li('INSERT INTO fud30_read (last_view, msg_id, thread_id, user_id) VALUES('. $tm .', '. $msg_id .', '. $thread_id .', '. _uid .')', $ef)) {
		q('UPDATE fud30_read SET last_view='. $tm .', msg_id='. $msg_id .' WHERE thread_id='. $thread_id .' AND user_id='. _uid);
	}
}

function user_set_post_count($uid)
{
	$pd = db_saq('SELECT MAX(id), count(*) FROM fud30_msg WHERE poster_id='. $uid .' AND apr=1');
	$level_id = (int) q_singleval(q_limit('SELECT id FROM fud30_level WHERE post_count <= '. $pd[1] .' ORDER BY post_count DESC', 1));
	q('UPDATE fud30_users SET u_last_post_id='. (int)$pd[0] .', posted_msg_count='. (int)$pd[1] .', level_id='. $level_id .' WHERE id='. $uid);
}

function user_mark_all_read($id)
{
	q('UPDATE fud30_users SET last_read='. __request_timestamp__ .' WHERE id='. $id);
	q('DELETE FROM fud30_read WHERE user_id='. $id);
	q('DELETE FROM fud30_forum_read WHERE user_id='. $id);
}

function user_mark_forum_read($id, $fid, $last_view)
{
	if (__dbtype__ == 'mysql') {	// MySQL optimization.
		q('INSERT INTO fud30_read (user_id, thread_id, msg_id, last_view) SELECT '. $id .', id, last_post_id, '. __request_timestamp__ .' FROM fud30_thread WHERE forum_id='. $fid .' AND last_post_date > '. $last_view .' ON DUPLICATE KEY UPDATE last_view=VALUES(last_view), msg_id=VALUES(msg_id)');
	} else if (__dbtype__ == 'sqlite') {	// SQLite optimization.
		q('REPLACE INTO fud30_read (user_id, thread_id, msg_id, last_view) SELECT '. $id .', id, last_post_id, '. __request_timestamp__ .' FROM fud30_thread WHERE forum_id='. $fid .' AND last_post_date > '. $last_view);
	} else {	// Other databases.
		if (!db_li('INSERT INTO fud30_read (user_id, thread_id, msg_id, last_view) SELECT '. $id .', id, last_post_id, '. __request_timestamp__ .' FROM fud30_thread WHERE forum_id='. $fid .' AND last_post_date > '. $last_view, $ef)) {
			q('UPDATE fud30_read SET user_id='. $id .', msg_id=t.last_post_id, last_view='. __request_timestamp__ .' FROM (SELECT id, last_post_id FROM fud30_thread WHERE forum_id='. $fid .' AND last_post_date > '. $last_view .') t WHERE user_id='. $id .' AND thread_id=t.id');
		}
	}
	user_register_forum_view($fid);
}

function sq_check($post, &$sq, $uid=__fud_real_user__, $ses=s)
{
	/* No sequence # check for anonymous users. */
	if (!$uid) {
		return 1;
	}

	if ($post && isset($_POST['SQ'])) {
		$s = $_POST['SQ'];
	} else if (!$post && isset($_GET['SQ'])) {
		$s = $_GET['SQ'];
	} else {
		$s = 0;
	}

	if ($sq !== $s) {
		if ($GLOBALS['t'] == 'post' || $GLOBALS['t'] == 'ppost') {
			define('fud_bad_sq', 1);
			$sq = regen_sq($uid);
			return 1;
		}
		header('Location: /index.php?S='. $ses);
		exit;
	}

	return 1;
}

function regen_sq($uid=__fud_real_user__)
{
	$sq = md5(get_random_value(128));
	q('UPDATE fud30_users SET sq=\''. $sq .'\' WHERE id='. $uid);
	return $sq;
}

if (isset($_SERVER['REMOTE_ADDR']) && !defined('no_session')) {
	$GLOBALS['usr'] = init_user();
}


	fud_use('err.inc');

	/* BUST_A_PUNK enabled. */
	if ($FUD_OPT_2 & 524288 && isset($_COOKIE[$COOKIE_NAME .'1']) && $t != 'error') {
		fud_use('errmsg.inc');
		exit_user_banned();
	}

	/* Check PHP_COMPRESSION_ENABLE. */
	if ($FUD_OPT_2 & 16384 && $t != 'getfile') {
		ini_set('zlib.output_compression_level', (int)$PHP_COMPRESSION_LEVEL);
		ob_start('ob_gzhandler');
	}

	if ($t == 'rview') {
		if (isset($_GET['th']) || isset($_GET['goto'])) {
			$t = $_GET['t'] = d_thread_view;
		} else if (isset($_GET['frm_id'])) {
			$t = $_GET['t'] = t_thread_view;
		} else {
			$t = $_GET['t'] = 'index';
		}
	} else if (preg_match('/[^a-z_]/', $t) || !@file_exists($WWW_ROOT_DISK . fud_theme . $t .'.php')) {
		// Page not found, redirect to front page.
		http_response_code(404);
		$t = 'index';
	}

	/* Call themed template. */
	if (defined('plugins')) {
		$t = plugin_call_hook('PRE_TEMPLATE', $t);
		if (isset($plugin_hooks['POST_TEMPLATE'])) {
			ob_start();	// Start capturing output for POST_TEMPLATE plugins.
		}
	}
	$variables = [];
	require($WWW_ROOT_DISK . fud_theme .'language.inc');	// Initialize theme's language helper functions.
#### Old Template System ###############################################################################################
//require($WWW_ROOT_DISK . fud_theme . $t .'.php');
#### New Template System ###############################################################################################
$renderer = new TwigRenderer();
$renderer->IS_ADMIN = $is_a;
$renderer->usr = $usr;
$renderer->_hs = _hs;
if (isset($frm)) {
    $renderer->forum_id = (isset($frm->forum_id)) ? $frm->forum_id : null;
}
require($WWW_ROOT_DISK . fud_theme . $t .'.php');
$renderer->RIGHT_SIDEBAR = isset($RIGHT_SIDEBAR) ? $RIGHT_SIDEBAR : null;
$renderer->render($t, $variables);
#### End New Template System ###########################################################################################

	if (defined('plugins') && isset($plugin_hooks['POST_TEMPLATE'])) {
		$template_data = ob_get_contents();
		ob_end_clean();
		echo plugin_call_hook('POST_TEMPLATE', $template_data);
	}

	/* Housekeeping. */
	while (ob_get_level() > 0) ob_end_flush();	// Flush all output to browser.
	switch ($t) {
		case 'msg':
			if (!isset($_GET['prevloaded'])) {
				th_inc_view_count($frm->id);
			}
			if (_uid && $obj2) {
				if ($frm->last_forum_view < $obj2->post_stamp) {
					user_register_forum_view($frm->forum_id);
				}
				if ($frm->last_view < $obj2->post_stamp) {
					user_register_thread_view($frm->id, $obj2->post_stamp, $obj2->id);
				}
			}
			break;
		case 'tree':
			if (_uid && $msg_obj) {
				th_inc_view_count($msg_obj->thread_id);
				if ($frm->last_forum_view < $msg_obj->post_stamp) {
					user_register_forum_view($msg_obj->forum_id);
				}
				if ($frm->last_view < $msg_obj->post_stamp) {
					user_register_thread_view($msg_obj->thread_id, $msg_obj->post_stamp, $msg_obj->id);
				}
			}
			break;
		case 'login':
			/* Clear expired sessions AND anonymous sessions older than 1 day. */
			q('DELETE FROM fud30_ses WHERE time_sec<'. (__request_timestamp__- ($FUD_OPT_3 & 1 ? $SESSION_TIMEOUT : $COOKIE_TIMEOUT)) .' OR (user_id>2000000000 AND time_sec<'. (__request_timestamp__- 86400) .')');
			break;
		case 'thread':
		case 'threadt':
			if (_uid) {
				user_register_forum_view($frm_id);
			}
			break;
	}
	//var_dump($GLOBALS);

?>
