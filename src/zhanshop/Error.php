<?php
// +----------------------------------------------------------------------
// | framework / ErrorException.php    [ 2021/10/29 11:29 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2022 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;

class Error
{
    protected $exceptionHandle = null;

    public function __construct(){
        if($handler = App::config()->get('app.handler')) $this->exceptionHandle = new $handler;
    }

    /**
     * 设置错误信息
     * @param int $code
     * @param string $msg
     */
    public function setError(string $msg, int $code = 500){
        throw new \Exception($msg, $code);
    }

    /**
     * 系统异常处理
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     */
    public function error(int $errno,  $errstr,  $errfile,  $errline){
        $this->setError($errstr.'; errfile:'.$errfile.'; errline:'.$errline, $errno);
    }

    public function exception(\Throwable $exception){
        return [
            'msg' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
        ];
    }

}