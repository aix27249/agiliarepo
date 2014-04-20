<?php

require_once '../core/mongo.class.php';

$d = MongoConnection::c();
$db = $d->agiliarepo;

$from = 'master';
$to = 'testclone';
foreach($db->packages->find(['repositories.repository' => $from]) as $pkg) {
	$records = [];
	foreach($pkg['repositories'] as $record) {
		if ($record['repository']!==$from) continue;
		$record['repository'] = $to;
		$records[] = $record;
	}
	$db->packages->findAndModify(
		['md5' => $pkg['md5'], '_rev' => $pkg['_rev']], 
		[
			'$addToSet' => ['repositories' => ['$each' => $records]], 
			'$inc' => ['_rev' => 1]
		]);
}
