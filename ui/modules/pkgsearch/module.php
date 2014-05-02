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
		if (isset($_GET['latest_only'])) {
			$query['repositories.latest'] = true;
		}


		$q = $this->db->packages->find($query)->sort(['add_date' => -1]);
		$ret = '<h1>Search results</h1>';
		return $ret . Module_pkglist::getList($q/*, (@$_GET['limit'] ? intval($_GET['limit']) : 50), @$_GET['offset']*/);
	}
}
