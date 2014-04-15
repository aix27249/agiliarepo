<?php

Page::loadModule('repository');
class Module_browser_subgroups extends RepositoryModule {
	public function run() {
		if (!isset($this->page->path[4])) return;
		$res = $this->db->packages->distinct('subgroup');
		if ($res) {
			$ret = '<label for="browser_subgroups">Subgroups:</label>';
			$ret .= '<ul id="browser_subgroups">';
			foreach($res as $r) {
				$ret .= '<li><a href="/browser/' . $this->page->path[2] . '/' . $this->page->path[3] . '/' . $this->page->path[4] . '/' . $r . '">' . $r . '</a></li>';
			}
			$ret .= '</ul>';

			return $ret;
		}
	}
}
