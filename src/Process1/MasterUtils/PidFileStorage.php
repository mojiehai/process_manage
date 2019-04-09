<?php

namespace ProcessManage\Process1\MasterUtils;

use ProcessManage\Config\ProcessConfig;
use ProcessManage\Exception\ProcessException;

/**
 * 进程 pid 文件存储类
 * Class PidStorage
 * @package ProcessManage\Process\MasterUtils
 */
class PidFileStorage
{
    /**
     * 进程名称
     * @var string
     */
    protected $title = '';

    /**
     * 进程存放pid文件的目录
     * @var string
     */
    protected $pidFileDir = '';

    /**
     * 进程pid文件的完整目录
     * @var string
     */
    protected $pidFilePath = '';

    /**
     * 进程pid文件的名称
     * @var string
     */
    protected $pidFileName = '';

    /**
     * PidStorage constructor.
     * @param string $title 进程完整名称
     */
    public function __construct(string $title)
    {
        $this->title = $title;
        // 初始化根目录
        $this->pidFileDir = ProcessConfig::$PidRoot;
        // 初始化pid文件名
        $this->pidFileName = $this->title;
        // 初始化pid完整文件路径
        $this->pidFilePath = $this->pidFileDir.DIRECTORY_SEPARATOR.$this->pidFileName;
    }

    /**
     * 初始化pid文件
     * @throws ProcessException
     */
    protected function initStorage()
    {
        if (empty($this->pidFileDir) || empty($this->pidFileName)) {
            throw new ProcessException("pid file configure error");
        }
        if (!is_dir($this->pidFileDir)) {
            if (!mkdir($this->pidFileDir, 0777, true)) {
                throw new ProcessException('create master pid directory failure');
            }
            chmod($this->pidFileDir, 0777);
        }
        if (!file_exists($this->pidFilePath)) {
            if (!touch($this->pidFilePath)) {
                throw new ProcessException('create master pid file failure');
            }
        }
    }

    /**
     * 保存pid
     * @param int $pid pid
     * @return bool
     * @throws ProcessException
     */
    public function save(int $pid)
    {
        if ($pid > 0) {
            $this->initStorage();
            $return = file_put_contents($this->pidFilePath, $pid);
            if ($return) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 从文件中获取pid
     * @return int pid
     * @throws ProcessException
     */
    public function read()
    {
        $this->initStorage();
        return intval(file_get_contents($this->pidFilePath));
    }


}