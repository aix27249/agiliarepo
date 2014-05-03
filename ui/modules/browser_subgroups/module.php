<?php

Page::loadModule('repository');
class Module_browser_subgroups extends RepositoryModule {
	public function run() {
		if (!isset($this->page->path[4])) return;
		$repository_name = $this->page->path[2];
		$osversion = $this->page->path[3];
		$branch = $this->page->path[4];
		$repository = new Repository($repository_name);
		$subgroups = $repository->subgroups();

		$ret = '<label for="browser_subgroups">Subgroups:</label>';
		$ret .= '<ul id="browser_subgroups">';
		foreach($subgroups as $r) {
			$ret .= '<li><a href="/browser/' . $repository_name . '/' . $osversion . '/' . $branch . '/' . $r . '">' . $r . '</a></li>';
		}
		$ret .= '</ul>';

		return $ret;
	}
}
