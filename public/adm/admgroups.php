<?php
/**
* copyright            : (C) 2001-2017 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: admgroups.php 6067 2017-09-01 14:51:22Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

	require ('./GLOBALS.php');
	fud_use('adm.inc', true);
	fud_use('widgets.inc', true);
	fud_use('groups_adm.inc', true);
	fud_use('groups.inc');

	require($WWW_ROOT_DISK .'adm/header.php');

	if (!empty($_POST['btn_cancel'])) {
		unset($_POST);
	}

	$edit = isset($_GET['edit']) ? (int)$_GET['edit'] : (isset($_POST['edit']) ? (int)$_POST['edit'] : 0);

	if (isset($_GET['del'])) {
		group_delete((int)$_GET['del']);
		echo successify('Group successfully deleted.');
	}

	$hdr = group_perm_array();
	$error_reason = $error = 0;

	/* Check for errors. */
	if (isset($_POST['btn_submit'])) {
		if (isset($_POST['gr_inherit_id'])) {
			$gr_inherit_id = (int)$_POST['gr_inherit_id'];
		} else {
			$gr_inherit_id = 0;
		}
		if (isset($_POST['gr_resource'])) {
			$gr_resource = is_string($_POST['gr_resource']) ? array($_POST['gr_resource']) : array_unique($_POST['gr_resource']);
		} else {
			$gr_resource = array();
		}
		$gr_ramasks = (int) !empty($_POST['gr_ramasks']);
		$perm = $permi = 0;

		$res = array();

		foreach ($hdr as $k => $v) {
			$val = (int) $_POST[$k];

			if ($val < 0 && !$gr_inherit_id) {
				$error_reason = 'One of your permissions is set to Inherit, however you have not selected a group to inherit from.';
				$error = 1;
			}

			if (empty($_POST['gr_name']) && $edit > 2) {
				$_POST['gr_name'] = q_singleval('SELECT name FROM '. $DBHOST_TBL_PREFIX .'groups WHERE id='. $edit);
			}

			if ($val < 0) {
				$val = $hdr[$k][0];
				$permi |= $val;
				/* Determine what the permission should be. */
				if (!$error) {
					if (!$res) {
						$r = uq('SELECT id, groups_opt, groups_opti, inherit_id FROM '. $DBHOST_TBL_PREFIX .'groups');
						while ($o = db_rowobj($r)) {
							$res[$o->id] = $o;
						}
						unset($c);
					}
					$ih = $gr_inherit_id;
					$ihl = array($edit=>1);
					while (1) {
						if (isset($ihl[$ih])) {
							$error_reason = 'You\'ve created a circular inheritence for "'. $v[1] .'" permission.';
							$error = 1;
							$val = 0;
							break;
						}
						$ihl[$ih] = 1;

						if (!isset($res[$ih])) {
							$error_reason = 'One of your permissions is set to Inherit, but the group it inherits permissions from, does not exist.';
							$error = 1;
							$val = 0;
							break;
						}

						/* Go to the next 'source' group, maybe it has the actual permission. */
						if ($res[$ih]->groups_opti & $val) {
							$ih = $res[$ih]->inherit_id;
							continue;
						}
						$val = $res[$ih]->groups_opt & $val;
						break;
					}
				}
			}
			$perm |= $val;
		}

		if (!$error && !$gr_resource && $edit < 2) {
			$error_reason = 'You must assign at least 1 resource (forum) to this group.';
			$error = 1;
		}

		if (!$error) {
			if (!$edit) { /* Create new group. */
				$rid1 = array_shift($gr_resource);
				$gid = group_add((int)$rid1, $_POST['gr_name'], $gr_ramasks, $perm, $permi, $gr_inherit_id);
				if (!$gid) {
					$error_reason = 'Failed to add group.';
					$error = 1;
				} else {
					if ($gr_resource) {
						foreach ($gr_resource as $v) {
							q('INSERT INTO '. $DBHOST_TBL_PREFIX .'group_resources (resource_id, group_id) VALUES('. (int)$v .', '. $gid .')');
						}
					}

					/* Only rebuild the group cache if the all ANON/REG users were added. */
					if ($gr_ramasks) {
						grp_rebuild_cache(array(0, 2147483647));
					}
					echo successify('Group successfully added.');
				}
			} else if (($frm = q_singleval('SELECT forum_id FROM '. $DBHOST_TBL_PREFIX .'groups WHERE id='. $edit)) !== null) { /* Update an existing group. */
				if (!$res) {
					$old = db_sab('SELECT groups_opt, groups_opti FROM '. $DBHOST_TBL_PREFIX .'groups WHERE id='. $edit);
				} else {
					$old =& $res[$edit];
				}
				group_sync($edit, (isset($_POST['gr_name']) ? $_POST['gr_name'] : null), $gr_inherit_id, $perm, $permi);
				if (!$frm) {
					q('DELETE FROM '. $DBHOST_TBL_PREFIX .'group_resources WHERE group_id='. $edit);
					$aff = db_affected();
					if ($gr_resource) {
						foreach ($gr_resource as $v) {
							q('INSERT INTO '. $DBHOST_TBL_PREFIX .'group_resources (resource_id, group_id) VALUES('. (int)$v .', '. $edit .')');
						}
					}
					echo successify('Group successfully removed.');
				}

				/* Only rebuild caches if the permissions or number of resources had changed. */
				if ($perm != $old->groups_opt || $permi != $old->groups_opti || $aff != count($gr_resource)) {
					rebuild_group_ih($edit, ($perm ^ $old->groups_opt), $perm);
					grp_rebuild_cache();
				}
			}
		}

		/* Restore form values. */
		if ($error) {
			$gr_name = !empty($_POST['gr_name']) ? $_POST['gr_name'] : '';
			$gr_resource = array();
			if (isset($_POST['gr_resource']) && is_array($_POST['gr_resource'])) {
				foreach ($_POST['gr_resource'] as $v) {
					$gr_resource[$v] = $v;
				}
			}
		} else {
			$edit = 0;
			unset($_POST);
		}
	}

	/* Fetch all groups. */
	$gl = array();
	$r = uq('SELECT g.id, g.name AS gn, g.inherit_id, g.groups_opt, g.groups_opti, f.name AS fname, g.forum_id FROM '. $DBHOST_TBL_PREFIX .'groups g LEFT JOIN '. $DBHOST_TBL_PREFIX .'forum f ON f.id=g.forum_id');
	while ($o = db_rowobj($r)) {
		$o = (array) $o;
		$gid = array_shift($o);
		$gl[$gid] = $o;
	}
	unset($r);

	if (!$error) {
		if ($edit && isset($gl[$edit])) {
			$gr_name = $gl[$edit]['gn'];
			$gr_inherit_id = $gl[$edit]['inherit_id'];
			$perm = $gl[$edit]['groups_opt'];
			$permi = $gl[$edit]['groups_opti'];

			/* Handle inheritences. */
			if ($gr_inherit_id) {
				$ip =& $gl[$gr_inherit_id]['groups_opt'];
				foreach ($hdr as $v2) {
					if ($ip & $v2[0]) {
						$perm |= $v2[0];
					} else {
						$perm = $perm &~ $v2[0];
					}
				}
			}
		} else {
			/* Default form values. */
			$gr_ramasks = $gr_name = '';
			$perm = $permi = $gr_inherit_id = 0;
			$gr_resource = array();
		}
	}

	if ($error_reason) {
		echo errorify($error_reason);
	}
?>
<h2>Admin Group Manager</h2>

<h3><?php echo $edit ? '<a name="edit">Edit Group:</a>' : 'Add New Group:'; ?></h3>
<form method="post" action="admgroups.php">
<?php echo _hs; ?>
<input type="hidden" name="edit" value="<?php echo $edit; ?>" />
<table class="datatable">
<tr class="field"><td>Group Name: </td><td>
<?php
	if ($edit && ($edit < 3 || $gl[$edit]['forum_id'])) {
		echo $gr_name;
		echo '<input type="hidden" name="gr_resource" value="1" />';
	} else {
		echo '<input type="text" name="gr_name" value="'.htmlspecialchars($gr_name).'" />';
	}
?>
</td></tr>
<?php
	if (!$edit || $edit > 2) {
		echo '<tr class="field"><td valign="top">Group Resources:';
		echo $edit ? '' : '<br /><font size="-2">(select one or more)</font>';
		echo '</td><td>';
		if ($edit && $gl[$edit]['forum_id']) {
			echo 'FORUM: '.$gl[$edit]['fname'];
		} else {
			echo '<select multiple="multiple" name="gr_resource[]" size="10">';
			if (!isset($_POST['edit']) && $edit) {
				$c = uq('SELECT resource_id FROM '. $DBHOST_TBL_PREFIX .'group_resources WHERE group_id='. $edit);
				while ($r = db_rowarr($c)) {
					$gr_resource[$r[0]] = $r[0];
				}
				unset($c);
			}
			$c = uq('SELECT f.id, f.name, c.name FROM '. $DBHOST_TBL_PREFIX .'forum f INNER JOIN '. $DBHOST_TBL_PREFIX .'cat c ON c.id=f.cat_id ORDER BY c.parent, c.view_order, f.view_order');
			while ($r = db_rowarr($c)) {
				echo '<option value="'. $r[0] .'"'. (isset($gr_resource[$r[0]]) ? ' selected="selected"' : '') .'>'. $r[2] .' &raquo; '. $r[1] .'</option>';
			}
			unset($c);
			echo '</select>';
		}
		echo '</td></tr><tr class="field"><td>Inherit from: </td><td><select name="gr_inherit_id"><option value="0">No where</option>';

		foreach ($gl as $k => $v) {
			if ($k == $edit) continue;
			echo '<option value="'. $k .'" '.($gr_inherit_id == $k ? ' selected="selected"' : '') .'>'. $v['gn'] .'</option>';
		}

		echo '</select></td></tr>';
	}

	if (!$edit) {
		echo '<tr class="field"><td>Anonymous and Registered Masks</td><td>';
		draw_select('gr_ramasks', "No\nYes", "\n1", $gr_ramasks);
		echo '</td></tr>';
	}
?>
<tr class="field"><td valign="top" colspan="2" align="center"><font size="+2"><b>Maximum Permissions</b></font><br /><font size="-1">(group leaders won't be able to assign permissions higher than these)</font></td></tr>
<tr><td colspan="2"><table cellspacing="2" cellpadding="2" border="0" width="100%">
<?php
	if (($edit || $error) && $gr_inherit_id && $permi) {
		echo '<tr class="field"><th nowrap="nowrap"><font size="+1">Permission</font></th><th><font size="+1">Value</font></th><th><font size="+1">Via Inheritance</font></th></tr>';
		$v1 = 1;
	} else {
		echo '<tr class="field"><th nowrap="nowrap"><font size="+1">Permission</font></th><th><font size="+1">Value</font></th></tr>';
		$v1 = 0;
	}

	foreach ($hdr as $k => $v) {
		echo '<tr class="field"><td>'. $v[1] .'</td><td><select name="'. $k .'">';
		if ($v1 && $permi & $v[0]) {
			echo '<option value="-'. $v[0] .'" selected="selected">Inherit</option>';
			echo '<option value="0">No</option><option value="'. $v[0] .'">Yes</option>';
		} else {
			echo '<option value="-'. $v[0] .'">Inherit</option>';
			if ($perm & $v[0]) {
				echo '<option value="0">No</option><option value="'. $v[0] .'" selected="selected">Yes</option>';
			} else {
				echo '<option value="0" selected="selected">No</option><option value="'. $v[0] .'">Yes</option>';
			}
		}
		echo '</select></td>';
		if ($v1) {
			echo '<td align="center">'.($perm & $v[0] ? 'Yes' : 'No').'</td>';
		}
		echo '</tr>';
	}
?>
</table></td></tr>
<tr><td colspan="2" align="left">
<?php
	if ($edit) {
		echo '<input type="submit" name="btn_cancel" value="Cancel" /> ';
	}
?>
<input type="submit" name="btn_submit" value="<?php echo (!$edit ? 'Add' : 'Update'); ?>" /></td></tr>
</table>
<input type="hidden" name="prevloaded" value="1" />
</form>

<h3>Available Groups:</h3>
<span class="linkgroup">The permissions shown below ONLY control the permissions group leaders will be able to change
for the group's they manage. To change the user permissions please use the <a href="../index.php?t=groupmgr&amp;<?php echo __adm_rsid; ?>">user-land group manager</a>.</span>

<table class="datatable fulltable">
<tr class="tiny field">
<td valign="top"><b>Group Name</b></td>
<?php
	$src = array('!\s!', '!([A-Za-z]{1})!\e');
	$dst = array('', '\\1<br />');
	foreach ($hdr as $k => $v) {
		echo '<td align="center" valign="top" title="'.$v[1].'"><b>';
		echo preg_replace_callback('!([^0]{1})!', function($matches) {return strtoupper($matches[1]).'<br />';}, $v[1]);
		echo '</b></td>';
	}
?>
<td valign="top"><b>Leaders</b></td>
<td valign="top" align="center"><b>Actions</b></td>
</tr>
<?php
	/* Fetch all group leaders. */
	$c = uq('SELECT gm.group_id, u.alias FROM '. $DBHOST_TBL_PREFIX .'group_members gm INNER JOIN '. $DBHOST_TBL_PREFIX .'users u ON gm.user_id=u.id WHERE gm.group_members_opt>=131072 AND '. q_bitand('gm.group_members_opt', 131072) .' > 0');
	while ($r = db_rowarr($c)) {
		$gll[$r[0]][] = $r[1];
	}
	unset($c);

	$i = 0;
	foreach ($gl as $k => $v) {
		$i++;
		$bgcolor = ($edit == $k) ? ' class="tiny resultrow3"' : (($i%2) ? ' class="tiny resultrow1"' : ' class="tiny resultrow2"');

		if (isset($gll[$k])) {
			$grl = '<font size="-1">(total: '. count($gll[$k]) .')</font><br /><select name="gr_leaders"><option>'. implode('</option><option>', $gll[$k]) .'</option></select>';
		} else {
			$grl = 'No Leaders';
		}

		$del_link = !$v['forum_id'] ? '[<a href="admgroups.php?del='. $k .'&amp;'. __adm_rsid .'">Delete</a>]' : '';
		$user_grp_mgr = ($k > 2) ? ' '. $del_link .'<br />[<a href="admgrouplead.php?group_id='. $k .'&amp;'. __adm_rsid .'">Manage Leaders</a>] [<a href="../'. __fud_index_name__ .'?t=groupmgr&amp;group_id='. $k .'&amp;'. __adm_rsid .'">Manage Users</a>]' : '';

		echo '<tr'. $bgcolor .'><td><a name="g'. $k .'">'. $v['gn'] .'</a></td>';
		foreach ($hdr as $v2) {
			echo '<td nowrap="nowrap" align="center" title="'. $v2[1] .'">';
			if ($v['inherit_id'] && $v['groups_opti'] & $v2[0]) {
				echo '<a href="#g'. $v['inherit_id'] .'" title="Inheriting permissions from '. $gl[$v['inherit_id']]['gn'] .'">(I: '.($gl[$v['inherit_id']]['groups_opt'] & $v2[0] ? '<font color="green">Y</font>' : '<font color="red">N</font>') .')</a>';
			} else {
				echo ($v['groups_opt'] & $v2[0] ? '<font color="green">Y</font>' : '<font color="red">N</font>');
			}
			echo '</td>';
		}
		echo '<td valign="middle" align="center">'. $grl .'</td> <td nowrap="nowrap">[<a href="admgroups.php?edit='. $k .'&amp;'. __adm_rsid .'#edit">Edit</a>] '. $user_grp_mgr .'</td></tr>';
	}
	if (!$i) {
		echo '<tr class="field"><td colspan="6"><center>No groups found. Define some above.</center></td></tr>';
	}
?>
</table>
<?php require($WWW_ROOT_DISK .'adm/footer.php'); ?>
