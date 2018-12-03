<?php

namespace ProcessManage\Command\Param;
use ProcessManage\Command\Action\Action;
use ProcessManage\Command\CommandTrait;


/**
 * 命令参数抽象
 * Interface Param
 * @package ProcessManage\Command\Param
 */
abstract class Param
{
    // 引入命令特性
    use CommandTrait;

    /**
     * 影响action的行为
     * @param Action $action
     * @return mixed
     */
    abstract public function impactAction(Action $action);
}