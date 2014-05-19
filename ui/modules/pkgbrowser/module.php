<?php

Page::loadModule('repository');
Page::loadModule('pkglist');

class Module_pkgbrowser extends RepositoryModule {
	public function run() {
		if ($this->blockname==='sidebar') return $this->run_sidebar();
		$path = array_slice($this->page->path, 2);
		$query = [];
		if (isset($path[0])) $query['repositories.repository'] = $path[0];
		if (isset($path[1])) $query['repositories.osversion'] = $path[1];
		if (isset($path[2])) $query['repositories.branch'] = $path[2];
		if (isset($path[3])) $query['repositories.subgroup'] = $path[3];

		$newpath = array_merge(['/'], $path);
		$page = intval(@$_GET['page']);
		$limit = (isset($_GET['limit']) ? intval($_GET['limit']) : 50);

		return '<div class="path">' . $this->renderPath($newpath) . '</div>' . Module_pkglist::getList($this->db->packages->find($query), $limit, $page, 'Complex');

	}
	public function run_sidebar() {
		$path = array_slice($this->page->path, 2);
		$query = [];
		if (isset($path[0])) $repository_name = $path[0];
		if (isset($path[1])) $osversion = $path[1];
		if (isset($path[2])) $branch = $path[2];
		if (isset($path[3])) $subgroup = $path[3];
		if (isset($repository_name)) {
			$repository = new Repository($repository_name);
			$ret = '<h2>' . implode($path, '/') . '</h2>';
			$x86 = 'http://' . SiteSettings::$site_url . '/pkgindex/x86/' . implode($path, '/') . '/';
			$x86_64 = 'http://' . SiteSettings::$site_url . '/pkgindex/x86_64/' . implode($path, '/') . '/';
			$ret .= 'x86: <a href="' . $x86 . '">' . $x86 . '</a><br />'; 
			$ret .= 'x86_64: <a href="' . $x86_64 . '">' . $x86_64 . '</a><br />'; 
			return $ret;
		}

	}


}
