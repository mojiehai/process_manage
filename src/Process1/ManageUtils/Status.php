<?php

namespace ProcessManage\Process1\ManageUtils;

use ProcessManage\Config\ProcessConfig;
use ProcessManage\Process1\Process;

/**
 * 进程状态类
 * Class StatusStorage
 * @package ProcessManage\Process\ManageUtils
 *
 * Process:
 * @property string type Master|Worker
 * @property int pid pid
 * @property string title 进程名称
 * @property string memory 内存
 * @property string startTime 开始时间
 * @property int runTime 运行时间
 * @property array config 配置信息
 *
 * Master:
 * @property int workerPid 子进程pid 逗号分隔
 *
 * Worker:
 * @property int workTimes 运行次数
 */
class Status
{

    /**
     * status array
     * @var array
     */
    protected $info = [];

    /**
     * base file name
     * @var string
     */
    protected $baseFileName = '';

    /**
     * file root
     * @var string
     */
    protected $fileRoot = '';

    /**
     * file name
     * @var string
     */
    protected $fileName = '';


    ###############################
    /**
     * @var string
     */
    protected $titlePrefix = '';

    /**
     * @var string
     */
    protected $baseTitle = '';
    ###############################

    public function __construct(string $titlePrefix, string $baseTitle)
    {
        $this->titlePrefix = $titlePrefix;
        $this->baseTitle = $baseTitle;
        $this->baseFileName = $titlePrefix.Process::TITLE_DELIMITER.$baseTitle;
        $this->initFile();
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->info[$name])) {
            return $this->info[$name];
        } else {
            return null;
        }
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->info[$name] = $value;
    }

    /**
     * 存储进程状态
     * @return bool
     */
    public function save()
    {
        if ($this->createFile()) {
            return file_put_contents($this->fileName, json_encode($this->info).PHP_EOL,FILE_APPEND);
        } else {
            return false;
        }

    }

    /**
     * 重新存储进程状态
     * @return bool
     */
    public function reSave()
    {
        if ($this->deleteFile()) {
            return $this->save();
        } else {
            return false;
        }
    }

    /**
     * 读取进程状态
     * @return array 状态数组
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
     */
    public function read()
    {
        if (file_exists($this->fileName)) {
            $result = [];
            $link = fopen($this->fileName, 'r');
            while (!feof($link)) {
                $str = trim(fgets($link));
                $arr = json_decode($str, true);
                if (!empty($arr)) {
                    $result[] = self::CreateStatus($this->titlePrefix, $this->baseTitle, $arr);
                }
            }
            fclose($link);
            return $this->format($result);
        } else {
            return [
                'MasterConfig' => [],
                'WorkerConfig' => [],
                'Master' => [],
                'Worker' => []
            ];
        }
    }

    /**
     * 格式化
     * @param Status[] $read
     * @return array
     */
    protected function format(array $read)
    {
        /** Process:
         * @property string type Master|Worker
         * @property int pid pid
         * @property string title 进程名称
         * @property string memory 内存
         * @property string startTime 开始时间
         * @property int runTime 运行时间
         *
         * Master:
         * @property int workerPid 子进程pid 逗号分隔
         *
         * Worker:
         * @property int workTimes 运行次数
         */
        $template = [
            'MasterConfig' => [
                'title' => 'title',
                'checkWorkerInterval' => 'checkWorkerInterval',
                'maxWorkerNum' => 'maxWorkerNum',
            ],
            'WorkerConfig' => [
                'title' => 'title',
                'executeTimes' => 'executeTimes',
                'executeUSleep' => 'executeUSleep',
                'limitSeconds' => 'limitSeconds',
            ],
            'Master' => [
                'pid' => 'pid',
                'title' => 'title',
                'memory' => 'memory(m)',
                'startTime' => 'start',
                'runTime' => 'run(s)',
                'workerPid' => 'count',
            ],
            'Worker' => [
                'pid' => 'pid',
                'title' => 'title',
                'memory' => 'memory(m)',
                'startTime' => 'start',
                'runTime' => 'run(s)',
                'workTimes' => 'work',
            ],
        ];
        $result = [];
        $pidArr = []; // 子进程列表
        $config = [
            'MasterConfig' => [],
            'WorkerConfig' => [],
        ]; // 配置列表
        foreach ($read as $k => $v) {
            $tmpValue = $v;
            if ($tmpValue->type == 'Master') {
                $pids = $tmpValue->workerPid;
                $pidArr = explode(',', $pids);
                $workerNum = count($pidArr);
                $tmpValue->workerPid = $workerNum;
            }
            // 配置信息
            if ($tmpValue->type == 'Master') {
                $config['MasterConfig'][$tmpValue->title] = array_merge(['title' => $tmpValue->title], $tmpValue->config);
            } else {
                $config['WorkerConfig'][$tmpValue->title] = array_merge(['title' => $tmpValue->title], $tmpValue->config);
            }
            // memory 使用 M
            $tmpValue->memory = sprintf('%.3f',$tmpValue->memory / 1024 / 1024).'('.$tmpValue->memory.'b)';
            // 去掉本身到配置数组
            $info = $tmpValue->info;
            unset($info['config']);
            $result[$tmpValue->type][$tmpValue->pid] = $info;
        }

        // 填充-没有获取到的worker进程
        foreach ($pidArr as $pid) {
            if (!isset($result['Worker'][$pid])) {
                $res = [];
                foreach ($template['Worker'] as $k => $v) {
                    if ($k == 'pid') {
                        $res[$k] = $pid;
                    } else {
                        $res[$k] = '--';
                    }
                }
                $result['Worker'][$pid] = $res;
            }
        }

        // 合并配置数组
        $result = array_merge($config, $result);

        // 根据模板替换field title
        foreach ($result as $type => $typeArr) {
            foreach ($typeArr as $pid => $row) {
                foreach ($row as $field => $value) {
                    if (isset($template[$type][$field])) {
                        $result[$type][$pid][$template[$type][$field]] = $value;
                        // 新旧title不一样的删除旧title
                        if ($template[$type][$field] != $field) {
                            unset($result[$type][$pid][$field]);
                        }
                    } else {
                        unset($result[$type][$pid][$field]);
                    }
                }
            }
        }

        return $result;
    }


    /**
     * init directory
     * @return string
     */
    protected function initDir()
    {
        $fileRoot = ProcessConfig::$StatusFileRoot;

        if (!is_dir($fileRoot)) {
            $result = mkdir($fileRoot, 0777, true);
            if ($result) {
                chmod($fileRoot, 0777);
                $this->fileRoot = $fileRoot;
            }
        } else {
            $this->fileRoot = $fileRoot;
        }
    }

    /**
     * init file
     */
    protected function initFile()
    {
        $this->initDir();
        if ($this->fileRoot) {
            $this->fileName = $this->fileRoot.DIRECTORY_SEPARATOR.$this->baseFileName;
        }
    }


    /**
     * delete file
     * @return bool
     */
    protected function deleteFile()
    {
        if (!empty($this->fileName) && file_exists($this->fileName)) {
            return unlink($this->fileName);
        } else {
            return true;
        }
    }

    /**
     * create file
     * @return bool
     */
    protected function createFile()
    {
        if (!empty($this->fileName)) {
            if (!file_exists($this->fileName)) {
                return touch($this->fileName);
            } else {
                return true;
            }
        } else {
            return false;
        }
    }


    /**
     * simple create status
     * @param string $titlePrefix
     * @param string $baseTitle
     * @param array $info
     * @return Status
     */
    public static function CreateStatus(string $titlePrefix, string $baseTitle, array $info)
    {
        $status = new Status($titlePrefix, $baseTitle);
        foreach ($info as $k => $v) {
            $status->$k = $v;
        }
        return $status;
    }

}