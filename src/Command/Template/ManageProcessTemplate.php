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
     * 命令映射的类
     * @var array
     */
    protected $mapping = [
        'action' => [
            'start' => '\ProcessManage\Command\Action\Start',
            'stop' => '\ProcessManage\Command\Action\Stop',
            'restart' => '\ProcessManage\Command\Action\ReStart',
        ],
        'options' => [
            'd' => '\ProcessManage\Command\Options\D',
        ],
    ];

    /**
     * 获取模板内容
     * @return string
     */
    public function getTemplateStr()
    {
        return '<start|stop|restart> -[d]';
    }
}