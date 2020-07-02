<?php
/**
* copyright            : (C) 2001-2019 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: GLOBALS.php 6340 2019-11-22 05:03:21Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

	$GLOBALS['INCLUDE'] 		= '/var/www/forum.wigedev.com/FUDforum/include/';
	$GLOBALS['WWW_ROOT'] 		= 'https://forum.wigedev.com/';
	$GLOBALS['WWW_ROOT_DISK']	= '/var/www/forum.wigedev.com/public/';
	$GLOBALS['DATA_DIR']		= '/var/www/forum.wigedev.com/FUDforum/';
	$GLOBALS['ERROR_PATH'] 		= '/var/www/forum.wigedev.com/FUDforum/errors/';
	$GLOBALS['MSG_STORE_DIR']	= '/var/www/forum.wigedev.com/FUDforum/messages/';
	$GLOBALS['TMP']			= '/var/www/forum.wigedev.com/FUDforum/tmp/';
	$GLOBALS['FILE_STORE']		= '/var/www/forum.wigedev.com/FUDforum/files/';
	$GLOBALS['FORUM_SETTINGS_PATH'] = '/var/www/forum.wigedev.com/FUDforum/cache/';
	$GLOBALS['PLUGIN_PATH'] 	= '/var/www/forum.wigedev.com/FUDforum/plugins/';

	$GLOBALS['FUD_OPT_1']		= 1741616191;
	$GLOBALS['FUD_OPT_2']		= 1777733759;
	$GLOBALS['FUD_OPT_3']		= 41943104;
	$GLOBALS['FUD_OPT_4']		= 3;

	$GLOBALS['CUSTOM_AVATAR_MAX_SIZE'] = 20000;	/* bytes */
	$GLOBALS['CUSTOM_AVATAR_MAX_DIM']  = '64x64';	/* width x height (pixels) */

	$GLOBALS['COOKIE_PATH']		= '/';
	$GLOBALS['COOKIE_DOMAIN']	= 'forum.wigedev.com';
	$GLOBALS['COOKIE_NAME']		= 'fud_session_1593574339';
	$GLOBALS['COOKIE_TIMEOUT'] 	= 604800;	/* seconds */
	$GLOBALS['SESSION_TIMEOUT'] 	= 604800;	/* seconds */

	$GLOBALS['DBHOST'] 		= 'db-mysql-nyc1-18194-do-user-2414580-0.db.ondigitalocean.com:25060';
	$GLOBALS['DBHOST_SLAVE_HOST']	= '';
	$GLOBALS['DBHOST_USER']		= 'forumadmin';
	$GLOBALS['DBHOST_PASSWORD']	= 'jh2u87asofc3evgp';
	$GLOBALS['DBHOST_DBNAME']	= 'forum';
	$GLOBALS['DBHOST_TBL_PREFIX']	= 'fud30_';		/* do not modify this */
	$GLOBALS['DBHOST_DBTYPE']	= 'pdo_mysql';

	$GLOBALS['FUD_SMTP_SERVER']	= '127.0.0.1';
	$GLOBALS['FUD_SMTP_PORT']	= 25;
	$GLOBALS['FUD_SMTP_TIMEOUT']	= 10;		/* seconds */
	$GLOBALS['FUD_SMTP_LOGIN']	= '';
	$GLOBALS['FUD_SMTP_PASS']	= '';

	$GLOBALS['ADMIN_EMAIL'] 	= 'root@forum.wigedev.com';

	$GLOBALS['PRIVATE_ATTACHMENTS']	= 5;		/* int */
	$GLOBALS['PRIVATE_ATTACH_SIZE']	= 1000000;	/* bytes */
	$GLOBALS['MAX_PMSG_FLDR_SIZE']	= 300000;	/* bytes */
	$GLOBALS['MAX_PMSG_FLDR_SIZE_AD']	= 1000000;	/* bytes */
	$GLOBALS['MAX_PMSG_FLDR_SIZE_PM']	= 1000000;	/* bytes */

	$GLOBALS['FORUM_IMG_CNT_SIG']	= 2;		/* int */
	$GLOBALS['FORUM_SIG_ML']	= 256;		/* int */

	$GLOBALS['UNCONF_USER_EXPIRY']	= 7;		/* days */
	$GLOBALS['MOVED_THR_PTR_EXPIRY']	= 3;		/* days */

	$GLOBALS['MAX_SMILIES_SHOWN']	= 15;		/* int */
	$GLOBALS['DISABLED_REASON']	= 'Temporarily offline; please come back soon!';
	$GLOBALS['POSTS_PER_PAGE'] 	= 40;
	$GLOBALS['THREADS_PER_PAGE']	= 40;
	$GLOBALS['WORD_WRAP']		= 60;
	$GLOBALS['NOTIFY_FROM']		= 'root@forum.wigedev.com';		/* email */
	$GLOBALS['ANON_NICK']		= 'Anonymous';	/* coward */
	$GLOBALS['FLOOD_CHECK_TIME']	= 60;		/* seconds */
	$GLOBALS['MOD_FIRST_N_POSTS']	= 1;
	$GLOBALS['POSTS_BEFORE_LINKS']	= 1;
	$GLOBALS['POST_MIN_LEN']	= 3;
	$GLOBALS['SERVER_TZ']		= 'UTC';
	$GLOBALS['SEARCH_CACHE_EXPIRY']	= 172800;	/* seconds */
	$GLOBALS['MEMBERS_PER_PAGE']	= 40;
	$GLOBALS['POLLS_PER_PAGE']	= 40;
	$GLOBALS['THREAD_MSG_PAGER']	= 5;
	$GLOBALS['GENERAL_PAGER_COUNT']	= 15;
	$GLOBALS['EDIT_TIME_LIMIT']	= 0;
	$GLOBALS['LOGEDIN_TIMEOUT']	= 5;		/* minutes */
	$GLOBALS['MAX_IMAGE_COUNT']	= 10;
	$GLOBALS['STATS_CACHE_AGE']	= 600;		/* seconds */
	$GLOBALS['FORUM_TITLE']		= 'My forum, my way!';
	$GLOBALS['FORUM_DESCR']		= 'Fast Uncompromising Discussions. FUDforum will get your users talking.';
	$GLOBALS['MAX_LOGIN_SHOW']	= 25;
	$GLOBALS['MAX_LOCATION_SHOW']	= 25;
	$GLOBALS['SHOW_N_MODS']		= 0;

	$GLOBALS['TREE_THREADS_MAX_DEPTH']	= 15;
	$GLOBALS['TREE_THREADS_MAX_SUBJ_LEN']	= 75;

	$GLOBALS['REG_TIME_LIMIT']		= 60;		/* seconds */
	$GLOBALS['POST_ICONS_PER_ROW']	= 9;		/* int */
	$GLOBALS['MAX_LOGGEDIN_USERS']	= 25;		/* int */
	$GLOBALS['PHP_COMPRESSION_LEVEL']	= 9;		/* int 1-9 */
	$GLOBALS['PHP_CLI']		= '';		/* Command line PHP exectable */
	$GLOBALS['MNAV_MAX_DATE']	= 31;		/* days */
	$GLOBALS['MNAV_MAX_LEN']	= 512;		/* characters */

	$GLOBALS['FEED_MAX_N_RESULTS']	= 20;		/* int */
	$GLOBALS['FEED_AUTH_ID']	= 0;		/* 0 - treat as anon user, >0 treat like specific forum user */
	$GLOBALS['FEED_CACHE_AGE']	= 3600;

	$GLOBALS['PDF_PAGE']		= 'letter';	/* string */
	$GLOBALS['PDF_WMARGIN']		= 15;		/* int */
	$GLOBALS['PDF_HMARGIN']		= 15;		/* int */
	$GLOBALS['PDF_MAX_CPU']		= 15;		/* seconds */

	$GLOBALS['FUD_WHOIS_SERVER']	= 'ws.arin.net';
	$GLOBALS['MIN_TIME_BETWEEN_LOGIN']	= 10;		/* seconds */

/* DO NOT EDIT FILE BEYOND THIS POINT UNLESS YOU KNOW WHAT YOU ARE DOING */

	require($GLOBALS['INCLUDE'] .'core.inc');
?>
