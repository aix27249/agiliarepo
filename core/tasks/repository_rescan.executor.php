<?php
/* Example task runner - sleeps specified amount of time */
require_once dirname(__FILE__) . '/../bootstrap.php';

class RepositoryRescanTask extends AsyncTask {
	public function run() {
		$this->setStatus('running');

		$count = self::db()->packages->count();
		$packages = self::db()->packages->find();
		$counter = 0;
		foreach($packages as $pkg) {
			$counter++;
			$package = new Package($pkg);
			$this->setProgress($counter, $count, 'Checking ' . $counter . '/' . $count . ': '. $package);
			$package_file = new PackageFile($package->fspath());
			$metadata = $package_file->metadata(ServerSettings::$root_path);
			$package->provides = $metadata['provides'];
			$package->conflicts = $metadata['conflicts'];
			$package->config_files = $metadata['config_files'];
			$package->save();
			
		}
		$this->setStatus('complete', 'Finished processing ' . $counter . ' files of ' . $count);
	}
}

if (!isset($argv[1])) die('Task ID not specified');
$task_id = $argv[1];
echo "Task ID: $task_id\n";

$task = new RepositoryRescanTask($task_id);
$task->run();
