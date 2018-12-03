<?php

namespace ProcessManage\Command\Template;


/**
 * 管理进程的命令模板
 * Class ManageProcessTemplate
 * @package ProcessManage\Command\Template
 */
class ManageProcessTemplate extends Template
{

    /**
     * 获取模板内容
     * @return string
     */
    public function getTemplateStr()
    {
        return '<start|stop|restart> -[d]';
    }
}