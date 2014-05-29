<?php

Page::loadModule('admin');
Page::loadModule('uicore');

class Module_admin_actions extends AdminModule {
	public function run() {
		if (isset($this->page->path[3])) {
			$method = 'action_' . $this->page->path[3];
			$sidemethod = 'sidebar_action_' . $this->page->path[3];
			if ($this->blockname==='sidebar') {
				if (method_exists($this, $sidemethod)) return $this->$sidemethod();
				else return '';
			}
			if (!method_exists($this, $method)) return 'Method ' . $method . ' not found';
			return $this->$method();
		}
		if ($this->blockname==='sidebar') return $this->run_sidebar();
		$ret = '<h1>Actions</h1>';
		$actions = [
			'example' => 'Test action',
			'find_old_versions' => 'Rescan for latest versions',
			'mirror' => 'Clone external repository',
			'create_iso' => 'Create an ISO',
			'bridge' => 'Run bridge sync',
			];

		$ret .= '<ul>';
		foreach($actions as $action => $title) {
			$ret .= '<li><a href="/admin/actions/' . $action . '">' . $title . '</a></li>';

		}
		$ret .= '</ul>';


		return $ret;
	}

	public function run_sidebar() {
	}

	public function action_example() {
		$options = [
			'sleep_time' => ['type' => 'text', 'label' => 'Sleep time']
			];
		$default_values = [
			'sleep_time' => 10,
			];

		if (@$_POST['__submit_form_id']==='action_form') {
			$task_options = [];
			foreach($options as $key => $t) {
				if (isset($_POST[$key])) $task_options[$key] = trim($_POST[$key]);
			}

			$task_id = AsyncTask::create(Auth::user()->name, 'example', 'Example task: sleeping some time', $task_options);
			header('Location: /taskmon/view/' . $task_id);
			return 'Task created';

		}


		$ret = '<h1>Test action</h1>';


		$code = '';
		foreach($options as $key => $fdesc) {
			$code .= UiCore::getInput($key, @$default_values[$key], '', $fdesc);
		}
		$ret .= UiCore::editForm('action_form', NULL, $code, '<input type="submit" value="Run" />');

		return $ret;
	}

	public function sidebar_action_example() {
		return '<h1>Test action</h1><p>Test action creates a sleep task that sleeps specified amount of seconds</p>';
	}


	public function action_find_old_versions() {
		$repositories = Repository::getList();

		if (@$_POST['__submit_form_id']==='action_form') {	
			$task_options = [];
			if (trim(@$_POST['package_name'])!=='') $task_options['package_name'] = trim($_POST['package_name']);

			$enabled_repositories = [];
			foreach($repositories as $repository_name) {
				if (isset($_POST[$repository_name . '_repository'])) $enabled_repositories[] = $repository_name;
			}
			if (count($enabled_repositories)>0) $task_options['repositories'] = $enabled_repositories;

			$task_id = AsyncTask::create(Auth::user()->name, 'find_old_versions', 'Scan repository for an old versions' . (count($enabled_repositories)>0 ? ' within ' . implode(', ', $enabled_repositories) : '') . (isset($task_options['package_name']) ? ', package ' . $task_options['package_name'] : ''), $task_options);
			header('Location: /taskmon/view/' . $task_id);
			return 'Task created';

		}


		$ret = '<h1>Find old versions</h1>';


		$code = UiCore::getInput('package_name', '', '', ['type' => 'text', 'label' => 'Package name (optional)']);
		$code .= '<h2>Repositories to scan</h2>';
		$code .= '<p>NOTE: if no repository will be selected, all repositories will be scanned</p>';
		foreach($repositories as $repository_name) {
			$code .= UiCore::getInput($repository_name, '', '_repository', ['type' => 'checkbox', 'label' => $repository_name]);
		}
		$ret .= UiCore::editForm('action_form', NULL, $code, '<input type="submit" value="Run" />');

		return $ret;
	}

	public function sidebar_action_find_old_versions() {
		return '<h1>Old version scan</h1><p>Scans specified area of repository, checks which packages are latest within subcategory, and stores that data</p>';
	}


	public function action_create_iso() {
		/* Form wizard will be here */

		$slides = [];

		// Select a repository
		$slide = '<h2>Repository and architecture</h2><p>At this moment, only one repository can be used to build an ISO. Ability to build ISO image using multiple repositories will be added in future.</p>';
		$slide .= UiCore::getInput('repository', Settings::get('default_repository'), '', ['type' => 'select', 'label' => 'Repository to use', 'options' => Repository::getList()]);
		$slide .= UiCore::getInput('arch', '', '', ['type' => 'select', 'label' => 'Architecture', 'options' => ['x86', 'x86_64', 'any']]);
		$slides[] = $slide;


		// Select OS versions, branches and subgroups
		$slide = '<h2>OS versions, branches, subgroups</h2><p>Select which packages should be used.</p>';
		if (isset($_POST['repository'])) {
			$slide .= '<p>Repository selected: ' . $_POST['repository'] . ', arch: ' . $_POST['arch'] . '</p>';
			$repository = new Repository(trim($_POST['repository']));
			$osversions = UiCore::multiCheckbox($repository->osversions(), [], '_osversion');
			$branches = UiCore::multiCheckbox($repository->branches(), [], '_branch');
			$subgroups = UiCore::multiCheckbox($repository->subgroups(), [], '_subgroup');
			$slide .= '<h3>OS version:</h3><div id="create_iso_osversions">' . $osversions . '</div>';
			$slide .= '<h3>Branch:</h3><div id="create_iso_branches">' . $branches . '</div>';
			$slide .= '<h3>Subgroup:</h3><div id="create_iso_subgroups">' . $subgroups . '</div>';
		}
		$slides[] = $slide;

		// Select setup variants
		$slide = '<h2>Setup variants</h2><p>Select setup variants which will be available. Note that image will include only packages which are relevant to specified variant.</p>';
		if (isset($_POST['repository'])) {
			$osversions = [];
			$repository = new Repository(trim($_POST['repository']));

			$slide .= '<p>Repository selected: ' . $_POST['repository'] . ', arch: ' . $_POST['arch'] . '</p>';
			$setup_variants = '';
			foreach($repository->osversions() as $osversion) {
				$js_osversion = preg_replace('/\./', '_', $osversion);
				if (!isset($_POST[$js_osversion . '_osversion'])) continue;
				$variant_names = $repository->setup_variants($osversion);
				if (count($variant_names)===0) continue;
				$setup_variants .= '<h4>' . $repository->name . '/' . $osversion . ':</h4>';
				$setup_variants .= UiCore::multiCheckbox($variant_names, [], '_setup_variant');
			}
			$slide .= '<h3>Setup variants:</h3><div id="create_iso_setup_variants">' . $setup_variants . '</div>';
		}
		$slides[] = $slide;


		// Select an ISO template
		$slide = '<h2>ISO template</h2><p>ISO template contains bootable kernel, live filesystem image and other files like this</p>';
		if (isset($_POST['repository'])) $slide .= '<p>Repository selected: ' . $_POST['repository'] . ', arch: ' . $_POST['arch'] . '</p>';
		$slide .= UiCore::getInput('iso_template', '', '', ['type' => 'select', 'label' => 'ISO template', 'options' => IsoBuilder::templates()]);
		$slides[] = $slide;

		// Final settings: ISO name and confirmation
		$slide = '<h2>Final settings</h2><p id="create_iso_final"></p>';
		$slide .= UiCore::getInput('iso_name', 'AgiliaLinux_' . date('Y-m-d_His'), '', ['type' => 'text', 'label' => 'ISO filename']);
		$slides[] = $slide;



		$ret = '<h1>Create an iso image</h1>';

		$ret .= UiCore::slideForm('create_iso_form', $slides, true);


		if (@$_POST['__submit_form_id']==='create_iso_form') {

			$task_options = [
				'repository' => trim($_POST['repository']),
					'arch' => trim($_POST['arch']),
					'iso_template' => trim($_POST['iso_template']),
					'iso_name' => trim($_POST['iso_name'])
					];

			$repository = new Repository($task_options['repository']);
			$task_options['osversions'] = [];
			$task_options['branches'] = [];
			$task_options['subgroups'] = [];
			$task_options['setup_variants'] = [];
			foreach($repository->osversions() as $osversion) {
				// Dots are transformed in underscore in POST keys, so it is required to handle it
				$js_osversion = preg_replace('/\./', '_', $osversion);
				if (isset($_POST[$js_osversion . '_osversion'])) $task_options['osversions'][] = $osversion;
				$variant_names = $repository->setup_variants($osversion);
				foreach($variant_names as $variant_name) {
					if (isset($_POST[$variant_name . '_setup_variant'])) $task_options['setup_variants'][] = $variant_name;
				}
			}
			foreach($repository->branches() as $branch) {
				if (isset($_POST[$branch . '_branch'])) $task_options['branches'][] = $branch;
			}
			foreach($repository->subgroups() as $subgroup) {
				if (isset($_POST[$subgroup . '_subgroup'])) $task_options['subgroups'][] = $subgroup;
			}


			$task_desc = 'Create ISO ' . $task_options['iso_name'] . 
				' using ' . $task_options['iso_template'] . 
				' template, repository: ' . $task_options['repository'] . 
				', osversions: ' . implode(', ', $task_options['osversions']) . 
				', branches: ' . implode(', ', $task_options['branches']) . 
				', subgroups: ' . implode(', ', $task_options['subgroups']) . 
				', setup variants: ' . implode(', ', $task_options['setup_variants']);



			$task_id = AsyncTask::create(Auth::user()->name, 'create_iso', $task_desc, $task_options);
			header('Location: /taskmon/view/' . $task_id);
			return 'Task created';

		}

		return $ret;
	}

	public function action_bridge() {
		$task_id = AsyncTask::create(Auth::user()->name, 'repository_bridge_sync', 'Bridge sync');
		header('Location: /taskmon/view/' . $task_id);
		return 'Task created';

	}


}
