<?php
Page::loadModule('repository');
Page::loadModule('uicore');
class Module_setup_variants extends RepositoryModule {
	public static $styles = ['setup_variants.css'];
	public function run() {
		if ($this->blockname==='sidebar') return $this->run_sidebar();
		// Path layout: /setup_variants/[action]/[repository]/[osver]/[variant_name]
		if (isset($this->page->path[2])) {
			$method = 'action_' . $this->page->path[2];
			if (method_exists($this, $method)) return $this->$method();
			else return 'Method ' . $method . ' not found';
		}
		else return $this->action_list();
	}

	public function run_sidebar() {
		$links = ['create' => 'Create new'];
		$ret = '<h2>Setup variants</h2>';

		foreach($links as $action => $title) {
			$ret .= '<li><a href="/setup_variants/' . $action . '">' . $title . '</a></li>';
		}
		return $ret;
	}

	// TODO: stub
	public function action_list() {
		$variants = self::db()->setup_variants->find();
		$ret = '<h1>Setup variants</h1>';
		$ret .= '<ul>';

		foreach($variants as $v) {
			$path = $v['repository'] . '/' . $v['osversion'] . '/' . $v['name'];
			$ret .= '<li><a href="/setup_variants/view/' . $path . '">' . $path . '</a></li>';
		}
		$ret .= '</ul>';

		return $ret;
	}

	// TODO: stub
	public function action_create() {
		$user = Auth::user();
		if (!$user) return 'Only registered users can edit anything';

		$ret = '<h1>Create new setup variant</h1>';
		if (!isset($this->page->path[3])) {
			if (@$_POST['__submit_form_id']==='create_router_repository') {
				header('Location: /setup_variants/create/' . $_POST['repository']);
				die();
			}
			$code = UiCore::getInput('repository', Settings::get('default_repository'), '', ['type' => 'select', 'label' => 'Repository', 'options' => Repository::getList($user, 'admin')]);
			$ret .= UiCore::editForm('create_router_repository', NULL, $code, '<input type="submit" value="Next" />');
			return $ret;
		}

		$repository_name = $this->page->path[3];
		$repository = new Repository($repository_name);
		$ret .= '<h3>Repository: ' . $repository_name . '</h3>';
		if (!isset($this->page->path[4])) {
			if (@$_POST['__submit_form_id']==='create_router_osversion') {
				header('Location: /setup_variants/create/' . $repository_name . '/' . $_POST['osversion']);
				die();
			}

			$code = UiCore::getInput('osversion', '', '', ['type' => 'select', 'label' => 'OS version', 'options' => $repository->osversions($user, 'admin')]);
			$ret .= UiCore::editForm('create_router_osversion', NULL, $code, '<input type="submit" value="Next" />');
			return $ret;

		}
		$osversion = $this->page->path[4];

		$ret .= '<h3>OS version: ' . $osversion . '</h3>';
		if (@$_POST['__submit_form_id']==='create') {
			try {
				$keys = ['name', 'hardware', 'desc', 'full'];
				$setup_variant = ['repository' => $repository_name, 'osversion' => $osversion, 'packages' => []];
				foreach($keys as $k) {
					if (trim(@$_POST[$k])==='') throw new Exception($k . ' is empty');
					$setup_variant[$k] = trim($_POST[$k]);
				}
				$packages_raw = explode("\r\n", $_POST['packages']);
				
				foreach($packages_raw as $p) {
					$t = trim(preg_replace('/\#.*/', '', $p));
					if ($t==='') continue;
					$setup_variant['packages'][] = $t;
				}
				$setup_variant['hasDM'] = isset($_POST['hasDM']);
				$setup_variant['hasX11'] = isset($_POST['hasX11']);

				self::db()->setup_variants->insert($setup_variant);
				header('Location: /setup_variants/view/' . $repository_name . '/' . $osversion . '/' . $setup_variant['name']);
				die();
			}
			catch (Exception $e) {
				$ret .= '<div class="error">' . $e->getMessage() . '</div>';
			}
		}



		$fields = [
			'name' => ['type' => 'text', 'label' => 'Name'],
			'hasDM' => ['type' => 'checkbox', 'label' => 'GUI login'],
			'hasX11' => ['type' => 'checkbox', 'label' => 'X11 available'],
			'hardware' => ['type' => 'text', 'label' => 'Recommended hardware'],
			'desc' => ['type' => 'text', 'label' => 'Short description'],
			'full' => ['type' => 'textarea', 'label' => 'Detailed description', 'placeholder' => 'Please enter detailed setup variant description'],
			'packages' => ['type' => 'textarea', 'label' => 'Packages', 'placeholder' => 'Please enter package names which should be installed in that setup variant. One line - one package.'],
			];

		$code = '';
		foreach($fields as $key => $fdesc) {
			$code .= UiCore::getInput($key, (@$_POST[$key] || isset($_POST[$key])), '', $fdesc);
		}
		$ret .= UiCore::editForm('create', NULL, $code);


		return $ret;
	}

	// TODO: stub
	public function action_view() {
		$repository_name = $this->page->path[3];
		$osversion = $this->page->path[4];
		$variant_name = $this->page->path[5];

		$setup_variant = self::db()->setup_variants->findOne(['repository' => $repository_name, 'osversion' => $osversion, 'name' => $variant_name]);
		$ret = '<pre>' . print_r($setup_variant, true) . '</pre>';
		return $ret;

	}

	// TODO: stub
	public function action_edit() {
	}
}
