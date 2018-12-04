<?php

require __DIR__."/bootstrap.php";

use ProcessManage\Command\Command;

$config = include __DIR__.'/config.php';


$command = new Command();
$command->run();


