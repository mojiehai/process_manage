<?php

namespace ProcessManage\Exception;
use ProcessManage\Log\ProcessLog;


/**
 * 进程管理相关异常
 * Class ProcessException
 * @package ProcessManage\Exception
 */
class ProcessException extends Exception
{
    /**
     * 进程状态 前台、后台
     */
    const PROCESS_HOME = 1;
    const PROCESS_BACKGROUND = 2;

    /**
     * 显示或记录错误信息
     * @param int $processCode  进程状态
     * @param \stdClass $obj    产生异常的对象
     */
    public function showRunErrors(int $processCode, \stdClass $obj = null)
    {
        switch ($processCode) {
            case self::PROCESS_HOME:    // 前台
                $str = "Error: ".$this->getMessage();
                $str .= "\n";
                echo $str;
                break;
            case self::PROCESS_BACKGROUND:  // 后台，通过日志展示错误
                $str = $this->getExceptionAsString();
                ProcessLog::Record('error', $obj, $str);
                break;
            default:
                $this->showRunErrors(self::PROCESS_BACKGROUND);
                break;
        }
    }

}