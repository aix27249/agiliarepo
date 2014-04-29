<?php
/* Example task runner - sleeps specified amount of time */
require_once dirname(__FILE__) . '/../bootstrap.php';

class FindOldVersionsTask extends AsyncTask {
	public function run() {
		$this->setStatus('running', 'Counting objects');


		$count = 0;
		if (isset($this->options['repositories'])) $repositories = $this->options['repositories'];
		else $repositories = Repository::getList();

		$archsets = ['i686' => Package::queryArchSet('i686'), 'x86_64' => Package::queryArchSet('x86_64')];
		$checkcount = 0;
		foreach($repositories as $reponame) {
			$checkcount++;
			$rep = new Repository($reponame);
			foreach($rep->osversions() as $osversion) {
				foreach($rep->branches() as $branch) {
					foreach($rep->subgroups() as $subgroup) {
						foreach($archsets as $arch => $archset) {
							$path = implode('/', [$reponame, $osversion, $branch, $subgroup]);
							$this->setProgress($checkcount, count($repositories), 'Processing ' . $path);

							$query = ['repositories.repository' => $reponame, 
								'repositories.osversion' => $osversion, 
								'repositories.branch' => $branch, 
								'repositories.subgroup' => $subgroup,
								'arch' => $archset
								];
							$pnames = self::db()->packages->distinct('name', $query);
							$count += count($pnames);
						}
					}
				}
			}
		}

		$counter = 0;
		$this->setProgress($counter, $count, 'Iterating thru packages');
		foreach($repositories as $reponame) {
			$rep = new Repository($reponame);
			foreach($rep->osversions() as $osversion) {
				foreach($rep->branches() as $branch) {
					foreach($rep->subgroups() as $subgroup) {
						foreach($archsets as $arch => $archset) {
							$query = ['repositories.repository' => $reponame, 
								'repositories.osversion' => $osversion, 
								'repositories.branch' => $branch, 
								'repositories.subgroup' => $subgroup,
								'arch' => $archset
								];

							$pnames = self::db()->packages->distinct('name', $query);
							foreach($pnames as $package_name) {
								$pkg = self::db()->packages->findOne(['name' => $package_name, 'arch' => $archset]);
								$package = new Package($pkg);
								$path = implode('/', [$reponame, $osversion, $branch, $subgroup]);
								$packages = $package->altVersions($path);
								Package::recheckLatest($packages, $path, true);

								$counter++;
								$this->setProgress($counter, $count, 'Processing ' . $arch . '/' . $path . ' (' . $counter . '/' . $count . '): ' . $package_name);
							}
						}
					}
				}
			}
		}

		$this->setStatus('complete', 'Finished');
	}
}

if (!isset($argv[1])) die('Task ID not specified');
$task_id = $argv[1];
echo "Task ID: $task_id\n";

$task = new FindOldVersionsTask($task_id);
$task->run();
