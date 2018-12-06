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
    public static $PidRoot = '/tmp/pm/pid';

    /**
     * 进程前缀
     * @var string
     */
    public static $TitlePrefix = 'process_m';
}