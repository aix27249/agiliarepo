<?php
class Module_404 extends Module {
	public function run() {
		header('HTTP/1.1 404 Not found');
		die(404);
		//return 'Sorry, page ' . $this->page->name . ' not found';
	}
}
