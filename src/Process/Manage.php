<?php

namespace ProcessManage\Process;


use ProcessManage\Exception\ProcessException;
use ProcessManage\Exception\Exception;
use ProcessManage\Helper\ResourceManage;
use ProcessManage\Process\ManageUtils\SystemRegister;

/**
 * code is far away from bug with the animal protecting
 *   ┏┓   ┏┓
 * ┏━┛┻━━━┛┻━┓
 * ┃　 　 　  ┃
 * ┃　　　━   ┃
 * ┃　┳┛　┗┳　┃
 * ┃　 　 　  ┃
 * ┃ 　　┻ 　 ┃
 * ┃　 　 　  ┃
 * ┗━┓　　　┏━┛
 *   ┃　　　┃  神兽保佑
 *   ┃　　　┃  代码无BUG！
 *   ┃　　　┗━━━┓
 *   ┃　　　    ┣┓
 *   ┃　　　　   ┏┛
 *   ┗┓┓┏━━━┳┓┏━┛
 *    ┃┫┫   ┃┫┫
 *    ┗┻┛   ┗┻┛
 *
 * ---------------------------------
 *
 * 单任务进程管理器
 * Class Manage
 * @package ProcessManage\Process
 */
class Manage
{

    /**
     * 配置
     * @var array
     */
    protected $config = [];

    /**
     * 工作初始化
     * @var \Closure
     */
    protected $closureInit = null;

    /**
     * 工作回调
     * @var \Closure
     */
    protected $closure = null;

    /**
     * master 进程
     * @var Master
     */
    protected $master = null;

    /**
     * @param array $config
     * Manage constructor.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->master = new Master($this->config);
    }

    /**
     * 设置为后台运行
     * @return $this
     * @throws ProcessException
     */
    public function setBackground()
    {
        //分离出子进程
        $pid = pcntl_fork();
        if($pid < 0){
            throw new ProcessException('background run error!');
        }else if($pid > 0){
            // 杀掉父进程
            exit;
        }
        //脱离当前终端(脱离死去的父进程的牵制)
        $sid = posix_setsid();
        if ($sid < 0) {
            exit;
        }

        // 重设资源描述符
        ResourceManage::resetFileDescriptor();

        return $this;
    }

    /**
     * 设置进程的工作初始化
     * @param \Closure $closure
     * @return $this
     */
    public function setWorkInit(\Closure $closure = null)
    {
        if (is_callable($closure)) {
            $this->closureInit = $closure;
        }
        return $this;
    }

    /**
     * 设置进程的工作内容
     * @param \Closure $closure
     * @return $this
     */
    public function setWork(\Closure $closure = null)
    {
        if (is_callable($closure)) {
            $this->closure = $closure;
        }
        return $this;
    }

    ################################## get ####################################

    /**
     * 获取master进程
     * @return Master
     */
    public function getMaster()
    {
        return $this->master;
    }

    ################################## command action ####################################
    /**
     * start命令动作
     * @return void
     * @throws Exception
     */
    public function start()
    {
        if (!$this->master->isAlive()) {
            // 注册加载函数
            SystemRegister::registerAllHandler();
            $this->master->setWorkInit($this->closureInit)->setWork($this->closure)->run();
        } else {
            throw new Exception('process('.$this->master->title.') is exists!');
        }
    }

    /**
     * stop命令动作
     * @return bool
     * @throws Exception
     */
    public function stop()
    {
        if ($this->master->isAlive()) {
            if ($this->master->setStop()) {
                return true;
            } else {
                throw new Exception('stop "'.$this->master->title.'" failure');
            }
        } else {
            throw new Exception('process('.$this->master->title.') is not exists!', 100);
        }
    }

    /**
     * wakeup命令动作
     * @return bool
     * @throws Exception
     */
    public function wakeup()
    {
        if ($this->master->isAlive()) {
            if ($this->master->wakeup()) {
                return true;
            } else {
                throw new Exception('wakeup "'.$this->master->title.'" failure');
            }
        } else {
            throw new Exception('process('.$this->master->title.') is not exists!', 100);
        }
    }

    /**
     * restart命令动作
     * @throws Exception
     */
    public function restart()
    {
        $this->stop();
        $i = 0;
        while($this->master->isAlive()) {
            if ($i > 10) {
                throw new Exception('failure to stop the master process('.$this->master->title.')!');
            }
            sleep(1);
            $i ++;
        }
        $this->start();
    }


    /**
     * 保存进程状态到缓存文件中（该方法保存后，需要等待一会，不能立即获取状态）
     * @return bool
     * @throws Exception
     */
    public function saveProcessStatusToCache()
    {
        if ($this->master->isAlive()) {
            // 发送信号让进程记录status
            return $this->master->saveStatus();
        } else {
            throw new Exception('process('.$this->master->title.') is not exists!', 100);
        }
    }

    /**
     * 在缓存文件中获取进程的状态
     * @return array 信息数组
     * [
     *  'MasterConfig' => [
     *      0 => ['title' => '...', ...],
     *  ],
     *  'WorkerConfig' => [
     *      0 => ['title' => '...', ...],
     *  ],
     *  'Master' => [
     *      123 => ['pid' => 123, ...]
     *  ],
     *  'Worker' => [
     *      1232 => ['pid' => 1232, ...],
     *      1233 => ['pid' => 1233, ...],
     *      1234 => ['pid' => 1234, ...],
     *      ...
     *  ]
     * ]
     * @throws Exception
     */
    public function getProcessStatusByCache()
    {
        if ($this->master->isAlive()) {
            // 获取进程的状态
            return $this->master->getAllStatus();
        } else {
            throw new Exception('process('.$this->master->title.') is not exists!', 100);
        }
    }

    /**
     * return status信息
     * @return array
     * @throws Exception
     */
    public function status()
    {
        $this->saveProcessStatusToCache();
        // 睡眠1秒，等待进程记录
        sleep(1);
        return $this->getProcessStatusByCache();
    }

    /**
     * 显示或输出status信息
     * @param array $status status信息数组
     * @param bool $isReturn 是否返回 true：返回字符串  false：直接输出字符串
     * @return void|string
     */
    public static function showStatus(array $status, bool $isReturn = false)
    {
        $str = '';
        foreach ($status as $processType => $infoArr) {
            $str .= $processType.PHP_EOL;
            // 获取每个字段最长的长度
            $lengthArr = [];
            foreach ($infoArr as $pid => $row) {
                foreach ($row as $field => $value) {
                    if (!isset($lengthArr[$field])) {
                        $lengthArr[$field] = strlen($field);
                    }
                    $lengthArr[$field] = max($lengthArr[$field], strlen($value.''));
                }
            }
            $i = 0;
            foreach ($infoArr as $pid => $row) {
                if ($i == 0) {
                    // title
                    $tmpRow = [];
                    foreach ($row as $k => $v) {
                        $tmpRow[$k] = $k;
                    }
                    $keys = static::fullStringByArray($tmpRow, $lengthArr);
                    $str .= '  ' . implode('    ', $keys).PHP_EOL;
                }
                $values = static::fullStringByArray($row, $lengthArr);
                $str .= '  '. implode('    ', $values).PHP_EOL;

                $i ++;
            }

            $str .= PHP_EOL;
        }
        if ($isReturn) {
            return $str;
        } else {
            echo $str;
        }
    }
    ################################## command action ####################################

    /**
     * 把field中每个元素的长度填充到lengthArr指定的长度去
     * @param array $field
     * @param array $lengthArr
     * @return array
     */
    protected static function fullStringByArray(array $field, array $lengthArr)
    {
        foreach ($field as $k => $v) {
            if (isset($lengthArr[$k])) {
                $field[$k] = str_pad($v.'', $lengthArr[$k], ' ');
            }
        }
        return $field;
    }


}