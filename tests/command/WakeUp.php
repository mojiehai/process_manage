<?php

namespace ProcessManage\Command\Action;

use ProcessManage\Exception\ProcessException;
use ProcessManage\Process1\Manage;
use ProcessManage\Command\Options\Work;

/**
 * wakeup 命令动作
 * Class Stop
 * @package ProcessManage\Command\Action
 */
class WakeUp extends Action
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
        (new Manage($work->config))->wakeup();
    }

    /**
     * 获取命令
     * @return string
     */
    public static function getCommandStr()
    {
        return 'wakeup';
    }

    /**
     * 获取命令描述
     * @return string
     */
    public static function getCommandDescription()
    {
        return 'wakeup worker process';
    }
}