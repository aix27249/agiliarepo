<?php
/* Example task runner - sleeps specified amount of time */
require_once dirname(__FILE__) . '/../bootstrap.php';

class FindOldVersionsTask extends AsyncTask {
	public function run() {
		$this->setStatus('running', 'Counting objects');


		$count = 0;
		if (isset($this->options['repositories'])) $repositories = $this->options['repositories'];
		else $repositories = Repository::getList();

		$selected_package_name = NULL;
		if (isset($this->options['package_name'])) $selected_package_name = trim($this->options['package_name']);

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
							if (trim($selected_package_name)!=='') $pnames = [$selected_package_name];
							else {
								$pnames = self::db()->packages->distinct('name', $query);
							}
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

							if (trim($selected_package_name)!=='') {
								echo "PNAME specified as $selected_package_name\n";
								$pnames = [$selected_package_name];
							}
							else {
								echo "Querying distinct names, query: " . print_r($query, true) . "\n";
								$pnames = self::db()->packages->distinct('name', $query);
							}
							

							echo "At $counter: pnames pack, size " . count($pnames) . "\n";
							foreach($pnames as $package_name) {
								$pkg = self::db()->packages->findOne(['name' => $package_name, 'arch' => $archset]);
								$package = new Package($pkg);
								$path = implode('/', [$reponame, $osversion, $branch, $subgroup]);
								$patharray = ['repository' => $reponame, 'osversion' => $osversion, 'branch' => $branch, 'subgroup' => $subgroup];
								$packages = $package->altVersions($patharray, $archset);
								Package::recheckLatest($packages, $patharray, true);

								$counter++;
								$this->setProgress($counter, $count, 'Processing ' . $arch . '/' . $path . ' (' . $counter . '/' . $count . '): ' . $package_name);
								echo "At $counter: done\n";
							}
							echo "$path Iterating next arch\n";
						}
						echo "$path Iterating next subset\n";
					}
					echo "$path Iterating next branch\n";
				}
				echo "$path Iterating next osversion\n";
			}
			echo "$path Iterating next repository\n";
		}
		echo "Complete at $path\n";

		$this->setStatus('complete', 'Finished after processing ' . $counter . ' packages of ' . $count);
	}
}

if (!isset($argv[1])) die('Task ID not specified');
$task_id = $argv[1];
echo "Task ID: $task_id\n";

$task = new FindOldVersionsTask($task_id);
$task->run();
