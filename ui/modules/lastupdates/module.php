<?php
Page::loadModule('repository');
Page::loadModule('pkglist');

class Module_lastupdates extends RepositoryModule {
	public function run() {
		$ret = '<h1>Last ' . (@$_GET['limit'] ? intval($_GET['limit']) : 50) . ' updates</h1>';
		$ret .= Module_pkglist::getList($this->db->packages->find()->sort(['add_date' => -1]), (@$_GET['limit'] ? intval($_GET['limit']) : 50), @$_GET['offset']);
		return $ret;
	}
}
