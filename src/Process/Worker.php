<?php

namespace ProcessManage\Process;

use ProcessManage\Exception\ProcessException;

/**
 * 工作进程
 * Class Worker
 * @package ProcessManage\Process
 */
class Worker extends Process
{
    /**
     * 工作进程最大执行时长 单位：秒 0为不限制
     * @var int
     */
    protected $limitSeconds = 0;

    /**
     * 工作进程最大工作次数(即工作回调最大回调次数) 0为无限循环执行
     * @var int
     */
    protected $executeTimes = 1;

    /**
     * 工作进程每次执行后睡眠时间 单位：微秒数  0为不睡眠
     * @var int
     */
    protected $executeUSleep = 200000;  // 0.2s

    /**
     * 工作进程当前执行次数
     * @var int
     */
    protected $currentExecuteTimes = 0;

    /**
     * 预计退出工作的时间戳 0为不退出
     * @var int
     */
    protected $preExitTime = 0;

    /**
     * 允许配置的变量
     * @var array
     */
    protected $configNameList = ['executeTimes', 'executeUSleep', 'limitSeconds'];

    /**
     * 加载配置
     */
    protected function configure()
    {
        parent::configure();

        // 如果设置了最大执行时长，则初始化预计退出时间
        if ($this->limitSeconds > 0) {
            $this->preExitTime = time() + $this->limitSeconds;
        }
    }

    /**
     * 执行工作初始化
     * @return mixed
     */
    protected function workInit()
    {
        if ($this->isRun()) {
            if (is_callable($this->closureInit)) {
                $closure = $this->closureInit;
                return $closure($this);
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * 执行工作回调
     * @param mixed $workInitReturn 工作初始化回调方法的返回值
     */
    protected function workExecute($workInitReturn = null)
    {
        if ($this->isRun()) {
            $closure = $this->closure;
            $closure($this, $workInitReturn);
            $this->currentExecuteTimes++;
        }
    }

    /**
     * 获取当前执行次数
     * @return int
     */
    public function getExecuteTimes()
    {
        return $this->currentExecuteTimes;
    }

    /**
     * 工作开始
     * @throws ProcessException
     */
    protected function runHandler()
    {
        $master = new Master($this->config);

        $work = $this->workInit();
        while (true) {
            // 检测信号
            pcntl_signal_dispatch();

            // 执行任务
            $this->workExecute($work);

            // 检测运行状态
            if ($this->checkNeedStop($master)) {
                $this->setStop();
            }

            // 检测信号
            pcntl_signal_dispatch();

            // 检测是否退出进程
            if ($this->isExpectStop()) {
                $this->stop();
            }

            // 睡眠
            $this->sleep();

        }
    }


    /**
     * 检测当前运行情况是否需要停止
     * @param Master $master master进程对象，主要用来补救操作
     * @return bool
     *  true表示当前进程需要设置停止，false表示当前进程还可以继续运行
     */
    protected function checkNeedStop(Master $master)
    {
        // 运行的情况下检测（已经设置当前状态需要停止的时候，检测就没有意义了）
        if ($this->isRun()) {

            $time = time();

            // 如果设置了预计退出时间，则检测是否需要退出
            if (
                ($this->preExitTime > 0) &&
                ($time >= $this->preExitTime)
            ) {
                return true;
            }

            // 如果限定了工作进程最大工作次数,则判断是否超出最大工作次数
            if (
                ($this->executeTimes > 0) &&
                ($this->getExecuteTimes() >= $this->executeTimes)
            ) {
                return true;
            }

            // 补救检测（检测主进程是否存在，不存在则需要自己退出进程(补救操作,10s补救操作一次)）
            if (
                ($time % 10 == 0) &&
                (!$master->isAlive())
            ) {
                return true;
            }

            return false;
        } else {
            return false;
        }
    }


    /**
     * 睡眠
     */
    protected function sleep()
    {
        if ($this->executeUSleep > 0) {
            usleep($this->executeUSleep);
        }
    }

}