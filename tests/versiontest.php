<?php
require_once '../core/bootstrap.php';
class TEST extends MongoDBAdapter {
	public static function run($package_name, $arch, $path) {
		$archset = Package::queryArchSet($arch);
		$pkg = self::db()->packages->findOne(['name' => $package_name, 'arch' => $archset]);
		$package = new Package($pkg);
		echo "Found package: " . $package->name . "\n";
		//$path = implode('/', [$reponame, $osversion, $branch, $subgroup]);
		$packages = $package->altVersions($path);
		echo "Found alternate versions at " . $path . ": " . count($packages) . "\n";
		Package::recheckLatest($packages, $path, true);
	}
}

TEST::run('mkpkg', 'i686', 'master/8.1/core/stable');

