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
     * 设置为后台运行
     * @return $this
     * @throws ProcessException
     */
    public function setBackground()
    {
        //分离出子进程
        $pid = pcntl_fork();
        if($pid < 0){
            throw new ProcessException('background run error!');
        }else if($pid > 0){
            // 杀掉父进程
            exit;
        }
        //脱离当前终端(脱离死去的父进程的牵制)
        $sid = posix_setsid();
        if ($sid < 0) {
            exit;
        }
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

        return $this;
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
        $master->setWorkInit($this->closureInit)->setWork($this->closure)->run();
    }

    /**
     * stop命令动作
     * @return bool
     * @throws ProcessException
     */
    public function stop()
    {
        $master = new Master($this->config);
        if ($master->isAlive()) {
            if ($master->setStop()) {
                return true;
            } else {
                throw new ProcessException('stop failure');
            }
        } else {
            throw new ProcessException('process is not exists!');
        }
    }

    /**
     * restart命令动作
     * @throws ProcessException
     */
    public function restart()
    {
        $this->stop();
        $master = new Master($this->config);
        $i = 0;
        while($master->isAlive()) {
            if ($i > 10) {
                throw new ProcessException('failure to stop the master process!');
            }
            sleep(1);
            $i ++;
        }
        $this->start();
    }
    ################################## command action ####################################



}