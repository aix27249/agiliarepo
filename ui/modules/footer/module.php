<?php

class Module_footer extends Module {
	public static $scripts = ['footer.js'];
	public static $styles = ['footer.css'];
	function run() {
		return 'Page ' . $this->page->name . ': context test passed, block: ' . $this->blockname;
	}
	
}
