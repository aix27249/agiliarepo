<?php

Page::loadModule('admin');
Page::loadModule('uicore');

class Module_admin_users extends AdminModule {
	static $scripts = ['admin_users.js'];
	public function run() {
		if ($this->blockname==='sidebar') return $this->run_sidebar();
		if (isset($this->page->path[3])) {
			$action = $this->page->path[3];
			$valid_actions = ['edit', 'create'];
			if (in_array($action, $valid_actions, true)) return $this->$action();
		}

		$users = $this->db->users->find()->sort(['name']);
		$ret = '<h1>Users</h1>';
		$ret .= '<div class="table">';

		foreach($users as $user) {
			$ret .= '<div class="table-row">
				<div class="table-cell"><a href="/admin/users/edit?name=' . urlencode($user['name']) . '">' . $user['name'] . '</a></div>
				</div>';
		}
		$ret .= '</div>';

		return $ret;
	}

	public function run_sidebar() {
		$ret = '<h1>Users</h1>';
		$ret .= '<ul>';
		$ret .= '<li><a href="/admin/users/create">New user</a></li>';
		$ret .= '</ul>';
		return $ret;
	}

	public function create() {
		$error = '';
		if (isset($_POST['__submit_form_id']) && $_POST['__submit_form_id']==='create_user') {
			try {
				User::create(trim($_POST['username']), trim($_POST['password']));
			}
			catch (Exception $e) {
				$error = $e->getMessage();
			}
			if ($error==='') {
				header('Location: /admin/users/edit?name=' . urlencode(trim($_POST['username'])));
				die();
			}
		}
		$fields = [
			'username' => ['type' => 'text', 'label' => 'Username', 'placeholder' => 'Username'],
			'password' => ['type' => 'password', 'label' => 'Password', 'placeholder' => 'Password'],
			];

		$values = [
			'username' => trim(@$_POST['username']),
			'password' => '',
			];
		$ret = '<h1>Create new user</h1>';
		if ($error!=='') $ret .= '<div class="error">' . $error . '</div>';
		$code = '';
		foreach($fields as $key => $desc) {
			$code .= UiCore::getInput($key, $values[$key], '', $desc);
		}

		$ret .= UiCore::editForm('create_user', NULL, $code);
		return $ret;
		// form: name, password, submit

	}

	public function edit() {
		$username = trim($_GET['name']);
		if ($username==='') return '<h1>User not specified</h1>';
		$user = User::byName($username);
		if (!$user) return '<h1>No such user: ' . $username . '</h1>';

		// Action backends
		if (isset($_POST['__submit_form_id'])) {
			switch($_POST['__submit_form_id']) {
			case 'add_group':
				try {
					User::addGroup($username, $_POST['groupname']);
				}
				catch (Exception $e) {
					die($e->getMessage());
				}
				die('OK');
				break;

			case 'remove_group':
				try {
					User::removeGroup($username, $_POST['groupname']);
				}
				catch (Exception $e) {
					die($e->getMessage());
				}
				die('OK');
				break;

			case 'add_permission':
				try {
					User::addPermission($username, $_POST['permname']);
				}
				catch (Exception $e) {
					die($e->getMessage());
				}
				die('OK');
				break;

			case 'remove_permission':
				try {
					User::removePermission($username, $_POST['permname']);
				}
				catch (Exception $e) {
					die($e->getMessage());
				}
				die('OK');
				break;

			case 'enable_user':
				try {
					User::setEnabled($username, intval($_POST['enable']));
				}
				catch (Exception $e) {
					die($e->getMessage());
				}
				die('OK');
				break;
			case 'change_password':
				try {
					User::setNewPassword($username, $_POST['password']);
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
		$ret = '<h1>Edit user: ' . $username . '</h1>';

		// Change group membership (checkboxes)
		$ret .= '<h2>Group membership</h2>';
		if (is_array($user->groups)) {
			$ret .= '<div class="table item_table group_table">';
			foreach($user->groups as $group) {
				$ret .= '<div class="table-row" id="row_group_' . $group . '">
					<div class="table-cell"><a href="/admin/groups/edit?name=' . $group . '">' . $group . '</a></div>
					<div class="table-cell"><a href="javascript:removeGroup(\'' . $group . '\');">Remove</a></div>
					</div>';

			}
			$ret .= '</div>';

		}
		else $ret .= '<p>None</p>';

		$ret .= '<div class="add_field"><input type="text" id="add_group_edit" placeholder="Add to group:" /><input type="button" onclick="addGroup();" value="Add to group" /></div>';
		// Add/remove personal permissions
		$ret .= '<h2>Permissions</h2>';
		if (is_array($user->permissions)) {
			$ret .= '<div class="table item_table permission_table">';
			foreach($user->permissions as $permission) {
				$ret .= '<div class="table-row" id="row_permission_' . $permission . '">
					<div class="table-cell">' . $permission . '</div>
					<div class="table-cell"><a href="javascript:removePermission(\'' . $permission . '\');">Remove</a></div>
					</div>';

			}
			$ret .= '</div>';

		}
		else $ret .= '<p>None</p>';

		$ret .= '<div class="add_field"><input type="text" id="add_permission_edit" placeholder="Add new permission:" /><input type="button" onclick="addPermission();" value="Add new permission" /></div>';


		// Enable/disable user
		$ret .= '<h2>Access</h2>';
		if ($user->enabled) $ret .= '<p>User is <b>enabled</b>. <input type="button" onclick="enableUser(0);" value="Disable" /></p>';
		else $ret .= '<p>User is disabled. <input type="button" onclick="enableUser(1);" value="Enable" /></p>';
		// Change password
		$ret .= '<h2>Change password</h2>';

		$fields = [
			'password' => ['type' => 'password', 'label' => 'New password', 'placeholder' => 'New password'],
			'repeat_password' => ['type' => 'password', 'label' => 'Repeat password', 'placeholder' => 'Repeat password'],
			];

		$code = '';
		foreach($fields as $key => $desc) {
			$code .= UiCore::getInput($key, '', '', $desc);
		}

		$ret .= UiCore::editForm('change_password', NULL, $code);

		
		$ret .= '<a href="/admin/users">Back to list</a>';
		return $ret;

		
	}
}
