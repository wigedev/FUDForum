<?php

namespace Model\Utility\Configuration;

use Model\Exceptions\ConfigurationException;

/**
 * Class Options
 *
 * Wrapper for the options globals that converts bits to friendly(friendlier?) text.
 *
 * @package Model
 */
class Options
{
    /** @var array The definitions of the various values */
    protected static $definitions = [
        'FORUM_ENABLED' => [
            'array' => 'FUD_OPT_1',
            'position' => 1,
            'description' => 'Enable/disable the forum. Use this while you are upgrading or performing maintenance on the forum.',
        ],
        'ALLOW_REGISTRATION' => [
            'array' => 'FUD_OPT_1',
            'position' => 2,
            'description' => 'Enable/disable new user registration. Disable if you don\'t want new users to register or if you have a plugin that auto registers uses from an external system.',
        ],
        'CUSTOM_AVATARS' => [
            'array' => 'FUD_OPT_1',
            'positions' => '0|16|4|8|20|24|12|28',
            'description' => 'Enable/disable to use of avatars. Definitions of the options:

URL - Users will need to give a URL to their avatar.
Uploaded - The avatar will be uploaded to the server.
Built In - A user can choose an avatar from the avatars available in the forum.
ALL - A user can use a URL or an uploaded/built-in avatar.
OFF - Avatars are disabled.',
            'options' => 'OFF\nBuilt In Only\nURL Only\nUploaded Only\nBuilt In & URL\nBuilt In & Uploaded\nURL & Uploaded\nALL',
        ],
        'CUSTOM_AVATAR_APPROVAL' => [
            'array' => 'FUD_OPT_1',
            'position' => 32,
            'description' => 'If set to yes the administrator will have to approve every custom avatar the user uploads or provides a URL to.',
        ],
        'CUSTOM_AVATAR_ALLOW_SWF' => [
            'array' => 'FUD_OPT_1',
            'position' => 64,
            'description' => 'Allow users to upload Macromedia Flash avatars, not recommended unless you trust your users implicitly, since Flash animation can potentially trigger various browser events.',
        ],
        'SESSION_USE_URL' => [
            'array' => 'FUD_OPT_1',
            'position' => 128,
            'description' => 'Allow session IDs to be passed via URL variables (as well as cookies). This should normally be disabled. Enable if your users cannot use cookies in their browsers, but if you do, use a small SESSION TIMEOUT value (for security).',
        ],
        'DBHOST_PERSIST' => [
            'array' => 'FUD_OPT_1',
            'position' => 256,
            'description' => 'Whether or not to use persistent connections. Slight performance increase, but may cause problems on some systems.',
        ],
        'USE_SMTP' => [
            'array' => 'FUD_OPT_1',
            'position' => 512,
            'description' => 'Whether or not to use FUDforum SMTP gateway to send E-mail instead of standard PHP mail() function. Not recommended, unless you need SMTP AUTH or wish to use alternate SMTP server on UNIX systems.',
        ],
        'PM_ENABLED' => [
            'array' => 'FUD_OPT_1',
            'position' => 1024,
            'description' => 'Enable/disable private messaging. Enabled by default.',
        ],
        'PRIVATE_TAGS' => [
            'array' => 'FUD_OPT_1',
            'positions' => '4096|0|2048',
            'description' => 'This setting specifies what kind of text formatting to allow in private messages.',
            'options' => 'BBCode\nHTML\nNone',
        ],
        'PRIVATE_MSG_SMILEY' => [
            'array' => 'FUD_OPT_1',
            'position' => 8192,
            'description' => 'Enable/disable smilies in private messages.',
        ],
        'PRIVATE_IMAGES' => [
            'array' => 'FUD_OPT_1',
            'position' => 16384,
            'description' => 'Enable/disable images in private messages.',
        ],
        'ALLOW_SIGS' => [
            'array' => 'FUD_OPT_1',
            'position' => 32768,
            'description' => 'Enable/disable user signatures in posts.',
        ],
        'FORUM_CODE_SIG' => [
            'array' => 'FUD_OPT_1',
            'positions' => '131072|0|65536',
            'description' => 'This setting specifies what kind of text formatting to allow in signatures.',
            'options' => 'BBCode\nHTML\nNone',
        ],
        'FORUM_SML_SIG' => [
            'array' => 'FUD_OPT_1',
            'position' => 262144,
            'description' => 'Enable/disable smilies in signatures.',
        ],
        'FORUM_IMG_SIG' => [
            'array' => 'FUD_OPT_1',
            'position' => 524288,
            'description' => 'Enable/disable images in signatures.',
        ],
        'COPPA' => [
            'array' => 'FUD_OPT_1',
            'position' => 1048576,
            'description' => 'Enable/disable the Children\'s Online Privacy Protection Act of 1998 (COPPA) check during new user registration.',
        ],
        'SPELL_CHECK_ENABLED' => [
            'array' => 'FUD_OPT_1',
            'position' => 2097152,
            'description' => 'Enable/disable the built-in spell checker.',
        ],
        'MEMBER_SEARCH_ENABLED' => [
            'array' => 'FUD_OPT_1',
            'positions' => '8388608|0|4194304',
            'description' => 'Enable/disable the forum member search.',
            'options' => 'Enabled\nDisabled\nOnly for registered users',
        ],
        'FORUM_SEARCH' => [
            'array' => 'FUD_OPT_1',
            'position' => 16777216,
            'description' => 'Enable/disable the ability to do a full text search of the forum. Disable this option if you have little CPU power and/or wish to conserve SQL space. If option is disabled the search database will NOT be populated during posting. If you decide to enable the search at a later point, you will need to rebuild the search index. This may take a while, and is entirely dependent on the speed of your server and the number of messages your forum has.',
        ],
        'SHOW_EDITED_BY' => [
            'array' => 'FUD_OPT_1',
            'position' => 33554432,
            'description' => 'Enable/disable the text that will reveal if a post was edited.',
        ],
        'EDITED_BY_MOD' => [
            'array' => 'FUD_OPT_1',
            'position' => 67108864,
            'description' => 'Enable/disable the text that will reveal if an edited post was edited by a moderator.',
        ],
        'DISPLAY_IP' => [
            'array' => 'FUD_OPT_1',
            'position' => 134217728,
            'description' => 'Publicly display the IP address that the message was posted from. The administrators will always see the IPs and so will the forum moderators in the forums they moderate.',
        ],
        'PUBLIC_RESOLVE_HOST' => [
            'array' => 'FUD_OPT_1',
            'position' => 268435456,
            'description' => 'Enable/disable the showing of the partially resolved hostname of the poster to all users (ex. me.myself.com will be shown as .myself.com). This option does not affect the showing of IP addresses.',
        ],
        'ACTION_LIST_ENABLED' => [
            'array' => 'FUD_OPT_1',
            'position' => 536870912,
            'description' => 'Enable/disable the ability for forum users to see what other forum users are doing.',
        ],
        'LOGEDIN_LIST' => [
            'array' => 'FUD_OPT_1',
            'position' => 1073741824,
            'description' => 'Enable/disable the list of users who are currently using the forum on the front page. This option is a MUST be enabled if you want to enable the Allow Action List.',
        ],
        'EMAIL_CONFIRMATION' => [
            'array' => 'FUD_OPT_2',
            'position' => 1,
            'description' => 'Enable/disable E-mail confirmation of new registered accounts. A special URL will be sent in the welcome E-mail upon registration.',
        ],
        'PUBLIC_STATS' => [
            'array' => 'FUD_OPT_2',
            'position' => 2,
            'description' => 'Enable/disable the showing of how long it took to generate the page.',
        ],
        'DEFAULT_THREAD_VIEW' => [
            'array' => 'FUD_OPT_2',
            'positions' => '12|0|4|8',
            'description' => 'The default view of the topic\'s contents and topic listing.',
            'options' => "Flat View thread and message list\nTree View thread and message list\nFlat thread listing/Tree message listing\nTree thread listing/Flat message listing"
        ],
        'FORUM_INFO' => [
            'array' => 'FUD_OPT_2',
            'position' => 16,
            'description' => 'Enable/disable the showing of forum statistics on the front page. Statistics include the total number of approved posts, number of topics and registered users and the last registered user.',
        ],
        'ONLINE_OFFLINE_STATUS' => [
            'array' => 'FUD_OPT_2',
            'position' => 32,
            'description' => 'Enable/disable the online/offline status indicator for the poster on messages.',
        ],
        'NOTIFY_WITH_BODY' => [
            'array' => 'FUD_OPT_2',
            'position' => 64,
            'description' => 'Include the message body and subject inside the E-mail notification.',
        ],
        'USE_ALIASES' => [
            'array' => 'FUD_OPT_2',
            'position' => 128,
            'description' => 'Allow users to specify a nick name, that will be used instead of a login name to identify those users on the forum.',
        ],
        'MULTI_HOST_LOGIN' => [
            'array' => 'FUD_OPT_2',
            'position' => 256,
            'description' => 'This option allows users to login into the forum from different computers/browsers and not terminate their existing sessions on other computers/browsers. WARNING: If you enable this option it is highly recommended that you disable URL sessions.',
        ],
        'TREE_THREADS_ENABLE' => [
            'array' => 'FUD_OPT_2',
            'position' => 512,
            'description' => 'Whether or not to allow the tree view of the topic listing. This is a fairly CPU intensive page compared to all other forum pages, so use this option with caution.',
        ],
        'MODERATE_USER_REGS' => [
            'array' => 'FUD_OPT_2',
            'position' => 1024,
            'description' => 'Whether or not every new registration will need to be approved by the administrator before the new account is activated.',
        ],
        'ENABLE_AFFERO' => [
            'array' => 'FUD_OPT_2',
            'position' => 2048,
            'description' => 'Previously used to enable affero, this resulted in an Affero button being shown beside each message, allowing users to give feedback via the Affero system. Removed in FUDforum 3.0.7.',
        ],
        'ENABLE_THREAD_RATING' => [
            'array' => 'FUD_OPT_2',
            'position' => 4096,
            'description' => 'Whether or not to allow users to rate topics.',
        ],
        'TRACK_REFERRALS' => [
            'array' => 'FUD_OPT_2',
            'position' => 8192,
            'description' => 'Whether or not to try to track forum referrals by setting a cookie with referral id to new incoming users.',
        ],
        'PHP_COMPRESSION_ENABLE' => [
            'array' => 'FUD_OPT_2',
            'position' => 16384,
            'description' => 'Whether or not to use PHP to compress forum\'s output. By turning this option you are likely to save a fair bit of bandwidth. However, for performance reasons it is recommended that you use native compression built-in into most webservers like mod_gzip or mod_deflate for Apache.',
        ],
        'USE_PATH_INFO' => [
            'array' => 'FUD_OPT_2',
            'position' => 32768,
            'description' => 'Use PATH_INFO based URLs that looks like http://forum.com/index.php/a/b/c/. This makes the URLs easier to remember and more search engine friendly. Check if your web server supports it by clicking on the above test link. If you see the forum\'s front page, you can enable it. Note that you also need to enable a theme based on the \'path_info\' template set.',
        ],
        'ALLOW_PROFILE_IMAGE' => [
            'array' => 'FUD_OPT_2',
            'position' => 65536,
            'description' => 'Whether or not to allow users to enter a URL to an image in their profile that will be displayed on the user info page for that user. The danger of this feature is that the user could potentially link to a page other then an image and insecure browsers like Internet Explorer will parse that page executing any potentially hostile Javascript that may be present.',
        ],
        'NEW_ACCOUNT_NOTIFY' => [
            'array' => 'FUD_OPT_2',
            'position' => 131072,
            'description' => 'This setting will only be used if you have chosen to manually approve all new registrations. If enabled, then every time a new user is registered all forum administrators will receive a notification via e-mail.',
        ],
        'MODERATED_POST_NOTIFY' => [
            'array' => 'FUD_OPT_2',
            'position' => 262144,
            'description' => 'Notify forum moderators via e-mail when a new message is posted in a forum where a message must first be approved by the moderator before shown to the rest of the visitors.',
        ],
        'BUST_A_PUNK' => [
            'array' => 'FUD_OPT_2',
            'position' => 524288,
            'description' => 'When a user is banned set a special cookie that will try to make sure that this user remains banned.',
        ],
        'SHOW_XML_LINK' => [
            'array' => 'FUD_OPT_2',
            'position' => 1048576,
            'description' => 'Whether or not to show a syndication link, linking to help on how get XML (RDF, Atom and RSS) feeds of the forum\'s data.}',
        ],
        'SHOW_PDF_LINK' => [
            'array' => 'FUD_OPT_2',
            'position' => 2097152,
            'description' => 'Whether or not to show a link allowing users to generate a printable PDF of the page they are viewing.',
        ],
        'DWLND_REF_CHK' => [
            'array' => 'FUD_OPT_2',
            'position' => 4194304,
            'description' => 'Check HTTP_REFERER of users before allowing users to download file attachments. If enabled, users who\'s HTTP_REFERER does not contain WWW_ROOT will be prevented from downloading the attachments.',
        ],
        'FILE_LOCK' => [
            'array' => 'FUD_OPT_2',
            'position' => 8388608,
            'description' => 'Whether or not to use secure file permissions (0600/0711).',
        ],
        'FEED_ENABLED' => [
            'array' => 'FUD_OPT_2',
            'position' => 16777216,
            'description' => 'Whether to allow XML (RDF, Atom and RSS) feeds of the forum\'s data.',
        ],
        'FEED_AUTH' => [
            'array' => 'FUD_OPT_2',
            'position' => 33554432,
            'description' => 'Whether or not to perform permission checks to determine if the user has access the requested data. If disabled, messages from restricted forums may be publicly visible in your forum\'s XML feed.',
        ],
        'FEED_ALLOW_USER_DATA' => [
            'array' => 'FUD_OPT_2',
            'position' => 67108864,
            'description' => 'Whether or not to allow user profile data to be fetched and presented as a feed.',
        ],
        'PDF_ENABLED' => [
            'array' => 'FUD_OPT_2',
            'position' => 134217728,
            'description' => 'Whether or not to enable PDF output.',
        ],
        'PDF_ALLOW_FULL' => [
            'array' => 'FUD_OPT_2',
            'position' => 268435456,
            'description' => 'Whether or not to allow users to generate a PDF containing ALL the messages in a particular forum.',
        ],
        'SHOW_REPL_LNK' => [
            'array' => 'FUD_OPT_2',
            'position' => 536870912,
            'description' => 'Whether or not to show a link (beside the message subject) to the message that the current message is a reply to.',
        ],
        'ALLOW_EMAIL' => [
            'array' => 'FUD_OPT_2',
            'position' => 1073741824,
            'description' => 'Enable/disable the forum\'s built-in E-mail client (allowing members to send other members mail).',
        ],
        'SESSION_COOKIES' => [
            'array' => 'FUD_OPT_3',
            'position' => 1,
            'description' => 'Make the cookie last only for as long as the browser window is open. If a security is a major concern, turn this option on.',
        ],
        'DISABLE_TREE_MSG' => [
            'array' => 'FUD_OPT_3',
            'position' => 2,
            'description' => 'Disable the threaded view of the contents for a single topic.',
        ],
        'ENABLE_REFERRER_CHECK' => [
            'array' => 'FUD_OPT_3',
            'position' => 4,
            'description' => 'Always check to make sure that the referring URL is on the same domain as the forum.',
        ],
        'NNTP_OBFUSCATE_EMAIL' => [
            'array' => 'FUD_OPT_3',
            'position' => 8,
            'description' => 'Whether or not to obfuscate user e-mails when sending e-mails from FUDforum to newsgroups.',
        ],
        'SESSION_IP_CHECK' => [
            'array' => 'FUD_OPT_3',
            'position' => 16,
            'description' => 'Whether or not to validate session\'s against user IP While this may increase security it will cause annoyances for users who\'s IP address frequently changes as they will need to re-login each time their IP changes.',
        ],
        'HIDE_PROFILES_FROM_ANON' => [
            'array' => 'FUD_OPT_3',
            'position' => 32,
            'description' => 'If enabled, anonymous and unconfirmed forum members will not be able to view profiles of registered users.',
        ],
        'SMART_EMAIL_NOTIFICATION' => [
            'array' => 'FUD_OPT_3',
            'position' => 64,
            'description' => 'Enable \'smart\' e-mail notification mechanism that would only notify users if they read previous notification. This prevents filling up of the user\'s mailbox with notifications if they are not reading them.',
        ],
        'DISABLE_TURING_TEST' => [
            'array' => 'FUD_OPT_3',
            'position' => 128,
            'description' => 'If disabled users will not need to complete a turing test intended to prevent automated account creation bots. Captcha test involves identifying a series of random letters that are displayed in ASCII form.',
        ],
        'DISABLE_AUTOCOMPLETE' => [
            'array' => 'FUD_OPT_3',
            'position' => 256,
            'description' => 'If disabled the browser will not attempt to \'remember\' the values for the login form.',
        ],
        'NNTP_MIME_POSTS' => [
            'array' => 'FUD_OPT_3',
            'position' => 512,
            'description' => 'Format forum messages as MIME encoded posts before sending them to a newsgroup. If disabled, messages will be UUencoded.',
        ],
        'EDIT_AFTER_REPLY' => [
            'array' => 'FUD_OPT_3',
            'position' => 1024,
            'description' => 'If enabled, users will be able to edit their own posts, even after someone replied to it. Disable to prevent users from changing questions that are already answered.',
        ],
        'DISABLE_WELCOME_EMAIL' => [
            'array' => 'FUD_OPT_3',
            'position' => 2048,
            'description' => 'If enabled the forum will no longer send welcome e-mails when new users register.',
        ],
        'USE_TEMP_TABLES' => [
            'array' => 'FUD_OPT_3',
            'position' => 4096,
            'description' => 'Use temporary SQL tables to optimize some operations (dependent on the ability to create temp tables).',
        ],
        'USE_ANON_TURING' => [
            'array' => 'FUD_OPT_3',
            'position' => 8192,
            'description' => 'When anonymous users post messages perform captcha test.',
        ],
        'FORUM_NOTIFY_ALL' => [
            'array' => 'FUD_OPT_3',
            'position' => 16384,
            'description' => 'If enabled, subscribers to a forum will receive notifications about every new message in the forum, rather then just new topics in the forum.',
        ],
        'DB_MESSAGE_STORAGE' => [
            'array' => 'FUD_OPT_3',
            'position' => 32768,
            'description' => 'If enabled, regular and private messages will be stored inside the database. If disabled, they will be stored in files on disk. For performance reasons we recommend you keep it disabled, especially on large forums!',
        ],
        'DISABLE_ANON_CACHE' => [
            'array' => 'FUD_OPT_3',
            'position' => 65536,
            'description' => 'Disable sending of page-cache headers to anonymous users, this may solve extended caching by browsers non-complaint with web specs.',
        ],
        'NO_ANON_ACTION_LIST' => [
            'array' => 'FUD_OPT_3',
            'position' => 131072,
            'description' => 'Disable displaying of the action list to anonymous users.',
        ],
        'NO_ANON_WHO_ONLINE' => [
            'array' => 'FUD_OPT_3',
            'position' => 262144,
            'description' => 'Disable displaying of who\'s online to anonymous users.',
        ],
        'ENABLE_GEO_LOCATION' => [
            'array' => 'FUD_OPT_3',
            'position' => 524288,
            'description' => 'Enable country identifier flags beside each message poster, requires import of Geolocation database, see the Geolocation Management control panel for more details.',
        ],
        'DISABLE_NOTIFICATION_EMAIL' => [
            'array' => 'FUD_OPT_3',
            'position' => 1048576,
            'description' => 'Disable the system for sending e-mail notification pertaining to new messages and topics being posted to the forums regardless of the user\'s settings.',
        ],
        'UPDATE_GEOLOC_ON_LOGIN' => [
            'array' => 'FUD_OPT_3',
            'position' => 2097152,
            'description' => 'If enabled then user\'s country (name and flag) will be updated each time they login. This option depends on Geo Location being enabled.',
        ],
        'PLUGINS_ENABLED' => [
            'array' => 'FUD_OPT_3',
            'position' => 4194304,
            'description' => 'Enable/disable forum plugins. Plugins are typically created by third-party developers to extended the default capabilities of FUDforum.',
        ],
        'QUICK_REPLY' => [
            'array' => 'FUD_OPT_3',
            'positions' => '0|8388608|16777216',
            'description' => 'Quick reply display mode: disabled, collapsed or expanded.',
            'options' => "Disabled\nExpanded\nCollapsed"
        ],
        'GRAPHICAL_TURING' => [
            'array' => 'FUD_OPT_3',
            'position' => 33554432,
            'description' => 'Use a graphical captcha instead of a text captcha (only if your PHP has GD support).',
        ],
        'CALENDAR_ENABLED' => [
            'array' => 'FUD_OPT_3',
            'position' => 134217728,
            'description' => 'Enable or disable the forum\'s built-in calendar.',
        ],
        'CALENDAR_SHOW_BIRTHDAYS' => [
            'array' => 'FUD_OPT_3',
            'position' => 268435456,
            'description' => 'Show forum member\'s birthdays on the built-in calendar.',
        ],
        'FORUM_DEBUG' => [
            'array' => 'FUD_OPT_3',
            'positions' => '0|536870912|1073741824',
            'description' => 'Debug the forum. Under normal conditions this should remain disabled.',
            'options' => "Disabled\nDetailed logging\nFull debugging"
        ],
        'THREAD_DUP_CHECK' => [
            'array' => 'FUD_OPT_3',
            'position' => 67108864,
            'description' => 'Double post protection. Prevent users from posting duplicate topics.',
        ],
        'ALLOW_USERID_CHANGES' => [
            'array' => 'FUD_OPT_4',
            'position' => 1,
            'description' => 'Allow users to change their own logins. Disable if you authenticate users externally (i.e. via a plugin).',
        ],
        'ALLOW_PASSWORD_RESET' => [
            'array' => 'FUD_OPT_4',
            'position' => 2,
            'description' => 'Allow users to reset their own passwords.',
        ],
        'KARMA' => [
            'array' => 'FUD_OPT_4',
            'position' => 4,
            'description' => 'Enable karma / user reputation system. If enabled, member can upvote or downvote other members.',
        ],
        'PAGES_ENABLED' => [
            'array' => 'FUD_OPT_4',
            'position' => 8,
            'description' => 'Display pages icon on menu. This setting will automatically be enabled if there are pages that should be shown in the List of Pages.',
        ],
        'BLOG_ENABLED' => [
            'array' => 'FUD_OPT_4',
            'position' => 16,
            'description' => 'Enable the forum\'s blog/ portal page.',
        ],
    ];
    protected $FUD_OPT_1;
    protected $FUD_OPT_2;
    protected $FUD_OPT_3;
    protected $FUD_OPT_4;

    public function __construct($FUD_OPT_1, $FUD_OPT_2, $FUD_OPT_3, $FUD_OPT_4)
    {
        $this->FUD_OPT_1 = $FUD_OPT_1;
        $this->FUD_OPT_2 = $FUD_OPT_2;
        $this->FUD_OPT_3 = $FUD_OPT_3;
        $this->FUD_OPT_4 = $FUD_OPT_4;
    }

    /**
     * @param string $name The name of the option
     *
     * @return int|null
     * @throws ConfigurationException if the option is not set up correctly or does not exist
     */
    public function __get(string $name)
    {
        if (isset(static::$definitions[$name])) {
            if (isset(static::$definitions[$name]['position'])) {
                $optArrName = static::$definitions[$name]['array'];
                return $this->$optArrName & static::$definitions[$name]['position'];
            } elseif (isset($this->definitions[$name]['positions'])) {
                // Evaluate the multiple options
                return null; //TODO: This
            } else {
                throw new ConfigurationException('Option improperly configured.');
            }
        } else {
            throw new ConfigurationException('Unknown Option.');
        }
    }
}
