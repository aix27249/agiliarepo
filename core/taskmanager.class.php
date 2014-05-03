<?php
require_once 'mongo.class.php';

class AsyncTask extends MongoDBAdapter {
	private $task;
	public function __construct($id = NULL) {
		if (is_string($id)) {
			$task = self::db()->tasks->findOne(['_id' => new MongoId($id)]);
			if (!$task) throw new Exception('Task ' . $id . ' not found');
			$this->task = $task;
		}
		else if (is_array($id)) {
			$this->task = $id;
		}
	}
	public function __get($key) {
		return @$this->task[$key];
	}

	public function __set($key, $value) {
		$this->task[$key] = $value;
	}


	public static function create($owner_name, $type, $description = '', $options = [], $js_callback = []) {
		$task = [
			'status' => 'new',
			'owner' => $owner_name,
			'type' => $type,
			'description' => $description,
			'options' => $options,
			'created' => new MongoDate(),
			'progress' => 0,
			'current_state' => '',
			'_rev' => 1,
			'js_callback' => $js_callback,
			'pid' => 0
			];
		$a = self::db()->tasks->insert($task);

		return trim($task['_id']);
	}
	public function executor() {
		if (isset($this->options['executor'])) return $this->options['executor'];
		return dirname(__FILE__) . '/tasks/' . $this->type . '.executor.php';
	}

	public function setPid($pid = NULL) {
		if (!$pid) $pid = getmypid();
		$this->pid = $pid;
		$this->update();
	}

	public function update() {
		self::db()->tasks->update(['_id' => new MongoId($this->_id)], $this->task);
	}
	public function setStatus($status, $current_state = '') {
		$this->status = $status;
		$this->current_state = $current_state;
		$this->update();
	}

	public function setProgress($current, $max = 100, $message = NULL) {
		$this->progress = ceil(($current/$max)*100);
		if ($this->progress>100) $this->progress = 100;
		if ($message!==NULL) $this->current_state = $message;
		else $this->current_state = $current . '/' . $max;
		$this->update();
	}

	public function raw() {
		return $this->task;
	}

}


class TaskRunner extends MongoDBAdapter {
	public static function run(AsyncTask $task) {
		echo "Initializing task\n";
		$task->setStatus('init');
		// Check if file exists
		$executor = $task->executor();
		if (!file_exists($executor)) {
			"Executor not found: $executor\n";
			$task->setStatus('failed', 'Task executor not found: ' . $executor);
			return false;
		}
		echo "Executor found: $executor, running\n";
		$cmd = 'nohup  php ' . $executor . ' ' . $task->_id . ' >> /tmp/agiliarepo_taskmanager.log 2>&1 &';
		echo "Command: $cmd\n";
		shell_exec($cmd);
		echo "Task started, pid: [$pid]\n";
		return true;

	}

}

// 
class TaskManager extends MongoDBAdapter {
	public static function nextTask() {
		$task = self::db()->tasks->findOne(['status' => 'new']);
		if (!$task) {
			//echo "No new tasks\n";
			return NULL;
		}
		return new AsyncTask(trim($task['_id']));
	}

	public static function tasks($query = []) {
		$tasks = self::db()->tasks->find($query);

		$ret = [];
		foreach($tasks as $task) {
			$ret[] = new AsyncTask($task);
		}
		return $ret;
	}


}

class TaskQueueRunner {
	public static function loop($sleep_time = 1) {

		$counter = 0;
		while (true) {
			//echo "Loop start ($counter)\n";
			$task = TaskManager::nextTask();
			if ($task) {
				echo "Found task " . $task->_id;
				TaskRunner::run($task);
			}
			//echo "Sleeping $sleep_time\n";
			sleep($sleep_time);
		}
	}
}
