<?php

namespace ProcessManage\Command;
use ProcessManage\Command\Template\ManageProcessTemplate;
use ProcessManage\Command\Template\Template;

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

}