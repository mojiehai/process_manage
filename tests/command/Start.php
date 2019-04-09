<?php

namespace ProcessManage\Command\Action;

use ProcessManage\Command\Options\Work;
use ProcessManage\Exception\Exception;
use ProcessManage\Process1\Manage;
use ProcessManage\Process1\Worker;

/**
 * start 命令动作
 * Class Start
 * @package ProcessManage\Command\Action
 */
class Start extends Action
{

    /**
     * 执行该命令的动作
     * @return void
     * @throws Exception
     */
    public function handler()
    {

        $work = new Work();

        // 创建进程管理器
        $manage = (new Manage($work->config))
            ->setWorkInit(
            // 工作内容初始化
                function (Worker $process) use ($work) {
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
        if ($this->getParam('runInBackground')) {
            // 后台运行
            $manage->setBackground();
        }
        $manage->start();
    }

    /**
     * 获取命令
     * @return string
     */
    public static function getCommandStr()
    {
        return 'start';
    }

    /**
     * 获取命令描述
     * @return string
     */
    public static function getCommandDescription()
    {
        return 'start process';
    }
}