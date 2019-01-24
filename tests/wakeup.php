<?php

use ProcessManage\Exception\ProcessException;

try {
    //$manage = (include __DIR__."/handler.php");
    $manage = (include __DIR__."/handlerMultiple.php");
    $manage->wakeup();
} catch (ProcessException $e) {
    echo $e->getExceptionAsString();
}
