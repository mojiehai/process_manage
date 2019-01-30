<?php

namespace ProcessManage\Process;

use ProcessManage\Config\ProcessConfig;
use ProcessManage\Exception\ProcessException;
use ProcessManage\Log\ProcessLog;
use ProcessManage\Process\ManageUtils\Status;

/**
 * 进程抽象类
 * Class Process
 * @package ProcessManage\src\Process
 */
abstract class Process
{

    /**
     * worker状态
     */
    const STATUS_PREPARE = 0;       // 准备
    const STATUS_INIT = 1;          // 初始化
    const STATUS_RUN = 2;           // 运行中
    const STATUS_SET_STOP = 3;      // 需要停止
    const STATUS_STOPPED = 4;       // 已经停止


    /**
     * 进程前缀、进程类型、进程基础名称的分隔符
     * @var string
     */
    const TITLE_DELIMITER = ':';

    /**
     * 进程id
     *
     * @var int
     */
    public $pid = 0;

    /**
     * 进程名称 (前缀、类型、进程基础名称组成)
     * @var string
     */
    public $title = '';

    /**
     * 进程名称前缀
     * @var string
     */
    protected $titlePrefix = '';

    /**
     * 进程基础名称(用来区分多个多进程任务)
     * @var string
     */
    public $baseTitle = 'process';

    /**
     * 进程实际工作内容的初始化
     * @var \Closure
     */
    protected $closureInit = null;

    /**
     * 进程的实际工作内容
     * @var \Closure
     */
    protected $closure = null;

    /**
     * worker当前状态
     * @var int
     */
    protected $status = self::STATUS_PREPARE;

    /**
     * 允许配置的变量名
     * @var array
     */
    protected $configNameList = [];

    /**
     * 允许配置的基础变量名
     * @var array
     */
    protected $baseConfigNameList = ['titlePrefix', 'baseTitle'];

    /**
     * 原始配置数组
     * @var array
     */
    protected $config = [];

    /**
     * run start time
     * @var int
     */
    protected $startTime = 0;

    /**
     * 进程类型(不带命名空间的类名)
     * @var string
     */
    protected $processType = '';

    /**
     * Process constructor.
     * @param array $config
     * @param int $pid
     */
    public function __construct(array $config = [], int $pid = 0)
    {
        $this->status = self::STATUS_PREPARE;
        $this->titlePrefix = ProcessConfig::$TitlePrefix;

        // 进程类型
        $className = get_class($this);
        $classNameInfoArr = explode('\\', $className);
        $this->processType = end($classNameInfoArr);

        // 加载配置
        $this->config = $config;
        $this->configure();
        if ($pid > 0) {
            $this->pid = $pid;
        } else {
            $this->setPid();
        }
    }

    /**
     * 加载配置
     */
    protected function configure()
    {
        $configList = array_merge($this->baseConfigNameList, $this->configNameList);
        foreach ($this->config as $k => $v) {
            if (in_array($k, $configList)) {
                if (!is_null($v)) {
                    $this->$k = $v;
                }
            }
        }

        // 生成title
        $titleArr = array_filter([$this->titlePrefix, $this->processType, $this->baseTitle]);
        $this->title = implode(self::TITLE_DELIMITER, $titleArr);
    }

    /**
     * 设置pid
     */
    protected function setPid()
    {
        $this->resetPid();
    }

    /**
     * 重设pid
     */
    public function resetPid()
    {
        $this->pid = posix_getpid();
    }

    /**
     * 初始化进程数据
     * @return $this
     */
    protected function init()
    {
        $this->status = self::STATUS_INIT;
        // 设置进程名称
        $this->setProcessTitle();
        return $this;
    }

    /**
     * 设置当前进程名称
     */
    protected function setProcessTitle()
    {
        cli_set_process_title($this->title);
    }

    /**
     * 设置进程的工作初始化
     * @param \Closure $closure
     * @return $this
     */
    public function setWorkInit(\Closure $closure = null)
    {
        if (is_callable($closure)) {
            $this->closureInit = $closure;
        }
        return $this;
    }

    /**
     * 设置进程的工作内容
     * @param \Closure $closure
     * @return $this
     */
    public function setWork(\Closure $closure = null)
    {
        if (is_callable($closure)) {
            $this->closure = $closure;
        }
        return $this;
    }

    ###################### 进程状态 #######################
    /**
     * 发送保存信息的信号
     * @return bool
     */
    public function saveStatus()
    {
        return posix_kill($this->pid, SIGUSR1);
    }

    /**
     * 强制停止进程(慎用,可能会造成工作进程数据丢失)
     * @return bool
     * @throws ProcessException
     */
    public function forceStop()
    {
        ProcessLog::warning('force stopping !!! ');
        if (posix_kill($this->pid, SIGKILL)) {
            return true;
        } else {
            throw new ProcessException('process is not exists!');
        }
    }

    /**
     * 设置进程需要停止
     * @return bool
     * @throws ProcessException
     */
    public function setStop()
    {
        if (posix_kill($this->pid, SIGTERM)) {
            return true;
        } else {
            throw new ProcessException('process is not exists!');
        }
    }

    /**
     * 判断进程是否准备停止
     * @return bool
     */
    public function isExpectStop()
    {
        return $this->status == self::STATUS_SET_STOP;
    }

    /**
     * 停止当前进程
     */
    protected function stop()
    {
        ProcessLog::info('stopped !!!');
        exit();
    }

    /**
     * 判断进程是否正在运行
     * @return bool
     */
    public function isRun()
    {
        return $this->status == static::STATUS_RUN;
    }

    ###################### 进程状态 #######################

    /**
     * 进程start
     * @return void
     * @throws ProcessException
     */
    public final function run()
    {
        $this->init();
        // 需要初始化才能运行
        if ($this->status == self::STATUS_INIT) {
            if (!is_callable($this->closure)) {
                // 如果没有工作任务，则退出进程
                $this->stop();
            }
            // 设置添加信号处理
            $this->setSignal();
            // 设置运行状态
            $this->status = self::STATUS_RUN;
            $this->startTime = time();
            ProcessLog::info('process start ok ! ');
            // 工作开始
            $this->runHandler();
        } else {
            throw new ProcessException('run after initialization');
        }
    }

    /**
     * 添加信号处理机制
     */
    protected function setSignal()
    {
        // SIGTERM 程序结束(terminate、信号, 与SIGKILL不同的是该信号可以被阻塞和处理.
        // 通常用来要求程序自己正常退出. shell命令kill缺省产生这个信号.
        pcntl_signal(SIGTERM, [$this, 'stopHandler'], false);
        // 程序终止(interrupt、信号, 在用户键入INTR字符(通常是Ctrl-C、时发出
        pcntl_signal(SIGINT, [$this, 'stopHandler'], false);
        // 记录进程运行状态信息
        pcntl_signal(SIGUSR1, [$this, 'statusHandler'], false);
    }

    /**
     * stop信号
     */
    protected function stopHandler()
    {
        if ($this->status != self::STATUS_SET_STOP) {
            ProcessLog::info('stopping ... ');
            // 设置当前进程为需要停止状态
            $this->status = self::STATUS_SET_STOP;
        }
    }

    /**
     * 获取运行状态类
     * @return Status
     */
    protected function getRunStatus()
    {
        $status = new Status($this->titlePrefix, $this->baseTitle);
        $status->type = $this->processType;
        $status->pid = $this->pid;
        $status->title = $this->title;
        $status->memory = memory_get_usage();
        $status->startTime = date('Y-m-d H:i:s', $this->startTime);
        $status->runTime = time() - $this->startTime;
        return $status;
    }

    /**
     * 进程运行状态信息信
     */
    protected function statusHandler()
    {
        // 记录状态信息
        $this->getRunStatus()->save();
    }

    /**
     * 工作开始
     */
    abstract protected function runHandler();


    /**
     * 检测当前进程是否存在
     * @return bool
     */
    public function isAlive()
    {
        return static::CheckAlive($this->pid);
    }


    /**
     * 检测进程是否存在
     * @param $pid
     * @return bool
     */
    public static function CheckAlive($pid)
    {
        if (empty($pid)) {
            return false;
        } else {
            return posix_kill($pid, 0);
        }
    }

}