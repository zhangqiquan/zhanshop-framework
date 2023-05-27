<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / Http2Client.php    [ 2023/4/24 14:21 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\client;

use Swoole\Http2\Request;
use Swoole\Coroutine\Http2\Client;
use Swoole\Http2\Response;

class Http2Client
{
    /**
     * @var Client
     */
    protected mixed $connects = [];

    protected $timeout = 3;

    public function setTimeOut(int $timeout){
        $this->timeout = $timeout;
    }


    public function test(){
        $domain = '127.0.0.1';
        $cli = new Client($domain, 9509, false);
        $cli->set([
            'timeout' => -1,
            'ssl_host_name' => $domain
        ]);
        $cli->connect();
        return $cli;
    }
    /**
     * 获取连接
     * @param $host
     * @param $port
     * @param $ssl
     * @return Client
     */
    protected function connect(string $host, int $port, bool $ssl){
        if(isset($this->connects[$host.':'.$port])){
            return $this->connects[$host.':'.$port];
        }
        $http2 = new Client($host, $port, $ssl);
        $setData = ['timeout' => $this->timeout];
        if($ssl) $setData['ssl_host_name'] = $host;

        $http2->connect();
        $http2->set($setData);

        $this->connects[$host.':'.$port] = $http2;

        return $http2;

    }

    /**
     * 请求
     * @param string $host
     * @param int $port
     * @param float $timeout
     * @return Response
     * @throws \Exception
     */
    public function request(string $url, $method = 'GET', mixed $data = [], $contentType = 'application/json'){
        $urls = parse_url($url);
        $host = $urls['host'] ?? '127.0.0.1';
        $port = $urls['port'] ?? 80;
        $ssl = false;
        if(($urls['scheme'] ?? 'http') == 'https'){
            $ssl = true;
            if(isset($urls['port']) == false) $port = 443;
        }
        $req = new Request();
        $req->method = $method;
        $req->path = $urls['path'];
        if(is_array($data) || is_object($data)) $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $req->data = $data;
        $req->headers = [
            'host' => $host,
            'user-agent' => 'zhanshop-client',
            'accept-encoding' => 'gzip',
            'Content-Type' => $contentType
        ];

        $connect = $this->connect($host, $port, $ssl);
        $connect->send($req);
        $response = $connect->recv();
        return $response;
    }

    /**
     * 销毁连接
     */
    public function __destruct()
    {
        foreach ($this->connects as $v){
            $v->close();
        }
    }
}