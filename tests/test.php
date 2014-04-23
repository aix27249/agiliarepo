<?php

require_once 'package.class.php';

$pkgfile = 'snappy-1.1.1-x86_64-1.txz';

/*
$data = PackageFile::metadata($pkgfile);
$xml = PackageFile::xml($pkgfile);
$json = PackageFile::json($pkgfile);
print_r($json);
 */
$package = new PackageFile($pkgfile);
print_r($package->metadata());
