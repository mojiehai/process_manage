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
1. 参数说明
	1. 固定配置（通过Config类的子类加载，作用域为全局，可在业务入口文件中指定）。例如`ProcessConfig::LoadConfig(["TitlePrefix" => "test"])`
		- 进程配置，通过`ProcessConfig::LoadConfig($configArray)`加载，配置项如下：

			| 配置项 | 描述 | 类型 | 默认值 |
			| --- | --- | --- | --- |
			| PidRoot | 存放master进程pid文件根目录 | string | /tmp/pm/pid |
			| TitlePrefix | 进程名称前缀 | string | process_m |
			
		- 日志配置，通过`LogConfig::LoadConfig($configArray)`加载，配置项如下:
			
			| 配置项                  | 描述                               | 类型    | 默认值                     |
			| ----------------------- | ---------------------------------- | ------- | -------------------------- |
			| ENABLED                 | 是否启动日志                       | boolean | true                       |
			| LogBaseRoot             | 日志文件根目录                     | string  | process_manage/runtime/log |
			| Debug_FileNamePrefix    | debug日志级别对应的文件名前缀      | string  |                            |
			| Info_FileNamePrefix     | info日志级别对应的文件名前缀       | string  |                            |
			| Notice_FileNamePrefix   | notice日志级别对应的文件名前缀     | string  |                            |
			| Warning_FileNamePrefix  | warning日志级别对应的文件名前缀    | string  |                            |
			| Error_FileNamePrefix    | error日志级别对应的文件名前缀      | string  | error_                           |
			| Fatal_FileNamePrefix    | fatal日志级别对应的文件名前缀      | string  | fatal_                           |
			| LogFileName             | 普通日志文件默认文件名             | string  | run                        |
			| LogDeLimiterRule        | 普通日志文件分隔规则，默认按天分隔 | string  | Y-m-d                      |
			| ProcessLogFileName      | 进程日志文件默认文件名             | string  | process                    |
			| ProcessLogDeLimiterRule | 进程日志文件分隔规则，默认按天分隔 | string  | Y-m-d                      |

			
	2. 非固定配置（通过manage构造函数加载进去，作用域为本次manage管理的进程）

		| 配置项              | 描述                                                                                                                         | 类型   | 是否必填 | 默认值               |
		| ------------------- | ---------------------------------------------------------------------------------------------------------------------------- | ------ | -------- | -------------------- |
		| titlePrefix         | 进程名称前缀，优先级大于固定配置                                                                                             | string | 否       | (默认读取固定配置值) |
		| baseTitle           | 进程基础名称，用来区分多个进程管理器                                                                                         | string | 否       | process              |
		| checkWorkerInterval | master：检查工作进程的时间间隔，单位：秒                                                                                     | int    | 否       | 300                  |
		| maxWorkerNum        | master：最大工作进程数                                                                                                       | int    | 否       | 4                    |
		| executeTimes        | worker：工作进程最大工作次数(即工作回调最大回调次数) 0为无限循环执行，(执行完指定次数后退出子进程，等待master进程重启子进程) | int    | 否       | 1                    |
		| executeUSleep       | worker：工作进程每次执行后睡眠时间 单位：微秒数  0为不睡眠                                                                   | int    | 否       | 200000               |
		| limitSeconds        | worker：工作进程最大执行时长 单位：秒 0为不限制(执行完指定次数后退出子进程，等待master进程重启子进程)                        | int    | 否       | 0                    |

2. 方法说明
	- Manage类
		
		| 方法名                                         | 参数说明           | 返回值 | 描述                                                                                                                                                                                                                                  |
		| ---------------------------------------------- | ------------------ | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
		| setBackground() :  Manage                      | 无                 | Manage | 设置为后台运行，该方法执行完毕后，当前进程就会脱离终端，成为init进程的子进程。                                                                                                                                                        |
		| setWorkInit(\Closure $closure = null) : Manage | $closure：回调函数 | Manage | 设置工作进程初始化的回调方法，这个回调方法会在worker进程对象初始化完成后调用。一般该回调方法中初始化一些资源数据，例如数据库连接，给当前worker进程的工作回调使用。该回调方法接收一个参数，为当前的worker进程对象(Worker)。(示例见 [1.0](#s1.0) ) |          
		| setWork(\Closure $closure = null) : Manage     | $closure：回调函数 | Manage | 设置工作进程工作回调，该回调会在setWorkInit设置的初始化回调后调用。该回调方法接收两个参数：第一个为当前的worker进程对象(Worker)，第二个为工作进程初始化的回调方法的返回值(建议在这个位置传递资源对象给工作回调)。(示例见 [1.1](#s1.1) )          |
		| start() : void                                 | 无                 | 无     | 启动任务                                                                                                                                                                                                                              |
		| stop() : void                                  | 无                 | 无     | 停止任务                                                                                                                                                                                                                              |
		| restart() : void                              | 无                 | 无     | 重启任务                                                                                                                                                                                                                              |
	
		- 示例
			- <a href='#s1.0'>1.0</a>
			```php
			(new Manage($config))->setWorkInit(
				// 工作内容初始化
				function (Worker $process) {
					// init
					$link = mysqli_connect(...);
					...
					$redis = new Redis(...);
					...
					return ['mysql' => $link, 'redis' => $redis];
				}
			 )
			```
			- <a href='#s1.1'>1.1</a>
			```php
			(new Manage($config))->setWork(
				// 执行的工作内容
				function(Worker $process, $result = []) {
					// work
					$mysqlLink = $result['mysql'];
					$redisLink = $result['redis'];
				})
			 )
			```
			
	- Process类
		- setNewPid() : void
			- 描述：重设pid(不需要手动调用)
		- setWorkInit(\Closure $closure = null) : Process
			- 描述：设置工作初始化回调(不需要手动调用)
		- setWork(\Closure $closure = null) : Process
			- 描述：设置工作回调(不需要手动调用)
		- setStop() : void
			- 描述：设置当前进程需要停止
		- isExpectStop() : bool
			- 描述：判断当前进程是否准备停止
		- setRestart() : void
			- 描述：设置当前进程需要重新启动
		- isExpectRestart() : bool
			- 描述：判断当前进程是否准备重启
		- run() : void
			- 描述：开始运行(不需要手动调用)
		- checkAlive() : bool
			- 描述：检测当前进程是否存在
		- static isAlive(int $pid) : bool
			- 描述：检测进程是否存在

	- Worker类(继承Process类)
		- getExecuteTimes() : int
			- 描述：返回当前工作回调执行的次数

	- Master类(继承Process类)
