<?php

namespace ProcessManage\Command\Template;

use ProcessManage\Exception\CommandException;

/**
 * 模板解析类
 * Class Parse
 * @package ProcessManage\Command\Template
 */
class Parse
{
    /**
     * @var Template
     */
    protected $template = null;

    /**
     * 模板行为参数的可选行为
     * @var array   [string, string, ...]
     */
    protected $actionList = [];

    /**
     * 模板附加参数的可选行为
     * @var array
     * [
     *  'must' => [array, ...],     // 必须的参数
     *  'notMust' => [array, ...],  // 非必须参数
     * ]
     */
    protected $optionsList = [];

    /**
     * Parse constructor.
     * @param Template $template
     */
    public function __construct(Template $template)
    {
        $this->template = $template;
        $this->parseTemplate();
    }

    /**
     * 获取用户命令的行为
     * @param array $commands 去掉文件名的$argv
     * @return string
     * @throws CommandException
     */
    public function getAction(array $commands)
    {
        // 用户行为
        $action = '';
        if (!empty($commands)) {
            // 校验是否是行为
            if ($this->isActionParam($commands[0])) {
                $action = $commands[0];
            }
        }
        // 校验行为是否已经定义
        if (!in_array($action, $this->actionList)) {
            throw new CommandException("ERROR: command '".$action."' syntax error");
        }
        return $action;
    }

    /**
     * 获取用户命令的附加参数
     * @param array $commands
     * @return array [string => null, string => null, ...]
     * @throws CommandException
     */
    public function getOptions(array $commands)
    {
        // 用户附加参数 [string => null, string => null, ...]
        $options = [];
        if (!empty($commands)) {
            // 去掉行为
            unset($commands[0]);

            foreach ($commands as $v) {
                if ($this->isOptionalParam($v)) {
                    // 去头
                    $v = $this->removeHead($v);
                    if (empty($v)) {
                        continue;
                    }
                    $param = explode('=', $v);
                    $param[0] = trim($param[0]);
                    $param[1] = isset($param[1]) ? trim($param[1]) : null;
                    $options[$param[0]] = $param[1];
                }
            }
        }

        // 附加参数名称列表
        $optionsName = array_keys($options);

        $optionsList = $this->optionsList;
        // 校验必填参数是否都有
        foreach ($optionsList['must'] as $v) {
            // $v = ['p', 's', 'd', ...]
            $isIn = false;
            foreach ($v as $kk => $vv) {
                if (in_array($vv, $optionsName)) {
                    $isIn = true;
                    break;
                }
            }
            if (!$isIn) {
                throw new CommandException("ERROR: missing params '-[".implode('|', $v)."]'");
            }
        }

        // 校验参数是否都已定义
        $allOptions = $this->mergeArray($optionsList['must'], $optionsList['notMust'], 2);
        foreach ($optionsName as $v) {
            if (!in_array($v, $allOptions)) {
                throw new CommandException("ERROR: command '-".$v."' syntax error");
            }
        }

        // 校验可选参数的唯一性
        $allOptionsArr = array_merge($optionsList['must'], $optionsList['notMust']);
        foreach ($allOptionsArr as $v) {
            $inCount = 0;
            foreach ($optionsName as $vv) {
                if (in_array($vv, $v)) {
                    $inCount ++;
                }
            }
            if ($inCount > 1) {
                throw new CommandException("ERROR: Too many parameters '-[".implode('|', $v)."]'");
            }
        }

        return $options;
    }


    /**
     * 解析模板
     * @return void
     */
    public function parseTemplate()
    {
        $this->parseAction();
        $this->parseOptions();
    }

    /**
     * 解析模板行为参数的可选行为
     * @return array [string, string, ...]
     */
    protected function parseAction()
    {
        $comList = explode(' ', $this->template->getTemplateStr());
        $actionList = [];
        if ($this->isActionParam($comList[0])) {
            if ($this->isMustBeParam($comList[0])) {
                $actionList = explode('|', $this->unWrap($comList[0]));
                array_map('trim', $actionList);
            }
        }
        $this->actionList = $actionList;
        return $this->actionList;
    }

    /**
     * 解析模板附加参数的可选行为
     * @return array
     * [
     *  'must' => [array, ...],     // 必须的参数
     *  'notMust' => [array, ...],  // 非必须参数
     * ]
     */
    protected function parseOptions()
    {
        $comList = explode(' ', $this->template->getTemplateStr());
        unset($comList[0]);
        $must = [];
        $notMust = [];
        foreach ($comList as $v) {
            if ($this->isOptionalParam($v)) {
                // 去头 -
                $item = $this->removeHead($v);
                if ($this->isMustBeParam($item)) {
                    $optionalList = explode('|', $this->unWrap($item));
                    $must[] = $optionalList;
                } else if ($this->isNotMustBeParam($item)) {
                    $optionalList = explode('|', $this->unWrap($item));
                    $notMust[] = $optionalList;
                }
            }
        }
        $this->optionsList = [
            'must' => $must,
            'notMust' => $notMust
        ];
        return $this->optionsList;
    }

    /**
     * 是否为行为参数
     * @param string $item 命令逗号分隔的每一项值
     * @return bool
     */
    protected function isActionParam(string $item)
    {
        return !($this->isOptionalParam($item));
    }

    /**
     * 是否为附加参数
     * @param string $item 命令逗号分隔的每一项值
     * @return bool
     */
    protected function isOptionalParam(string $item)
    {
        return mb_strpos($item, '-') === 0;
    }

    /**
     * 是否为必填参数
     * @param string $item 命令逗号分隔的每一项值(有-的去掉-)
     * @return bool
     */
    protected function isMustBeParam(string $item)
    {
        return (mb_strpos($item, '<') === 0 && mb_strpos($item, '>') === (mb_strlen($item) - 1));
    }

    /**
     * 是否为选填参数
     * @param string $item 命令逗号分隔的每一项值(有-的去掉-)
     * @return bool
     */
    protected function isNotMustBeParam(string $item)
    {
        return (mb_strpos($item, '[') === 0 && mb_strpos($item, ']') === (mb_strlen($item) - 1));
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
     * 获取模板详情
     * @return string
     */
    public function getDescription()
    {
        $actionList = $this->actionList;
        $str = "action: \n";
        foreach ($actionList as $v) {
            $className = $this->template->mapping['action'][$v];
            $cmd = $className::getCommandStr();
            $desc = $className::getCommandDescription();
            $cmdStr = "  ".$cmd;
            while(mb_strlen($cmdStr) < 15) {
                $cmdStr .= ' ';
            }
            $str .= $cmdStr.' '.$desc."\n";
        }

        $optionsList = $this->optionsList;
        $optionsList = $this->mergeArray($optionsList['must'], $optionsList['notMust'], 2);
        $str .= "options: \n";
        foreach ($optionsList as $v) {
            $className = $this->template->mapping['options'][$v];
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


    /**
     * 合并两个多维数组为一个数组(深度，结构相同)
     * @param array $array1
     * @param array $array2
     * @param int $depth    深度，默认为1，为1时和array_merge效果相同
     * @return array
     */
    private function mergeArray(array $array1, array $array2, int $depth = 1)
    {
        if ($depth == 1) {
            return array_merge($array1, $array2);
        } else {
            $depth --;
            $newArray = [];
            $allArr = array_merge($array1, $array2);
            foreach ($allArr as $v) {
                $newArray = $this->mergeArray($newArray, $v, $depth);
            }
            return $newArray;
        }
    }

}