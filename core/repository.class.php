<?php
require_once 'mongo.class.php';
class Repository extends MongoDBAdapter {
	public static function createClone($from, $to) {
		$any_found = false;
		foreach(self::db()->packages->find(['repositories.repository' => $from]) as $pkg) {
			$any_found = true;
			$records = [];
			foreach($pkg['repositories'] as $record) {
				if ($record['repository']!==$from) continue;
				$record['repository'] = $to;
				$records[] = $record;
			}
			self::db()->packages->findAndModify(
				['md5' => $pkg['md5'], '_rev' => $pkg['_rev']], 
				[
				'$addToSet' => ['repositories' => ['$each' => $records]], 
				'$inc' => ['_rev' => 1]
				]);
		}
		return $any_found;
	}
	public static function delete($repo_name) {
		foreach(self::db()->packages->find(['repositories.repository' => $repo_name]) as $pkg) {
			$newset = [];
			foreach($pkg['repositories'] as $record) {
				if ($record['repository']==$repo_name) continue;
				$newset[] = $record;
			}
			self::db()->packages->findAndModify(
				['md5' => $pkg['md5'], '_rev' => $pkg['_rev']], 
				[
				'$set' => ['repositories' => $newset], 
				'$inc' => ['_rev' => 1]
				]);
		}


	}
}

