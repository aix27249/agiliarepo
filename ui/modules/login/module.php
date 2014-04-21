<?php

Page::loadModule('auth');
class Module_login extends Module {
	static $styles = ['login.css'];
	static $scripts = ['login.js'];
	public function run() {
		$error = '';
		$top = '';
		$menu = '';
		if (isset($_POST['login']) && isset($_POST['password'])) {
			$result = Auth::tryAuth($_POST['login'], $_POST['password'], true);
			if (!$result) {
				$error = 'Login and/or password incorrect';
				if (isset($_POST['ajax'])) die($error);
			}
		}

		$user = Auth::user();
		if ($user) {
			if (isset($_POST['ajax'])) die('OK');
			$top = 'Logged in as <b>' . $user->name . '</b>';
			$menu = '';
			if ($user->can('admin_panel')) $menu .= '<a href="/admin">Administration</a>';
			if ($user->can('taskmon')) $menu .= '<a href="/taskmon">Task monitor</a>';
		       	$menu .= '<a href="/logout">Logout</a>';
		}
		else {
			$top = 'Sign in';
			$menu = '<form method="post">
			<input type="text" placeholder="Username" id="login" name="login" /><input type="password" placeholder="Password" id="password" name="password" /><input type="submit" value="Sign in" />
			</form>';
		}

		return '<div id="login_top">' . $top . '</div><div id="login_menu">' . $menu . '</div>';
	}


}
