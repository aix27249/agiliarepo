<?php
require_once '../core/bootstrap.php';

$package = new Package('d47b652b38e95003b11f1f7a694c3ae1');
$package->provides = ['xfburn'];
$package->save();
