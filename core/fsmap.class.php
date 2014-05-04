<?php
/* Maps package files to specified directory using hardlinks
 * IMPORTANT: source and destination should be ON THE SAME FILESYSTEM, since hardlinks can work only within one filesystem.
 * And of course, your filesystem should support hardlinks. If you're not using exotic 
 *
 * Usage example:

	FsMap::createMap('/home/aix/www/repositories/package_tree', 'map/test', ['latest' => 1], ['split_directories' => false]);

 */

require_once 'mongo.class.php';

class FsMap {
	public static function createMap($root_path, $target_dir, $query = [], $options = []) {
		$db = MongoConnection::c()->agiliarepo;
		$packages = $db->packages->find($query);
		system('mkdir -p ' . $target_dir);
		if (@$options['split_directories']) {
			$dirs = [];
			foreach($packages as $pkg) {
				$subdir = '';
				if (@$options['split_directories']) {
					$subdir = $pkg['repository'][0] . '/' . $pkg['osversion'][0] . '/' . $pkg['branch'][0] . '/' . $pkg['subgroup'][0] . '/';
				}
				$dest = $target_dir . '/' . $subdir;
				$dirs[] = $dest;
			}
			$dirs = array_unique($dirs);
			foreach($dirs as $dir) {
				system("mkdir -p " . $dir);
			}
		}
		foreach($packages as $pkg) {
			$orig = $root_path . '/' .  $pkg['location'] . '/' . $pkg['filename'];
			if (!file_exists($orig)) echo "$orig: file doesnt exist\n";
			$subdir = '';
			if (@$options['split_directories']) {
				$subdir = $pkg['repository'][0] . '/' . $pkg['branch'][0] . '/' . $pkg['subgroup'][0] . '/';
			}
			$dest = $target_dir . '/' . $subdir . $pkg['filename'];
			link($orig, $dest);

		}
	}

	public static function hardlinkDirectoryContents($from, $to) {
		if (!file_exists($from)) throw new Exception('Source directory does not exist');
		if (!file_exists($to)) {
			mkdir($to);
			throw new Exception('Destination directory does not exist');
		}

		$root = self::scandir($from);
		foreach($root as $fitem) {
			if (is_dir($from . '/' . $fitem)) {
				mkdir($to . '/' . $fitem);
				self::hardlinkDirectoryContents($from . '/' . $fitem, $to . '/' . $fitem);
			}
			else link($from . '/' . $fitem, $to . '/' . $fitem);
		}
	}

	public static function scandir($path) {
		return array_values(array_diff(scandir($path), ['..', '.']));
	}

}


