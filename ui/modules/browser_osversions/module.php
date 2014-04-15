<?php

Page::loadModule('repository');
class Module_browser_osversions extends RepositoryModule {
	public function run() {
		if (!isset($this->page->path[2])) return;
		$res = $this->db->packages->distinct('osversion');
		if ($res) {
			$ret = '<label for="browser_osversions">OS versions:</label>';
			$ret .= '<ul id="browser_osversions">';
			foreach($res as $r) {
				$ret .= '<li><a href="/browser/' . $this->page->path[2] . '/' . $r . '">' . $r . '</a></li>';
			}
			$ret .= '</ul>';

			return $ret;
		}
	}
}
