<?php

namespace ProcessManage\Command\Action;

use ProcessManage\Exception\Exception;
use ProcessManage\Process\Manage;
use ProcessManage\Command\Options\Work;

/**
 * Status 命令动作
 * Class Stop
 * @package ProcessManage\Command\Action
 */
class Status extends Action
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
        (new Manage($work->config))->showStatus();
    }

    /**
     * 获取命令
     * @return string
     */
    public static function getCommandStr()
    {
        return 'status';
    }

    /**
     * 获取命令描述
     * @return string
     */
    public static function getCommandDescription()
    {
        return 'process status';
    }
}