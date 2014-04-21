<?php
/* Example task runner - sleeps specified amount of time */
require_once dirname(__FILE__) . '/../bootstrap.php';
require_once dirname(__FILE__) . '/../../tests/bridge_run.private.php';

class RepositoryBridgeSyncTask extends AsyncTask {
	public function run() {
		global $dbhost, $dbusername, $dbpass, $dbname;
		$this->setStatus('running');
		
		$bridge = new RepositoryBridge($dbhost, $dbusername, $dbpass, $dbname);
		$this->setProgress(0, 100, 'Searching for new packages');
		$bridge->getNewPackages($this);
		$this->setStatus('complete', 'Finished');
	}
}

if (!isset($argv[1])) die('Task ID not specified');
$task_id = $argv[1];
echo "Task ID: $task_id\n";

$task = new RepositoryBridgeSyncTask($task_id);
$task->run();
