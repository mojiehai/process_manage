<?php

namespace ProcessManage\Process\MasterUtils;

use ProcessManage\Exception\ProcessException;
use ProcessManage\Helper\ResourceManage;
use ProcessManage\Process\Worker;
use ProcessManage\Log\ProcessLog;
use ProcessManage\Exception\Exception;

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
     * 工作进程配置
     * @var array
     */
    protected $workerConfig = [];

    /**
     * WorkerManage constructor.
     * @param int $maxWorkerNum 设置最大工作进程数量
     */
    public function __construct(int $maxWorkerNum)
    {
        $this->maxWorkerNum = $maxWorkerNum;
    }


    #################################### fork ########################################

    /**
     * set config
     * @param array $workerConfig
     * @return $this
     */
    public function setWorkerConfig(array $workerConfig)
    {
        $this->workerConfig = $workerConfig;
        return $this;
    }

    /**
     * fork worker
     * @throws ProcessException
     */
    public function fork()
    {
        $this->recyclingAllWorker();
        // 循环开启子进程
        while ($this->isAdd()) {
            $workerPid = pcntl_fork();  // fork出子进程
            if ($workerPid > 0) {

                $this->masterBranch($workerPid);

            } else if ($workerPid == 0) {

                $this->workerBranch();

            } else {
                // fork失败
                throw new ProcessException('fork process error');
            }
        }
    }

    /**
     * fork后，master的分支
     * @param int $pid fork出来的子进程pid
     */
    protected function masterBranch(int $pid)
    {
        // 该分支为父进程，创建一个简易worker对象，加入到worker管理器中
        $worker = new Worker($this->workerConfig['config'], $pid);
        $this->add($worker);
        // 睡眠0.1s再启动下一个
        usleep(100000);
    }

    /**
     * fork后，worker的分支
     */
    protected function workerBranch()
    {
        // 该分支为子进程
        try {
            //重设资源描述符
            ResourceManage::resetFileDescriptor();

            // 启动子进程任务
            $worker = new Worker($this->workerConfig['config']);
            $worker->setWorkInit($this->workerConfig['closureInit'])->setWork($this->workerConfig['closure'])->run();
        } catch (ProcessException $processException) {
            // 已经记录过日志，可以不用记录
        } catch (Exception $exception){
            $msg = $exception->getExceptionAsString();
            ProcessLog::error($msg);
        } finally {
            exit();
        }
    }
    #################################### fork ########################################

    #################################### status ########################################
    /**
     * 保存进程的status
     * @param int $pid
     */
    public function saveStatus(int $pid = 0)
    {
        if ($pid > 0) {
            $worker = $this->get($pid);
            if ($worker) {
                $worker->saveStatus();
            }
        } else {
            foreach ($this->workers as $k => $worker) {
                $this->saveStatus($worker->pid);
            }
        }
    }
    #################################### status ########################################

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
    protected function clean(int $pid = 0)
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
     * get workers
     * @return Worker[]
     */
    public function getWorkers()
    {
        return $this->workers;
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
     * 快速检测回收一下所有子进程（不阻塞）
     */
    public function recyclingAllWorker()
    {
        foreach ($this->workers as $k => $worker) {
            $this->recyclingWorker($worker->pid);
        }
    }

    /**
     * 回收子进程资源
     * @param int $pid  >0时为回收指定子进程退出后的资源，-1时为回收任意子进程退出后的资源 ，默认-1
     * @param bool $isBlocking 是否阻塞, 设置为true时，子进程未退出时阻塞
     * @return int
     *  -1 没有子进程或者发生错误
     *  0  子进程还未退出
     *  >0 回收的子进程pid
     */
    protected function recyclingWorker($pid = -1, $isBlocking = false)
    {
        if ($isBlocking) {
            // 阻塞
            $option = WUNTRACED;
        } else {
            // 非阻塞
            $option = WNOHANG;
        }
        $pid = pcntl_waitpid($pid, $status, $option);
        if ($pid > 0) {
            // 子进程退出，调用子进程管理器清理退出的进程
            $this->clean($pid);
        }
        return $pid;
    }

    /**
     * 监听子进程退出(阻塞)
     */
    public function listenWorkers()
    {
        return $this->recyclingWorker(-1, true);
    }

    /**
     * 停止子进程(给子进程发送停止信号SIGTERM)
     * @param int $pid 子进程pid，为0时表示停止所有子进程
     * @return bool
     */
    public function stopWorker($pid = 0)
    {
        try {
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
                    if ($worker->setStop()) {
                        // 发送信号后，等待0.01s再回收资源
                        usleep(10000);
                        // 不阻塞 回收子进程资源
                        $this->recyclingWorker($worker->pid);
                        return true;
                    } else {
                        return false;
                    }
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
                    if ($worker->forceStop()) {
                        // 阻塞回收子进程资源
                        $this->recyclingWorker($worker->pid, true);
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return true;
                }
            }
        } catch (ProcessException $e) {
            return false;
        }
    }

}