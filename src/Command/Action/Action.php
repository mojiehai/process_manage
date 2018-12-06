<?php

namespace ProcessManage\Command\Action;
use ProcessManage\Command\CommandTrait;
use ProcessManage\Command\Options\Options;

/**
 * 命令动作抽象
 * Class AbstractAction
 * @package ProcessManage\Command\Action
 */
abstract class Action
{
    // 引入命令特性
    use CommandTrait;

    /**
     * 参数数组，用来存储数据
     * @var array
     */
    protected $params = [];

    /**
     * 附加参数对象列表
     * @var array
     */
    protected $options = [];

    /**
     * 添加Options
     * @param Options $options
     */
    public function addOptions(Options $options)
    {
        $this->options[] = $options;
    }

    /**
     * 执行操作
     */
    public function exec()
    {
        foreach ($this->options as $v) {
            if ($v instanceof Options) {
                $v->impactAction($this);
            }
        }
        $this->handler();
    }

    /**
     * 执行该命令的程序
     * @return void
     */
    abstract public function handler();


    /**
     * 设置参数
     * @param $key
     * @param $value
     */
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
    }

    /**
     * 获取参数
     * @param $key
     * @return mixed
     */
    public function getParam($key)
    {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }

}