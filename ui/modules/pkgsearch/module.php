<?php

Page::loadModule('repository');
Page::loadModule('pkglist');

class Module_pkgsearch extends RepositoryModule {
	public function run() {
		$query = [];
		if (trim(@$_GET['name'])!=='') $query['name'] = trim($_GET['name']);
		else if (trim($_GET['q'])!=='') {
			$get_q = preg_replace('/\+/', '\+', trim($_GET['q']));
			$query['name'] = new MongoRegex('/' . $get_q . '/i');
		}
		if (trim(@$_GET['version'])!=='') {
			$query['version'] = trim($_GET['version']);
		}
		if (trim(@$_GET['arch'])!=='' && trim(@$_GET['arch'])!=='any') {
			$archset = Package::queryArchSet(trim($_GET['arch']));
			$query['arch'] = $archset;
		}
		if (trim(@$_GET['build'])!=='') {
			$query['build'] = trim($_GET['build']);
		}
		if (trim(@$_GET['by'])) {
			$query['maintainer.name'] = trim($_GET['by']);
		}
		if (isset($_GET['latest'])) {
			$query['repositories.latest'] = true;
		}
		foreach(['repository', 'osversion', 'branch', 'subgroup'] as $b) {
			if (isset($_GET[$b])) {
				$query['repositories.' . $b] = trim($_GET[$b]);
			}
		}


		$q = $this->db->packages->find($query)->sort(['add_date' => -1]);
		$page = intval(@$_GET['page']);
		$limit = (isset($_GET['limit']) ? intval($_GET['limit']) : 50);


		$ret = '<h1>Search results</h1>';
		return $ret . Module_pkglist::getList($q, $limit, $page, 'Complex');
	}
}
