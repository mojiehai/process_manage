<?php

namespace ProcessManage\Command\Param;
use ProcessManage\Command\Action\Action;

/**
 * d 参数的动作
 * Class D
 * @package ProcessManage\Command\Param
 */
class D extends Param
{

    /**
     * 获取命令
     * @return string
     */
    public function getCommandStr()
    {
        return 'd';
    }

    /**
     * 获取命令描述
     * @return string
     */
    public function getCommandDescription()
    {
        return 'background running process';
    }

    /**
     * 影响action的行为
     * @param Action $action
     * @return mixed
     */
    public function impactAction(Action $action)
    {
        return $action->backgroundRun();
    }

}