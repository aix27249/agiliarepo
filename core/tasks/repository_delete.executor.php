<?php
/* Example task runner - sleeps specified amount of time */
require_once dirname(__FILE__) . '/../bootstrap.php';

class RepositoryDeleteTask extends AsyncTask {
	public function run() {
		$this->setStatus('running');
		$repname = $this->options['repname'];

		$this->setProgress(0, 100, 'Delete in progress');
		try {
			$repository = new Repository($repname);
			$repository->delete();
		}
		catch (Exception $e) {
			$this->setStatus('failed', $e->getMessage());
			die();
		}

		$this->setStatus('complete', 'Finished');
	}
}

if (!isset($argv[1])) die('Task ID not specified');
$task_id = $argv[1];
echo "Task ID: $task_id\n";

$task = new RepositoryDeleteTask($task_id);
$task->run();
