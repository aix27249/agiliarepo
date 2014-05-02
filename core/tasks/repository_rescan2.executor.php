<?php
/* Add missing data from MySQL database: provides, conflicts, config_files */
require_once dirname(__FILE__) . '/../bootstrap.php';
require_once dirname(__FILE__) . '/../../tests/bridge_run.private.php';

class RepositoryBridgeSyncTask extends AsyncTask {
	public function run() {
		global $dbhost, $dbusername, $dbpass, $dbname;
		$this->setStatus('running');

		$count = self::db()->packages->count();
		$counter = 0;

		$bridge = new AdvancedBridge($dbhost, $dbusername, $dbpass, $dbname);
		$packages = iterator_to_array(self::db()->packages->find());
		$this->setProgress(0, $count, 'Searching for new packages');
		foreach($packages as $pkg) {
			$counter++;
			$package = new Package($pkg);
			$this->setProgress($counter, $count, 'Processing ' . $counter . '/' . $count . ': ' . $package);

			$ext = $bridge->getData($package->md5);
			if ($ext) {
				$package->provides = [$ext['provides']];
				$package->conflicts = [$ext['conflicts']];
				$package->config_files = $ext['config_files'];
				$package->save();
			}
			else {
				try {
					$package_file = $package->packageFile();
					$meta = $package_file->metadata();
					$package->provides = [$meta['provides']];
					$package->conflicts = [$meta['conflicts']];
					$package->config_files = $meta['config_files'];
					$package->save();
				}
				catch (Exception $e) {
					echo $e->getMessage();
				}
			}
		}

		$this->setStatus('complete', 'Finished');
	}
}


class AdvancedBridge extends RepositoryBridge {

	public function getData($md5) {
		$stmt = $this->mysqli->prepare('SELECT packages.package_id, packages.package_conflicts, packages.package_provides FROM packages WHERE packages.package_md5=?') or die($this->mysqli->error);
		$stmt->bind_param('s', $md5) or die($stmt->error);
		$stmt->bind_result($id, $conflicts, $provides) or die($stmt->error);
		$ret = [];
		$stmt->execute() or die($stmt->error);
		$stmt->store_result();
		if ($stmt->fetch()) {
			$ret['provides'] = $provides;
			$ret['conflicts'] = $conflicts;
		}
		else {
			$stmt->free_result();
			$stmt->close();
			return NULL;
		}
		$stmt->free_result();
		$stmt->close();

		$ret['config_files'] = [];
	
		$stmt = $this->mysqli->prepare('SELECT config_files.filename FROM config_files WHERE package_id=?') or die($this->mysqli->error);
		$stmt->bind_param('i', $id) or die($stmt->error);
		$stmt->bind_result($filename) or die($stmt->error);
		$stmt->execute() or die($stmt->error);
		$ret['config_files'] = [];
		while ($stmt->fetch()) {
			$ret['config_files'][] = trim($filename);
		}
		$stmt->close();


		return $ret;

	}

}

if (!isset($argv[1])) die('Task ID not specified');
$task_id = $argv[1];
echo "Task ID: $task_id\n";

$task = new RepositoryBridgeSyncTask($task_id);
echo "PID: " . $task->pid . "\n";
$task->run();
