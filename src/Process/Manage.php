<?php

namespace ProcessManage\Process;


use ProcessManage\Exception\ProcessException;

class Manage
{

    /**
     * 配置
     * @var array
     */
    protected $config = [];

    /**
     * 工作初始化
     * @var \Closure
     */
    protected $closureInit = null;

    /**
     * 工作回调
     * @var \Closure
     */
    protected $closure = null;

    /**
     * @param array $config
     * Manage constructor.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        //设置默认文件权限
        umask(022);
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

    ################################## command action ####################################
    /**
     * start命令动作
     * @return void
     * @throws ProcessException
     */
    public function start()
    {
        $master = new Master($this->config);
        echo $master->pid . ' -- '. $master->title . ' -- starting !' . PHP_EOL;
        $master->setWorkInit($this->closureInit)->setWork($this->closure)->run();
    }

    /**
     * stop命令动作
     * @return void
     * @throws ProcessException
     */
    public function stop()
    {
        $master = new Master($this->config, -1);
        $master->pid = $master->getPidByFile();
        if (Master::isMasterAlive($master)) {
            if (posix_kill($master->pid, SIGUSR2)) {
                echo 'stop'.PHP_EOL;
            } else {
                throw new ProcessException('stop failure');
            }
        } else {
            throw new ProcessException('process is not exists!');
        }
    }

    /**
     * restart命令动作
     * @return void
     * @throws ProcessException
     */
    public function restart()
    {
        $master = new Master($this->config, -1);
        $master->pid = $master->getPidByFile();
        if (Master::isMasterAlive($master)) {
            if (posix_kill($master->pid, SIGUSR1)) {
                echo 'restart'.PHP_EOL;
            } else {
                throw new ProcessException('restart failure');
            }
        } else {
            throw new ProcessException('process is not exists!');
        }
    }
    ################################## command action ####################################



}