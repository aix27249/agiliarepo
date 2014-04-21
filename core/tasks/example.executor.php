<?php
/* Example task runner - sleeps specified amount of time */
require_once dirname(__FILE__) . '/../bootstrap.php';

class ExampleTask extends AsyncTask {
	public function run() {
		$this->setStatus('running');
		$sleep_time = intval($this->options['sleep_time']);
		for ($i=0; $i<$sleep_time; $i++) {
			$this->setProgress(($i+1), $sleep_time, 'Sleeping ' . $i . ' of ' . $sleep_time);
			sleep(1);
		}
		$this->setStatus('complete', 'Finished');
	}
}

if (!isset($argv[1])) die('Task ID not specified');
$task_id = $argv[1];
echo "Task ID: $task_id\n";

$task = new ExampleTask($task_id);
$task->run();
