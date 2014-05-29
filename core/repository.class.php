<?php
require_once 'mongo.class.php';
class Repository extends MongoDBAdapter {

	public $name = NULL;
	public $settings = NULL;

	/* Constructor. Accepts repository name. */
	public function __construct ($repo_name) {
		$this->name = $repo_name;
		$this->settings = self::db()->repositories->findOne(['name' => $repo_name]);
	}

	public function __toString() {
		return $this->name;
	}

	/* Map properties to settings */
	public function __set($key, $value) {
		$this->settings[$key] = $value;
	}

	public function __get($key) {
		return @$this->settings[$key];
	}



	/* ------- Clone, merge, delete --------- */

	/* Creates a clone of repository
	 * Required permissions:
	 * source: read
	 * global: create_repository
	 *
	 */
	public function cloneTo($to) {
		$any_found = false;
		$count = $this->count();
		$counter = 0;
		
		foreach(self::db()->packages->find(['repositories.repository' => $this->name]) as $pkg) {
			$counter++;
			$any_found = true;
			$records = [];
			foreach($pkg['repositories'] as $record) {
				if ($record['repository']!==$this->name) continue;
				$record['repository'] = $to;
				$records[] = $record;
			}
			if (AsyncTask::$current) AsyncTask::$current->setProgress($counter, $count);
			self::db()->packages->findAndModify(
				['md5' => $pkg['md5'], '_rev' => $pkg['_rev']], 
				[
				'$addToSet' => ['repositories' => ['$each' => $records]], 
				'$inc' => ['_rev' => 1]
				]);
		}

		// Copy configuration record, if any
		$configuration = self::db()->repositories->findOne(['name' => $this->name]);
		if ($configuration) {
			unset($configuration['_id']);
			$configuration['name'] = $to;
			self::db()->repositories->insert($configuration);
		}
		return $any_found;
	}

	
	/* Merge specified repository into this
	 * Logic: 
	 * 	1. every os version and branch, which contains in $repo_name, but not in $this, should be added.
	 * 	2. every package which contains in $repo_name, but not in $this, should be added to $this at the same location.
	 *
	 * TODO: implement
	 */
	public function merge($repo_name) {
	}


	/* Deletes repository
	 * Required permissions:
	 * repository: admin
	 *
	 */

	public function delete() {
		$count = $this->count();
		$counter = 0;

		foreach(self::db()->packages->find(['repositories.repository' => $this->name]) as $pkg) {
			$counter++;
			$newset = [];
			foreach($pkg['repositories'] as $record) {
				if ($record['repository']==$this->name) continue;
				$newset[] = $record;
			}

			if (AsyncTask::$current) AsyncTask::$current->setProgress($counter, $count);
			self::db()->packages->findAndModify(
				['md5' => $pkg['md5'], '_rev' => $pkg['_rev']], 
				[
				'$set' => ['repositories' => $newset], 
				'$inc' => ['_rev' => 1]
				]);
		}
		self::db()->repositories->remove(['name' => $this->name]);
	}




	/* Returns count of packages in repository */
	public function count() {
		return self::db()->packages->count(['repositories.repository' => $this->name]);
	}

	/* Returns repository owner */
	public function owner() {
		$owner = @$this->settings['owner'];
		if (!$owner) return 'admin';
		return $owner;
	}

	/* Returns repository settings, such as permissions */
	public function settings() {
		return $this->settings;
	}

	/* Returns user names which has specified permission on this repository */
	public function whoCan($perm) {
		$permissions = @$this->settings['permissions'];

		@$ret = $permissions[$perm];
		if ($ret) return $ret;
		return ['admin'];
	}

	/* Checks if specified user has specified permission.
	 * Returns true if it can do this, or false if he can't.
	 */
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

	/* Returns git remote URL
	 * FIXME: git model isn't implemented yet 
	 */
	public function gitRemote() {
		return @$this->settings['git_remote'];
	}

	/* Returns OS versions inside this repository
	 * By default, returns all of it, based on repository settings.
	 * If user and permission are specified, returns only ones on which this user has specified permission. (TODO)
	 * If $force_scan is set to true, returns OS versions from packages inside repository, instead of settings. In other words, it returns 'de-facto' data.
	 */
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


	/* Returns branches inside this repository
	 * By default, returns all of it, based on repository settings.
	 * If user and permission are specified, returns only ones on which this user has specified permission. (TODO)
	 * If $force_scan is set to true, returns branches from packages inside repository, instead of settings. In other words, it returns 'de-facto' data.
	 */
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

	/* Returns subgroups inside this repository
	 * By default, returns all of it, based on repository settings.
	 * If user and permission are specified, returns only ones on which this user has specified permission. (TODO)
	 * If $force_scan is set to true, returns subgroups from packages inside repository, instead of settings. In other words, it returns 'de-facto' data.
	 */
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


	public function setPermissions($perms) {
		foreach(['read', 'write', 'admin'] as $perm) {
			$this->settings['permissions'][$perm] = array_values($perms[$perm]);
		}
	}

	/* Update repository settings: write to database new values
	 * Required permissions: 
	 * repository: admin
	 */

	public function update() {
		$this->settings['name'] = $this->name;
		self::db()->repositories->update(['name' => $this->name], $this->settings, ['upsert' => true]);
	}

	/* Set new settings 
	 * Required permissions:
	 * repository: admin
	 *
	 */
	public function setSettings($settings) {
		unset($settings['_id']);
		$settings['name'] = $this->name;
		$this->settings = $settings;
	}

	/* Returns repository list.
	 * If user and permission are specified, return only ones on which user can specified permission
	 */
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

	/* Returns default package path */
	public function defaultPath($prefix = '') {
		if ($prefix!='' && strrpos($prefix, '/')!==strlen($prefix)-1) $prefix .= '/';
		if (isset($this->settings['default_path'])) return $prefix . $this->settings['default_path'];
		return $prefix . $this->name;
	}

	/* Returns setup variants related to this repository */
	public function setup_variants($osversions = NULL) {
		$query = ['repository' => $this->name];
		if (is_array($osversions)) $query['osversion'] = ['$in' => $osversions];
		else if ($osversions!==NULL) $query['osversion'] = $osversions;

		$names = self::db()->setup_variants->distinct('name', $query);
		return $names;
	}

}

