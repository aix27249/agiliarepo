<?php
/* Example task runner - sleeps specified amount of time */
require_once dirname(__FILE__) . '/../bootstrap.php';

class RepositoryCloneTask extends AsyncTask {
	public function run() {
		$this->setStatus('running');
		$from = $this->options['from'];
		$to = $this->options['to'];

		$this->setProgress(0, 100, 'Cloning in progress');
		try {
			$repository = new Repository($from);
			$repository->cloneTo($to);
		}
		catch (Exception $e) {
			$this->setStatus('failed', $e->getMessage());
			die($e->getMessage());
		}
		$this->setStatus('complete', 'Finished');
	}
}

if (!isset($argv[1])) die('Task ID not specified');
$task_id = $argv[1];
echo "Task ID: $task_id\n";

$task = new RepositoryCloneTask($task_id);
$task->run();
