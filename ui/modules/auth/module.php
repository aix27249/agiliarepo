<?php

require_once 'PasswordHash.php';
Page::loadModule('repository');
class Auth extends RepositoryModule {
	private static $user = NULL;

	public static function tryAuth($username, $password, $create_session = true, $limit_per_ip = false) {

		// 1. Check if such user exists in database
		$user = self::db()->users->findOne(['name' => $username, 'enabled' => 1]);
		if (!$user) return false;

		// Check password match
		$hasher = new PasswordHash(8, false);
		if (!$hasher->CheckPassword($password, $user['pass'])) return false;

		// Create user session
		if ($create_session) {
			$user_id = trim($user['_id']);
			$session_hash = hash('sha512', $hasher->get_random_bytes(32));
			$ip = $_SERVER['REMOTE_ADDR'];

			// Enable this to limit session only to one browser and IP
			if ($limit_per_ip) {
				self::db()->user_sessions->remove(['uid' => $user_id]);
			}
			
			self::db()->user_sessions->insert(['uid' => trim($user['_id']), 'hash' => $session_hash, 'ip' => $ip, 'start_date' => new MongoDate()]);

			setcookie('uid', $user_id, time()+86400, '/');
			setcookie('hash', $session_hash, time()+86400, '/');
			
			self::$user = new User($user_id);
		}
		return true;
	}


	// Returns currently authorized user, or NULL if not authorized
	public static function user() {
		if (static::$user) {
			return static::$user;
		}

		if (!isset($_COOKIE['uid']) || !isset($_COOKIE['hash'])) return NULL;
		$user_id = $_COOKIE['uid'];
		$hash = $_COOKIE['hash'];
		$ip = $_SERVER['REMOTE_ADDR'];

		$session = self::db()->user_sessions->findOne(['uid' => $user_id, 'hash' => $hash, 'ip' => $ip]);
		if (!$session)  return NULL;

		static::$user = new User($user_id);
		return static::$user;
	}

	public static function logout($all_sessions = false) {
		$user = self::user();
		if (!$user) return;

		// "Safely" delete session: only for specific user and specific IP
		$ip = $_SERVER['REMOTE_ADDR'];
		if ($all_sessions) {
			self::db()->user_sessions->remove(['uid' => $user->uid]);
		}
		else {
			self::db()->user_sessions->remove(['uid' => $user->uid, 'hash' => $_COOKIE['hash'], 'ip' => $ip]);
		}
			
		unset($_COOKIE['uid']);
		unset($_COOKIE['hash']);
		setcookie('uid', NULL, -1, '/');
		setcookie('hash', NULL, -1, '/');

		self::$user = NULL;


	}


}

class User {
	private $___fields = [];
	private static function db() {
		return RepositoryModule::db();
	}

	public function __construct($user_id = NULL, $enabled_only = false) {
		$query = ['_id' => new MongoId($user_id)];
		if ($enabled_only) $query['enabled'] = 1;
		$user = self::db()->users->findOne($query);
		if (!$user) {
			return NULL;
		}

		foreach($user as $key => $value) {
			$this->___fields[$key] = $value;
		}
		$this->___fields['uid'] = trim($user['_id']);
	}

	public static function byName($name, $enabled_only = false) {
		$user = self::db()->users->findOne(['name' => $name]);
		if (!$user) {
			return NULL;
		}
		$user_id = trim($user['_id']);
		return new static($user_id, $enabled_only);

	}
 
	public function __get($key) {
		return @$this->___fields[$key];
	}
	public function __set($key, $value) {
		if (in_array($key, ['name', 'uid'], true)) trigger_error("User->$key is read-only");
		$this->___fields[$key] = $value;
	}

	// TODO
	public function can($permission) {
		// Special case: admin can everuthing.
		if ($this->name==='admin') return true;

		if (is_array($this->permissions) && in_array($permission, $this->permissions, true)) return true;
		if (is_array($this->groups)) {
			foreach($this->groups as $groupname) {
				if (Group::can($groupname, $permission)) return true;
			}
		}
		return false;
	}

	public static function create($name, $password) {
		// 0. Check if name and password are not empty
		if (trim($name)==='' || trim($password)==='') throw new Exception('Name and/or password are empty');
		// 1. Check if such user alreay in DB
		$user = self::db()->users->findOne(['name' => $name]);
		if ($user) throw new Exception('User with name ' . $name . ' already exists');

		$hasher = new PasswordHash(8, false);
		$hash = $hasher->HashPassword($password);

		self::db()->users->insert(['name' => $name, 'pass' => $hash, 'enabled' => 1]);
		return true;
	}

	public static function delete($name) {
		self::db()->users->remove(['name' => $name]);
	}

	public static function setEnabled($name, $enable = 1) {
		$user = self::db()->users->findOne(['name' => $name]);
		if (!$user) throw new Exception('User ' . $name . ' not found');
		$user['enabled'] = ($enable ? 1 : 0);
		self::db()->users->update(['name' => $name], $user);
		if ($user['enabled']===0) {
			self::db()->user_sessions->remove(['uid' => trim($user['_id'])]);
		}
		return true;
	}

	public static function enable($name) {
		return self::setEnabled($name, 1);
	}

	public static function disable($name) {
		return self::setEnabled($name, 0);
	}

	public static function setNewPassword($name, $password) {
		$user = self::db()->users->findOne(['name' => $name]);
		if (!$user) throw new Exception('User ' . $name . ' not found');

		if (trim($password)==='') throw new Exception('Password is empty');
		$hasher = new PasswordHash(8, false);
		$hash = $hasher->HashPassword($password);
		$user['pass'] = $hash;

		self::db()->users->update(['name' => $name], $user);
		return true;
	}

	public static function addGroup($username, $groupname) {
		if (trim($groupname)==='') throw new Exception('Group name is empty');
		$user = self::db()->users->findOne(['name' => $username]);
		if (!$user)  throw new Exception('User not found');


		$user['groups'][] = $groupname;
		$user['groups'] = array_values($user['groups']);

		self::db()->users->update(['name' => $username], $user);
		return true;

	}

	public static function removeGroup($username, $groupname) {
		if (trim($groupname)==='') throw new Exception('Group name is empty');
		$user = self::db()->users->findOne(['name' => $username]);
		if (!$user) throw new Exception('User not found');

		if (isset($user['groups'])) {
			if (($key = array_search($groupname, $user['groups'])) !== false) {
				unset($user['groups'][$key]);
				$user['groups'] = array_values($user['groups']);
				self::db()->users->update(['name' => $username], $user);
			}
		}
		return true;

	}

	public function memberOf($groupname) {
		if (is_array($this->groups) && in_array($groupname, $this->groups, true)) return true;
		return false;
	}

	public static function addPermission($username, $permname) {
		if (trim($permname)==='') throw new Exception('Permission name is empty');
		$user = self::db()->users->findOne(['name' => $username]);
		if (!$user)  throw new Exception('User not found');


		$user['permissions'][] = $permname;
		$user['permissions'] = array_values($user['permissions']);
		self::db()->users->update(['name' => $username], $user);
		return true;

	}

	public static function removePermission($username, $permname) {
		if (trim($permname)==='') throw new Exception('Permission name is empty');
		$user = self::db()->users->findOne(['name' => $username]);
		if (!$user) throw new Exception('User not found');

		if (isset($user['permissions'])) {
			if (($key = array_search($permname, $user['permissions'])) !== false) {
				unset($user['permissions'][$key]);
				self::db()->users->update(['name' => $username], $user);
			}
		}
		return true;

	}

}

class Group {
	private static function db() {
		return RepositoryModule::db();
	}

	public static function addPermission($groupname, $permname) {
		if (trim($permname)==='') throw new Exception('Permission name is empty');
		$permissions = self::db()->group_permissions->findOne(['group' => $groupname]);
		if (!$permissions) $permissions = ['group' => $groupname, 'permissions' => []];


		$permissions['permissions'][] = $permname;
		$permissions['permissions'] = array_values($permissions['permissions']);
		self::db()->group_permissions->update(['group' => $groupname], $permissions, ['upsert' => true]);
		return true;

	}

	public static function removePermission($groupname, $permname) {
		if (trim($permname)==='') throw new Exception('Permission name is empty');
		$permissions = self::db()->group_permissions->findOne(['group' => $groupname]);
		if (!$permissions) return true;

		if (isset($permissions['permissions'])) {
			if (($key = array_search($permname, $permissions['permissions'])) !== false) {
				unset($permissions['permissions'][$key]);
				self::db()->group_permissions->update(['group' => $groupname], $permissions);
			}
		}
		return true;

	}

	public static function can($groupname, $permname) {
		if ($groupname==='admins') return true; // Group 'admins' is special: can everything
		$permissions = self::db()->group_permissions->findOne(['group' => $groupname, 'permissions' => $permname]);
		if ($permissions) return true;
		return false;

	}




}	
// Implements user manipulation

class Module_auth extends Module {
	public function run() {
	}
}

