<?php

require_once '../core/bootstrap.php';

$package = new Package('7d26d56a9b56c321f3a86595ddd957c0');

$package->setLatest('master/8.1/edge/stable', true);
$package->save();
