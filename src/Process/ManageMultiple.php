<?php

namespace ProcessManage\Process;

use ProcessManage\Exception\ProcessException;
use ProcessManage\Helper\ResourceManage;
use ProcessManage\Exception\Exception;

/**
 * 多任务进程管理器
 * Class ManageMultiple
 * @package ProcessManage\Process
 */
class ManageMultiple
{

    /**
     * manage数组
     * [
     *      'master process baseTitle' => Manage,   // master基础名称 => Manage管理器
     *      'master process baseTitle' => Manage,
     *      'master process baseTitle' => Manage,
     *      ...
     * ]
     * @var Manage[]
     */
    protected $manage = [];

    public function __construct()
    {
    }

    ########################### 进程管理器操作 ############################
    /**
     * 添加manage管理器
     * @param Manage 管理器对象
     * @return void
     */
    public function addManage(Manage $manage)
    {
        $this->manage[$manage->getMaster()->baseTitle] = $manage;
    }

    /**
     * 删除manage管理器
     * @param string $baseTitle master基础名称
     */
    public function removeManage(string $baseTitle)
    {
        if (isset($this->manage[$baseTitle])) {
            unset($this->manage[$baseTitle]);
        }
    }

    /**
     * 获取单个进程管理器
     * @param string $baseTitle master基础名称
     * @return Manage|null
     */
    public function getManage(string $baseTitle)
    {
        if (isset($this->manage[$baseTitle])) {
            return $this->manage[$baseTitle];
        } else {
            return null;
        }
    }
    ########################### 进程管理器操作 ############################

    /**
     * 新进程异步运行回调方法
     * @param \Closure $closure 回调方法
     * @return void
     * @throws ProcessException
     */
    protected function asyncRun(\Closure $closure)
    {
        //分离出子进程
        $pid = pcntl_fork();
        if($pid < 0){
            throw new ProcessException('background run error!');
        }else if($pid == 0){
            // 子进程
            //脱离当前终端(脱离父进程的牵制)
            $sid = posix_setsid();
            if ($sid < 0) {
                exit;
            }

            // 重设资源描述符
            ResourceManage::resetFileDescriptor();

            // 回调
            $closure();

            exit;
        }
    }


    ################################## command action ####################################

    /**
     * start命令动作
     * @return void
     * @throws ProcessException
     */
    public function start()
    {
        foreach ($this->manage as $manage) {
            $this->asyncRun(function () use ($manage) {
                $manage->start();
            });
        }
    }

    /**
     * stop命令动作
     * @return void
     * @throws Exception
     */
    public function stop()
    {
        foreach ($this->manage as $manage) {
            try {
                $manage->stop();
            } catch (Exception $e) {
                if ($e->getCode() != 100) {
                    // error
                    throw $e;
                }
            }
        }
    }

    /**
     * wakeup命令动作
     * @return void
     * @throws Exception
     */
    public function wakeup()
    {
        foreach ($this->manage as $manage) {
            try {
                $manage->wakeup();
            } catch (Exception $e) {
                if ($e->getCode() != 100) {
                    // error
                    throw $e;
                }
            }
        }
    }

    /**
     * restart命令动作
     * @throws Exception
     */
    public function restart()
    {
        $this->stop();
        foreach ($this->manage as $manage) {
            $i = 0;
            while($manage->getMaster()->isAlive()) {
                if ($i > 10) {
                    throw new Exception('failure to stop the master process('.$manage->getMaster()->title.')!');
                }
                sleep(1);
                $i ++;
            }
        }
        // 所有进程已经停止
        $this->start();
    }

    /**
     * return status信息
     * @return array
     * @throws Exception
     */
    public function status()
    {
        foreach ($this->manage as $manage) {
            try {
                $manage->saveProcessStatusToCache();
            } catch (Exception $e) {
                if ($e->getCode() != 100) {
                    // error
                    throw $e;
                }
            }
        }
        // 睡眠1秒，等待进程记录
        sleep(1);
        $statusArr = [
            'MasterConfig' => [],
            'WorkerConfig' => [],
            'Master' => [],
            'Worker' => [],
        ];
        foreach ($this->manage as $manage) {
            try {
                $status = $manage->getProcessStatusByCache();
                $statusArr['MasterConfig'] = array_merge($statusArr['MasterConfig'], $status['MasterConfig']);
                $statusArr['WorkerConfig'] = array_merge($statusArr['WorkerConfig'], $status['WorkerConfig']);
                $statusArr['Master'] = array_merge($statusArr['Master'], $status['Master']);
                $statusArr['Worker'] = array_merge($statusArr['Worker'], $status['Worker']);
            } catch (Exception $e) {
                if ($e->getCode() != 100) {
                    // error
                    throw $e;
                }
            }
        }
        if (empty($statusArr['Master'])) {
            throw new Exception('There are no running processes!');
        }
        return $statusArr;
    }

}