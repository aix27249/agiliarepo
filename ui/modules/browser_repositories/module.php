<?php

Page::loadModule('repository');
class Module_browser_repositories extends RepositoryModule {
	public function run() {
		$res = $this->db->packages->distinct('repositories.repository');
		if ($res) {
			$ret = '<label for="browser_repositories">Repositories:</label>';
			$ret .= '<ul id="browser_repositories">';
			foreach($res as $repository) {
				$ret .= '<li><a href="/browser/' . $repository . '">' . $repository . '</a></li>';
			}
			$ret .= '</ul>';

			return $ret;
		}
	}
}
