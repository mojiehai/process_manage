<?php

namespace ProcessManage\Config;


/**
 * 进程配置类
 * Class ProcessConfig
 * @package ProcessManage\Config
 */
class ProcessConfig extends Config
{

    /**
     * 进程pid文件根目录
     * @var string
     */
    public static $PidRoot = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'pid';
}