<?php

Page::loadModule('auth');
class Module_logout extends Module {
	public function run() {
		Auth::logout();
		header('Location: /');
	}


}
