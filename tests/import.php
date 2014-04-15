<?php

require_once '../core/bootstrap.php';
require_once '../core/import.class.php';

// Old tree system here
$branches = ['badversions', 'core', 'edge', 'games', 'next', 'staging', 'testing', 'userland'];
$osversions = ['8.0', '8.0_deprecated'];
$arches = ['x86', 'x86_64'];

$root_path = '/home/aix/www/repositories/package_tree'; // Root path isn't stored in database; should be specified in config instead

foreach($branches as $branch) {
	foreach($osversions as $osver) {
		foreach($arches as $arch) {
			$dir = $root_path . '/' . $branch . '/' . $osver . '/' . $arch . '/repository';
			$new_osver = '8.1';
			$new_branch = $branch;
			$new_subgroup = 'stable';
			$is_latest = true;
			if ($branch==='next') {
				$new_osver = '9.0';
				$new_branch = 'core';
			}
			else if ($branch==='testing') {
				$new_subgroup = 'testing';
				$new_branch = 'userland';
			}
			if ($osver==='8.0_deprecated') {
				$is_latest = false;
			}
			

			ImportDirectory::import($dir, $root_path, ['master'], [$new_osver], [$new_branch], [$new_subgroup], $is_latest);
		}
	}
}
