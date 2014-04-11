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
}
