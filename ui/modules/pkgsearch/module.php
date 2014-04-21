<?php

Page::loadModule('repository');
Page::loadModule('pkglist');

class Module_pkgsearch extends RepositoryModule {
	static $requires = ['repository', 'pkglist'];
	public function run() {
		$query = [];
		if (isset($_GET['name'])) $query['name'] = trim($_GET['name']);
		else if (isset($_GET['q'])) {
			$get_q = preg_replace('/\+/', '\+', trim($_GET['q']));
			$query['name'] = new MongoRegex('/' . $get_q . '/i');
		}
		if (isset($_GET['version'])) {
			$query['version'] = trim($_GET['version']);
		}
		if (isset($_GET['arch'])) {
			$query['arch'] = trim($_GET['arch']);
		}
		if (isset($_GET['build'])) {
			$query['build'] = trim($_GET['build']);
		}
		if (isset($_GET['by'])) {
			$query['maintainer.name'] = trim($_GET['by']);
		}
		if (isset($_GET['latest'])) {
			$query['latest'] = 1;
		}


		$q = $this->db->packages->find($query);
		$ret = '<h1>Search results</h1>';
		return $ret . Module_pkglist::getList($q/*, (@$_GET['limit'] ? intval($_GET['limit']) : 50), @$_GET['offset']*/);
	}
}
