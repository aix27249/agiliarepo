<?php
require_once '../core/bootstrap.php';
$reponame = 'master';
$osversion = '8.1';
$branch = 'core';
$subgroup = 'stable';

$patharray = ['repository' => $reponame, 'osversion' => $osversion, 'branch' => $branch, 'subgroup' => $subgroup];

$package = new Package('a38d21cd3c7c325e5c834b3f13a40113');
$packages = $package->altVersions($patharray);
Package::recheckLatest($packages, $patharray, true);

