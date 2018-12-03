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
    protected $mapping = [];

    /**
     * 模板行为参数的可选行为
     * @var array
     */
    protected $actionListCache = [];

    /**
     * 模板附加参数的可选行为
     * @var array
     */
    protected $optionsListCache = [];

    public function __construct()
    {
        $this->initTemplate();
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
     * 解析模板行为参数的可选行为
     * @return array [string, string, ...]
     */
    public function getActionList()
    {
        if (empty($this->actionListCache)) {
            $comList = explode(' ', $this->tempLate);
            $actionList = [];
            if ($this->isActionParam($comList[0])) {
                if ($this->isMustBeParam($comList[0])) {
                    $actionList = explode('|', $this->unWrap($comList[0]));
                    array_map('trim', $actionList);
                }
            }
            $this->actionListCache = $actionList;
        }
        return $this->actionListCache;
    }

    /**
     * 解析模板附加参数的可选行为
     * @return array
     * [
     *  'must' => [string, ...],
     *  'notMust' => [string, ...],
     * ]
     */
    public function getOptionsList()
    {
        if (empty($this->optionsListCache)) {
            $comList = explode(' ', $this->tempLate);
            unset($comList[0]);
            $must = [];
            $notMust = [];
            foreach ($comList as $v) {
                if ($this->isOptionalParam($v)) {
                    // 去头 -
                    $item = $this->removeHead($v);
                    if ($this->isMustBeParam($item)) {
                        $optionalList = explode('|', $this->unWrap($item));
                        $must = array_merge($must, $optionalList);
                    } else if ($this->isNotMustBeParam($item)) {
                        $optionalList = explode('|', $this->unWrap($item));
                        $notMust = array_merge($notMust, $optionalList);
                    }
                }
            }
            $this->optionsListCache = [
                'must' => $must,
                'notMust' => $notMust
            ];
        }
        return $this->optionsListCache;
    }

    /**
     * 是否为行为参数
     * @param string $item
     * @return bool
     */
    protected function isActionParam(string $item)
    {
        return !($this->isOptionalParam($item));
    }

    /**
     * 是否为附加参数
     * @param string $item
     * @return bool
     */
    protected function isOptionalParam(string $item)
    {
        return mb_strpos($item, '-') === 0;
    }

    /**
     * 是否为必填参数
     * @param string $item
     * @return bool
     */
    protected function isMustBeParam(string $item)
    {
        return (mb_strpos($item, '<') === 0 && mb_strpos($item, '>') === (mb_strlen($item) - 1));
    }

    /**
     * 是否为选填参数
     * @param string $item
     * @return bool
     */
    protected function isNotMustBeParam(string $item)
    {
        return (mb_strpos($item, '[') === 0 && mb_strpos($item, ']') === (mb_strlen($item) - 1));
    }

    /**
     * 是否为用户输入的值
     * @param string $item
     * @return bool
     */
    protected function isValueParam(string $item)
    {
        return (mb_strpos($item, '{') === 0 && mb_strpos($item, '}') === (mb_strlen($item) - 1));
    }


    /**
     * 去掉包裹的字符(去头去尾)
     * @param string $item
     * @return string
     */
    protected function unWrap(string $item)
    {
        // 去头
        $item = $this->removeHead($item);
        // 去尾
        $item = $this->removeTail($item);
        return $item;
    }

    /**
     * 去除第一个字符
     * @param string $item
     * @return string
     */
    protected function removeHead(string $item)
    {
        return mb_substr($item, 1);
    }

    /**
     * 去除最后一个字符
     * @param string $item
     * @return string
     */
    protected function removeTail(string $item)
    {
        return mb_substr($item, 0, mb_strlen($item) - 1);
    }

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
        $actionList = $this->getActionList();
        $str = "action: \n";
        foreach ($actionList as $v) {
            $className = $this->mapping['action'][$v];
            $cmd = $className::getCommandStr();
            $desc = $className::getCommandDescription();
            $cmdStr = "  ".$cmd;
            while(mb_strlen($cmdStr) < 15) {
                $cmdStr .= ' ';
            }
            $str .= $cmdStr.' '.$desc."\n";
        }

        $optionsList = $this->getOptionsList();
        $optionsList = array_merge($optionsList['must'], $optionsList['notMust']);
        $str .= "options: \n";
        foreach ($optionsList as $v) {
            $className = $this->mapping['options'][$v];
            $cmd = $className::getCommandStr();
            $desc = $className::getCommandDescription();
            $cmdStr = "  -".$cmd;
            while(mb_strlen($cmdStr) < 15) {
                $cmdStr .= ' ';
            }
            $str .= $cmdStr.' '.$desc."\n";
        }
        return $str;
    }

}