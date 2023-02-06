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

class File
{
    /**
     * 日志写入
     * @access protected
     * @param array  $message     日志信息
     * @param string $destination 日志文件
     * @return bool
     */
    public function write(string &$message): bool
    {

        return error_log($message, 3, $this->getDestination());
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