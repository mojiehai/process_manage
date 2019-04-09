<?php

use ProcessManage\Exception\Exception;
use ProcessManage\Process1\Manage;

try {
    //$manage = (include __DIR__."/handler.php");
    $manage = (include __DIR__."/handlerMultiple.php");
    Manage::showStatus($manage->status());
} catch (Exception $e) {
    echo $e->getExceptionAsString();
}
