<?php

require_once 'mongo.class.php';
require_once 'fsmap.class.php';
require_once 'settings.class.php';
class IsoBuilder {

	public static function templates() {
		$templates_path = Settings::get('iso_templates_path');
		if (trim($templates_path)==='') throw new Exception('iso_templates_path is empty, specify it in settings');
		if (!file_exists($templates_path)) throw new Exception('ISO templates directory ' . $templates_path . ' not found');

		$dir = array_values(array_diff(scandir($templates_path), ['..', '.']));
		return $dir;
	}


	public static function makeISO($iso_name, $iso_template, $packages_query, $owner_name, $task = NULL) {

		if ($task) $task->setProgress(0, 100, 'Creating directory structure');
		// Detect and create directory structure
		$user_directory = dirname(__FILE__) . '/../users/' . $owner_name;
		if (!file_exists($user_directory)) throw new Exception('User directory not found: ' . $user_directory);

		$template_path = Settings::get('iso_templates_path') . '/' . $iso_template;
		if (!file_exists($template_path) || !is_dir($template_path)) throw new Exception('Template does not exist or not a directory');
		$template_path = realpath($template_path);


		$user_directory = realpath($user_directory);
		$structure_directory = $user_directory . '/iso/structures/' . $iso_name;
		$image_directory = $user_directory . '/iso/images';

		while (file_exists($structure_directory)) {
			$structure_directory .= '_1';
		}
		system('mkdir -p ' . $structure_directory);

		if (!file_exists($image_directory)) mkdir($image_directory);

		$package_root = $structure_directory . '/repository';

		// Hardlink ISO template to target directory
		if ($task) $task->setProgress(0, 100, 'Mapping ISO template');
		FsMap::hardlinkDirectoryContents($template_path, $structure_directory);

		// Hardlink packages

		if ($task) $task->setProgress(0, 100, 'Mapping package tree');
		FsMap::createMap(ServerSettings::$root_path, $package_root, $packages_query);


		// TODO: setup variants
		// As for now, it is included in ISO template, but this is WRONG. User should be able to select setup variants he wants to be included.

		// Create an index for packages

		if ($task) $task->setProgress(0, 100, 'Generating package index');
		$index = PackageIndex::xml($packages_query, $package_root, '', '.');

		// Actually make an ISO	

		if ($task) $task->setProgress(0, 100, 'Creating ISO image');
		self::mkisofs($structure_directory, $image_directory . '/' . $iso_name . '.iso', $task); 

	}

	public static function mkisofs($root, $iso, $task = NULL, $publisher = 'AgiliaLinux / http://agilialinux.ru', $volume_label = 'AGILIA', $app_id = 'AgiliaLinux') {
		
		$mkiso_code = 'mkisofs -o ' . $iso . ' -b isolinux/isolinux.bin -c isolinux/boot.cat -no-emul-boot -boot-load-size 4 -boot-info-table -hide-rr-moved -iso-level 3 -R -J -v -d -N  -publisher "' . $publisher . '" -V "' . $volume_label . '" -A "' . $app_id . '" ' . $root . ' 2>&1';
		$handle = popen($mkiso_code, 'r');
		$progress = 0;
		while (($data = fgets($handle))!==false) {
			if (strpos($data, '% done')!==false) $progress = intval(preg_replace('/\..*/', '', trim($data)));
			if ($task) {
				$task->setProgress($progress, 100, 'Creating ISO image: ' . trim($data));
			}
		}

		pclose($handle);
	
		if ($task) {
			$task->setProgress(99, 100, 'Converting image to hybrid ISO...');
		}
		system('isohybrid ' . $iso);

	}

}
