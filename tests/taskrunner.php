<?php

require_once '../core/bootstrap.php';

$task = AsyncTask::create('admin', 'example', 'Trying example task execution', ['sleep_time' => 10], ['complete' => 'alert("Task finished");']);
echo "Task ID: $task\n";
