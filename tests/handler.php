<?php

require __DIR__."/bootstrap.php";

use ProcessManage\Process1\Manage;
use ProcessManage\Process1\Process;
use ProcessManage\Process1\Worker;

$config = [
    // 进程基础配置
    'titlePrefix' => 'process_m',   // 进程前缀
    'baseTitle' => 'test',  // 进程基础名称

    // master 进程配置
    'checkWorkerInterval' => 600,    // n秒检测一次进程(<=0则为不检测)
    'maxWorkerNum' => 1,    //1个进程

    // worker 进程配置
    'executeTimes' => 0,    // 任务的最大执行次数(0为没有最大执行次数，一直执行)
    'executeUSleep' => 10000000,  // 每次执行任务睡眠时间(微秒) 1s = 1 000 000 us (1s)
    'limitSeconds' => 10800,    // 工作进程最大执行时长(秒)(跑3个小时重启)
];

// 创建进程管理器
return (new Manage($config))
    ->setWorkInit(
    // 工作内容初始化
        function (Process $process) {
            // init
            \ProcessManage\Log\ProcessLog::info('work init ... ');
        }
    )
    ->setWork(
    // 执行的工作内容
        function(Worker $process) {
            // work
            \ProcessManage\Log\ProcessLog::info('work run ... ');
        });

