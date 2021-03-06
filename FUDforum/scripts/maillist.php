#!/usr/bin/php -q
<?php
/**
* copyright            : (C) 2001-2017 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: maillist.php 6175 2018-07-14 10:04:32Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it 
* under the terms of the GNU General Public License as published by the 
* Free Software Foundation; version 2 of the License. 
**/

 	/* Prevent session initialization. */
	define('no_session', 1);
	define('script', 'mlist');

	if (ini_get('register_argc_argv')) {
		// Try to get the Mailing List name/id from command line.
		if ($_SERVER['argc'] < 2) {
			exit("Please specify the Mailing List name or id as a command line parameter.\n");
		}
		$dir = $_SERVER['argv'][0];
		$id  = $_SERVER['argv'][1];
	} else if (isset($_GET['id'])) {
		// Try to get it via HTTP GET.
		$dir = '';
		$id  = $_GET['id'];
	} else {
		// Give up.
		exit("Please specify the Mailing List name or id.\n");
	}

	if (strncmp($dir, '.', 1)) {
		require (dirname($dir) .'/GLOBALS.php');
	} else {
		require (getcwd() .'/GLOBALS.php');
	}

	if (!($FUD_OPT_1 & 1)) {
		exit("Forum is currently disabled. Please try again later.\n");
	}

	fud_use('err.inc');
	fud_use('db.inc');
	fud_use('imsg_edt.inc');
	fud_use('th.inc');
	fud_use('th_adm.inc');
	fud_use('wordwrap.inc');
	fud_use('isearch.inc');
	fud_use('replace.inc');
	fud_use('rev_fmt.inc');
	fud_use('iemail.inc');
	fud_use('post_proc.inc');
	fud_use('is_perms.inc');
	fud_use('users.inc');
	fud_use('users_reg.inc');
	fud_use('attach.inc');
	fud_use('rhost.inc');
	fud_use('smiley.inc');
	fud_use('fileio.inc');
	fud_use('forum_notify.inc');
	fud_use('mime_decode.inc', true);
	fud_use('scripts_common.inc', true);

	define('sql_p', $DBHOST_TBL_PREFIX);

	if (is_numeric($id)) {
		$config = db_sab('SELECT /* USE MASTER */ * FROM '. sql_p .'mlist WHERE id='. $id);
	} else {
		$config = db_sab('SELECT /* USE MASTER */ * FROM '. sql_p .'mlist WHERE name='. _esc($id));
	}
	if (!$config) {
		exit("The mailing list name or id is incorrect. Please enter it as defined in the ACP.\n");
	}

	$CREATE_NEW_USERS = $config->mlist_opt & 64;
	$FUD_OPT_2 |= $FUD_OPT_2 &~ (1024|8388608);
	$FUD_OPT_2 |= 128;

	/* Set language, locale and time zone. */
	$GLOBALS['usr'] = new stdClass();
	list($GLOBALS['usr']->lang, $locale) = db_saq(q_limit('SELECT lang, locale FROM '. sql_p .'themes WHERE theme_opt='. (1|2), 1));
	$GLOBALS['good_locale'] = setlocale(LC_ALL, $locale);
	date_default_timezone_set($GLOBALS['SERVER_TZ']);

	$frm = db_sab('SELECT /* USE MASTER */ id, name, forum_opt, message_threshold, (max_attach_size * 1024) AS max_attach_size, max_file_attachments FROM '. sql_p .'forum WHERE id='. $config->forum_id);

	/* Fetch messaged form IMAP of POP3 inbox. */
	if ($config->mbox_server && $config->mbox_user) {
		if (!function_exists('imap_open')) {
			exit('PHP\'s IMAP extension was not detected, mail cannot be fetched.');
		}

		// Setup protocol, port and flags.
		if (!$config->mbox_type) {	// Unsecure POP3 mailbox.
			$protocol = 'POP3';
			$port     = 110;
			$flags    = '/novalidate-cert';
		} else if ($config->mbox_type & 1) {	// Unsecure IMAP mailbox.
			$protocol = 'IMAP';
			$port     = 143;
			$flags    = '/novalidate-cert';
		} else if ($config->mbox_type & 2) {	// POP3, TLS mode.
			$protocol = 'POP3';
			$port     = 110;
			$flags    = '/tls/novalidate-cert';
		} else if ($config->mbox_type & 4) {	// IMAP, TLS mode.
			$protocol = 'IMAP';
			$port     = 143;
			$flags    = '/tls/novalidate-cert';
		} else if ($config->mbox_type & 8) {	// POP3, SSL mode.
			$protocol = 'POP3';
			$port     = 995;
			$flags    = '/ssl/novalidate-cert';
		} else if ($config->mbox_type & 16) {	// IMAP, SSL mode.
			$protocol = 'IMAP';
			$port     = 993;
			$flags    = '/ssl/novalidate-cert';
		}

		// Add default port if the user didn't specify it.
		$config->mbox_server .= (strpos($config->mbox_server, ':') === FALSE) ? ':'. $port : '';

		// Connect and search for e-mail messages.
		$inbox = '{'. $config->mbox_server .'/'. $protocol . $flags .'}INBOX';
		$mbox = @imap_open($inbox, $config->mbox_user, $config->mbox_pass) or die('Can\'t connect to mailbox: '. imap_last_error());
		if ($mbox) {
			echo "Connected to mailbox $inbox\n";
			// $emails = @imap_search($mbox, 'ALL');
			$emails = @imap_sort($mbox, SORTARRIVAL, 1);
		} else {
			echo 'Error connecting to mailbox $inbox: '. imap_last_error() ."\n";
		}
	}

	$done = 0;
	$counter = 1;
	while (!$done) {
		$emsg = new fud_mime_msg();
		$emsg->subject_cleanup_rgx = $config->subject_regex_haystack;
		$emsg->subject_cleanup_rep = $config->subject_regex_needle;
		$emsg->body_cleanup_rgx    = $config->body_regex_haystack;
		$emsg->body_cleanup_rep    = $config->body_regex_needle;

		echo $counter==1 ? '' : "\n";

		if (!empty($_SERVER['argv'][2])) {
			// Get list of files we need to load.
			if (!isset($GLOBALS['filelist'])) {
				// Get list of files to load sorted by name.
				$GLOBALS['filelist'] = array_reverse(glob($_SERVER['argv'][2]));
			}

			if (empty($GLOBALS['filelist'])) {
				echo 'No more files to process.';
				$done = 1;
				continue;
			}
			
			/* Read message from file and load it into the forum. */
			$filename = array_pop($GLOBALS['filelist']);
			echo "Load message from file $filename";
			$email_message = file_get_contents($filename);
			$emsg->parse_message($email_message, $config->mlist_opt & 16);
			$counter++;
		} else if ($config->mbox_server && $config->mbox_user) {
			/* Fetch message from mailbox and load them into the forum. */
			if (empty($emails)) {
				echo 'No more mails to process.';
				$done = 1;
				continue;
			}
			$email_number = array_pop($emails);
			$email_message = imap_fetchbody($mbox, $email_number, '');
			echo 'Load message '. $email_number;
			$emsg->parse_message($email_message, $config->mlist_opt & 16);
			echo '. Done. Deleting message.';
			imap_delete($mbox, $email_number);
			$counter++;
		} else {
			/* Read single message from pipe (stdin) and load it into the forum. */
			echo 'Load message from STDIN (type message)';
			$email_message = file_get_contents('php://stdin');
			if (empty($email_message)) {
				fud_logerror('Nothing to import! Please pipe your messages into the script or use a mailbox.', 'mlist_errors');
				exit();
			}
			$emsg->parse_message($email_message, $config->mlist_opt & 16);
			$done = 1;
		}

		$emsg->fetch_useful_headers();
		$emsg->clean_up_data();

		$msg_post = new fud_msg_edit;

		/* Check if message was already imported. */
		if ($emsg->msg_id && q_singleval('SELECT m.id FROM '. sql_p .'msg m
						INNER JOIN '. sql_p .'thread t ON t.id=m.thread_id
						WHERE mlist_msg_id='. _esc($emsg->msg_id) .' AND t.forum_id='. $frm->id)) {
			echo ' - previously loaded';
			continue;
		}
		
		/* Skip spam messages. */
		if (isset($emsg->headers['x-spam-flag']) && ($emsg->headers['x-spam-flag'] == 'YES')) {
			echo ' - skip spam message.';
			continue;
		}

		/* Handler for our own messages, which do not need to be imported. */
		if (isset($emsg->headers['x-fudforum']) && preg_match('!'. md5($GLOBALS['WWW_ROOT']) .' <([0-9]+)>!', $emsg->headers['x-fudforum'], $m)) {
			q('UPDATE '. sql_p .'msg SET mlist_msg_id='. _esc($emsg->msg_id) .' WHERE id='. (int)$m[1] .' AND mlist_msg_id IS NULL');
			continue;
		}

		/* Parse 'Date:' header. */
		$msg_post->post_stamp = !empty($emsg->headers['date']) ? strtotime($emsg->headers['date']) : 0;
		if ($msg_post->post_stamp < 1 || $msg_post->post_stamp > __request_timestamp__) {
			// Try to extract date from 'Received:' header
			if (($p = strpos($emsg->headers['received'], '; ')) !== false) {
				$p += 2;
				$msg_post->post_stamp = strtotime(substr($emsg->headers['received'], $p, (strpos($emsg->headers['received'], '00 ', $p) + 2 - $p)));
			}
			if ($msg_post->post_stamp < 1 || $msg_post->post_stamp > __request_timestamp__) {
				// Last resort, use curent date.
				fud_logerror('Invalid date.', 'mlist_errors', $emsg->raw_msg);
				$msg_post->post_stamp = __request_timestamp__;
			}
		}

		if (!$emsg->from_email || !$emsg->from_name) {
			$msg_post->poster_id = 0;
		} else {
			// Get or create new forum user.
			$msg_post->poster_id = match_user_to_post($emsg->from_email, $emsg->from_name, $config->mlist_opt & 64, $emsg->user_id, $msg_post->post_stamp);
			if ($msg_post->poster_id == -1) {
				continue;	// Skip, user is banned.
			}
		}

		/* Mail sent to control address (*-admin) by a known forum user. */
		if ($msg_post->poster_id && preg_match('!-admin!i', $emsg->headers['to'])) {
			$alias = q_singleval('SELECT alias FROM '. sql_p .'users WHERE id='. $msg_post->poster_id);

			/* Handle E-mail unsubscribe requests. */
			if (preg_match('!unsubscribe!i', $emsg->subject)) {
				if (is_forum_notified($msg_post->poster_id, $frm->id)) {
					forum_notify_del($msg_post->poster_id, $frm->id);
					fud_logerror('Unsubscribe '. $alias .' from '. $frm->name, 'mlist_errors');
					echo('Unsubscribe '. $alias .' from forum '. $frm->name .".\n");
				} else {
					echo('User '. $alias .' is not subscribed and can hence not be unsubscribed from forum '. $frm->name .".\n");
				}
				continue;
			}
			/* Handle E-mail subscribe requests. */
			if (preg_match('!subscribe!i', $emsg->subject)) {
				if (!is_forum_notified($msg_post->poster_id, $frm->id)) {
					forum_notify_add($msg_post->poster_id, $frm->id);
					fud_logerror('Subscribe '. $alias .' to '. $frm->name, 'mlist_errors');
					echo('Subscribe '. $alias .' to forum '. $frm->name .".\n");
				} else {
					echo('User '. $alias .' is already subscribed to forum '. $frm->name .".\n");
				}
				continue;
			}
		}

		$attach_list = array();
		/* Handle inlined attachments. */
		if ($config->mlist_opt & 8) {	// allow_mlist_attch
			foreach ($emsg->inline_files as $k => $v) {
				if (strpos($emsg->body, 'cid:'. $v) !== false) {
					$id = add_attachment($k, $emsg->attachments[$k], $msg_post->poster_id);
					$attach_list[$id] = $id;
					$emsg->body = str_replace('cid:'. $v, $WWW_ROOT .'index.php?t=getfile&amp;id='. $id, $emsg->body);
				}
				unset($emsg->attachments[$k]);
			}
		}

		$msg_post->body = $emsg->body;

		/* For anonymous users prefix 'contact' link. */
		if (!$msg_post->poster_id) {
			if ($frm->forum_opt & 16) {	// BBCode tag style.
				$msg_post->body = '[b]Originally posted by:[/b] [email='. $emsg->from_email .']'. (!empty($emsg->from_name) ? $emsg->from_name : $emsg->from_email) ."[/email]\n\n". $msg_post->body;
			} else {
				$msg_post->body = 'Originally posted by: '. str_replace('@', '&#64', $emsg->from_email) ."\n\n". $msg_post->body;
			}
		}

		// Color levels of quoted text.
		$msg_post->body = apply_custom_replace($msg_post->body);
		$msg_post->body = color_quotes($msg_post->body, $frm->forum_opt);

		// If HTML is not allowed, strip it out.
		if (!($config->mlist_opt & 16)) {	// NOT allow_mlist_html
			$msg_post->body = preg_replace("/<([^>]*(<|$))/", "&lt;$1", $msg_post->body);
			$msg_post->body = strip_tags($msg_post->body);
		}

		if ($frm->forum_opt & 16) {	// Forum takes BBcode tags.
			// Convert BBCode tags to HTML.
			// tags_to_html() will do a nl2br() as well.
			$msg_post->body = tags_to_html($msg_post->body, 0);
		} else {
			// Forum takes HTML tags. No need for conversion.
			$msg_post->body = nl2br($msg_post->body);
		}

		fud_wordwrap($msg_post->body);
		$msg_post->subject = apply_custom_replace($emsg->subject);
		if (!strlen($msg_post->subject)) {
			fud_logerror('Blank subject.', 'mlist_errors', $emsg->raw_msg);
			$msg_post->subject = '(no subject)';
		}

		/* Check if matching user and if not, skip if necessary. */
		if (!$msg_post->poster_id && $config->mlist_opt & 128) {
			continue;
		}

		$msg_post->ip_addr      = $emsg->ip;
		$msg_post->mlist_msg_id = $emsg->msg_id;
		$msg_post->attach_cnt   = 0;
		$msg_post->poll_id      = 0;
		$msg_post->msg_opt      = 1|2;

		// Try to determine whether this message is a reply or a new thread.
		list($msg_post->reply_to, $msg_post->thread_id) = get_fud_reply_id($config->mlist_opt & 32, $frm->id, $msg_post->subject, $emsg->reply_to_msg_id);

		$msg_post->add($frm->id, $frm->message_threshold, 0, 0, false);

		// Handle file attachments.
		if ($config->mlist_opt & 8) {	// allow_mlist_attch
			foreach($emsg->attachments as $key => $val) {
				$id = add_attachment($key, $val, $msg_post->poster_id);			
				$attach_list[$id] = $id;
			}
		}
		if ($attach_list) {
			attach_finalize($attach_list, $msg_post->id);
		}

		if (!($config->mlist_opt & 1)) {	// mlist_post_apr
			$msg_post->approve($msg_post->id);
		}
		// echo 'Added message '. $msg_post->id .' to forum '. $frm->id ."\n";
	}

	// Close the mailbox.
	if ($config->mbox_server && $config->mbox_user) {
		@imap_expunge($mbox);
		@imap_close($mbox);
	}

	echo "\nDone.\n";
?>
