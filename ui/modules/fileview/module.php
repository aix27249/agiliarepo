<?php

Page::loadModule('repository');
class Module_fileview extends RepositoryModule {
	static $styles = ['fileview.css'];
	public function run() {

		$ret = '';
		$md5 = trim(@$this->page->path[2]);
		$file = preg_replace('/^\//', '', trim(@$_GET['f']));
		if (strlen($md5)!==32) return 'Не указан идентификатор пакета';
		$pkg = $this->db->packages->findOne(['md5' => $md5]);
		$pkgfiles = $this->db->package_files->findOne(['md5' => $md5, 'files' => $file]);

		$ret .= '<h1>' . $pkg['name'] . ' ' . $pkg['version'] . '-' . $pkg['build'] . ' (' . $pkg['arch'] . ')</h1>';
		$ret .= '<div class="path">' . $this->renderPath(explode('/', $file), '', '/fileview/' . $md5 . '?f=') . '</div>';

		if (!$pkgfiles) {
			return 'В пакете отсутствует данный файл';
		}


		if (strrpos($file, '/')===strlen($file)-1) {
			$ret .= '<ol>';
			foreach($pkgfiles['files'] as $pkgfile) {
				if (strpos($pkgfile, $file)!==0) continue;
				$ret .= '<li><a href="/fileview/' . $pkg['md5'] . '?f=' . urlencode($pkgfile) . '">' . $pkgfile . '</a></li>';
			}
			$ret .= '</ol>';
			return $ret;

		}

	
		$pkg_filename = SiteSettings::$root_path . '/' . $pkg['location'] . '/' . $pkg['filename'];

		if (isset($_GET['raw'])) {
			TgzHandler::passFile(SiteSettings::$root_path . '/' . $pkg['location'] . '/' . $pkg['filename'], $file);

			die();
		}

		$data = TgzHandler::readFile(SiteSettings::$root_path . '/' . $pkg['location'] . '/' . $pkg['filename'], $file);
		$ret .= '<pre id="fileview">' . $data . '</pre>';

		return $ret;



	}

	// Move it from here in future to some class like RepositoryUiElements
	public function renderPath($path, $delimiter = '', $prefix = '') {
		$ret = '';
		$prev = '';
		for ($i=0; $i<count($path); $i++) {
			$k = $path[$i];
			if ($k==='') break;
			if ($i===count($path)-1) $prev .= $k;
			else $prev .= $k . '/';
			$ret .= '<a href="' . $prefix . $prev . '">' . $k . '</a>' . $delimiter;
		}
		return $ret;
	}


}
