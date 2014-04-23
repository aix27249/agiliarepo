<?php
Page::loadModule('repository');
Page::loadModule('uicore');
class Module_pkgview extends RepositoryModule {
	static $styles = ['pkgview.css'];
	static $scripts = ['pkgview.js'];
	public function run() {
		$ret = '';
		$md5 = trim(@$this->page->path[2]);
		if (strlen($md5)!==32) return 'Не указан идентификатор пакета';
		$pkg = $this->db->packages->findOne(['md5' => $md5]);
		if (isset($_POST['__submit_form_id'])) die($this->requestDispatcher($_POST, $pkg));
		$pkgfiles = $this->db->package_files->findOne(['md5' => $md5]);

		$paths = [];
		foreach($pkg['repositories'] as $path) {
			$paths[] = implode('/', $path);
		}
		$paths = array_unique($paths);


		$ret .= '<h1>' . $pkg['name'] . '</h1>';

		$ret .= '<div class="infoblock description">' . ($pkg['description']!=='' ? $pkg['description'] : $pkg['short_description']) . '</div>';
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

		$ret .= '<div class="infoblock meta_block">';
		foreach($meta as $title => $data) {
			$ret .= '<div class="meta_row"><div class="meta_title">' . $title . '</div>
				<div class="meta_data">' . $data . '</div>
				</div>';
		}

		$ret .= '</div>';


		$ret .= '<div class="infoblock repository_info"><h3>Repositories</h3><ul>';
		foreach($paths as $p) {
			$ret .= '<li><a href="/browser/' . $p . '">' . $p . '</a></li>';
		}

		$ret .= '</ul></div>';



		// Downloads
		$ret .= '<div class="infoblock download_links"><h3>Download</h3>';
		$ret .= '<div class="download">Package: <a href="http://packages.agilialinux.ru/package_tree/' . $pkg['location'] . '/' . $pkg['filename'] . '">' . $pkg['filename'] . '</a></div>';


		// Build tree link
		$abuild_file = NULL;
		foreach($pkgfiles['files'] as $file) {
			if (strpos($file, 'usr/src/BuildTrees/')===0 && strpos($file, 'build_tree.tar.xz')!==false) {
				$abuild_file = $file;
				break;
			}
		}
		if ($abuild_file) {
			$ret .= '<div class="download">Build tree: <a href="/fileview/' . $pkg['md5'] . '?f=' . urlencode($abuild_file) . '">' . basename($abuild_file) . '</a></div>';
		}
		$ret .= '</div>';

		// Data in tabs
		$tabs = [];





		// Dependencies
		$code = '<ul>';
		foreach($pkg['dependencies'] as $dep) {
			$cond = trim(UiCore::dependParse($dep['condition']));
			// TODO: fix link to search within packages that matches version criteria only.
			$code .= '<li><a href="/search?name=' . urlencode($dep['name']) . '">' . $dep['name'] . '</a>' . ($cond !== '' ? $cond . $dep['version'] : '') . '</li>';
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

		// Edit
		// ...There will be ajax, lots of it
		// Prepare table

		$user = Auth::user();
		if ($user) {
			$table = [];
			foreach($pkg['repositories'] as $path) {
				$args = '\'' . $path['repository'] . '\', \'' . $path['osversion'] . '\', \'' . $path['branch'] . '\', \'' . $path['subgroup'] . '\'';
				$row = [
					implode('/', $path), 
					'<input type="button" value="Copy" onclick="pkgCopy(' . $args . ');" />',
					'<input type="button" value="Move" onclick="pkgMove(' . $args . ');" />',
					'<input type="button" value="Delete" onclick="pkgDelete(' . $args . ');" />',
					];
				$table[] = $row;
			}
			$code = UiCore::table($table);
			if (count($paths)===0) $code .= '<input type="button" value="New location" onclick="pkgCopy();" />';

			$tabs[] = ['title' => 'Edit locations', 'body' => $code];
		}

	
	
		$ret .= UiCore::tabs($tabs);

		return $ret;
	}

	private function requestDispatcher($data, $pkg) {
		$callback = 'process_' . trim($data['__submit_form_id']);
		if (method_exists($this, $callback)) return $this->$callback($data, $pkg);
		return 'Call signature ' . $callback . ' unknown';
	}

	private function process_pkgMoveFormInit($data, $pkg) {
		$user = Auth::user();
		$old_location = [];
		foreach(['repository', 'osversion', 'branch', 'subgroup'] as $k) {
			if (!isset($data[$k])) return $k . ' is mandatory';
			$old_location[$k] = trim($data[$k]);
		}

		$repository = new Repository($data['repository']);
		if (!$repository->can($user, 'write')) return '<h1>Access denied</h1>Sorry, you have no permissions to edit '. $data[$k] . ' repository';

		$fields = [
			'repository' => ['type' => 'select', 'label' => 'Repository', 'options' => Repository::getList($user, 'write'), 'events' => ['onchange' => 'pkgFormUpdate(\'write\');']],
			'osversion' =>  ['type' => 'select', 'label' => 'OS version', 'options' => $repository->osversions($user, 'write'), 'events' => ['onchange' => 'pkgFormUpdate(\'write\');']],
			'branch' =>  ['type' => 'select', 'label' => 'Branch', 'options' => $repository->branches($data['osversion'], $user, 'write'), 'events' => ['onchange' => 'pkgFormUpdate(\'write\');']],
			'subgroup' =>  ['type' => 'select', 'label' => 'Subgroup', 'options' => $repository->subgroups($data['osversion'], $data['branch'], $user, 'write'), 'events' => ['onchange' => 'pkgFormUpdate(\'write\');']],
			];
		
		$code = '<h1>Move package ' . $pkg['name'] . '-' . $pkg['version'] . '-' . $pkg['arch'] . '-' . $pkg['build'] . '</h1>';
		$code .= 'From: ' . implode('/', [$data['repository'], $data['osversion'], $data['branch'], $data['subgroup']]);
		foreach ($fields as $key => $desc) {
			$code .= UiCore::getInput($key, $data[$key], '', $desc);
		}

		$code .= UiCore::getInput('old_location', json_encode($old_location), '', ['type' => 'hidden']);


		$form = UiCore::editForm('pkgMoveFormSave', NULL, $code, '<input type="submit" value="Move" />');

		$ret = '';
		$ret .= $form;

		return $ret;
	}


	private function process_getFormFieldOptions($data, $pkg) {
		$user = Auth::user();
		$permission = $data['permission'];
		$repository = new Repository($data['repository']);
		$ret = [
			'osversion' => $repository->osversions($user, $permission),
			'branch' => $repository->branches($data['osversion'], $user, $permission),
			'subgroup' => $repository->subgroups($data['osversion'], $data['branch'], $user, $permission),
		];

		return json_encode($ret);

	}

	private function process_pkgMoveFormSave($data, $pkg) {
		$user = Auth::user();
		$new_location = [];
		$f = ['repository', 'osversion', 'branch', 'subgroup'];
		foreach($f as $k) {
			if (!isset($data[$k])) return $k . ' is mandatory';
			$new_location[$k] = trim($data[$k]);
		}

		$old_location = json_decode($data['old_location']);

		foreach($pkg['repositories'] as &$location) {
			$match = true;
			foreach($f as $k) {
				if ($location[$k]!==$old_location->$k) {
					$match = false;
					break;
				}
			}
			if ($match) {
				$location = $new_location;

			}
		}


		self::db()->packages->findAndModify(
		['md5' => $pkg['md5'], '_rev' => $pkg['_rev']], 
		[
			'$set' => ['repositories' => $pkg['repositories']], 
			'$inc' => ['_rev' => 1]
		]);


		header('Location: /pkgview/' . $pkg['md5']);

	}

	private function process_pkgCopyFormInit($data, $pkg) {
		$user = Auth::user();
	
		if (isset($data['repository'])) {
			$repository = new Repository($data['repository']);
		}
		else {
			$reps = Repository::getList($user, 'write');
			if (count($reps)===0) return 'Sorry, there are no repositories with write permissions for you';
			$repository = new Repository($reps[0]);
		}

		$fields = [
			'repository' => ['type' => 'select', 'label' => 'Repository', 'options' => Repository::getList($user, 'write'), 'events' => ['onchange' => 'pkgFormUpdate(\'write\');']],
			'osversion' =>  ['type' => 'select', 'label' => 'OS version', 'options' => $repository->osversions($user, 'write'), 'events' => ['onchange' => 'pkgFormUpdate(\'write\');']],
			'branch' =>  ['type' => 'select', 'label' => 'Branch', 'options' => $repository->branches(@$data['osversion'], $user, 'write'), 'events' => ['onchange' => 'pkgFormUpdate(\'write\');']],
			'subgroup' =>  ['type' => 'select', 'label' => 'Subgroup', 'options' => $repository->subgroups(@$data['osversion'], @$data['branch'], $user, 'write'), 'events' => ['onchange' => 'pkgFormUpdate(\'write\');']],
			];

		$code = '<h1>Copy package ' . $pkg['name'] . '-' . $pkg['version'] . '-' . $pkg['arch'] . '-' . $pkg['build'] . ' to:</h1>';

		if (isset($data['repository'])) {
			$code .= 'From: ' . implode('/', [$data['repository'], $data['osversion'], $data['branch'], $data['subgroup']]);
		}
		foreach ($fields as $key => $desc) {
			$code .= UiCore::getInput($key, @$data[$key], '', $desc);
		}



		$form = UiCore::editForm('pkgCopyFormSave', NULL, $code, '<input type="submit" value="' . (isset($data['repository']) ? 'Copy' : 'Create location') . '" />');

		$ret = '';
		$ret .= $form;

		return $ret;
	}

	private function process_pkgCopyFormSave($data, $pkg) {
		$user = Auth::user();
		$new_location = [];
		$f = ['repository', 'osversion', 'branch', 'subgroup'];
		foreach($f as $k) {
			if (!isset($data[$k])) return $k . ' is mandatory';
			$new_location[$k] = trim($data[$k]);
		}

		$match = false;
		foreach($pkg['repositories'] as &$location) {
			$match = true;
			foreach($f as $k) {
				if ($location[$k]!==$new_location[$k]) {
					$match = false;
					break;
				}
			}
		}

		if (!$match) {
			self::db()->packages->findAndModify(
			['md5' => $pkg['md5'], '_rev' => $pkg['_rev']], 
			[
				'$addToSet' => ['repositories' => $new_location], 
				'$inc' => ['_rev' => 1]
			]);
		}


		header('Location: /pkgview/' . $pkg['md5']);

	}


	private function process_pkgDeleteFormSave($data, $pkg) {
		$user = Auth::user();
		$rm_location = [];
		$f = ['repository', 'osversion', 'branch', 'subgroup'];
		foreach($f as $k) {
			if (!isset($data[$k])) return $k . ' is mandatory';
			$rm_location[$k] = trim($data[$k]);
		}


		$newset = [];
		foreach($pkg['repositories'] as &$location) {
			$match = true;
			foreach($f as $k) {
				if ($location[$k]!==$rm_location[$k]) {
					$match = false;
					break;
				}
			}
			if (!$match) {
				$newset[] = $location;
			}
		}


		self::db()->packages->findAndModify(
		['md5' => $pkg['md5'], '_rev' => $pkg['_rev']], 
		[
			'$set' => ['repositories' => $newset], 
			'$inc' => ['_rev' => 1]
		]);


		return 'OK';

	}



}
