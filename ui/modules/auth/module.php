<?php

require_once 'PasswordHash.php';
Page::loadModule('repository');
class Auth extends RepositoryModule {
	private static $user = NULL;

	public static function tryAuth($username, $password, $create_session = true, $limit_per_ip = false) {

		// 1. Check if such user exists in database
		$user = self::db()->users->findOne(['name' => $username]);
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

	public function __construct($user_id = NULL) {
		$user = self::db()->users->findOne(['_id' => new MongoId($user_id)]);
		if (!$user) {
			return NULL;
		}

		$this->___fields['name'] = trim($user['name']);
		$this->___fields['uid'] = trim($user['_id']);
	}
 
	// TODO
	public function can($permission) {
		return true;
	}

	public static function createUser($name, $password) {
		// 0. Check if name and password are not empty
		if (trim($name)==='' || trim($password)==='') return false;
		// 1. Check if such user alreay in DB
		$user = self::db()->users->findOne(['name' => $name]);
		if ($user) return false;

		$hasher = new PasswordHash(8, false);
		$hash = $hasher->HashPassword($password);

		self::db()->users->insert(['name' => $name, 'pass' => $hash]);
		return true;
	}

	public function __get($key) {
		return @$this->___fields[$key];
	}
	public function __set($key, $value) {
		if (in_array($key, ['name', 'uid'], true)) trigger_error("User->$key is read-only");
		$this->___fields[$key] = $value;
	}
}


// Implements user manipulation

class Module_auth extends Module {
	public function run() {
		if (isset($_POST['login']) && isset($_POST['password'])) {
			Auth::tryAuth($_POST['login'], $_POST['password'], true);
		}
	}
}

