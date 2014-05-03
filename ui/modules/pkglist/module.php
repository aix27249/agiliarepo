<?php

Page::loadModule('repository');
class Module_pkglist extends RepositoryModule {
	public static $styles = ['pkglist.css'];
	public static function getList($packages, $limit = 50, $page = 0, $type = 'Simple', $show_pagination = true) {
		$offset = intval($page) * intval($limit);
		$limit = intval($limit);
		$method = 'renderItem' . $type;
		if (!method_exists('Module_pkglist', $method)) throw new Exception('List method ' . $method . ' does not exist');
		$ret = '<ul class="pkglist">';
		$counter = 0;
		foreach($packages as $pkg) {
			$counter++;
			if ($offset>=$counter) continue;
			if ($limit > 0 && $limit<($counter - $offset)) break;
			$ret .= self::$method($pkg);
		}
		$ret .= '</ul>';
		if ($show_pagination) {
			$count = $packages->count();
			$pages = ceil($count/$limit);

			$ret .= '<div class="pagination">';
			$ret .= 'Count: ' . $count . ', pages: ' . $pages . '<br />';
			$args = $_GET;
			unset($args['page']);
			unset($args['limit']);
			$eargs = '';
			if (count($args)>0) {
				foreach($args as $key => $value) {
					$eargs .= '&amp;' . $key . '=' . urlencode($value);
				}
			}
			for($i=0; $i<=$pages; $i++) {
				$ret .= '<a href="?page=' . $i . ($limit!==50 ? '&amp;limit=' . $limit : '') . $eargs . '">' . $i . '</a>';
			}
			$ret .= '</div>';
		}
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
		return self::getList($this->db->packages->find()->sort(['add_date' => -1]), (@$_GET['limit'] ? intval($_GET['limit']) : 50), @$_GET['page']);
	}
}
