<?php

Page::loadModule('repository');
class Module_pkglist extends RepositoryModule {
	public function getList($packages, $limit = NULL, $offset = 0) {
		$offset = intval($offset);
		$limit = intval($limit);
		$ret = '<ol>';
		$counter = 0;
		foreach($packages as $pkg) {
			$counter++;
			if ($offset>=$counter) continue;
			if ($limit > 0 && $limit<($counter - $offset)) break;
			$ret .= '<li><a href="/pkgview/' . $pkg['md5'] . '">' . $pkg['name'] . '-' . $pkg['version'] . '-' . $pkg['build'] . ' (' . $pkg['arch'] . ')</a></li>';
		}
		$ret .= '</ol>';
		return $ret;


	}

	public function run() {
		return $this->getList($this->db->packages->find(), (@$_GET['limit'] ? intval($_GET['limit']) : 20), @$_GET['offset']);
	}
}
