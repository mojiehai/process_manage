<?php

namespace ProcessManage\Config;

/**
 * 配置基类
 * Class Config
 * @package ProcessManage\Config
 */
abstract class Config
{
    ###
    // config item (static variable)
    ###

    /**
     * 加载配置
     * @param array $config
     * @return void
     */
    public static function LoadConfig(array $config)
    {
        foreach ($config as $configField => $configValue) {
            if (isset(static::$$configField)) {
                static::$$configField = $configValue;
            }
        }
    }
}