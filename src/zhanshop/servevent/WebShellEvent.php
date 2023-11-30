<?php
// +----------------------------------------------------------------------
// | zhanshop-admin / WebShell.php    [ 2023/11/30 11:51 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\servevent;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use zhanshop\App;
use zhanshop\ServEvent;

class WebShellEvent extends ServEvent
{
    protected $device = [];
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
        try {
            if($request->server['request_uri'] == '/register'){
                $id = $request->get['id'] ?? App::error()->setError('id不能为空', 400);
                $this->device[$request->server['remote_addr']][$id][$request->fd] = 0;
            }else if($request->server['request_uri'] == '/terminal'){
                $id = $request->get['id'] ?? App::error()->setError('设备id不能为空');
                list($ip, $device) = explode(":", $id);
                $toFd = 0;
                $ipDevice = $this->device[$ip][$device] ?? App::error()->setError($id.'没有在线设备可用', 404);
                foreach($ipDevice as $fd => $status){
                    if($status == 0){
                        // 告诉用户端你要连接的fd是多少
                        $toFd = $fd;
                        break;
                    }
                }
                if($ipDevice == false) App::error()->setError($id.'统计0个可用连接');
                if($toFd == false) App::error()->setError('没有可使用的设备连接', 503);
                $server->push($request->fd, ServEvent::eventResult('deviceFd', ['tofd' => $toFd, 'myfd' => $request->fd])); // 告诉前端对方的fd和自身的fd
            }
        }catch (\Throwable $e){
            $server->push($request->fd, ServEvent::eventResult('notconnect', null, $e->getMessage(), $e->getCode()));
        }
    }

    /**
     * WebSocket收到来自客户端的数据帧时会回调此函数
     * @param \Swoole\Server $server
     * @param $frame
     * @return void
     */
    public function onMessage($server, $frame) :void{
        $data = $frame->data;
        $data = json_decode($data, true);
        $toFd = $data['tofd'] ?? App::error()->setError('tofd不能为空', 400);
        $myFd = $data['myfd'] ?? App::error()->setError('myfd不能为空', 400);
        $data['tofd'] = $myFd;
        $data['myfd'] = $toFd;
        $server->push($toFd, json_encode($data, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param int $protocol
     * @param string $routeGroup
     * @return void
     */
    public function onRequest(mixed $request, mixed $response, int $protocol = \zhanshop\console\command\Server::WEBSOCKET, string $routeGroup = 'webshell') :void{
        $response->status(200);
        if($request->getMethod() == 'GET'){
            $path = App::rootPath().'/public'.($request->server['request_uri'] == '/' ? '/index.html' : $request->server['request_uri']);
            if(file_exists($path) && !is_dir($path)){
                $response->end(file_get_contents($path));
            }else{
                $response->status(404);
                $response->end('404 not found');
            }
        }else{
            $devices = [];
            foreach ($this->device as $ip => $ids){
                foreach ($ids as $id => $fds){
                    if(!in_array($ip.':'.$id, $devices)){
                        $devices[] = $ip.':'.$id;
                    }
                }
            }
            $response->header('Content-Type', 'application/json; charset=utf-8');
            $response->end(json_encode($devices));
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
        foreach($this->device[$ip] ?? [] as $id => $fds){
            foreach($fds as $_fd => $status){
                if($_fd == $fd){
                    unset($this->device[$ip][$id][$fd]);
                    break;
                }
            }
        }
        if(isset($this->device[$ip]) && $this->device[$ip] == false) unset($this->device[$ip]);
    }
}