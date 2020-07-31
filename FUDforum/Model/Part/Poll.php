<?php

namespace Model\Part;

class Poll
{
    /* Handle poll votes if any are present. */
    public static function register_vote(&$options, $poll_id, $opt_id, $mid)
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
}
