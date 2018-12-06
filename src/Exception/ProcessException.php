<?php

namespace ProcessManage\Exception;
use ProcessManage\Log\ProcessLog;
use Throwable;


/**
 * 进程管理相关异常
 * Class ProcessException
 * @package ProcessManage\Exception
 */
class ProcessException extends Exception
{

    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->message = 'pid: '.posix_getpid(). '    ppid: '.posix_getppid(). '    title:'.cli_get_process_title() . '    '.$this->message;
        ProcessLog::error($this->getExceptionAsString());
    }

}