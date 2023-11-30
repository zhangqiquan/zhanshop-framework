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
        if($request->server['request_uri'] = '/device'){
            $id = $request->get['id'] ?? App::error()->setError('id不能为空', 400);
            $this->device[$request->server['remote_addr']][$id][$request->fd] = 0;
        }else{
            $id = $request->get['id'] ?? App::error()->setError('设备id不能为空');
            list($ip, $device) = explode(":", $id);
            $devices = $this->device[$id] ?? App::error()->setError('id不能为空', 404);
            $toFd = 0;
            $ipDevice = $this->device[$ip][$device] ?? App::error()->setError('没有'.$id.'的在线设备可用', 404);
            foreach($ipDevice as $fd => $status){
                if($status == 0){
                    // 告诉用户端你要连接的fd是多少
                    $toFd = $fd;
                    break;
                }
            }
            if($ipDevice == false) App::error()->setError($id.'统计0个可用连接');
            if($toFd == false) App::error()->setError('没有可使用的设备连接', 503);
            $server->push($request->fd, ServEvent::eventResult('device_fd', ['to_fd' => $toFd, 'my_fd' => $request->fd])); // 告诉前端对方的fd和自身的fd
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
        $server->push($data['to_fd'], ServEvent::eventResult($data['event'], $data['body']));
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param int $protocol
     * @param string $routeGroup
     * @return void
     */
    public function onRequest(mixed $request, mixed $response, int $protocol = \zhanshop\console\command\Server::WEBSOCKET, string $routeGroup = 'webshell') :void{

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
        if($this->device[$ip] == false) unset($this->device[$ip]);
    }
}