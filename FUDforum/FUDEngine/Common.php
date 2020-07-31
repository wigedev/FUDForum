<?php

namespace FUDEngine;

class Common
{
    protected $dbc;

    public function __construct($dbc)
    {
        $this->dbc = $dbc;
    }

    public function getPrivateMessageCount(): ?string
    {
        if (__fud_real_user__ && F()->options->PM_ENABLED) {	// PM_ENABLED
            $c = q_singleval('SELECT count(*) FROM fud30_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
            switch ($c) {
                case 0:
                    return '\'<li><a href="/index.php?t=pmsg&amp;\'._rsid.\'" title="Private Messaging"><img src="/theme/responsive/images/top_pm.png" alt="" /> Private Messaging</a></li>';
                case 1:
                    return '<li><a href="/index.php?t=pmsg&amp;'._rsid.'" title="Private Messaging"><img src="/theme/responsive/images/top_pm.png" alt="" /> You have <span class="GenTextRed">(1)</span> unread private message)).\'</a></li>';
                default:
                    return '';
            }
        }
        return '';
    }
}
