<?php

Page::loadModule('repository');
class Module_browser_repositories extends RepositoryModule {
	public function run() {
		$repositories = Repository::getList();
		$ret = '<label for="browser_repositories">Repositories:</label>';
		$ret .= '<ul id="browser_repositories">';
		foreach($repositories as $repository) {
			$ret .= '<li><a href="/browser/' . $repository . '">' . $repository . '</a></li>';
		}
		$ret .= '</ul>';

		return $ret;
		
	}
}
