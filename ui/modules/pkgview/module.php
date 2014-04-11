<?php
Page::loadModule('repository');

class Module_pkgview extends RepositoryModule {
	public function run() {
		$ret = '';
		$md5 = trim(@$this->page->path[2]);
		if (strlen($md5)!==32) return 'Не указан идентификатор пакета';
		$pkg = $this->db->packages->findOne(['md5' => $md5]);
		$pkgfiles = $this->db->package_files->findOne(['md5' => $md5]);
		$ret .= '<h1>' . $pkg['name'] . '</h1>';
		$ret .= '<div class="description">' . ($pkg['description']!=='' ? $pkg['description'] : $pkg['short_description']) . '</div>';

		$ret .= '<div id="filelist"><ol>';
		foreach($pkgfiles['files'] as $file) {
			$ret .= '<li>' . $file . '</li>';
		}
		$ret .= '</ol></div>';

		return $ret;
	}
}
