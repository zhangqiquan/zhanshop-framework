<?php

namespace zhanshop\servevent;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use zhanshop\App;
use zhanshop\Error;

class TaskSchedulerEvent
{
    /**
     * websocket连接
     * @var array
     */
    protected $clientInfo = [];

    /**
     * 任务响应类
     * @var array
     */
    protected $taskResp = [];

    /**
     * 有新的连接进入时，在 worker 进程中回调
     * @param \Swoole\Server $server
     * @param int $fd
     * @param int $reactorId
     * @return void
     */
    public function onConnect($server, int $fd, int $reactorId) :void{

    }

    /**
     * 首次访问
     * @param Server $server
     * @param Request $request
     * @return void
     */
    public function onOpen($server, $request) :void{
        $this->clientInfo[$request->server['remote_addr']][$request->fd] = $request->fd;
        App::log()->push($request->server['remote_addr'].':'.$request->fd.'建立连接');
    }

    /**
     * WebSocket收到来自客户端的数据帧时会回调此函数
     * @param \Swoole\Server $server
     * @param $frame
     * @return void
     */
    public function onMessage($server, $frame) :void{
        $resp = $this->taskResp[$frame->fd] ?? "";
        if($resp){
            $resp->end($frame->data);
            unset($this->taskResp[$frame->fd]);
            return;
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param int $protocol
     * @param string $routeGroup
     * @return void
     */
    public function onRequest(mixed $request, mixed $response, int $protocol = \zhanshop\console\command\Server::WEBSOCKET, string $routeGroup = 'index') :void{

        $response->header('Server', 'zhanshop');
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Headers', '*');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE');
        $response->header('Access-Control-Max-Age', '3600');
        $response->header('Content-Type', 'application/json; charset=utf-8');

        $result = [
            'code' => 0,
            'msg' => "OK",
            'data' => null,
        ];

        try{
            $servRequest = new \zhanshop\Request($protocol, $request);
            $inputData = $servRequest->validateRule([
                'handler' => 'required | string',
                'param' => 'array'
            ])->getData();
            $this->dispatchTask($inputData['handler'], (array)$inputData['param']);
            $this->taskResp[$request->fd] = $response;
        }catch (\Throwable $e){
            $code = $e->getCode();
            $httpCode = ($code < 200 || $code > 505) ? 500 : $code;
            $response->status($httpCode);
            $result['code'] = $code;
            $result['msg'] = $e->getMessage();
            $result['data'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            $result['trace'] = $e->getTrace();
            $response->end(json_encode($result, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * TCP 客户端连接关闭后，在 Worker 进程中回调此函数
     * @param \Swoole\Server $server
     * @param int $fd
     * @param int $reactorId
     * @return void
     */
    public function onClose(\Swoole\Server $server, int $fd, int $reactorId) :void{
        $ip = $server->getClientInfo($fd)['remote_ip'];
        if(isset($this->clientInfo[$ip][$fd])){
            unset($this->clientInfo[$ip][$fd]);
            if($this->clientInfo[$ip] == false) unset($this->clientInfo[$ip]);
            App::log()->push($ip.':'.$fd.'断开连接');
        }
        usleep($this->taskResp[$fd]);
    }
    protected $ipIndex = 0;
    protected $ipFdIndex = [];
    /**
     * 调度任务
     * @param string $handler
     * @param array $param
     * @return void
     */
    protected function dispatchTask(string $handler, array $param){
        $fd = $this->getTaskFd();
        if($fd == false) App::error()->setError('没有任何可执行的客户端连接', 503);
        $this->server->push($fd, json_encode([
            'app' => $app,
            'param' => $param,
        ], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE));
    }

    /**
     * 获取任务执行的fd
     * @return int|string
     */
    protected function getTaskFd(){
        if($this->ipIndex > count($this->clientInfo)){
            $this->ipIndex = 0;
        }

        $clientIpNumber = 0;
        $clients = [];
        foreach($this->clientInfo as $ip => $client){
            if($clientIpNumber == $this->ipIndex){
                $clients = $client;
            }
            $clientIpNumber++;
        }

        if(isset($this->ipFdIndex[$this->ipIndex]) == false){
            $this->ipFdIndex[$this->ipIndex] = 0;
        }
        $clientNumber = 0;
        $fd = 0;

        if($this->ipFdIndex[$this->ipIndex] > count($clients)){
            $this->ipFdIndex[$this->ipIndex] = 0;
        }

        foreach($clients as $fd => $client){
            if($clientNumber == $this->ipFdIndex[$this->ipIndex]){
                $fd = $fd;
                break;
            }
            $clientNumber++;
        }

        $this->ipFdIndex[$this->ipIndex]++;

        $this->ipIndex++;
        return $fd;
    }
}