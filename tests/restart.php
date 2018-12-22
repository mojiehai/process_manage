<?php

use ProcessManage\Exception\ProcessException;

try {
    $manage = (include __DIR__."/handler.php");
    $manage->restart();
} catch (ProcessException $e) {
    echo $e->getExceptionAsString();
}
