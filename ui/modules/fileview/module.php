<?php

Page::loadModule('repository');
class Module_fileview extends RepositoryModule {
	static $styles = ['fileview.css'];
	public function run() {
		if (!Auth::user()) return '<h1>Sorry, authenticated users only</h1><p>Due to high server load, we limited some functions to authorized users only. Please log in to access this feature.</p> ';

		$ret = '';
		$md5 = trim(@$this->page->path[2]);
		$file = preg_replace('/^\/*/', '', trim(@$_GET['f']));
		if (strlen($md5)!==32) return 'Не указан идентификатор пакета';
		$pkg = $this->db->packages->findOne(['md5' => $md5]);
		if ($file==='') {
			$file = '/';
			$pkgfiles = $this->db->package_files->findOne(['md5' => $md5]);
		}
		else $pkgfiles = $this->db->package_files->findOne(['md5' => $md5, 'files' => $file]);

		$ret .= '<h1>' . $pkg['name'] . ' ' . $pkg['version'] . '-' . $pkg['build'] . ' (' . $pkg['arch'] . ')</h1>';

		$path = array_merge(['/'], explode('/', $file));

		$ret .= '<div class="path">' . $this->renderPath($path, '', '/fileview/' . $md5 . '?f=') . '</div>';

		if (!$pkgfiles) {
			return 'В пакете отсутствует данный файл';
		}


		if (strrpos($file, '/')===strlen($file)-1) {
			$ret .= '<h4>Список файлов</h4>';
			$ret .= '<ol>';
			foreach($pkgfiles['files'] as $pkgfile) {
				if ($file!=='/' && strpos($pkgfile, $file)!==0) continue;
				$ret .= '<li><a href="/fileview/' . $pkg['md5'] . '?f=' . urlencode($pkgfile) . '">' . $pkgfile . '</a></li>';
			}
			$ret .= '</ol>';
			return $ret;

		}


		$pkg_filename = ServerSettings::$root_path . '/' . $pkg['location'] . '/' . $pkg['filename'];
		$tmp = tempnam('/tmp', 'agrepo_fileview');
		TgzHandler::extractFile(ServerSettings::$root_path . '/' . $pkg['location'] . '/' . $pkg['filename'], $file, $tmp);

		// Check if file is binary
		$finfo = finfo_open(FILEINFO_MIME);
		$is_ascii = (substr(finfo_file($finfo, $tmp), 0, 4) == 'text');



		if (!$is_ascii || isset($_GET['raw'])) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.basename($file));
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($tmp));
			readfile($tmp);
			unlink($tmp);
			die();
		}

		else $ret .= '<pre id="fileview">' . file_get_contents($tmp) . '</pre>';

		unlink($tmp);

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

	public static function isBinary($binary) {
		return 1===preg_match('#^(?:[01]{8}){0,12}$#', $binary);
	}


}
