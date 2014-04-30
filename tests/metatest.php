<?php
require_once '../core/bootstrap.php';

$package = new Package('cc169d8cf40788e21d8ddf97db16c0c1');
$package_file = new PackageFile($package->fspath());
$meta = $package_file->metadata(ServerSettings::$root_path);

print_r($meta);
$package->provides = $meta['provides'];
$package->save();
