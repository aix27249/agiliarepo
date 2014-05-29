<?php
Page::loadModule('repository');
Page::loadModule('uicore');
class Module_admin_setup_variants extends RepositoryModule {
	public static $styles = ['setup_variants.css'];
	public static $scripts = ['setup_variants.js'];
	public function run() {
		if ($this->blockname==='sidebar') return $this->run_sidebar();
		// Path layout: /setup_variants/[action]/[repository]/[osver]/[variant_name]
		if (isset($this->page->path[3])) {
			$method = 'action_' . $this->page->path[3];
			if (method_exists($this, $method)) return $this->$method();
			else return 'Method ' . $method . ' not found';
		}
		else return $this->action_list();
	}

	public function run_sidebar() {
		$links = ['create' => 'Create new'];
		$ret = '<h2>Setup variants</h2>';

		
		foreach($links as $action => $title) {
			$ret .= '<li><a href="/admin/setup_variants/' . $action . '">' . $title . '</a></li>';
		}

		if (isset($this->page->path[3])) {
			$ret .= '<h2>Available</h2>';
			$ret .= $this->action_list(true);
		}
		return $ret;
	}

	public function action_list($noheader = false) {
		$variants = self::db()->setup_variants->find();

		$ret = '';
		if (!$noheader) $ret .= '<h1>Setup variants</h1>';
		$ret .= '<ul>';

		foreach($variants as $v) {
			$path = $v['repository'] . '/' . $v['osversion'] . '/' . $v['name'];
			$ret .= '<li><a href="/admin/setup_variants/view/' . $path . '">' . $path . '</a></li>';
		}
		$ret .= '</ul>';

		return $ret;
	}

	public function action_create() {
		$user = Auth::user();
		if (!$user) return 'Only registered users can edit anything';
		if (!$user->can('edit_setup_variants')) return 'You have no permission to create setup variants';
		// TODO: check if user can manage setup variants in SPECIFIC repository

		$ret = '<h1>Create new setup variant</h1>';
		if (!isset($this->page->path[4])) {
			if (@$_POST['__submit_form_id']==='create_router_repository') {
				header('Location: /admin/setup_variants/create/' . $_POST['repository']);
				die();
			}
			$code = UiCore::getInput('repository', Settings::get('default_repository'), '', ['type' => 'select', 'label' => 'Repository', 'options' => Repository::getList($user, 'admin')]);
			$ret .= UiCore::editForm('create_router_repository', NULL, $code, '<input type="submit" value="Next" />');
			return $ret;
		}

		$repository_name = $this->page->path[4];
		$repository = new Repository($repository_name);
		$ret .= '<h3>Repository: ' . $repository_name . '</h3>';
		if (!isset($this->page->path[4])) {
			if (@$_POST['__submit_form_id']==='create_router_osversion') {
				header('Location: /admin/setup_variants/create/' . $repository_name . '/' . $_POST['osversion']);
				die();
			}

			$code = UiCore::getInput('osversion', '', '', ['type' => 'select', 'label' => 'OS version', 'options' => $repository->osversions($user, 'admin')]);
			$ret .= UiCore::editForm('create_router_osversion', NULL, $code, '<input type="submit" value="Next" />');
			return $ret;

		}
		$osversion = $this->page->path[5];

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
				header('Location: /admin/setup_variants/view/' . $repository_name . '/' . $osversion . '/' . $setup_variant['name']);
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

	public function action_view() {
		$repository_name = $this->page->path[4];
		$osversion = $this->page->path[5];
		$variant_name = $this->page->path[6];

		$setup_variant = self::db()->setup_variants->findOne(['repository' => $repository_name, 'osversion' => $osversion, 'name' => $variant_name]);
		$ret = '<h1>' . $setup_variant['name'] . '</h1>';
		$ret .= '<h2>' . $setup_variant['desc'] . '</h2>';
		$ret .= '<div class="setup_variant_info">';
		$ret .= '<div class="full">' . $setup_variant['full'] . '</div>';
		$ret .= '<div class="specs">
			Repository: ' . $repository_name . '<br />
			OS version: ' . $osversion . '<br />

			X11: ' . ($setup_variant['hasX11'] ? 'yes' : 'no') . '<br />
			GUI login: ' . ($setup_variant['hasDM'] ? 'yes': 'no') . '<br />
			</div>';

		$ret .= '</div>';
		if (Auth::user() && Auth::user()->can('edit_setup_variants')) $ret .= '<div><a href="/admin/setup_variants/edit/' . $repository_name . '/' . $osversion . '/' . $variant_name . '">Edit</a></div>';
		$ret .= '<h2>Packages: ' . count($setup_variant['packages']) . '</h2><a id="verbose_link" href="javascript:setup_variants.switchMode(\'verbose\');">Show verbose list</a><br /><br />';
		$package_names = $setup_variant['packages'];

		sort($package_names);
		if (@$_POST['__ajax']==='verbose') {
			$packages = self::db()->packages->find(['name' => ['$in' => $package_names], 'repositories.latest' => true, 'repositories.repository' => $repository_name, 'repositories.osversion' => $osversion, 'repositories.branch' => 'core']);
			die($this->ajax_verbose_pkglist($package_names, $packages, $repository_name, $osversion));
		}
		else {
			$ret .= '<div id="package_container">' . nl2br(implode("\n", $package_names)) . '</div>';
		}
		return $ret;

	}

	public function ajax_verbose_pkglist($package_names, $packages, $repository_name, $osversion) {
		$ret = '';
		$packages = iterator_to_array($packages, true);


		$ret .= '<div class="packages table">';
		$head = ['name', 'x86', 'x86_64'];
		$ret .= '<div class="table-row table-head">';
		foreach($head as $h) $ret .= '<div class="table-cell table-head">' . $h . '</div>';
		$ret .= '</div>';
		$x86 = Package::queryArchSet('i686');
		$x86 = $x86['$in'];
		$x86_64 = Package::queryArchSet('x86_64');
		$x86_64 = $x86_64['$in'];

		foreach($package_names as $name) {
			$ret .= '<div class="table-row">';
			$ret .= '<div class="table-cell">
				<a href="/search?name=' . urlencode($name) . '&amp;repository=' . $repository_name . '&amp;osversion=' . $osversion . '&amp;latest=1">' . $name . '</a>
				</div>';
			$ret .= '<div class="table-cell">';
			$x86_packages = [];
			foreach($packages as $pkg) {
				if ($pkg['name']===$name && in_array($pkg['arch'], $x86, true)) {
					foreach($pkg['repositories'] as $r) {
						if (@$r['latest'] && $r['repository']===$repository_name && $r['osversion']===$osversion) {
							$x86_packages[] = $pkg;
							break;
						}
					}
				}
			}
			$counter = 0;
			if (count($x86_packages)===0) {
				$ret .= 'Not found';
			}	
			foreach($x86_packages as $pkg) {
				$ret .= '<a href="/pkgview/' . $pkg['md5'] . '">' . $pkg['version'] . '-' . $pkg['build'];
				if (count($x86_packages)>1) {
					foreach($pkg['repositories'] as $r) {
						if (@$r['latest'] && $r['repository']===$repository_name && $r['osversion']===$osversion) {
							$ret .= ' (' . $r['branch'] . '/'. $r['subgroup'] . ')';
							break;
						}
					}
				}
				$ret .= '</a>';
				if ($counter<count($x86_packages)) $ret .= '<br />';
				$counter++;
			}

			$ret .= '</div>';


			$ret .= '<div class="table-cell">';
			$x86_64_packages = [];
			foreach($packages as $pkg) {
				if ($pkg['name']===$name && in_array($pkg['arch'], $x86_64, true)) {
					foreach($pkg['repositories'] as $r) {
						if (@$r['latest'] && $r['repository']===$repository_name && $r['osversion']===$osversion) {
							$x86_64_packages[] = $pkg;
							break;
						}
					}
				}
			}
			$counter = 0;
			if (count($x86_64_packages)===0) {
				$ret .= 'Not found';
			}	

			foreach($x86_64_packages as $pkg) {
				$ret .= '<a href="/pkgview/' . $pkg['md5'] . '">' . $pkg['version'] . '-' . $pkg['build'];
				if (count($x86_64_packages)>1) {
					foreach($pkg['repositories'] as $r) {
						if (@$r['latest'] && $r['repository']===$repository_name && $r['osversion']===$osversion) {
							$ret .= ' (' . $r['branch'] . '/'. $r['subgroup'] . ')';
							break;
						}
					}
				}
				$ret .= '</a>';
				if ($counter<count($x86_64_packages)) $ret .= '<br />';
				$counter++;
			}

			$ret .= '</div>';




			$ret .= '</div>';
		}
		$ret .= '</div>';
		return $ret;

	}

	// TODO: stub
	public function action_edit() {
		$user = Auth::user();
		if (!$user) return 'Only registered users can edit anything';
		if (!$user->can('edit_setup_variants')) return 'You have no permission to edit setup variants';

		$repository_name = $this->page->path[4];
		$osversion = $this->page->path[5];
		$variant_name = $this->page->path[6];

		$variant = self::db()->setup_variants->findOne(['name' => $variant_name, 'repository' => $repository_name, 'osversion' => $osversion]);

		if (!$variant) return 'No such setup variant';
	
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

				self::db()->setup_variants->update(['name' => $variant_name, 'repository' => $repository_name, 'osversion' => $osversion], $setup_variant);
				header('Location: /admin/setup_variants/view/' . $repository_name . '/' . $osversion . '/' . $setup_variant['name']);
				die();
			}
			catch (Exception $e) {
				$ret .= '<div class="error">' . $e->getMessage() . '</div>';
			}
		}


		$ret = '<h1>' . $repository_name. '/' . $osversion . '/' . $variant_name . '</h1>';
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
			$code .= UiCore::getInput($key, (is_array($variant[$key]) ? implode("\n", $variant[$key]) : $variant[$key]), '', $fdesc);
		}
		$ret .= UiCore::editForm('create', NULL, $code);


		return $ret;


	}
}
