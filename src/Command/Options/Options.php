<?php

namespace ProcessManage\Command\Options;
use ProcessManage\Command\Action\Action;
use ProcessManage\Command\CommandTrait;


/**
 * 命令参数抽象
 * Interface Param
 * @package ProcessManage\Command\Options
 */
abstract class Options
{
    // 引入命令特性
    use CommandTrait;

    /**
     * 参数
     * @var mixed
     */
    public $param = null;

    public function __construct($param = null)
    {
        $this->addParam($param);
    }

    /**
     * 添加参数对应的值
     * @param $param
     */
    public function addParam($param)
    {
        $this->param = $param;
    }

    /**
     * 影响action的行为
     * @param Action $action
     * @return mixed
     */
    abstract public function impactAction(Action $action);
}