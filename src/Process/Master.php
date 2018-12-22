<?php

namespace ProcessManage\Process;

use ProcessManage\Exception\ProcessException;
use ProcessManage\Log\ProcessLog;
use ProcessManage\Exception\Exception;
use ProcessManage\Process\MasterUtils\PidFileStorage;
use ProcessManage\Process\MasterUtils\WorkerManage;

/**
 * 主进程类
 * Class Master
 * @package ProcessManage\Process
 */
class Master extends Process
{
    /**
     * pid文件存储类
     * @var PidFileStorage
     */
    protected $pidStorage = null;

    /**
     * worker管理对象
     * @var WorkerManage
     */
    protected $workerManage = null;

    /**
     * 最大工作进程数
     * @var int
     */
    protected $maxWorkerNum = 4;

    /**
     * 检查工作进程时间间隔 单位：秒
     * @var int
     */
    protected $checkWorkerInterval = 300;

    /**
     * 现在是否需要检测工作进程数量
     * @var bool
     */
    protected $isCheckWorker = false;

    /**
     * 允许配置的变量
     * @var array
     */
    protected $configNameList = ['checkWorkerInterval', 'maxWorkerNum'];

    /**
     * 加载配置
     */
    protected function configure()
    {
        parent::configure();

        // 实例化pid文件存储类
        $this->pidStorage = new PidFileStorage($this->title);

        // 实例化worker管理类
        $this->workerManage = new WorkerManage($this->maxWorkerNum);
    }

    /**
     * 设置pid
     * @throws ProcessException
     */
    protected function setPid()
    {
        try {
            $this->pid = $this->pidStorage->read();
        } catch (Exception $e) {
            throw new ProcessException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * 初始化进程数据
     * @throws ProcessException
     */
    protected function init()
    {
        // 检查当前进程是否有任务待执行
        if (!is_callable($this->closure)) {
            throw new ProcessException('There is no work !');
        }

        // 检查当前进程是否已经启动
        if ($this->isAlive()) {
            throw new ProcessException('process is already exists!');
        }
        // 重设pid
        $this->resetPid();
        parent::init();
        try {
            $this->pidStorage->save($this->pid);
        } catch (Exception $e) {
            throw new ProcessException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $this;
    }


    ############################## fork操作 ###############################
    /**
     * 根据当前子进程数，检查并fork出worker进程
     * @throws ProcessException
     */
    protected function fork()
    {
        // 当前进程在运行状态，且需要检查子进程数为true
        if (
            ($this->isRun()) &&
            ($this->isCheckWorker)
        ) {
            // 已经检查，当前不需要再次检查
            $this->isCheckWorker = false;
            // 设置下次轮询的闹钟
            if ($this->checkWorkerInterval > 0) {
                pcntl_alarm($this->checkWorkerInterval);
            }
            // 循环开启子进程
            while ($this->workerManage->isAdd()) {
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
    }

    /**
     * fork后，master的分支
     * @param int $pid fork出来的子进程pid
     */
    protected function masterBranch(int $pid)
    {
        // 该分支为父进程，创建一个简易worker对象，加入到worker管理器中
        $worker = new Worker([], $pid);
        $this->workerManage->add($worker);
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
            //设置默认文件权限
            umask(022);
            //将当前工作目录更改为根目录
            chdir('/');
            //关闭文件描述符
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);
            //重定向输入输出
            global $STDOUT, $STDERR;
            $STDOUT = fopen('/dev/null', 'a');
            $STDERR = fopen('/dev/null', 'a');

            // 启动子进程任务
            $worker = new Worker($this->config);
            $worker->setWorkInit($this->closureInit)->setWork($this->closure)->run();
        } catch (ProcessException $processException) {
            // 已经记录过日志，可以不用记录
        } catch (Exception $exception){
            $msg = $exception->getExceptionAsString();
            ProcessLog::error($msg);
        } finally {
            exit();
        }
    }
    ############################## fork操作 ###############################


    /**
     * 停止当前进程
     */
    protected function stop()
    {
        // 停止所有子进程
        $this->workerManage->stopWorker();
        $isStop = false;
        // 检测5次 共5s
        for ($i = 1; $i <= 5; $i++) {
            // 睡眠1s，等待子进程安全退出
            sleep(1);
            // 检测子进程状态
            $this->workerManage->clean();
            // 如果子进程全部退出完成
            if ($this->workerManage->count() == 0) {
                $isStop = true;
                break;
            }
        }
        if (!$isStop) {
            // 强制退出还未退出的子进程
            $this->workerManage->forceStopWorker();
        }
        // 设置状态
        $this->status = self::STATUS_STOPPED;
        // 停止主进程
        parent::stop();
    }



    /**
     * 工作开始
     * @return void
     * @throws ProcessException
     */
    protected function runHandler()
    {
        posix_kill($this->pid, SIGALRM);
        while (true) {
            // 调用信号处理程序
            pcntl_signal_dispatch();

            // fork子进程
            $this->fork();

            // 阻塞方法
            $this->wait();

            // 调用信号处理程序
            pcntl_signal_dispatch();

            // 检测运行状态
            if ($this->checkNeedStop()) {
                $this->setStop();
            }

            // 调用信号处理程序
            pcntl_signal_dispatch();

            // 是否需要停止
            if ($this->isExpectStop()) {
                $this->stop();
            }
        }
    }

    /**
     * 阻塞方法(子进程退出或者有信号过来，则退出阻塞状态)
     */
    protected function wait()
    {
        if ($this->isRun()) {
            $workerPid = pcntl_wait($status, WUNTRACED);
            if ($workerPid > 0) {

                // 子进程退出，调用子进程管理器清理退出的进程
                $this->workerManage->clean($workerPid);

                // 如果检测子进程间隔>0，说明子进程需要长存，
                // 因为当前已经退出一个子进程，所以则需要再次检测fork出子进程
                if ($this->checkWorkerInterval > 0) {
                    $this->isCheckWorker = true;
                }
            }
        }
    }

    /**
     * 检测当前运行情况是否需要停止
     * @return bool
     *  true表示当前进程需要设置停止，false表示当前进程还可以继续运行
     */
    protected function checkNeedStop()
    {
        // 运行的情况下检测（已经设置当前状态需要停止的时候，检测就没有意义了）
        if ($this->isRun()) {

            // 检测子进程时间间隔如果<0，则表示子进程不需要检测重启，则当子进程全部执行完毕后，退出主进程
            if ($this->checkWorkerInterval <= 0) {
                $this->workerManage->clean();
                if ($this->workerManage->count() == 0) {
                    return true;
                }
            }

            return false;
        } else {
            return false;
        }
    }

    ########################## 信号处理程序 ##############################
    /**
     * 添加信号
     */
    protected function setSignal()
    {
        parent::setSignal();
        // 闹钟信号(检测子进程,进程数不足则启动子进程)
        pcntl_signal(SIGALRM, [$this, 'checkHandler'], false);
    }


    /**
     * 闹钟信号处理程序(设置当前需要检测子进程数)
     */
    protected function checkHandler()
    {
        $this->isCheckWorker = true;
    }
    ########################## 信号处理程序 ##############################



}