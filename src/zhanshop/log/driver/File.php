<?php
// +----------------------------------------------------------------------
// | flow-course / File.php    [ 2021/10/26 9:47 上午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\log\driver;

use zhanshop\App;
use zhanshop\Log;

/**
 *
 */
class File
{
    /**
     * 日志写入
     * @access protected
     * @param Log  $obj     日志对象
     * @param string $destination 日志文件
     * @return bool
     */
    public function write(Log &$obj): bool
    {
        $message = "";
        while ($row = $obj->pop()){
            $message .= $row.PHP_EOL;
        }
        if($message) return error_log($message, 3, $this->getDestination());
        return false;
    }

    /**
     * 获取日志文件名
     * @access public
     * @return string
     */
    protected function getDestination(): string
    {
        $filename = date('Ymd') . '.log';
        return App::runtimePath() .DIRECTORY_SEPARATOR. 'log' .DIRECTORY_SEPARATOR. $filename;
    }

}