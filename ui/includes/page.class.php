<?php
class Page {
	public $canonical_url;

	public static function fromURI($uri) {
		$uri = preg_replace('/\/\/*/', '/', preg_replace('/\/$/', '', preg_replace('/\?.*/', '', preg_replace('/\&.*/', '', $uri))));
		$u = explode('/', $uri);
		$pname = (isset($u[1]) ? $u[1] : 'home');

		return new Page($pname, $u);
	}
	public $modules = [], $module_blacklist = [], $styles = [], $scripts = [], $header_items = [];
	public $title = '';
	public $template = 'default';
	public $name = '';
	private $loaded_modules = [];
	public $block_data = [];

	public function __construct($name, $path) {
		$name = preg_replace('/\//', '', $name);
		if (trim($name)==='') $name = 'home';
		$this->name = $name;
		$this->path = $path;
		$this->template = SiteSettings::$template;
		$f = 'pages/' . $name . '.php';
		if (file_exists($f)) require_once $f;
		else {
			// TODO: Load 404 handler: get static page from database, or return 404 error.
			$f = 'pages/404.php';
			if (file_exists($f)) require_once $f;
			else {
				header('HTTP/1.1 404 Not found');
				die('404 File not found');
			}
		}
		$this->loadTemplate();
	}
	private function loadTemplate() {
		require_once 'templates/' . $this->template . '/template.php';
		Template::$name = $this->template;
	}

	public function load() {
		// Preload blocks
		foreach(PageSettings::$modules as $blockname => $block) {
			foreach($block as $modname) {
				if ($this->isModuleBlacklisted($modname, $blockname)) continue;
				$this->loadModule($modname, $blockname);
			}
		}
		foreach($this->modules as $blockname => $block) {
			foreach($block as $modname) {
				if ($this->isModuleBlacklisted($modname, $blockname)) continue;
				$this->loadModule($modname, $blockname);
			}
		}

		echo Template::render($this);
	}
	public function printBlocks($blockname) {
		// Load global modules
		if (isset(PageSettings::$modules[$blockname])) {
			foreach(PageSettings::$modules[$blockname] as $modname) {
				if ($this->isModuleBlacklisted($modname, $blockname)) continue;
				echo @$this->block_data[$blockname][$modname];
			}
		}
		if (isset($this->modules[$blockname])) {
			foreach($this->modules[$blockname] as $modname) {
				if ($this->isModuleBlacklisted($modname, $blockname)) continue;
				echo @$this->block_data[$blockname][$modname];
			}
		}

	}

	public function loadModule($modname, $blockname = NULL) {
		$filename = 'modules/' . $modname . '/module.php';
		if (file_exists($filename)) {
			require_once $filename;
			$this->loadModuleStuff($modname, $blockname);
		}
		if ($blockname) $this->block_data[$blockname][$modname] = $this->loadModuleContent($modname, $blockname);
	}

	private function loadModuleStuff($modname, $blockname) {
		$classname = 'Module_' . $modname;
		if (!class_exists($classname)) return;
		$path = 'modules/' . $modname . '/';
		
		$modstyles = $classname::getStyles($this, $blockname);
		foreach($modstyles as $style) {
			$this->styles[] = $path . $style;
		}
		$this->styles = array_unique($this->styles);
		
		$modscripts = $classname::getScripts($this, $blockname);
		foreach($modscripts as $script) {
			$this->scripts[] = $path . $script;
		}
		$this->scripts = array_unique($this->scripts);


	}
	private function loadModuleContent($modname, $blockname) {
		$classname = 'Module_' . $modname;
		if (class_exists($classname)) {
			$module = new $classname($this, $blockname);
			if (method_exists($classname, 'run')) {
				return $module->run();
			}
			else return 'Method run() not found in class ' . $classname;
		}
		else return 'Class ' . $classname . ' not found';

	}

	public function blacklistModule($modname, $block = '__all__') {
		$this->module_blacklist[$block][] = $modname;
	}
	public function isModuleBlacklisted($modname, $block) {
		if (isset($this->module_blacklist['__all__']) && in_array($modname, $this->module_blacklist['__all__'], true)) return true;
		if (!isset($this->module_blacklist[$block])) return false;
		if (in_array($modname, $this->module_blacklist[$block], true)) return true;
		return false;
	}

	public function loadScripts() {
		// First, template scripts
		foreach(Template::$scripts as $script) {
			echo '<script type="text/javascript" src="/templates/' . Template::$name . '/js/' . $script . '"></script>' . "\n";
		}
		foreach($this->scripts as $script) {
			echo '<script type="text/javascript" src="' . $script . '"></script>' . "\n";
		}

	}

	public function loadStyles() {
		// First, template scripts
		foreach(Template::$styles as $style) {
			echo '@import url(/templates/' . Template::$name . '/css/' . $style . ');' . "\n";
		}
		foreach($this->styles as $style) {
			echo '@import url(' . $style . ');' . "\n";
		}
	}

	public function loadHeaderItems() {
	}

}
