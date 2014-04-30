<?php
Page::loadModule('repository');
class Module_index extends RepositoryModule {
	public $filepath = NULL;
	public function run() {

		/* There are basically two methods of getting JSON data from collection: use PHP code, or use mongoexport shell command. 
		 * PHP mode is simplier in code, safer to pass arguments from user, requires no shell calls, but about 7x times slower.
		 *
		 * mongoexport example here:
		 * system('mongoexport -d ' . $mongo_db_name . ' -c packages -q \'{"repositories.repository":"master","repositories.branch":"core","repositories.osversion":"8.1"}\'');
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
		if (isset($this->page->path[2])) $query['arch'] = Package::queryArchSet($this->page->path[2]);
		if (isset($this->page->path[3])) $query['repositories.repository'] = $this->page->path[3];
		if (isset($this->page->path[4])) $query['repositories.osversion'] = $this->page->path[4];
		if (isset($this->page->path[5])) $query['repositories.branch'] = $this->page->path[5];
		if (isset($this->page->path[6])) $query['repositories.subgroup'] = $this->page->path[6];

		$format = 'xml';
		if (@$_GET['format']==='json') $format = 'json';


		$packages = self::db()->packages->find($query);


		die($this->$format($packages, $as_file));
	}
	public function json($packages, $as_file = false) {
		die(json_encode(iterator_to_array($packages)));
	}

	public function xml($packages, $as_file = false) {
		$xml = new SimpleXMLElement('<repository />');

		$counter = 0;
		foreach($packages as $pkg) {
			$counter++;
			$xmlpkg = $xml->addChild('package');
			foreach(['name', 'version', 'arch', 'build', 'md5', 'filename', 'provides', 'conflicts', 'short_description', 'description', 'compressed_size', 'installed_size', 'location'] as $key) {
				if (!isset($pkg[$key])) continue;
				// TODO: handle location correctly: mpkg assumes that location is relative to repository index, even if an absolute path with http:// is specified. The only way to do that is map somehow 
				if ($key!=='location') $xmlpkg->$key = $pkg[$key];
				else $xmlpkg->$key = '__pr__/' . $pkg[$key];
			}

			$deps = $xmlpkg->addChild('dependencies');
			foreach($pkg['dependencies'] as $pkgdep) {
				$dep = $deps->addChild('dependency');
				$dep->addChild('name', $pkgdep['name']);
				$dep->addChild('condition', $pkgdep['condition']);
				$dep->addChild('version', $pkgdep['version']);
			}
			$tags = $xmlpkg->addChild('tags');
			foreach($pkg['tags'] as $tag) {
				$tags->addChild('tag', $tag);
			}

			//break; // DEBUG

		}

		//$dom = dom_import_simplexml($xml)->ownerDocument;
		//$dom->formatOutput = true;
		if ($as_file) {
			$tmpfile = '/tmp/xmltest.xml';
			$xzfile = $tmpfile . '.xz';
			unlink($xzfile);
			file_put_contents($tmpfile, $xml->asXML());
			system('xz ' . $tmpfile);
			header('Content-Length: ' . filesize($xzfile));
			readfile($xzfile);
			die();
		}
		else {
			$dom = dom_import_simplexml($xml)->ownerDocument;
			$dom->formatOutput = true;
			die($dom->saveXML());
		}
	}

	public function handleFile() {
		$pos = array_search('__pr__', $this->page->path);
		if ($pos===false) die('Invalid path');
		$rpath = array_slice($this->page->path, $pos+1);
		$fullpath = SiteSettings::$root_path . '/' . implode('/', $rpath);
		$filename = $rpath[(count($rpath)-1)];
		if (!file_exists($fullpath)) die($fullpath . ' does not exist');
	
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . $filename);
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($fullpath));
		readfile($fullpath);


		die();
	}
}
