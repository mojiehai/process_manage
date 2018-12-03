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
     * @return string
     */
    public function showErrors(Command $com)
    {
        $str = '';
        if (empty($this->getMessage())) {
            $str .= "Please enter the command";
        } else {
            $str .= $this->getMessage();
        }
        $str .= "\n";

        $str .= $com->showHelps();

        return $str;
    }



}