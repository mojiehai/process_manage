<?php

require __DIR__."/bootstrap.php";

use ProcessManage\Exception\Exception;
use ProcessManage\Process\Manage;
use ProcessManage\Process\Process;
use ProcessManage\Process\Worker;

$config = [
    // 进程基础配置
    //'titlePrefix' => 'pm',   // 进程前缀
    'baseTitle' => 'test',  // 进程基础名称

    // master 进程配置
    'checkWorkerInterval' => 1000,    // 10秒检测一次进程
    'maxWorkerNum' => 10,    //2个进程

    // worker 进程配置
    'executeTimes' => 0,    // 任务的最大执行次数
    'executeUSleep' => 1000000,  // 每次执行任务睡眠时间(微秒) 1s = 1 000 000 us (1s)
    'limitSeconds' => 10800,    // 工作进程最大执行时长(秒)(跑3个小时重启)
];

function curlPost($url, $data, $timeout = 30, $head='')
{
    $ssl = substr($url, 0, 8) == "https://" ? TRUE : FALSE;
    $ch = curl_init();
    $opt = array(
        CURLOPT_URL => $url,
        //CURLOPT_PORT => $port,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => $timeout,
    );
    if ($ssl) {
        $opt[CURLOPT_SSL_VERIFYHOST] = 2;
        $opt[CURLOPT_SSL_VERIFYPEER] = FALSE;
    }
    curl_setopt_array($ch, $opt);
    if(is_array($head))curl_setopt($ch, CURLOPT_HTTPHEADER, $head);
    $data = curl_exec($ch);
    curl_close($ch);
    $errno = curl_errno($ch);
    if ($errno) {
        return 'error: '.$errno;
    } else {
        return 'success';
    }
}

try {
    // 创建进程管理器
    (new Manage($config))
        ->setWorkInit(
            function (Process $process) {
                // init
                //\ProcessManage\Log\ProcessLog::Record('info', $process, 'work init ... ');
                \ProcessManage\Log\Log::info('init');
                $text = '';
                // 50M
                for($i = 0; $i < 1024*1024*50; $i ++) {
                    $text .= (string)(rand(0,9));
                }
                return $text;
            }
        )
        ->setWork(
            // 执行的工作内容
            function(Worker $process, string $text) {
                //return ;
                //\ProcessManage\Log\ProcessLog::Record('info', $process, 'work run ... ');
                $url = "http://192.168.11.148/phpinfo.php";
                $res = curlPost($url, $text);
                \ProcessManage\Log\Log::info('time: '.$process->getExecuteTimes().'; content: '.$res);
            })
        ->start();
} catch (Exception $e) {
    echo $e->getExceptionAsString();
}
