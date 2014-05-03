<?php

Page::loadModule('repository');
class Module_pkglist extends RepositoryModule {
	public static $styles = ['pkglist.css'];
	public static function getList($packages, $limit = 50, $page = 0, $type = 'Simple', $show_pagination = true, $base_repository = NULL) {
		$offset = intval($page) * intval($limit);
		$limit = intval($limit);
		$method = 'renderItem' . $type;
		if (!method_exists('Module_pkglist', $method)) throw new Exception('List method ' . $method . ' does not exist');
		$ret = '<ul class="pkglist pkglist_' . $type . '">';
		$counter = 0;
		foreach($packages as $pkg) {
			$package = new Package($pkg);
			$counter++;
			if ($offset>=$counter) continue;
			if ($limit > 0 && $limit<($counter - $offset)) break;
			$ret .= self::$method($package, $base_repository);
		}
		$ret .= '</ul>';
		if ($show_pagination) {
			$count = $packages->count();
			$pages = ceil($count/$limit);

			$ret .= '<div class="pagination">';
			$ret .= 'Total: ' . $count . ' packages' . ($pages>1 ? ' at ' . $pages . ' pages' : '') . '<br />';
			$args = $_GET;
			unset($args['page']);
			unset($args['limit']);
			$eargs = '';
			if (count($args)>0) {
				foreach($args as $key => $value) {
					$eargs .= '&amp;' . $key . '=' . urlencode($value);
				}
			}
			if ($pages>1) {
				$ret .= 'Go to page: ';
				for($i=0; $i<$pages; $i++) {
					$ret .= '<a href="?page=' . $i . ($limit!==50 ? '&amp;limit=' . $limit : '') . $eargs . '">' . $i . '</a>';
				}
			}
			$ret .= '</div>';
		}
		return $ret;


	}

	private static function renderItemComplex($package, $base_repo = NULL) {
		$osversions = [];
		$branches = [];
		foreach($package->repositories as $path) {
			if ($base_repo!==NULL && $path['repository']!==$base_repo) continue;
			$osversions[] = $path['osversion'];
			$branches[] = $path['branch'] . '/' . $path['subgroup'];
		}
		$osversions = implode(', ', array_unique($osversions));
		$branches = implode(', ', array_unique($branches));
		$added_by = $package->added_by;

		$tagstack = [];
		foreach($package->tags as $tag) {
			if (strpos($tag, '-')===false) $tagstack[] = $tag;
		}
		/*foreach($package->tags as $tag) {
			if (strpos($tag, '-')!==false) $tagstack[] = $tag;
		}*/

		$tags = implode(', ', $tagstack);

		if (trim($added_by)==='') $added_by = $package->maintainer['name'];
		return '<li><a class="pkglink" href="/pkgview/' . $package->md5 . '">
			<div class="left">
				<div class="title"><span class="title_highlight">' . $package->name . '</span> ' . $package->version . '-' . $package->arch . '-' . $package->build . '</div>
				<div class="smalldesc">' . $package->short_description . '</div>
				<div class="added">Added: ' . date('d.m.Y H:i', $package->add_date->sec) . ' by ' . $added_by . '</div>
			</div>
			<div class="right">
				<div class="osversion">' . $osversions . '</div>
				<div class="branch">' . $branches . '</div>
				<div class="tags">' . $tags . '</div>
			</div>
			</a></li>';

	}

	private static function renderItemSimple($package, $base_repo = NULL) {
		return '<li><a class="pkglink" href="/pkgview/' . $package->md5 . '">' . $package . '</a></li>';

	}

	public function run() {
		return self::getList($this->db->packages->find()->sort(['add_date' => -1]), (@$_GET['limit'] ? intval($_GET['limit']) : 50), @$_GET['page']);
	}
}
