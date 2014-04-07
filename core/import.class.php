<?php
/* Scan directory for files and store it into database 
 * As for now, mostly for debugging, but should be extended to useful stuff 
 */
require_once 'package.class.php';
require_once 'mongo.class.php';
class ImportDirectory {

	// Scans directory and returns array of Package instances
	public static function getPackages($dir) {
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
	public static function addToDb($pkg) {
		$db = MongoConnection::c()->agiliarepo;
		$db->packages->insert($pkg->metadata());
		$db->package_files->insert(['md5' => $pkg->md5(), 'files' => $pkg->filelist()]);

	}

	// Scans specified directory and imports to database
	public static function import($dir) {
		$l = static::getPackages($dir);
		$total = count($l);
		$pos = 0;
		foreach($l as $pkg) {
			$pos++;
			echo "[$pos/$total] Importing $pkg->filename\n";
			static::addToDb($pkg);
		}
	}

}



