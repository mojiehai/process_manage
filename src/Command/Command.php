<?php

namespace ProcessManage\Command;
use ProcessManage\Command\Action\Action;
use ProcessManage\Command\Options\Options;
use ProcessManage\Command\Template\ManageProcessTemplate;
use ProcessManage\Command\Template\Template;
use ProcessManage\Exception\CommandException;

/**
 * 命令类
 * Class Command
 * @package ProcessManage\Process
 */
class Command
{
    /**
     * 命令模板
     * @var Template
     */
    protected $template = null;

    /**
     * 命令(去掉文件名)
     * @var string
     */
    public $command = '';

    /**
     * 执行文件的文件名
     * @var string
     */
    protected $execFileName = '';

    /**
     * 命令参数列表
     * @var array
     */
    protected $commands = [];

    public function __construct(Template $template = null)
    {
        if (empty($template)) {
            $template = new ManageProcessTemplate();
        }
        $this->template = $template;
        $this->loadCommand();
    }

    /**
     * 初始化命令
     * @return void
     */
    protected function loadCommand()
    {
        GLOBAL $argv;
        foreach ($argv as $k => $v) {
            if ($k == 0) {
                $this->execFileName = $v;
            } else {
                $this->commands[] = $v;
            }
        }
        $this->command = implode(' ', $this->commands);
    }

    /**
     * 获取命令数组
     * @return array
     */
    public function getCommandsToArray()
    {
        return $this->commands;
    }

    /**
     * 获取执行文件名称
     * @return string
     */
    public function getExecFileName()
    {
        return $this->execFileName;
    }

    /**
     * 获取模板对象
     * @return ManageProcessTemplate|Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * 命令执行
     */
    public function run()
    {
        try {
            if ($this->checkCommand()) {
                $action = null;
                foreach ($this->commands as $k => $v) {
                    if ($k == 0) {
                        $action = $this->template->getActionClass($v);
                        if (!($action instanceof Action)) {
                            throw new CommandException("ERROR: command action '".$v."' not found");
                        }
                    } else {
                        // 去掉-
                        if (mb_strpos($v, '-') === 0) {
                            $v = mb_substr($v, 1);
                        }
                        $optionsArr = explode('=', $v);
                        $options = $this->template->getOptionsClass($optionsArr[0]);
                        if (!($options instanceof Options)) {
                            throw new CommandException("ERROR: command options '-".$v."' not found");
                        }
                        if (isset($optionsArr[1])) {
                            $options->addParam($optionsArr[1]);
                        }
                        $action->addOptions($options);
                    }
                }
                $action->exec();
            } else {
                throw new CommandException();
            }
        } catch (CommandException $commandException) {
            echo $commandException->showErrors($this);
        }
    }

    /**
     * 校验命令正确性
     * @return bool
     * @throws CommandException
     */
    public function checkCommand()
    {
        if (empty($this->commands)) {
            return false;
        }
        $actionList = $this->template->getActionList();
        $optionsList = $this->template->getOptionsList();
        $action = '';
        $options = [];
        foreach ($this->commands as $k => $v) {
            if ($k == 0) {
                // action
                $action = $v;
            } else {
                // options
                // 去掉-
                if (mb_strpos($v, '-') === 0) {
                    $v = mb_substr($v, 1);
                }
                // 刨除参数值
                $optionsArr = explode('=', $v);
                $options[] = $optionsArr[0];
            }
        }

        // 校验行为是否存在
        if (!in_array($action, $actionList)) {
            throw new CommandException("ERROR: command '".$action."' syntax error");
        }

        // 校验必填参数是否都有
        foreach ($optionsList['must'] as $v) {
            if (!in_array($v, $options)) {
                throw new CommandException("ERROR: missing params '-".$v."'");
            }
        }

        // 校验参数是否都已定义
        $allOptions = array_merge($optionsList['must'], $optionsList['notMust']);
        foreach ($options as $v) {
            if (!in_array($v, $allOptions)) {
                throw new CommandException("ERROR: command '".$v."' syntax error");
            }
        }
        return true;
    }

    /**
     * 显示提示信息
     * @return string
     */
    public function showHelps()
    {
        $str = "";
        $str .= "Usage: ".$this->getExecFileName()." ".$this->template->getTemplateStr()."\n";
        $str .= $this->template->getDescription();
        return $str;
    }

}