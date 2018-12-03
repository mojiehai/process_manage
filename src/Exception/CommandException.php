<?php

namespace ProcessManage\Exception;
use ProcessManage\Command\Command;


/**
 * 命令异常类
 * Class CommandException
 * @package ProcessManage\Exception
 */
class CommandException extends Exception
{

    /**
     * 显示错误信息
     * @param Command $com
     */
    public static function showErrors(Command $com)
    {
        $str = '';
        $commend = $com->command;
        if (empty($commend)) {
            $str .= "Please enter the command";
        } else {
            $str .= "ERROR: command '".$commend."' syntax error";
        }
        $str .= "\n";

        echo $str;

        static::showHelps($com);
    }

    /**
     * 显示提示信息
     * @param Command $com
     */
    public static function showHelps(Command $com)
    {
        $str = "";
        $str .= "Usage: ".$com->getExecFileName()." ".$com->getTemplate()->getTemplateStr()."\n";
        echo $str;
    }

}