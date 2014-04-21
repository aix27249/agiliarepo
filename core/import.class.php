<?php
/* Scan directory for files and store it into database 
 * As for now, mostly for debugging, but should be extended to useful stuff 
 */
require_once 'package.class.php';
require_once 'mongo.class.php';
class ImportDirectory {

	// Scans directory and returns array of Package instances
	public static function getPackages($dir, $root_path = NULL) {
		$ptr = popen('find ' . $dir . ' -type f -name \'*.t?z\'', 'r');
		$ret = [];
		while (!feof($ptr)) {
			$pkgfile = trim(fgets($ptr));
			if ($pkgfile==='') continue;
			try {
				$package = new Package($pkgfile);
			}
			catch (Exception $e) {
				echo "$pkgfile not found\n";
			}
			$ret[] = $package;
		}
		fclose($ptr);
		return $ret;
	}


	// Adds package to database. TODO: function should be moved to some appropriate class in future
	public static function addToDb($pkg, $root_path = NULL, array $repository = [], array $osversion = [], array $branch = [], array $subgroup = [], $is_latest = false) {
		$db = MongoConnection::c()->agiliarepo;
		$p = $pkg->metadata($root_path);

		$p['repositories'][] = ['repository' => $repository, 'osversion' => $osversion, 'branch' => $branch, 'subgroup' => $subgroup];
		$p['add_date'] = new MongoDate();

		$p['_rev'] = 1;
		if ($is_latest) $p['latest'] = 1;
		$db->packages->remove(['md5' => $p['md5']]);
		$db->package_files->remove(['md5' => $p['md5']]);
		$db->packages->insert($p);
		
		$db->package_files->insert(['md5' => $p['md5'], 'files' => $pkg->filelist()]);

	}

	// Scans specified directory and imports to database
	public static function import($dir, $root_path = NULL, array $repository = [], array $osversion = [], array $branch = [], array $subgroup = [], $is_latest = false) {
		$l = static::getPackages($dir, $root_path);
		$total = count($l);
		$pos = 0;
		foreach($l as $pkg) {
			$pos++;
			echo "[$pos/$total] Importing $pkg->filename\n";
			static::addToDb($pkg, $root_path, $repository, $osversion, $branch, $subgroup, $is_latest);
		}
	}

}



