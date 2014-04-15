<?php

class Module_searchbar extends Module {
	static $styles = ['searchbar.css'];
	public function run() {
		$str = '';
		if ($this->page->name === 'search') $str = @$_GET['q'];
		return '<form action="/search" method="GET" id="searchbar_form"><input type="search" name="q" id="q" placeholder="Найти пакет" value="' . htmlspecialchars($str) . '" /><input type="submit" value="Поиск" /></form>';
	}
}
