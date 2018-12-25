<?php


namespace ProcessManage\Helper;

/**
 * 资源管理帮助类
 * Class ResourceManage
 * @package ProcessManage\Helper
 */
class ResourceManage
{
    /**
     * 重设资源描述符
     */
    public static function resetFileDescriptor()
    {
        //设置默认文件权限
        umask(022);
        //将当前工作目录更改为根目录
        chdir('/');
        //关闭文件描述符
        if (!defined('IS_CLOSE_STDIN')) {
            fclose(STDIN);
            define('IS_CLOSE_STDIN', true);
        }
        if (!defined('IS_CLOSE_STDOUT')) {
            fclose(STDOUT);
            define('IS_CLOSE_STDOUT', true);
        }
        if (!defined('IS_CLOSE_STDERR')) {
            fclose(STDERR);
            define('IS_CLOSE_STDERR', true);
        }
        //重定向输入输出
        global $STDOUT, $STDERR;
        $STDOUT = fopen('/dev/null', 'a');
        $STDERR = fopen('/dev/null', 'a');
    }
}