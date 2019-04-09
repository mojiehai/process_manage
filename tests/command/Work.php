<?php

namespace ProcessManage\Command\Options;

use ProcessManage\Process1\Worker;

class Work
{
    public $config = [
        // 进程基础配置
        'titlePrefix' => 'process_m',   // 进程前缀
        'baseTitle' => 'test',  // 进程基础名称

        // master 进程配置
        'checkWorkerInterval' => 180,    // n秒检测一次进程(<=0则为不检测)
        'maxWorkerNum' => 2,    //2个进程

        // worker 进程配置
        'executeTimes' => 0,    // 任务的最大执行次数(0为没有最大执行次数，一直执行)
        'executeUSleep' => 10000000,  // 每次执行任务睡眠时间(微秒) 1s = 1 000 000 us (1s)
        'limitSeconds' => 10800,    // 工作进程最大执行时长(秒)(跑3个小时重启)
    ];


    public function init(Worker $process)
    {
        $time = time();
        // init
        \ProcessManage\Log\ProcessLog::info('work init ... '.date('Y-m-d H:i:s', $time));
        return $time;
    }

    public function work(Worker $process, $result)
    {
        // work
        \ProcessManage\Log\ProcessLog::info('work run ..., times: '.$process->getExecuteTimes().' , start time:'.date('Y-m-d H:i:s', $result));
    }
}