<?php
Page::loadModule('repository');
Page::loadModule('uicore');
class Module_pkgview extends RepositoryModule {
	static $styles = ['pkgview.css'];
	public function run() {
		$ret = '';
		$md5 = trim(@$this->page->path[2]);
		if (strlen($md5)!==32) return 'Не указан идентификатор пакета';
		$pkg = $this->db->packages->findOne(['md5' => $md5]);
		$pkgfiles = $this->db->package_files->findOne(['md5' => $md5]);

		$paths = [];
		foreach($pkg['repositories'] as $path) {
			$paths[] = implode('/', $path);
		}
		$paths = array_unique($paths);


		/*
		foreach($paths as $path) {
			$ret .= '<div class="path">' . $this->renderPath(explode('/', $path), '') . '</div>';
		}*/

		$ret .= '<h1>' . $pkg['name'] . '</h1>';

		$ret .= '<div class="description">' . ($pkg['description']!=='' ? $pkg['description'] : $pkg['short_description']) . '</div>';
		$tags = implode(', ', $pkg['tags']);
		$meta = [
			'version' => $pkg['version'],
			'build' => $pkg['build'],
			'architecture' => $pkg['arch'],
			'add_date' => date('Y-m-d H:i', @$pkg['add_date']->sec),
			'tags' => $tags,
			'md5' => $pkg['md5'],
			'package size' => UI::humanizeSize($pkg['compressed_size']),
			'uncompressed' => Ui::humanizeSize($pkg['installed_size']),
			'add_date' => date('Y-m-d H:i:s', $pkg['add_date']->sec),
			];

		$ret .= '<div class="meta_block">';
		foreach($meta as $title => $data) {
			$ret .= '<div class="meta_row"><div class="meta_title">' . $title . '</div>
				<div class="meta_data">' . $data . '</div>
				</div>';
		}

		$ret .= '</div>';

		$ret .= '<div class="download"><a href="http://packages.agilialinux.ru/package_tree/' . $pkg['location'] . '/' . $pkg['filename'] . '">Download ' . $pkg['filename'] . '</a></div>';


		// Data in tabs
		$tabs = [];





		// Dependencies
		$code = '<ul>';
		foreach($pkg['dependencies'] as $dep) {
			$cond = trim(UiCore::dependParse($dep['condition']));
			// TODO: fix link to search within packages that matches version criteria only.
			$code .= '<li><a href="/search?q=' . urlencode($dep['name']) . '">' . $dep['name'] . '</a>' . ($cond !== '' ? $cond . $dep['version'] : '') . '</li>';
		}
		$code .= '</ul>';

		$tabs[] = ['title' => 'Dependencies', 'body' => $code];
	

		// Filelist
		$code = '<div id="filelist"><ol>';
		foreach($pkgfiles['files'] as $file) {
			$code .= '<li><a href="/fileview/' . $pkg['md5'] . '?f=' . urlencode($file) . '">' . $file . '</a></li>';
		}
		$code .= '</ol></div>';

		$tabs[] = ['title' => 'Files', 'body' => $code];

	
		// Repository locations
		$code = '<ul>';
		foreach($paths as $p) {
			$code .= '<li><a href="/browser/' . $p . '">' . $p . '</a></li>';
		}

		$code .= '</ul>';
		$tabs[] = ['title' => 'Repositories', 'body' => $code];
	
		$ret .= UiCore::tabs($tabs);

		return $ret;
	}

}
