<?php

namespace ProcessManage\Command\Template;

use ProcessManage\Command\Action\Action;
use ProcessManage\Command\Options\Options;

/**
 * 命令模板抽象
 *
 * 模板格式:
 * <>包裹着为必填参数，参数可选值用 | 分隔
 * []包裹着为选填参数，参数可选值用 | 分隔
 *
 * 不带 -     表示行为参数
 *          1. 行为参数只能有一个
 *          2. 且只能在最前面一项
 *          3. 且只能为必填参数
 * 带 -       表示附加参数
 *          1. 附加参数可以有多个
 *          2. 每个附加参数可以连上 = 号传递用户输入的参数
 * 例如：
 *  <start|stop|restart> -[d] -[a|s]
 *
 * abstract class Template
 * @package ProcessManage\Command\Template
 */
abstract class Template
{
    /**
     * 模板
     * @var string
     */
    protected $tempLate = '';

    /**
     * 模板解析类
     * @var Parse
     */
    public $parse = null;

    /**
     * 命令映射的类
     * @var array
     * [
     *  'action' => [
     *      'start' => '\ProcessManage\Command\Action\Start',
     *      ...
     *  ],
     *  'options' => [
     *      'd' => '\ProcessManage\Command\Options\D',
     *  ]
     * ]
     */
    public $mapping = [];

    public function __construct()
    {
        $this->initTemplate();
        $this->parse = new Parse($this);
    }

    /**
     * 初始化模板值
     */
    protected function initTemplate()
    {
        $this->tempLate = $this->getTemplateStr();
    }

    /**
     * 获取模板内容
     * @return string
     */
    abstract public function getTemplateStr();

    /**
     * 根据action获取类
     * @param string $action
     * @return Action|null
     */
    public function getActionClass(string $action)
    {
        if (isset($this->mapping['action'][$action])) {
            $class = $this->mapping['action'][$action];
            return new $class();
        } else {
            return null;
        }
    }

    /**
     * 根据options获取类
     * @param string $options
     * @return Options|null
     */
    public function getOptionsClass(string $options)
    {
        if (isset($this->mapping['options'][$options])) {
            $class = $this->mapping['options'][$options];
            return new $class();
        } else {
            return null;
        }
    }

    /**
     * 获取命令详情
     * @return string
     */
    public function getDescription()
    {
        return $this->parse->getDescription();
    }

}