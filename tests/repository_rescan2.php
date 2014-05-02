<?php

require_once '../core/bootstrap.php';

$task = AsyncTask::create('admin', 'repository_rescan2', 'Repository rescan');
echo "Task ID: $task\n";
