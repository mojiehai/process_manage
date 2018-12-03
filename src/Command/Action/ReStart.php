<?php

namespace ProcessManage\Command\Action;


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
        echo 'restart';
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