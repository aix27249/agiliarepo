<?php

Page::loadModule('admin');
Page::loadModule('uicore');

class Module_admin_groups extends AdminModule {
	static $scripts = ['admin_groups.js'];
	public function run() {
		if ($this->blockname==='sidebar') return $this->run_sidebar();
		if (isset($this->page->path[3])) {
			$action = $this->page->path[3];
			$valid_actions = ['edit', 'create'];
			if (in_array($action, $valid_actions, true)) return $this->$action();
		}

		$groups = $this->db->users->distinct('groups');
		$ret = '<h1>Groups</h1>';
		$ret .= '<div class="table">';

		foreach($groups as $group) {
			$ret .= '<div class="table-row">
				<div class="table-cell"><a href="/admin/groups/edit?name=' . urlencode($group) . '">' . $group . '</a></div>
				</div>';
		}
		$ret .= '</div>';

		return $ret;
	}

	public function run_sidebar() {

	}

	public function edit() {
		$groupname = trim($_GET['name']);
		if ($groupname==='') return '<h1>Group not specified</h1>';

	
		// Action backends
		if (isset($_POST['__submit_form_id'])) {
			switch($_POST['__submit_form_id']) {
			case 'add_member':
				try {
					User::addGroup($_POST['username'], $groupname);
				}
				catch (Exception $e) {
					die($e->getMessage());
				}
				die('OK');
				break;

			case 'remove_member':
				try {
					User::removeGroup($_POST['username'], $groupname);
				}
				catch (Exception $e) {
					die($e->getMessage());
				}
				die('OK');
				break;

			case 'add_permission':
				try {
					Group::addPermission($groupname, $_POST['permname']);
				}
				catch (Exception $e) {
					die($e->getMessage());
				}
				die('OK');
				break;

			case 'remove_permission':
				try {
					Group::removePermission($groupname, $_POST['permname']);
				}
				catch (Exception $e) {
					die($e->getMessage());
				}
				die('OK');
				break;


			default:
				die('Unknown action');
			}
		}


		// UI itself
		$ret = '<h1>Group ' . $groupname . '</h1>';

		// Group members
		$ret .= '<h2>Members</h2>';
		$members = self::db()->users->find(['groups' => $groupname]);
		foreach($members as $user) {
			$ret .= '<div class="table-row" id="row_member_' . $user['name'] . '">
				<div class="table-cell"><a href="/admin/users/edit?name=' . $user['name'] . '">' . $user['name']. '</a></div>
				<div class="table-cell"><a href="javascript:removeMember(\'' . $user['name'] . '\');">Remove from group</a></div>
				</div>';

		}
		$ret .= '<div class="add_field"><input type="text" id="add_member_edit" placeholder="Add new member:" /><input type="button" onclick="addMember();" value="Add new member" /></div>';

		// Group permissions
		$ret .= '<h2>Permissions</h2>';
		$permissions = self::db()->group_permissions->findOne(['group' => $groupname]);
		if ($permissions) {
			$ret .= '<div class="table item_table permission_table">';
			foreach($permissions['permissions'] as $permission) {
				$ret .= '<div class="table-row" id="row_permission_' . $permission . '">
					<div class="table-cell">' . $permission . '</div>
					<div class="table-cell"><a href="javascript:removePermission(\'' . $permission . '\');">Remove</a></div>
					</div>';

			}
			$ret .= '</div>';

		}
		else $ret .= '<p>None</p>';

		$ret .= '<div class="add_field"><input type="text" id="add_permission_edit" placeholder="Add new permission:" /><input type="button" onclick="addPermission();" value="Add new permission" /></div>';


	
		$ret .= '<a href="/admin/groups">Back to list</a>';
		return $ret;

		
	}
}
