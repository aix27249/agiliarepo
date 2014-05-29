<?php

Page::loadModule('repository');
class Module_auth extends Module {
	public function run() {
		$this->cookie_auth();
	}

	public function cookie_auth() {
		if (!isset($_COOKIE['uid']) || !isset($_COOKIE['hash'])) return NULL;
		$user_id = $_COOKIE['uid'];
		$hash = $_COOKIE['hash'];
		$ip = $_SERVER['REMOTE_ADDR'];
		Auth::try_login($user_id, $hash, $ip);

	}

}

