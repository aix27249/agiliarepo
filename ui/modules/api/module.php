<?php
Page::loadModule('repository');
class Module_api extends RepositoryModule {
	public function run() {
		if (!isset($this->page->path[2])) return $this->tutorial();
		$method = $this->page->path[2] . '_call';
		if (!method_exists($this, $method)) return $this->error('400 Bad Request', 'Invalid method');
		die($this->$method(array_slice($this->page->path, 3)));

	}

	public function tutorial() {
		$ret = '<h1>Repository API</h1>
			<p>Please, read API documentation.</p>';
		return $ret;
	}
	public function error($header, $err_text) {
		header($header);
		die($err_text);
	}

	// Test call: returns call arguments
	public function test_call($args) {
		die(print_r($args, true));
	}

	// Repository call dispatcher (redirect to list and info)
	public function repositories_call($args) {
		$valid_calls = ['list', 'info', 'packages'];
		if (count($args)===0) {
			return 'Valid subcalls: ' . implode(', ', $valid_calls);
		}

		if (!in_array($args[0], $valid_calls, true)) return $this->error('400 Bad Request', 'Invalid call: ' . $args[0] . ' is not a valid method');

		$method = 'repositories_call_' . $args[0];
		return $this->$method(array_slice($args, 1));
	}
	public function repositories_call_list($args) {
		$list = Repository::getList();
		if (@$args[0]==='simple') {
			return implode("\n", $list);
		}
		else {
			return json_encode($list, JSON_PRETTY_PRINT);
		}
	}

	public function repositories_call_info($args) {
		if (!isset($args[0])) return $this->error('400 Bad Request', 'Repository name not specified');
		$repository = new Repository($args[0]);
		return json_encode($repository->settings(), JSON_PRETTY_PRINT);

	}
	public function repositories_call_packages($args) {
		if (!isset($args[0])) return $this->error('400 Bad Request', 'Repository name not specified');
		$query = ['repositories.repository' => $args[0]];
		$opts = $_GET;
		if (isset($opts['latest'])) $query['repositories.latest'] = true;
		if (isset($opts['osversion'])) $query['repositories.osversion'] = $opts['osversion'];
		if (isset($opts['branch'])) $query['repositories.branch'] = $opts['branch'];
		if (isset($opts['subgroup'])) $query['repositories.subgroup'] = $opts['subgroup'];
	
		$pkgs = self::db()->packages->distinct('md5', $query);
		return json_encode($pkgs, JSON_PRETTY_PRINT);
	}

	// Packages call dispatcher
	public function packages_call($args) {
		$valid_calls = ['info', 'files', 'url'];
		if (count($args)===0) {
			return 'Valid subcalls: ' . implode(', ', $valid_calls);
		}

		if (!in_array($args[0], $valid_calls, true)) return $this->error('400 Bad Request', 'Invalid call: ' . $args[0] . ' is not a valid method');

		$method = 'packages_call_' . $args[0];
		return $this->$method(array_slice($args, 1));
	}
	public function packages_call_info($args) {
		$md5 = $args[0];
		$pkg = self::db()->packages->findOne(['md5' => $md5]);
		return json_encode($pkg, JSON_PRETTY_PRINT);
	}

	public function packages_call_files($args) {
		$md5 = $args[0];
		$pkg = self::db()->package_files->findOne(['md5' => $md5]);
		return json_encode($pkg, JSON_PRETTY_PRINT);
	}
	public function packages_call_url($args) {
		$md5 = $args[0];
		$package = new Package($md5);

		return '/pkgindex/__pr__/' . $package->location . '/' . $package->filename;
	}

	public function upload_call($args) {
		// First of all: do auth
		if (!isset($_POST['login'])) die('Login unspecified');
		if (!isset($_POST['pass'])) die('Password unspecified');
		$login = $_POST['login'];
		$pass = $_POST['pass'];

		$login_result = Auth::tryAuth($login, $pass, false, false);
		if (!$login_result) die('Invalid login/pass');


		var_dump($_FILES);
		$tmp_name = $_FILES['file']['tmp_name'];
		$orig_name = $_FILES['file']['name'];

		$upload_dir = Auth::user()->homedir() . '/incoming';
		if (!file_exists($upload_dir) mkdir($upload_dir);
		$upload_dir = realpath($upload_dir);
		move_uploaded_file($tmp_name, $upload_dir . '/' . $orig_name);
		return 'OK';
	}


}
