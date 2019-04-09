<?php

namespace ProcessManage\Process;

use Closure;

/**
 * Class Process
 * @package ProcessManage\Process
 */
class Process
{
    /**
     * process pid
     * @var int
     */
    public $pid = 0;

    /**
     * callback
     * @var Closure
     */
    protected $callback;

    /**
     * 是否开启通讯
     * @var bool
     */
    protected $isCom;
    /**
     * pipe path
     * @var string
     */
    protected $pipePath;

    /**
     * Process constructor.
     * @param Closure $callback
     * @param string|bool $pipePath 通讯管道路径,false:不需要通讯
     */
    public function __construct(Closure $callback, $pipePath = false)
    {
        $this->callback = $callback;
        if ($pipePath === false) {
            $this->isCom = false;
            $this->pipePath = '';
        } else {
            $this->isCom = true;
            $this->pipePath = $pipePath;
        }
    }


    /**
     * start process
     * @return int|false
     */
    public function start()
    {
        $pid = pcntl_fork();
        if ($pid > 0) {
            // master
            $this->pid = $pid;
            return $this->pid;
        } else if ($pid == 0) {
            // child
            $this->pid = posix_getpid();
        } else {
            // error
            return false;
        }

        // run callback
        $this->runHandler();
    }

    /**
     * run callback
     */
    protected function runHandler()
    {
        $callback = $this->callback;
        $callback($this);
    }


    public static function Wait($pid = 0)
    {

    }

}