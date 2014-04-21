<?php
require_once 'mongo.class.php';
class Repository extends MongoDBAdapter {

	private $name = NULL;
	private $settings = NULL;

	public static function createClone($from, $to, $task = NULL) {
		$any_found = false;
		$source = new Repository($from);
		$count = $source->count();
		$counter = 0;
		
		foreach(self::db()->packages->find(['repositories.repository' => $from]) as $pkg) {
			$counter++;
			$any_found = true;
			$records = [];
			foreach($pkg['repositories'] as $record) {
				if ($record['repository']!==$from) continue;
				$record['repository'] = $to;
				$records[] = $record;
			}
			if ($task) $task->setProgress($counter, $count);
			self::db()->packages->findAndModify(
				['md5' => $pkg['md5'], '_rev' => $pkg['_rev']], 
				[
				'$addToSet' => ['repositories' => ['$each' => $records]], 
				'$inc' => ['_rev' => 1]
				]);
		}
		return $any_found;
	}
	public static function delete($repo_name, $task = NULL) {
		$rep = new Repository($repo_name);
		$count = $rep->count();
		$counter = 0;

		foreach(self::db()->packages->find(['repositories.repository' => $repo_name]) as $pkg) {
			$counter++;
			$newset = [];
			foreach($pkg['repositories'] as $record) {
				if ($record['repository']==$repo_name) continue;
				$newset[] = $record;
			}

			if ($task) $task->setProgress($counter, $count);
			self::db()->packages->findAndModify(
				['md5' => $pkg['md5'], '_rev' => $pkg['_rev']], 
				[
				'$set' => ['repositories' => $newset], 
				'$inc' => ['_rev' => 1]
				]);
		}
		self::db()->repositories->remove(['name' => $repo_name]);
	}

	public static function getList() {
		$repos = self::db()->packages->distinct('repositories.repository');
		$meta = self::db()->repositories->distinct('name');
		return array_unique(array_merge($repos, $meta));
	}


	public function __construct ($repo_name) {
		$this->name = $repo_name;
		$this->settings = self::db()->repositories->findOne(['name' => $repo_name]);
	}
	public function count() {
		return self::db()->packages->count(['repositories.repository' => $this->name]);
	}

	public function __toString() {
		return $this->name;
	}

	public function owner() {
		$owner = @$this->settings['owner'];
		if (!$owner) return 'admin';
		return $owner;
	}

	public function settings() {
		return $this->settings;
	}

	public function whoCan($perm) {
		$permissions = @$this->settings['permissions'];

		@$ret = $permissions[$perm];
		if ($ret) return $ret;
		return ['admin'];
	}

	public function gitRemote() {
		return @$this->settings['git_remote'];
	}

	public function osversions() {
		$defacto = self::db()->packages->distinct('repositories.osversion', ['repositories.repository' => $this->name]);
		$settings = @$this->settings['osversions'];
		if ($settings) return array_unique(array_merge($defacto, $settings));
		else return $defacto;
	}

	public function __set($key, $value) {
		$this->settings[$key] = $value;
	}

	public function __get($key) {
		return @$this->settings[$key];
	}

	public function setPermissions($perms) {
		foreach(['read', 'write', 'admin'] as $perm) {
			$this->settings['permissions'][$perm] = array_values($perms[$perm]);
		}
	}
	public function update() {
		$this->settings['name'] = $this->name;
		self::db()->repositories->update(['name' => $this->name], $this->settings, ['upsert' => true]);
	}
}

