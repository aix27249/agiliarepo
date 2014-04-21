<?php

require_once '../core/bootstrap.php';

$task = AsyncTask::create('admin', 'repository_bridge_sync', 'Bridge sync');
echo "Task ID: $task\n";
