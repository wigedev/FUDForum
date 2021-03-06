<?php
/**
* copyright            : (C) 2001-2018 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: groups_adm.inc 6130 2018-03-31 16:35:42Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

/** Add a new group. */
function group_add($res_id, $name, $add_gl=0, $perm=0, $permi=2147483647, $inherit_id=2)
{
	$tbl =& $GLOBALS['DBHOST_TBL_PREFIX'];

	$gid = db_qid('INSERT INTO '. $tbl .'groups (name, inherit_id, groups_opt, groups_opti, forum_id) VALUES
		('. _esc($name) .', '. $inherit_id .', '. $perm .', '. $permi .', '. ($add_gl == 2 ? $res_id : 0) .')');

	foreach ((array)$res_id as $v) {
		q('INSERT INTO '. $tbl .'group_resources (group_id, resource_id) VALUES ('. $gid .', '. $v .')');
	}

	if ($add_gl) {
		$reg = q_singleval('SELECT groups_opt FROM '. $tbl .'groups WHERE id=2') | 65536;
		$ano = q_singleval('SELECT groups_opt FROM '. $tbl .'groups WHERE id=1') | 65536;

		q('INSERT INTO '. $tbl .'group_members (group_id, user_id, group_members_opt) VALUES('. $gid .', 2147483647, '. $reg .')');
		q('INSERT INTO '. $tbl .'group_members (group_id, user_id, group_members_opt) VALUES('. $gid .', 0, '. $ano .')');
	}

	return $gid;
}

/** Update a group. */
function group_sync($id, $name, $inherit, $perm, $permi)
{
	$name = is_null($name) ? '' : 'name='. _esc($name) .', ';
	if (!$inherit) {
		$permi = 0;
	}
	q('UPDATE '. $GLOBALS['DBHOST_TBL_PREFIX'] .'groups SET '. $name .' inherit_id='. $inherit .', groups_opt='. $perm .', groups_opti='. $permi .' WHERE id='. $id);
}


/** Rebuild group inheritance. */
function rebuild_group_ih($gid, $perm, $perma)
{
	$tbl =& $GLOBALS['DBHOST_TBL_PREFIX'];
	if (isset($GLOBALS['hdr'])) {
		$hdr =& $GLOBALS['hdr'];
	} else {
		$hdr = group_perm_array();
	}

	if ($perm === null) {
		$perma = $perm = q_singleval('SELECT groups_opt FROM '. $tbl .'groups WHERE id='. $gid);
	}

	$gl = array($gid=>0);
	foreach ($gl as $gid => $v) {
		if ($v) continue; /* Prevents circular references. */

		/* Select all groups that inherit permissions from current group. */
		$r = q('SELECT id, groups_opt, groups_opti FROM '. $tbl .'groups WHERE inherit_id='. $gid .' AND '. q_bitand('groups_opti', $perm) .' > 0');
		while ($o = db_rowarr($r)) {
			foreach ($hdr as $p) {
				if ($o[2] & $p[0]) {
					if ($perma & $p[0]) {
						$o[1] |= $p[0];
					} else {
						$o[1] &= ~$p[0];
					}
				}
			}
			q('UPDATE '. $tbl .'groups SET groups_opt='. $o[1] .' WHERE id='. $o[0]);

			if (empty($gl[$o[0]])) {
				$gl[$o[0]] = 0;
			}
		}
		$gl[$gid] = 1;
	}
}

/** Remove a group. */
function group_delete($id)
{
	if ($id < 3) {
		return;
	}

	$tbl =& $GLOBALS['DBHOST_TBL_PREFIX'];

	q('DELETE FROM '. $tbl .'group_resources WHERE group_id='. $id);

	/* Rebuild group manager cache for managers of the removed group. */
	$r = q('SELECT user_id FROM '. $tbl .'group_members WHERE group_id='. $id .' AND group_members_opt>=131072 AND '. q_bitand('group_members_opt', 131072) .' > 0');
	while ($o = db_rowarr($r)) {
		rebuild_group_ldr_cache($o[0]);
	}

	q('DELETE FROM '. $tbl .'group_members WHERE group_id='. $id);
	q('UPDATE '. $tbl .'groups SET groups_opti=0, inherit_id=0 WHERE inherit_id='. $id);

	/* Deal with inheritence. */
	if (!function_exists('grp_rebuild_cache')) {
		fud_use('groups.inc');
	}
	grp_rebuild_cache();

	q('DELETE FROM '. $tbl .'groups WHERE id='. $id);
}

/** Rebuild the group leader cache. */
function rebuild_group_ldr_cache($id)
{
	$tbl =& $GLOBALS['DBHOST_TBL_PREFIX'];

	$g = db_all('SELECT group_id FROM '. $tbl .'group_members WHERE user_id='. $id .' AND group_members_opt>=131072 AND '. q_bitand('group_members_opt', 131072) .' > 0');

	if ($g) {
		q('UPDATE '. $tbl .'users SET group_leader_list='. ssn(serialize($g)).' WHERE id='. $id);
	} else {
		q('UPDATE '. $tbl .'users SET group_leader_list=NULL WHERE id='. $id);
	}
}

?>
