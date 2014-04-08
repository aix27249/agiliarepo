<?php

class GenericTemplate {
	public static $name = '';
	public static $styles = [
		'defaults.css'
		];
	// Template scripts (optional)
	public static $scripts = [];

	public static function renderBody($page) {
		ob_start();
		require_once 'templates/' . static::$name . '/template_body.php';
		$page_content = ob_get_contents();
		ob_end_clean();
		return $page_content;
	}

	public static function render($page) {
		ob_start();
		require_once 'templates/' . static::$name . '/index.php';
		$page_content = ob_get_contents();
		ob_end_clean();
		return $page_content;
	}

}
