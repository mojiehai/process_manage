<?php

namespace ProcessManage\Process\MasterUtils;

use ProcessManage\Exception\ProcessException;
use ProcessManage\Process\Worker;
use ProcessManage\Log\ProcessLog;

/**
 * worker进程管理类
 * Class WorkerManage
 * @package ProcessManage\Process\MasterUtils
 */
class WorkerManage
{

    /**
     * worker进程对象
     * @var Worker[]
     */
    protected $workers = [];

    /**
     * 最大工作进程数
     * @var int
     */
    protected $maxWorkerNum = 0;


    /**
     * WorkerManage constructor.
     * @param int $maxWorkerNum 设置最大工作进程数量
     */
    public function __construct(int $maxWorkerNum)
    {
        $this->maxWorkerNum = $maxWorkerNum;
    }

    /**
     * 返回当前worker进程数量
     * @return int
     */
    public function count()
    {
        return count($this->workers);
    }

    /**
     * 检查worker进程，清理不存在的进程
     * @param int $pid 大于0时，检测该pid进程; 否则，检测所有worker进程
     */
    public function clean(int $pid = 0)
    {
        if ($pid > 0) {
            $worker = $this->get($pid);
            if ($worker && !$worker->isAlive()) {
                $this->remove($pid);
            }
        } else {
            foreach ($this->workers as $k => $v) {
                if (!$v->isAlive()) {
                    $this->remove($v->pid);
                }
            }
        }
    }


    /**
     * 是否需要添加工作进程
     * @return bool
     */
    public function isAdd()
    {
        $this->clean();
        // 工作进程数量 小于 最大工作进程数
        if ($this->count() < $this->maxWorkerNum) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 根据pid获取worker
     * @param int $pid
     * @return Worker
     */
    public function get(int $pid)
    {
        if (isset($this->workers[$pid])) {
            return $this->workers[$pid];
        } else {
            return null;
        }
    }

    /**
     * 添加worker
     * @param Worker $worker
     */
    public function add(Worker $worker)
    {
        $this->workers[$worker->pid] = $worker;
    }

    /**
     * 删除worker
     * @param int $pid worker pid
     */
    protected function remove(int $pid)
    {
        if ($this->get($pid)) unset($this->workers[$pid]);
    }


    /**
     * 停止子进程(给子进程发送停止信号SIGTERM)
     * @param int $pid 子进程pid，为0时表示停止所有子进程
     * @return bool
     */
    public function stopWorker($pid = 0)
    {
        try {
            $this->clean();
            if ($pid == 0) {
                $isStop = true;
                foreach ($this->workers as $k => $v) {
                    if (!$this->stopWorker($k)) {
                        ProcessLog::error('kill worker ('.$k.') failed');
                        $isStop = false;
                    }
                }
                return $isStop;
            } else {
                $worker = $this->get($pid);
                if ($worker) {
                    return $worker->setStop();
                } else {
                    return true;
                }
            }
        } catch (ProcessException $e) {
            return false;
        }
    }


    /**
     * 强行停止子进程(给子进程发送停止信号SIGKILL)
     * @param int $pid 子进程pid，为0时表示停止所有子进程
     * @return bool
     */
    public function forceStopWorker($pid = 0)
    {
        try {
            $this->clean();
            if ($pid == 0) {
                $isStop = true;
                foreach ($this->workers as $k => $v) {
                    if (!$this->forceStopWorker($k)) {
                        ProcessLog::error('kill -9 worker ('.$k.') failed');
                        $isStop = false;
                    }
                }
                return $isStop;
            } else {
                $worker = $this->get($pid);
                if ($worker) {
                    return $worker->forceStop();
                } else {
                    return true;
                }
            }
        } catch (ProcessException $e) {
            return false;
        }
    }

}