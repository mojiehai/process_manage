<?php

namespace ProcessManage\Command\Action;

use ProcessManage\Exception\ProcessException;
use ProcessManage\Process\Manage;

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
     */
    public function handler()
    {
        $config = [
            // 进程基础配置
            'baseTitle' => 'test',  // 进程基础名称
        ];

        try {
            // 创建进程管理器
            (new Manage($config))->restart();
        } catch (ProcessException $e) {
            echo $e->getExceptionAsString();
        }
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