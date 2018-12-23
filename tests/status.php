<?php

require __DIR__."/bootstrap.php";

use ProcessManage\Exception\ProcessException;
use ProcessManage\Exception\Exception;
use ProcessManage\Process\Manage;

$config = [
    // 进程基础配置
    'baseTitle' => 'test',  // 进程基础名称
];

try {
    // 创建进程管理器
    (new Manage($config))->showStatus();
} catch (Exception $e) {
    echo $e->getExceptionAsString();
}
