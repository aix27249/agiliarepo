<?php
Page::loadModule('repository');
class TaskMonitor extends Module_taskmon {
	public static function taskProgress($task, $execute_callbacks = true) {
		if (is_string($task)) {
			try {
				$task = new AsyncTask($task);
			}
			catch (Exception $e) {
				return $e->getMessage();
			}
		}

		$ret = '<div class="taskprogress" id="taskprogress_' . $task->_id . '" data-task-id="' . $task->_id . '">';
		$ret .= '<div class="task_data">ID: ' . $task->_id . ', type: ' . $task->type . '</div>
			<div class="task_status">' . $task->status . '</div>';
		$ret .= '<div class="task_description">' . $task->description . '</div>';

		$ret .= '<div class="task_progress">
			<div class="task_progress_text">' . $task->progress . '%</div>
			<div class="task_progress_bar"></div>
			</div>';
		$ret .= '<div class="task_state">' . $task->current_state . '</div>';

		$ret .= '</div>';
		$ret .= '<script>taskmon_execute_callbacks = ' . ($execute_callbacks ? 'true' : 'false') . ';</script>';

		return $ret;
	}
}

class Module_taskmon extends RepositoryModule {
	public static $scripts = ['taskmon.js'];
	public static $styles = ['taskmon.css'];

	public function run() {
		$query = [];
		if (isset($this->page->path[2])) {
			if ($this->page->path[2]==='poll') {
				die($this->poll(@$this->page->path[3]));
			}
			if ($this->page->path[2]==='active') {
				$query = ['status' => ['$nin' => ['complete']]];
			}
		}

		$tasks = TaskManager::tasks($query);
		$ret = '<div class="taskmanager">';
		foreach($tasks as $task) {
			$ret .= TaskMonitor::taskProgress($task, false); 
		}


		$ret .= '</div>';


		return $ret;
	}
	public function poll($task_id) {
		$ret = [];
		try {
			$task = new AsyncTask($task_id);
		}
		catch (Exception $e) {
			return json_encode(['error' => $e->getMessage()]);
		}

		

		return json_encode($task->raw());
	}

}
