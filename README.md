# process_manage
php多进程管理器

## 业务场景
在实际业务场景中，我们可能需要定时执行或者近乎实时到业务逻辑，简单的可以使用unix自带的crontab实现。但是对于一些实时性要求比较高的业务就不适用了，所以我们就需要一个常驻内存的任务管理工具，为了保证实时性，一方面我们让它一直执行任务(适当的睡眠，保证cpu不被100%占用)，另一方面我们实现多进程保证并发的执行任务。

## 简述
基于php-cli模式实现master(父进程)-worker(子进程)的多进程管理器。
- 创建：一个master fork出多个worker
- 运行：
    - master通过信号(signal)控制多个worker的生命周期(master会阻塞的等待信号或者子进程退出)
    - worker会在生命周期中执行预定的任务
      
## 依赖
- php: >=7.0
- ext-pcntl: *
- ext-posix: *
- ext-json: *
- ext-mbstring: *
      
## 安装
> composer require mojiehai/process_manage

## 使用
1. 启动
    ```php
    use ProcessManage\Exception\ProcessException;
    use ProcessManage\Process\Manage;
    use ProcessManage\Process\Process;
    use ProcessManage\Process\Worker;

    $config = [
        // 进程基础配置
        'titlePrefix' => 'process_m',   // 进程前缀
        'baseTitle' => 'test',  // 进程基础名称

        // master 进程配置
        'checkWorkerInterval' => 0,    // n秒检测一次进程(<=0则为不检测)
        'maxWorkerNum' => 1,    //1个进程

        // worker 进程配置
        'executeTimes' => 1,    // 任务的最大执行次数(0为没有最大执行次数，一直执行)
        'executeUSleep' => 10000000,  // 每次执行任务睡眠时间(微秒) 1s = 1 000 000 us (1s)
        'limitSeconds' => 10800,    // 工作进程最大执行时长(秒)(跑3个小时重启)
    ];

    try {
        // 创建进程管理器
        (new Manage($config))
            ->setWorkInit(
                // 工作内容初始化
                function (Process $process) {
                    // init
                    \ProcessManage\Log\ProcessLog::Record('info', $process, 'work init ... ');
                }
            )
            ->setWork(
                // 执行的工作内容
                function(Worker $process) {
                    // work
                    \ProcessManage\Log\ProcessLog::Record('info', $process, 'work run ... ');
                })
            ->start();
    } catch (ProcessException $e) {
        echo $e->getExceptionAsString();
    }
    ```
2. 停止
    ```php
    use ProcessManage\Exception\ProcessException;
    use ProcessManage\Process\Manage;
    
    $config = [
        // 进程基础配置
        'baseTitle' => 'test',  // 进程基础名称
    ];
    
    try {
        // 创建进程管理器
        (new Manage($config))->stop();
    } catch (ProcessException $e) {
        echo $e->getExceptionAsString();
    }
    ```
3. 平滑重启
    ```php
    use ProcessManage\Exception\ProcessException;
    use ProcessManage\Process\Manage;
    
    $config = [
        // 进程基础配置
        'baseTitle' => 'test',  // 进程基础名称
    ];
    
    try {
        // 创建进程管理器
        (new Manage($config))->restart();
    } catch (ProcessException $e) {
        echo $e->getExceptionAsString();
    }
    ```
    
> 注意：baseTitle(进程基础名称)为进程的标识，start/stop/restart指定的名称必须相同。

## 说明
