<?php
Page::loadModule('repository');
Page::loadModule('uicore');
class Module_incoming extends RepositoryModule {
	public $dirpath = NULL, $user = NULL;

	public static $styles = ['incoming.css', 'dropzone/css/dropzone.css'];
	public static $scripts = ['incoming.js', 'dropzone.js'];

	public function run() {
		$this->user = Auth::user();
		if (!$this->user) return 'You need to be logged in to access incoming';
		$directory = dirname(__FILE__) . '/../../../users/' . $this->user->name . '/incoming';
		if (!file_exists($directory)) mkdir($directory);
		$this->dirpath = realpath($directory);

		if ($this->blockname==='sidebar') return $this->run_sidebar();

		if (@$this->page->path[2]==='dropzone') die($this->dropzone_backend());

		if (isset($_POST['action'])) {
			$method = 'action_' . $_POST['action'];
			if (method_exists($this, $method)) die($this->$method($_POST));
			else die("Method $method does not exist");
		}
		// Required calls:
		// (Re)Import metadata from single package
		// Import metadata from all packages which are changed or wasn't imported before
		// Move selected packages to specific repository and/or branch

		// Scan directory for files

		$ret = '<h1>Incoming packages</h1>';
		$ret .= '<div id="incoming" class="table">';
		$ret .= $this->loadTable();
		$ret .= '</div>';

		$ret .= '<h2>Upload</h2>';
		$ret .= '<div class="dropzone" id="dropzone"></div>';

		return $ret;

	}

	public function run_sidebar() {
		$ret = '';

		$ret .= '<h2>Incoming zone</h2>
			<p>Here is your incoming packages. Select some of them and do fancy actions &mdash; that\'s yours!</p>';
		$ret .= '<div id="multi_buttons">';
		foreach(['deleteMulti' => 'Delete selected', 'importMulti' => '(re)import selected', 'addMulti' => 'Add selected to...'] as $key => $title) {
			$ret .= '<input type="button" class="' . $key . '_button" value="' . $title . '" onclick="incoming.' . $key . '();" />';
		}

		$ret .= '</div>';

		$ret .= '<div id="common_buttons">';
		$ret .= '<input type="button" class="select_all_button" value="Select all" onclick="incoming.selectAll();" />';
		$ret .= '</div>';
		return $ret;

	}

	public function loadTable() {
		$ret = '';
		$dir = scandir($this->dirpath);

		foreach($dir as $filename) {
			$ext = pathinfo($this->dirpath . '/'. $filename, PATHINFO_EXTENSION);
			if ($ext!=='txz') continue;
			$info = self::db()->incoming_packages->findOne(['username' => $this->user->name, 'filename' => $filename]);
			$ret .= $this->renderPackage($filename, $info);
		}

		return $ret;

	}

	public function renderPackage($filename, $info = NULL) {
		$ret = '<div class="package table-row" data-filename="' . $filename . '" data-imported="' . ($info ? 'true' : 'false') . '">';
		$ret .= '<div class="table-cell table_checkbox"><input type="checkbox" class="package_select" onchange="incoming.check(this, \'' . $filename . '\');"/></div>';
		// Package info
		$ret .= '<div class="table-cell table_info">';
		$ret .= '<div class="package_filename">' . $filename . '</div>';
		$ret .= '<div class="package_info">';
		if ($info) {
			$ret .= $info['md5'] . ', ' . implode('/', $info['tags']);
		}
		else {
			$ret .= 'not imported yet';
		}
		$ret .= '</div>';

		$ret .= '</div>'; // End of package_info

		$ret .= '<div class="table-cell table_actions">';
		foreach(['delete' => 'Delete', 'import' => 'Import metadata', 'add' => 'Add to...'] as $key => $title) {
			if ($info && $key==='import') {
				$title = 'Re-import metadata';
			}
			if (!$info && $key==='add') continue;
			$ret .= '<input type="button" class="' . $key . '_button" value="' . $title . '" onclick="incoming.' . $key . '(\'' . $filename . '\');" />';
		}

		$ret .= '</div>';

		$ret .= '</div>';

		return $ret;

	}

	public function action_reload_table($options) {
		return $this->loadTable();
	}

	public function action_pkginfo($options) {
		$filename = @$options['filename'];
		if (!$filename || !file_exists($this->dirpath . '/' . $filename)) return 'File not found';

		$info = self::db()->incoming_packages->findOne(['username' => $this->user->name, 'filename' => $filename]);
		return $this->renderPackage($filename, $info);
	}

	public function action_import($options) {
		$filename = @$options['filename'];
		if (!$filename || !file_exists($this->dirpath . '/' . $filename)) return 'File not found';

		// Clean old records
		self::db()->incoming_packages->remove(['username' => $this->user->name, 'filename' => $filename]);
		self::db()->incoming_package_files->remove(['username' => $this->user->name, 'filename' => $filename]);

		$package_file = new PackageFile($this->dirpath . '/' . $filename);
		$metadata = $package_file->metadata();
		$files = $package_file->filelist();
		$metadata['username'] = $this->user->name;


		self::db()->incoming_packages->insert($metadata);
		self::db()->incoming_package_files->insert(['username' => $this->user->name, 'filename' => $filename, 'files' => $files]);
		return 'OK';

	}

	public function action_validate($filename, $repository = NULL, $osversion = NULL, $branch = NULL, $subgroup = NULL) {
		if (!$filename || !file_exists($this->dirpath . '/' . $filename)) return 'File not found';

		$info = self::db()->incoming_packages->findOne(['username' => $this->user->name, 'filename' => $filename]);

		// Check if package is a duplicate
		$dupe = self::db()->packages->findOne(['md5' => $info['md5']]);
		$errors = [];
		if ($dupe) {
			$errors[] = 'duplicate';
		}

		// Check if package with same name/version/arch/build is within specified repository
		$dupe = self::db()->packages->findOne(['name' => $info['name'], 'version' => $info['version'], 'arch' => Package::queryArchSet($info['arch']), 'build' => $info['build'], 'repositories.repository' => $repository]);
		if ($dupe) {
			$errors[] = 'samename';
		}


		return $errors;

	}

	public function action_delete($options) {
		$filename = @$options['filename'];
		if (!$filename || !file_exists($this->dirpath . '/' . $filename)) return 'File not found';
		self::db()->incoming_packages->remove(['username' => $this->user->name, 'filename' => $filename]);
		self::db()->incoming_package_files->remove(['username' => $this->user->name, 'filename' => $filename]);
		unlink($this->dirpath . '/' . $filename);
		return 'OK';
	}

	public function dropzone_backend() {

		if (!empty($_FILES)) {
			$temp_file = $_FILES['file']['tmp_name'];
			$orig_name = $_FILES['file']['name'];
			
			$ext = pathinfo($orig_name, PATHINFO_EXTENSION);
			if ($ext!=='txz') die('Invalid file type');
			
			$target_file = $this->dirpath . '/' . $orig_name;

			move_uploaded_file($temp_file, $target_file);
			$this->action_import(['filename' => $orig_name]);
			die('OK');
		}
		else {
			die('Nothing uploaded');
		}

	}

	public function action_addForm($options) {
		$stage = intval($options['stage']);
		$next_stage = $stage + 1;
		$ret = '<h1>Add packages to repository</h1>
			<p><b>Packages to be added:</b> ' . implode(', ', $options['filenames']) . '</p>';

		$ret .= '<div class="uicore_form">';
		if ($stage===1) {
			$ret .= '<h2>Select repository:</h2>';
			$repositories = Repository::getList($this->user, 'write');
			$ret .= UiCore::getInput('repository', '', '', ['type' => 'select', 'label' => 'Select repository', 'options' => $repositories]);
		}
		if ($stage===2) {
			$repository = new Repository($options['repository']);
			$ret .= '<h2>Selected repository: ' . $repository->name . '</h2>';
			foreach(['osversion' => 'OS version', 'branch' => 'Branch', 'subgroup' => 'Subgroup'] as $key => $value) {
				$method = $key . 's';
				if ($key==='branch') $method = $key . 'es';
				$key_options = $repository->$method($this->user, 'write');
				$ret .= UiCore::getInput($key, '', '', ['type' => 'select', 'label' => $value, 'options' => $key_options]);
			}
			$ret .= UiCore::getInput('repository', $repository->name, '', ['type' => 'hidden']);
		}
		if ($stage===3) {
			// Validate every package at this point to display errors before adding anything
			// TODO

			// If all ok, show confirm table
			$repository = new Repository($options['repository']);
			$ret .= '<h2>Please confirm target:</h2>';

			$table = [];
			$code = '';
			foreach(['repository' => 'Repository', 'osversion' => 'OS version', 'branch' => 'Branch', 'subgroup' => 'Subgroup'] as $key => $value) {
				$code .= UiCore::getInput($key, $options[$key], '', ['type' => 'hidden']);
				$table[$value] = $options[$key];
			}
			$ret .= UiCore::table($table);
			$ret .= $code;

		}
		if ($stage===4) {
			// Move packages
			$errors = $this->movePackages($options);
			if (count($errors)===0) {
				$ret .= '<h2>Packages added successfully</h2>';
				$ret .= '<input type="button" onclick="window.location.reload;" value="OK" />';
			}
			else {
				$ret .= '<h2>' . count($errors) . ' packages failed to add:</h2>';
				$table = [];
				foreach($errors as $filename => $error) {
					$table[$filename] = implode('; ', $error);
				}
				$ret .= UiCore::table($table);
			}
		}
		if ($stage<4) $ret .= '<input type="button" onclick="incoming.addMulti(' . $next_stage . ');" value="Next" />';
		$ret .= '</div>';
		//$ret .= UiCore::editForm('repository_select', NULL, $code, '<input type="submit" value="Next" />');
		return $ret;
	}

	public function movePackages($options) {
		$ret = [];
		foreach($options['filenames'] as $filename) {
			$errors = $this->action_validate($filename, $options['repository'], $options['osversion'], $options['branch'], $options['subgroup']);
			if (count($errors)>0) {
				$ret[$filename] = $errors;
				continue;
			}
			$incoming_pkg = self::db()->incoming_packages->findOne(['username' => $this->user->name, 'filename' => $filename]);
			$incoming_pkg_files = self::db()->incoming_package_files->findOne(['username' => $this->user->name, 'filename' => $filename]);

			if (!$incoming_pkg || !$incoming_pkg_files) continue; // TODO: Mark this as error

			$repository = new Repository($options['repository']);
			$location = $repository->defaultPath();

			unset($incoming_pkg['_id']);
			unset($incoming_pkg['username']);

			$incoming_pkg['added_by'] = $this->user->name;
			$incoming_pkg['add_date'] = new MongoDate;
			$incoming_pkg['location'] = $location;

			$incoming_pkg['repositories'][] = ['repository' => $options['repository'], 'osversion' => $options['osversion'], 'branch' => $options['branch'], 'subgroup' => $options['subgroup']];

			unset($incoming_pkg_files['_id']);
			unset($incoming_pkg_files['username']);
			unset($incoming_pkg_files['filename']);
			$incoming_pkg_files['md5'] = $incoming_pkg['md5'];


			$from = $this->dirpath . '/' . $filename;
			$to = ServerSettings::$root_path . '/' . $location . '/' . $filename;
			if (!file_exists(dirname($to))) system("mkdir -p " . dirname($to));
			rename($from, $to);

			self::db()->packages->insert($incoming_pkg);
			self::db()->package_files->insert($incoming_pkg_files);
	
			self::db()->incoming_packages->remove(['username' => $this->user->name, 'filename' => $filename]);
			self::db()->incoming_package_files->remove(['username' => $this->user->name, 'filename' => $filename]);

			// TODO: update latest package flags for specific package name within specified repository
		}
		return $ret;
	}
}
