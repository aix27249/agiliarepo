<?php

Page::loadModule('repository');
Page::loadModule('pkglist');

class Module_pkgsearch extends RepositoryModule {
	static $requires = ['repository', 'pkglist'];
	public function run() {
		$regex = new MongoRegex('/^.*' . $_GET['q'] . '.*$/i');
		$q = $this->db->packages->find(['name' => $regex]);
		return Module_pkglist::getList($q/*, (@$_GET['limit'] ? intval($_GET['limit']) : 50), @$_GET['offset']*/);
	}
}
