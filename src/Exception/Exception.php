<?php

namespace ProcessManage\Exception;


/**
 * Class Exception
 * @package ProcessManage\Exception
 */
class Exception extends \Exception
{

    /**
     * 获取异常信息
     * @return string
     */
    public function getExceptionAsString()
    {
        $msg = get_class($this). ': ' . $this->getMessage() . ' in ' . $this->getFile() . ' on line ' . $this->getLine() . PHP_EOL;
        $msg .= $this->getTraceAsString();
        return $msg;
    }

    public function __toString()
    {
        return $this->getExceptionAsString();
    }

}