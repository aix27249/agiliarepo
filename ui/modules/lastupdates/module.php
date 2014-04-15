<?php
Page::loadModule('repository');
Page::loadModule('pkglist');

class Module_lastupdates extends RepositoryModule {
	public function run() {
		return Module_pkglist::getList($this->db->packages->find(), (@$_GET['limit'] ? intval($_GET['limit']) : 20), @$_GET['offset']);
	}
}
