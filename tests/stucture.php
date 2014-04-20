<?php

require_once '../core/mongo.class.php';

$d = MongoConnection::c();
$db = $d->agiliarepo;


foreach($db->packages->find() as $pkg) {
	foreach($pkg['repository'] as $rep) {
		foreach($pkg['osversion'] as $osver) {
			foreach($pkg['branch'] as $branch) {
				foreach($pkg['subgroup'] as $sub) {
					$pkg['repositories'][] = ['repository' => $rep, 'osversion' => $osver, 'branch' => $branch, 'subgroup' => $sub];
				}
			}
		}
	}
	$pkg['_rev'] = 1;
	$db->packages->update(['md5' => $pkg['md5']], $pkg);
}
