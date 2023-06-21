<?php
// +----------------------------------------------------------------------
// | zhanshop_admin / Response.php [ 2023/4/16 下午3:52 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop;

use app\http\Controller;
use zhanshop\console\command\Server;

/**
 * @mixin \Swoole\Http\Response
 */
class Response
{
    /**
     * 协议
     * @var int
     */
    //protected $protocol = Server::HTTP;

    /**
     * 原始请求对象
     * @var \Swoole\Http\Response
     */
    protected mixed $rawResponse = null;
    /**
     * @var \app\admin\Controller
     */
    protected mixed $controller = null;

    protected $fd;

    protected $status = 200;

    protected $data = [];

    /**
     * 构造器
     * @param int $protocol
     * @param \Swoole\Http\Response $rawResponse
     */
//    public function __construct(int &$protocol, mixed &$rawResponse, int $fd = 0)
//    {
//        $this->fd = $fd;
//        $this->protocol = $protocol;
//        $this->rawResponse = $rawResponse;
//    }

    /**
     * 构造器
     * @param int $protocol
     * @param \Swoole\Http\Response $rawResponse
     */
    public function __construct(mixed &$rawResponse, int $fd = 0)
    {
        $this->fd = $fd;
        $this->rawResponse = $rawResponse;
    }

    /**
     * 设置控制器
     * @param string $class
     * @return void
     */
    public function setController(string $class){
        try {
            $this->controller = App::make($class);
        }catch (\Throwable $e){
            Log::errorLog(SWOOLE_LOG_ERROR, $e->getMessage().PHP_EOL.'#@ '.$e->getFile().':'.$e->getLine().PHP_EOL.$e->getTraceAsString());
            $this->data = $e->getMessage();
        }
        return $this->controller;
    }

    /**
     * 设置响应code
     * @param int $code
     * @return void
     */
    public function setStatus(int $code){
        if($code < 200){
            $this->status = 500;
        }else if($code > 505){
            $this->status = 417;
        }else{
            $this->status = $code;
        }
    }
    /**
     * 获取响应code
     * @param int $code
     * @return void
     */
    public function getStatus(){
        return $this->status;
    }

    /**
     * 设置响应data
     * @param mixed $data
     * @return void
     */
    public function setData(mixed $data){
        $this->data = $data;
    }

    /**
     * 获取响应data
     * @return int|mixed
     */
    public function getData(){
        return $this->data;
    }

    /**
     * 设置控制器成功信息
     * @param mixed $data
     * @return void
     */
    public function setSuccessData(mixed $data){
        $this->data = $data;
    }

    /**
     * 设置控制器错误信息
     * @param string $controller
     * @param \Throwable $e
     * @return void
     */
    public function setErrorData(\Throwable $e){
        try {
            $data = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
            $this->data = $this->controller->result($data, $e->getMessage(), $e->getCode());
        }catch (\Throwable $e){
            Log::errorLog(SWOOLE_LOG_ERROR, $e->getMessage().PHP_EOL.'#@ '.$e->getFile().':'.$e->getLine().PHP_EOL.$e->getTraceAsString());
            $this->data = $e->getMessage();
        }
    }

    /**
     * 发送http响应
     * @return void
     */
    public function sendHttp(){
        if($this->data){
            $respData = $this->data;
            if(is_array($respData)){
                $respData['trace_id'] = uniqid().rand(10000, 99999).'.'. App::config()->get('app.serial_code', 0);
                // 判断常量是否存在
                if($this->status > 399 && App::config()->get('app.show_error', true) == false){
                    $filedMsg = constant($this->controller::class.'::RESP_MSG');
                    $filedData = constant($this->controller::class.'::RESP_DATA');
                    if($filedMsg && $filedData){
                        $respData[$filedData] = null;
                        if($this->status >= 500) $respData[$filedMsg] = App::config()->get('app.error_message', 'server error');
                    }
                }
                $this->rawResponse->header('Content-Type', 'application/json; charset=utf-8');
                $respData = json_encode($respData);
            }

            $this->rawResponse->status($this->status);
            $this->rawResponse->end($respData);
        }
    }

    /**
     * 调用原始方法数据
     * @param string $name
     * @param array $args
     * @return false
     */
    public function __call(string $name, array $args){
        try {
            return $this->rawResponse->$name(...$args);
        }catch (\Throwable $e){
            return null;
        }
    }
}