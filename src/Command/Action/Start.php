<?php

namespace ProcessManage\Command\Action;


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
     */
    public function handler()
    {
        echo 'start';
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