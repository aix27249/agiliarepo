<?php
class Module_menu extends Module {
	static $styles = ['menu.css'];
	public function run() {
		$links = [
			'Browser' => '/browser',
			];

		$ret = '';
		foreach($links as $title => $url) {
			$ret .= '<a href="' . $url . '">' . $title . '</a>';
		}
		return $ret;

	}
}
