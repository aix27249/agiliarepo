<?php

Page::loadModule('repository');
class Module_browser_branches extends RepositoryModule {
	public function run() {
		if (!isset($this->page->path[3])) return;
		$res = $this->db->packages->distinct('repositories.branch');
		if ($res) {
			$ret = '<label for="browser_branches">Branches:</label>';
			$ret .= '<ul id="browser_branches">';
			foreach($res as $r) {
				$ret .= '<li><a href="/browser/' . $this->page->path[2] . '/' . $this->page->path[3] . '/' . $r . '">' . $r . '</a></li>';
			}
			$ret .= '</ul>';

			return $ret;
		}
	}
}
