<?php

/* 
 * AgiliaLinux PHP package handling library
 *
 * Class PackageFile: read different data from *.txz package files 
 * WARNING: do not use complex filenames (with spaces, brackets etc) - they passed to shell directly.
 * TODO: fix this issue
 *
 */

require_once 'tgzhandler.class.php';
require_once 'mongo.class.php';
require_once 'strverscmp.class.php';
class PackageFile {
	public $filename = NULL;
	// Checks if file exists
	public function __construct($filename) {
		if (!file_exists($filename)) {
			throw new Exception($filename . ': file not found');
		}
		$this->filename = $filename;
	}

	// Returns SimpleXML object with metadata
	public function xml() {
		$raw_xml = TgzHandler::readFile($this->filename, 'install/data.xml');
		$xml = new SimpleXMLElement($raw_xml);
		return $xml;
	}

	// Returns array of metadata
	public function metadata($root_path = NULL) {
		$filename = basename($this->filename);
		$location = dirname($this->filename);
		if ($root_path!==NULL) $location = mb_substr($location, (mb_strlen($root_path) + 1));

		$xml = $this->xml();
		$data = [
			'name' => trim($xml->name),
			'version' => trim($xml->version),
			'arch' => trim($xml->arch),
			'build' => trim($xml->build),
			'short_description' => trim($xml->short_description),
			'description' => trim($xml->description),
			'dependencies' => [], // Fill later
			'suggests' => [], // Fill later
			'maintainer' => ['name' => trim($xml->maintainer->name), 'email' => trim($xml->maintainer->email)],
			'tags' => [], // Fill later,
			'compressed_size' => $this->filesize(),
			'installed_size' => $this->datasize(),
			'filename' => $filename,
			'location' => $location,
			'provides' => [trim($xml->provides)],
			'conflicts' => [trim($xml->conflicts)],
			'config_files' => [], // Will fill later
			'md5' => $this->md5(),
			];

		foreach($xml->dependencies->children() as $dep) {
			$data['dependencies'][] = ['name' => trim($dep->name), 'condition' => trim($dep->condition), 'version' => trim($dep->version)];
		}

		foreach($xml->suggests->children() as $dep) {
			$data['suggests'][] = ['name' => trim($dep->name), 'condition' => trim($dep->condition), 'version' => trim($dep->version)];
		}

		foreach($xml->tags->children() as $tag) {
			$data['tags'][] = trim($tag);
		}
		if (@count(@$xml->config_files->children())>0) {
			foreach(@$xml->config_files->children() as $config_file) {
				$data['config_files'][] = trim($config_file);
			}
		}

		return $data;
	}

	// Returns JSON with metadata. I think it will be used often, so it seems to be usable to make this separate function
	public function json() {
		return json_encode($this->metadata());
	}

	// Returns array of files inside package (including install/*)
	public function filelist() {
		return TgzHandler::filelist($this->filename);
	}

	// Returns JSON-encoded array of files inside package (including install/*)
	public function json_filelist() {
		return json_encode($this->filelist());
	}

	public function md5() {
		return preg_replace('/\s.*/', '', shell_exec("md5sum " . $this->filename));
	}

	public function filesize() {
		return filesize($this->filename);
	}

	public function datasize() {
		$data = explode("\t", shell_exec('xz -l --robot ' . $this->filename . ' | grep totals'));
		return $data[4];

	}


}


class Package extends MongoDBObject {
	public function __construct($data = NULL) {
		$this->id_key = 'md5';
		if ($data===NULL) return;
		if (is_array($data)) {
			$this->data = $data;
			return;
		}
		$query = ['md5' => $data];
		$collection = 'packages';
		$this->load($collection, $query);
	}

	public static function recheckLatest($packages, $path, $save_inplace = false) {

		$latest = NULL;
		foreach($packages as $package) {
			if ($latest===NULL) {
				$latest = $package;
				continue;
			}
			if (self::compareVersions($package, $latest)>0) $latest = $package;
		}

		foreach($packages as &$package) {
			$is_latest = false;
			if ($package->md5===$latest->md5) $is_latest = true;
			$package->setLatest($path, $is_latest);
			if ($save_inplace) $package->save();
		}
		

		return $packages;
	}

	public static function compareVersions($package1, $package2) {
		$vcmp = VersionCompare::strverscmp($package1->version, $package2->version);
		if ($vcmp===0) {
			$vcmp = VersionCompare::strverscmp($package1->build, $package2->build);
		}
		return $vcmp;

	}

	public function __toString() {
		return $this->name . '-' . $this->version . '-' . $this->arch . '-' . $this->build;
	}

	public function setLatest($path, $is_latest = true) {
		if (!is_array($path)) list($repository, $osversion, $branch, $subgroup) = explode('/', $path);
		else {
			$repository = $path['repository'];
			$osversion = $path['osversion'];
			$branch = $path['branch'];
			$subgroup = $path['subgroup'];
		}

		$this->storeState(false);
		//echo $this . ' at ' . $path . ': is_latest = ' . ($is_latest ? 'true' : 'false') . "\n";
		foreach($this->data['repositories'] as &$p) {
			if ($p['repository']===$repository && $p['osversion']===$osversion && $p['branch'] === $branch && $p['subgroup']===$subgroup) {
				echo "Setting latest to " . $this . ': '. ($is_latest ? 'true' : 'false') . "\n";
				$p['latest'] = $is_latest;
			}
		}
	}

	public function altVersions($path = NULL, $arch_hint = NULL) {
		if ($path!==NULL) {
			if (!is_array($path)) list($repository, $osversion, $branch, $subgroup) = explode('/', $path);
			else {
				//if (!isset($path['repository'])) throw new Exception ('Instead of path, we got a trash here: ' . print_r($path, true));
				$repository = $path['repository'];
				$osversion = $path['osversion'];
				$branch = $path['branch'];
				$subgroup = $path['subgroup'];
			}
		}

		$query = ['name' => $this->name];
		if ($path!==NULL) {
			$query['repositories'] = ['$elemMatch' => [
				'repository' => $repository,
				'branch' => $branch,
				'osversion' => $osversion,
				'subgroup' => $subgroup,
				]];
		}
		if ($arch_hint!==NULL) $archsubset = $arch_hint;
		else $archsubset = self::queryArchSet($this->arch);
		if ($archsubset!==NULL) $query['arch'] = $archsubset;
		
		//if (preg_match('/^..*86$/', $this->arch)>0) $query['arch'] = ['$in' => ['x86', 'i386', 'i486', 'i586', 'i686', 'noarch', 'fw']];
		//else if ($this->arch==='x86_64') $query['arch'] = ['$in' => ['x86_64', 'noarch', 'fw']];

		$packages = self::db()->packages->find($query);
		$parray = [];
		foreach($packages as $pitem) {
			$parray[] = new Package($pitem);
		}
		return $parray;

	}

	public static function queryArchSet($arch) {
		if (preg_match('/^..*86$/', $arch)>0) {
			$subset = ['$in' => ['x86', 'i386', 'i486', 'i586', 'i686', 'noarch', 'fw']];
			//echo "Subset for $arch: " . print_r($subset, true) . "\n";
			return $subset;
		}
		else if ($arch==='x86_64') {
			$subset = ['$in' => ['x86_64', 'noarch', 'fw']];
			//echo "Subset for $arch: " . print_r($subset, true) . "\n";
			return $subset;
		}
		else {
			//echo "Subset for $arch: NULL\n";
			return NULL;
		}
	}

	public function fspath() {
		return ServerSettings::$root_path . '/' . $this->location. '/' . $this->filename;
	}

	public function packageFile() {
		return new PackageFile($this->fspath());
	}

	public function packageFiles() {
		return self::db()->package_files->findOne(['md5' => $this->md5]);
	}
}

class PackageTree {
	private $packages = [];
	private $errors = []; // Error container
	public function __construct($packages = NULL) {
		if ($packages) $this->add($packages);
	}
	public function add($packages) {
		if (is_array($packages)) $this->packages = array_merge($this->packages, $packages);
		else $this->packages = array_merge($this->packages, iterator_to_array($packages));
	}
	public function reduced($md5_only = false) {
		$names = $this->package_names();
		$reduced = [];
		foreach($names as $name) {
			$variants = $this->variants($name);
			if ($md5_only) {
				$latest = self::latest_of($variants);
				$reduced[] = $latest['md5'];
			}
			else {
				$reduced[] = self::latest_of($variants);
			}

		}
		return $reduced;
	}

	// TODO: $md5_only is ignored
	public function dep_reduced($package_names, $md5_only = true) {
		echo "Performing initial reduce\n";
		$reduced = $this->reduced(false);
		echo "Running deptree\n";
		$ret = [];
		foreach($package_names as $name) {
			foreach($reduced as $package) {
				if ($package['name']===$name) {
					self::package_requires($package, $reduced, $ret);
				}
			}

		}
		return array_values(array_unique($ret));
		return $ret;
	}
	private static function package_requires(&$package, &$packages, &$ret) {
		echo "\npackage_requires call for " . $package['name'] . "\n";
		//if (in_array($package['md5'], $ret, true)) return;
		$ret[] = $package['md5'];
		foreach($package['dependencies'] as $dep) {
			$dep_package = self::find_package($dep['name'], $packages);
			if ($dep_package===NULL) throw new Exception('<a href="/pkgview/' . $package['md5'] . '">' . $package['name'] . '-' . $package['version'] . '-' . $package['arch'] . '-' . $package['build'] . '</a> has broken deps: cannot find "' . $dep['name'] . '"');
			if (in_array($dep_package['md5'], $ret, true)) {
				echo "Package " . $dep_package['name'] . " is already selected\n";
				continue;
			}
			echo "\tGot " . $dep_package['name'] . "\n";
			$ret[] = $dep_package['md5'];
			self::package_requires($dep_package, $packages, $ret);
		}
	}
	public static function find_package($name, &$packages) {
		foreach($packages as $package) {
			if ($package['name']===$name) return $package;
		}
		foreach($packages as $package) {
			if (in_array($name, $package['provides'], true)) return $package;
		}
		return NULL;
	}

	public function package_names() {
		$names = [];
		foreach($this->packages as $package) {
			$names[] = $package['name'];
		}
		array_unique($names);
		return $names;
	}
	public function variants($package_name) {
		$packages = [];
		foreach($this->packages as $package) {
			if ($package['name']===$package_name) $packages[] = $package;
		}
		return $packages;
	}

	public static function latest_of($variants) {
		$latest = NULL;
		foreach($variants as $package) {
			if ($latest===NULL) {
				$latest = $package;
				continue;
			}
			if (self::compare_versions($package, $latest)>0) $latest = $package;
		}

		return $latest;

	}
	public static function compare_versions($package1, $package2) {
		$vcmp = VersionCompare::strverscmp($package1['version'], $package2['version']);
		if ($vcmp===0) {
			$vcmp = VersionCompare::strverscmp($package1['build'], $package2['build']);
		}
		return $vcmp;
	}

	public function resetErrors() {
		$this->errors = [];
	}
	public function errors() {
		return $this->errors;
	}
	public function hasErrors() {
		return (count($this->errors)>0);
	}

}
