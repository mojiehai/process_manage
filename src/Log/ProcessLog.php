<?php

namespace ProcessManage\Log;

use ProcessManage\Process\Manage;
use ProcessManage\Process\Master;
use ProcessManage\Process\Worker;
use ProcessManage\Config\LogConfig;

/**
 * 进程日志类
 * Class ProcessLog
 * @package ProcessManage\Log
 */
class ProcessLog extends Log
{

    /**
     * 调用日志的对象
     * @var \stdClass
     */
    public static $obj = null;

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

        switch (true) {
            case (static::$obj instanceof Master):
                $prefix = '['.$levelArr['name'].'][ '.(static::$obj)->title.' '.(static::$obj)->pid.' '.date('Y-m-d H:i:s', time()).']: ';
                break;
            case (static::$obj instanceof Worker):
                $prefix = '['.$levelArr['name'].'][ ---- '.(static::$obj)->title.' '.(static::$obj)->pid.' '.date('Y-m-d H:i:s', time()).']: ';
                break;
            case (static::$obj instanceof Manage):
                $prefix = '['.$levelArr['name'].'][Manage '.date('Y-m-d H:i:s', time()).']: ';
                break;
            default:
                $prefix = parent::getRowLogPrefix($level);
                break;
        }
        return $prefix;
    }

    /**
     * process记录日志
     * @param string $type 日志级别
     * @param object $obj 对象
     * @param string $content 内容
     * @param string $fileName 日志文件名
     * @return mixed
     */
    public static function Record($type, $obj, $content, $fileName = '')
    {
        static::$obj = $obj;
        $return = static::$type($content, $fileName);
        static::$obj = null;
        return $return;
    }

}