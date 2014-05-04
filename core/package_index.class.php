<?php

require_once 'mongo.class.php';
class PackageIndex extends MongoDBAdapter {
	public static function xml($query, $output_directory = false, $location_prefix = '__pr__', $override_location = false) {
		$packages = self::db()->packages->find($query);
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><repository />');

		$counter = 0;
		foreach($packages as $pkg) {
			$counter++;
			$xmlpkg = $xml->addChild('package');
			foreach(['name', 'version', 'arch', 'build', 'md5', 'filename', 'short_description', 'description', 'compressed_size', 'installed_size', 'location'] as $key) {
				if (!isset($pkg[$key])) continue;
				// TODO: handle location correctly: mpkg assumes that location is relative to repository index, even if an absolute path with http:// is specified. The only way to do that is map somehow 
				if ($key==='location') {
					if ($override_location===false) $xmlpkg->$key = $location_prefix . '/' . $pkg[$key];
					else $xmlpkg->$key = $override_location;
				}
				else $xmlpkg->$key = $pkg[$key];
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
		if ($output_directory!==false) {
			$tmpfile = $output_directory . '/packages.xml';
			$xzfile = $tmpfile . '.xz';
			unlink($xzfile);
			file_put_contents($tmpfile, $dom->saveXML());
			system('xz ' . $tmpfile);
			return true;
		}
		else {
			return $dom->saveXML();
		}
	}

}
