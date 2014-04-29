<?php

require_once '../core/bootstrap.php';

$task = AsyncTask::create('admin', 'find_old_versions', 'Find old versions', ['repositories' => ['testclone', 'testclone3']]);
echo "Task ID: $task\n";
