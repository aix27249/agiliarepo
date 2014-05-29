<?php

Page::loadModule('repository');
Page::loadModule('auth');

class Module_admin extends AdminModule {
	public static $styles = ['admin.css'];
	public function run() {

		/*if ($this->blockname==='sidebar') return $this->run_sidebar();*/

		$o = [
			'settings' => 'Server settings',
			'users' => 'Users',
			'groups' => 'Groups',
			'repositories' => 'Repositories',
			'setup_variants' => 'Setup variants',
			'actions' => 'Actions',
		];

		$ret = '<h1>Administration</h1>';

		$ret .= '<ul>';
		foreach($o as $link => $title) {
			$ret .= '<li><a href="/admin/' . $link . '">' . $title . '</a></li>';
		}
		$ret .= '</ul>';

		return $ret;
	}

	public function run_sidebar() {
	}
}

class AdminModule extends RepositoryModule {
	public function __construct($page, $blockname) {
		$user = Auth::user();
		if (!$user || !$user->can('admin_panel')) {
			header('HTTP/1.1 403 Forbidden');
			die('Access denied');
		}

		return parent::__construct($page, $blockname);
	}
}
