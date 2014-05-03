<?php

require_once '../core/bootstrap.php';

$task = AsyncTask::create('admin', 'mirror', 'Test mirror from flow.agilialinux.ru to local machine', ['server_url' => 'http://flow.agilialinux.ru/', 'repository_name' => 'master'] );
echo "Task ID: $task\n";
