<?php

namespace ProcessManage\Process1\ManageUtils;
use ProcessManage\Log\ProcessLog;
use Throwable;

/**
 * 系统注册类
 * Class SystemRegister
 * @package ProcessManage\Process\ManageUtils
 */
class SystemRegister
{

    /**
     * 注册所有捕获函数
     */
    public static function registerAllHandler()
    {
        self::registerErrorHandler();
        self::registerExceptionHandler();
        self::registerShutdownHandler();
    }

    /**
     * 注册错误捕捉函数
     */
    protected static function registerErrorHandler()
    {
        // 一般用于捕捉  E_NOTICE 、E_USER_ERROR、E_USER_WARNING、E_USER_NOTICE
        // 不能捕捉：E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR and E_COMPILE_WARNING
        // 一般与trigger_error("...", E_USER_ERROR)，配合使用

        set_error_handler(function($errno, $errmsg, $filename, $linenum){
            self::SaveError($errno, $errmsg, $filename, $linenum);
        });
    }

    /**
     * 注册未捕获异常处理函数
     */
    protected static function registerExceptionHandler()
    {
        set_exception_handler(function(Throwable $throwable){
            self::SaveError($throwable->getCode(), $throwable->getMessage(), $throwable->getFile(), $throwable->getLine(), $throwable->getTraceAsString());
        });
    }

    /**
     * 注册脚本执行完毕的处理函数
     */
    protected static function registerShutdownHandler()
    {
        register_shutdown_function(function(){
            if($e = error_get_last()) {
                self::SaveError($e['type'], $e['message'], $e['file'], $e['line']);
            }
        });
    }

    /**
     * 格式化异常信息
     * @param $errorNo
     * @param $errorMsg
     * @param $errorFile
     * @param $errorLine
     * @param $traceString
     */
    protected static function SaveError($errorNo, $errorMsg, $errorFile, $errorLine, $traceString = '')
    {
        $errorTypeList = array (
            E_ERROR              => ['Error', 'error'],
            E_WARNING            => ['Warning', 'warning'],
            E_PARSE              => ['Parsing Error', 'error'],
            E_NOTICE             => ['Notice', 'notice'],
            E_CORE_ERROR         => ['Core Error', 'error'],
            E_CORE_WARNING       => ['Core Warning', 'warning'],
            E_COMPILE_ERROR      => ['Compile Error', 'error'],
            E_COMPILE_WARNING    => ['Compile Warning', 'warning'],
            E_USER_ERROR         => ['User Error', 'error'],
            E_USER_WARNING       => ['User Warning', 'warning'],
            E_USER_NOTICE        => ['User Notice', 'notice'],
            E_STRICT             => ['Runtime Notice', 'notice'],
            E_RECOVERABLE_ERROR  => ['Catchable Fatal Error', 'fatal']
        );
        $errorType = isset($errorTypeList[$errorNo]) ? $errorTypeList[$errorNo][0] : $errorNo;
        $msg = $errorType. ': ' . $errorMsg . ' in ' . $errorFile . ' on line ' . $errorLine . PHP_EOL;
        if (!empty($traceString)) {
            $msg .= $traceString;
        }

        if (isset($errorTypeList[$errorNo])) {
            $logType = $errorTypeList[$errorNo][1];
            $logType = empty($logType) ? 'error' : $logType;
            ProcessLog::$logType($msg);
        } else {
            ProcessLog::error($msg);
        }

    }
}