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

	public function test_call($args) {
		die(print_r($args, true));
	}

	public function repositories_call($args) {
		$valid_calls = ['list', 'info'];
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
}
