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
     * 原始请求对象
     * @var \Swoole\Http\Response
     */
    protected mixed $response = null;

    protected $fd;
    // http响应码
    protected $httpCode = 200;
    // http响应消息
    protected $msg = "OK";

    protected $data = [];

    /**
     * 构造器
     * @param int $protocol
     * @param \Swoole\Http\Response $rawResponse
     */
    public function __construct(mixed &$response, int $fd = 0)
    {
        $this->fd = $fd;
        $this->response = $response;
    }

    /**
     * 设置响应code
     * @param int $code
     * @return void
     */
    public function setHttpCode(int $code){
        if($code >= 10000){
            $this->httpCode = 417;
        }else{
            $this->httpCode = ($code < 200 || $code > 505) ? 500 : $code;
        }
    }
    /**
     * 获取响应code
     * @param int $code
     * @return void
     */
    public function getHttpCode(){
        return $this->httpCode;
    }

    /**
     * 设置msg
     * @param string $msg
     * @return void
     */
    public function setMsg(string $msg){
        $this->msg = $msg;
    }

    /**
     * 获取msg
     * @return mixed|string
     */
    public function getMsg(){
        return $this->msg;
    }

    public function setFd(int $fd){
        $this->fd = $fd;
    }

    public function getFd(){
        return $this->fd;
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
     * 发送http响应
     * @return void
     */
    public function sendHttp(){
        if($this->data){
            $respData = $this->data;
            if(is_array($respData)){
                $respData['trace_id'] = microtime(true).rand(10000, 99999).'.'. App::config()->get('app.serial_code', 0).'.'.getmypid();
                $this->response->header('Content-Type', 'application/json; charset=utf-8');
                $respData = json_encode($respData);
            }
            $this->response->status($this->httpCode);
            $this->response->end($respData);
        }else{
            $this->response->end();
        }
    }

    /**
     * 发送websocket响应
     * @return void
     */
    public function sendWebSocket(){
        try{
            if($this->data){
                $respData = $this->data;
                if(is_array($respData)){
                    $respData['header']['fd'] = $this->fd;
                    $respData['trace_id'] = microtime(true).rand(10000, 99999).'.'. App::config()->get('app.serial_code', 0).'.'.getmypid();
                    $respData = json_encode($respData, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
                }
                $this->response->push($this->fd, $respData);
            }
        }catch (\Throwable $e){}
    }

    /**
     * 调用原始方法数据
     * @param string $name
     * @param array $args
     * @return false
     */
    public function __call(string $name, array $args){
        try {
            return $this->response->$name(...$args);
        }catch (\Throwable $e){
            return null;
        }
    }
}