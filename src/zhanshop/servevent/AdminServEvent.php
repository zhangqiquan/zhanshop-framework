<?php

namespace zhanshop\servevent;

use zhanshop\App;
use zhanshop\ClientInfo;
use zhanshop\console\command\Server;
use zhanshop\Log;
use zhanshop\ServEvent;

class AdminServEvent extends ServEvent
{
    /**
     * http请求
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     * @param string $group
     * @return void
     */
    public function onRequest(mixed $request, mixed $response, int $protocol = Server::HTTP, string $routeGroup = 'admin') :void{
        parent::onRequest($request, $response, $protocol, $routeGroup);
    }
}