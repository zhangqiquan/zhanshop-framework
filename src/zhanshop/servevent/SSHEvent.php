<?php
// +----------------------------------------------------------------------
// | zhanshop-docker-server / SSHEvent.php    [ 2023/12/7 15:43 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\servevent;

use phpseclib3\Net\SSH2;
use zhanshop\App;
use zhanshop\console\command\Server;
use zhanshop\ServEvent;

class SSHEvent extends ServEvent
{
    /**
     * http请求
     * @param mixed $request
     * @param mixed $response
     * @param int $protocol
     * @param string $appName
     * @return void
     */
    public function onRequest(mixed $request, mixed $response, int $protocol = Server::WEBSOCKET, string $appName = 'terminal') :void{
        $uri = App::rootPath().'/public'.$request->server['request_uri'];
        if(is_dir($uri)) $uri = rtrim($uri, '/').'/index.html';
        if(file_exists($uri)){
            $ext = pathinfo($uri, PATHINFO_EXTENSION);
            if(in_array($ext, ['js', 'css'])){
                $response->header('Content-Type', 'text/'.$ext);
            }else{
                $response->header('Content-Type', mime_content_type($uri));
            }
            $response->end(file_get_contents($uri, false, null, 0, 1000000));
            return;
        }
        $response->status(404);
        $response->end();
    }

    protected $sshs = [];
    /**
     * websocket首次请求
     * @param $server
     * @param $request
     * @param int $protocol
     * @param string $appName
     * @return void
     */
    public function onOpen($server, $request, int $protocol = Server::WEBSOCKET, string $appName = 'terminal') :void{
        try {
            App::phar()->import('app/library/phpseclib');
            $ssh = new SSH2('127.0.0.1', 22);
            $ssh->login('root', '123456');
            $ssh->setTimeout(0.1);
            $this->sshs[$request->fd] = $ssh;
            while (true){
                if($server->exist($request->fd) == false){
                    $ssh->disconnect();
                    break;
                }
                $read = $ssh->read('root@root:~$');
                if($read){
                    try {
                        $server->push($request->fd, $read);
                    }catch (\Throwable $e){
                        $server->close($request->fd);
                        break;
                    }
                }
            }
        }catch (\Throwable $err){

        }
    }

    /**
     * 消息响应
     * @param $server
     * @param $frame
     * @param int $protocol
     * @param string $appName
     * @return void
     */
    public function onMessage($server, \Swoole\WebSocket\Frame $frame, int $protocol = Server::WEBSOCKET, string $appName = 'terminal') :void{
        $command = $frame->data;
        $ssh = $this->sshs[$frame->fd] ?? null;
        if($command && $ssh){
            $ssh->write($command."\n");
        }
    }

    /**
     * 连接断开
     * @param \Swoole\Server $server
     * @param int $fd
     * @param int $reactorId
     * @return void
     */
    public function onClose(\Swoole\Server $server, int $fd, int $reactorId) :void{
        if(isset($this->sshs[$fd])){
            unset($this->sshs[$fd]);
        }
    }
}