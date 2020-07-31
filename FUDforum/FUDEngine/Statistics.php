<?php

namespace FUDEngine;

use PDO;

/**
 * Class Statistics
 *
 * @package FUDEngine
 */
class Statistics
{
    protected $variables;

    public function __construct($variables)
    {
        $this->variables = $variables;
    }

    public function generateStatistics()
    {
        $counts = [
            'avatar_count' => $this->getAvatarCount(),
            'reported_count' => $this->getReportedCount(),
            'thread_exchange_count' => $this->getThreadExchangeRequestCount(),
            'account_approval_count' => $this->getAccountApprovalCount(),
            'moderation_count' => $this->getMessageApprovalCount(),
            'private_message_count' => $this->getPrivateMessageCount(),
        ];
        // If all the values are null return an empty array
        foreach ($counts as $count) {
            if (!is_null($count)) {
                return $counts;
            }
        }
        return [];
    }

    protected function getAvatarCount()
    {
        if ($this->variables['IS_ADMIN']) {
            if ($this->variables['FUD_OPT_1'] & 32) {
                return DB::i()->q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=16777216 AND '. DB::i()->q_bitand('users_opt', 16777216) .' > 0');
            }
        }
        return null;
    }

    protected function getReportedCount()
    {
        if ($this->variables['IS_ADMIN']) {
            return DB::i()->q_singleval('SELECT count(*) FROM fud30_msg_report');
        } elseif ($this->variables['IS_MANAGER']) {
            return DB::i()->q_singleval("SELECT count(*) FROM fud30_msg_report mr INNER JOIN fud30_msg m ON mr.msg_id=m.id INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_mod mm ON t.forum_id=mm.forum_id AND mm.user_id=". _uid);
        }
        return null;
    }

    protected function getThreadExchangeRequestCount()
    {
        if ($this->variables['IS_ADMIN']) {
            return DB::i()->q_singleval('SELECT count(*) FROM fud30_thr_exchange');
        } elseif ($this->variables['IS_MANAGER']) {
            return DB::i()->q_singleval('SELECT count(*) FROM fud30_thr_exchange te INNER JOIN fud30_mod m ON m.user_id='. _uid .' AND te.frm=m.forum_id');
        }
        return null;
    }

    protected function getAccountApprovalCount()
    {
        if ($this->variables['IS_ADMIN'] && $this->variables['FUD_OPT_2'] & 1024) {
            return DB::i()->q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND '. DB::i()->q_bitand('users_opt', 2097152) .' > 0 AND id > 0');
        } elseif ($this->variables['usr']->users_opt & 268435456 && $this->variables['FUD_OPT_2'] & 1024) {
            return DB::i()->q_singleval('SELECT count(*) FROM fud30_users WHERE users_opt>=2097152 AND '. DB::i()->q_bitand('users_opt', 2097152) .' > 0 AND id > 0');
        }
        return null;
    }

    protected function getMessageApprovalCount()
    {
        if ($this->variables['IS_ADMIN']) {
            return DB::i()->q_singleval('SELECT count(*) FROM fud30_msg m INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_forum f ON t.forum_id=f.id WHERE m.apr=0 AND f.forum_opt>=2');
        } elseif ($this->variables['IS_MANAGER']) {
            return DB::i()->q_singleval('SELECT count(*) FROM fud30_msg m INNER JOIN fud30_thread t ON m.thread_id=t.id INNER JOIN fud30_forum f ON t.forum_id=f.id INNER JOIN fud30_mod mm ON f.id=mm.forum_id AND mm.user_id='. _uid. ' WHERE m.apr=0 AND f.forum_opt>=2');
        }
        return null;
    }

    protected function getPrivateMessageCount()
    {
        if (__fud_real_user__ && $this->variables['PRIVATE_MESSAGES_ENABLED']) {    // PM_ENABLED
            return DB::i()->q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id=' . _uid . ' AND fldr=1 AND read_stamp=0');
        } else {
            return null;
        }
    }


}
