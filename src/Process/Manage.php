<?php

namespace ProcessManage\Process;


use ProcessManage\Exception\ProcessException;
use ProcessManage\Exception\Exception;
use ProcessManage\Process\ManageUtils\SystemRegister;

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
        // 注册加载函数
        SystemRegister::registerAllHandler();
        $master->setWorkInit($this->closureInit)->setWork($this->closure)->run();
    }

    /**
     * stop命令动作
     * @return bool
     * @throws Exception
     */
    public function stop()
    {
        $master = new Master($this->config);
        if ($master->isAlive()) {
            if ($master->setStop()) {
                return true;
            } else {
                throw new Exception('stop failure');
            }
        } else {
            throw new Exception('process is not exists!');
        }
    }

    /**
     * restart命令动作
     * @throws Exception
     */
    public function restart()
    {
        $this->stop();
        $master = new Master($this->config);
        $i = 0;
        while($master->isAlive()) {
            if ($i > 10) {
                throw new Exception('failure to stop the master process!');
            }
            sleep(1);
            $i ++;
        }
        $this->start();
    }


    /**
     * return status信息
     * @return array 信息数组
     * [
     *  'Master' => [
     *      123 => ['pid' => 123, ...]
     *  ],
     *  'Worker' => [
     *      1232 => ['pid' => 1232, ...],
     *      1233 => ['pid' => 1233, ...],
     *      1234 => ['pid' => 1234, ...],
     *      ...
     *  ]
     * ]
     * @throws Exception
     */
    public function status()
    {
        $master = new Master($this->config);
        if ($master->isAlive()) {
            // 发送信号让进程记录status
            $master->saveStatus();
            // 睡眠1秒，等待进程记录
            sleep(1);
            return $master->getAllStatus();
        } else {
            throw new Exception('process is not exists!');
        }
    }

    /**
     * 显示status信息
     * @throws Exception
     */
    public function showStatus()
    {
        $status = $this->status();
        $str = '';
        foreach ($status as $processType => $infoArr) {
            $str .= $processType.PHP_EOL;
            // 获取每个字段最长的长度
            $lengthArr = [];
            foreach ($infoArr as $pid => $row) {
                foreach ($row as $field => $value) {
                    if (!isset($lengthArr[$field])) {
                        $lengthArr[$field] = strlen($field);
                    }
                    $lengthArr[$field] = max($lengthArr[$field], strlen($value.''));
                }
            }
            $i = 0;
            foreach ($infoArr as $pid => $row) {
                if ($i == 0) {
                    // title
                    $tmpRow = [];
                    foreach ($row as $k => $v) {
                        $tmpRow[$k] = $k;
                    }
                    $keys = $this->fullStringByArray($tmpRow, $lengthArr);
                    $str .= '  ' . implode('    ', $keys).PHP_EOL;
                }
                $values = $this->fullStringByArray($row, $lengthArr);
                $str .= '  '. implode('    ', $values).PHP_EOL;

                $i ++;
            }

            $str .= PHP_EOL;
        }
        echo $str;
    }
    ################################## command action ####################################

    /**
     * 把field中每个元素的长度填充到lengthArr指定的长度去
     * @param array $field
     * @param array $lengthArr
     * @return array
     */
    protected function fullStringByArray(array $field, array $lengthArr)
    {
        foreach ($field as $k => $v) {
            if (isset($lengthArr[$k])) {
                $field[$k] = str_pad($v.'', $lengthArr[$k], ' ');
            }
        }
        return $field;
    }


}