<?php
class Module_logo extends Module {

	static $styles = ['logo.css'];
	public function run() {
		return '<a href="/"><img src="/images/logo.svg" alt="" onerror="this.error=null; this.src=\'/images/logo.png\';" /></a>';
	}
}
