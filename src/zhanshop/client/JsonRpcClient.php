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
     * 已建立的连接
     * @var Client
     */
    protected static array $clients = [];

    /**
     * 连接
     * @param string $host
     * @param int $port
     * @param float $timeout
     * @return void
     */
    public static function client(string $host, int $port = 6204, float $timeout = 3){
        $client = self::$clients[$host.$port] ?? null;

        if($client && $client->isConnected() != false){
            return $client;
        }

        $client = new Client(SWOOLE_SOCK_TCP);
        if (!$client->connect($host, $port, $timeout)){
            App::error()->setError($host.':'.$port.'连接失败#'.$client->errCode);
        }
        self::$clients[$host.$port] = $client;

        return $client;
    }

    /**
     * 销毁连接
     * @param string $host
     * @param int $port
     * @return void
     */
    public static function close(string $host, int $port = 6204){
        if(isset(self::$clients[$host.$port])){
            $client = self::$clients[$host.$port];
            $client->close();
            unset(self::$clients[$host.$port]);
        }
    }

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
    public function request(string $path, mixed $data, bool $again = false){
        $client = JsonRpcClient::client($this->serverHost, $this->serverPort, $this->timeout);
        $requestData = [
            'path' => $path,
            'header' => [],
            'body' => $data,
        ];
        $client->send(json_encode($requestData, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)."\r\n");
        $resp =  $client->recv();
        
        if($resp == false){
            JsonRpcClient::close($this->serverHost, $this->serverPort);
            if($again == false){
                return $this->request($path, $data, true); // 再试一次
            }
            App::error()->setError($this->serverHost.':'.$this->serverPort.$path.' jsonRpc请求失败：'.$resp);
        }
        
        $data = json_decode($resp, true);
        if($data['code'] != 0){
            App::error()->setError($this->serverHost.':'.$this->serverPort.$path.' jsonRpc请求错误：'.$resp, $data['code']);
        }
        return $data['data'];
    }
}