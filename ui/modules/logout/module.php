<?php

Page::loadModule('auth');
class Module_logout extends Module {
	public function run() {
		$ip = $_SERVER['REMOTE_ADDR'];
		$hash = @$_COOKIE['hash'];
		$all_sessions = false;
		if (isset($_GET['all'])) $all_sessions = true;
		Auth::logout($all_sessions, $ip, $hash);
			
		unset($_COOKIE['uid']);
		unset($_COOKIE['hash']);
		setcookie('uid', NULL, -1, '/');
		setcookie('hash', NULL, -1, '/');

		header('Location: /');
	}


}
