<?php
// +----------------------------------------------------------------------
// | admin / GrpcClient.php    [ 2023/7/4 下午2:37 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\client;

use Swoole\Coroutine\Http2\Client;
use Swoole\Http2\Request;
use zhanshop\helper\Grpc;

class GrpcClient
{
    protected $serverHost = 'http://127.0.0.1:6203';

    protected $config = [
        'timeout' => 3.0, // 3秒
    ];
    /**
     * 已建立的连接
     * @var Client
     */
    protected array $connects = [];

    /**
     * 设置配置
     * @param string $key
     * @param mixed $val
     * @return void
     */
    public function setConfig(string $key, mixed $val){
        $this->config[$key] = $val;
        return $this;
    }

    /**
     * 获取连接
     * @param string $host
     * @param int $port
     * @param bool $ssl
     * @return mixed|null
     */
    protected function connect(string $host, int $port, bool $ssl = false){
        $connect = $this->connects[$host.$port] ?? null;
        if(!$connect){
            $connect = new Client($host, $port, $ssl);
            $config = $this->config;
            if($ssl) $config['ssl_host_name'] = $host;
            $connect->set($config);
            $connect->connect();
            $this->connects[$host.$port] = $connect;
        }
        return $connect;
    }

    /**
     * 解析URL
     * @param string $url
     * @return array|int|string
     */
    protected function parseUrl(string &$url){
        $urls = parse_url($url);
        $urls['host'] = $urls['host'] ?? '127.0.0.1';
        $urls['port'] = $urls['port'] ?? 80;
        $urls['ssl'] = false;
        if(($urls['scheme'] ?? 'http') == 'https'){
            $urls['ssl'] = true;
            if(isset($urls['port']) == false) $urls['port'] = 443;
        }
        return $urls;
    }
    /**
     * 获取请求对象
     * @param string $url
     * @param mixed $data
     * @return void
     */
    protected function requestData(string &$host, string &$path, mixed &$data){

        $req = new Request();
        $req->method = 'POST';
        $req->path = $path;
        if(is_array($data) || is_object($data)) $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $req->data = $data;
        $req->headers = [
            'host' => $host,
            'user-agent' => 'zhanshop',
            'accept-encoding' => 'gzip',
            'Content-Type' => 'application/grpc+proto'
        ];
        return $req;
    }

    /**
     * 发起grpc请求
     * @param string $url
     * @param mixed $data
     * @return mixed
     */
    public function request(string $url, mixed $data){
        $parseUrl = $this->parseUrl($url);
        $requestData = $this->requestData($parseUrl['host'], $parseUrl['path'], $data);

        $connect = $this->connect($parseUrl['host'], $parseUrl['port'], $parseUrl['ssl']);
        $connect->send($requestData);
        $response = $connect->recv();
        if($response == false) App::error()->setError($url.' 无法访问');
        return $response;
    }

    /**
     * 设置grpc服务地址
     * @param string $name
     * @return $this
     */
    public function setServerHost(string $host){
        $this->serverHost = $host;
        return $this;
    }
    /**
     * 简易请求
     * @param mixed $request
     * @param mixed $response
     * @return void
     */
    public function simpleRequest(string $url, mixed &$request, mixed &$response){
        $body = Grpc::serialize($request);
        $resp = $this->request($url, $body);
        if($resp->statusCode != 200){
            App::error()->setError($url.' grpc请求错误：'.$resp->data, $resp->statusCode);
        }
        Grpc::deserialize($response, $resp->data ?? '');
    }
}