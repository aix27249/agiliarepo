<?php
Page::loadModule('repository');

class Module_sidebar_pkginfo extends RepositoryModule {
	static $styles = ['sidebar_pkginfo.css'];
	public function run() {
		$ret = '';
		$md5 = trim(@$this->page->path[2]);
		if (strlen($md5)!==32) return 'Не указан идентификатор пакета';
		$pkg = $this->db->packages->findOne(['md5' => $md5]);
		//$pkgfiles = $this->db->package_files->findOne(['md5' => $md5]);

		$paths = [];
		if (isset($pkg['repository'])) {
			foreach($pkg['repository'] as $rep) {
				if (isset($pkg['osversion'])) {
					foreach($pkg['osversion'] as $osver) {
						if (isset($pkg['branch'])) {
							foreach($pkg['branch'] as $branch) {
								if (isset($pkg['subgroup'])) {
									foreach($pkg['subgroup'] as $subgroup) {
										$path = $rep . '/' . $osver . '/' . $branch . '/' . $subgroup;
										$paths[] = $path;
									}
								}
							}
						}
					}
				}
			}
		}


		foreach($paths as $path) {
			$ret .= '<div class="path">' . $this->renderPath(explode('/', $path), '') . '</div>';
		}

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
			];

		$ret .= '<div class="meta_block">';
		foreach($meta as $title => $data) {
			$ret .= '<div class="meta_row"><div class="meta_title">' . $title . '</div>
				<div class="meta_data">' . $data . '</div>
				</div>';
		}

		$ret .= '</div>';

		$ret .= '<div class="download"><a href="http://packages.agilialinux.ru/package_tree/' . $pkg['location'] . '/' . $pkg['filename'] . '">Download</a></div>';
		//$ret .= '<pre>' . print_r($pkg, true) . '</pre>';

		/*$ret .= '<div id="filelist"><ol>';
		foreach($pkgfiles['files'] as $file) {
			$ret .= '<li><a href="/fileview/' . $pkg['md5'] . '?f=' . urlencode($file) . '">' . $file . '</a></li>';
		}
		$ret .= '</ol></div>';
		 */

		return $ret;
	}

}
