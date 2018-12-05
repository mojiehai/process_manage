<?php

require __DIR__."/bootstrap.php";

use ProcessManage\Exception\Exception;
use ProcessManage\Process\Manage;
use ProcessManage\Process\Process;

$config = [
    // 进程基础配置
    'titlePrefix' => 'queue_task',   // 进程前缀
    'baseTitle' => 'push',  // 进程基础名称

    // master 进程配置
    'checkWorkerInterval' => 10,    // 10秒检测一次进程
    'maxWorkerNum' => 30,    //30个进程

    // worker 进程配置
    'executeTimes' => 1,    // 任务的最大执行次数
    'executeUSleep' => 1000000,  // 每次执行任务睡眠时间(微秒) 1s = 1 000 000 us (1s)
    'limitSeconds' => 10800,    // 工作进程最大执行时长(秒)(跑3个小时重启)
];

try {
    // 创建进程管理器
    (new Manage($config))
        ->setWorkInit(
            function (Process $process) {
                // init
            }
        )
        ->setWork(
            // 执行的工作内容
            function(Process $process) {
                //return ;
            })
        ->run();
} catch (Exception $e) {
    echo $e->getExceptionAsString();
}
