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

    public function __construct(Template $template)
    {
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
            // 执行other(预定义命令)
            $this->template->parse->execOther($this->commands);

            // 获取action
            $actionName = $this->template->parse->getAction($this->commands);
            $action = $this->template->getActionClass($actionName);
            if (!($action instanceof Action)) {
                throw new CommandException("ERROR: command action '".$actionName."' not found");
            }

            // 获取options
            $optionNames = $this->template->parse->getOptions($this->commands);
            foreach ($optionNames as $k => $v) {
                $options = $this->template->getOptionsClass($k);
                if (!($options instanceof Options)) {
                    throw new CommandException("ERROR: command options '-".$k."' not found");
                }
                $options->setParam($v);
                $action->addOptions($options);
            }

            $action->exec();

        } catch (CommandException $commandException) {
            echo $commandException->showErrors($this);
        } catch (\Exception $exception) {
            echo 'Error: '.$exception->getMessage().PHP_EOL;
        }
    }

    /**
     * 显示提示信息
     * @return string
     */
    public function showHelps()
    {
        return $this->template->getDescription();
    }

}