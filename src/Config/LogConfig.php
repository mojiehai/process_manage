<?php

namespace ProcessManage\Config;


/**
 * 日志配置文件类
 * Class LogConfig
 * @package ProcessManage\Config
 */
class LogConfig extends Config
{
    /**
     * 是否启动日志
     * @var bool
     */
    public static $ENABLED = true;

    /**
     * 日志文件根目录
     * @var string
     */
    public static $LogBaseRoot = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'log';

    /**
     * 日志级别对应的文件名前缀
     * @var string
     */
    public static $Debug_FileNamePrefix = '';
    public static $Info_FileNamePrefix = '';
    public static $Notice_FileNamePrefix = '';
    public static $Warning_FileNamePrefix = '';
    public static $Error_FileNamePrefix = 'error_';
    public static $Fatal_FileNamePrefix = 'fatal_';


    /**
     * 普通日志文件默认文件名
     * @var string
     */
    public static $LogFileName = 'run';

    /**
     * 普通日志文件分隔规则
     * @var string
     */
    public static $LogDeLimiterRule = 'Y-m-d'; // 按天分隔

    /**
     * 进程日志文件默认文件名
     * @var string
     */
    public static $ProcessLogFileName = 'process';

    /**
     * 进程日志文件分隔规则
     * @var string
     */
    public static $ProcessLogDeLimiterRule = 'Y-m-d'; // 按天分隔

}