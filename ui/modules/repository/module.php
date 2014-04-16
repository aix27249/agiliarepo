<?php

require_once '../core/bootstrap.php';
error_reporting(-1);
ini_set('display_errors', 'on');
// Modules that use repository may extend this class. Only database for now, but I think it'll be useful
class RepositoryModule extends Module {
	protected static $_db = NULL;
	public function __construct ($page, $blockname) {
		$this->db = self::db();

		return parent::__construct($page, $blockname);
	}

	public static function db() {
		if (self::$_db) return self::$_db;
		self::$_db = MongoConnection::c()->agiliarepo;
		return self::$_db;
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
