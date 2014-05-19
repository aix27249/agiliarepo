<?php
require_once 'mongo.class.php';
class Repository extends MongoDBAdapter {

	public $name = NULL;
	public $settings = NULL;

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

		// Copy configuration record, if any
		$configuration = self::db()->repositories->findOne(['name' => $from]);
		if ($configuration) {
			unset($configuration['_id']);
			$configuration['name'] = $to;
			self::db()->repositories->insert($configuration);
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

	public function can($user, $perm) {
		if ($user==='admin') return true;
		$whocan = $this->whoCan($perm);
		if (in_array($user->name, $whocan, true)) return true;
		if (in_array('@everyone', $whocan, true)) return true;
		foreach($user->groups as $group) {
			if (in_array('@' . $group, $whocan, true)) return true;
		}
		return false;
	}

	public function gitRemote() {
		return @$this->settings['git_remote'];
	}

	public function osversions($user = NULL, $permission = NULL, $force_scan = false) {
		$settings = @$this->settings['osversions'];
		$defacto = [];
		if (!$settings || $force_scan) $defacto = self::db()->packages->distinct('repositories.osversion', ['repositories.repository' => $this->name]);
		
		if ($settings) $osversions = array_unique(array_merge($defacto, $settings));
		else $osversions = $defacto;

		if (!$permission) return $osversions;
		if (!$user) return [];
		if (!$this->can($user, $permission)) return [];

		$ret = [];
		foreach($osversions as $osver) {
			// TODO: add permission check for specific OS version within that repository
			$ret[] = $osver;
		}

		return $ret;
	}

	public function branches($osversion = NULL, $user = NULL, $permission = NULL, $force_scan = false) {
		$settings = @$this->settings['branches'];
		$defacto = [];

		if (!$settings || $force_scan) $defacto = self::db()->packages->distinct('repositories.branch', ['repositories.repository' => $this->name]);
		
		if ($settings) $branches = array_unique(array_merge($defacto, $settings));
		else $branches = $defacto;

		if (!$permission) return $branches;
		if (!$user) return [];
		if (!$this->can($user, $permission)) return [];

		$ret = [];
		foreach($branches as $branch) {
			// TODO: add permission check for specific branch within that repository/osver
			$ret[] = $branch;
		}

		return $ret;
	}

	public function subgroups($osversion = NULL, $branch = NULL, $user = NULL, $permission = NULL, $force_scan = false) {
		$settings = @$this->settings['subgroups'];
		$defacto = [];
		if (!$settings || $force_scan) $defacto = self::db()->packages->distinct('repositories.subgroup', ['repositories.repository' => $this->name]);
		
		if ($settings) $subgroups = array_unique(array_merge($defacto, $settings));
		else $subgroups = $defacto;

		if (!$permission) return $subgroups;
		if (!$user) return [];
		if (!$this->can($user, $permission)) return [];

		$ret = [];
		foreach($subgroups as $sub) {
			// TODO: add permission check for specific subgroup within that repository/osver/branch
			$ret[] = $sub;
		}

		return $ret;
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

	public function setSettings($settings) {
		unset($settings['_id']);
		$settings['name'] = $this->name;
		$this->settings = $settings;
	}

	public static function getList($user = NULL, $permission = NULL, $force_scan = false) {
		$meta = self::db()->repositories->distinct('name');
		$repos = [];
		if ($force_scan) $repos = self::db()->packages->distinct('repositories.repository');
		$all_repos = array_unique(array_merge($repos, $meta));

		if (!$permission) return $all_repos;
		if (!$user) return [];
		$ret = [];
		foreach($all_repos as $reponame) {
			$rep = new Repository($reponame);
			if ($rep->can($user, $permission)) $ret[] = $reponame;
		}

		return $ret;
	}

	public function defaultPath($prefix = '') {
		if ($prefix!='' && strrpos($prefix, '/')!==strlen($prefix)-1) $prefix .= '/';
		if (isset($this->settings['default_path'])) return $prefix . $this->settings['default_path'];
		return $prefix . $this->name;
	}

	public function setup_variants($osversions = NULL) {
		$query = ['repository' => $this->name];
		if (is_array($osversions)) $query['osversion'] = ['$in' => $osversions];
		else if ($osversions!==NULL) $query['osversion'] = $osversions;

		$names = self::db()->setup_variants->distinct('name', $query);
		return $names;
	}

}

