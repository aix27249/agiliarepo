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
		$package = new Package($md5);
		if (isset($_POST['__submit_form_id'])) die($this->requestDispatcher($_POST, $package));
		$pkgfiles = $package->packageFiles();//$this->db->package_files->findOne(['md5' => $md5]);

		$paths = [];
		foreach($package->repositories as $path) {
			unset($path['latest']);
			$paths[] = implode('/', $path);
		}
		$paths = array_unique($paths);


		$ret .= '<h1>' . $package . '</h1>';

		$ret .= '<div class="infoblock description">' . ($package->description!=='' ? $package->description : $package->short_description) . '</div>';
		$tags = implode(', ', $package->tags);
		$meta = [
			'version' => $package->version,
			'build' => $package->build,
			'architecture' => $package->arch,
			'add_date' => date('Y-m-d H:i', $package->add_date->sec),
			'added_by' => $package->added_by,
			'tags' => $tags,
			'md5' => $package->md5,
			'package size' => UI::humanizeSize($package->compressed_size),
			'uncompressed' => Ui::humanizeSize($package->installed_size),
			'provides' => (is_array($package->provides) ? implode(', ', $package->provides) : ''),
			'conflicts' => (is_array($package->conflicts) ? implode(', ', $package->conflicts) : ''),
			'config_files' => (is_array($package->config_files) ? implode(', ', $package->config_files) : ''),
			];

		$ret .= '<div class="infoblock meta_block">';
		foreach($meta as $title => $data) {
			$ret .= '<div class="meta_row"><div class="meta_title">' . $title . '</div>
				<div class="meta_data">' . $data . '</div>
				</div>';
		}

		$ret .= '</div>';


		$ret .= '<div class="infoblock repository_info"><h3>Repositories</h3><ul>';
		foreach($package->repositories as $path) {
			$latest = @$path['latest'];
			unset($path['latest']);
			$p = implode('/', $path);
			$ret .= '<li><a href="/browser/' . $p . '">' . $p . '</a>' . ($latest ? ' (latest)' : '') . '</li>';
		}

		$ret .= '</ul></div>';



		// Downloads
		$ret .= '<div class="infoblock download_links"><h3>Download</h3>';
		$ret .= '<div class="download">Package: <a href="' . SiteSettings::$web_root_path . '/' . $package->location . '/' . $package->filename . '">' . $package->filename . '</a></div>';


		// Build tree link
		$abuild_file = NULL;
		foreach($pkgfiles['files'] as $file) {
			if (strpos($file, 'usr/src/BuildTrees/')===0 && strpos($file, 'build_tree.tar.xz')!==false) {
				$abuild_file = $file;
				break;
			}
		}
		if ($abuild_file) {
			$ret .= '<div class="download">Build tree: <a href="/fileview/' . $package->md5 . '?f=' . urlencode($abuild_file) . '">' . basename($abuild_file) . '</a></div>';
		}
		$ret .= '</div>';

		// Data in tabs
		$tabs = [];





		// Dependencies
		$code = '<ul>';
		foreach($package->dependencies as $dep) {
			$cond = trim(UiCore::dependParse($dep['condition']));
			// TODO: fix link to search within packages that matches version criteria only.
			$code .= '<li><a href="/search?name=' . urlencode($dep['name']) . '">' . $dep['name'] . '</a>' . ($cond !== '' ? $cond . $dep['version'] : '') . '</li>';
		}
		$code .= '</ul>';

		$tabs[] = ['title' => 'Dependencies', 'body' => $code];
	

		// Filelist
		$code = '<div id="filelist"><ol>';
		foreach($pkgfiles['files'] as $file) {
			$code .= '<li><a href="/fileview/' . $package->md5 . '?f=' . urlencode($file) . '">' . $file . '</a></li>';
		}
		$code .= '</ol></div>';

		$tabs[] = ['title' => 'Files', 'body' => $code];

		// Other versions
		// TODO: think about span of search
		$code = '<div id="otherversions"><ol>';
		//foreach($package->repositories as $patharray) {
		//	if (isset($patharray['latest'])) unset($patharray['latest']);
		//	$path = implode('/', $patharray);
			$section = '';
			$section_count = 0;
			foreach($package->altVersions() as $altpackage) {
				if ($altpackage->md5==$package->md5) continue;
				$section_count++;
				$section .= '<li><a href="/pkgview/' . $altpackage->md5 . '">' . $altpackage . '</a></li>';
			}
			$code .= $section;
		//	if ($section_count>0) {
		//		$code .= '<h3>' . $path . '</h3>' . $section;
		//	}
		//}
		$code .= '</ol></div>';

		$tabs[] = ['title' => 'Other versions', 'body' => $code];


		// Edit
		// ...There will be ajax, lots of it
		// Prepare table

		$user = Auth::user();
		if ($user) {
			$table = [];
			foreach($package->repositories as $path) {
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

	private function requestDispatcher($data, $package) {
		$callback = 'process_' . trim($data['__submit_form_id']);
		if (method_exists($this, $callback)) return $this->$callback($data, $package);
		return 'Call signature ' . $callback . ' unknown';
	}

	private function process_pkgMoveFormInit($data, $package) {
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
		
		$code = '<h1>Move package ' . $package . '</h1>';
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


	private function process_getFormFieldOptions($data, $package) {
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

	private function process_pkgMoveFormSave($data, $package) {
		$user = Auth::user();
		$new_location = [];
		$f = ['repository', 'osversion', 'branch', 'subgroup'];
		foreach($f as $k) {
			if (!isset($data[$k])) return $k . ' is mandatory';
			$new_location[$k] = trim($data[$k]);
		}

		$old_location = json_decode($data['old_location']);

		$paths = $package->repositories;
		foreach($paths as &$location) {
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
		$package->repositories = $paths;
		$package->save();

		$task_options['package_name'] = $package->name;
		AsyncTask::create($user->name, 'find_old_versions', 'Scan repository for an old versions after moving ' . $package->name, $task_options);
		header('Location: /pkgview/' . $package->md5);

	}

	private function process_pkgCopyFormInit($data, $package) {
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

		$code = '<h1>Copy package ' . $package . ' to:</h1>';

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

	private function process_pkgCopyFormSave($data, $package) {
		$user = Auth::user();
		$new_location = [];
		$f = ['repository', 'osversion', 'branch', 'subgroup'];
		foreach($f as $k) {
			if (!isset($data[$k])) return $k . ' is mandatory';
			$new_location[$k] = trim($data[$k]);
		}

		$match = false;
		$paths = $package->repositories;
		foreach($paths as &$location) {
			$match = true;
			foreach($f as $k) {
				if ($location[$k]!==$new_location[$k]) {
					$match = false;
					break;
				}
			}
		}

		if (!$match) {
			$paths[] = $new_location;
			$package->repositories = $paths;
			$package->save();

		}
		$task_options['package_name'] = $package->name;
		AsyncTask::create($user->name, 'find_old_versions', 'Scan repository for an old versions after moving ' . $package->name, $task_options);


		header('Location: /pkgview/' . $package->md5);

	}


	private function process_pkgDeleteFormSave($data, $package) {
		$user = Auth::user();
		$rm_location = [];
		$f = ['repository', 'osversion', 'branch', 'subgroup'];
		foreach($f as $k) {
			if (!isset($data[$k])) return $k . ' is mandatory';
			$rm_location[$k] = trim($data[$k]);
		}


		$newset = [];
		foreach($package->repositories as $location) {
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
		$package->repositories = $newset;
		$package->save();

		$task_options['package_name'] = $package->name;
		AsyncTask::create($user->name, 'find_old_versions', 'Scan repository for an old versions after moving ' . $package->name, $task_options);

		return 'OK';

	}



}
