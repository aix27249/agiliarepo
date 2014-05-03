<?php

Page::loadModule('admin');
Page::loadModule('uicore');

class Module_admin_actions extends AdminModule {
	public function run() {
		if (isset($this->page->path[3])) {
			$method = 'action_' . $this->page->path[3];
			$sidemethod = 'sidebar_action_' . $this->page->path[3];
			if ($this->blockname==='sidebar' && method_exists($this, $sidemethod)) return $this->$sidemethod();
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
}
