<?php

namespace ProcessManage\Log;

use ProcessManage\Config\LogConfig;
use ProcessManage\Exception\Exception;

/**
 * 基础日志类
 * Class Log
 * @package ProcessManage\Log
 */
class Log
{
    /**
     * 日志级别
     * @var array
     */
    protected static $LEVELS = [
        'debug' => [
            'name' => 'Debug',
            'level' => 1,
            'fileNamePrefix' => '',
        ],
        'info' => [
            'name' => 'Info',
            'level' => 2,
            'fileNamePrefix' => '',
        ],
        'notice' => [
            'name' => 'Notice',
            'level' => 3,
            'fileNamePrefix' => '',
        ],
        'warning' => [
            'name' => 'Warning',
            'level' => 4,
            'fileNamePrefix' => '',
        ],
        'error' => [
            'name' => 'Error',
            'level' => 5,
            'fileNamePrefix' => 'error_',
        ],
        'fatal' => [
            'name' => 'Fatal',
            'level' => 6,
            'fileNamePrefix' => 'fatal_',
        ],
    ];


    ######################## 日志文件相关 ##########################

    /**
     * 是否启动日志
     * @return bool
     */
    final public static function isEnabled()
    {
        return LogConfig::$ENABLED;
    }

    /**
     * 获取日志等级
     */
    final protected static function getLevels()
    {
        // load config
        if (!is_null(LogConfig::$Debug_FileNamePrefix)) {
            static::$LEVELS['debug']['fileNamePrefix'] = (string)(LogConfig::$Debug_FileNamePrefix);
        }
        if (!is_null(LogConfig::$Info_FileNamePrefix)) {
            static::$LEVELS['info']['fileNamePrefix'] = (string)(LogConfig::$Info_FileNamePrefix);
        }
        if (!is_null(LogConfig::$Notice_FileNamePrefix)) {
            static::$LEVELS['notice']['fileNamePrefix'] = (string)(LogConfig::$Notice_FileNamePrefix);
        }
        if (!is_null(LogConfig::$Warning_FileNamePrefix)) {
            static::$LEVELS['warning']['fileNamePrefix'] = (string)(LogConfig::$Warning_FileNamePrefix);
        }
        if (!is_null(LogConfig::$Error_FileNamePrefix)) {
            static::$LEVELS['error']['fileNamePrefix'] = (string)(LogConfig::$Error_FileNamePrefix);
        }
        if (!is_null(LogConfig::$Fatal_FileNamePrefix)) {
            static::$LEVELS['fatal']['fileNamePrefix'] = (string)(LogConfig::$Fatal_FileNamePrefix);
        }
        return static::$LEVELS;
    }
    /**
     * 获取日志文件根目录
     * @return string
     */
    final protected static function getLogBaseDir()
    {
        $logRoot = LogConfig::$LogBaseRoot;
        if (!is_dir($logRoot)) {
            $mkdir = mkdir($logRoot, 0777, true);
            if (!$mkdir) {
                throw new Exception($logRoot . ' 不可写');
            }
            $chmod = chmod($logRoot, 0777);
            if (!$chmod) {
                throw new Exception($logRoot . ' 权限不足');
            }
        }
        return $logRoot;
    }

    /**
     * 获取默认日志文件名
     * @return string
     */
    protected static function getLogFileName()
    {
        if (!is_null(LogConfig::$LogFileName)) {
            return LogConfig::$LogFileName;
        } else {
            return 'run';
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
     * 获取日志完整路径
     * @param string $level 日志等级
     * @param string $fileName  日志文件名
     * @return string
     */
    protected static function getLogFilePath($level, $fileName = '')
    {
        // 获取日志根目录
        $logRoot = static::getLogBaseDir();

        // 组装文件名
        if (empty($fileName)) {
            $fileName = static::getLogFileName();
        }
        $levels = static::getLevels();
        $fileName = $levels[$level]['fileNamePrefix'].$fileName;

        $logFileDelimiterRule = static::getLogDeLimiterRule();
        $logFile = $logRoot . DIRECTORY_SEPARATOR . $fileName . '.' . date($logFileDelimiterRule, time()) . '.log';

        if (!file_exists($logFile)) {
            touch($logFile);
        }
        return $logFile;
    }
    ######################## 日志文件相关 ##########################

    /**
     * 写入日志
     * @param string $level
     * @param string $content
     * @param string $fileName
     * @return bool|int
     */
    protected static function write($level, $content, $fileName = '')
    {
        if (static::isEnabled()) {
            $logFile = static::getLogFilePath($level, $fileName);
            $logPrefix = static::getRowLogPrefix($level);
            $log = $logPrefix . $content . PHP_EOL;
            return file_put_contents($logFile, $log, FILE_APPEND);
        } else {
            return true;
        }
    }

    ######################## 日志内容相关 ##########################

    /**
     * 获取每行日志记录前缀
     * @param $level
     * @return string
     */
    protected static function getRowLogPrefix($level)
    {
        $levels = static::getLevels();
        $levelArr = $levels[$level];
        $prefix = '['.$levelArr['name'].']['.date('Y-m-d H:i:s', time()).']: ';
        return $prefix;
    }
    ######################## 日志内容相关 ##########################

    /**
     * @param $name
     * @param $arguments
     * @return bool
     */
    public static function __callStatic($name, $arguments)
    {
        $content = isset($arguments[0]) ? $arguments[0] : '';
        $fileName = isset($arguments[1]) ? $arguments[1] : '';
        $levels = static::getLevels();
        if (isset($levels[strtolower($name)])) {
            $name = strtolower($name);
            return static::$name($content, $fileName);
        } else {
            // 未定义的按照debug处理
            return static::debug($content, $fileName);
        }
    }

    /**
     * @param string $content
     * @param string $fileName
     * @return bool|int
     */
    public static function debug($content, $fileName = '')
    {
        return static::write('debug', $content, $fileName);
    }

    /**
     * @param string $content
     * @param string $fileName
     * @return bool|int
     */
    public static function info($content, $fileName = '')
    {
        return static::write('info', $content, $fileName);
    }

    /**
     * @param string $content
     * @param string $fileName
     * @return bool|int
     */
    public static function notice($content, $fileName = '')
    {
        return static::write('notice', $content, $fileName);
    }

    /**
     * @param string $content
     * @param string $fileName
     * @return bool|int
     */
    public static function warning($content, $fileName = '')
    {
        return static::write('warning', $content, $fileName);
    }

    /**
     * @param string $content
     * @param string $fileName
     * @return bool|int
     */
    public static function error($content, $fileName = '')
    {
        return static::write('error', $content, $fileName);
    }

    /**
     * @param string $content
     * @param string $fileName
     * @return bool|int
     */
    public static function fatal($content, $fileName = '')
    {
        return static::write('fatal', $content, $fileName);
    }
}