<?php

Page::loadModule('repository');
class Module_browser_branches extends RepositoryModule {
	public function run() {
		if (!isset($this->page->path[3])) return;
		$repository_name = $this->page->path[2];
		$osversion = $this->page->path[3];
		$repository = new Repository($repository_name);
		$branches = $repository->branches();
		$ret = '<label for="browser_branches">Branches:</label>';
		$ret .= '<ul id="browser_branches">';
		foreach($branches as $r) {
			$ret .= '<li><a href="/browser/' . $repository_name . '/' . $osversion . '/' . $r . '">' . $r . '</a></li>';
		}
		$ret .= '</ul>';

		return $ret;
		
	}
}
