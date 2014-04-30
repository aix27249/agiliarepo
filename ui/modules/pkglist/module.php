<?php

Page::loadModule('repository');
class Module_pkglist extends RepositoryModule {
	public static $styles = ['pkglist.css'];
	public static function getList($packages, $limit = NULL, $offset = 0) {
		$offset = intval($offset);
		$limit = intval($limit);
		$ret = '<ul class="pkglist">';
		$counter = 0;
		foreach($packages as $pkg) {
			$counter++;
			if ($offset>=$counter) continue;
			if ($limit > 0 && $limit<($counter - $offset)) break;
			$ret .= self::renderItemSimple($pkg);
		}
		$ret .= '</ul>';
		return $ret;


	}

	private static function renderItemComplex($pkg, $base_repo = NULL) {
		$paths = [];
		foreach($pkg['repositories'] as $path) {

			if ($base_repo && $path['repository']!==$base_repo) continue;
			$paths[] = implode('/', $path);
		}
		$path_links = implode(', ', $paths);
		return '<li><a class="pkglink" href="/pkgview/' . $pkg['md5'] . '">
			<div class="title">' . $pkg['name'] . '-' . $pkg['version'] . '-' . $pkg['arch'] . '-' . $pkg['build'] . '</div>
			<div class="repos">' . $path_links . '</div>
			</a></li>';

	}

	private static function renderItemSimple($pkg) {
		$paths = [];
		foreach($pkg['repositories'] as $path) {
			$paths[] = implode('/', $path);
		}
		$path_links = implode(', ', $paths);
		return '<li><a class="pkglink" href="/pkgview/' . $pkg['md5'] . '">' . $pkg['name'] . '-' . $pkg['version'] . '-' . $pkg['arch'] . '-' . $pkg['build'] . '</a></li>';

	}

	public function run() {
		return self::getList($this->db->packages->find()->sort(['add_date' => -1]), (@$_GET['limit'] ? intval($_GET['limit']) : 20), @$_GET['offset']);
	}
}
