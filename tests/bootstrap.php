<?php

define('PROCESS_ROOT', dirname(__DIR__));

function __autoload($class)
{
    $classArr = explode('\\', $class);
    $file = PROCESS_ROOT;
    foreach ($classArr as $v) {
        if ($v == 'ProcessManage') {
            $v = 'src';
        } else if ($v == 'Tests') {
            $v = 'tests';
        }
        $file .= DIRECTORY_SEPARATOR . $v;
    }
    $file .= '.php';

    if (file_exists($file)) {
        require $file;
    }
}
