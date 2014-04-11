<?php

Page::loadModule('pkglist');

class Module_homepage extends Module {
	public function run() {
		$p = new Module_pkglist($this->page, $this->blockname);
		return $p->run();
	}
}
