<?php
/**
* copyright            : (C) 2001-2020 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: getfile.php.t 6357 2020-01-28 21:00:54Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
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
}


function get_preview_img($id)
{
	return db_saq('SELECT mm.mime_hdr, a.original_name, a.location, 0, 0, 0, a.fsize FROM fud30_attach a LEFT JOIN fud30_mime mm ON mm.id=a.mime_type WHERE a.message_id=0 AND a.id='. $id);
}

/* main */
	if (!isset($_GET['id']) || !($id = (int)$_GET['id'])) {
		// Previously: invl_inp_err();
		header('HTTP/1.0 400 Bad Request', true, 400);
		echo 'Bad Request';
		exit;
	}
	if (empty($_GET['private'])) { /* Non-private upload. */
		$r = db_saq('SELECT mm.mime_hdr, a.original_name, a.location, m.id, mo.id,
			'. q_bitand(_uid ? 'COALESCE(g2.group_cache_opt, g1.group_cache_opt)' : '(g1.group_cache_opt)', 2) .',
			a.fsize
			FROM fud30_attach a
			INNER JOIN fud30_msg m ON a.message_id=m.id AND a.attach_opt=0
			INNER JOIN fud30_thread t ON m.thread_id=t.id
			INNER JOIN fud30_group_cache g1 ON g1.user_id='. (_uid ? 2147483647 : 0) .' AND g1.resource_id=t.forum_id
			LEFT JOIN fud30_mod mo ON mo.forum_id=t.forum_id AND mo.user_id='. _uid .'
			LEFT JOIN fud30_mime mm ON mm.id=a.mime_type
			'. (_uid ? 'LEFT JOIN fud30_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=t.forum_id' : '') .'
			WHERE a.id='. $id);
		if (!$r) {
			if (!($r = get_preview_img($id))) {
				// Previously: invl_inp_err();
				header('HTTP/1.0 404 Not Found', true, 404);
				echo 'Not Found';
				exit;
			}
		} else if (!$is_a && !$r[4] && !$r[5]) {
			// Previously: std_error('access');
			header('HTTP/1.0 401 Unauthorized', true, 401);
			echo 'Unauthorized';
			exit;
		}
	} else {
		$r = db_saq('SELECT mm.mime_hdr, a.original_name, a.location, pm.id, a.owner, a.fsize
			FROM fud30_attach a
			INNER JOIN fud30_pmsg pm ON a.message_id=pm.id AND a.attach_opt=1
			LEFT JOIN fud30_mime mm ON mm.id=a.mime_type
			WHERE a.attach_opt=1 AND a.id='. $id);
		if (!$r) {
			if (!($r = get_preview_img($id))) {
				// Previously: invl_inp_err();
				header('HTTP/1.0 404 Not Found', true, 404);
				echo 'Not Found';
				exit;
			}
		} else if (!$is_a && $r[4] != _uid) {
			// Previously: std_error('access');
			header('HTTP/1.0 401 Unauthorized', true, 401);
			echo 'Unauthorized';
			exit;
		}
	}

	// DWLND_REF_CHK
	$WWW_ROOT = preg_replace('#^\/\/|^https?:\/\/|www\.#', '', $WWW_ROOT);	// Remove www, \\ and http/https before referer checking.
	if ($FUD_OPT_2 & 4194304 && !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $WWW_ROOT) === false) {
		header('HTTP/1.0 403 Forbidden', true, 403);
		echo 'Forbidden - bad referer';
		exit;
	}

	$r[1] = reverse_fmt($r[1]);
	if (!$r[2]) {	// Empty location means we are previewing a message with an attachment.
		$r[2] = $GLOBALS['FILE_STORE'] . $id .'.atch';
	}

	if (!strncmp($r[0], 'image/', 6)) {
		$s = getimagesize($r[2]);
		$r[0] = $s['mime'];
	}

	if (!$r[0]) {
		$r[0] = 'application/octet-stream';
		$append = 'attachment; ';
	} else if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') && preg_match('!^(?:audio|video|image)/!i', $r[0])) {
		$append = 'inline; ';
	} else if (strncmp($r[0], 'image/', 6)) {
		$append = 'attachment; ';
	} else {
		$append = '';
	}

	/* If we encounter a compressed file and PHP's output compression is enabled do not
	 * try to compress images & already compressed files. */
	if ($FUD_OPT_2 & 16384 && $append) {
		$comp_ext = array('zip', 'gz', 'rar', 'tgz', 'bz2', 'tar');
		$ext = strtolower(substr(strrchr($r[1], '.'), 1));
		if (!in_array($ext, $comp_ext)) {
			ob_start('ob_gzhandler', (int)$PHP_COMPRESSION_LEVEL);
		}
	}

	/* This is a hack for IE browsers when working on HTTPs.
	 * The no-cache headers appear to cause problems as indicated by the following
	 * MS advisories:
	 *	http://support.microsoft.com/?kbid=812935
	 *	http://support.microsoft.com/default.aspx?scid=kb;en-us;316431
	 */
	if ($_SERVER['SERVER_PORT'] == '443' && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0', 1);
		header('Pragma: public', 1);
	} else if (__fud_cache(filemtime($r[2]))) {
		return;
	}

	header('Content-Type: '. $r[0]);
	header('Content-Disposition: '. $append .'filename="'. urlencode($r[1]) .'"');
	header('Content-Length: '. array_pop($r));
	header('Connection: close');

	// Increment counter and disconnect to prevent long opens while data is transferred.
	attach_inc_dl_count($id, $r[3]);
	db_close();

	// Spool file to browser.
	@ob_end_flush();	// Output buffering may cause memory issues with large files.
	@readfile($r[2]);
?>
