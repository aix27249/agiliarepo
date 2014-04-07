<?php

require_once 'package.class.php';

$pkgfile = 'snappy-1.1.1-x86_64-1.txz';

/*
$data = Package::metadata($pkgfile);
$xml = Package::xml($pkgfile);
$json = Package::json($pkgfile);
print_r($json);
 */
$package = new Package($pkgfile);
print_r($package->metadata());
