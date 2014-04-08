<?php
require_once 'conf/defaults.conf.php';
require_once 'conf/site.conf.php';
require_once 'conf/page.conf.php';
require_once 'module.class.php';
require_once 'page.class.php';
require_once 'template_generic.class.php';
class Core {
	public static function load() {
		// 1: determine page which should be served
		$page = Page::fromURI(@$_SERVER['REQUEST_URI']);
		$page->load();
	}
}


