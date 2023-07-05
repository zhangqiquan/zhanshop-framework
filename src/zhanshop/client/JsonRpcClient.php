<?php
// +----------------------------------------------------------------------
// | admin / JsonRpcClient.php    [ 2023/7/4 下午2:38 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\client;

use Swoole\Coroutine\Client;
use zhanshop\App;

class JsonRpcClient
{
    protected $serverHost = '127.0.0.1';

    protected $serverPort = 6204;

    protected $timeout = 3;

    /**
     * 设置grpc服务地址
     * @param string $name
     * @return $this
     */
    public function setServerHost(string $host, int $port = 6204, float $timeout = 3){
        $this->serverHost = $host;
        $this->serverPort = $port;
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * 原始请求
     * @param string $path
     * @param mixed $data
     * @return mixed
     * @throws \Exception
     */
    public function request(string $path, mixed $data){
        $client = new Client(SWOOLE_SOCK_TCP);
        if (!$client->connect($this->serverHost, $this->serverPort, $this->timeout)){
            App::error()->setError($this->serverHost.':'.$this->serverPort.'连接失败#'.$client->errCode);
        }
        $requestData = [
            'path' => $path,
            'header' => [],
            'body' => $data,
        ];

        $client->send(json_encode($requestData, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)."\r\n");
        $resp =  $client->recv();
        $data = json_decode($resp, true);
        if($data['code'] != 0){
            App::error()->setError($this->serverHost.':'.$this->serverPort.$path.' jsonRpc请求错误：'.$resp, $data['code']);
        }
        return $data['data'];
    }
}