<?php
Page::loadModule('uicore');
class Module_searchbar extends Module {
	static $styles = ['searchbar.css'];
	static $scripts = ['searchbar.js'];
	public function run() {
		$str = '';
		if ($this->page->name === 'search') $str = @$_GET['q'];
		$ret = '<div class="minisearch"><form action="/search" method="GET" id="searchbar_form"><input type="search" name="q" id="q" placeholder="Package search" value="' . htmlspecialchars($str) . '" /><input type="submit" value="Search" /><input type="button" value="Advanced search..." onclick="searchbar.advancedSearch();" /></form></div>';
		$ret .= '<div class="extendedsearch"><h1>Advanced search</h1>';
		$fields = [
			'name' => ['type' => 'text', 'label' => 'Name'],
			'version' => ['type' => 'text', 'label' => 'Version'],
			'arch' => ['type' => 'select', 'label' => 'Architecture', 'options' => ['any', 'x86', 'x86_64']],
			'build' => ['type' => 'text', 'label' => 'Build'],
			'latest_only' => ['type' => 'checkbox', 'label' => 'Latest only']
			];

		$code = '';
		foreach($fields as $key => $fdesc) {
			$code .= UiCore::getInput($key, '', '', $fdesc);
		}

		$ret .= UiCore::editForm('extendedsearch', NULL, $code, '<input type="submit" value="Search" />', '/search', 'GET');

		$ret .= '</div>';
		return $ret;
	}
}
