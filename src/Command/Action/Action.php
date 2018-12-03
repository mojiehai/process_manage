<?php

namespace ProcessManage\Command\Action;
use ProcessManage\Command\CommandTrait;
use ProcessManage\Command\Param\Param;
use ProcessManage\Exception\ProcessException;

/**
 * 命令动作抽象
 * Class AbstractAction
 * @package ProcessManage\Command\Action
 */
abstract class Action
{
    // 引入命令特性
    use CommandTrait;

    /**
     * 参数对象列表
     * @var array
     */
    protected $params = [];

    public function addParam(Param $param)
    {
        $this->params[] = $param;
    }

    /**
     * 执行该命令的动作
     * @return void
     */
    abstract public function exec();


    /**
     * 后台运行程序(守护进程运行)
     * @return mixed
     */
    public function backgroundRun()
    {
        //分离出子进程
        $pid = pcntl_fork();
        if($pid < 0){
            try {
                throw new ProcessException('background run error!');
            } catch (ProcessException $processException) {
                $processException->showRunErrors(ProcessException::PROCESS_HOME, $this);
            }
            exit();
        }else if($pid > 0){
            // 杀掉父进程
            echo 'start ok !' . PHP_EOL;
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
    }
    
}