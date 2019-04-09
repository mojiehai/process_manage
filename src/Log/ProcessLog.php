<?php

namespace ProcessManage\Log;

use ProcessManage\Process1\Manage;
use ProcessManage\Process1\Master;
use ProcessManage\Process1\Process;
use ProcessManage\Process1\Worker;
use ProcessManage\Config\LogConfig;

/**
 * 进程日志类
 * Class ProcessLog
 * @package ProcessManage\Log
 */
class ProcessLog extends Log
{

    /**
     * 获取默认日志文件名
     * @return string
     */
    public static function getLogFileName()
    {
        if (!is_null(LogConfig::$ProcessLogFileName)) {
            return LogConfig::$ProcessLogFileName;
        } else {
            return 'process';
        }
    }

    /**
     * 获取日志文件分隔规则
     */
    protected static function getLogDeLimiterRule()
    {
        if (!is_null(LogConfig::$LogDeLimiterRule)) {
            return LogConfig::$LogDeLimiterRule;
        } else {
            return 'Y-m-d';     // 按天分隔
        }
    }

    /**
     * 获取每行日志记录前缀
     * @param $level
     * @return string
     */
    protected static function getRowLogPrefix($level)
    {
        $levelArr = static::$LEVELS[$level];

        $title = cli_get_process_title();
        $pid = posix_getpid();

        switch (true) {
            case (strpos($title, Process::TITLE_DELIMITER.'Master'.Process::TITLE_DELIMITER) !== false):
                $prefix = '['.$levelArr['name'].'][ '.$title.' '.$pid.' '.date('Y-m-d H:i:s', time()).']: ';
                break;
            case (strpos($title, Process::TITLE_DELIMITER.'Worker'.Process::TITLE_DELIMITER) !== false):
                $prefix = '['.$levelArr['name'].'][ ---- '.$title.' '.$pid.' '.date('Y-m-d H:i:s', time()).']: ';
                break;
            default:
                $prefix = parent::getRowLogPrefix($level);
                break;
        }
        return $prefix;
    }

}