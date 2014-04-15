<?php

require_once '../core/bootstrap.php';
error_reporting(-1);
ini_set('display_errors', 'on');
// Modules that use repository may extend this class. Only database for now, but I think it'll be useful
class RepositoryModule extends Module {
	public $db = NULL;
	public function __construct ($page, $blockname) {
		$this->db = MongoConnection::c()->agiliarepo;
		return parent::__construct($page, $blockname);
	}

	// Move it from here in future to some class like RepositoryUiElements
	public function renderPath($path, $delimiter = '', $prefix = '/browser/') {
		$ret = '';
		$prev = '';
		foreach($path as $k) {
			if ($k==='') break;
			if ($k!=='/') $prev .= $k . '/';
			$ret .= '<a href="' . $prefix . $prev . '">' . $k . '</a>' . $delimiter;
		}
		return $ret;
	}

}


class Module_repository extends Module {
	static $styles = ['repository.css'];
}
