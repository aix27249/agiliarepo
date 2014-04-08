<?php

class Module {
	public static $styles = [];
	public static $scripts = [];
	public function __construct($page, $blockname) {
		$this->page = $page;
		$this->blockname = $blockname;
	}

	public static function getStyles($page = NULL, $blockname = NULL) {
		return static::$styles;
	}
	public static function getScripts($page = NULL, $blockname = NULL) {
		return static::$scripts;
	}

}
