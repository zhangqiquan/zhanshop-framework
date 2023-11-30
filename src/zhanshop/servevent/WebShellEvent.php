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
use zhanshop\ServEvent;

class WebShellEvent extends ServEvent
{
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
    }

    /**
     * WebSocket收到来自客户端的数据帧时会回调此函数
     * @param \Swoole\Server $server
     * @param $frame
     * @return void
     */
    public function onMessage($server, $frame) :void{
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
    }
}