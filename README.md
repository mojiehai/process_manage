# process_manage
php多进程管理器

<br />
<br />

## 业务场景
在实际业务场景中，我们可能需要定时执行或者近乎实时到业务逻辑，简单的可以使用unix自带的crontab实现。但是对于一些实时性要求比较高的业务就不适用了，所以我们就需要一个常驻内存的任务管理工具，为了保证实时性，一方面我们让它一直执行任务(适当的睡眠，保证cpu不被100%占用)，另一方面我们实现多进程保证并发的执行任务。

<br />
<br />

## 简述
基于php-cli模式实现master(父进程)-worker(子进程)的多进程管理器。
- 创建：一个master fork出多个worker
- 运行：
    - master通过信号(signal)控制多个worker的生命周期(master会阻塞的等待信号或者子进程退出)
    - worker会在生命周期中执行预定的任务
      
<br />
<br />
      
## 依赖
- php: >=7.0
- ext-pcntl: *
- ext-posix: *
- ext-json: *
- ext-mbstring: *
 
<br />
<br />
 
## 安装
> linux：`composer require mojiehai/process_manage`  

> windows：`composer require mojiehai/process_manage --ignore-platform-reqs`  (windows下仅安装，不支持使用)

<br />
<br />

## 使用
1. 启动
    ```php

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
    
<br />
    
2. 停止
    ```php
    
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
    
<br />
    
3. 平滑重启
    ```php
    
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
            ->setBackground()->restart();
    } catch (ProcessException $e) {
        echo $e->getExceptionAsString();
    }
    ```

<br />

4. 查看信息
    ```php
     
    $config = [
        // 进程基础配置
        'baseTitle' => 'test',  // 进程基础名称
    ];
    
    try {
        // 创建进程管理器
        (new Manage($config))->showStatus();
    } catch (ProcessException $e) {
        echo $e->getExceptionAsString();
    }
    ```
    
> 注意：baseTitle(进程基础名称)为进程的标识，start/stop/restart/status指定的名称必须相同。

<br />
<br />

## 说明
1. 参数说明
	1. 固定配置（通过Config类的子类加载，作用域为全局，可在业务入口文件中指定）。例如`ProcessConfig::LoadConfig(["TitlePrefix" => "test"])`
		- 进程配置，通过`ProcessConfig::LoadConfig($configArray)`加载，配置项如下：

			| 配置项 | 描述 | 类型 | 默认值 |
			| --- | --- | --- | --- |
			| PidRoot | 存放master进程pid文件根目录 | string | /tmp/pm/pid |
			| TitlePrefix | 进程名称前缀 | string | process_m |
			| StatusFileRoot | 存放进程状态文件根目录 | string | /tmp/pm/status |
			
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

<br />

2. 方法说明
	- Manage类(单任务多进程管理器)
		
		| 方法名                                         | 参数说明           | 返回值 | 描述                                                                                                                                                                                                                                  |
		| ---------------------------------------------- | ------------------ | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
		| setBackground()                      | 无                 | Manage | 设置为后台运行，该方法执行完毕后，当前进程就会脱离终端，成为init进程的子进程。                                                                                                                                                        |
		| setWorkInit(\Closure $closure = null) | $closure：回调函数 | Manage | 设置工作进程初始化的回调方法，这个回调方法会在worker进程对象初始化完成后调用。一般该回调方法中初始化一些资源数据，例如数据库连接，给当前worker进程的工作回调使用。该回调方法接收一个参数，为当前的worker进程对象(Worker)。(示例见 [1.0](#s1.0) ) |          
		| setWork(\Closure $closure = null)     | $closure：回调函数 | Manage | 设置工作进程工作回调，该回调会在setWorkInit设置的初始化回调后调用。该回调方法接收两个参数：第一个为当前的worker进程对象(Worker)，第二个为工作进程初始化的回调方法的返回值。(示例见 [1.1](#s1.1) )          |
		| start()                                | 无                 | 无     | 启动任务                                                                                                                                                                                                                              |
		| stop()                                 | 无                 | 无     | 停止任务                                                                                                                                                                                                                              |
		| restart()                             | 无                 | 无     | 重启任务                                                                                                                                                                                                                              |
		| status()                              | 无                 | array  | 进程状态数组                                                                                                                                                                                                         |
		| showStatus()                          | array $status: 状态数组(status()的返回值)  | 无     | 格式化显示进程状态信息 (说明见 [1.2](#s1.2) )                                                                                                                                                                                                          |
	
	- ManageMultiple类(多任务多进程管理器)
    		
        | 方法名          | 参数说明           | 返回值 | 描述                                                                                                                                                                                                                                  |
        | ---------------| ------------------ | ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
        | addManage()    | Manage $manage: 单任务管理器  | 无     | 添加管理器                                                                                                                                                                                                                             |
        | removeManage() | String $baseTitle: 进程的基础名称 | 无   | 删除管理器                                                                                                                                                                                                                              |
        | getManage()    | String $baseTitle: 进程的基础名称 | Manage     | 获取单个进程管理器                                                                                                                                                                                                                              |
        | start()        | 无                 | 无     | 启动任务                                                                                                                                                                                                                              |
        | stop()         | 无                 | 无     | 停止任务                                                                                                                                                                                                                              |
        | restart()      | 无                 | 无     | 重启任务                                                                                                                                                                                                                              |
        | status()       | 无                 | array  | 进程状态数组                                                                                                                                                                                                         |
			
	- Process类
	
        | 方法名                                          | 参数说明           | 返回值  | 描述                               |
        | ----------------------------------------------- | ------------------ | ------- | ---------------------------------- |
        | resetPid()                               | 无                 | 无      | 重设pid(不需要手动调用)            |
        | setWorkInit(\Closure $closure = null)  | $closure：回调函数 | Process | 设置工作初始化回调(不需要手动调用) |
        | setWork(\Closure $closure = null)      | $closure：回调函数 | Process | 设置工作回调(不需要手动调用)       |
        | setStop()                                 | 无                 | 无      |  给当前进程对象发送停止信号              |
        | isExpectStop()                            | 无                 | bool    | 判断当前进程是否准备停止           |
        | isRun()                 | 无                 | 无      | 判断当前进程是否为正在运行状态           |
        | run()                                     | 无                 | 无      | 开始运行(不需要手动调用)           |
        | isAlive()                              | 无                 | bool    | 检测当前进程对象是否存在               |
        | static CheckAlive(int $pid)                  | $pid：进程pid       | bool    | 检测进程是否存在                   |

	- Worker类(继承Process类)
	
		| 方法名                  | 参数说明 | 返回值 | 描述 |
        | ----------------------- | -------- | ------ | ---- |
        | getExecuteTimes()  | 无       | int    |  获取当前执行次数 |

	- Master类(继承Process类)
	
	    | 方法名 | 参数说明 | 返回值 | 描述 | 
        | ------- | -------- | ------ | ---- |
        | getAllStatus() | 无 | array |  获取所有进程状态信息 |

<br />

3. 示例或说明
	- <a name='s1.0'>1.0</a>  
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
	- <a name='s1.1'>1.1</a>  
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
	- <a name='s1.2'>1.2</a>  
		```
		[root@localhost command]# php cmd.php status
		Master
		  type      pid      title                    memory(m)         start                  run(s)    count
		  Master    29570    process_m:Master:test    0.661(693296b)    2018-12-23 17:29:01    6         2           

		Worker
		  type      pid      title                    memory(m)         start                  run(s)    work
		  Worker    29571    process_m:Worker:test    0.661(692760b)    2018-12-23 17:29:01    6         1         
		  Worker    29572    process_m:Worker:test    0.661(693608b)    2018-12-23 17:29:01    6         1         
		```
		字段说明：(Master表示主进程，Worker表示工作进程)
		- `type`：进程类型说明(Master/Worker)
		- `pid`：进程pid
		- `title`：进程名称
		- `memory`：内存消耗，单位：M，括号中的为字节数
		- `start`：进程开始时间
		- `run`：运行时长，单位：秒
		- `count`：(Master进程独有属性)当前子进程个数
		- `work`：(Worker进程独有属性)当前进程执行任务回调的次数

<br />
<br />

## 命令管理
提供了一套命令管理的方案，通过实现部分接口即可接入

1. 简述  
    该方案为自定义命令，但是有部分预定义命令。
    - 预定义命令：所有命令均有的基本行为，权重最高。预定义命令列表如下：
        - `--help`: 查看命令列表
    - 自定义命令：分为两个部分,一部分为行为参数(必填参数),一部分为附加参数(选填参数)。(需要定义模板)  
        一条命令由一个明确的行为参数确定行为动作，若干个附加参数附带其他信息配置等。  
        例如：`start -d` 行为参数为start，表示启动，附加参数`d`，表示后台运行  
    
<br />

2. 命令模板
    - 格式:
        - `<>`包裹着为必填参数(行为参数)，参数可选值用 | 分隔
        - `[]`包裹着为选填参数(附加参数)，参数可选值用 | 分隔
        - 附加参数前缀必须带上`-`
    - 注意事项：
        - 行为参数只能有一个，且只能在最前面一项
        - 附加参数可以有多个，在行为参数后面
        - 输入命令时，每个附加参数可以连上`=`号传递想输入的参数
    - 例如：`<start|stop|restart> -[d] -[a|s]`
    
<br />
    
3. 构建命令
    1. 创建行为动作类继承`ProcessManage\Command\Action`类，并实现下列方法：(一个行为动作一个类)
        - handler()  
            执行该命令的动作
        - getCommandStr()  
            返回命令字符串
        - getCommandDescription()  
            返回命令描述
            
    2. 创建附加参数类继承`ProcessManage\Command\Options`类，并实现下列方法：(一个附加参数一个类)
        - getCommandStr()  
            返回命令字符串
        - getCommandDescription()  
            返回命令描述
        - impactAction(Action $action)  
            影响action的行为方式，建议在这个方法中使用`$action->setParam('key', 'value');`给action设置参数，然后在action类的handler方法中通过`$this->getParam($key)`获取参数，进行操作。例如：
            ```php
            /**
             * 影响action的行为
             * @param Action $action
             * @return mixed
             */
            public function impactAction(Action $action)
            {
                // $this->param 存储了用户输入的这个附加参数所带的值
                $action->setParam('runInBackground', true);
            }
            ```

    3. 创建模板类继承`ProcessManage\Command\Template`类，并实现下列内容：(一个命令一个模板)
        - $mapping  
            命令映射关系(把action、options映射到具体的类),例如：
            ```php
            /**
             * 命令映射的类
             * @var array
             */
            public $mapping = [
                'action' => [
                    'start' => '\ProcessManage\Command\Action\Start',
                    'stop' => '\ProcessManage\Command\Action\Stop',
                    'restart' => '\ProcessManage\Command\Action\ReStart',
                ],
                'options' => [
                    'd' => '\ProcessManage\Command\Options\D',
                ],
            ];
            ```
        - getTemplateStr()  
            定义模板格式，例如：
            ```php
            /**
             * 获取模板内容
             * @return string
             */
            public function getTemplateStr()
            {
                return '<start|stop|restart> -[d]';
            }
            ```

<br />
      
4. 使用
    ```php
    use ProcessManage\Command\Command;
    use ProcessManage\Command\Template\ManageProcessTemplate;

    $command = new Command(new ManageProcessTemplate());
    $command->run();
    ```

<br />

5. 运行
    ```
    [root@localhost command]# php cmd.php --help
    Usage: <start|stop|restart|status> -[d]
    action: 
      start         start process
      stop          stop process
      restart       restart process
      status        process status
    options: 
      -d            background running process
    other: 
      --help        to display the list of available commands, please use the list command.
    ```
