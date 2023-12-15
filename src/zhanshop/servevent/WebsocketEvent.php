<?php
// +----------------------------------------------------------------------
// | zhanshop-server / WebsocketEvent.php    [ 2023/12/7 15:54 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2023 zhangqiquan All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhangqiquan <768617998@qq.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace zhanshop\servevent;

use zhanshop\App;
use zhanshop\console\command\Server;
use zhanshop\Request;
use zhanshop\Response;
use zhanshop\ServEvent;

class WebsocketEvent extends ServEvent
{
    /**
     * http请求
     * @param mixed $request
     * @param mixed $response
     * @param int $protocol
     * @param string $appName
     * @return void
     */
    public function onRequest(mixed $request, mixed $response, int $protocol = Server::WEBSOCKET, string $appName = 'websocket') :void{
        if(!$this->onStatic()){
            $servRequest = new Request($protocol, $request);
            $servResponse = new Response($response, $request->fd);
            App::webhandle()->dispatch($appName, $servRequest, $servResponse);
            $servResponse->sendHttp();
        }
    }

    /**
     * 首次请求
     * @param $server
     * @param $request
     * @param int $protocol
     * @param string $appName
     * @return void
     */
    public function onOpen($server, $request,int $protocol = Server::WEBSOCKET, string $appName = 'websocket') :void{
        $servRequest = new Request($protocol, $request);
        $servResponse = new Response($server, $request->fd);

        App::webhandle()->dispatchWebSocket($appName, $servRequest, $servResponse);

        $servResponse->sendWebSocket();
    }

    /**
     * 静态访问响应
     * @param mixed $request
     * @param mixed $response
     * @return bool
     */
    private function onStatic(mixed $request, mixed $response){
        try{
            $uri = App::rootPath().'/public'.$request->server['request_uri'];
            if(is_dir($uri)) $uri = rtrim($request->server['request_uri'], '/').'/index.html';
            if(file_exists($uri)){
                $ext = pathinfo($uri, PATHINFO_EXTENSION);
                if(in_array($ext, ['js', 'css'])){
                    $response->header('Content-Type', 'text/'.$ext);
                }else{
                    $response->header('Content-Type', mime_content_type($uri));
                }
                $response->end(file_get_contents($uri, false, null, 0, 1000000));
                return true;
            }
        }catch (\Throwable $e){}

        return false;
    }
}