<?php

Page::loadModule('repository');
Page::loadModule('pkglist');

class Module_pkgbrowser extends RepositoryModule {
	public function run() {
		$path = array_slice($this->page->path, 2);
		$query = [];
		if (isset($path[0])) $query['repository'] = $path[0];
		if (isset($path[1])) $query['osversion'] = $path[1];
		if (isset($path[2])) $query['branch'] = $path[2];
		if (isset($path[3])) $query['subgroup'] = $path[3];

		$newpath = array_merge(['/'], $path);

		return '<div class="path">' . $this->renderPath($newpath) . '</div>' . Module_pkglist::getList($this->db->packages->find($query)/*, (@$_GET['limit'] ? intval($_GET['limit']) : 20), @$_GET['offset']*/);

	}


}
