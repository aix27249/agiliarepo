<?php

/* A task queue runner
 *
 * Algorithm is simple:
 *   Check if a new task is there to execute
 *   Check if a file with specified task type exists
 *   Mark task status as "starting"
 *   Execute it by calling shell_exec, specifying task ID to it
 *   Loop again
 *
 * 
 */
require_once 'bootstrap.php';

TaskQueueRunner::loop();
