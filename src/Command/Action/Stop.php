<?php

namespace ProcessManage\Command\Action;

/**
 * stop 命令动作
 * Class Stop
 * @package ProcessManage\Command\Action
 */
class Stop extends Action
{

    /**
     * 执行该命令的动作
     * @return void
     */
    public function handler()
    {
        echo 'stop';
    }

    /**
     * 获取命令
     * @return string
     */
    public static function getCommandStr()
    {
        return 'stop';
    }

    /**
     * 获取命令描述
     * @return string
     */
    public static function getCommandDescription()
    {
        return 'stop process';
    }
}