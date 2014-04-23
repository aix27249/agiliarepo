<?php
if (isset($path[2])) $this->modules = ['content' => ['admin_' . $path[2]], 'sidebar' => ['admin', 'admin_' . $path[2]]];
else $this->modules = ['content' => ['admin']];
$this->title = 'Administration';
