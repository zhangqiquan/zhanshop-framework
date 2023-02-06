<?php
// +----------------------------------------------------------------------
// | flow-course / Db.php    [ 2021/10/27 4:25 下午 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2021 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);


namespace zhanshop\log\driver;


class Db extends File
{
    /**
     * 日志写入
     * @access protected
     * @param array  $message     日志信息
     * @param string $destination 日志文件
     * @return bool
     */
    protected function write(array $message, string $destination): bool
    {
        // 写入日志直接将日志写入到db
//        $this->checkLogSize($destination);
//
//        $info = [];
//
//        foreach ($message as $type => $msg) {
//            $info[$type] = is_array($msg) ? implode(PHP_EOL, $msg) : $msg;
//        }
//
//        $message = implode(PHP_EOL, $info) . PHP_EOL;
//
//        return error_log($message, 3, $destination);
    }
}