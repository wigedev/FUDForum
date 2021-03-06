<?php
/**
* copyright            : (C) 2001-2018 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: nntp.inc 6138 2018-04-07 08:01:08Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

class fud_nntp
{
	var $server, $port=119, $user, $pass, $timeout=25, $newsgroup;
	var $error, $lrc;
	var $fs;
	var $group_article_cnt, $group_first, $group_last, $nntp_opt, $custom_sig;

	// Per message variables.
	var $raw_msg;
	var $attachments = array();

	/** Read and return the NNTP return code. */
	function get_return_code()
	{
		$this->lrc = fgets($this->fs, 1024);
		return trim(substr($this->lrc, 0, strpos($this->lrc, ' ')));
	}

	/** Check for matching NNTP return code. */
	function compare_return_codes($code, $str='')
	{
		if (empty($str)) {
			$str = $this->get_return_code();
		}

		return ($str == $code);
	}

	/** Log in to NNTP server with supplied username and password. */
	function auth_cmd()
	{
		if ($this-> nntp_opt & 128) {	// Auth (ORIGINAL/SIMPLE).
			switch ($this->cmd('AUTHINFO user '. $this->user, false)) {
				case 281:
					return true;
					break;
				case 381:
					if ($this->cmd('AUTHINFO pass '. $this->pass, false) != 281) {
						$this->error = "Authentication failed\n";
						return false;
					} else {
						return true;
					}
					break;
				default:
					return false;
					break;
			}
		} else if (!($this->nntp_opt & 64)) {	// Auth NONE.
			if ($this->cmd('AUTHINFO SIMPLE', false) != 350) {
				$this->error = "Authentication failed\n";
				return false;
			} else {
				if ($this->cmd($this->user .' '. $this->pass, false) == 250) {
					return true;
				} else {
					return false;
				}
			}
		} else {
			$this->error = "NNTP Authentication required, but no authentication method specified\n";
			return false;
		}

		return false;
	}

	/** Send NNTP command to the server and authenticate if required. */
	function cmd($cmd, $auth=true)
	{
		fputs($this->fs, $cmd ."\r\n");
		$code = $this->get_return_code();

		// Reissue command if authentication is required. Check for:
		// - 450 Authorization required for this command
		// - 480 Authentication required (old)
		// Tip: If server wrongly responds "440 Posting not permitted", add check for it below.
		if ($auth && ($code == 450 || $code == 480)) {
			if (!$this->auth_cmd()) {
				return false;
			}
			$code = $this->cmd($cmd, false);
		}
		return $code;
	}

	/** Connect to newsgroup server and get metadata for the group. */
	function connect()
	{
		$this->fs = @fsockopen($this->server, $this->port, $errno, $errstr, $this->timeout);
		if (!@is_resource($this->fs)) {
			$this->error = 'Unable to establish connection to '. $this->server .' on port '. $this->port ." failed\nWith Error #". $errno .': '. $errstr ."\n";
			return false;
		}
		if (!socket_set_blocking($this->fs, true)) {
			$this->error = "Unable to make socket to blocking mode\n";
			return false;
		}

		$ret = $this->get_return_code();

		if (!$this->compare_return_codes(200, $ret) && !$this->compare_return_codes(201, $ret)) {
			$this->error = 'Failed to receive proper response from NNTP Server, got '. $this->lrc ."\n";
			return false;
		}

		$ret = $this->cmd('MODE reader');

		if (($this->cmd('GROUP '. $this->newsgroup) != 211)) {
			$this->error = 'Unable to use '. $this->newsgroup .' newsgroup NTTP Msg: '. $this->lrc ."\n";
			return false;
		} else {
			$tmp = explode(' ', $this->lrc);
			$this->group_article_cnt = $tmp[1];
			$this->group_first       = $tmp[2];
			$this->group_last        = $tmp[3];
		}

		return true;
	}

	/** Fetch a single post from a newsgroup. */
	function get_message($id)
	{
		// Zero the vars.
		$this->raw_msg = $this->attachments = null;

		$code = $this->cmd('ARTICLE '. $id);
		if ($code != 220) {
			$this->error = "Cannot fetch #$id: ". $this->lrc;

			// Check for resumable errors:
			// * 423 no such article number in this group
			// * 430 no such article found
			if  ($code == 423 || $code == 430) {	// Resumable errors.
				return false;
			} else {				// Non-resumable errors.
				$this->exit_handler();
			}
		}
		while (!feof($this->fs)) {
			$line = fgets($this->fs, 1024);
			if (!$line || $line == ".\r\n" || $line == ".\n") {
				break;
			}
			$this->raw_msg .= $line;
		}

		// We may want to rather return $this->raw_msg in a future release.
		return true;
	}

	/** Close the connection. */
	function close_connection()
	{
		if (@is_resource($this->fs)) {
			unset($this->fs);
		}
	}

	/** Check if we need to log an error and/or abort processing. */
	function exit_handler($exit=1)
	{
		if (!empty($this->error)) {
			fud_logerror($this->error, 'nntp_errors');
		}
		$this->close_connection();
		if ($exit) {
			exit($this->error);
		}
	}

	/** Acquire lock to prevent concurrent NNTP imports. */ 
	function get_lock()
	{
		$u = umask(0111);
		$fp = fopen($GLOBALS['ERROR_PATH'] .'.nntp/'. $this->server .'-'. $this->newsgroup .'.lock' , 'wb');
		flock($fp, LOCK_EX);
		umask($u);

		return $fp;
	}

	/** Release the lock. */
	function release_lock($fp)
	{
		fclose($fp);
	}

	/** Save message number as restarting point for the next run. */
	function set_tracker_end($rule_id, $tracker)
	{
		if (++$tracker > $this->group_last) {
			$tracker = $this->group_last;
		}

		q('UPDATE '. $GLOBALS['DBHOST_TBL_PREFIX'] .'nntp SET tracker = '. $tracker .' WHERE id='. $rule_id);
	}

	/** Post a message back to the USENET newsgroup. */
	function post_message($subject, $body, $from, $forum_msg_id, $reply_to=null, $attch=null, $attach_mime=null)
	{
		$multi_part = 0;
		$final_body = '';

		if ($GLOBALS['FUD_OPT_3'] & 8) {	// NNTP_OBFUSCATE_EMAIL
			// $from = str_replace(array('.', '@'), array('[dot]', '[at]'), $from);
			$from = str_replace('@', '@nospam.', $from);
		}

		// If we have attachments, and MIME encoding is preferred, insert attachments into body.
		if ($GLOBALS['FUD_OPT_3'] & 512) {	// NNTP_MIME_POSTS
			$multi_part = 1;
			$boundry = md5(get_random_value());

			$final_body = "\r\n--". $boundry ."\r\nContent-Type: text/plain;\r\n  charset=\"". $GLOBALS['CHARSET'] ."\"\nContent-Transfer-Encoding: 8bit\r\n\r\n". $body ."\r\n";

			if (!empty($attch)) {
				foreach ($attch as $file_name => $file_data) {
					$final_body .= "\r\n--". $boundry ."\r\nContent-Type: ". $attach_mime[$file_name] ."; name=\"$file_name\"\nContent-Transfer-Encoding: base64\nContent-Disposition: attachment; filename=\"". $file_name ."\"\n\n". chunk_split(base64_encode($file_data)) ."\r\n\r\n";
				}
			}
			$final_body .= "\r\n--". $boundry ."--\r\n";
		} else {
			$final_body = $body;
		}

		/* NNTP connect. If connection fails, it will set $this->error. */
		if (!$this->connect()) {
			$this->exit_handler(0);
			return;
		}

		if ($this->cmd('POST') != 340) {
			$this->error = 'Failed to receive proper response to POST command, NNTP server replied: '. $this->lrc ."\n";
			$this->exit_handler(0);
			return;
		}

		fputs($this->fs, 'From: '. $from ."\r\n");
		fputs($this->fs, 'Newsgroups: '. $this->newsgroup ."\r\n");
		if (function_exists('iconv_mime_encode')) {
			fputs($this->fs, iconv_mime_encode('Subject', $subject,
				array('scheme'=>'Q', 'input-charset'=>$GLOBALS['CHARSET'], 'output-charset'=>$GLOBALS['CHARSET']))
				."\r\n"); 
		} else {
		        fputs($this->fs, 'Subject: '. $subject ."\r\n");
		}

		if ($GLOBALS['FORUM_TITLE']) {
	        	fputs($this->fs, 'Organization: '. $GLOBALS['FORUM_TITLE'] ."\r\n");
		}

		fputs($this->fs, 'Date: '. date('r') ."\r\n");
		fputs($this->fs, 'Reply-To: '. $from ."\r\n");
		fputs($this->fs, 'Lines: '. substr_count($final_body, "\n") ."\r\n");

		// Will be used to identify forum's messages. Prevent circular loads later.
		fputs($this->fs, 'X-FUDforum: '. md5($GLOBALS['WWW_ROOT']) ." <$forum_msg_id>\r\n");

		// Reference parent's Message-ID so it is seen as a reply.
		if ($reply_to) {
			fputs($this->fs, 'References: <'. $reply_to .">\r\n");
		}

		fputs($this->fs, 'User-Agent: FUDforum '. $GLOBALS['FORUM_VERSION'] ."\r\n");
		fputs($this->fs, 'MIME-Version: 1.0' . "\r\n");
		fputs($this->fs, 'Content-Transfer-Encoding: 8bit' . "\r\n");
		if (!$multi_part) {
			fputs($this->fs, 'Content-Type: text/plain; charset='. $GLOBALS['CHARSET'] ."; format=flowed\r\n");
		} else {
			fputs($this->fs, "Content-Type: Multipart/Mixed;\r\n  boundary=\"$boundry\"\r\n");
		}

		if (is_array($attch) && (!$multi_part)) {
			// Split body at first signature.
			$sigpos = strpos($final_body, "\n-- \n");
			$body_before_sig = $sigpos ? substr($final_body, 0, $sigpos) : $final_body;
			$body_after_sig  = $sigpos ? substr($final_body, $sigpos)    : '';

			fputs($this->fs, "\r\n". $body_before_sig);

			// Insert file attachments before the first signature to prevent signature pruning from removing them.
			fputs($this->fs, "\r\n");
			foreach ($attch as $fname => $file_data) {
				fputs($this->fs, $this->uuencode($file_data, $fname));
			}

			fputs($this->fs, $body_after_sig);
		} else {
			fputs($this->fs, "\r\n". $final_body);
		}

		fputs($this->fs, "\r\n.\r\n");

		if (!$this->compare_return_codes(240)) {
			$this->error = 'Posting Failed, NNTP Server Said: '. $this->lrc ."\n";
			$this->exit_handler(0);
			return;
		}

		$this->close_connection();
		return true;
	}

	/** UUencoded an attachment. Used by post_message(). */
	function uuencode($in, $file_name)
	{
		$out = 'begin 644 '. $file_name ."\r\n";

		if (function_exists('convert_uuencode')) {
			return $out . convert_uuencode($in) ."end\r\n";
		}

		$n = strlen($in);
		for ($i = 0; $i < $n; $i += 3) {
			if (!($i % 45)) {
				if ($i) {
					$out .= "\r\n";
				}
				if (($i + 45) < $n) {
					$out .= 'M';
				} else {
					if (($c = ($n - $i))) {
						$out .= chr(($c & 077) + 32);
					} else {
						$out .= '`';
					}
				}
			}

			$n1 = ($i + 1 < $n) ? ord($in[$i + 1]) : 0;
			$n2 = ($i + 2 < $n) ? ord($in[$i + 2]) : 0;

			$c = ord($in[$i]) >> 2;
			$out .= $c ? chr(($c & 077) + 32) : '`';
			$c = ((ord($in[$i]) << 4) & 060) | (($n1 >> 4) & 017);
			$out .= $c ? chr(($c & 077) + 32) : '`';
			$c = (($n1 << 2) & 074) | (($n2 >> 6) & 03);
			$out .= $c ? chr(($c & 077) + 32) : '`';
			$c = $n2 & 077;
			$out .= $c ? chr(($c & 077) + 32) : '`';

		}
		return $out ."\r\n`\r\nend\r\n";
	}

	/** Remove attachments from post and return body. 
	 * Attachments will be decoded and added to $this->attachments.
	*/
	function parse_attachments($body)
	{
		$this->attachments = null;

		// UU encoded.
		$s = 0;
		while (($s = strpos($body, 'begin ', $s)) !== false) {
			if (!$s || ($body[$s - 2] == "\r" && $body[$s - 1] == "\n")) {
				if (($e = strpos($body, "\r\nend\r\n", $s)) === false) {
					if (($e = strpos($body, "\nend\n", $s)) === false) { // Invalid line endings.
						if (substr($body, -3) == 'end') {
							$e = strlen($body) - 5;
						} else {
							$s += 6;
							continue;
						}
					} else {
						$e -= 2;
					}
				}
				if ($this->nntp_opt & 8) {	// allow_nntp_attch
					$this->fud_uudecode(str_replace("\r", '', substr($body, $s, ($e + 5 - $s))));
				}
				$body = substr($body, 0, $s) . substr($body, ($e + 7));
				$s = 0;
			} else {
				$s += 6;
			}
		}

		// Base64 encoded.
		$s = 0;
		while (($s = strpos($body, 'begin-base64 ', $s)) !== false) {
			if (!$s || ($body[$s - 2] == "\r" && $body[$s - 1] == "\n")) {
				if (($e = strpos($body, "====\r\n", $s)) === false) {
					if (substr($body, -4) == '====') {
						$e = strlen($body) - 4;
					} else {
						$s += 13;
						continue;
					}
				}
				if ($this->nntp_opt & 8) {
					$this->fud_base64decode(str_replace("\r", '', substr($body, $s, ($e+4-$s))));
				}
				$body = substr($body, 0, $s) . substr($body, ($e + 5));
				$s = 0;
			}
			$s += 13;
		}

		// Send the body (without attachments) back.
		return $body;
	}

	/** Decode a BASE64 attachment and add to $this->attachments. */
	function fud_base64decode($data)
	{
		if (strncmp($data, 'begin-base64', 12)) {
			return;
		}

		$filename = substr($data, 0, ($e=strpos($data, "\n")));
		$filename = substr($filename, strpos($filename, ' ', 13)+1);

		if (($e2 = strpos($data, '====', $e)) === false) {
			return;
		}

		$data = trim(substr($data, $e, ($e2-$e)));
		$data = str_replace("\r", '', $data);

		$this->attachments[$filename] = base64_decode($data);
	}

	/** Decode a UUEncoded attachment and add to $this->attachments. */
	function fud_uudecode($data)
	{
		$data = trim($data);
		// begin 0-7{3} (.*)\r\n (filename)
		if (strncmp($data, 'begin', 5)) {
			return;
		}
		$filename = substr($data, 0, ($e=strpos($data, "\n")));
		$filename = substr($filename, strpos($filename, ' ', 6)+1);

		if (($e2 = strrpos($data, 'end')) === false) {
			return;
		}
		if (function_exists('convert_uudecode')) {
			$this->attachments[$filename] = convert_uudecode(trim(substr($data, $e, ($e2-$e))));
		}

		$data = trim(substr($data, $e, ($e2-$e)));

		$out = '';
		foreach(explode("\n", $data) as $line) {
			$p = 0;
			$n = ((ord($line[$p]) -32) & 077);
			if ($n <= 0) {
				break;
			}

			for (++$p; $n > 0; $n -= 3) {
				if ($n >= 3) {
					$out .= chr(((ord($line[$p++]) - 32) & 077) << 2 | ((ord($line[$p]) - 32) & 077) >> 4);
					$out .= chr(((ord($line[$p++]) - 32) & 077) << 4 | ((ord($line[$p]) - 32) & 077) >> 2);
					$out .= chr(((ord($line[$p++]) - 32) & 077) << 6 | ((ord($line[$p++]) - 32) & 077));
				} else {
					if ($n >= 1) {
						$out .= chr(((ord($line[$p]) - 32) & 077) << 2 | ((ord($line[$p+1]) - 32) & 077) >> 4);
					}
					if ($n >= 2) {
						$out .= chr(((ord($line[$p+1]) - 32) & 077) << 4 | ((ord($line[$p+2]) - 32) & 077) >> 2);
					}

					$p += 4;
				}
			}
		}
		$this->attachments[$filename] = $out;
	}
}

?>
