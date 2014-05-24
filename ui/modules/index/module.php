<?php
Page::loadModule('repository');
class Module_index extends RepositoryModule {
	public $filepath = NULL;
	public function run() {

		/* There are basically two methods of getting JSON data from collection: use PHP code, or use mongoexport shell command. 
		 * PHP mode is simplier in code, safer to pass arguments from user, requires no shell calls, but about 7x times slower.
		 *
		 * mongoexport example here:
		 * system('mongoexport -d ' . $mongo_db_name . ' -c packages -q \'{"repositories: {"$elemMatch" : {repository":"master","branch":"core","osversion":"8.1"}}\'');
		 */

		if (in_array('__pr__', $this->page->path, true)) {
			return $this->handleFile();
		}
		$query = ['repositories.latest' => true];
		$plength = count($this->page->path);
		$as_file = false;
		if ($this->page->path[($plength-1)]==='packages.xml.xz') {
			$as_file = true;
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=packages.xml.xz');
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			unset($this->page->path[($plength-1)]);
		}

		$ematch = [];
		if (isset($this->page->path[2])) $query['arch'] = Package::queryArchSet($this->page->path[2]);
		if (isset($this->page->path[3])) $ematch['repository'] = $this->page->path[3];
		if (isset($this->page->path[4])) $ematch['osversion'] = $this->page->path[4];
		if (isset($this->page->path[5])) $ematch['branch'] = $this->page->path[5];
		if (isset($this->page->path[6])) $ematch['subgroup'] = $this->page->path[6];

		if (count($ematch)>0) $query['repositories'] = ['$elemMatch' => $ematch];

		$format = 'xml';
		if (@$_GET['format']==='json') $format = 'json';


		$packages = self::db()->packages->find($query);


		die($this->$format($packages, $as_file));
	}
	public function json($packages, $as_file = false) {
		die(json_encode(iterator_to_array($packages)));
	}

	public function xml($packages, $as_file = false) {
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><repository />');

		$counter = 0;
		foreach($packages as $pkg) {
			$counter++;
			$xmlpkg = $xml->addChild('package');
			foreach(['name', 'version', 'arch', 'build', 'md5', 'filename', 'short_description', 'description', 'compressed_size', 'installed_size', 'location'] as $key) {
				if (!isset($pkg[$key])) continue;
				// TODO: handle location correctly: mpkg assumes that location is relative to repository index, even if an absolute path with http:// is specified. The only way to do that is map somehow 
				if ($key!=='location') $xmlpkg->$key = $pkg[$key];
				else $xmlpkg->$key = '__pr__/' . $pkg[$key];
			}

			if (isset($pkg['conflicts'])) {
				$xmlpkg->conflicts=$pkg['conflicts'][0];
			}
			if (isset($pkg['provides'])) {
				$xmlpkg->provides=$pkg['provides'][0];
			}
			if (isset($pkg['config_files'])) {
				$xmlconfig = $xmlpkg->addChild('config_files');
				$config_files = array_unique($pkg['config_files']);
				foreach($config_files as $conf_file) {
					$xmlconfig->addChild('conf_file', $conf_file);

				}
			}

			$maintainer = $xmlpkg->addChild('maintainer');
			$maintainer->email = $pkg['maintainer']['email'];
			$maintainer->name = $pkg['maintainer']['name'];




			$deps = $xmlpkg->addChild('dependencies');
			foreach($pkg['dependencies'] as $pkgdep) {
				$dep = $deps->addChild('dep');
				$dep->addChild('name', $pkgdep['name']);
				$dep->addChild('condition', $pkgdep['condition']);
				$dep->addChild('version', $pkgdep['version']);
			}
			$tags = $xmlpkg->addChild('tags');
			foreach($pkg['tags'] as $tag) {
				$tags->addChild('tag', $tag);
			}

			$xmlpkg->distro_version = $pkg['repositories'][0]['osversion'];

			//break; // DEBUG

		}

		$dom = dom_import_simplexml($xml)->ownerDocument;
		$dom->formatOutput = true;
		if ($as_file) {
			$tmpfile = '/tmp/xmltest.xml';
			$xzfile = $tmpfile . '.xz';
			unlink($xzfile);
			file_put_contents($tmpfile, $dom->saveXML());
			system('xz ' . $tmpfile);
			header('Content-Length: ' . filesize($xzfile));
			readfile($xzfile);
			die();
		}
		else {
			die($dom->saveXML());
		}
	}

	public function handleFile() {
		$pos = array_search('__pr__', $this->page->path);
		if ($pos===false) die('Invalid path');
		$rpath = array_slice($this->page->path, $pos+1);
		$fullpath = ServerSettings::$root_path . '/' . implode('/', $rpath);
		$realpath = realpath($fullpath);
		if (strpos($realpath, ServerSettings::$root_path)!==0) die('Invalid path');
		$filename = $rpath[(count($rpath)-1)];
		if (!file_exists($realpath)) die($fullpath . ' does not exist');
	
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . $filename);
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($realpath));
		readfile($realpath);


		die();
	}
}
