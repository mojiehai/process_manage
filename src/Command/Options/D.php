<?php

namespace ProcessManage\Command\Options;

use ProcessManage\Command\Action\Action;
use ProcessManage\Exception\ProcessException;

/**
 * d 参数的动作
 * Class D
 * @package ProcessManage\Command\Options
 */
class D extends Options
{

    /**
     * 获取命令
     * @return string
     */
    public static function getCommandStr()
    {
        return 'd';
    }

    /**
     * 获取命令描述
     * @return string
     */
    public static function getCommandDescription()
    {
        return 'background running process';
    }

    /**
     * 影响action的行为
     *
     * 后台运行程序(守护进程运行)
     *
     * @param Action $action
     * @return mixed
     */
    public function impactAction(Action $action)
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