<?php
require_once '../core/bootstrap.php';
class RepositoryBridge extends MongoDBAdapter {
	public $mysqli = NULL;
	public function __construct($dbhost, $dbusername, $dbpass, $dbname) {
		$this->mysqli = new mysqli($dbhost, $dbusername, $dbpass, $dbname);
		if (mysqli_connect_errno()) {
			printf("Connect failed: %s\n", mysqli_connect_error());
		}
		$this->mysqli->set_charset("utf8");
	}

	public function getNewPackages() {
		$new = self::db()->packages->distinct('md5');
		
		$stmt = $this->mysqli->prepare("SELECT DISTINCT package_md5 FROM packages");
		$stmt->bind_result($md5);
		$stmt->execute();
		$stmt->store_result();
		$new_packages_md5 = [];
		while ($stmt->fetch()) {
			if (in_array($md5, $new, true)) continue;
			$this->importPackage($md5);
			$new_packages_md5[] = $md5;
		}
		$stmt->free_result();
		$stmt->close();
		return $new_packages_md5;
	}

	public function syncAddDate() {
		$stmt = $this->mysqli->prepare("SELECT package_md5, package_add_date FROM packages");
		$stmt->bind_result($md5, $add_timestamp);

		$stmt->execute();

		while ($stmt->fetch()) {
			self::db()->packages->update(['md5' => $md5], ['$set' => ['add_date' => new MongoDate($add_timestamp)]]);
		}
		$stmt->close();

	}

	public function importPackage($md5) {
		$stmt = $this->mysqli->prepare('SELECT packages.package_filename, packages.package_add_date, locations.server_url, locations.location_path, locations.distro_arch, locations.distro_version FROM packages, locations WHERE packages.package_md5=? AND packages.package_id=locations.packages_package_id');
		$stmt->bind_param('s', $md5);
		$stmt->bind_result($filename, $add_timestamp, $repo, $subpath, $arch, $osver);
		$stmt->execute();
		$stmt->store_result();

		$root_path = '/home/aix/www/repositories/package_tree';
		while ($stmt->fetch()) {
			$path = $root_path . '/' .  $repo . '/' . $osver . '/' . $arch . '/repository/' . $subpath . '/' . $filename;
			if (!file_exists($path)) {
				echo "File $path does not exist\n";
				return false;
			}
			$pkg = new Package($path);
			$p = $pkg->metadata($root_path);

			$new_osver = '8.1';
			$new_branch = $repo;
			$new_subgroup = 'stable';
			$is_latest = true;
			if ($repo==='next') {
				$new_osver = '9.0';
				$new_branch = 'core';
			}
			else if ($repo==='testing') {
				$new_subgroup = 'testing';
				$new_branch = 'userland';
			}
			if ($osver==='8.0_deprecated') {
				$is_latest = false;
			}



			$p['repositories'][] = ['repository' => 'master', 'osversion' => $new_osver, 'branch' => $new_branch, 'subgroup' => $new_subgroup];
			$p['add_date'] = new MongoDate($add_timestamp);

			if ($is_latest) $p['latest'] = 1;
			$p['_rev'] = 1;

			self::db()->packages->remove(['md5' => $p['md5']]);
			self::db()->package_files->remove(['md5' => $p['md5']]);
			self::db()->packages->insert($p);
			
			self::db()->package_files->insert(['md5' => $p['md5'], 'files' => $pkg->filelist()]);

		}
		$stmt->free_result();
		$stmt->close();

	}
}


