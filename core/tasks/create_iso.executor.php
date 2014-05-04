<?php
require_once dirname(__FILE__) . '/../bootstrap.php';

class CreateIsoTask extends AsyncTask {
	public function run() {

		// Query packages which should be added to ISO
		$this->setStatus('running', 'Querying packages to be added');
		$query = [
			'repositories.repository' => $this->options['repository'],
			'repositories.latest' => true,
			'repositories.branch' => ['$in' => $this->options['branches']],
			'repositories.osversion' => ['$in' => $this->options['osversions']],
			'repositories.subgroup' => ['$in' => $this->options['subgroups']],
			];
		$archset = Package::queryArchSet($this->options['arch']);
		if ($archset!==NULL) {
			$query['arch'] = $archset;
		}

		// Call IsoBuilder to build this stuff
		IsoBuilder::makeISO($this->options['iso_name'], $this->options['iso_template'], $query, $this->owner, $this);

		$this->setProgress(100, 100, 'Done');
		$this->setStatus('complete', 'ISO created (maybe, successfully)');
		


	}
}
$task_id = $argv[1];
echo "Task ID: $task_id\n";

$task = new CreateIsoTask($task_id);
$task->setPid();
$task->run();


