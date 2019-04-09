<?php

namespace ProcessManage\Command\Action;

use ProcessManage\Exception\ProcessException;
use ProcessManage\Process1\Manage;
use ProcessManage\Process1\Process;
use ProcessManage\Process1\Worker;
use ProcessManage\Command\Options\Work;

/**
 * restart 命令动作
 * Class ReStart
 * @package ProcessManage\Command\Action
 */
class ReStart extends Action
{

    /**
     * 执行该命令的动作
     * @return void
     * @throws ProcessException
     */
    public function handler()
    {

        $work = new Work();

        // 创建进程管理器
        $manage = (new Manage($work->config))
            ->setWorkInit(
            // 工作内容初始化
                function (Process $process) use ($work) {
                    // init
                    return $work->init($process);
                }
            )
            ->setWork(
            // 执行的工作内容
                function(Worker $process, $result) use ($work) {
                    // work
                    $work->work($process, $result);
                });
        // 后台运行
        $manage->setBackground()->restart();
    }

    /**
     * 获取命令
     * @return string
     */
    public static function getCommandStr()
    {
        return 'restart';
    }

    /**
     * 获取命令描述
     * @return string
     */
    public static function getCommandDescription()
    {
        return 'restart process';
    }
}