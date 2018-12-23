<?php

require __DIR__ .'/..'. "/bootstrap.php";
require __DIR__.'/Work.php';
require __DIR__.'/Start.php';
require __DIR__.'/ReStart.php';
require __DIR__.'/Stop.php';
require __DIR__.'/Status.php';
require __DIR__.'/D.php';
require __DIR__.'/ManageProcessTemplate.php';

use ProcessManage\Command\Command;
use ProcessManage\Command\Template\ManageProcessTemplate;

$command = new Command(new ManageProcessTemplate());
$command->run();
